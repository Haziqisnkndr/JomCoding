<?php
// mysql_database.php - MySQL Database Course Dashboard with Progress Tracking
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config.php';

$user_id = $_SESSION['user_id'];
$course_id = 5; // MySQL Database

// Get user info
$user_query = "SELECT * FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if user is premium
$is_premium = ($user['subscription_type'] === 'premium');

// Get course info
$course_query = "SELECT * FROM courses WHERE course_id = ?";
$stmt = $conn->prepare($course_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user's video progress (25% per video)
$video_progress_query = "SELECT video_id, watched FROM video_progress WHERE student_id = ? AND course_id = ?";
$stmt = $conn->prepare($video_progress_query);
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$video_progress_result = $stmt->get_result();
$watched_videos = [];
while($row = $video_progress_result->fetch_assoc()) {
    if($row['watched'] == 1) {
        $watched_videos[$row['video_id']] = $row['watched'];
    }
}
$stmt->close();

// Calculate progress based on videos watched (18.75% per video, 4 videos = 75% max)
$total_videos = 4;
$watched_count = count($watched_videos);
$progress_percentage = ($watched_count / 4) * 75; // 18.75% per video
$progress_display = (fmod($progress_percentage, 1) == 0) ? intval($progress_percentage) : $progress_percentage; // Format: remove .0 for whole numbers

// Get lessons for display (but not for progress calculation)
$lessons_query = "SELECT * FROM lessons WHERE course_id = ? ORDER BY sort_order ASC";
$stmt = $conn->prepare($lessons_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$lessons_result = $stmt->get_result();
$lessons = [];
while($row = $lessons_result->fetch_assoc()) {
    $lessons[] = $row;
}
$stmt->close();

// Get user's lesson progress (for lesson cards display only)
$progress_query = "SELECT lesson_id, completed FROM lesson_progress WHERE student_id = ?";
$stmt = $conn->prepare($progress_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$progress_result = $stmt->get_result();
$completed_lessons = [];
while($row = $progress_result->fetch_assoc()) {
    $completed_lessons[$row['lesson_id']] = $row['completed'];
}
$stmt->close();

$total_lessons = count($lessons);
$completed_lessons_count = count(array_filter($completed_lessons));

// Sample video data for MySQL Database (4 videos - 18.75% each)
$course_videos = [
    [
        'id' => 1,
        'title' => 'MySQL Tutorial for Beginners - Full Course',
        'description' => 'Complete MySQL course by Programming with Mosh covering database fundamentals, queries, and administration.',
        'duration' => '3:10:44',
        'level' => 'Beginner',
        'thumbnail' => '🗄️',
        'url' => 'https://www.youtube.com/embed/7S_tz1z_5bA'
    ],
    [
        'id' => 2,
        'title' => 'MySQL Functions & Operators',
        'description' => 'Master MySQL built-in functions including string, numeric, date functions, and aggregate operations.',
        'duration' => '17:16',
        'level' => 'Intermediate',
        'thumbnail' => '⚡',
        'url' => 'https://www.youtube.com/embed/Cz3WcZLRaWc'
    ],
    [
        'id' => 3,
        'title' => 'MySQL Joins Explained - INNER, LEFT, RIGHT',
        'description' => 'Learn all types of SQL joins with practical examples and understand table relationships.',
        'duration' => '10:26',
        'level' => 'Intermediate',
        'thumbnail' => '🔗',
        'url' => 'https://www.youtube.com/embed/9yeOJ0ZMUYw'
    ],
    [
        'id' => 4,
        'title' => 'MySQL Stored Procedures & Functions',
        'description' => 'Advanced MySQL programming with stored procedures, functi-ons, triggers, and error handling.',
        'duration' => '33:39',
        'level' => 'Advanced',
        'thumbnail' => '🔧',
        'url' => 'https://www.youtube.com/embed/_HgwlGFxuVM'
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySQL Database | JomCoding</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2f57ef;
            --primary-light: #6366f1;
            --success: #10b981;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --sidebar-bg: #ffffff;
            --border: #e2e8f0;
            --hover-bg: #f1f5f9;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .logo {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary), #ff4a57);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: 900;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 800;
        }

        .nav-menu {
            flex: 1;
            padding: 12px;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            margin-bottom: 2px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
            color: var(--text-light);
            font-size: 14px;
            font-weight: 600;
        }

        .nav-item:hover {
            background: var(--hover-bg);
            color: var(--text);
        }

        .nav-item.active {
            background: var(--text);
            color: white;
        }

        .nav-icon {
            font-size: 16px;
            width: 18px;
            text-align: center;
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        /* TOP BAR */
        .top-bar {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title h1 {
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: var(--bg);
            border-radius: 12px;
            cursor: pointer;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary), #ff4a57);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 14px;
        }

        /* CONTENT AREA */
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 24px 32px;
        }

        /* PROGRESS DASHBOARD */
        .progress-dashboard {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 16px;
            padding: 32px;
            color: white;
            margin-bottom: 32px;
            box-shadow: 0 10px 40px rgba(47, 87, 239, 0.2);
        }

        .progress-header {
            margin-bottom: 24px;
        }

        .progress-header h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .progress-header p {
            opacity: 0.9;
            font-size: 15px;
        }

        .progress-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 13px;
            opacity: 0.9;
            font-weight: 600;
        }

        .progress-bar-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            height: 16px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
            border-radius: 12px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 8px;
        }

        .progress-percentage {
            font-size: 11px;
            font-weight: 900;
            color: white;
        }

        /* SECTION HEADERS */
        .section-header {
            margin: 32px 0 20px;
        }

        .section-header h3 {
            font-size: 20px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 4px;
        }

        .section-header p {
            color: var(--text-light);
            font-size: 14px;
        }

        /* VIDEO CAROUSEL */
        .video-carousel-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .video-carousel {
            display: flex;
            gap: 20px;
            overflow-x: hidden;
            scroll-behavior: smooth;
            padding: 20px 0;
        }

        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            background: white;
            border: 2px solid var(--border);
            border-radius: 50%;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .carousel-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-btn.prev {
            left: -24px;
        }

        .carousel-btn.next {
            right: -24px;
        }

        .carousel-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            background: #f5f5f5;
        }

        .carousel-btn:disabled:hover {
            background: #f5f5f5;
            color: inherit;
            transform: translateY(-50%) scale(1);
        }

        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            margin-bottom: 40px;
        }

        .dot {
            width: 10px;
            height: 10px;
            background: var(--border);
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot:hover {
            background: var(--text-light);
        }

        .dot.active {
            background: var(--primary);
            width: 32px;
            border-radius: 5px;
        }

        .video-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid var(--border);
            min-width: 340px;
            max-width: 340px;
            flex-shrink: 0;
            position: relative;
        }

        .video-card.watched {
            border-color: var(--success);
            background: #f0fdf4;
        }

        .video-card.locked {
            opacity: 0.6;
            cursor: not-allowed;
            background: #f9fafb;
        }

        .video-card.locked:hover {
            transform: none;
        }

        .locked-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 12px;
            transition: opacity 0.3s ease;
        }

        .lock-icon {
            font-size: 48px;
            margin-bottom: 12px;
            animation: shake 2s ease-in-out infinite;
        }

        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }

        .lock-text {
            color: white;
            font-weight: 700;
            font-size: 14px;
            text-align: center;
            padding: 0 20px;
        }

        .watched-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--success);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            z-index: 5;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .video-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }

        .video-thumbnail {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            position: relative;
        }

        .video-duration {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }

        .video-play-icon {
            position: absolute;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .video-content {
            padding: 20px;
        }

        .video-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text);
            line-height: 1.4;
        }

        .video-description {
            font-size: 13px;
            color: var(--text-light);
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .video-meta {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-beginner {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-intermediate {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-advanced {
            background: #fee2e2;
            color: #991b1b;
        }

        /* LESSONS GRID - SLIDE STYLE */
        .lessons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .lesson-slide-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .lesson-slide-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            border-color: var(--primary);
        }

        .lesson-slide-card.completed {
            border-color: var(--success);
        }

        .lesson-slide-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .lesson-slide-count {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .lesson-slide-check {
            width: 24px;
            height: 24px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: 900;
        }

        .lesson-slide-icon {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .lesson-slide-content {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .lesson-slide-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .lesson-tag {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 4px;
            background: var(--bg);
            color: var(--text-light);
        }

        .lesson-slide-title {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.4;
            color: var(--text);
            margin: 0;
        }

        .lesson-slide-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .lesson-slide-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-light);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--border);
        }

        .status-dot.completed {
            background: var(--success);
        }

        .status-dot.not-started {
            background: var(--text-light);
        }

        /* VIDEO MODAL */
        .video-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .video-modal.active {
            display: flex;
        }

        .video-modal-content {
            background: white;
            border-radius: 16px;
            max-width: 1100px;
            width: 90%;
            overflow: hidden;
        }

        .video-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .video-modal-header h3 {
            font-size: 18px;
            font-weight: 800;
        }

        .close-modal {
            width: 36px;
            height: 36px;
            border: none;
            background: var(--bg);
            border-radius: 8px;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal:hover {
            background: var(--border);
        }

        .video-modal-body {
            padding: 0;
        }

        .video-player {
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
        }

        .video-modal-footer {
            padding: 20px 24px;
            border-top: 1px solid var(--border);
        }

        /* SLIDE VIEWER MODAL */
        .slide-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .slide-modal.active {
            display: flex;
        }

        .slide-modal-content {
            background: white;
            border-radius: 16px;
            max-width: 1100px;
            width: 95%;
            max-height: 95vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .slide-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .slide-modal-header h3 {
            font-size: 18px;
            font-weight: 800;
            margin: 0;
        }

        .slide-modal-body {
            flex: 1;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            overflow: hidden;
        }

        .slide-viewer-container {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 0;
            min-height: 0;
        }

        .slide-display {
            flex: 1;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            height: 100%;
            min-height: 650px;
        }

        .slide-frame {
            width: 100%;
            height: 650px;
            border: none;
        }

        .slide-nav-btn {
            display: none; /* Hide navigation buttons since each lesson has only 1 slide */
        }
        
        .slide-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            background: var(--bg);
            border-radius: 12px;
        }

        .slide-counter {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
        }

        .slide-dots {
            display: flex;
            gap: 8px;
        }

        .slide-dot {
            width: 8px;
            height: 8px;
            background: var(--border);
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .slide-dot:hover {
            background: var(--text-light);
        }

        .slide-dot.active {
            background: var(--primary);
            width: 24px;
            border-radius: 4px;
        }

        .slide-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .progress-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .video-grid,
            .lessons-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo-text">JomCoding</div>
        </div>

        <div class="nav-menu">
            <a href="../../dashboard.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="mysql_database.php" class="nav-item active">
                <span>Lessons</span>
            </a>
            <a href="quiz.php" class="nav-item">
                <span>Quizzes</span>
            </a>
            <a href="certificates.php" class="nav-item">
                <span>Certificate</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="page-title">
                <h1>MySQL Database Course</h1>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <span style="font-weight: 700; font-size: 14px;"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <button onclick="showLogoutModal()" style="padding: 8px 16px; background: #fee2e2; color: #991b1b; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">Logout</button>
            </div>
        </div>

        <div class="content-area">
            <!-- PROGRESS DASHBOARD -->
            <div class="progress-dashboard">
                <div class="progress-header">
                    <h2>📊 Your Learning Progress</h2>
                    <p>Track your journey through MySQL Database</p>
                </div>

                <div class="progress-stats">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $total_videos; ?></div>
                        <div class="stat-label">Total Videos</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $watched_count; ?></div>
                        <div class="stat-label">Videos Watched</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo ($total_videos - $watched_count); ?></div>
                        <div class="stat-label">Remaining</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $progress_display; ?>%</div>
                        <div class="stat-label">Videos Progress (of 75%)</div>
                    </div>
                </div>

                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: <?php echo $progress_percentage; ?>%;">
                        <?php if($progress_percentage > 10): ?>
                        <span class="progress-percentage"><?php echo $progress_display; ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>

                <p style="margin-top: 12px; font-size: 14px; opacity: 0.9;">
                    <?php if($progress_percentage >= 75): ?>
                        🎉 Amazing! You've watched <?php echo $watched_count; ?> out of <?php echo $total_videos; ?> videos!
                    <?php elseif($progress_percentage >= 45): ?>
                        💪 Great job! Keep watching videos to increase your progress!
                    <?php elseif($progress_percentage > 0): ?>
                        🚀 Good start! Watch more videos to boost your progress!
                    <?php else: ?>
                        🎯 Click on any video below to start learning! Each video = 18.75% progress
                    <?php endif; ?>
                </p>
            </div>

            <!-- VIDEO LESSONS SECTION -->
            <div class="section-header">
                <h3>🎥 Video Lessons</h3>
                <p>Watch comprehensive video tutorials to master programming concepts</p>
            </div>

            <div class="video-carousel-wrapper">
                <button class="carousel-btn prev" onclick="scrollCarousel(-1)" id="prevBtn">
                    <span>←</span>
                </button>
                
                <div class="video-carousel" id="videoCarousel">
                    <?php 
                    foreach($course_videos as $index => $video): 
                        $is_watched = isset($watched_videos[$video['id']]) && $watched_videos[$video['id']] == 1;
                        
                        // Check if previous video is watched (for locking)
                        $is_locked = false;
                        if($index > 0) {
                            $prev_video_id = $course_videos[$index - 1]['id'];
                            $is_locked = !isset($watched_videos[$prev_video_id]) || $watched_videos[$prev_video_id] != 1;
                        }
                        
                        $card_class = 'video-card';
                        if($is_watched) $card_class .= ' watched';
                        if($is_locked) $card_class .= ' locked';
                    ?>
                    <div class="<?php echo $card_class; ?>" onclick="<?php echo $is_locked ? 'showLockedMessage()' : 'openVideoModal(' . $video['id'] . ', ' . $course_id . ')'; ?>">
                        <?php if($is_locked): ?>
                        <div class="locked-overlay">
                            <div class="lock-icon">🔒</div>
                            <div class="lock-text">Watch previous video first</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($is_watched): ?>
                        <div class="watched-badge">✓ Watched</div>
                        <?php endif; ?>
                        
                        <div class="video-thumbnail">
                            <?php echo $video['thumbnail']; ?>
                            <?php if(!$is_locked): ?>
                            <div class="video-play-icon">▶️</div>
                            <?php endif; ?>
                            <div class="video-duration"><?php echo $video['duration']; ?></div>
                        </div>
                        <div class="video-content">
                            <h4 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h4>
                            <p class="video-description"><?php echo htmlspecialchars($video['description']); ?></p>
                            <div class="video-meta">
                                <span class="badge badge-<?php echo strtolower($video['level']); ?>">
                                    <?php echo $video['level']; ?>
                                </span>
                                <?php if($is_watched): ?>
                                <span class="badge" style="background: #d1fae5; color: #065f46;">
                                    ✓ Completed
                                </span>
                                <?php elseif($is_locked): ?>
                                <span class="badge" style="background: #fee2e2; color: #991b1b;">
                                    🔒 Locked
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button class="carousel-btn next" onclick="scrollCarousel(1)" id="nextBtn">
                    <span>→</span>
                </button>
            </div>

            <!-- LESSONS SECTION -->
            <div class="section-header">
                <h3>📖 Course Lessons</h3>
                <p>Complete all lessons to master web development</p>
            </div>

            <?php if(count($lessons) > 0): ?>
            <div class="lessons-grid">
                <?php 
                foreach($lessons as $index => $lesson): 
                    $is_completed = isset($completed_lessons[$lesson['lesson_id']]) && $completed_lessons[$lesson['lesson_id']] == 1;
                    // Only show lessons 1-3 (lessons with slides)
                    if($index >= 1) continue;
                    
                    // Images for lessons - CORRECT FILENAMES
                    $lesson_images = [
                        '../../assets/Bab 8.jpg',  // Lesson 1 - Introduction to Programming (Capital B)
                        '../../assets/bab 5.jpg',  // Lesson 2 - Variables and Data Types (lowercase b)
                        '../../assets/bab 6.jpg'   // Lesson 3 - Operators and Expressions (lowercase b)
                    ];
                    $lesson_image = $lesson_images[$index];
                ?>
                <div class="lesson-slide-card <?php echo $is_completed ? 'completed' : ''; ?>" 
                     onclick="openLessonSlides(<?php echo $lesson['lesson_id']; ?>, '<?php echo htmlspecialchars($lesson['lesson_title'], ENT_QUOTES); ?>')">
                    
                    <div class="lesson-slide-header">
                        <div class="lesson-slide-count"><?php echo $lesson['sort_order']; ?> lessons</div>
                        <?php if($is_completed): ?>
                        <div class="lesson-slide-check">✓</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="lesson-slide-icon" style="background: #f0f0f0;">
                        <img src="<?php echo $lesson_image; ?>" 
                             alt="<?php echo htmlspecialchars($lesson['lesson_title']); ?>" 
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                    </div>
                    
                    <div class="lesson-slide-content">
                        <div class="lesson-slide-tags">
                            <span class="lesson-tag">Programming</span>
                            <span class="lesson-tag">Beginner</span>
                        </div>
                        
                       <h3 class="lesson-slide-title">
                        <?php 
                        // Override title for first lesson to show PHP Fundamentals
                        if($index == 0) {
                            echo "What is MySQL?";
                        } else {
                            echo htmlspecialchars($lesson['lesson_title']); 
                        }
                        ?>
                    </h3>
                        <div class="lesson-slide-meta">
                            <div class="lesson-slide-status">
                                <span>Introduction of MySQL</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 60px; color: var(--text-light);">
                <div style="font-size: 64px; margin-bottom: 16px;">📚</div>
                <h3 style="font-size: 18px; margin-bottom: 8px;">No Lessons Available</h3>
                <p>This course doesn't have any lessons yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- VIDEO MODAL -->
    <div class="video-modal" id="videoModal">
        <div class="video-modal-content">
            <div class="video-modal-header">
                <h3 id="modalVideoTitle">Video Title</h3>
                <button class="close-modal" onclick="closeVideoModal()">✕</button>
            </div>
            <div class="video-modal-body">
                <iframe id="videoPlayer" class="video-player" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            <div class="video-modal-footer">
                <p id="modalVideoDescription" style="color: var(--text-light); font-size: 14px;"></p>
            </div>
        </div>
    </div>

    <!-- SLIDE VIEWER MODAL -->
    <div class="slide-modal" id="slideModal">
        <div class="slide-modal-content">
            <div class="slide-modal-header">
                <h3 id="slideModalTitle">Lesson Slides</h3>
                <button class="close-modal" onclick="closeSlideModal()">✕</button>
            </div>
            <div class="slide-modal-body">
                <div class="slide-viewer-container">
                    <button class="slide-nav-btn prev" onclick="previousSlide()" id="prevSlideBtn">
                        <span>←</span>
                    </button>
                    
                    <div class="slide-display" id="slideDisplay">
                        <iframe id="slideFrame" class="slide-frame"></iframe>
                    </div>
                    
                    <button class="slide-nav-btn next" onclick="nextSlide()" id="nextSlideBtn">
                        <span>→</span>
                    </button>
                </div>
                
                <div class="slide-controls">
                    <div class="slide-counter">
                        <span id="currentSlide">1</span> / <span id="totalSlides">1</span>
                    </div>
                    <div class="slide-dots" id="slideDots"></div>
                    <div class="slide-info">
                        <span style="font-size: 12px; color: var(--text-light);">🔒 Download disabled for copyright protection</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const videos = <?php echo json_encode($course_videos); ?>;
        let currentSlide = 0;
        const carousel = document.getElementById('videoCarousel');
        const dots = document.querySelectorAll('.dot');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        function updateCarousel() {
            const cardWidth = 340;
            const gap = 20;
            const scrollPosition = currentSlide * (cardWidth + gap);
            carousel.scrollTo({
                left: scrollPosition,
                behavior: 'smooth'
            });

            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });

            prevBtn.disabled = currentSlide === 0;
            nextBtn.disabled = currentSlide === videos.length - 1;
        }

        function scrollCarousel(direction) {
            currentSlide += direction;
            currentSlide = Math.max(0, Math.min(currentSlide, videos.length - 1));
            updateCarousel();
        }

        function goToSlide(index) {
            currentSlide = index;
            updateCarousel();
        }

        updateCarousel();

        function showLockedMessage() {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #ef4444, #dc2626);
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(239, 68, 68, 0.3);
                z-index: 10000;
                font-weight: 700;
                animation: slideInRight 0.3s ease;
            `;
            notification.innerHTML = `🔒 Please watch the previous video first!`;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function trackVideoView(videoId, courseId) {
            fetch('../../track_video.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'video_id=' + videoId + '&course_id=' + courseId
            })
            .then(response => response.json())
            .then(data => {
                if(data.success && !data.already_watched) {
                    showProgressNotification(data.progress_percentage);
                    updateProgressDisplay(data.watched_count, data.progress_percentage);
                    unlockNextVideo(videoId, courseId);
                }
            })
            .catch(error => {
                console.error('Error tracking video:', error);
            });
        }

        function unlockNextVideo(currentVideoId, courseId) {
            const videoCards = Array.from(carousel.querySelectorAll('.video-card'));
            
            let currentIndex = -1;
            videoCards.forEach((card, index) => {
                const cardVideoId = videos[index].id;
                if(cardVideoId === currentVideoId) {
                    currentIndex = index;
                    
                    card.classList.add('watched');
                    card.classList.remove('locked');
                    
                    if(!card.querySelector('.watched-badge')) {
                        const badge = document.createElement('div');
                        badge.className = 'watched-badge';
                        badge.textContent = '✓ Watched';
                        card.appendChild(badge);
                    }
                    
                    const videoMeta = card.querySelector('.video-meta');
                    const lockedBadge = videoMeta.querySelector('.badge[style*="fee2e2"]');
                    if(lockedBadge) {
                        lockedBadge.remove();
                    }
                    
                    const completedBadge = videoMeta.querySelector('.badge[style*="d1fae5"]');
                    if(!completedBadge) {
                        const newCompletedBadge = document.createElement('span');
                        newCompletedBadge.className = 'badge';
                        newCompletedBadge.style.background = '#d1fae5';
                        newCompletedBadge.style.color = '#065f46';
                        newCompletedBadge.textContent = '✓ Completed';
                        videoMeta.appendChild(newCompletedBadge);
                    }
                }
            });
            
            if(currentIndex >= 0 && currentIndex < videoCards.length - 1) {
                const nextIndex = currentIndex + 1;
                const nextCard = videoCards[nextIndex];
                const nextVideoId = videos[nextIndex].id;
                
                if(nextCard.classList.contains('locked')) {
                    nextCard.classList.remove('locked');
                    nextCard.style.opacity = '1';
                    nextCard.style.cursor = 'pointer';
                    
                    const overlay = nextCard.querySelector('.locked-overlay');
                    if(overlay) {
                        overlay.style.opacity = '0';
                        setTimeout(() => overlay.remove(), 300);
                    }
                    
                    nextCard.onclick = function() {
                        openVideoModal(nextVideoId, courseId);
                    };
                    
                    const nextVideoMeta = nextCard.querySelector('.video-meta');
                    const nextLockedBadge = nextVideoMeta.querySelector('.badge[style*="fee2e2"]');
                    if(nextLockedBadge) {
                        nextLockedBadge.remove();
                    }
                    
                    showUnlockNotification(nextIndex + 1);
                }
            }
        }

        function showProgressNotification(percentage) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981, #34d399);
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
                z-index: 10000;
                font-weight: 700;
                animation: slideInRight 0.3s ease;
            `;
            notification.innerHTML = `🎉 +15% Progress! You're now at ${percentage}%`;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function showUnlockNotification(videoNumber) {
            setTimeout(() => {
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 80px;
                    right: 20px;
                    background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    color: white;
                    padding: 16px 24px;
                    border-radius: 12px;
                    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
                    z-index: 10000;
                    font-weight: 700;
                    animation: slideInRight 0.3s ease;
                `;
                notification.innerHTML = `🔓 Video ${videoNumber} Unlocked!`;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }, 500);
        }

        function updateProgressDisplay(watchedCount, percentage) {
            document.querySelectorAll('.stat-number')[1].textContent = watchedCount;
            document.querySelectorAll('.stat-number')[2].textContent = (5 - watchedCount);
            document.querySelectorAll('.stat-number')[3].textContent = percentage + '%';
            
            const progressBar = document.querySelector('.progress-bar-fill');
            progressBar.style.width = percentage + '%';
            if(percentage > 10) {
                const percentageText = progressBar.querySelector('.progress-percentage');
                if(percentageText) {
                    percentageText.textContent = percentage + '%';
                }
            }
            
            const progressMsg = document.querySelector('.progress-dashboard p');
            if(percentage >= 75) {
                progressMsg.innerHTML = '🎉 Amazing! You\'ve watched ' + watchedCount + ' out of 5 videos!';
            } else if(percentage >= 45) {
                progressMsg.innerHTML = '💪 Great job! Keep watching videos to increase your progress!';
            } else if(percentage > 0) {
                progressMsg.innerHTML = '🚀 Good start! Watch more videos to boost your progress!';
            }
        }

        function openVideoModal(videoId, courseId) {
            const video = videos.find(v => v.id === videoId);
            if (!video) return;

            trackVideoView(videoId, courseId);

            document.getElementById('modalVideoTitle').textContent = video.title;
            document.getElementById('modalVideoDescription').textContent = video.description;
            document.getElementById('videoPlayer').src = video.url + '?autoplay=1';
            document.getElementById('videoModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeVideoModal() {
            document.getElementById('videoModal').classList.remove('active');
            document.getElementById('videoPlayer').src = '';
            document.body.style.overflow = 'auto';
        }

        document.getElementById('videoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideoModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideoModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (!document.getElementById('videoModal').classList.contains('active')) {
                if (e.key === 'ArrowLeft') {
                    scrollCarousel(-1);
                } else if (e.key === 'ArrowRight') {
                    scrollCarousel(1);
                }
            }
        });

        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // SLIDE VIEWER FUNCTIONALITY
        let currentSlideIndex = 0;
        let totalSlidesCount = 1; // Each lesson has 1 slide
        let currentLessonId = 0;

        const lessonSlides = {
            1: ['/jomcoding/slides/slide7.pdf'],  // Introduction to Programming
            2: ['/jomcoding/slides/slide5.pdf'],  // Variables and Data Types
            3: ['/jomcoding/slides/slide6.pdf']   // Operators and Expressions
        };

     function openLessonSlides(lessonId, lessonTitle) {
    currentLessonId = lessonId;
    currentSlideIndex = 0;
    
    // Smart mapping: Maps any lesson ID to slides based on sort_order
    const allLessons = <?php echo json_encode($lessons); ?>;
    const lessonIndex = allLessons.findIndex(l => l.lesson_id == lessonId);
    
    if(lessonIndex === -1 || lessonIndex > 2) {
        console.log('No slides for this lesson');
        return;
    }
    
    // Fix: Use absolute path from web root
    const slideFiles = ['/jomcoding/slides/slide9.pdf'];
    lessonSlides[lessonId] = [slideFiles[lessonIndex]];
    totalSlidesCount = 1;
    
    document.getElementById('slideModalTitle').textContent = lessonTitle;
    document.getElementById('slideModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    
    const dotsContainer = document.getElementById('slideDots');
    dotsContainer.innerHTML = '';
    const dot = document.createElement('span');
    dot.className = 'slide-dot active';
    dot.onclick = () => goToSlideNumber(0);
    dotsContainer.appendChild(dot);
    
    loadSlide(0);
}

        function showNoSlidesMessage(lessonTitle) {
            // Do nothing - silently ignore clicks on lessons without slides
            return;
        }

        function closeSlideModal() {
            // Mark lesson as completed before closing
            if(currentLessonId > 0) {
                markLessonCompleted(currentLessonId);
            }
            
            document.getElementById('slideModal').classList.remove('active');
            document.getElementById('slideFrame').src = '';
            document.body.style.overflow = 'auto';
        }

        function markLessonCompleted(lessonId) {
            fetch('mark_lesson_complete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `lesson_id=${lessonId}&course_id=3`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    console.log('Lesson marked as completed');
                    // Reload page to update the UI
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function loadSlide(index) {
            currentSlideIndex = index;
            
            document.getElementById('currentSlide').textContent = index + 1;
            document.getElementById('totalSlides').textContent = totalSlidesCount;
            
            const dots = document.querySelectorAll('.slide-dot');
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
            
            document.getElementById('prevSlideBtn').disabled = index === 0;
            document.getElementById('nextSlideBtn').disabled = index === totalSlidesCount - 1;
            
            if(lessonSlides[currentLessonId]) {
                loadPDFSlide(lessonSlides[currentLessonId][index]);
            }
        }

        function loadPDFSlide(pdfUrl) {
            const slideFrame = document.getElementById('slideFrame');
            slideFrame.src = pdfUrl + '#toolbar=0&navpanes=0&scrollbar=0&view=FitH';
        }

        function previousSlide() {
            if(currentSlideIndex > 0) {
                loadSlide(currentSlideIndex - 1);
            }
        }

        function nextSlide() {
            if(currentSlideIndex < totalSlidesCount - 1) {
                loadSlide(currentSlideIndex + 1);
            }
        }

        function goToSlideNumber(index) {
            if(index >= 0 && index < totalSlidesCount) {
                loadSlide(index);
            }
        }

        // Keyboard navigation for slides
        document.addEventListener('keydown', function(e) {
            const slideModal = document.getElementById('slideModal');
            if (slideModal.classList.contains('active')) {
                if (e.key === 'ArrowLeft') {
                    previousSlide();
                } else if (e.key === 'ArrowRight') {
                    nextSlide();
                } else if (e.key === 'Escape') {
                    closeSlideModal();
                }
            }
        });

        // Click outside to close slide modal
        document.getElementById('slideModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSlideModal();
            }
        });

        // Logout Modal Functions
        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function hideLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function confirmLogout() {
            window.location.href = '../../logout.php';
        }

        // Click outside logout modal to close
        document.addEventListener('DOMContentLoaded', function() {
            const logoutModal = document.getElementById('logoutModal');
            if(logoutModal) {
                logoutModal.addEventListener('click', function(e) {
                    if (e.target === this) hideLogoutModal();
                });
            }
            
            // Close modals with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    hideLogoutModal();
                }
            });
        });
    </script>

    <!-- LOGOUT MODAL -->
    <div style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center;" id="logoutModal">
        <div style="background: #fff; border-radius: 24px; padding: 40px; max-width: 440px; width: 90%; box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);">
            <div style="width: 80px; height: 80px; margin: 0 auto 24px; border-radius: 50%; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); display: flex; align-items: center; justify-content: center; font-size: 40px;">
                👋
            </div>
            <h2 style="font-size: 28px; font-weight: 900; margin: 0 0 12px; text-align: center; color: var(--text);">Logout Confirmation</h2>
            <p style="font-size: 16px; color: var(--text-light); text-align: center; margin: 0 0 32px; line-height: 1.6;">
                Are you sure you want to logout? You'll need to login again to access your course progress.
            </p>
            <div style="display: flex; gap: 12px;">
                <button onclick="hideLogoutModal()" style="flex: 1; padding: 14px 24px; border: 2px solid var(--border); border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; background: var(--bg); color: var(--text); transition: all 0.3s ease; font-family: inherit;">
                    No, Stay
                </button>
                <button onclick="confirmLogout()" style="flex: 1; padding: 14px 24px; border: none; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; background: linear-gradient(135deg, #ff4a57 0%, #ff6b76 100%); color: #fff; box-shadow: 0 8px 20px rgba(255, 74, 87, 0.3); transition: all 0.3s ease; font-family: inherit;">
                    Yes, Logout
                </button>
            </div>
        </div>
    </div>

</body>
</html>