<?php
// certificates.php - Certificate Management System  
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config.php';

$user_id = $_SESSION['user_id'];
$course_id = 2; // Web Development

// Get user info
$user_query = "SELECT * FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if user is premium
$is_premium = ($user['subscription_type'] === 'premium');

// Redirect free users - certificates only for premium
if(!$is_premium) {
    $_SESSION['access_denied'] = 'Certificates are only available for Premium members. Upgrade to earn your certificate!';
    $_SESSION['upgrade_prompt'] = true;
    header("Location: ../../dashboard.php");
    exit();
}

// Calculate Video Progress (75% of total)

// Calculate Video Progress (75% of total)
$video_progress_query = "SELECT COUNT(*) as watched FROM video_progress WHERE student_id = ? AND course_id = ? AND watched = 1";
$stmt = $conn->prepare($video_progress_query);
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$video_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$videos_watched = $video_stats['watched'];
$total_videos = 5;
$video_progress_percentage = $total_videos > 0 ? ($videos_watched / $total_videos) * 75 : 0;

// Calculate Quiz Progress (25% of total - only for Web Development)

// Calculate Quiz Progress (25% of total) - BEST SCORE PER QUESTION ONLY
$quiz_score_query = "SELECT SUM(best_scores.max_points) as total_score
FROM (
    SELECT qa.quiz_id, MAX(qa.points_earned) as max_points
    FROM quiz_attempts qa
    INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
    WHERE qa.student_id = ? AND q.course_id = ?
    GROUP BY qa.quiz_id
) as best_scores";
$stmt = $conn->prepare($quiz_score_query);
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$quiz_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get total possible quiz points dynamically
$total_quiz_points_query = "SELECT SUM(points) as total_possible FROM quizzes WHERE course_id = ?";
$stmt = $conn->prepare($total_quiz_points_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$total_quiz_result = $stmt->get_result()->fetch_assoc();
$stmt->close();

$quiz_total_score = $quiz_stats['total_score'] ?? 0;
$total_quiz_possible = $total_quiz_result['total_possible'] ?? 1;
$quiz_progress_percentage = $total_quiz_possible > 0 ? ($quiz_total_score / $total_quiz_possible) * 25 : 0;

// Overall Progress = Videos (75%) + Quiz (25%)
$overall_progress = round($video_progress_percentage + $quiz_progress_percentage, 1);

// Check if eligible for certificate (>90%)
$is_eligible = $overall_progress >= 90;

// Generate certificate ID
$certificate_id = 'JOMCODING-' . strtoupper(substr(md5($user_id . 'web_development'), 0, 8));
$issue_date = date('F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificates | JomCoding</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2f57ef;
            --success: #10b981;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --border: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid var(--border);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
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
            padding: 12px;
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
            background: #f1f5f9;
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
            min-height: 100vh;
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

        /* PROGRESS CARD */
        .progress-card {
            background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
            color: white;
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 32px;
            box-shadow: 0 10px 40px rgba(47, 87, 239, 0.2);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .progress-header h2 {
            font-size: 24px;
        }

        .progress-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
        }

        .progress-badge.eligible {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        }

        .progress-badge.not-eligible {
            background: rgba(255, 255, 255, 0.2);
        }

        .progress-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 20px;
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
            font-size: 12px;
            opacity: 0.9;
            font-weight: 600;
        }

        .progress-bar-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            height: 16px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #34d399);
            border-radius: 12px;
            transition: width 1s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 8px;
        }

        .progress-text {
            font-size: 11px;
            font-weight: 900;
        }

        /* CERTIFICATE - LANDSCAPE FORMAT */
        .certificate-wrapper {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 1400px;
            margin: 0 auto;
        }

        .certificate-document {
            width: 1200px;  /* EXPLICIT WIDE WIDTH */
       
            height: 850px;  /* INCREASED HEIGHT - FIT ALL CONTENT */
            margin: 0 auto;
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border: 16px solid #2f57ef;
            border-radius: 0;
            padding: 40px 80px;  /* REDUCED TOP/BOTTOM PADDING */
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        /* Header */
        .cert-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;  /* REDUCED FROM 40px */
        }

        .cert-logo-left {
            width: 120px;
            height: 120px;
            object-fit: contain;
            /* FIX: White background to remove transparency */
            background-color: #ffffff !important;
            padding: 10px;
            border-radius: 8px;
        }

        .cert-logo-center {
            text-align: center;
            flex: 1;
        }

        .cert-logo {
            font-size: 56px;
            font-weight: 900;
            color: #2f57ef;
            margin-bottom: 10px;
            letter-spacing: 4px;
        }

        .cert-date {
            font-size: 14px;
            color: #64748b;
            font-weight: 400;
        }

        .cert-logo-right {
            width: 120px;
            height: 120px;
        }

        /* Body */
        .cert-body {
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 20px 0;  /* REDUCED FROM 40px */
        }

        .cert-title {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 25px;  /* REDUCED FROM 35px */
        }

        .cert-recipient {
            font-size: 52px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 25px;  /* REDUCED FROM 35px */
            font-family: 'Georgia', serif;
            line-height: 1.2;
        }

        .cert-description {
            font-size: 15px;
            color: #64748b;
            margin-bottom: 25px;  /* REDUCED FROM 35px */
            font-weight: 400;
        }

        .cert-course-title {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .cert-subtext {
            font-size: 13px;
            color: #64748b;
            font-style: italic;
            font-weight: 400;
        }

        /* Footer */
        .cert-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 30px;  /* REDUCED FROM 60px */
            padding-top: 25px;  /* REDUCED FROM 40px */
            border-top: 2px solid #e2e8f0;
        }

        .cert-signature {
            text-align: left;
            flex: 1;  /* EQUAL WIDTH */
        }

        .cert-signature-image {
            width: 150px;
            height: auto;
            margin-bottom: 5px;
            /* FIX: White background to remove transparency */
            background-color: #ffffff !important;
            padding: 8px;
            border-radius: 4px;
        }

        .cert-signature-name {
            font-weight: 700;
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .cert-signature-title {
            font-size: 12px;
            color: #64748b;
            font-weight: 400;
            line-height: 1.4;
        }

        .cert-badge {
            text-align: center;
            flex: 1;  /* EQUAL WIDTH */
        }

        .cert-badge-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid #2f57ef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            background: white;
            padding: 10px;
            overflow: hidden;
            /* FIX: Force white background */
            background-color: #ffffff !important;
        }

        .cert-badge-logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            /* FIX: White background to remove transparency */
            background-color: #ffffff !important;
        }

        .cert-badge-text {
            font-weight: 700;
            font-size: 13px;
            color: #1e293b;
        }

        .cert-badge-subtext {
            font-size: 11px;
            color: #64748b;
        }

        .cert-verification {
            text-align: right;
            flex: 1;  /* EQUAL WIDTH */
        }

        .cert-verify-text {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 3px;
        }

        .cert-verify-score {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .cert-verify-link {
            font-size: 10px;
            color: #2f57ef;
            text-decoration: none;
            word-break: break-all;
        }

        /* Print Styles - FORCE LANDSCAPE MODE */
        @media print {
            @page {
                size: landscape;
                size: A4 landscape;
                size: 297mm 210mm;
                margin: 0.5cm;
            }
            
            html, body {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
                overflow: hidden;
            }
            
            .sidebar,
            .top-bar,
            .progress-card,
            .no-print {
                display: none !important;
            }
            
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .content-area {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .certificate-wrapper {
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
            }
            
            .certificate-document {
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
                border: 12px solid #2f57ef !important;
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 auto !important;
                padding: 30px 60px !important;
                box-sizing: border-box !important;
                /* Force to fit on one page */
                transform: scale(0.90) !important;
                transform-origin: top center !important;
            }
            
            /* Reduce spacing for print */
            .cert-header {
                margin-bottom: 20px !important;
            }
            
            .cert-body {
                padding: 15px 0 !important;
            }
            
            .cert-title {
                margin-bottom: 20px !important;
            }
            
            .cert-recipient {
                font-size: 48px !important;
                margin-bottom: 20px !important;
            }
            
            .cert-description {
                margin-bottom: 20px !important;
            }
            
            .cert-course-title {
                margin-bottom: 12px !important;
            }
            
            .cert-footer {
                margin-top: 20px !important;
                padding-top: 20px !important;
            }
            
            /* FIX: Force white backgrounds when printing */
            .cert-logo-left,
            .cert-signature-image,
            .cert-badge-logo,
            .cert-badge-circle {
                background-color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            img {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 15px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            margin-top: 20px;
        }

        .btn-primary {
            background: #2f57ef;
            color: white;
            box-shadow: 0 4px 12px rgba(47, 87, 239, 0.3);
        }

        .btn-primary:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(47, 87, 239, 0.4);
        }

        .btn-secondary {
            background: white;
            color: var(--text);
            border: 2px solid var(--border);
            margin-left: 12px;
        }

        .btn-secondary:hover {
            background: var(--bg);
        }

        /* NOT ELIGIBLE */
        .not-eligible-section {
            text-align: center;
            padding: 60px 40px;
        }

        .certificate-icon {
            font-size: 80px;
            margin-bottom: 24px;
        }

        .certificate-title {
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .certificate-message {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .requirement-list {
            background: var(--bg);
            border-radius: 12px;
            padding: 24px;
            margin: 24px auto;
            text-align: left;
            max-width: 600px;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            margin-bottom: 8px;
            background: white;
            border-radius: 8px;
        }

        .requirement-icon {
            font-size: 24px;
        }

        .requirement-text {
            flex: 1;
        }

        .requirement-status {
            font-weight: 700;
            font-size: 14px;
        }

        .requirement-status.complete {
            color: var(--success);
        }

        .requirement-status.incomplete {
            color: #ef4444;
        }

        /* PRINT STYLES - IBM CERTIFICATE SIZE (11" x 8.5" LANDSCAPE) */
    </style>
    
    <!-- html2canvas for certificate download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" crossorigin="anonymous"></script>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar no-print">
        <div class="logo">
      
            <div class="logo-text">JomCoding</div>
        </div>
        
        <div class="nav-menu">
            <a href="../../dashboard.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="web_development.php" class="nav-item">
                <span>Lessons</span>
            </a>
            <a href="quiz.php" class="nav-item">
                <span>Quizzes</span>
            </a>
            <a href="certificates.php" class="nav-item active">
             
                <span>Certificate</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="page-title">
                <h1>Certificates</h1>
            </div>
            <div class="top-actions">
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
        <div class="content-area">
            <!-- PROGRESS CARD -->
            <div class="progress-card no-print">
                <div class="progress-header">
                    <h2>Your Progress</h2>
                    <span class="progress-badge <?php echo $is_eligible ? 'eligible' : 'not-eligible'; ?>">
                        <?php echo $is_eligible ? '✓ Eligible' : 'Not Eligible'; ?>
                    </span>
                </div>

                <div class="progress-stats">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo round($video_progress_percentage, 1); ?>%</div>
                        <div class="stat-label">Video Progress (75%)</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo round($quiz_progress_percentage, 1); ?>%</div>
                        <div class="stat-label">Quiz Progress (25%)</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $overall_progress; ?>%</div>
                        <div class="stat-label">Overall Progress</div>
                    </div>
                </div>

                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: <?php echo min($overall_progress, 100); ?>%;">
                        <span class="progress-text"><?php echo $overall_progress; ?>%</span>
                    </div>
                </div>

                <p style="font-size: 14px; opacity: 0.9; margin-top: 8px;">
                    <?php if($overall_progress > 90): ?>
                        🎉 Congratulations! You've achieved >90% and earned your certificate!
                    <?php else: ?>
                        📚 Complete <?php echo round(90 - $overall_progress, 1); ?>% more to earn your certificate
                    <?php endif; ?>
                </p>
            </div>

            <!-- CERTIFICATE DISPLAY -->
            <?php if($is_eligible): ?>
                <!-- PROFESSIONAL CERTIFICATE -->
                <div class="certificate-wrapper">
                    <div class="certificate-document" id="certificate">
                        <!-- Header -->
                        <div class="cert-header">
                            <img src="../../assets/uitmlogo.jpg" alt="UiTM Logo" class="cert-logo-left">
                            <div class="cert-logo-center">
                                <div class="cert-logo">JOMCODING</div>
                            </div>
                            <div class="cert-logo-right"></div>
                        </div>

                        <!-- Body -->
                        <div class="cert-body">
                            <div class="cert-title">CERTIFICATE OF COMPLETION</div>
                            
                            <div class="cert-recipient"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></div>
                            
                            <div class="cert-description">has successfully completed</div>
                            
                            <div class="cert-course-title">Web Development</div>
                            
                            <div class="cert-subtext">an online course authorized by JomCoding</div>
                        </div>

                        <!-- Footer -->
                        <div class="cert-footer">
                            <!-- Signature -->
                            <div class="cert-signature">
                                <img src="../../assets/signaturesirnazrimansor.jpg" alt="Signature" class="cert-signature-image">
                                <div class="cert-signature-name">AHMAD NAZRI MANSOR</div>
                                <div class="cert-signature-title">Lecturer</div>
                                <div class="cert-signature-title">Universiti Teknologi MARA</div>
                            </div>

                            <!-- Badge -->
                            <div class="cert-badge">
                                <div class="cert-badge-circle">
                                    <img src="../../assets/uitmlogo.jpg" alt="UiTM Logo" class="cert-badge-logo">
                                </div>
                                <div class="cert-badge-text">Programming</div>
                                <div class="cert-badge-subtext">Certificate</div>
                            </div>

                            <!-- Verification -->
                            <div class="cert-verification">
                                <div class="cert-verify-text">Certificate No: <?php echo $certificate_id; ?></div>
                                <div class="cert-verify-score">Score: <?php echo $overall_progress; ?>%</div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="text-align: center; margin-top: 20px;" class="no-print">
                        <button onclick="downloadCertificate()" class="btn btn-primary">
                            📥 Download Certificate (Landscape PNG)
                        </button>
                        <button onclick="printCertificate()" class="btn btn-secondary">
                            🖨️ Print Certificate (Landscape)
                        </button>
                        <a href="../../dashboard.php" class="btn btn-secondary">
                            🏠 Back to Dashboard
                        </a>
                        <div style="margin-top: 16px; padding: 12px; background: #e0f2fe; border: 2px solid #0284c7; border-radius: 8px; font-size: 13px; color: #075985; max-width: 600px; margin-left: auto; margin-right: auto;">
                            <strong>📌 Landscape format - ONE PAGE ONLY (IBM Style)</strong><br>
                            Download = High-quality PNG | Print = Single-page PDF (fits perfectly on A4/Letter)
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- NOT ELIGIBLE -->
                <div class="certificate-wrapper">
                    <div class="not-eligible-section">
                        <div class="certificate-icon">🔒</div>
                        <h2 class="certificate-title">Certificate Locked</h2>
                        <p class="certificate-message">
                            You need to achieve >90% overall progress to earn your certificate. Keep learning!
                        </p>

                        <div class="requirement-list">
                            <div class="requirement-item">
                                <span class="requirement-icon">🎥</span>
                                <div class="requirement-text">
                                    <strong>Watch All Videos (75% Weight)</strong><br>
                                    <small><?php echo $videos_watched; ?>/<?php echo $total_videos; ?> videos completed</small>
                                </div>
                                <span class="requirement-status <?php echo $videos_watched == $total_videos ? 'complete' : 'incomplete'; ?>">
                                    <?php echo round($video_progress_percentage, 1); ?>%
                                </span>
                            </div>

                            <div class="requirement-item">
                                <span class="requirement-icon">❓</span>
                                <div class="requirement-text">
                                    <strong>Complete Quiz (25% Weight)</strong><br>
                                    <small><?php echo $quiz_total_score; ?>/325 points earned</small>
                                </div>
                                <span class="requirement-status <?php echo $quiz_progress_percentage >= 22.5 ? 'complete' : 'incomplete'; ?>">
                                    <?php echo round($quiz_progress_percentage, 1); ?>%
                                </span>
                            </div>

                            <div class="requirement-item">
                                <span class="requirement-icon">🎯</span>
                                <div class="requirement-text">
                                    <strong>Overall Progress >90%</strong><br>
                                    <small>Required for certificate</small>
                                </div>
                                <span class="requirement-status <?php echo $overall_progress > 90 ? 'complete' : 'incomplete'; ?>">
                                    <?php echo $overall_progress; ?>%
                                </span>
                            </div>
                        </div>

                        <div>
                            <?php if($videos_watched < $total_videos): ?>
                                <a href="programming_basics.php" class="btn btn-primary">
                                    📚 Watch Videos
                                </a>
                            <?php elseif($quiz_progress_percentage < 22.5): ?>
                                <a href="quiz.php" class="btn btn-primary">
                                    ❓ Take Quiz
                                </a>
                            <?php else: ?>
                                <a href="quiz.php" class="btn btn-primary">
                                    ❓ Retake Quiz
                                </a>
                            <?php endif; ?>
                            <a href="../../dashboard.php" class="btn btn-secondary">
                                🏠 Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- LOGOUT MODAL -->
    <div style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center;" id="logoutModal" class="no-print">
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

    <script>
        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function hideLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function confirmLogout() {
            window.location.href = '../../logout.php';
        }

        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) hideLogoutModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') hideLogoutModal();
        });

        // AUTO-DOWNLOAD CERTIFICATE - PROPER LANDSCAPE (WIDE FORMAT)
        function downloadCertificate() {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Generating...';
            button.disabled = true;

            // Check if html2canvas is loaded
            if (typeof html2canvas === 'undefined') {
                alert('Error: html2canvas library not loaded. Please refresh the page and try again.');
                button.innerHTML = originalText;
                button.disabled = false;
                return;
            }

            // Get the certificate element
            const certificate = document.getElementById('certificate');
            
            if (!certificate) {
                alert('Error: Certificate element not found');
                button.innerHTML = originalText;
                button.disabled = false;
                return;
            }
            
            // AGGRESSIVE FIX: Force white backgrounds on ALL elements
            const images = certificate.querySelectorAll('img');
            images.forEach(img => {
                img.style.backgroundColor = '#ffffff';
                img.style.padding = '8px';
                img.style.borderRadius = '4px';
            });
            
            // Force white background on logo containers
            const logoContainers = certificate.querySelectorAll('.cert-logo-left, .cert-badge-circle, .cert-badge-logo, .cert-signature-image');
            logoContainers.forEach(el => {
                el.style.backgroundColor = '#ffffff';
            });

            // Wait for styles to fully apply
            setTimeout(() => {
                // Get LANDSCAPE dimensions (WIDE > TALL)
                const certWidth = certificate.offsetWidth;   // Should be ~1200px (WIDE)
                const certHeight = certificate.offsetHeight; // Should be ~800px (TALL)
                
                console.log('Certificate dimensions:', certWidth, 'x', certHeight); // Debug
                
                // Use html2canvas - LANDSCAPE capture (WIDE format)
                html2canvas(certificate, {
                    scale: 3, // High quality (3x resolution)
                    backgroundColor: '#ffffff',
                    logging: false,
                    useCORS: true,
                    allowTaint: true,
                    imageTimeout: 0,
                    removeContainer: true,
                    scrollY: -window.scrollY,
                    scrollX: -window.scrollX,
                    width: certWidth,   // WIDE
                    height: certHeight, // TALL (certWidth > certHeight = LANDSCAPE)
                    windowWidth: certWidth,
                    windowHeight: certHeight,
                    onclone: function(clonedDoc) {
                        // Force white backgrounds in cloned document
                        const clonedCert = clonedDoc.getElementById('certificate');
                        if (clonedCert) {
                            clonedCert.style.backgroundColor = '#ffffff';
                            clonedCert.style.width = certWidth + 'px';
                            clonedCert.style.height = certHeight + 'px';
                            const clonedImages = clonedCert.querySelectorAll('img');
                            clonedImages.forEach(img => {
                                img.style.backgroundColor = '#ffffff';
                                img.style.padding = '8px';
                            });
                        }
                    }
                }).then(canvas => {
                    // Canvas should now be LANDSCAPE (WIDE: 3600px x TALL: 2400px at 3x)
                    console.log('Canvas dimensions:', canvas.width, 'x', canvas.height); // Debug
                    
                    canvas.toBlob(function(blob) {
                        if (!blob) {
                            alert('Error: Failed to generate image');
                            button.innerHTML = originalText;
                            button.disabled = false;
                            return;
                        }

                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        const filename = 'JomCoding_Certificate_<?php echo str_replace(' ', '_', $user['full_name'] ?? $user['username']); ?>_<?php echo date('Ymd'); ?>.png';
                        
                        link.href = url;
                        link.download = filename;
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();
                        
                        // Cleanup
                        setTimeout(() => {
                            document.body.removeChild(link);
                            URL.revokeObjectURL(url);
                        }, 100);

                        // Success feedback
                        button.innerHTML = '✅ Downloaded!';
                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.disabled = false;
                        }, 2000);
                    }, 'image/png', 1.0);
                }).catch(error => {
                    console.error('Error generating certificate:', error);
                    alert('Error generating certificate: ' + error.message + '\n\nPlease try:\n1. Refresh the page\n2. Try the Print button instead\n3. Check browser console for details');
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }, 300);
        }

        // PRINT CERTIFICATE - LANDSCAPE MODE
        function printCertificate() {
            const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
            
            if (!isMobile) {
                const proceed = confirm(
                    "🖨️ PRINT IN LANDSCAPE MODE:\n\n" +
                    "⚠️ IMPORTANT: Check orientation in print dialog!\n\n" +
                    "Steps:\n" +
                    "1. When print dialog opens, click 'More settings'\n" +
                    "2. Find 'Layout' or 'Orientation' option\n" +
                    "3. SELECT 'Landscape' (NOT Portrait)\n" +
                    "4. Turn OFF 'Headers and footers'\n" +
                    "5. Paper: A4 or Letter\n" +
                    "6. Margins: Default\n\n" +
                    "✅ Certificate should appear WIDE (horizontal)\n" +
                    "❌ If TALL (vertical), change to Landscape!\n\n" +
                    "Click OK to open print dialog."
                );
                
                if (proceed) {
                    window.print();
                }
            } else {
                window.print();
            }
        }
    </script>
</body>
</html>