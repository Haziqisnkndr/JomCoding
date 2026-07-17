<?php
// payment_success.php - UPDATED for /payment folder
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if payment was successful
if(!isset($_SESSION['payment_success'])) {
    header("Location: ../dashboard.php");
    exit();
}

// Clear the success flag
unset($_SESSION['payment_success']);

require_once '../config.php';

// Get user info
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE user_id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Get subscription details
$sub_query = "SELECT * FROM subscriptions WHERE student_id = $user_id ORDER BY created_at DESC LIMIT 1";
$sub_result = mysqli_query($conn, $sub_query);
$subscription = mysqli_fetch_assoc($sub_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful | JomCoding</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            top: -200px;
            right: -200px;
            animation: float 8s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-30px); }
        }
        
        .success-container {
            background: white;
            border-radius: 24px;
            padding: 60px 48px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 40px 120px rgba(0, 0, 0, 0.3);
            text-align: center;
            position: relative;
            z-index: 10;
            animation: successSlide 0.6s ease-out;
        }
        
        @keyframes successSlide {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(40px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .checkmark-circle {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 32px;
            box-shadow: 0 12px 32px rgba(16, 185, 129, 0.4);
            animation: checkmarkPop 0.6s ease-out 0.3s both;
        }
        
        @keyframes checkmarkPop {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .checkmark {
            font-size: 64px;
            color: white;
        }
        
        .success-header h1 {
            font-size: 36px;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 16px;
            animation: fadeInUp 0.6s ease-out 0.5s both;
        }
        
        .success-header p {
            font-size: 18px;
            color: #64748b;
            margin-bottom: 40px;
            line-height: 1.6;
            animation: fadeInUp 0.6s ease-out 0.7s both;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .transaction-details {
            background: #f8fafc;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            text-align: left;
            animation: fadeInUp 0.6s ease-out 0.9s both;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            padding-top: 16px;
            margin-top: 8px;
            border-top: 2px dashed #e2e8f0;
        }
        
        .detail-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
        }
        
        .detail-value {
            font-size: 15px;
            color: #0f172a;
            font-weight: 700;
        }
        
        .detail-value.highlight {
            color: #10b981;
            font-size: 18px;
        }
        
        .premium-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #8b6914;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }
        
        .benefits-box {
            background: #ecfdf5;
            border: 2px solid #86efac;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            text-align: left;
            animation: fadeInUp 0.6s ease-out 1.1s both;
        }
        
        .benefits-title {
            font-size: 16px;
            font-weight: 900;
            color: #065f46;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .benefits-list {
            list-style: none;
        }
        
        .benefits-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            font-size: 14px;
            color: #166534;
            font-weight: 600;
        }
        
        .benefit-icon {
            width: 24px;
            height: 24px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .action-button {
            display: inline-block;
            padding: 18px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 14px;
            font-weight: 800;
            font-size: 16px;
            text-decoration: none;
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out 1.3s both;
        }
        
        .action-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(102, 126, 234, 0.4);
        }
        
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #ffd700;
            animation: confettiFall 3s linear infinite;
        }
        
        @keyframes confettiFall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        @media (max-width: 600px) {
            .success-container {
                padding: 40px 24px;
            }
            
            .success-header h1 {
                font-size: 28px;
            }
            
            .success-header p {
                font-size: 16px;
            }
            
            .checkmark-circle {
                width: 100px;
                height: 100px;
            }
            
            .checkmark {
                font-size: 52px;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="checkmark-circle">
            <div class="checkmark">✓</div>
        </div>
        
        <div class="success-header">
            <h1>Payment Successful!</h1>
            <p>Congratulations, <?php echo htmlspecialchars($user['full_name']); ?>! Your Premium subscription is now active.</p>
        </div>
        
        <div class="transaction-details">
            <div class="detail-row">
                <span class="detail-label">Plan</span>
                <span class="premium-badge">💎 Premium</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount Paid</span>
                <span class="detail-value">RM<?php echo number_format($subscription['amount'], 2); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method</span>
                <span class="detail-value"><?php echo htmlspecialchars($subscription['payment_method']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Start Date</span>
                <span class="detail-value"><?php echo date('d M Y', strtotime($subscription['start_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Valid Until</span>
                <span class="detail-value highlight"><?php echo date('d M Y', strtotime($subscription['end_date'])); ?></span>
            </div>
        </div>
        
        <div class="benefits-box">
            <div class="benefits-title">
                <span>🎉</span>
                <span>You Now Have Access To:</span>
            </div>
            <ul class="benefits-list">
                <li>
                    <div class="benefit-icon">✓</div>
                    <span>All 5 premium courses (unlimited access)</span>
                </li>
                <li>
                    <div class="benefit-icon">✓</div>
                    <span>Advanced quizzes and assessments</span>
                </li>
                <li>
                    <div class="benefit-icon">✓</div>
                    <span>Professional certificates upon completion</span>
                </li>
                <li>
                    <div class="benefit-icon">✓</div>
                    <span>Progress tracking and analytics</span>
                </li>
                <li>
                    <div class="benefit-icon">✓</div>
                    <span>Priority support from instructors</span>
                </li>
            </ul>
        </div>
        
        <a href="../dashboard.php" class="action-button">
            Start Learning Now →
        </a>
    </div>
    
    <!-- Confetti Effect -->
    <script>
        // Generate confetti on page load
        function createConfetti() {
            const colors = ['#ffd700', '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#dfe6e9'];
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 3 + 's';
                    confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 5000);
                }, i * 50);
            }
        }
        
        // Run confetti on load
        createConfetti();
    </script>
</body>
</html>