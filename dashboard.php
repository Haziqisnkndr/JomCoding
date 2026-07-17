<?php
// dashboard.php - FIXED VERSION with proper synchronization
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if profile is incomplete
if(isset($_SESSION['temp_user'])) {
    header("Location: complete-profile.php");
    exit();
}

require_once 'config.php';

// Get user info
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE user_id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Check subscription status from user table (faster)
$is_premium = ($user['subscription_type'] === 'premium');

// Also verify against subscriptions table
$sub_query = "SELECT * FROM subscriptions WHERE student_id = $user_id AND status = 'active' ORDER BY created_at DESC LIMIT 1";
$sub_result = mysqli_query($conn, $sub_query);
$subscription = mysqli_fetch_assoc($sub_result);

// Double-check: user must have subscription_type='premium' AND active subscription
if($subscription && $subscription['plan_name'] == 'Premium' && $subscription['end_date'] >= date('Y-m-d')) {
    // Ensure user table is synced
    if($user['subscription_type'] !== 'premium') {
        mysqli_query($conn, "UPDATE user SET subscription_type = 'premium' WHERE user_id = $user_id");
        $is_premium = true;
    }
} else {
    // If subscription expired, set to free
    if($user['subscription_type'] === 'premium') {
        mysqli_query($conn, "UPDATE user SET subscription_type = 'free' WHERE user_id = $user_id");
        $is_premium = false;
    }
}

// ============================================================
// FIXED: Calculate courses accessed (based on actual activity)
// ============================================================

// Count courses where user has watched at least one video OR taken a quiz
$courses_accessed_query = "
    SELECT COUNT(DISTINCT course_id) as course_count FROM (
        SELECT DISTINCT course_id FROM video_progress WHERE student_id = $user_id
        UNION
        SELECT DISTINCT q.course_id FROM quiz_attempts qa 
        INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id 
        WHERE qa.student_id = $user_id
    ) as accessed_courses
";
$courses_accessed_result = mysqli_query($conn, $courses_accessed_query);
$courses_accessed_count = mysqli_fetch_assoc($courses_accessed_result)['course_count'];

// ============================================================
// FIXED: Calculate videos watched (count of watched videos)
// ============================================================

$videos_watched_query = "SELECT COUNT(*) as watched_count FROM video_progress WHERE student_id = $user_id AND watched = 1";
$videos_watched_result = mysqli_query($conn, $videos_watched_query);
$videos_watched_count = mysqli_fetch_assoc($videos_watched_result)['watched_count'];

// ============================================================
// FIXED: Calculate unique quizzes attempted
// ============================================================

$quiz_attempts_query = "SELECT COUNT(DISTINCT quiz_id) as quiz_count FROM quiz_attempts WHERE student_id = $user_id";
$quiz_result = mysqli_query($conn, $quiz_attempts_query);
$quiz_stats = mysqli_fetch_assoc($quiz_result);
$unique_quizzes_attempted = $quiz_stats['quiz_count'];

// ============================================================
// FIXED: Calculate REAL overall progress (weighted system)
// ============================================================

// Course ID mapping for total videos
$course_videos = [
    1 => 5,  // Programming Basics
    2 => 5,  // Web Development
    3 => 3,  // PHP Backend
    4 => 3,  // JavaScript Essentials
    5 => 4   // MySQL Database
];

$total_progress_sum = 0;
$courses_with_activity = 0;

// Get all courses user has interacted with
$active_courses_query = "
    SELECT DISTINCT course_id FROM (
        SELECT DISTINCT course_id FROM video_progress WHERE student_id = $user_id
        UNION
        SELECT DISTINCT q.course_id FROM quiz_attempts qa 
        INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id 
        WHERE qa.student_id = $user_id
    ) as active
";
$active_courses_result = mysqli_query($conn, $active_courses_query);

while($course_row = mysqli_fetch_assoc($active_courses_result)) {
    $cid = $course_row['course_id'];
    $courses_with_activity++;
    
    // Video progress (75% weight)
    $total_videos = isset($course_videos[$cid]) ? $course_videos[$cid] : 3;
    $watched_query = "SELECT COUNT(*) as watched FROM video_progress WHERE student_id = $user_id AND course_id = $cid AND watched = 1";
    $watched_result = mysqli_query($conn, $watched_query);
    $watched_count = mysqli_fetch_assoc($watched_result)['watched'];
    $video_progress = ($watched_count / $total_videos) * 75;
    
    // Quiz progress (25% weight) - BEST SCORE
    $quiz_score_query = "
        SELECT SUM(best_scores.max_points) as total_score
        FROM (
            SELECT qa.quiz_id, MAX(qa.points_earned) as max_points
            FROM quiz_attempts qa
            INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
            WHERE qa.student_id = $user_id AND q.course_id = $cid
            GROUP BY qa.quiz_id
        ) as best_scores
    ";
    $quiz_score_result = mysqli_query($conn, $quiz_score_query);
    $quiz_score_data = mysqli_fetch_assoc($quiz_score_result);
    $quiz_total_score = $quiz_score_data['total_score'] ?? 0;
    
    // Get total possible quiz points
    $total_quiz_query = "SELECT SUM(points) as total_possible FROM quizzes WHERE course_id = $cid";
    $total_quiz_result = mysqli_query($conn, $total_quiz_query);
    $total_quiz_possible = mysqli_fetch_assoc($total_quiz_result)['total_possible'] ?? 1;
    
    $quiz_progress = ($quiz_total_score / $total_quiz_possible) * 25;
    
    // Course overall progress
    $course_progress = $video_progress + $quiz_progress;
    $total_progress_sum += $course_progress;
}

// Calculate average progress across all accessed courses
$overall_progress = $courses_with_activity > 0 ? ($total_progress_sum / $courses_with_activity) : 0;

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard | JomCoding</title>

  <style>
    :root{
      --primary:#2f57ef;
      --primary-dark:#1d3fc9;
      --accent:#ff4a57;
      --accent-light:#ff6b76;
      --text:#0f172a;
      --muted:#64748b;
      --bg:#f6f7fb;
      --card:#ffffff;
      --border:#e6e8f0;
      --shadow: 0 18px 60px rgba(15, 23, 42, .08);
      --radius:18px;
      --max:1180px;
    }
    
    *{box-sizing:border-box}
    
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
      background: var(--bg);
      color: var(--text);
      overflow-x: hidden;
      overflow-y: auto;
    }
    
    a{color:inherit; text-decoration:none}
    .container{max-width:var(--max); margin:0 auto; padding:0 18px}

    /* Main Navbar */
    .navbar{
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      z-index: 20;
      box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
    }
    
    .nav-inner{
      display:flex; justify-content:space-between; align-items:center;
      padding: 16px 0;
      gap: 16px;
    }
    
    .brand{
      display:flex; align-items:center; gap:12px;
      font-weight:900;
      letter-spacing:-.2px;
      font-size: 20px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      transition: transform 0.3s ease;
    }
    
    .brand:hover {
      transform: scale(1.05);
    }
    
    .nav-links{display:flex; gap:18px; align-items:center}
    
    .nav-links a{
      font-weight:700;
      color: #22304a;
      font-size: 14px;
      transition: all 0.3s ease;
      position: relative;
    }
    
    .nav-links a:hover{
      color: var(--primary);
      transform: translateY(-2px);
    }
    
    .nav-links a:not(.btn):not(.user-menu)::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 0;
      width: 0;
      height: 2px;
      background: var(--primary);
      transition: width 0.3s ease;
    }
    
    .nav-links a:not(.btn):not(.user-menu):hover::after {
      width: 100%;
    }
    
    .user-menu {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 14px;
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 12px;
      font-weight: 700;
      font-size: 14px;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .user-menu:hover {
      background: #f1f5ff;
      border-color: var(--primary);
      transform: translateY(-2px);
    }
    
    .avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 900;
      font-size: 14px;
      box-shadow: 0 4px 12px rgba(47, 87, 239, 0.3);
    }
    
    .btn{
      display:inline-flex; align-items:center; justify-content:center;
      padding: 10px 20px;
      border-radius: 12px;
      font-weight: 800;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      white-space: nowrap;
    }
    
    .btn.danger{
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      color: #991b1b;
    }
    
    .btn.danger:hover{
      background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
    }

    /* Welcome Section */
    .welcome{
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      color: white;
      padding: 60px 0;
      position: relative;
      overflow: hidden;
    }
    
    .welcome::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.05)"/></svg>');
      opacity: 0.5;
    }
    
    .welcome-content{
      position: relative;
      z-index: 1;
    }
    
    .subscription-badge{
      display: inline-block;
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 800;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    .subscription-badge.premium{
      background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
      color: #8b6914;
    }
    
    .subscription-badge.free{
      background: rgba(255,255,255,0.15);
      color: white;
      border: 2px solid rgba(255,255,255,0.3);
    }
    
    .welcome h1{
      font-size: 48px;
      font-weight: 900;
      margin: 0 0 16px;
      line-height: 1.2;
    }
    
    .welcome p{
      font-size: 18px;
      opacity: 0.9;
      margin: 0 0 32px;
      max-width: 600px;
    }
    
    .stats-row{
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-top: 40px;
    }
    
    .stat-card{
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      padding: 24px;
      text-align: center;
      transition: all 0.3s ease;
    }
    
    .stat-card:hover{
      background: rgba(255, 255, 255, 0.15);
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
    }
    
    .stat-number{
      font-size: 36px;
      font-weight: 900;
      margin-bottom: 8px;
      background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .stat-label{
      font-size: 14px;
      font-weight: 600;
      opacity: 0.9;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* Main Content */
    .main-content{
      padding: 60px 0 80px;
    }
    
    .section-header{
      margin-bottom: 60px;
      position: relative;
      padding-bottom: 24px;
    }
    
    .section-header::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 60px;
      height: 3px;
      background: linear-gradient(90deg, var(--primary) 0%, transparent 100%);
      border-radius: 2px;
    }
    
    .section-header h2{
      font-size: 32px;
      font-weight: 700;
      margin: 0 0 8px 0;
      color: #0f172a;
      letter-spacing: -0.5px;
      line-height: 1.2;
    }
    
    .section-header p {
      font-size: 15px;
      color: #64748b;
      margin: 0;
      font-weight: 400;
    }
    
    .course-grid{
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 28px;
      margin-top: 32px;
    }
    
    .course-card{
      background: var(--card);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
      transition: all 0.3s ease;
      border: 1px solid #e2e8f0;
      position: relative;
    }
    
    .course-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .course-card:hover::before {
      opacity: 1;
    }
    
    .course-card:hover{
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
      border-color: #cbd5e1;
    }
    
    .course-image{
      height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 49px;
      font-weight: 800;
      color: white;
      position: relative;
      overflow: hidden;
    }
    
    .course-image::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, 
        rgba(0, 0, 0, 0.05) 0%, 
        rgba(0, 0, 0, 0.15) 100%);
      pointer-events: none;
    }
    
    .course-body{
      padding: 28px;
      position: relative;
      background: #ffffff;
    }
    
    .course-title{
      font-size: 20px;
      font-weight: 700;
      margin: 0 0 12px;
      line-height: 1.4;
      color: #0f172a;
      transition: color 0.2s ease;
    }
    
    .course-card:hover .course-title {
      color: var(--primary);
    }
    
    .course-desc{
      font-size: 14px;
      color: #64748b;
      line-height: 1.6;
      margin: 0 0 20px;
    }
    
    .course-meta{
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    
    .badge{
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.3px;
      transition: all 0.2s ease;
    }
    
    .badge:hover {
      transform: translateY(-1px);
    }
    
    .badge-blue{
      background: #eff6ff;
      color: #1e40af;
      border: 1px solid #dbeafe;
    }
    
    .badge-blue:hover {
      background: #dbeafe;
      border-color: #bfdbfe;
    }
    
    .badge-green{
      background: #f0fdf4;
      color: #166534;
      border: 1px solid #dcfce7;
    }
    
    .badge-green:hover {
      background: #dcfce7;
      border-color: #bbf7d0;
    }

    /* Footer */
    .footer{
      background: var(--card);
      border-top: 1px solid var(--border);
      padding: 32px 0;
      text-align: center;
      color: var(--muted);
      font-size: 14px;
      font-weight: 600;
    }

    /* Modal */
    .modal-overlay{
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
    
    .modal-overlay.active{
      display: flex;
    }
    
    .modal-box{
      background: white;
      border-radius: 24px;
      padding: 40px;
      max-width: 440px;
      width: 90%;
      box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
      animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn{
      from{
        opacity: 0;
        transform: scale(0.9) translateY(20px);
      }
      to{
        opacity: 1;
        transform: scale(1) translateY(0);
      }
    }
    
    .modal-icon{
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
    
    .modal-title{
      font-size: 28px;
      font-weight: 900;
      margin: 0 0 12px;
      text-align: center;
      color: var(--text);
    }
    
    .modal-message{
      font-size: 16px;
      color: var(--muted);
      text-align: center;
      margin: 0 0 32px;
      line-height: 1.6;
    }
    
    .modal-actions{
      display: flex;
      gap: 12px;
    }
    
    .modal-btn{
      flex: 1;
      padding: 14px 24px;
      border-radius: 12px;
      font-weight: 800;
      font-size: 15px;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      font-family: inherit;
    }
    
    .modal-btn.cancel{
      border: 2px solid var(--border);
      background: var(--bg);
      color: var(--text);
    }
    
    .modal-btn.cancel:hover{
      background: var(--border);
    }
    
    .modal-btn.confirm{
      background: linear-gradient(135deg, #ff4a57 0%, #ff6b76 100%);
      color: white;
      box-shadow: 0 8px 20px rgba(255, 74, 87, 0.3);
    }
    
    .modal-btn.confirm:hover{
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(255, 74, 87, 0.4);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .hide-sm{display: none;}
      .welcome h1{font-size: 36px;}
      .welcome p{font-size: 16px;}
      .stat-number{font-size: 28px;}
      .section-header h2{font-size: 28px;}
      .course-grid{grid-template-columns: 1fr;}
      .nav-links{gap: 8px;}
    }
  </style>
</head>

<body>

<!-- ACCESS DENIED / UPGRADE PROMPT -->
<?php if(isset($_SESSION['access_denied'])): ?>
<div style="background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%); border: 2px solid #f59e0b; border-radius: 16px; padding: 24px; margin: 24px auto; max-width: 1180px; display: flex; align-items: center; gap: 16px; animation: slideIn 0.3s ease-out;">
    <div style="font-size: 48px;">⚠️</div>
    <div style="flex: 1;">
        <h3 style="margin: 0 0 8px; font-weight: 800; color: #92400e; font-size: 18px;">Premium Feature Required</h3>
        <p style="margin: 0; color: #78350f; font-weight: 600; font-size: 15px;">
            <?php echo htmlspecialchars($_SESSION['access_denied']); ?>
        </p>
    </div>
    <?php if(isset($_SESSION['upgrade_prompt'])): ?>
    <a href="payment/subscription.php" style="padding: 12px 24px; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; white-space: nowrap; text-decoration: none; font-size: 14px;">
        Upgrade Now ✨
    </a>
    <?php endif; ?>
</div>
<?php 
    unset($_SESSION['access_denied']); 
    unset($_SESSION['upgrade_prompt']);
endif; 
?>

  <!-- NAVBAR -->
  <div class="navbar">
    <div class="container">
      <div class="nav-inner">
        <a class="brand" href="index.php">
          <span>🚀</span> JomCoding
        </a>
        
        <div class="nav-links">
          <a class="hide-sm" href="index.php">Home</a>
          <a class="hide-sm" href="dashboard.php">Dashboard</a>
         
          
          <div class="user-menu">
            <div class="avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
            <span><?php echo htmlspecialchars($user['username']); ?></span>
          </div>
          
          <a class="btn danger" href="#" onclick="showLogoutModal(); return false;">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- WELCOME SECTION -->
  <section class="welcome">
    <div class="container">
      <div class="welcome-content">
        <?php if($is_premium): ?>
          <span class="subscription-badge premium">💎 Premium Member</span>
        <?php else: ?>
          <span class="subscription-badge free">🆓 Free Plan</span>
        <?php endif; ?>
        
       <h1>Selamat Petang, <?php echo htmlspecialchars($user['full_name']); ?>! </h1>
        <p>Ready to continue your coding journey? Let's build something amazing today.</p>
        
        <?php if(!$is_premium): ?>
          <div style="margin-top: 20px;">
            <a href="payment/subscription.php" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #8b6914; font-weight: 800; border-radius: 12px; text-decoration: none; box-shadow: 0 8px 20px rgba(255, 215, 0, 0.3); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 28px rgba(255, 215, 0, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 20px rgba(255, 215, 0, 0.3)'">
              ⭐ Upgrade to Premium - RM20/month
            </a>
          </div>
        <?php endif; ?>
        
        <div class="stats-row">
          <div class="stat-card">
            <div class="stat-number"><?php echo $courses_accessed_count; ?></div>
            <div class="stat-label">Courses Accessed</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo $videos_watched_count; ?></div>
            <div class="stat-label">Videos Watched</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo $unique_quizzes_attempted; ?></div>
            <div class="stat-label">Quizzes Attempted</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo round($overall_progress, 1); ?>%</div>
            <div class="stat-label">Overall Progress</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- MAIN CONTENT -->
  <section class="main-content">
    <div class="container">
      <div class="section-header">
        <h2>Available Courses</h2>
      </div>
      
      <div class="course-grid">
        <?php
        // Fetch all courses from database
        $courses_query = "SELECT * FROM courses ORDER BY created_at ASC";
        $courses_result = mysqli_query($conn, $courses_query);
        
        $course_icons = [
          1 => ['icon' => 'Programming?', 'gradient' => 'linear-gradient(135deg, #17a2b8 0%, #138496 100%)'],
          2 => ['icon' => 'HTML + CSS', 'gradient' => 'linear-gradient(135deg, #2f57ef 0%, #1d3fc9 100%)'],
          3 => ['icon' => 'JS', 'gradient' => 'linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%)'],
          4 => ['icon' => 'PHP', 'gradient' => 'linear-gradient(135deg, #fd7e14 0%, #e8590c 100%)'],
          5 => ['icon' => 'DB', 'gradient' => 'linear-gradient(135deg, #20c997 0%, #17a689 100%)']
        ];
        
        // Map course IDs to dedicated pages in Available Courses folder
$course_pages = [
  1 => 'Available%20Courses/programming%20Basics/programming_basics.php',
  2 => 'Available%20Courses/web%20development/web_development.php',
  3 => 'Available%20Courses/javascript%20essentials/javascript_essentials.php',
  4 => 'Available%20Courses/php%20backend/php_backend.php',
  5 => 'Available%20Courses/mysql%20database/mysql_database.php'
];
        
        while($course = mysqli_fetch_assoc($courses_result)):
          $course_id = $course['course_id'];
          $icon_data = isset($course_icons[$course_id]) ? $course_icons[$course_id] : ['icon' => '📚', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'];
          $course_link = isset($course_pages[$course_id]) ? $course_pages[$course_id] : 'course_lessons.php?id=' . $course_id;
          
          // Count lessons for this course
          $lesson_count_query = "SELECT COUNT(*) as total FROM lessons WHERE course_id = " . $course_id;
          $lesson_count_result = mysqli_query($conn, $lesson_count_query);
          $lesson_count = mysqli_fetch_assoc($lesson_count_result)['total'];
        ?>
        <div class="course-card" onclick="window.location.href='<?php echo $course_link; ?>'" style="cursor: pointer;">
          <div class="course-image" style="background: <?php echo $icon_data['gradient']; ?>;">
            <?php echo $icon_data['icon']; ?>
          </div>
          <div class="course-body">
            <h3 class="course-title"><?php echo htmlspecialchars($course['course_title']); ?></h3>
            <p class="course-desc"><?php echo htmlspecialchars($course['description']); ?></p>
            <div class="course-meta">
              <span class="badge badge-green"><?php echo $course['difficulty_level']; ?></span>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <div class="footer">
    <div class="container">
      © <?php echo date("Y"); ?> JomCoding • Built with XAMPP (PHP + MySQL) • Made with ❤️ for learners
    </div>
  </div>

  <!-- LOGOUT CONFIRMATION MODAL -->
  <div class="modal-overlay" id="logoutModal">
    <div class="modal-box">
      <div class="modal-icon">👋</div>
      <h2 class="modal-title">Logout Confirmation</h2>
      <p class="modal-message">Are you sure you want to logout? You'll need to login again to access your courses and progress.</p>
      <div class="modal-actions">
        <button class="modal-btn cancel" onclick="hideLogoutModal()">No, Stay</button>
        <button class="modal-btn confirm" onclick="confirmLogout()">Yes, Logout</button>
      </div>
    </div>
  </div>

  <script>
    // Show logout modal
    function showLogoutModal() {
      const modal = document.getElementById('logoutModal');
      modal.classList.add('active');
      document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    // Hide logout modal
    function hideLogoutModal() {
      const modal = document.getElementById('logoutModal');
      modal.classList.remove('active');
      document.body.style.overflow = 'auto'; // Restore scrolling
    }

    // Confirm logout
    function confirmLogout() {
      window.location.href = 'logout.php';
    }

    // Close modal when clicking outside
    document.getElementById('logoutModal').addEventListener('click', function(e) {
      if (e.target === this) {
        hideLogoutModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        hideLogoutModal();
      }
    });
  </script>

</body>
</html>