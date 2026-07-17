<?php
// complete-profile.php - FIXED to redirect to payment/subscription.php
session_start();

// Check if user is logged in and profile is incomplete
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// If profile is already complete, redirect to dashboard
if(!isset($_SESSION['temp_user'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $birth_date = mysqli_real_escape_string($conn, $_POST['birth_date']);
    $country = mysqli_real_escape_string($conn, trim($_POST['country']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $bio = mysqli_real_escape_string($conn, trim($_POST['bio']));
    
    // Validation
    if(empty($full_name) || empty($birth_date) || empty($country)) {
        $error = 'Please fill in all required fields';
    } else {
        // Update user profile
        $update_query = "UPDATE user SET 
                        full_name = '$full_name',
                        birth_date = '$birth_date',
                        country = '$country',
                        phone = '$phone',
                        gender = '$gender',
                        bio = '$bio',
                        profile_completed = 1,
                        subscription_type = 'free'
                        WHERE user_id = $user_id";
        
        if(mysqli_query($conn, $update_query)) {
            // Get updated user info
            $user_query = "SELECT * FROM user WHERE user_id = $user_id";
            $user_result = mysqli_query($conn, $user_query);
            $user = mysqli_fetch_assoc($user_result);
            
            // Update session
            $_SESSION['username'] = $user['username'];
            unset($_SESSION['temp_user']);
            
            $success = 'Profile completed! Redirecting to subscription...';
            header("refresh:1;url=payment/subscription.php");
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

// Get current user info
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
  <title>Complete Profile | JomCoding</title>

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

    .profile-wrapper {
      max-width: 900px;
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
    
    .profile-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 40px 50px;
      color: #fff;
      position: relative;
      overflow: hidden;
    }
    
    .profile-header::before {
      content: '';
      position: absolute;
      width: 300px;
      height: 300px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
      top: -100px;
      right: -100px;
    }
    
    .brand-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 900;
      font-size: 24px;
      margin-bottom: 24px;
      position: relative;
      z-index: 2;
    }
    
    .profile-header h1 {
      font-size: 36px;
      margin-bottom: 12px;
      letter-spacing: -0.8px;
      position: relative;
      z-index: 2;
    }
    
    .profile-header p {
      font-size: 16px;
      opacity: 0.9;
      position: relative;
      z-index: 2;
    }
    
    .progress-bar {
      margin-top: 24px;
      height: 8px;
      background: rgba(255,255,255,0.2);
      border-radius: 999px;
      overflow: hidden;
      position: relative;
      z-index: 2;
    }
    
    .progress-fill {
      height: 100%;
      background: #fff;
      border-radius: 999px;
      width: 50%;
      animation: progressFill 0.8s ease-out;
    }
    
    @keyframes progressFill {
      from { width: 0%; }
      to { width: 50%; }
    }
    
    .profile-body {
      padding: 50px;
    }
    
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }
    
    .form-group {
      margin-bottom: 0;
    }
    
    .form-group.full {
      grid-column: 1 / -1;
    }
    
    label {
      display: block;
      margin-bottom: 10px;
      font-weight: 700;
      font-size: 14px;
      color: var(--text);
    }
    
    label .required {
      color: var(--accent);
    }
    
    input[type="text"],
    input[type="date"],
    input[type="tel"],
    select,
    textarea {
      width: 100%;
      padding: 16px;
      border: 2px solid var(--border);
      border-radius: 14px;
      font-size: 15px;
      font-family: inherit;
      transition: all 0.3s ease;
      background: #fff;
    }
    
    textarea {
      resize: vertical;
      min-height: 100px;
    }
    
    input:focus,
    select:focus,
    textarea:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(47, 87, 239, 0.1);
    }
    
    select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20'%3E%3Cpath fill='%2364748b' d='M5 7l5 5 5-5z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 40px;
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
      margin-top: 32px;
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
    
    .info-text {
      color: var(--muted);
      font-size: 13px;
      margin-top: 6px;
      font-weight: 500;
    }
    
    .radio-group {
      display: flex;
      gap: 16px;
      margin-top: 10px;
    }
    
    .radio-option {
      flex: 1;
    }
    
    .radio-option input[type="radio"] {
      display: none;
    }
    
    .radio-option label {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 14px;
      border: 2px solid var(--border);
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 600;
      margin: 0;
    }
    
    .radio-option input[type="radio"]:checked + label {
      border-color: var(--primary);
      background: rgba(47, 87, 239, 0.05);
      color: var(--primary);
    }
    
    .radio-option label:hover {
      border-color: var(--primary);
    }
    
    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
      
      .profile-header {
        padding: 32px 30px;
      }
      
      .profile-body {
        padding: 32px 30px;
      }
      
      .profile-header h1 {
        font-size: 28px;
      }
      
      .profile-wrapper {
        margin: 20px auto;
      }
    }
    
    @media (max-width: 600px) {
      .profile-header {
        padding: 24px 20px;
      }
      
      .profile-body {
        padding: 24px 20px;
      }
      
      .profile-header h1 {
        font-size: 24px;
      }
      
      .brand-logo {
        font-size: 20px;
      }
      
      .profile-wrapper {
        border-radius: 20px;
        margin: 10px auto;
      }
    }
  </style>
</head>

<body>
  <div class="profile-wrapper">
    <!-- Header -->
    <div class="profile-header">
      <div class="brand-logo">
        <span>🚀</span> JomCoding
      </div>
      <h1>Complete Your Profile</h1>
      <p>Just a few more details to personalize your learning experience</p>
      <div class="progress-bar">
        <div class="progress-fill"></div>
      </div>
    </div>
    
    <!-- Form Body -->
    <div class="profile-body">
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
        <div class="form-grid">
          <div class="form-group">
            <label for="full_name">
              Full Name <span class="required">*</span>
            </label>
            <input type="text" id="full_name" name="full_name" placeholder="John Doe" required>
          </div>
          
          <div class="form-group">
            <label for="birth_date">
              Date of Birth <span class="required">*</span>
            </label>
            <input type="date" id="birth_date" name="birth_date" required max="<?php echo date('Y-m-d'); ?>">
          </div>
          
          <div class="form-group">
            <label for="country">
              Country <span class="required">*</span>
            </label>
            <select id="country" name="country" required>
              <option value="">Select your country</option>
              <option value="Malaysia">Malaysia</option>
              <option value="Singapore">Singapore</option>
              <option value="Indonesia">Indonesia</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="phone">
              Phone Number (Optional)
            </label>
            <input type="tel" id="phone" name="phone" placeholder="+60 12-345 6789">
            <div class="info-text">We'll never share your phone number</div>
          </div>
          
          <div class="form-group full">
            <label>Gender</label>
            <div class="radio-group">
              <div class="radio-option">
                <input type="radio" id="male" name="gender" value="Male">
                <label for="male">👨 Male</label>
              </div>
              <div class="radio-option">
                <input type="radio" id="female" name="gender" value="Female">
                <label for="female">👩 Female</label>
              </div>
  
            </div>
          </div>
          
          <div class="form-group full">
            <label for="bio">
              Bio / About You (Optional)
            </label>
            <textarea id="bio" name="bio" placeholder="Tell us a bit about yourself and your coding goals..."></textarea>
            <div class="info-text">This will appear on your profile</div>
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Complete Profile & Continue →</button>
      </form>
    </div>
  </div>
</body>
</html>