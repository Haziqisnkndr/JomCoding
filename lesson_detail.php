<?php
// lesson_detail.php - Display individual lesson content
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$user_id = $_SESSION['user_id'];
$lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($lesson_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Get lesson details
$lesson_query = "SELECT l.*, c.course_title, c.course_id 
                 FROM lessons l 
                 JOIN courses c ON l.course_id = c.course_id 
                 WHERE l.lesson_id = ?";
$stmt = $conn->prepare($lesson_query);
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$lesson) {
    header("Location: dashboard.php");
    exit();
}

// Get user info
$user_query = "SELECT * FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if user is premium
$is_premium = ($user['subscription_type'] === 'premium');

// Get course info to check lesson order
$course_id = $lesson['course_id'];
$current_sort_order = $lesson['sort_order'];

// FREE USER ACCESS CONTROL
if(!$is_premium && $current_sort_order > 1) {
    // Free users can only access first lesson
    $_SESSION['access_denied'] = 'This lesson is available for Premium members only. Upgrade to access all lessons!';
    $_SESSION['upgrade_prompt'] = true;
    header("Location: dashboard.php");
    exit();
}

// PREMIUM USER SEQUENTIAL UNLOCK
if($is_premium && $current_sort_order > 1) {
    // Check if previous lesson is completed
    $prev_check_query = "SELECT l.lesson_id, lp.completed 
                         FROM lessons l 
                         LEFT JOIN lesson_progress lp ON l.lesson_id = lp.lesson_id AND lp.student_id = ?
                         WHERE l.course_id = ? AND l.sort_order = ?";
    $stmt = $conn->prepare($prev_check_query);
    $prev_sort = $current_sort_order - 1;
    $stmt->bind_param("iii", $user_id, $course_id, $prev_sort);
    $stmt->execute();
    $prev_lesson_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if(!$prev_lesson_result || $prev_lesson_result['completed'] != 1) {
        $_SESSION['access_denied'] = 'Please complete the previous lesson first before accessing this one.';
        header("Location: dashboard.php");
        exit();
    }
}

// Check if lesson is completed
$progress_query = "SELECT completed FROM lesson_progress WHERE student_id = ? AND lesson_id = ?";
$stmt = $conn->prepare($progress_query);
$stmt->bind_param("ii", $user_id, $lesson_id);
$stmt->execute();
$progress_result = $stmt->get_result();
$is_completed = false;
if($row = $progress_result->fetch_assoc()) {
    $is_completed = $row['completed'] == 1;
}
$stmt->close();

// Get next and previous lessons
$prev_query = "SELECT lesson_id, lesson_title FROM lessons 
               WHERE course_id = ? AND sort_order < ? 
               ORDER BY sort_order DESC LIMIT 1";
$stmt = $conn->prepare($prev_query);
$stmt->bind_param("ii", $lesson['course_id'], $lesson['sort_order']);
$stmt->execute();
$prev_lesson = $stmt->get_result()->fetch_assoc();
$stmt->close();

$next_query = "SELECT lesson_id, lesson_title FROM lessons 
               WHERE course_id = ? AND sort_order > ? 
               ORDER BY sort_order ASC LIMIT 1";
$stmt = $conn->prepare($next_query);
$stmt->bind_param("ii", $lesson['course_id'], $lesson['sort_order']);
$stmt->execute();
$next_lesson = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle mark as complete
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_complete'])) {
    // Check if progress entry exists
    $check_query = "SELECT progress_id FROM lesson_progress WHERE student_id = ? AND lesson_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $lesson_id);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if($exists) {
        // Update existing
        $update_query = "UPDATE lesson_progress SET completed = 1, completed_at = NOW() 
                        WHERE student_id = ? AND lesson_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ii", $user_id, $lesson_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new
        $insert_query = "INSERT INTO lesson_progress (student_id, lesson_id, completed, completed_at) 
                        VALUES (?, ?, 1, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ii", $user_id, $lesson_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Refresh page to show updated status
    header("Location: lesson_detail.php?id=" . $lesson_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lesson['lesson_title']); ?> | JomCoding</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2f57ef;
            --primary-dark: #1d3fc9;
            --accent: #ff4a57;
            --success: #10b981;
            --text: #0f172a;
            --text-light: #64748b;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            font-size: 24px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text);
            font-weight: 600;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            box-shadow: 0 4px 12px rgba(47, 87, 239, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(47, 87, 239, 0.4);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .lesson-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }

        .lesson-header h1 {
            font-size: 42px;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .lesson-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .meta-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .lesson-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .lesson-content h2 {
            font-size: 28px;
            margin-top: 30px;
            margin-bottom: 16px;
            color: var(--text);
        }

        .lesson-content h2:first-child {
            margin-top: 0;
        }

        .lesson-content p {
            margin-bottom: 16px;
            color: var(--text-light);
            font-size: 16px;
            line-height: 1.8;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 16px;
            margin: 30px 0;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        .lesson-navigation {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 40px;
        }

        .nav-button {
            flex: 1;
            padding: 16px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            text-align: center;
            transition: all 0.3s;
        }

        .nav-button.prev {
            background: var(--bg);
            color: var(--text);
            border: 2px solid var(--border);
        }

        .nav-button.prev:hover {
            background: white;
            border-color: var(--primary);
            transform: translateX(-4px);
        }

        .nav-button.next {
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            box-shadow: 0 4px 12px rgba(47, 87, 239, 0.3);
        }

        .nav-button.next:hover {
            transform: translateX(4px);
            box-shadow: 0 6px 16px rgba(47, 87, 239, 0.4);
        }

        .nav-button.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .completion-card {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid var(--success);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        .completion-card h3 {
            color: #065f46;
            margin-bottom: 12px;
            font-size: 24px;
        }

        .completion-card p {
            color: #047857;
            margin-bottom: 0;
        }

        .mark-complete-form {
            text-align: center;
            margin-top: 30px;
            padding: 30px;
            background: var(--bg);
            border-radius: 16px;
        }

        @media (max-width: 768px) {
            .lesson-header h1 {
                font-size: 32px;
            }

            .lesson-content {
                padding: 24px;
            }

            .lesson-navigation {
                flex-direction: column;
            }

            .nav-links {
                flex-wrap: wrap;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <div class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="dashboard.php" class="brand">🚀 JomCoding</a>
                <div class="nav-links">
                    <a href="dashboard.php">← Back to Dashboard</a>
                    <a href="dashboard.php" class="btn btn-primary">My Courses</a>
                </div>
            </div>
        </div>
    </div>

    <!-- LESSON HEADER -->
    <div class="lesson-header">
        <div class="container">
            <h1><?php echo htmlspecialchars($lesson['lesson_title']); ?></h1>
            <p style="font-size: 18px; opacity: 0.9;">
                Course: <?php echo htmlspecialchars($lesson['course_title']); ?>
            </p>
            <div class="lesson-meta">
                <span class="meta-item">📚 Lesson <?php echo $lesson['sort_order']; ?></span>
                <?php if($lesson['duration_minutes']): ?>
                <span class="meta-item">⏱️ <?php echo $lesson['duration_minutes']; ?> minutes</span>
                <?php endif; ?>
                <?php if($is_completed): ?>
                <span class="meta-item">✅ Completed</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container">
        <?php if($is_completed): ?>
        <div class="completion-card">
            <h3>✅ Lesson Completed!</h3>
            <p>Great job! You've completed this lesson.</p>
        </div>
        <?php endif; ?>

        <div class="lesson-content">
            <?php if($lesson['video_url']): ?>
            <div class="video-container">
                <iframe 
                    src="<?php echo htmlspecialchars($lesson['video_url']); ?>" 
                    allowfullscreen
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                </iframe>
            </div>
            <?php endif; ?>

            <h2>Lesson Content</h2>
            <div>
                <?php echo nl2br(htmlspecialchars($lesson['content'])); ?>
            </div>

            <?php if(!$is_completed): ?>
            <div class="mark-complete-form">
                <h3 style="margin-bottom: 16px;">Finished this lesson?</h3>
                <form method="POST">
                    <button type="submit" name="mark_complete" class="btn btn-success" style="padding: 14px 32px; font-size: 16px;">
                        ✓ Mark as Complete
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- LESSON NAVIGATION -->
        <div class="lesson-navigation">
            <?php if($prev_lesson): ?>
                <a href="lesson_detail.php?id=<?php echo $prev_lesson['lesson_id']; ?>" class="nav-button prev">
                    ← Previous: <?php echo htmlspecialchars($prev_lesson['lesson_title']); ?>
                </a>
            <?php else: ?>
                <div class="nav-button prev disabled">← No Previous Lesson</div>
            <?php endif; ?>

            <?php if($next_lesson): ?>
                <a href="lesson_detail.php?id=<?php echo $next_lesson['lesson_id']; ?>" class="nav-button next">
                    Next: <?php echo htmlspecialchars($next_lesson['lesson_title']); ?> →
                </a>
            <?php else: ?>
                <div class="nav-button next disabled">No Next Lesson →</div>
            <?php endif; ?>
        </div>
    </div>

    <div style="height: 60px;"></div>
</body>
</html>