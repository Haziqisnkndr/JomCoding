<?php
// payment.php - UPDATED for /payment folder
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if coming from subscription page
if(!isset($_SESSION['pending_subscription'])) {
    header("Location: subscription.php");
    exit();
}

require_once '../config.php';

$user_id = $_SESSION['user_id'];

// Get user info
$query = "SELECT * FROM user WHERE user_id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

$error = '';
$processing = false;

// Handle payment confirmation
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['confirm_payment'])) {
        // User clicked "Yes, Pay Now"
        $processing = true;
        
        // Simulate payment processing delay
        sleep(1);
        
        // Calculate subscription dates
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+1 month'));
        
        // Get card details for transaction record
        $card_type = $_POST['card_type'];
        $card_number = $_POST['card_number'];
        $last_4_digits = substr(str_replace(' ', '', $card_number), -4);
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert premium subscription
            $insert_sub = "INSERT INTO subscriptions (student_id, plan_name, start_date, end_date, status, amount, payment_method, created_at)
                           VALUES ($user_id, 'Premium', '$start_date', '$end_date', 'active', 20.00, '$card_type (**** $last_4_digits)', NOW())";
            
            if(!mysqli_query($conn, $insert_sub)) {
                throw new Exception('Failed to create subscription');
            }
            
            // Update user subscription_type
            $update_user = "UPDATE user SET subscription_type = 'premium' WHERE user_id = $user_id";
            if(!mysqli_query($conn, $update_user)) {
                throw new Exception('Failed to update user status');
            }
            
            mysqli_commit($conn);
            
            // Clear pending subscription
            unset($_SESSION['pending_subscription']);
            
            // Set success message
            $_SESSION['payment_success'] = true;
            $_SESSION['subscription_type'] = 'premium';
            
            // Redirect to success page
            header("Location: payment_success.php");
            exit();
            
        } catch(Exception $e) {
            mysqli_rollback($conn);
            $error = 'Payment failed. Please try again.';
            $processing = false;
        }
        
    } elseif(isset($_POST['cancel_payment'])) {
        // User clicked "No, Cancel"
        unset($_SESSION['pending_subscription']);
        header("Location: subscription.php");
        exit();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Payment - RM20 | JomCoding</title>

  <style>
    :root{
      --primary:#2f57ef;
      --accent:#ff4a57;
      --text:#0f172a;
      --muted:#64748b;
      --bg:#f6f7fb;
      --border:#e6e8f0;
    }
    
    *{box-sizing:border-box; margin:0; padding:0;}
    
    body{
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
      width: 600px;
      height: 600px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
      top: -300px;
      right: -300px;
      animation: float 10s ease-in-out infinite;
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-30px) rotate(180deg); }
    }
    
    .payment-container {
      background: #fff;
      border-radius: 24px;
      padding: 48px;
      max-width: 600px;
      width: 100%;
      box-shadow: 0 40px 100px rgba(0,0,0,0.3);
      position: relative;
      z-index: 10;
      animation: slideUp 0.5s ease-out;
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .lock-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      font-size: 40px;
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    .payment-header {
      text-align: center;
      margin-bottom: 40px;
    }
    
    .payment-header h1 {
      font-size: 32px;
      font-weight: 900;
      margin-bottom: 12px;
      color: var(--text);
    }
    
    .payment-header p {
      color: var(--muted);
      font-size: 16px;
      line-height: 1.6;
    }
    
    .amount-box {
      background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
      border-radius: 16px;
      padding: 24px;
      text-align: center;
      margin-bottom: 32px;
      box-shadow: 0 8px 24px rgba(255, 215, 0, 0.3);
    }
    
    .amount-label {
      font-size: 14px;
      font-weight: 700;
      color: #8b6914;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 8px;
    }
    
    .amount-value {
      font-size: 56px;
      font-weight: 900;
      color: #8b6914;
      line-height: 1;
    }
    
    .period {
      font-size: 16px;
      color: #8b6914;
      font-weight: 600;
      margin-top: 4px;
    }
    
    .form-group {
      margin-bottom: 24px;
    }
    
    .form-label {
      display: block;
      font-weight: 700;
      font-size: 14px;
      color: var(--text);
      margin-bottom: 8px;
    }
    
    .form-input {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid var(--border);
      border-radius: 12px;
      font-size: 16px;
      font-family: inherit;
      transition: all 0.3s ease;
      background: #fff;
    }
    
    .form-input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(47, 87, 239, 0.1);
    }
    
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    
    .card-icons {
      display: flex;
      gap: 12px;
      margin-top: 12px;
    }
    
    .card-icon {
      width: 50px;
      height: 32px;
      background: #f1f5f9;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: 900;
      color: var(--muted);
      border: 2px solid var(--border);
    }
    
    .card-type-selector {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 24px;
    }
    
    .card-type-option {
      position: relative;
    }
    
    .card-type-option input[type="radio"] {
      position: absolute;
      opacity: 0;
    }
    
    .card-type-label {
      display: block;
      padding: 16px;
      border: 2px solid var(--border);
      border-radius: 12px;
      text-align: center;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      background: #fff;
    }
    
    .card-type-option input[type="radio"]:checked + .card-type-label {
      border-color: var(--primary);
      background: #f0f4ff;
      color: var(--primary);
    }
    
    .security-note {
      background: #f0fdf4;
      border: 2px solid #86efac;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 32px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .security-note-icon {
      font-size: 24px;
    }
    
    .security-note-text {
      font-size: 13px;
      color: #166534;
      font-weight: 600;
      line-height: 1.5;
    }
    
    .button-group {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-top: 32px;
    }
    
    .btn {
      padding: 18px 32px;
      border-radius: 14px;
      font-weight: 800;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      font-family: inherit;
      text-align: center;
    }
    
    .btn-cancel {
      background: #fff;
      color: var(--accent);
      border: 2px solid var(--accent);
    }
    
    .btn-cancel:hover {
      background: var(--accent);
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(255, 74, 87, 0.3);
    }
    
    .btn-confirm {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #fff;
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }
    
    .btn-confirm:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(102, 126, 234, 0.4);
    }
    
    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    
    .alert {
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .alert-error {
      background: #fee;
      color: #c33;
      border: 2px solid #fcc;
    }
    
    .processing-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.8);
      backdrop-filter: blur(8px);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    
    .processing-overlay.active {
      display: flex;
    }
    
    .processing-box {
      background: #fff;
      border-radius: 24px;
      padding: 48px;
      text-align: center;
      max-width: 400px;
      animation: pulse 1.5s ease-in-out infinite;
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    .spinner {
      width: 60px;
      height: 60px;
      border: 4px solid var(--border);
      border-top-color: var(--primary);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 24px;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    @media (max-width: 600px) {
      .payment-container {
        padding: 32px 24px;
      }
      
      .payment-header h1 {
        font-size: 24px;
      }
      
      .amount-value {
        font-size: 48px;
      }
      
      .button-group {
        grid-template-columns: 1fr;
      }
      
      .form-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <div class="payment-container">
    <div class="lock-icon">🔒</div>
    
    <div class="payment-header">
      <h1>Secure Payment</h1>
      <p>Complete your Premium subscription payment</p>
    </div>
    
    <?php if($error): ?>
      <div class="alert alert-error">
        <span>⚠️</span> <?php echo $error; ?>
      </div>
    <?php endif; ?>
    
    <div class="amount-box">
      <div class="amount-label">Total Amount</div>
      <div class="amount-value">RM20</div>
      <div class="period">per month</div>
    </div>
    
    <form method="POST" action="" id="paymentForm">
      <!-- Card Type Selection -->
      <div class="card-type-selector">
        <div class="card-type-option">
          <input type="radio" name="card_type" value="Credit Card" id="credit" checked>
          <label for="credit" class="card-type-label">
            💳 Credit Card
          </label>
        </div>
        <div class="card-type-option">
          <input type="radio" name="card_type" value="Debit Card" id="debit">
          <label for="debit" class="card-type-label">
            💰 Debit Card
          </label>
        </div>
      </div>
      
      <!-- Card Number -->
      <div class="form-group">
        <label class="form-label" for="card_number">Card Number</label>
        <input 
          type="text" 
          class="form-input" 
          id="card_number" 
          name="card_number"
          placeholder="1234 5678 9012 3456" 
          maxlength="19"
          required
          pattern="[0-9\s]{13,19}"
        >
        <div class="card-icons">
          <div class="card-icon">VISA</div>
          <div class="card-icon">MC</div>
          <div class="card-icon">AMEX</div>
        </div>
      </div>
      
      <!-- Cardholder Name -->
      <div class="form-group">
        <label class="form-label" for="card_name">Cardholder Name</label>
        <input 
          type="text" 
          class="form-input" 
          id="card_name" 
          name="card_name"
          placeholder="JOHN DOE" 
          value="<?php echo strtoupper($user['full_name']); ?>"
          required
        >
      </div>
      
      <!-- Expiry and CVV -->
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="expiry">Expiry Date</label>
          <input 
            type="text" 
            class="form-input" 
            id="expiry" 
            name="expiry"
            placeholder="MM/YY" 
            maxlength="5"
            required
            pattern="(0[1-9]|1[0-2])\/[0-9]{2}"
          >
        </div>
        <div class="form-group">
          <label class="form-label" for="cvv">CVV</label>
          <input 
            type="text" 
            class="form-input" 
            id="cvv" 
            name="cvv"
            placeholder="123" 
            maxlength="4"
            required
            pattern="[0-9]{3,4}"
          >
        </div>
      </div>
      
      <!-- Security Note -->
      <div class="security-note">
        <div class="security-note-icon">🛡️</div>
        <div class="security-note-text">
          <strong>This is a simulation.</strong> No real payment will be processed. This is a dummy transaction for testing purposes only.
        </div>
      </div>
      
      <!-- Action Buttons -->
      <div class="button-group">
        <button type="submit" name="cancel_payment" class="btn btn-cancel">
          ✗ No, Cancel
        </button>
        <button type="submit" name="confirm_payment" class="btn btn-confirm" id="confirmBtn">
          ✓ Yes, Pay RM20
        </button>
      </div>
    </form>
  </div>
  
  <!-- Processing Overlay -->
  <div class="processing-overlay" id="processingOverlay">
    <div class="processing-box">
      <div class="spinner"></div>
      <h2 style="font-size: 24px; font-weight: 900; margin-bottom: 8px; color: var(--text);">Processing Payment...</h2>
      <p style="color: var(--muted); font-size: 14px;">Please wait while we process your transaction</p>
    </div>
  </div>

  <script>
    // Auto-format card number with spaces
    const cardInput = document.getElementById('card_number');
    cardInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\s/g, '');
      let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
      e.target.value = formattedValue;
    });
    
    // Auto-format expiry date
    const expiryInput = document.getElementById('expiry');
    expiryInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length >= 2) {
        value = value.slice(0, 2) + '/' + value.slice(2, 4);
      }
      e.target.value = value;
    });
    
    // Only allow numbers in CVV
    const cvvInput = document.getElementById('cvv');
    cvvInput.addEventListener('input', function(e) {
      e.target.value = e.target.value.replace(/\D/g, '');
    });
    
    // Show processing overlay on confirm
    const form = document.getElementById('paymentForm');
    const confirmBtn = document.getElementById('confirmBtn');
    const processingOverlay = document.getElementById('processingOverlay');
    
    confirmBtn.addEventListener('click', function(e) {
      if (form.checkValidity()) {
        processingOverlay.classList.add('active');
      }
    });
  </script>
</body>
</html>