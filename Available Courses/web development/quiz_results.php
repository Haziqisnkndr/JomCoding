<?php
// quiz_results.php - Quiz Results Page
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config.php';

$user_id = $_SESSION['user_id'];
$course_id = 2; // Web Development

// Get results from URL
$score = isset($_GET['score']) ? intval($_GET['score']) : 0;
$total_points = isset($_GET['total']) ? intval($_GET['total']) : 0;
$correct_count = isset($_GET['correct']) ? intval($_GET['correct']) : 0;
$total_questions = isset($_GET['questions']) ? intval($_GET['questions']) : 0;
$percentage = isset($_GET['percentage']) ? floatval($_GET['percentage']) : 0;

// Get user info
$user_query = "SELECT * FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check pass/fail status
$pass_threshold = 60;
$has_passed = $percentage >= $pass_threshold;

// Check cooldown status
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

// Determine grade and message
$grade = '';
$grade_color = '';
$message = '';

if($percentage >= 90) {
    $grade = 'A+';
    $grade_color = '#10b981';
    $message = 'Outstanding! You have excellent knowledge of Web Development!';
} elseif($percentage >= 80) {
    $grade = 'A';
    $grade_color = '#10b981';
    $message = 'Great job! You have a strong understanding of the concepts!';
} elseif($percentage >= 70) {
    $grade = 'B';
    $grade_color = '#3b82f6';
    $message = 'Good work! Keep practicing to master all concepts!';
} elseif($percentage >= 60) {
    $grade = 'C';
    $grade_color = '#f59e0b';
    $message = 'You passed! Review the topics you missed to improve!';
} else {
    $grade = 'D';
    $grade_color = '#ef4444';
    $message = 'You need to score at least 60% to pass. Review the lessons and try again after the cooldown!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results | JomCoding</title>
    
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
            --border: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        .top-header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text);
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: #000;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .power-icon {
            width: 24px;
            height: 24px;
        }

        .logo-text {
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
        }

        .logout-btn {
            padding: 10px 20px;
            background: #000;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
            background: #1a1a1a;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .results-container {
            background: white;
            border-radius: 24px;
            padding: 48px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .results-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .trophy-icon {
            font-size: 80px;
            margin-bottom: 16px;
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .results-header h1 {
            font-size: 32px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 8px;
        }

        .results-header p {
            font-size: 16px;
            color: var(--text-light);
        }

        .grade-display {
            text-align: center;
            margin: 32px 0;
            padding: 32px;
            background: var(--bg);
            border-radius: 16px;
        }

        .grade-letter {
            font-size: 80px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .grade-message {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-light);
        }

        .score-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        .score-card {
            background: var(--bg);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .score-number {
            font-size: 32px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 4px;
        }

        .score-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .progress-bar-container {
            background: #e5e7eb;
            border-radius: 12px;
            height: 20px;
            margin: 32px 0;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: width 1s ease-out;
        }

        .progress-text {
            color: white;
            font-size: 12px;
            font-weight: 800;
        }

        .actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 16px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            box-shadow: 0 8px 24px rgba(47, 87, 239, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(47, 87, 239, 0.4);
        }

        .btn-secondary {
            background: white;
            border: 2px solid var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            background: var(--bg);
            transform: translateY(-2px);
        }

        .btn-disabled {
            background: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
            border: 2px solid #d1d5db;
        }

        .btn-disabled:hover {
            transform: none;
        }

        .cooldown-notice {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            text-align: center;
        }

        .cooldown-notice h4 {
            color: #92400e;
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .cooldown-notice p {
            color: #78350f;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .cooldown-timer {
            font-size: 24px;
            font-weight: 900;
            color: #dc2626;
        }

        .pass-notice {
            background: #d1fae5;
            border: 2px solid #10b981;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            text-align: center;
            color: #065f46;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #fff;
            border-radius: 24px;
            padding: 40px;
            max-width: 440px;
            width: 90%;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out;
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .modal-content h2 {
            font-size: 28px;
            font-weight: 900;
            margin: 0 0 12px;
            text-align: center;
            color: var(--text);
        }

        .modal-content p {
            font-size: 16px;
            color: var(--text-light);
            text-align: center;
            margin: 0 0 32px;
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .modal-btn {
            flex: 1;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            border: none;
        }

        .modal-btn-cancel {
            background: var(--bg);
            color: var(--text);
            border: 2px solid var(--border);
        }

        .modal-btn-cancel:hover {
            background: #fff;
            border-color: var(--primary);
        }

        .modal-btn-confirm {
            background: #000;
            color: #fff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="top-header">
        <a href="../../dashboard.php" class="logo-link">
            <div class="logo-icon">
                <svg class="power-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2V12M12 12C9.79086 12 8 13.7909 8 16C8 18.2091 9.79086 20 12 20C14.2091 20 16 18.2091 16 16C16 13.7909 14.2091 12 12 12Z" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5.63604 5.63604C2.12132 9.15076 2.12132 14.8492 5.63604 18.364C9.15076 21.8787 14.8492 21.8787 18.364 18.364C21.8787 14.8492 21.8787 9.15076 18.364 5.63604" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </div>
            <span class="logo-text">JomCoding</span>
        </a>
        <button onclick="showLogoutModal()" class="logout-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9M16 17L21 12M21 12L16 7M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Logout
        </button>
    </div>

    <div class="content-wrapper">
        <div class="results-container">
            <div class="results-header">
                <div class="trophy-icon"><?php echo $has_passed ? '🏆' : '📚'; ?></div>
                <h1>Quiz Completed!</h1>
                <p>Here are your results for Web Development Quiz</p>
            </div>

            <div class="grade-display">
                <div class="grade-letter" style="color: <?php echo $grade_color; ?>">
                    <?php echo $grade; ?>
                </div>
                <div class="grade-message">
                    <?php echo $message; ?>
                </div>
            </div>

            <div class="score-grid">
                <div class="score-card">
                    <div class="score-number"><?php echo $correct_count; ?>/<?php echo $total_questions; ?></div>
                    <div class="score-label">Correct Answers</div>
                </div>
                <div class="score-card">
                    <div class="score-number"><?php echo $score; ?>/<?php echo $total_points; ?></div>
                    <div class="score-label">Points Earned</div>
                </div>
            </div>

            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo $percentage; ?>%;">
                    <span class="progress-text"><?php echo number_format($percentage, 1); ?>%</span>
                </div>
            </div>

            <?php if($has_passed): ?>
                <div class="pass-notice">
                    <strong>🎉 Congratulations!</strong> You passed the quiz! You don't need to retake it.
                </div>
            <?php elseif($is_in_cooldown): ?>
                <div class="cooldown-notice">
                    <h4>⏳ Cooldown Active</h4>
                    <p>You need to wait before retaking the quiz</p>
                    <div class="cooldown-timer" id="cooldownTimer">
                        <?php 
                            $hours = floor($cooldown_remaining / 3600);
                            $minutes = floor(($cooldown_remaining % 3600) / 60);
                            $seconds = $cooldown_remaining % 60;
                            echo sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="actions">
                <?php if($has_passed): ?>
                    <a href="quiz.php" class="btn btn-secondary">
                        📖 View Quiz
                    </a>
                <?php elseif($is_in_cooldown): ?>
                    <span class="btn btn-disabled" title="Wait for cooldown to end">
                        🔒 Retake Quiz (Locked)
                    </span>
                <?php else: ?>
                    <a href="quiz.php" class="btn btn-secondary">
                        🔄 Retake Quiz
                    </a>
                <?php endif; ?>
                <a href="../../dashboard.php" class="btn btn-primary">
                    📊 Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon">👋</div>
            <h2>Logout Confirmation</h2>
            <p>Are you sure you want to logout? You'll need to login again to access your courses and progress.</p>
            <div class="modal-actions">
                <button onclick="hideLogoutModal()" class="modal-btn modal-btn-cancel">
                    No, Stay
                </button>
                <button onclick="confirmLogout()" class="modal-btn modal-btn-confirm">
                    Yes, Logout
                </button>
            </div>
        </div>
    </div>

    <script>
        <?php if($is_in_cooldown): ?>
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

        function showLogoutModal() {
            document.getElementById('logoutModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function hideLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function confirmLogout() {
            window.location.href = '../../logout.php';
        }

        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideLogoutModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideLogoutModal();
            }
        });
    </script>
</body>
</html>