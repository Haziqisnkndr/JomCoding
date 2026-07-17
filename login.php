<?php
// login.php
session_start();

require_once 'config.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $query = "SELECT * FROM user WHERE email = '$email' LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if(mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            if(password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                // Check if profile is complete
                if($user['profile_completed'] == 0) {
                    $_SESSION['temp_user'] = true;
                    header("Location: complete-profile.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login | JomCoding</title>

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
      background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
      min-height: 100vh;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
      overflow-y: auto;
    }
    
    a{color:inherit; text-decoration:none;}

    .auth-wrapper {
      display: flex;
      max-width: 1100px;
      width: 100%;
      background: #fff;
      border-radius: 32px;
      overflow: hidden;
      box-shadow: 0 30px 80px rgba(0,0,0,0.2);
      animation: slideUp 0.6s ease-out;
      margin: 40px auto;
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .auth-left {
      flex: 1;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      position: relative;
      overflow: hidden;
    }
    
    .auth-left::before {
      content: '';
      position: absolute;
      width: 400px;
      height: 400px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
      top: -100px;
      right: -100px;
    }
    
    .auth-left::after {
      content: '';
      position: absolute;
      width: 300px;
      height: 300px;
      background: rgba(255,255,255,0.08);
      border-radius: 50%;
      bottom: -80px;
      left: -80px;
    }
    
    .illustration {
      position: relative;
      z-index: 2;
      text-align: center;
    }
    
    .illustration-img {
      width: 100%;
      max-width: 380px;
      margin-bottom: 32px;
      filter: drop-shadow(0 20px 40px rgba(0,0,0,0.15));
      animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-20px); }
    }
    
    .illustration h2 {
      color: #fff;
      font-size: 32px;
      font-weight: 900;
      margin-bottom: 12px;
      letter-spacing: -0.5px;
    }
    
    .illustration p {
      color: rgba(255,255,255,0.9);
      font-size: 16px;
      line-height: 1.6;
      max-width: 320px;
    }
    
    .auth-right {
      flex: 1;
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .brand-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 40px;
    }
    
    .brand-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 900;
      font-size: 24px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .auth-header h1 {
      font-size: 32px;
      color: var(--text);
      margin-bottom: 8px;
      font-weight: 900;
      letter-spacing: -0.8px;
    }
    
    .auth-header p {
      color: var(--muted);
      font-size: 15px;
      margin-bottom: 32px;
    }
    
    .form-group {
      margin-bottom: 24px;
      position: relative;
    }
    
    label {
      display: block;
      margin-bottom: 10px;
      font-weight: 700;
      font-size: 14px;
      color: var(--text);
    }
    
    .input-wrapper {
      position: relative;
    }
    
    input[type="email"],
    input[type="password"],
    input[type="text"] {
      width: 100%;
      padding: 16px 48px 16px 16px;
      border: 2px solid var(--border);
      border-radius: 14px;
      font-size: 15px;
      font-family: inherit;
      transition: all 0.3s ease;
      background: #fff;
    }
    
    input[type="email"]:focus,
    input[type="password"]:focus,
    input[type="text"]:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(47, 87, 239, 0.1);
    }
    
    .password-toggle {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      user-select: none;
      font-size: 20px;
      color: var(--muted);
      transition: color 0.3s ease;
    }
    
    .password-toggle:hover {
      color: var(--primary);
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
      margin-top: 8px;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #fff;
      box-shadow: 0 16px 32px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 20px 40px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:active {
      transform: translateY(0px);
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
    
    .divider {
      text-align: center;
      margin: 32px 0;
      color: var(--muted);
      font-size: 14px;
      font-weight: 600;
      position: relative;
    }
    
    .divider::before,
    .divider::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 40%;
      height: 1px;
      background: var(--border);
    }
    
    .divider::before { left: 0; }
    .divider::after { right: 0; }
    
    .social-login {
      display: flex;
      gap: 12px;
      margin-bottom: 24px;
    }
    
    .social-btn {
      flex: 1;
      padding: 14px;
      border: 2px solid var(--border);
      border-radius: 12px;
      background: #fff;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }
    
    .social-btn:hover {
      border-color: var(--primary);
      transform: translateY(-2px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }
    
    .text-center {
      text-align: center;
      margin-top: 24px;
    }
    
    .link {
      color: var(--primary);
      font-weight: 700;
      transition: color 0.3s ease;
    }
    
    .link:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }
    
    .signup-link {
      text-align: right;
      margin-top: 8px;
      font-size: 14px;
      color: var(--muted);
    }
    
    @media (max-width: 900px) {
      .auth-wrapper {
        flex-direction: column;
        max-width: 500px;
        margin: 20px auto;
      }
      
      .auth-left {
        padding: 40px 30px;
      }
      
      .auth-right {
        padding: 40px 30px;
      }
      
      .illustration-img {
        max-width: 280px;
      }
      
      .illustration h2 {
        font-size: 26px;
      }
    }
    
    @media (max-width: 600px) {
      .auth-wrapper {
        border-radius: 20px;
        margin: 10px auto;
      }
      
      .auth-left {
        padding: 30px 20px;
      }
      
      .auth-right {
        padding: 30px 20px;
      }
      
      .brand-logo {
        font-size: 20px;
      }
      
      .auth-header h1 {
        font-size: 26px;
      }
      
      .illustration h2 {
        font-size: 22px;
      }
      
      .illustration-img {
        max-width: 220px;
      }
    }
  </style>
</head>

<body>
  <div class="auth-wrapper">
    <!-- Left Side - Illustration -->
    <div class="auth-left">
      <div class="illustration">
        <!-- SVG Illustration -->
        <svg class="illustration-img" viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
          <!-- Laptop -->
          <rect x="80" y="80" width="240" height="160" rx="8" fill="#fff" opacity="0.95"/>
          <rect x="90" y="90" width="220" height="130" rx="4" fill="#667eea"/>
          
          <!-- Code lines -->
          <line x1="110" y1="110" x2="180" y2="110" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity="0.8"/>
          <line x1="110" y1="130" x2="200" y2="130" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity="0.8"/>
          <line x1="110" y1="150" x2="160" y2="150" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity="0.8"/>
          <line x1="110" y1="170" x2="190" y2="170" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity="0.8"/>
          
          <!-- Lock Icon -->
          <circle cx="250" cy="140" r="30" fill="#ff4a57"/>
          <rect x="240" y="150" width="20" height="15" rx="2" fill="#fff"/>
          <path d="M 245 150 Q 245 140 250 140 T 255 150" stroke="#fff" stroke-width="3" fill="none" stroke-linecap="round"/>
          
          <!-- Checkmark -->
          <circle cx="140" cy="60" r="20" fill="#20c997"/>
          <path d="M 133 60 L 138 65 L 147 56" stroke="#fff" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
          
          <!-- Base -->
          <rect x="150" y="240" width="100" height="8" rx="4" fill="#fff" opacity="0.9"/>
        </svg>
        
        <h2>Welcome to JomCoding!</h2>
        <p>Start your coding journey with interactive lessons and hands-on practice.</p>
      </div>
    </div>
    
    <!-- Right Side - Login Form -->
    <div class="auth-right">
      <div class="brand-header">
        <div class="brand-logo">
          <span>🚀</span> JomCoding
        </div>
      </div>
      
      <div class="auth-header">
        <h1>Welcome Back!</h1>
        <p>Login to your account</p>
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
      
      <form method="POST" action="">
        <div class="form-group">
          <label for="email">Email</label>
          <div class="input-wrapper">
            <input type="email" id="email" name="email" placeholder="your@email.com" required>
          </div>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            <span class="password-toggle" onclick="togglePassword('password')">👁️</span>
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Login</button>
      </form>
      
      <div class="divider">OR</div>
      
  
      
      <div class="text-center">
        <span style="color: var(--muted);">Don't have an account?</span>
        <a href="register.php" class="link">Sign Up</a>
      </div>
    </div>
  </div>

  <script>
    function togglePassword(fieldId) {
      const field = document.getElementById(fieldId);
      const icon = event.target;
      
      if (field.type === 'password') {
        field.type = 'text';
        icon.textContent = '🙈';
      } else {
        field.type = 'password';
        icon.textContent = '👁️';
      }
    }
  </script>
</body>
</html>