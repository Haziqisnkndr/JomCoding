<?php
// subscription.php - UPDATED for /payment folder
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config.php';

$error = '';
$success = '';

// Handle subscription selection
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $plan_type = $_POST['plan_type']; // 'free' or 'premium'
    
    if($plan_type == 'premium') {
        // Set pending subscription and redirect to payment page
        $_SESSION['pending_subscription'] = 'premium';
        header("Location: payment.php");
        exit();
    } else {
        // Free plan - just update user type
        $update_user = "UPDATE user SET subscription_type = 'free' WHERE user_id = $user_id";
        mysqli_query($conn, $update_user);
        $_SESSION['subscription_type'] = 'free';
        header("Location: ../dashboard.php");
        exit();
    }
}

// Get user info
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE user_id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Choose Your Plan | JomCoding</title>

  <style>
    :root{
      --primary:#2f57ef;
      --primary-dark:#1d3fc9;
      --accent:#ff4a57;
      --accent-light:#ff6b76;
      --text:#0f172a;
      --muted:#64748b;
      --bg:#f6f7fb;
      --border:#e6e8f0;
    }
    
    *{box-sizing:border-box; margin:0; padding:0;}
    
    body{
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
     background: linear-gradient(135deg, #0f1629 0%, #1a2847 50%, #141b3a 100%);
      min-height: 100vh;
      padding: 40px 20px;
      position: relative;
      overflow-x: hidden;
      overflow-y: auto;
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
    
    body::after {
      content: '';
      position: absolute;
      width: 400px;
      height: 400px;
      background: rgba(255,255,255,0.08);
      border-radius: 50%;
      bottom: -150px;
      left: -150px;
      animation: float 10s ease-in-out infinite;
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-30px); }
    }
    
    .subscription-container {
      max-width: 1200px;
      width: 100%;
      position: relative;
      z-index: 2;
      animation: slideUp 0.6s ease-out;
      margin: 0 auto;
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .header {
      text-align: center;
      margin-bottom: 50px;
      color: #fff;
    }
    
    .brand-logo {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-weight: 900;
      font-size: 32px;
      margin-bottom: 20px;
    }
    
    .header h1 {
      font-size: 48px;
      margin-bottom: 16px;
      letter-spacing: -1px;
      font-weight: 900;
    }
    
    .header p {
      font-size: 18px;
      opacity: 0.95;
      max-width: 600px;
      margin: 0 auto;
      line-height: 1.6;
    }
    
    .pricing-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      margin-top: 40px;
    }
    
    .pricing-card {
      background: #fff;
      border-radius: 24px;
      padding: 40px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 30px 80px rgba(0,0,0,0.2);
      transition: all 0.4s ease;
      border: 3px solid transparent;
    }
    
    .pricing-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 40px 100px rgba(0,0,0,0.3);
    }
    
    .pricing-card.free {
      border-color: var(--border);
    }
    
    .pricing-card.premium {
      border-color: #ffd700;
      background: linear-gradient(180deg, #fff 0%, #fffef8 100%);
    }
    
    .badge {
      display: inline-block;
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 800;
      font-size: 12px;
      letter-spacing: 0.5px;
      margin-bottom: 20px;
      text-transform: uppercase;
    }
    
    .badge.free-badge {
      background: #e0f2fe;
      color: #0369a1;
    }
    
    .badge.premium-badge {
      background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
      color: #8b6914;
      box-shadow: 0 8px 20px rgba(255, 215, 0, 0.3);
    }
    
    .popular {
      position: absolute;
      top: 20px;
      right: -35px;
      background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
      color: #fff;
      padding: 8px 40px;
      font-weight: 800;
      font-size: 12px;
      transform: rotate(45deg);
      box-shadow: 0 4px 12px rgba(255, 74, 87, 0.4);
    }
    
    .plan-name {
      font-size: 28px;
      font-weight: 900;
      margin-bottom: 16px;
      color: var(--text);
    }
    
    .plan-price {
      font-size: 48px;
      font-weight: 900;
      color: var(--text);
      margin-bottom: 8px;
    }
    
    .plan-price span {
      font-size: 20px;
      color: var(--muted);
      font-weight: 600;
    }
    
    .plan-description {
      color: var(--muted);
      font-size: 15px;
      margin-bottom: 32px;
      line-height: 1.6;
    }
    
    .features-list {
      list-style: none;
      margin-bottom: 32px;
    }
    
    .features-list li {
      padding: 14px 0;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: flex-start;
      gap: 12px;
      font-size: 15px;
      color: var(--text);
    }
    
    .features-list li:last-child {
      border-bottom: none;
    }
    
    .check-icon {
      width: 24px;
      height: 24px;
      background: linear-gradient(135deg, #20c997 0%, #17a689 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 14px;
      font-weight: 900;
      flex-shrink: 0;
      margin-top: 2px;
    }
    
    .cross-icon {
      width: 24px;
      height: 24px;
      background: #fee;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #c33;
      font-size: 16px;
      font-weight: 900;
      flex-shrink: 0;
      margin-top: 2px;
    }
    
    .feature-text {
      flex: 1;
    }
    
    .feature-text.disabled {
      color: var(--muted);
      text-decoration: line-through;
      opacity: 0.5;
    }
    
    .btn {
      width: 100%;
      padding: 18px;
      border: none;
      border-radius: 14px;
      font-weight: 800;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: inherit;
      text-align: center;
      text-decoration: none;
      display: block;
    }
    
    .btn-free {
      background: #fff;
      color: var(--primary);
      border: 2px solid var(--primary);
    }
    
    .btn-free:hover {
      background: var(--primary);
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(47, 87, 239, 0.3);
    }
    
    .btn-premium {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #fff;
      box-shadow: 0 16px 32px rgba(102, 126, 234, 0.3);
    }
    
    .btn-premium:hover {
      transform: translateY(-2px);
      box-shadow: 0 20px 40px rgba(102, 126, 234, 0.4);
    }
    
    .alert {
      padding: 14px 18px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-weight: 600;
      font-size: 14px;
      animation: slideDown 0.3s ease-out;
      display: flex;
      align-items: center;
      gap: 8px;
      max-width: 600px;
      margin: 0 auto 30px;
    }
    
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .alert-error {
      background: #fee;
      color: #c33;
      border: 2px solid #fcc;
    }
    
    .alert-success {
      background: #efe;
      color: #3c3;
      border: 2px solid #cfc;
    }
    
    @media (max-width: 900px) {
      .pricing-grid {
        grid-template-columns: 1fr;
        max-width: 500px;
        margin: 40px auto 0;
      }
      
      .header h1 {
        font-size: 36px;
      }
      
      .pricing-card {
        padding: 32px 24px;
      }
      
      body {
        padding: 20px;
      }
    }
    
    @media (max-width: 600px) {
      .header h1 {
        font-size: 28px;
      }
      
      .header p {
        font-size: 16px;
      }
      
      .plan-price {
        font-size: 40px;
      }
      
      .popular {
        font-size: 10px;
        padding: 6px 35px;
      }
      
      .pricing-card {
        padding: 24px 20px;
      }
    }
  </style>
</head>

<body>
  <div class="subscription-container">
    <div class="header">
      <div class="brand-logo">
        <span>🚀</span> JomCoding
      </div>
      <h1>Choose Your Learning Plan</h1>
      <p>Start your coding journey with the plan that fits your goals. Upgrade anytime!</p>
    </div>
    
    <?php if($error): ?>
      <div class="alert alert-error">
        <span>⚠️</span> <?php echo $error; ?>
      </div>
    <?php endif; ?>
    
    <?php if($success): ?>
      <div class="alert alert-success">
        <span>✅</span> <?php echo $success; ?>
      </div>
    <?php endif; ?>
    
    <div class="pricing-grid">
      <!-- FREE PLAN -->
      <div class="pricing-card free">
        <div class="badge free-badge">Free Forever</div>
        <h3 class="plan-name">Basic Plan</h3>
        <div class="plan-price">RM0<span>/month</span></div>
        <p class="plan-description">Perfect for getting started with coding basics</p>
        
        <ul class="features-list">
          <li>
            <div class="check-icon">✓</div>
            <div class="feature-text">Access to first lesson of each course</div>
          </li>
          <li>
            <div class="check-icon">✓</div>
            <div class="feature-text">Basic coding exercises</div>
          </li>
          <li>
            <div class="check-icon">✓</div>
            <div class="feature-text">Community support</div>
          </li>
          <li>
            <div class="cross-icon">✗</div>
            <div class="feature-text disabled">Full course access</div>
          </li>
          <li>
            <div class="cross-icon">✗</div>
            <div class="feature-text disabled">Quizzes & assessments</div>
          </li>
          <li>
            <div class="cross-icon">✗</div>
            <div class="feature-text disabled">Certificates</div>
          </li>
        </ul>
        
        <form method="POST" action="">
          <input type="hidden" name="plan_type" value="free">
          <button type="submit" class="btn btn-free">Start with Free</button>
        </form>
      </div>
      
      <!-- PREMIUM PLAN -->
      <div class="pricing-card premium">
        <div class="popular">⭐ MOST POPULAR</div>
        <div class="badge premium-badge">💎 Premium</div>
        <h3 class="plan-name">Premium Plan</h3>
        <div class="plan-price">RM20<span>/month</span></div>
        <p class="plan-description">Unlock your full potential with complete access</p>
        
        <ul class="features-list">
          <li>
            <div class="check-icon">✓</div>
            <div class="feature-text"><strong>Full access to ALL courses</strong></div>
          </li>
          <li>
            <div class="check-icon">✓</div>
            <div class="feature-text"><strong>Unlimited coding exercises</strong></div>
          </li>
          <li>
            <div class="check-icon">✓</div>
            <div class="feature-text"><strong>Certificates upon completion</strong></div>
          </li>
          <li>
            <div class="check-icon">✓</div>
            <div class="feature-text"><strong>Advanced quizzes & assessments</strong></div>
          </li>
          <li>
            <div class="check-icon">✓</div>
            <div class="feature-text"><strong>Track your learning progress</strong></div>
          </li>
          <li>
            <div class="check-icon">✓</div>
            <div class="feature-text"><strong>Priority support</strong></div>
          </li>
        </ul>
        
        <form method="POST" action="">
          <input type="hidden" name="plan_type" value="premium">
          <button type="submit" class="btn btn-premium">Continue to Payment →</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>