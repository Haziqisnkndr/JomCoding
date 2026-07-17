<?php
// analytics_instructor.php - Instructor Analytics Dashboard
session_start();
require_once '../config.php';

// Check if user is authenticated as instructor
if(!isset($_SESSION['admin_verified']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: admin_manage_subscriptions.php");
    exit();
}

// Handle logout
if(isset($_GET['logout'])) {
    unset($_SESSION['admin_verified']);
    unset($_SESSION['user_role']);
    unset($_SESSION['selected_role']);
    header("Location: admin_manage_subscriptions.php");
    exit();
}

// Get filter from URL (default to 'all')
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$valid_filters = ['all', 'week', 'month', 'quarter', 'year', 'custom'];
if(!in_array($filter, $valid_filters)) {
    $filter = 'all';
}

// Get custom date range if set
$custom_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$custom_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Get course filter
$course_filter = isset($_GET['course']) ? intval($_GET['course']) : 0;

// Initialize analytics data
$analytics = [
    'total_students' => 0,
    'active_students' => 0,
    'new_students' => 0,
    'students_with_certificates' => 0,
    'total_quiz_attempts' => 0,
    'avg_quiz_score' => 0,
    'total_videos_watched' => 0,
    'total_lessons_completed' => 0,
    'chart_labels' => [],
    'student_signup_data' => [],
    'video_watch_data' => [],
    'quiz_attempt_data' => [],
    'course_completion_data' => [],
    'courses' => [],
    'top_performers' => [],
    'struggling_students' => [],
    'recent_activities' => []
];

// Build WHERE clause based on filter
$where_clause = "";
$period_name = "All Time";

if($filter === 'custom' && $custom_start_date && $custom_end_date) {
    $where_clause = " AND DATE(created_at) BETWEEN '$custom_start_date' AND '$custom_end_date'";
    $period_name = date('M d, Y', strtotime($custom_start_date)) . ' - ' . date('M d, Y', strtotime($custom_end_date));
} else if($filter !== 'all') {
    $days_map = [
        'week' => ['days' => 7, 'name' => 'Last 7 Days'],
        'month' => ['days' => 30, 'name' => 'Last 30 Days'],
        'quarter' => ['days' => 90, 'name' => 'Last 90 Days'],
        'year' => ['days' => 365, 'name' => 'Last 12 Months']
    ];
    
    if(isset($days_map[$filter])) {
        $days = $days_map[$filter]['days'];
        $where_clause = " AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)";
        $period_name = $days_map[$filter]['name'];
    }
}

// Course filter clause
$course_where = "";
if($course_filter > 0) {
    $course_where = " AND course_id = $course_filter";
}

// ============================================
// 1. TOTAL STUDENTS
// ============================================
$total_students_query = "SELECT COUNT(*) as total FROM user WHERE username != 'admin' AND role = 'student'";
$total_result = mysqli_query($conn, $total_students_query);
if($total_result && $row = mysqli_fetch_assoc($total_result)) {
    $analytics['total_students'] = $row['total'];
}

// ============================================
// 2. ACTIVE STUDENTS (in selected period)
// Students who watched videos or took quizzes in the period
// ============================================

// Build where clauses for different tables
$video_where = str_replace('created_at', 'watched_at', $where_clause);
$quiz_where = str_replace('created_at', 'attempted_at', $where_clause);

$active_students_query = "
    SELECT COUNT(DISTINCT user_id) as active FROM (
        SELECT DISTINCT student_id as user_id FROM video_progress 
        WHERE watched = 1 $video_where $course_where
        UNION
        SELECT DISTINCT qa.student_id as user_id FROM quiz_attempts qa
        INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE 1=1 $quiz_where $course_where
    ) as active_users
";
$active_result = mysqli_query($conn, $active_students_query);
if($active_result && $row = mysqli_fetch_assoc($active_result)) {
    $analytics['active_students'] = $row['active'];
}

// ============================================
// 3. NEW STUDENTS (in selected period)
// ============================================
$new_students_query = "
    SELECT COUNT(*) as new_students 
    FROM user 
    WHERE username != 'admin' AND role = 'student' $where_clause
";
$new_result = mysqli_query($conn, $new_students_query);
if($new_result && $row = mysqli_fetch_assoc($new_result)) {
    $analytics['new_students'] = $row['new_students'];
}

// ============================================
// 4. STUDENTS WITH CERTIFICATES
// ============================================
$course_lessons_query = "SELECT course_id, COUNT(*) as total_lessons FROM lessons GROUP BY course_id";
$course_lessons_result = mysqli_query($conn, $course_lessons_query);
$course_total_lessons = [];
while($row = mysqli_fetch_assoc($course_lessons_result)) {
    $course_total_lessons[$row['course_id']] = $row['total_lessons'];
}

$certificate_query = "
    SELECT DISTINCT lp.student_id, COUNT(DISTINCT lp.lesson_id) as completed_lessons, l.course_id
    FROM lesson_progress lp
    INNER JOIN lessons l ON lp.lesson_id = l.lesson_id
    INNER JOIN user u ON lp.student_id = u.user_id
    WHERE lp.completed = 1 AND u.username != 'admin'
    " . ($course_filter > 0 ? " AND l.course_id = $course_filter" : "") . "
    GROUP BY lp.student_id, l.course_id
";
$certificate_result = mysqli_query($conn, $certificate_query);
$students_with_certs = [];
while($row = mysqli_fetch_assoc($certificate_result)) {
    $course_id = $row['course_id'];
    $completed = $row['completed_lessons'];
    $required = $course_total_lessons[$course_id] ?? 0;
    
    if($required > 0 && $completed >= $required) {
        $students_with_certs[$row['student_id']] = true;
    }
}
$analytics['students_with_certificates'] = count($students_with_certs);

// ============================================
// 5. QUIZ STATISTICS
// ============================================
$quiz_stats_query = "
    SELECT 
        COUNT(*) as total_attempts,
        SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as avg_score
    FROM quiz_attempts qa
    INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
    WHERE 1=1 $quiz_where $course_where
";
$quiz_stats_result = mysqli_query($conn, $quiz_stats_query);
if($quiz_stats_result && $row = mysqli_fetch_assoc($quiz_stats_result)) {
    $analytics['total_quiz_attempts'] = $row['total_attempts'] ?? 0;
    $analytics['avg_quiz_score'] = round($row['avg_score'] ?? 0, 1);
}

// ============================================
// 6. VIDEO WATCH STATISTICS
// ============================================
$video_stats_query = "
    SELECT COUNT(*) as total_watched
    FROM video_progress vp
    WHERE vp.watched = 1 $video_where $course_where
";
$video_stats_result = mysqli_query($conn, $video_stats_query);
if($video_stats_result && $row = mysqli_fetch_assoc($video_stats_result)) {
    $analytics['total_videos_watched'] = $row['total_watched'];
}

// ============================================
// 7. LESSONS COMPLETED
// ============================================
$lesson_where = str_replace('created_at', 'completed_at', $where_clause);
$lessons_query = "
    SELECT COUNT(*) as total_completed
    FROM lesson_progress lp
    INNER JOIN lessons l ON lp.lesson_id = l.lesson_id
    WHERE lp.completed = 1 $lesson_where
    " . ($course_filter > 0 ? " AND l.course_id = $course_filter" : "");
$lessons_result = mysqli_query($conn, $lessons_query);
if($lessons_result && $row = mysqli_fetch_assoc($lessons_result)) {
    $analytics['total_lessons_completed'] = $row['total_completed'];
}

// ============================================
// 8. CHART DATA - STUDENT SIGNUPS
// ============================================
if($filter === 'all' || $filter === 'year') {
    $chart_interval = ($filter === 'all') ? '365 DAY' : '365 DAY';
    $signup_chart_query = "
        SELECT DATE_FORMAT(created_at, '%Y-%m') as period, COUNT(*) as count 
        FROM user 
        WHERE username != 'admin' AND role = 'student'
        AND created_at >= DATE_SUB(NOW(), INTERVAL $chart_interval)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY period ASC
    ";
    
    $signup_result = mysqli_query($conn, $signup_chart_query);
    $signup_data = [];
    while($row = mysqli_fetch_assoc($signup_result)) {
        $signup_data[$row['period']] = $row['count'];
    }
    
    $months_back = 12;
    for($i = $months_back - 1; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $analytics['chart_labels'][] = date('M Y', strtotime($month . '-01'));
        $analytics['student_signup_data'][] = isset($signup_data[$month]) ? $signup_data[$month] : 0;
    }
    
} else if($filter === 'week' || $filter === 'month') {
    $days = ($filter === 'week') ? 7 : 30;
    $signup_chart_query = "
        SELECT DATE(created_at) as period, COUNT(*) as count 
        FROM user 
        WHERE username != 'admin' AND role = 'student'
        AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
        GROUP BY DATE(created_at)
        ORDER BY period ASC
    ";
    
    $signup_result = mysqli_query($conn, $signup_chart_query);
    $signup_data = [];
    while($row = mysqli_fetch_assoc($signup_result)) {
        $signup_data[$row['period']] = $row['count'];
    }
    
    for($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $analytics['chart_labels'][] = date('M d', strtotime($date));
        $analytics['student_signup_data'][] = isset($signup_data[$date]) ? $signup_data[$date] : 0;
    }
} else if($filter === 'custom' && $custom_start_date && $custom_end_date) {
    $signup_chart_query = "
        SELECT DATE(created_at) as period, COUNT(*) as count 
        FROM user 
        WHERE username != 'admin' AND role = 'student'
        AND DATE(created_at) BETWEEN '$custom_start_date' AND '$custom_end_date'
        GROUP BY DATE(created_at)
        ORDER BY period ASC
    ";
    
    $signup_result = mysqli_query($conn, $signup_chart_query);
    while($row = mysqli_fetch_assoc($signup_result)) {
        $analytics['chart_labels'][] = date('M d', strtotime($row['period']));
        $analytics['student_signup_data'][] = $row['count'];
    }
}

// ============================================
// 9. CHART DATA - VIDEO WATCHES
// ============================================
$video_watch_query = "";
if($filter === 'all' || $filter === 'year') {
    $video_watch_query = "
        SELECT DATE_FORMAT(watched_at, '%Y-%m') as period, COUNT(*) as count
        FROM video_progress
        WHERE watched = 1 AND watched_at IS NOT NULL
        AND watched_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)
        $course_where
        GROUP BY DATE_FORMAT(watched_at, '%Y-%m')
        ORDER BY period ASC
    ";
} else if($filter === 'week' || $filter === 'month') {
    $days = ($filter === 'week') ? 7 : 30;
    $video_watch_query = "
        SELECT DATE(watched_at) as period, COUNT(*) as count
        FROM video_progress
        WHERE watched = 1 AND watched_at IS NOT NULL
        AND watched_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
        $course_where
        GROUP BY DATE(watched_at)
        ORDER BY period ASC
    ";
} else if($filter === 'custom' && $custom_start_date && $custom_end_date) {
    $video_watch_query = "
        SELECT DATE(watched_at) as period, COUNT(*) as count
        FROM video_progress
        WHERE watched = 1 AND watched_at IS NOT NULL
        AND DATE(watched_at) BETWEEN '$custom_start_date' AND '$custom_end_date'
        $course_where
        GROUP BY DATE(watched_at)
        ORDER BY period ASC
    ";
}

if($video_watch_query) {
    $video_result = mysqli_query($conn, $video_watch_query);
    $video_data = [];
    while($row = mysqli_fetch_assoc($video_result)) {
        $video_data[$row['period']] = $row['count'];
    }
    
    // Fill in data for chart labels
    foreach($analytics['chart_labels'] as $label) {
        if($filter === 'all' || $filter === 'year') {
            $date_obj = DateTime::createFromFormat('M Y', $label);
            $key = $date_obj ? $date_obj->format('Y-m') : '';
        } else {
            $date_obj = DateTime::createFromFormat('M d', $label);
            $current_year = date('Y');
            $key = $date_obj ? $current_year . '-' . $date_obj->format('m-d') : '';
        }
        $analytics['video_watch_data'][] = isset($video_data[$key]) ? $video_data[$key] : 0;
    }
}

// ============================================
// 10. CHART DATA - QUIZ ATTEMPTS
// ============================================
$quiz_chart_query = "";
if($filter === 'all' || $filter === 'year') {
    $quiz_chart_query = "
        SELECT DATE_FORMAT(qa.attempted_at, '%Y-%m') as period, COUNT(*) as count
        FROM quiz_attempts qa
        INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE qa.attempted_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)
        $course_where
        GROUP BY DATE_FORMAT(qa.attempted_at, '%Y-%m')
        ORDER BY period ASC
    ";
} else if($filter === 'week' || $filter === 'month') {
    $days = ($filter === 'week') ? 7 : 30;
    $quiz_chart_query = "
        SELECT DATE(qa.attempted_at) as period, COUNT(*) as count
        FROM quiz_attempts qa
        INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE qa.attempted_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
        $course_where
        GROUP BY DATE(qa.attempted_at)
        ORDER BY period ASC
    ";
} else if($filter === 'custom' && $custom_start_date && $custom_end_date) {
    $quiz_chart_query = "
        SELECT DATE(qa.attempted_at) as period, COUNT(*) as count
        FROM quiz_attempts qa
        INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE DATE(qa.attempted_at) BETWEEN '$custom_start_date' AND '$custom_end_date'
        $course_where
        GROUP BY DATE(qa.attempted_at)
        ORDER BY period ASC
    ";
}

if($quiz_chart_query) {
    $quiz_chart_result = mysqli_query($conn, $quiz_chart_query);
    $quiz_data = [];
    while($row = mysqli_fetch_assoc($quiz_chart_result)) {
        $quiz_data[$row['period']] = $row['count'];
    }
    
    foreach($analytics['chart_labels'] as $label) {
        if($filter === 'all' || $filter === 'year') {
            $date_obj = DateTime::createFromFormat('M Y', $label);
            $key = $date_obj ? $date_obj->format('Y-m') : '';
        } else {
            $date_obj = DateTime::createFromFormat('M d', $label);
            $current_year = date('Y');
            $key = $date_obj ? $current_year . '-' . $date_obj->format('m-d') : '';
        }
        $analytics['quiz_attempt_data'][] = isset($quiz_data[$key]) ? $quiz_data[$key] : 0;
    }
}

// ============================================
// 11. GET ALL COURSES FOR FILTER
// ============================================
$courses_query = "SELECT course_id, course_title FROM courses ORDER BY course_title";
$courses_result = mysqli_query($conn, $courses_query);
while($row = mysqli_fetch_assoc($courses_result)) {
    $analytics['courses'][] = $row;
}

// ============================================
// 12. TOP PERFORMERS (Highest quiz scores)
// ============================================
$top_performers_query = "
    SELECT 
        u.user_id,
        u.username,
        u.full_name,
        COUNT(DISTINCT qa.quiz_id) as quizzes_taken,
        SUM(CASE WHEN qa.is_correct = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as score,
        SUM(qa.points_earned) as total_points
    FROM user u
    INNER JOIN quiz_attempts qa ON u.user_id = qa.student_id
    INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
    WHERE u.username != 'admin'
    " . ($course_filter > 0 ? " AND q.course_id = $course_filter" : "") . "
    GROUP BY u.user_id
    HAVING COUNT(DISTINCT qa.quiz_id) >= 3
    ORDER BY score DESC, total_points DESC
    LIMIT 10
";
$top_result = mysqli_query($conn, $top_performers_query);
while($row = mysqli_fetch_assoc($top_result)) {
    $analytics['top_performers'][] = $row;
}

// ============================================
// 13. STRUGGLING STUDENTS (Low quiz scores)
// ============================================
$struggling_query = "
    SELECT 
        u.user_id,
        u.username,
        u.full_name,
        COUNT(DISTINCT qa.quiz_id) as quizzes_taken,
        SUM(CASE WHEN qa.is_correct = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as score,
        COUNT(DISTINCT vp.id) as videos_watched
    FROM user u
    INNER JOIN quiz_attempts qa ON u.user_id = qa.student_id
    INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
    LEFT JOIN video_progress vp ON u.user_id = vp.student_id AND vp.watched = 1
    WHERE u.username != 'admin'
    " . ($course_filter > 0 ? " AND q.course_id = $course_filter" : "") . "
    GROUP BY u.user_id
    HAVING COUNT(DISTINCT qa.quiz_id) >= 3 AND score < 60
    ORDER BY score ASC
    LIMIT 10
";
$struggling_result = mysqli_query($conn, $struggling_query);
while($row = mysqli_fetch_assoc($struggling_result)) {
    $analytics['struggling_students'][] = $row;
}

// ============================================
// 14. RECENT ACTIVITIES
// ============================================
$recent_activities_query = "
    (SELECT 
        'video' as type,
        u.full_name,
        u.username,
        c.course_title,
        vp.watched_at as activity_time,
        NULL as details
    FROM video_progress vp
    INNER JOIN user u ON vp.student_id = u.user_id
    INNER JOIN courses c ON vp.course_id = c.course_id
    WHERE vp.watched = 1 AND u.username != 'admin'
    " . ($course_filter > 0 ? " AND vp.course_id = $course_filter" : "") . "
    ORDER BY vp.watched_at DESC
    LIMIT 20)
    
    UNION ALL
    
    (SELECT 
        'quiz' as type,
        u.full_name,
        u.username,
        c.course_title,
        qa.attempted_at as activity_time,
        CONCAT(IF(qa.is_correct, '✓', '✗'), ' ', qa.points_earned, ' pts') as details
    FROM quiz_attempts qa
    INNER JOIN user u ON qa.student_id = u.user_id
    INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
    INNER JOIN courses c ON q.course_id = c.course_id
    WHERE u.username != 'admin'
    " . ($course_filter > 0 ? " AND q.course_id = $course_filter" : "") . "
    ORDER BY qa.attempted_at DESC
    LIMIT 20)
    
    ORDER BY activity_time DESC
    LIMIT 30
";
$recent_result = mysqli_query($conn, $recent_activities_query);
while($row = mysqli_fetch_assoc($recent_result)) {
    $analytics['recent_activities'][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Analytics - JomCoding LMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
        }

        /* Dashboard Layout */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #2d5016 0%, #1a3d0a 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .sidebar-subtitle {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #2d5016;
        }

        .sidebar-menu a i {
            width: 24px;
            margin-right: 12px;
            font-size: 16px;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            background: #f5f7fa;
        }

        /* Top Navigation */
        .top-nav {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .page-title {
            font-size: 24px;
            color: #2c3e50;
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2d5016 0%, #1a3d0a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .user-email {
            font-size: 12px;
            color: #7f8c8d;
        }

        .logout-btn {
            padding: 10px 20px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 40px;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .filter-header {
            margin-bottom: 20px;
        }

        .filter-header h3 {
            font-size: 18px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #7f8c8d;
            margin-bottom: 6px;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 2px solid #e0e6ed;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #2d5016;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .apply-filter-btn {
            padding: 10px 24px;
            background: #2d5016;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .apply-filter-btn:hover {
            background: #1a3d0a;
        }

        .reset-filter-btn {
            padding: 10px 24px;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .reset-filter-btn:hover {
            background: #7f8c8d;
        }

        .custom-dates {
            display: none;
        }

        .custom-dates.active {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #2d5016 0%, #1a3d0a 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #27ae60 0%, #229954 100%); }
        .stat-icon.purple { background: linear-gradient(135deg, #16a085 0%, #138d75 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #239b56 0%, #1e8449 100%); }
        .stat-icon.red { background: linear-gradient(135deg, #28b463 0%, #229954 100%); }
        .stat-icon.teal { background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%); }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 500;
        }

        .stat-change {
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-change.positive {
            color: #27ae60;
        }

        .stat-change.negative {
            color: #e74c3c;
        }

        /* Chart Container */
        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .chart-header {
            margin-bottom: 25px;
        }

        .chart-header h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .chart-header p {
            font-size: 14px;
            color: #7f8c8d;
        }

        canvas {
            max-height: 400px;
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e0e6ed;
        }

        .table-header h3 {
            font-size: 18px;
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-video {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-quiz {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .no-data {
            padding: 60px 20px;
            text-align: center;
            color: #7f8c8d;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .sidebar-title, .sidebar-subtitle {
                display: none;
            }

            .sidebar-menu a span {
                display: none;
            }

            .main-content {
                margin-left: 70px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-nav {
                padding: 15px 20px;
            }

            .user-details {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Dashboard Layout -->
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-title">INSTRUCTOR Panel</div>
                <div class="sidebar-subtitle">JomCoding LMS</div>
            </div>

            <ul class="sidebar-menu">
                <li><a href="instructor.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li><a href="analytics_instructor.php" class="active"><i class="fas fa-chart-line"></i> <span>Analytics</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <nav class="top-nav">
                <h1 class="page-title">📊 Student Analytics & Insights</h1>
                <div class="user-info">
                    <div class="user-avatar">IN</div>
                    <div class="user-details">
                        <span class="user-name">Instructor</span>
                        <span class="user-email">instructor@jomcoding.edu</span>
                    </div>
                    <a href="?logout" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-header">
                        <h3><i class="fas fa-filter"></i> Filter Analytics Data</h3>
                    </div>
                    
                    <form method="GET" id="filterForm" action="analytics_instructor.php">
                        <div class="filter-controls">
                            <div class="filter-group">
                                <label>Time Period</label>
                                <select name="filter" id="filterSelect" onchange="handleFilterChange()">
                                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                                    <option value="week" <?php echo $filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="month" <?php echo $filter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                                    <option value="quarter" <?php echo $filter === 'quarter' ? 'selected' : ''; ?>>Last 90 Days</option>
                                    <option value="year" <?php echo $filter === 'year' ? 'selected' : ''; ?>>Last 12 Months</option>
                                    <option value="custom" <?php echo $filter === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Course Filter</label>
                                <select name="course" onchange="handleCourseChange()">
                                    <option value="0" <?php echo $course_filter === 0 ? 'selected' : ''; ?>>All Courses</option>
                                    <?php foreach($analytics['courses'] as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>" 
                                                <?php echo $course_filter === $course['course_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-buttons">
                                <button type="submit" class="apply-filter-btn" id="applyBtn">
                                    <i class="fas fa-check"></i> Apply Filter
                                </button>
                                <a href="analytics_instructor.php" class="reset-filter-btn">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                        
                        <div class="custom-dates <?php echo $filter === 'custom' ? 'active' : ''; ?>" id="customDates">
                            <div class="filter-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" value="<?php echo htmlspecialchars($custom_start_date); ?>">
                            </div>
                            <div class="filter-group">
                                <label>End Date</label>
                                <input type="date" name="end_date" value="<?php echo htmlspecialchars($custom_end_date); ?>">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($analytics['total_students']); ?></div>
                                <div class="stat-label">Total Students</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($analytics['active_students']); ?></div>
                                <div class="stat-label">Active Students</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                        <div class="stat-change positive">
                            <i class="fas fa-chart-line"></i>
                            <?php echo $analytics['total_students'] > 0 ? round(($analytics['active_students'] / $analytics['total_students']) * 100, 1) : 0; ?>% activity rate
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($analytics['new_students']); ?></div>
                                <div class="stat-label">New Students (<?php echo $period_name; ?>)</div>
                            </div>
                            <div class="stat-icon purple">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($analytics['students_with_certificates']); ?></div>
                                <div class="stat-label">Certificates Earned</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-certificate"></i>
                            </div>
                        </div>
                        <?php if($analytics['total_students'] > 0): ?>
                        <div class="stat-change positive">
                            <i class="fas fa-trophy"></i>
                            <?php echo round(($analytics['students_with_certificates'] / $analytics['total_students']) * 100, 1); ?>% completion rate
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($analytics['total_videos_watched']); ?></div>
                                <div class="stat-label">Videos Watched</div>
                            </div>
                            <div class="stat-icon teal">
                                <i class="fas fa-video"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo $analytics['avg_quiz_score']; ?>%</div>
                                <div class="stat-label">Average Quiz Score</div>
                            </div>
                            <div class="stat-icon red">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                        </div>
                        <div class="stat-change <?php echo $analytics['avg_quiz_score'] >= 70 ? 'positive' : 'negative'; ?>">
                            <i class="fas fa-<?php echo $analytics['avg_quiz_score'] >= 70 ? 'smile' : 'frown'; ?>"></i>
                            <?php echo $analytics['avg_quiz_score'] >= 70 ? 'Good performance!' : 'Needs improvement'; ?>
                        </div>
                    </div>
                </div>

                <!-- Multi-Line Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>📈 Student Activity Trends</h3>
                        <p>Overview of student signups, video watches, and quiz attempts (<?php echo $period_name; ?>)</p>
                    </div>
                    <canvas id="activityChart"></canvas>
                </div>

                <!-- Top Performers Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>🏆 Top Performers</h3>
                    </div>

                    <?php if(count($analytics['top_performers']) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Student</th>
                                    <th>Quizzes Taken</th>
                                    <th>Average Score</th>
                                    <th>Total Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach($analytics['top_performers'] as $performer): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: <?php echo $rank <= 3 ? '#f39c12' : '#7f8c8d'; ?>;">
                                                #<?php echo $rank++; ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($performer['full_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($performer['username']); ?></small>
                                        </td>
                                        <td><?php echo $performer['quizzes_taken']; ?> quizzes</td>
                                        <td>
                                            <strong style="color: #27ae60; font-size: 16px;">
                                                <?php echo round($performer['score'], 1); ?>%
                                            </strong>
                                        </td>
                                        <td><strong><?php echo $performer['total_points']; ?></strong> pts</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-trophy"></i>
                            <p>No quiz data available yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Struggling Students Table -->
                <?php if(count($analytics['struggling_students']) > 0): ?>
                <div class="table-container">
                    <div class="table-header">
                        <h3>⚠️ Students Needing Help (Score < 60%)</h3>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Quizzes Taken</th>
                                <th>Average Score</th>
                                <th>Videos Watched</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($analytics['struggling_students'] as $student): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($student['username']); ?></small>
                                    </td>
                                    <td><?php echo $student['quizzes_taken']; ?> quizzes</td>
                                    <td>
                                        <strong style="color: #e74c3c; font-size: 16px;">
                                            <?php echo round($student['score'], 1); ?>%
                                        </strong>
                                    </td>
                                    <td><?php echo $student['videos_watched']; ?> videos</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Recent Activities -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>🔔 Recent Student Activities</h3>
                    </div>

                    <?php if(count($analytics['recent_activities']) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Details</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($analytics['recent_activities'] as $activity): ?>
                                    <tr>
                                        <td>
                                            <?php if($activity['type'] === 'video'): ?>
                                                <span class="badge badge-video">
                                                    <i class="fas fa-video"></i> Video
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-quiz">
                                                    <i class="fas fa-question-circle"></i> Quiz
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($activity['username']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($activity['course_title']); ?></td>
                                        <td><?php echo $activity['details'] ?: '-'; ?></td>
                                        <td>
                                            <?php 
                                            $time_diff = time() - strtotime($activity['activity_time']);
                                            if($time_diff < 3600) {
                                                echo round($time_diff / 60) . ' mins ago';
                                            } else if($time_diff < 86400) {
                                                echo round($time_diff / 3600) . ' hours ago';
                                            } else {
                                                echo date('M d, H:i', strtotime($activity['activity_time']));
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-clock"></i>
                            <p>No recent activities</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Chart.js Script -->
    <script>
        const ctx = document.getElementById('activityChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($analytics['chart_labels']); ?>,
                datasets: [
                    {
                        label: 'Student Signups',
                        data: <?php echo json_encode($analytics['student_signup_data']); ?>,
                        borderColor: 'rgb(45, 80, 22)',
                        backgroundColor: 'rgba(45, 80, 22, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Videos Watched',
                        data: <?php echo json_encode($analytics['video_watch_data']); ?>,
                        borderColor: 'rgb(39, 174, 96)',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Quiz Attempts',
                        data: <?php echo json_encode($analytics['quiz_attempt_data']); ?>,
                        borderColor: 'rgb(22, 160, 133)',
                        backgroundColor: 'rgba(22, 160, 133, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Handle filter change - auto-submit for non-custom filters
        function handleFilterChange() {
            const filterSelect = document.getElementById('filterSelect');
            const customDates = document.getElementById('customDates');
            
            if(filterSelect.value === 'custom') {
                // Show custom date fields, don't auto-submit
                customDates.classList.add('active');
            } else {
                // Hide custom dates and auto-submit
                customDates.classList.remove('active');
                document.getElementById('filterForm').submit();
            }
        }

        // Handle course change - auto-submit
        function handleCourseChange() {
            const filterSelect = document.getElementById('filterSelect');
            
            // Only auto-submit if not on custom range or if custom dates are filled
            if(filterSelect.value !== 'custom') {
                document.getElementById('filterForm').submit();
            }
        }

        // Initialize - show/hide custom dates on page load
        function toggleCustomDates() {
            const filterSelect = document.getElementById('filterSelect');
            const customDates = document.getElementById('customDates');
            
            if(filterSelect.value === 'custom') {
                customDates.classList.add('active');
            } else {
                customDates.classList.remove('active');
            }
        }
        
        // Call on page load
        toggleCustomDates();
    </script>
</body>
</html>