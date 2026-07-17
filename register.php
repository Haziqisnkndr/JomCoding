<?php
// register.php
session_start();

require_once 'config.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if(empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif(strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $check_query = "SELECT user_id FROM user WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $error = 'Email already registered';
        } else {
            // Generate username from email
            $username = explode('@', $email)[0];
            $base_username = mysqli_real_escape_string($conn, $username);
            $username = $base_username;
            $counter = 1;
            
            // Check if username exists and add number if needed
            while(true) {
                $check_username = "SELECT user_id FROM user WHERE username = '$username'";
                $check_username_result = mysqli_query($conn, $check_username);
                
                if(mysqli_num_rows($check_username_result) == 0) {
                    break;
                }
                $username = $base_username . $counter;
                $counter++;
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $insert_query = "INSERT INTO user (username, email, password_hash, role, created_at) 
                            VALUES ('$username', '$email', '$hashed_password', 'student', NOW())";
            
            if(mysqli_query($conn, $insert_query)) {
                // Get the new user's ID
                $new_user_id = mysqli_insert_id($conn);
                
                // Log them in automatically
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['email'] = $email;
                $_SESSION['temp_user'] = true; // Mark as temporary until profile is complete
                
                $success = 'Account created! Please complete your profile...';
                header("refresh:1;url=complete-profile.php");
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign Up | JomCoding</title>

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
    
    .password-strength {
      margin-top: 8px;
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
    }
    
    .password-match {
      margin-top: 8px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .password-match.match {
      color: #20c997;
    }
    
    .password-match.nomatch {
      color: #ff4a57;
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
          <!-- Rocket -->
          <g transform="translate(150, 80)">
            <!-- Rocket body -->
            <path d="M 50 0 L 70 50 L 70 100 L 30 100 L 30 50 Z" fill="#fff" opacity="0.95"/>
            <circle cx="50" cy="30" r="8" fill="#667eea"/>
            
            <!-- Flames -->
            <ellipse cx="40" cy="100" rx="8" ry="15" fill="#ff4a57" opacity="0.8">
              <animate attributeName="ry" values="15;20;15" dur="1s" repeatCount="indefinite"/>
            </ellipse>
            <ellipse cx="60" cy="100" rx="8" ry="15" fill="#ff4a57" opacity="0.8">
              <animate attributeName="ry" values="15;18;15" dur="1.2s" repeatCount="indefinite"/>
            </ellipse>
            
            <!-- Window -->
            <circle cx="50" cy="30" r="12" fill="#667eea" opacity="0.3"/>
            
            <!-- Fins -->
            <path d="M 30 70 L 10 90 L 30 90 Z" fill="#764ba2"/>
            <path d="M 70 70 L 90 90 L 70 90 Z" fill="#764ba2"/>
          </g>
          
          <!-- Stars -->
          <circle cx="80" cy="50" r="3" fill="#fff" opacity="0.8">
            <animate attributeName="opacity" values="0.8;0.3;0.8" dur="2s" repeatCount="indefinite"/>
          </circle>
          <circle cx="300" cy="80" r="4" fill="#fff" opacity="0.7">
            <animate attributeName="opacity" values="0.7;0.2;0.7" dur="3s" repeatCount="indefinite"/>
          </circle>
          <circle cx="100" cy="200" r="2" fill="#fff" opacity="0.9">
            <animate attributeName="opacity" values="0.9;0.4;0.9" dur="2.5s" repeatCount="indefinite"/>
          </circle>
          <circle cx="320" cy="180" r="3" fill="#fff" opacity="0.8">
            <animate attributeName="opacity" values="0.8;0.3;0.8" dur="2.8s" repeatCount="indefinite"/>
          </circle>
          
          <!-- Checkmark badge -->
          <circle cx="280" cy="140" r="25" fill="#20c997"/>
          <path d="M 270 140 L 277 147 L 290 134" stroke="#fff" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        
        <h2>Start Your Journey!</h2>
        <p>Join thousands of learners who are mastering coding skills through interactive practice.</p>
      </div>
    </div>
    
    <!-- Right Side - Register Form -->
    <div class="auth-right">
      <div class="brand-header">
        <div class="brand-logo">
          <span>🚀</span> JomCoding
        </div>
      </div>
      
      <div class="auth-header">
        <h1>Create Account</h1>
        <p>Sign up to start learning</p>
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
      
      <form method="POST" action="" onsubmit="return validateForm()">
        <div class="form-group">
          <label for="email">Email</label>
          <div class="input-wrapper">
            <input type="email" id="email" name="email" placeholder="your@email.com" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
          </div>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <input type="password" id="password" name="password" placeholder="At least 6 characters" required
                   oninput="checkPasswordStrength(); checkPasswordMatch();">
            <span class="password-toggle" onclick="togglePassword('password')">👁️</span>
          </div>
          <div id="password-strength" class="password-strength"></div>
        </div>
        
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <div class="input-wrapper">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required
                   oninput="checkPasswordMatch();">
            <span class="password-toggle" onclick="togglePassword('confirm_password')">👁️</span>
          </div>
          <div id="password-match" class="password-match"></div>
        </div>
        
        <button type="submit" class="btn btn-primary">Create Account</button>
      </form>
      
      <div class="divider">OR</div>
      
    
      
      <div class="text-center">
        <span style="color: var(--muted);">Already have an account?</span>
        <a href="login.php" class="link">Login</a>
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
    
    function checkPasswordStrength() {
      const password = document.getElementById('password').value;
      const strengthDiv = document.getElementById('password-strength');
      
      if (password.length === 0) {
        strengthDiv.textContent = '';
        return;
      }
      
      if (password.length < 6) {
        strengthDiv.textContent = '⚠️ Too short (minimum 6 characters)';
        strengthDiv.style.color = '#ff4a57';
      } else if (password.length < 8) {
        strengthDiv.textContent = '💛 Weak password';
        strengthDiv.style.color = '#fd7e14';
      } else if (password.length < 12) {
        strengthDiv.textContent = '💚 Good password';
        strengthDiv.style.color = '#20c997';
      } else {
        strengthDiv.textContent = '💪 Strong password';
        strengthDiv.style.color = '#20c997';
      }
    }
    
    function checkPasswordMatch() {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const matchDiv = document.getElementById('password-match');
      
      if (confirmPassword.length === 0) {
        matchDiv.textContent = '';
        return;
      }
      
      if (password === confirmPassword) {
        matchDiv.textContent = '✅ Passwords match';
        matchDiv.className = 'password-match match';
      } else {
        matchDiv.textContent = '❌ Passwords do not match';
        matchDiv.className = 'password-match nomatch';
      }
    }
    
    function validateForm() {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      
      if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return false;
      }
      
      if (password.length < 6) {
        alert('Password must be at least 6 characters!');
        return false;
      }
      
      return true;
    }
  </script>
</body>
</html>