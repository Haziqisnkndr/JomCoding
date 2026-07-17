<?php
// quiz.php - Programming Basics Quiz Page
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config.php';

$user_id = $_SESSION['user_id'];
$course_id = 1; // Programming Basics

// Get user info
$user_query = "SELECT * FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle quiz submission$stmt->close();

// Check if user is premium
$is_premium = ($user['subscription_type'] === 'premium');

// Redirect free users
if(!$is_premium) {
    $_SESSION['access_denied'] = 'Quizzes are only available for Premium members. Upgrade to test your knowledge!';
    $_SESSION['upgrade_prompt'] = true;
    header("Location: ../../dashboard.php");
    exit();
}

// COOLDOWN & PASS SYSTEM
$pass_threshold = 60;
$total_points_query = "SELECT SUM(points) as total_possible_points FROM quizzes WHERE course_id = ?";
$stmt = $conn->prepare($total_points_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$points_result = $stmt->get_result()->fetch_assoc();
$stmt->close();
$total_possible_points = $points_result['total_possible_points'] ?? 0;

$performance_query = "SELECT SUM(best_scores.max_points) as total_score
FROM (SELECT qa.quiz_id, MAX(qa.points_earned) as max_points FROM quiz_attempts qa
INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id WHERE qa.student_id = ? AND q.course_id = ? GROUP BY qa.quiz_id) as best_scores";
$stmt = $conn->prepare($performance_query);
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$performance = $stmt->get_result()->fetch_assoc();
$stmt->close();

$user_score = $performance['total_score'] ?? 0;
$user_percentage = ($total_possible_points > 0) ? ($user_score / $total_possible_points) * 100 : 0;
$has_passed = $user_percentage >= $pass_threshold;

$last_attempt_time = $_SESSION['last_quiz_attempt_' . $course_id] ?? null;
$cooldown_hours = 1;
$is_in_cooldown = false;
$cooldown_remaining = 0;

if ($last_attempt_time && !$has_passed) {
    $current_timestamp = time();
    $cooldown_end = $last_attempt_time + ($cooldown_hours * 3600);
    if ($current_timestamp < $cooldown_end) {
        $is_in_cooldown = true;
        $cooldown_remaining = $cooldown_end - $current_timestamp;
    }
}
$quiz_blocked = !$has_passed && $is_in_cooldown;

// Handle quiz submission (only if not blocked)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_quiz']) && !$quiz_blocked) {
    $_SESSION['last_quiz_attempt_' . $course_id] = time();
    $score = 0;
    $total_points = 0;
    $correct_count = 0;
    $total_questions = 0;
    
    foreach($_POST as $key => $value) {
        if(strpos($key, 'question_') === 0) {
            $quiz_id = str_replace('question_', '', $key);
            $selected_answer = $value;
            
            $check_query = "SELECT correct_answer, points FROM quizzes WHERE quiz_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("i", $quiz_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $is_correct = ($selected_answer == $result['correct_answer']) ? 1 : 0;
            $points_earned = $is_correct ? $result['points'] : 0;
            
            $insert_query = "INSERT INTO quiz_attempts (student_id, quiz_id, selected_answer, is_correct, points_earned) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iisii", $user_id, $quiz_id, $selected_answer, $is_correct, $points_earned);
            $stmt->execute();
            $stmt->close();
            
            $score += $points_earned;
            $total_points += $result['points'];
            if($is_correct) $correct_count++;
            $total_questions++;
        }
    }
    
    $percentage = round(($score / $total_points) * 100, 2);
    header("Location: quiz_results.php?score=$score&total=$total_points&correct=$correct_count&questions=$total_questions&percentage=$percentage");
    exit();
}

// Get all quizzes for Programming Basics
$quiz_query = "SELECT * FROM quizzes WHERE course_id = ? ORDER BY quiz_id ASC";
$stmt = $conn->prepare($quiz_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$quizzes_result = $stmt->get_result();
$quizzes = [];
while($row = $quizzes_result->fetch_assoc()) {
    $quizzes[] = $row;
}
$stmt->close();

// Get user's quiz statistics (only for Programming Basics course)
$stats_query = "SELECT 
    COUNT(DISTINCT best_scores.quiz_id) as attempted_quizzes,
    SUM(best_scores.max_points) as total_score,
    SUM(CASE WHEN best_scores.max_points = best_scores.question_points THEN 1 ELSE 0 END) as correct_answers,
    COUNT(DISTINCT best_scores.quiz_id) as total_attempts
FROM (
    SELECT qa.quiz_id, MAX(qa.points_earned) as max_points, q.points as question_points
    FROM quiz_attempts qa
    INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
    WHERE qa.student_id = ? AND q.course_id = ?
    GROUP BY qa.quiz_id, q.points
) as best_scores";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calculate quiz percentage (25% of total course grade)
$quiz_raw_percentage = ($total_possible_points > 0 && $stats['total_score'] > 0) 
    ? ($stats['total_score'] / $total_possible_points) * 100 
    : 0;
$quiz_percentage = round(($quiz_raw_percentage / 100) * 25, 1);

$total_questions = count($quizzes);
$certificates_link = 'certificates.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programming Basics Quiz | JomCoding</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2f57ef;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --sidebar-bg: #ffffff;
            --border: #e2e8f0;
            --hover-bg: #f1f5f9;
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

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: var(--bg);
            border-radius: 12px;
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
            padding: 32px;
        }

        /* QUIZ HEADER */
        .quiz-header {
            background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
            border-radius: 16px;
            padding: 32px;
            color: white;
            margin-bottom: 32px;
            box-shadow: 0 10px 40px rgba(47, 87, 239, 0.2);
        }

        .quiz-header h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .quiz-header p {
            opacity: 0.9;
            font-size: 15px;
            margin-bottom: 24px;
        }

        .quiz-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            font-weight: 600;
        }

        /* QUIZ FORM */
        .quiz-container {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .quiz-question {
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 2px solid var(--border);
        }

        .quiz-question:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .question-number {
            background: var(--primary);
            color: white;
            font-weight: 800;
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 20px;
        }

        .difficulty-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
        }

        .difficulty-badge.easy {
            background: #d1fae5;
            color: #065f46;
        }

        .difficulty-badge.normal {
            background: #dbeafe;
            color: #1e40af;
        }

        .question-text {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .option {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            border: 2px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .option:hover {
            border-color: var(--primary);
            background: #f0f4ff;
        }

        .option input[type="radio"] {
            margin-right: 12px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .option label {
            cursor: pointer;
            flex: 1;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }

        .option input[type="radio"]:checked + label {
            color: var(--primary);
        }

        /* SUBMIT BUTTON */
        .submit-section {
            margin-top: 40px;
            text-align: center;
            padding: 32px;
            background: var(--bg);
            border-radius: 16px;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
            border: none;
            padding: 16px 48px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(16, 185, 129, 0.4);
        }

        .submit-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* INSTRUCTIONS */
        .instructions {
            background: #fef3c7;
            border-left: 4px solid var(--warning);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 32px;
        }

        .instructions h3 {
            font-size: 16px;
            font-weight: 800;
            color: #92400e;
            margin-bottom: 12px;
        }

        .instructions ul {
            margin-left: 20px;
            color: #78350f;
            font-size: 14px;
            line-height: 1.8;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .quiz-stats {
                grid-template-columns: repeat(2, 1fr);
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
            <a href="programming_basics.php" class="nav-item">
                <span>Lessons</span>
            </a>
            <a href="quiz.php" class="nav-item active">
                <span>Quizzes</span>
            </a>
            <a href="<?php echo $certificates_link; ?>" class="nav-item">
                <span>Certificate</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="page-title">
                <h1>Quiz</h1>
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

        <!-- CONTENT AREA -->

            <?php if($has_passed): ?>
                <!-- PASSED MESSAGE -->
                <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border: 2px solid #10b981; padding: 32px; border-radius: 16px; margin-bottom: 24px; text-align: center;">
                    <h3 style="font-size: 24px; font-weight: 900; color: #065f46; margin-bottom: 12px;">🎉 Congratulations! You've Passed!</h3>
                    <p style="color: #064e3b; font-size: 16px;">You achieved <?php echo round($user_percentage, 1); ?>% - You don't need to retake this quiz.</p>
                </div>
            <?php elseif($quiz_blocked): ?>
                <!-- COOLDOWN WARNING -->
                <div style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border: 2px solid #ef4444; padding: 32px; border-radius: 16px; margin-bottom: 24px; text-align: center;">
                    <h3 style="font-size: 24px; font-weight: 900; color: #991b1b; margin-bottom: 12px;">⏳ Quiz Cooldown Active</h3>
                    <p style="color: #7f1d1d; font-size: 16px; margin-bottom: 20px;">You need to wait before retaking the quiz. Please come back after the timer ends.</p>
                    <div style="font-size: 48px; font-weight: 900; color: #dc2626; margin: 20px 0;" id="cooldownTimer">
                        <?php 
                            $hours = floor($cooldown_remaining / 3600);
                            $minutes = floor(($cooldown_remaining % 3600) / 60);
                            $seconds = $cooldown_remaining % 60;
                            echo sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                        ?>
                    </div>
                    <p style="color: #7f1d1d; font-size: 16px;">You scored <?php echo round($user_percentage, 1); ?>% (need <?php echo $pass_threshold; ?>% to pass)</p>
                </div>
            <?php else: ?>

        <div class="content-area">
            <!-- QUIZ HEADER -->
            <div class="quiz-header">
                <h2>🎯 Test Your Knowledge</h2>
                <p>Challenge yourself with <?php echo $total_questions; ?> questions covering all Programming Basics topics</p>
                
                <div class="quiz-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_questions; ?></div>
                        <div class="stat-label">Total Questions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['attempted_quizzes'] ?? 0; ?></div>
                        <div class="stat-label">Attempted</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['correct_answers'] ?? 0; ?></div>
                        <div class="stat-label">Correct Answers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $quiz_percentage; ?>%</div>
                        <div class="stat-label">Quiz Progress (of 25%)</div>
                    </div>
                </div>
            </div>

            <!-- INSTRUCTIONS -->
            <div class="instructions">
                <h3>📋 Instructions</h3>
                <ul>
                    <li>Read each question carefully before selecting your answer</li>
                    <li>Select one answer for each question</li>
                    <li>You can scroll through all questions before submitting</li>
                    <li>Click "Submit Quiz" when you're done to see your results</li>
                </ul>
            </div>

            <!-- QUIZ FORM -->
            <form method="POST" action="" id="quizForm">
                <div class="quiz-container">
                    <?php foreach($quizzes as $index => $quiz): ?>
                    <div class="quiz-question">
                        <div class="question-header">
                            <span class="question-number">Question <?php echo $index + 1; ?></span>
                            
                        </div>
                        
                        <div class="question-text">
                            <?php echo htmlspecialchars($quiz['question']); ?>
                        </div>
                        
                        <div class="options">
                            <div class="option">
                                <input type="radio" 
                                       name="question_<?php echo $quiz['quiz_id']; ?>" 
                                       id="q<?php echo $quiz['quiz_id']; ?>_a" 
                                       value="A" 
                                       required>
                                <label for="q<?php echo $quiz['quiz_id']; ?>_a">
                                    <strong>A.</strong> <?php echo htmlspecialchars($quiz['option_a']); ?>
                                </label>
                            </div>
                            
                            <div class="option">
                                <input type="radio" 
                                       name="question_<?php echo $quiz['quiz_id']; ?>" 
                                       id="q<?php echo $quiz['quiz_id']; ?>_b" 
                                       value="B">
                                <label for="q<?php echo $quiz['quiz_id']; ?>_b">
                                    <strong>B.</strong> <?php echo htmlspecialchars($quiz['option_b']); ?>
                                </label>
                            </div>
                            
                            <div class="option">
                                <input type="radio" 
                                       name="question_<?php echo $quiz['quiz_id']; ?>" 
                                       id="q<?php echo $quiz['quiz_id']; ?>_c" 
                                       value="C">
                                <label for="q<?php echo $quiz['quiz_id']; ?>_c">
                                    <strong>C.</strong> <?php echo htmlspecialchars($quiz['option_c']); ?>
                                </label>
                            </div>
                            
                            <div class="option">
                                <input type="radio" 
                                       name="question_<?php echo $quiz['quiz_id']; ?>" 
                                       id="q<?php echo $quiz['quiz_id']; ?>_d" 
                                       value="D">
                                <label for="q<?php echo $quiz['quiz_id']; ?>_d">
                                    <strong>D.</strong> <?php echo htmlspecialchars($quiz['option_d']); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- SUBMIT SECTION -->
                <div class="submit-section">
                    <p style="margin-bottom: 16px; color: var(--text-light); font-size: 14px;">
                        Make sure you've answered all questions before submitting!
                    </p>
                    <button type="submit" name="submit_quiz" class="submit-btn">
                        🚀 Submit Quiz
                    </button>
                </div>
            </form>
        </div>
    </div>

            <?php endif; ?>
        </div>
    </div>

    <script>
        // Countdown timer
        <?php if($quiz_blocked): ?>
        let remainingSeconds = <?php echo $cooldown_remaining; ?>;
        function updateTimer() {
            if (remainingSeconds <= 0) {
                location.reload();
                return;
            }
            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;
            document.getElementById('cooldownTimer').textContent = 
                String(hours).padStart(2, '0') + ':' + 
                String(minutes).padStart(2, '0') + ':' + 
                String(seconds).padStart(2, '0');
            remainingSeconds--;
        }
        setInterval(updateTimer, 1000);
        <?php endif; ?>

        // Confirm before submit
        document.getElementById('quizForm')?.addEventListener('submit', function(e) {
            const confirmed = confirm('Are you sure you want to submit your quiz? You cannot change your answers after submission.');
            if(!confirmed) {
                e.preventDefault();
                return false;
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

        document.addEventListener('DOMContentLoaded', function() {
            const logoutModal = document.getElementById('logoutModal');
            if(logoutModal) {
                logoutModal.addEventListener('click', function(e) {
                    if (e.target === this) hideLogoutModal();
                });
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('logoutModal').style.display === 'flex') {
                hideLogoutModal();
            }
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
                Are you sure you want to logout? You'll need to login again to access your quiz progress.
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