<?php
// instructor.php - Instructor Dashboard with Student Analytics
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

// Initialize analytics data
$analytics = [
    'total_students' => 0,
    'students_with_certificates' => 0,
    'students_without_quiz_attempts' => 0,
    'students_without_video_watches' => 0,
    'total_quiz_attempts' => 0,
    'avg_quiz_score' => 0,
    'courses' => [],
    'recent_quiz_results' => [],
    'student_list' => []
];

// Get analytics data (user is already authenticated)
// ============================================
// 1. TOTAL STUDENTS (excluding admin)
// ============================================
    $total_students_query = "SELECT COUNT(*) as total FROM user WHERE username != 'admin' AND role = 'student'";
    $total_result = mysqli_query($conn, $total_students_query);
    if($total_result && $row = mysqli_fetch_assoc($total_result)) {
        $analytics['total_students'] = $row['total'];
    }
    
    // ============================================
    // 2. STUDENTS WHO EARNED CERTIFICATES
    // A student earns a certificate when they complete ALL lessons in a course
    // ============================================
    
    // Get total lessons per course
    $course_lessons_query = "SELECT course_id, COUNT(*) as total_lessons FROM lessons GROUP BY course_id";
    $course_lessons_result = mysqli_query($conn, $course_lessons_query);
    $course_total_lessons = [];
    while($row = mysqli_fetch_assoc($course_lessons_result)) {
        $course_total_lessons[$row['course_id']] = $row['total_lessons'];
    }
    
    // Find students who completed all lessons in at least one course
    $certificate_query = "
        SELECT DISTINCT lp.student_id, u.username, u.full_name, u.email,
               COUNT(DISTINCT lp.lesson_id) as completed_lessons,
               l.course_id,
               c.course_title
        FROM lesson_progress lp
        INNER JOIN lessons l ON lp.lesson_id = l.lesson_id
        INNER JOIN courses c ON l.course_id = c.course_id
        INNER JOIN user u ON lp.student_id = u.user_id
        WHERE lp.completed = 1 AND u.username != 'admin'
        GROUP BY lp.student_id, l.course_id
    ";
    $certificate_result = mysqli_query($conn, $certificate_query);
    $students_with_certs = [];
    while($row = mysqli_fetch_assoc($certificate_result)) {
        $course_id = $row['course_id'];
        $completed = $row['completed_lessons'];
        $required = $course_total_lessons[$course_id] ?? 0;
        
        // If student completed all lessons in this course
        if($required > 0 && $completed >= $required) {
            $students_with_certs[$row['student_id']] = [
                'username' => $row['username'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'course_title' => $row['course_title']
            ];
        }
    }
    $analytics['students_with_certificates'] = count($students_with_certs);
    
    // ============================================
    // 3. STUDENTS WHO DIDN'T ANSWER QUIZZES YET
    // ============================================
    $students_no_quiz_query = "
        SELECT u.user_id, u.username, u.full_name, u.email
        FROM user u
        LEFT JOIN quiz_attempts qa ON u.user_id = qa.student_id
        WHERE u.username != 'admin' AND u.role = 'student'
        GROUP BY u.user_id
        HAVING COUNT(qa.attempt_id) = 0
    ";
    $students_no_quiz_result = mysqli_query($conn, $students_no_quiz_query);
    $students_no_quiz = [];
    while($row = mysqli_fetch_assoc($students_no_quiz_result)) {
        $students_no_quiz[] = $row;
    }
    $analytics['students_without_quiz_attempts'] = count($students_no_quiz);
    
    // ============================================
    // 4. STUDENTS WHO DIDN'T WATCH VIDEOS YET
    // ============================================
    $students_no_video_query = "
        SELECT u.user_id, u.username, u.full_name, u.email
        FROM user u
        LEFT JOIN video_progress vp ON u.user_id = vp.student_id AND vp.watched = 1
        WHERE u.username != 'admin' AND u.role = 'student'
        GROUP BY u.user_id
        HAVING COUNT(vp.id) = 0
    ";
    $students_no_video_result = mysqli_query($conn, $students_no_video_query);
    $students_no_video = [];
    while($row = mysqli_fetch_assoc($students_no_video_result)) {
        $students_no_video[] = $row;
    }
    $analytics['students_without_video_watches'] = count($students_no_video);
    
    // ============================================
    // 5. QUIZ RESULTS AND STATISTICS
    // ============================================
    
    // Total quiz attempts
    $total_attempts_query = "SELECT COUNT(*) as total FROM quiz_attempts";
    $total_attempts_result = mysqli_query($conn, $total_attempts_query);
    if($total_attempts_result && $row = mysqli_fetch_assoc($total_attempts_result)) {
        $analytics['total_quiz_attempts'] = $row['total'];
    }
    
    // Average quiz score (percentage of correct answers)
    $avg_score_query = "
        SELECT 
            SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as avg_score
        FROM quiz_attempts
    ";
    $avg_score_result = mysqli_query($conn, $avg_score_query);
    if($avg_score_result && $row = mysqli_fetch_assoc($avg_score_result)) {
        $analytics['avg_quiz_score'] = round($row['avg_score'] ?? 0, 1);
    }
    
    // Recent quiz results (last 50 attempts)
    $recent_quiz_query = "
        SELECT 
            qa.attempt_id,
            u.username,
            u.full_name,
            c.course_title,
            q.question,
            qa.selected_answer,
            qa.is_correct,
            qa.points_earned,
            qa.attempted_at
        FROM quiz_attempts qa
        INNER JOIN user u ON qa.student_id = u.user_id
        INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
        INNER JOIN courses c ON q.course_id = c.course_id
        WHERE u.username != 'admin'
        ORDER BY qa.attempted_at DESC
        LIMIT 50
    ";
    $recent_quiz_result = mysqli_query($conn, $recent_quiz_query);
    while($row = mysqli_fetch_assoc($recent_quiz_result)) {
        $analytics['recent_quiz_results'][] = $row;
    }
    
    // ============================================
    // 6. COURSE-WISE STATISTICS
    // ============================================
    $course_stats_query = "
        SELECT 
            c.course_id,
            c.course_title,
            COUNT(DISTINCT vp.student_id) as students_watching,
            COUNT(DISTINCT qa.student_id) as students_taking_quiz,
            COUNT(DISTINCT CASE WHEN lp.completed = 1 THEN lp.student_id END) as students_completing
        FROM courses c
        LEFT JOIN video_progress vp ON c.course_id = vp.course_id AND vp.watched = 1
        LEFT JOIN quizzes q ON c.course_id = q.course_id
        LEFT JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id
        LEFT JOIN lessons l ON c.course_id = l.course_id
        LEFT JOIN lesson_progress lp ON l.lesson_id = lp.lesson_id
        GROUP BY c.course_id
        ORDER BY c.course_id
    ";
    $course_stats_result = mysqli_query($conn, $course_stats_query);
    while($row = mysqli_fetch_assoc($course_stats_result)) {
        $analytics['courses'][] = $row;
    }
    
    // ============================================
    // 7. DETAILED STUDENT LIST WITH ACTIVITY
    // ============================================
    $student_list_query = "
        SELECT 
            u.user_id,
            u.username,
            u.full_name,
            u.email,
            u.subscription_type,
            u.created_at,
            COUNT(DISTINCT vp.id) as videos_watched,
            COUNT(DISTINCT qa.quiz_id) as quizzes_taken,
            COUNT(DISTINCT CASE WHEN lp.completed = 1 THEN lp.lesson_id END) as lessons_completed
        FROM user u
        LEFT JOIN video_progress vp ON u.user_id = vp.student_id AND vp.watched = 1
        LEFT JOIN quiz_attempts qa ON u.user_id = qa.student_id
        LEFT JOIN lesson_progress lp ON u.user_id = lp.student_id
        WHERE u.username != 'admin' AND u.role = 'student'
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
    ";
    $student_list_result = mysqli_query($conn, $student_list_query);
    while($row = mysqli_fetch_assoc($student_list_result)) {
        $analytics['student_list'][] = $row;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - JomCoding LMS</title>
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

        /* Login Screen Styles */
        .login-screen {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .login-icon {
            text-align: center;
            font-size: 80px;
            color: #667eea;
            margin-bottom: 20px;
        }

        .login-container h2 {
            text-align: center;
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .login-container > p {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
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

        .badge-premium {
            background: linear-gradient(135deg, #a044ff 0%, #6a3093 100%);
            color: white;
        }

        .badge-free {
            background: #e9ecef;
            color: #6c757d;
        }

        .badge-correct {
            background: #d4edda;
            color: #155724;
        }

        .badge-incorrect {
            background: #f8d7da;
            color: #721c24;
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
                    <li><a href="instructor.php" class="active"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                    <li><a href="analytics_instructor.php"><i class="fas fa-chart-line"></i> <span>Analytics</span></a></li>
                </ul>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Top Navigation -->
                <nav class="top-nav">
                    <h1 class="page-title">Student Analytics Dashboard</h1>
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
                                    <div class="stat-value"><?php echo number_format($analytics['students_with_certificates']); ?></div>
                                    <div class="stat-label">Students with Certificates</div>
                                </div>
                                <div class="stat-icon green">
                                    <i class="fas fa-certificate"></i>
                                </div>
                            </div>
                            <?php if($analytics['total_students'] > 0): ?>
                            <div class="stat-change positive">
                                <i class="fas fa-percentage"></i>
                                <?php echo round(($analytics['students_with_certificates'] / $analytics['total_students']) * 100, 1); ?>% completion rate
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo number_format($analytics['students_without_quiz_attempts']); ?></div>
                                    <div class="stat-label">Students Not Taking Quizzes</div>
                                </div>
                                <div class="stat-icon orange">
                                    <i class="fas fa-question-circle"></i>
                                </div>
                            </div>
                            <?php if($analytics['total_students'] > 0): ?>
                            <div class="stat-change negative">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?php echo round(($analytics['students_without_quiz_attempts'] / $analytics['total_students']) * 100, 1); ?>% inactive on quizzes
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo number_format($analytics['students_without_video_watches']); ?></div>
                                    <div class="stat-label">Students Not Watching Videos</div>
                                </div>
                                <div class="stat-icon red">
                                    <i class="fas fa-video-slash"></i>
                                </div>
                            </div>
                            <?php if($analytics['total_students'] > 0): ?>
                            <div class="stat-change negative">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?php echo round(($analytics['students_without_video_watches'] / $analytics['total_students']) * 100, 1); ?>% haven't started videos
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo number_format($analytics['total_quiz_attempts']); ?></div>
                                    <div class="stat-label">Total Quiz Attempts</div>
                                </div>
                                <div class="stat-icon purple">
                                    <i class="fas fa-pen-to-square"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo $analytics['avg_quiz_score']; ?>%</div>
                                    <div class="stat-label">Average Quiz Score</div>
                                </div>
                                <div class="stat-icon teal">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                            </div>
                            <div class="stat-change <?php echo $analytics['avg_quiz_score'] >= 70 ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-trophy"></i>
                                <?php echo $analytics['avg_quiz_score'] >= 70 ? 'Good performance!' : 'Needs improvement'; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Course-Wise Statistics -->
                    <div class="table-container">
                        <div class="table-header">
                            <h3>📚 Course-Wise Student Activity</h3>
                        </div>

                        <?php if(count($analytics['courses']) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Course ID</th>
                                        <th>Course Title</th>
                                        <th>Students Watching</th>
                                        <th>Students Taking Quiz</th>
                                        <th>Students Completing</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($analytics['courses'] as $course): ?>
                                        <tr>
                                            <td><strong>#<?php echo $course['course_id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                                            <td>
                                                <span class="badge badge-free">
                                                    <i class="fas fa-eye"></i>
                                                    <?php echo $course['students_watching']; ?> students
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-free">
                                                    <i class="fas fa-pencil"></i>
                                                    <?php echo $course['students_taking_quiz']; ?> students
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-premium">
                                                    <i class="fas fa-check-circle"></i>
                                                    <?php echo $course['students_completing']; ?> students
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-book"></i>
                                <p>No course data available</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Quiz Results -->
                    <div class="table-container">
                        <div class="table-header">
                            <h3>📝 Recent Quiz Results (Last 50 Attempts)</h3>
                        </div>

                        <?php if(count($analytics['recent_quiz_results']) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Question</th>
                                        <th>Answer</th>
                                        <th>Result</th>
                                        <th>Points</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($analytics['recent_quiz_results'] as $result): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($result['full_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($result['username']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($result['course_title']); ?></td>
                                            <td><?php echo substr(htmlspecialchars($result['question']), 0, 50) . '...'; ?></td>
                                            <td><strong><?php echo $result['selected_answer']; ?></strong></td>
                                            <td>
                                                <?php if($result['is_correct']): ?>
                                                    <span class="badge badge-correct">
                                                        <i class="fas fa-check"></i> Correct
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-incorrect">
                                                        <i class="fas fa-times"></i> Incorrect
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?php echo $result['points_earned']; ?></strong> pts</td>
                                            <td><?php echo date('M d, Y H:i', strtotime($result['attempted_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-clipboard-question"></i>
                                <p>No quiz attempts yet</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Student Activity Table -->
                    <div class="table-container">
                        <div class="table-header">
                            <h3>👥 All Students - Activity Overview</h3>
                        </div>

                        <?php if(count($analytics['student_list']) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Subscription</th>
                                        <th>Videos Watched</th>
                                        <th>Quizzes Taken</th>
                                        <th>Lessons Completed</th>
                                        <th>Joined Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($analytics['student_list'] as $student): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($student['username']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $student['subscription_type']; ?>">
                                                    <?php if($student['subscription_type'] === 'premium'): ?>
                                                        <i class="fas fa-crown"></i>
                                                    <?php endif; ?>
                                                    <?php echo ucfirst($student['subscription_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-free">
                                                    <i class="fas fa-video"></i>
                                                    <?php echo $student['videos_watched']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-free">
                                                    <i class="fas fa-question"></i>
                                                    <?php echo $student['quizzes_taken']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $student['lessons_completed'] > 0 ? 'correct' : 'incorrect'; ?>">
                                                    <i class="fas fa-check-circle"></i>
                                                    <?php echo $student['lessons_completed']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-user-slash"></i>
                                <p>No students found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
</body>
</html>