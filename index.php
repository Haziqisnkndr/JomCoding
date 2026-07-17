<?php
// index.php
session_start();
require_once 'config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>JomCoding | Learn Coding</title>

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

    /* Main Navbar with glassmorphism */
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
    
    .nav-links a:not(.btn)::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 0;
      width: 0;
      height: 2px;
      background: var(--primary);
      transition: width 0.3s ease;
    }
    
    .nav-links a:not(.btn):hover::after {
      width: 100%;
    }
    
    .btn{
      display:inline-flex; align-items:center; justify-content:center;
      padding: 11px 18px;
      border-radius: 12px;
      font-weight: 800;
      font-size: 14px;
      cursor:pointer;
      border: 1px solid var(--border);
      background: #fff;
      transition: all 0.3s ease;
    }
    
    .btn.primary{
      border:none;
      background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
      color:#fff;
      box-shadow: 0 14px 30px rgba(255,74,87,.25);
    }
    
    .btn.primary:hover{
      transform: translateY(-2px);
      box-shadow: 0 18px 40px rgba(255,74,87,.35);
    }
    
    .btn.ghost:hover{
      background:#f1f5ff;
      transform: translateY(-2px);
    }
    
    .user-menu{
      display:flex;
      align-items:center;
      gap:10px;
      padding: 8px 14px;
      border-radius: 12px;
      background: #fff;
      border: 1px solid var(--border);
      font-weight:700;
      font-size: 14px;
      transition: all 0.3s ease;
    }
    
    .user-menu:hover{
      background:#f1f5ff;
      border-color: var(--primary);
      transform: translateY(-2px);
    }
    
    .avatar{
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:900;
      font-size: 14px;
      box-shadow: 0 4px 12px rgba(47, 87, 239, 0.3);
    }

    /* Hero with enhanced animations */
    .hero{
      background: linear-gradient(135deg, #0f1629 0%, #1a2847 50%, #141b3a 100%);
      color: #fff;
      padding: 80px 0;
      position: relative;
      overflow:hidden;
    }
    
    .hero::before{
      content:"";
      position:absolute;
      inset:-60px -60px auto auto;
      width: 600px; height: 600px;
      background: radial-gradient(circle at 30% 30%, rgba(47,87,239,.65), transparent 60%);
      filter: blur(80px);
      opacity: 0.6;
      animation: pulse 8s ease-in-out infinite;
    }
    
    .hero::after{
      content:"";
      position:absolute;
      inset:auto auto -60px -60px;
      width: 500px; height: 500px;
      background: radial-gradient(circle at 70% 70%, rgba(255,74,87,.45), transparent 60%);
      filter: blur(80px);
      opacity: 0.4;
      animation: pulse 10s ease-in-out infinite;
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 0.6; }
      50% { transform: scale(1.1); opacity: 0.8; }
    }
    
    .hero-grid{
      display:grid;
      grid-template-columns: 1.1fr .9fr;
      gap: 40px;
      align-items:center;
      position: relative;
      z-index: 2;
    }
    
    .hero h1{
      margin: 12px 0 16px;
      font-size: clamp(32px, 4.5vw, 52px);
      line-height: 1.12;
      letter-spacing: -1px;
      animation: fadeInUp 0.8s ease-out;
    }
    
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .hero p{
      margin: 0;
      color: rgba(255,255,255,.88);
      line-height: 1.7;
      font-size: 17px;
      max-width: 620px;
      animation: fadeInUp 0.8s ease-out 0.2s both;
    }
    
    .hero-actions{
      margin-top: 28px;
      display:flex;
      gap:14px;
      flex-wrap:wrap;
      align-items:center;
      animation: fadeInUp 0.8s ease-out 0.4s both;
    }
    
    .btn.hero-primary{
      background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
      color:#fff;
      border:none;
      padding: 14px 28px;
      font-size: 16px;
      box-shadow: 0 16px 40px rgba(255,74,87,.35);
    }
    
    .btn.hero-primary:hover{
      transform: translateY(-3px);
      box-shadow: 0 20px 50px rgba(255,74,87,.45);
    }
    
    .btn.hero-secondary{
      background: rgba(255,255,255,.1);
      color:#fff;
      border: 1px solid rgba(255,255,255,.3);
      backdrop-filter: blur(10px);
      padding: 14px 28px;
      font-size: 16px;
    }
    
    .btn.hero-secondary:hover{
      background: rgba(255,255,255,.15);
      border-color: rgba(255,255,255,.5);
      transform: translateY(-3px);
    }
    
    .hero-card{
      background: rgba(255,255,255,.09);
      border: 1px solid rgba(255,255,255,.18);
      border-radius: 20px;
      padding: 24px;
      box-shadow: 0 24px 60px rgba(0,0,0,.3);
      backdrop-filter: blur(12px);
      animation: fadeInUp 0.8s ease-out 0.6s both;
      transition: transform 0.3s ease;
    }
    
    .hero-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 30px 70px rgba(0,0,0,.4);
    }
    
    .codebox{
      margin-top: 14px;
      background: rgba(0,0,0,.4);
      border: 1px solid rgba(255,255,255,.15);
      border-radius: 14px;
      padding: 18px;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
      font-size: 13px;
      line-height: 1.65;
      overflow:auto;
      color: #dbeafe;
      white-space: pre;
      box-shadow: inset 0 2px 8px rgba(0,0,0,.3);
    }
    
    .stats{
      display:flex; gap:12px; flex-wrap:wrap;
      margin-top: 18px;
      color: rgba(255,255,255,.9);
      font-weight: 800;
      font-size: 13px;
    }
    
    .stat{
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 999px;
      padding: 10px 16px;
      transition: all 0.3s ease;
      backdrop-filter: blur(8px);
    }
    
    .stat:hover {
      background: rgba(255,255,255,.18);
      transform: translateY(-2px);
    }

    /* Categories with enhanced cards */
    .section{
      padding: 70px 0;
      position: relative;
    }
    
    .section h2{
      margin:0;
      font-size: 38px;
      letter-spacing:-.8px;
      background: linear-gradient(135deg, var(--text) 0%, var(--primary) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .section p{
      margin: 12px 0 0;
      color: var(--muted);
      line-height:1.7;
      max-width: 760px;
      font-size: 17px;
    }

    .cat-grid{
      margin-top: 40px;
      display:grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 20px;
    }
    
    .cat{
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 28px 20px;
      box-shadow: 0 14px 40px rgba(15,23,42,.06);
      text-align:center;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }
    
    .cat::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(47,87,239,0.05), transparent);
      transition: left 0.5s ease;
    }
    
    .cat:hover::before {
      left: 100%;
    }
    
    .cat:hover{
      transform: translateY(-8px);
      box-shadow: 0 24px 60px rgba(15,23,42,.12);
      border-color: rgba(47,87,239,0.3);
    }
    
    .icon{
      width: 64px; height: 64px;
      margin: 0 auto 16px;
      border-radius: 18px;
      display:flex; align-items:center; justify-content:center;
      color:#fff;
      font-weight: 900;
      font-size: 22px;
      box-shadow: 0 12px 28px rgba(0,0,0,.2);
      transition: all 0.3s ease;
    }
    
    .cat:hover .icon {
      transform: scale(1.1) rotate(5deg);
      box-shadow: 0 16px 36px rgba(0,0,0,.28);
    }
    
    .cat h3{
      margin:0; 
      font-size: 16px;
      font-weight: 800;
    }
    
    .cat small{
      display:block; 
      margin-top:8px; 
      color: var(--muted); 
      font-weight:600;
      font-size: 13px;
    }

    /* Badge animation */
    .badge {
      display: inline-block;
      font-weight:900; 
      letter-spacing:.14em; 
      font-size:12px; 
      color:rgba(255,255,255,.8);
      background: rgba(255,255,255,.1);
      padding: 8px 16px;
      border-radius: 8px;
      border: 1px solid rgba(255,255,255,.2);
      animation: fadeInUp 0.8s ease-out;
    }

    /* Features Grid */
    .features-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 24px;
      margin-top: 40px;
    }

    .feature-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 32px 24px;
      text-align: center;
      transition: all 0.4s ease;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }

    .feature-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 50px rgba(47, 87, 239, 0.15);
      border-color: var(--primary);
    }

    .feature-icon {
      font-size: 48px;
      margin-bottom: 16px;
      animation: bounce 2s infinite;
    }

    .feature-card:hover .feature-icon {
      animation: bounce 0.6s;
    }

    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    .feature-card h3 {
      margin: 12px 0;
      font-size: 18px;
      font-weight: 800;
    }

    .feature-card p {
      margin: 8px 0 0;
      color: var(--muted);
      line-height: 1.6;
      font-size: 14px;
    }

    /* Stats Showcase */
    .stats-showcase {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 30px;
      padding: 40px 0;
    }

    .stat-box {
      background: var(--card);
      border: 2px solid var(--border);
      border-radius: 20px;
      padding: 40px 20px;
      text-align: center;
      transition: all 0.3s ease;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .stat-box:hover {
      transform: translateY(-5px) scale(1.05);
      border-color: var(--primary);
      box-shadow: 0 16px 40px rgba(47, 87, 239, 0.2);
    }

    .stat-number {
      font-size: 48px;
      font-weight: 900;
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 8px;
    }

    .stat-label {
      font-size: 14px;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Testimonials */
    .testimonial-slider {
      position: relative;
      max-width: 800px;
      margin: 0 auto;
      min-height: 280px;
    }

    .testimonial {
      position: absolute;
      width: 100%;
      opacity: 0;
      transform: translateX(50px);
      transition: all 0.6s ease;
      pointer-events: none;
    }

    .testimonial.active {
      opacity: 1;
      transform: translateX(0);
      pointer-events: auto;
    }

    .testimonial-content {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
      position: relative;
    }

    .quote-icon {
      font-size: 64px;
      color: var(--primary);
      opacity: 0.2;
      position: absolute;
      top: 20px;
      left: 30px;
      font-family: Georgia, serif;
    }

    .testimonial-content p {
      font-size: 18px;
      line-height: 1.7;
      color: var(--text);
      margin: 0 0 24px;
      position: relative;
      z-index: 1;
      font-style: italic;
    }

    .testimonial-author {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .author-avatar {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 900;
      font-size: 18px;
      box-shadow: 0 8px 20px rgba(47, 87, 239, 0.3);
    }

    .author-name {
      font-weight: 800;
      font-size: 16px;
      color: var(--text);
    }

    .author-role {
      font-size: 14px;
      color: var(--muted);
      font-weight: 600;
      margin-top: 2px;
    }

    .slider-dots {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin-top: 32px;
    }

    .dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: var(--border);
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .dot:hover {
      background: var(--muted);
    }

    .dot.active {
      background: var(--primary);
      width: 32px;
      border-radius: 6px;
    }

    /* CTA Box */
    .cta-box {
      padding: 60px 40px;
      text-align: center;
    }

    /* Footer */
    .footer{
      padding: 32px 0;
      border-top: 1px solid var(--border);
      color: var(--muted);
      font-size: 14px;
      background: #fff;
      font-weight: 600;
    }

    /* responsive */
    @media (max-width: 1100px){
      .cat-grid{grid-template-columns: repeat(3, 1fr);}
      .features-grid { grid-template-columns: repeat(2, 1fr); }
      .stats-showcase { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 900px){
      .hero-grid{grid-template-columns: 1fr;}
      .nav-links .hide-sm{display:none}
      .hero{padding: 60px 0;}
    }
    
    @media (max-width: 768px){
      .features-grid { grid-template-columns: 1fr; }
      .stats-showcase { grid-template-columns: repeat(2, 1fr); gap: 16px; }
      .testimonial-content { padding: 28px; }
    }
    
    @media (max-width: 560px){
      .cat-grid{grid-template-columns: repeat(2, 1fr);}
      .hero h1{font-size: 28px;}
    }
  </style>
</head>

<body>

  <!-- NAVBAR -->
  <div class="navbar">
    <div class="container">
      <div class="nav-inner">
        <a class="brand" href="index.php">
          <span>🚀</span> JomCoding
        </a>

        <div class="nav-links">
          <?php if($is_logged_in): ?>
            <!-- Logged in navigation -->
            <a class="hide-sm" href="index.php">Home</a>
            <a class="btn primary" href="dashboard.php">My Dashboard</a>
            <a class="user-menu" href="dashboard.php">
              <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
              <span><?php echo htmlspecialchars($username); ?></span>
            </a>
          <?php else: ?>
            <!-- Logged out navigation -->
            <a class="hide-sm" href="#home">Home</a>
            <a class="btn ghost" href="login.php">Login</a>
            <a class="btn primary" href="register.php">Get Started</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- HERO -->
  <section id="home" class="hero">
    <div class="container">
      <div class="hero-grid">
        <div>
          <div class="badge">
            ✨ GET THE CERTIFICATES AND KNOWLEDGES
          </div>

          <h1>Upgrade your coding skills & upgrade your future.</h1>

          <p>
            JomCoding is a beginner-friendly learning platform where students learn through
            structured courses, short lessons, and hands-on coding exercises with progress tracking.
          </p>

          <div class="hero-actions">
            <?php if($is_logged_in): ?>
              <a class="btn hero-primary" href="dashboard.php">Go to Dashboard →</a>
              <a class="btn hero-secondary" href="dashboard.php">My Courses</a>
            <?php else: ?>
              <a class="btn hero-primary" href="register.php">Get Started Free →</a>
              <a class="btn hero-secondary" href="#categories">Explore Courses</a>
            <?php endif; ?>
          </div>

          <div class="stats">
            <div class="stat">✅ Beginner Friendly</div>
            <div class="stat">🧠 Learn Step-by-Step</div>
            <div class="stat">💻 Practice & Submit</div>
          </div>
        </div>

        <div class="hero-card">
          <div style="font-weight:900; font-size: 15px;">⚡ Quick Preview: PHP Exercise</div>
          <div class="codebox"><?php echo htmlspecialchars(
'<?php
// Task: Print "Hello JomCoding!"
echo "Hello JomCoding!";
?>'
); ?></div>
          <div style="margin-top:14px; color:rgba(255,255,255,.85); font-weight:700; font-size:13px;">
            💡 Tip: Learn with short lessons + practical tasks (like Codecademy style).
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php if(!$is_logged_in): ?>
  <!-- CATEGORIES (Only show when not logged in) -->
  <section id="categories" class="section">
    <div class="container">
      <h2>Categories you want to learn</h2>
      <p>Choose a track and start learning with lessons + exercises. Master coding through hands-on practice.</p>

      <div class="cat-grid">
        <div class="cat">
          <div class="icon" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">&lt;/&gt;</div>
          <h3>Programming Basics</h3>
          <small>Variables, loops</small>
        </div>
        <div class="cat">
          <div class="icon" style="background: linear-gradient(135deg, #2f57ef 0%, #1d3fc9 100%);">🌐</div>
          <h3>Web Development</h3>
          <small>HTML, CSS</small>
        </div>
        <div class="cat">
          <div class="icon" style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);">JS</div>
          <h3>JavaScript</h3>
          <small>Logic & DOM</small>
        </div>
        <div class="cat">
          <div class="icon" style="background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%);">PHP</div>
          <h3>PHP Backend</h3>
          <small>Forms & DB</small>
        </div>
        <div class="cat">
          <div class="icon" style="background: linear-gradient(135deg, #20c997 0%, #17a689 100%);">DB</div>
          <h3>MySQL</h3>
          <small>Queries & tables</small>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- FEATURES SECTION -->
  <section class="section" style="background: linear-gradient(180deg, #fff 0%, #f6f7fb 100%);">
    <div class="container">
      <h2 style="text-align: center;">Why Choose JomCoding?</h2>
      <p style="text-align: center; margin: 12px auto;">Everything you need to start your coding journey</p>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">📚</div>
          <h3>Structured Learning Path</h3>
          <p>Follow our carefully designed curriculum from beginner to advanced</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">⚡</div>
          <h3>Instant Feedback</h3>
          <p>Get real-time feedback on your code submissions</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🏆</div>
          <h3>Track Progress</h3>
          <p>Monitor your learning journey with detailed analytics</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">👥</div>
          <h3>Community Support</h3>
          <p>Join thousands of learners and help each other grow</p>
        </div>
      </div>
    </div>
  </section>

  <!-- LIVE STATS COUNTER -->
  <section class="section" style="background: var(--bg);">
    <div class="container">
      <div class="stats-showcase">
        <div class="stat-box">
          <div class="stat-number" data-target="102">0</div>
          <div class="stat-label">Active Students</div>
        </div>
        <div class="stat-box">
          <div class="stat-number" data-target="75">0</div>
          <div class="stat-label">Quizzes</div>
        </div>
        <div class="stat-box">
          <div class="stat-number" data-target="5">0</div>
          <div class="stat-label">Courses Available</div>
        </div>
        <div class="stat-box">
          <div class="stat-number" data-target="98">0</div>
          <div class="stat-label">Success Rate %</div>
        </div>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS SLIDER -->
  <section class="section">
    <div class="container">
      <h2 style="text-align: center;">What Our Students Say</h2>
      <p style="text-align: center; margin: 12px auto 40px;">Real feedback from real learners</p>

      <div class="testimonial-slider">
        <div class="testimonial active">
          <div class="testimonial-content">
            <div class="quote-icon">"</div>
            <p>JomCoding helped me land my first job as a web developer. The exercises are practical and fun!</p>
            <div class="testimonial-author">
              <div class="author-avatar">AM</div>
              <div>
                <div class="author-name">Ahmad Malik</div>
                <div class="author-role">Web Developer</div>
              </div>
            </div>
          </div>
        </div>
        <div class="testimonial">
          <div class="testimonial-content">
            <div class="quote-icon">"</div>
            <p>Best platform for beginners! The step-by-step approach makes learning easy and enjoyable.</p>
            <div class="testimonial-author">
              <div class="author-avatar">SL</div>
              <div>
                <div class="author-name">Siti Lina</div>
                <div class="author-role">Student</div>
              </div>
            </div>
          </div>
        </div>
        <div class="testimonial">
          <div class="testimonial-content">
            <div class="quote-icon">"</div>
            <p>I went from zero coding knowledge to building my own websites in just 3 months. Amazing!</p>
            <div class="testimonial-author">
              <div class="author-avatar">RK</div>
              <div>
                <div class="author-name">Raj Kumar</div>
                <div class="author-role">Freelancer</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="slider-dots">
        <span class="dot active" onclick="currentSlide(0)"></span>
        <span class="dot" onclick="currentSlide(1)"></span>
        <span class="dot" onclick="currentSlide(2)"></span>
      </div>
    </div>
  </section>

  <?php if(!$is_logged_in): ?>
  <!-- CTA SECTION (Only show when not logged in) -->
  <section class="section" style="background: linear-gradient(135deg, #2f57ef 0%, #6366f1 100%); color: #fff;">
    <div class="container">
      <div class="cta-box">
        <h2 style="color: #fff; text-align: center; margin-bottom: 16px;">Ready to Start Your Coding Journey?</h2>
        <p style="text-align: center; color: rgba(255,255,255,0.9); margin-bottom: 28px; font-size: 18px;">
          Join thousands of students learning to code with JomCoding today!
        </p>
        <div style="text-align: center;">
          <a class="btn hero-primary" href="register.php" style="font-size: 18px; padding: 16px 36px;">Start Learning for Free →</a>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- FOOTER -->
  <div class="footer">
    <div class="container">
    </div>
  </div>

  <script>
    // Animated Counter
    function animateCounter() {
      const counters = document.querySelectorAll('.stat-number');
      counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const updateCounter = () => {
          current += increment;
          if (current < target) {
            counter.textContent = Math.floor(current);
            requestAnimationFrame(updateCounter);
          } else {
            counter.textContent = target;
          }
        };
        updateCounter();
      });
    }

    // Intersection Observer for counter animation
    const observerOptions = {
      threshold: 0.5
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter();
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    const statsSection = document.querySelector('.stats-showcase');
    if (statsSection) {
      observer.observe(statsSection);
    }

    // Testimonial Slider
    let currentTestimonial = 0;
    const testimonials = document.querySelectorAll('.testimonial');
    const dots = document.querySelectorAll('.dot');

    function showTestimonial(index) {
      testimonials.forEach((t, i) => {
        t.classList.remove('active');
        dots[i].classList.remove('active');
        if (i === index) {
          t.classList.add('active');
          dots[i].classList.add('active');
        }
      });
    }

    function currentSlide(index) {
      currentTestimonial = index;
      showTestimonial(index);
    }

    function nextTestimonial() {
      currentTestimonial = (currentTestimonial + 1) % testimonials.length;
      showTestimonial(currentTestimonial);
    }

    // Auto-rotate testimonials every 5 seconds
    setInterval(nextTestimonial, 5000);
  </script>

</body>
</html>