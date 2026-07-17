<?php
session_start();
require_once '../config.php';

// ⚠️ CHANGE THESE PASSWORDS!
$admin_password = 'jomcoding2025';
$instructor_password = 'instructor2025';

$error = '';
$success = '';

// Handle role selection
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_role'])) {
    $_SESSION['selected_role'] = $_POST['role'];
}

// Handle login
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    $selected_role = $_SESSION['selected_role'] ?? '';
    
    if($selected_role === 'admin' && $_POST['password'] === $admin_password) {
        $_SESSION['admin_verified'] = true;
        $_SESSION['user_role'] = 'admin';
        // Redirect admin to admin_manage_subscriptions.php
        header("Location: admin_manage_subscriptions.php");
        exit();
    } elseif($selected_role === 'instructor' && $_POST['password'] === $instructor_password) {
        $_SESSION['admin_verified'] = true;
        $_SESSION['user_role'] = 'instructor';
        // Redirect instructor to instructor.php
        header("Location: instructor.php");
        exit();
    } else {
        $error = 'Incorrect password!';
    }
}

// Handle subscription update (admin only)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_subscription']) && isset($_SESSION['admin_verified']) && $_SESSION['user_role'] === 'admin') {
    $user_id = intval($_POST['user_id']);
    $sub_type = mysqli_real_escape_string($conn, $_POST['subscription_type']);
    
    if(in_array($sub_type, ['free', 'premium'])) {
        mysqli_begin_transaction($conn);
        
        try {
            // Update user subscription_type
            $update_query = "UPDATE user SET subscription_type = '$sub_type' WHERE user_id = $user_id";
            if(!mysqli_query($conn, $update_query)) {
                throw new Exception('Failed to update user');
            }
            
            // If setting to premium, create/update subscription record
            if($sub_type === 'premium') {
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d', strtotime('+1 month'));
                
                // Check if subscription exists
                $check_query = "SELECT subscription_id FROM subscriptions WHERE student_id = $user_id AND status = 'active' ORDER BY created_at DESC LIMIT 1";
                $check_result = mysqli_query($conn, $check_query);
                
                if(mysqli_num_rows($check_result) > 0) {
                    // Update existing
                    $sub_row = mysqli_fetch_assoc($check_result);
                    $sub_id = $sub_row['subscription_id'];
                    mysqli_query($conn, "UPDATE subscriptions SET end_date = '$end_date', plan_name = 'Premium' WHERE subscription_id = $sub_id");
                } else {
                    // Create new
                    mysqli_query($conn, "INSERT INTO subscriptions (student_id, plan_name, start_date, end_date, status, amount, payment_method) VALUES ($user_id, 'Premium', '$start_date', '$end_date', 'active', 20.00, 'Admin Update')");
                }
            } else {
                // If setting to free, expire any active subscriptions
                mysqli_query($conn, "UPDATE subscriptions SET status = 'cancelled' WHERE student_id = $user_id AND status = 'active'");
            }
            
            mysqli_commit($conn);
            $success = "Successfully updated user #$user_id to " . strtoupper($sub_type) . "!";
            
        } catch(Exception $e) {
            mysqli_rollback($conn);
            $error = "Failed to update: " . $e->getMessage();
        }
    }
}

// Handle logout
if(isset($_GET['logout'])) {
    unset($_SESSION['admin_verified']);
    unset($_SESSION['user_role']);
    unset($_SESSION['selected_role']);
    header("Location: admin_manage_subscriptions.php");
    exit();
}

// Handle back to role selection
if(isset($_GET['change_role'])) {
    unset($_SESSION['selected_role']);
    header("Location: admin_manage_subscriptions.php");
    exit();
}

// Get filter from URL
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all users and statistics if admin is verified
$users = [];
$stats = [
    'total_students' => 0,
    'premium_users' => 0,
    'free_users' => 0,
    'total_classes' => 0,
    'total_revenue' => 0,
    'active_subscriptions' => 0
];

if(isset($_SESSION['admin_verified'])) {
    // Build WHERE clause for filtering
    $where_conditions = ["u.username != 'admin'"];
    
    if($filter_type === 'premium') {
        $where_conditions[] = "u.subscription_type = 'premium'";
    } else if($filter_type === 'free') {
        $where_conditions[] = "u.subscription_type = 'free'";
    }
    
    if($search_query !== '') {
        $search_escaped = mysqli_real_escape_string($conn, $search_query);
        $where_conditions[] = "(u.username LIKE '%$search_escaped%' OR u.email LIKE '%$search_escaped%' OR u.full_name LIKE '%$search_escaped%')";
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    // Get users
    $users_query = "SELECT u.user_id, u.username, u.email, u.full_name, u.subscription_type, u.created_at,
                    s.start_date, s.end_date, s.amount
                    FROM user u 
                    LEFT JOIN subscriptions s ON u.user_id = s.student_id AND s.status = 'active'
                    $where_clause 
                    ORDER BY u.user_id DESC";
    $result = mysqli_query($conn, $users_query);
    while($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    // Calculate statistics (for all users, not filtered)
    $all_users_query = "SELECT COUNT(*) as total FROM user WHERE username != 'admin'";
    $all_users_result = mysqli_query($conn, $all_users_query);
    if($all_users_result && $row = mysqli_fetch_assoc($all_users_result)) {
        $stats['total_students'] = $row['total'];
    }
    
    // Count premium users
    $premium_query = "SELECT COUNT(*) as total FROM user WHERE username != 'admin' AND subscription_type = 'premium'";
    $premium_result = mysqli_query($conn, $premium_query);
    if($premium_result && $row = mysqli_fetch_assoc($premium_result)) {
        $stats['premium_users'] = $row['total'];
    }
    
    // Count free users
    $free_query = "SELECT COUNT(*) as total FROM user WHERE username != 'admin' AND subscription_type = 'free'";
    $free_result = mysqli_query($conn, $free_query);
    if($free_result && $row = mysqli_fetch_assoc($free_result)) {
        $stats['free_users'] = $row['total'];
    }
    
    // Count classes (courses)
    $class_query = "SELECT COUNT(*) as count FROM courses";
    $class_result = mysqli_query($conn, $class_query);
    if($class_result && $row = mysqli_fetch_assoc($class_result)) {
        $stats['total_classes'] = $row['count'];
    }
    
    // Calculate total revenue and active subscriptions
    $revenue_query = "SELECT SUM(amount) as total, COUNT(*) as count FROM subscriptions WHERE status = 'active'";
    $revenue_result = mysqli_query($conn, $revenue_query);
    if($revenue_result && $row = mysqli_fetch_assoc($revenue_result)) {
        $stats['total_revenue'] = $row['total'] ?? 0;
        $stats['active_subscriptions'] = $row['count'] ?? 0;
    }
}

$current_role = $_SESSION['user_role'] ?? '';
$selected_role = $_SESSION['selected_role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Management - JomCoding LMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
        }

        /* Role Selection Screen */
        .role-selection-screen {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .role-container {
            max-width: 900px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .role-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .role-header i {
            font-size: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        .role-header h2 {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .role-header p {
            color: #7f8c8d;
            font-size: 16px;
        }

        .role-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 30px;
        }

        .role-card {
            background: #f8f9fa;
            border: 3px solid #e0e6ed;
            border-radius: 16px;
            padding: 40px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .role-card:hover {
            transform: translateY(-5px);
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }

        .role-card input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .role-card input[type="radio"]:checked + .role-content {
            color: #667eea;
        }

        .role-card input[type="radio"]:checked ~ .role-checkmark {
            opacity: 1;
        }

        .role-icon {
            font-size: 60px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .role-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .role-description {
            font-size: 14px;
            color: #7f8c8d;
            line-height: 1.6;
        }

        .role-checkmark {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .continue-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .continue-btn:hover {
            transform: translateY(-2px);
        }

        .continue-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }

        /* Login Screen Styles */
        .login-screen {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .login-icon {
            text-align: center;
            font-size: 80px;
            margin-bottom: 20px;
        }

        .login-icon.admin {
            color: #667eea;
        }

        .login-icon.instructor {
            color: #27ae60;
        }

        .login-container h2 {
            text-align: center;
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .login-container > p {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
        }

        .role-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .role-badge.admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .role-badge.instructor {
            background: linear-gradient(135deg, #1d2e28 0%, #0f5132 100%);
            color: white;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
        }

        .login-btn.instructor {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }

        /* Dashboard Layout */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar.instructor {
            background: linear-gradient(180deg, #27ae60 0%, #2ecc71 100%);
        }

        .sidebar-header {
            padding: 30px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
        }

        .sidebar-logo.instructor {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .sidebar-subtitle {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #667eea;
        }

        .sidebar-menu a i {
            width: 24px;
            margin-right: 12px;
            font-size: 16px;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            background: #f5f7fa;
        }

        /* Top Navigation */
        .top-nav {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .page-title {
            font-size: 24px;
            color: #2c3e50;
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s;
        }

        .notification-icon:hover {
            background: #e9ecef;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }

        .user-avatar.instructor {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .user-email {
            font-size: 12px;
            color: #7f8c8d;
        }

        .logout-btn {
            padding: 10px 20px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 40px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert i {
            font-size: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); }
        .stat-icon.purple { background: linear-gradient(135deg, #a044ff 0%, #6a3093 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f46b45 0%, #eea849 100%); }
        .stat-icon.teal { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 500;
        }

        .stat-change {
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
            color: #27ae60;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-header h3 {
            font-size: 18px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-controls {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr;
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #7f8c8d;
            margin-bottom: 6px;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px 14px;
            border: 2px solid #e0e6ed;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .apply-filter-btn {
            padding: 10px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
            height: fit-content;
        }

        .apply-filter-btn:hover {
            background: #5568d3;
        }

        .reset-filter-btn {
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
            height: fit-content;
        }

        .reset-filter-btn:hover {
            background: #7f8c8d;
        }

        /* Section Header */
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e6ed;
        }

        .section-header i {
            font-size: 24px;
            color: #667eea;
        }

        .section-header h2 {
            font-size: 22px;
            color: #2c3e50;
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e0e6ed;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-premium {
            background: linear-gradient(135deg, #a044ff 0%, #6a3093 100%);
            color: white;
        }

        .badge-free {
            background: #e9ecef;
            color: #6c757d;
        }

        .update-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .update-form select {
            padding: 8px 12px;
            border: 2px solid #e0e6ed;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .update-form select:focus {
            outline: none;
            border-color: #667eea;
        }

        .update-form button {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .update-form button:hover {
            background: #5568d3;
        }

        .no-data {
            padding: 60px 20px;
            text-align: center;
            color: #7f8c8d;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-expiring {
            background: #fff3cd;
            color: #856404;
        }

        .status-expired {
            background: #f8d7da;
            color: #721c24;
        }

        .read-only-notice {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .read-only-notice i {
            font-size: 20px;
            color: #856404;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .sidebar-title, .sidebar-subtitle {
                display: none;
            }

            .sidebar-menu a span {
                display: none;
            }

            .main-content {
                margin-left: 70px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-nav {
                padding: 15px 20px;
            }

            .user-details {
                display: none;
            }

            .filter-controls {
                grid-template-columns: 1fr;
            }

            .role-options {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <?php if(!isset($_SESSION['selected_role'])): ?>
        <!-- Role Selection Screen -->
        <div class="role-selection-screen">
            <div class="role-container">
                <div class="role-header">
                    <i class="fas fa-users-cog"></i>
                    <h2>Welcome to JomCoding LMS</h2>
                    <p>Please select your role to continue</p>
                </div>

                <form method="POST" id="roleForm">
                    <div class="role-options">
                        <label class="role-card">
                            <input type="radio" name="role" value="admin" required>
                            <div class="role-content">
                                <div class="role-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="role-title">Administrator</div>
                                <div class="role-description">
                                    Full system access with ability to manage subscriptions, users, and all platform settings
                                </div>
                            </div>
                            <div class="role-checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </label>

                        <label class="role-card">
                            <input type="radio" name="role" value="instructor" required>
                            <div class="role-content">
                                <div class="role-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="role-title">Instructor</div>
                                <div class="role-description">
                                    Access to view student data, analytics, and course management tools
                                </div>
                            </div>
                            <div class="role-checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </label>
                    </div>

                    <button type="submit" name="select_role" class="continue-btn" id="continueBtn" disabled>
                        Continue <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>

        <script>
            // Enable continue button when a role is selected
            const roleInputs = document.querySelectorAll('input[name="role"]');
            const continueBtn = document.getElementById('continueBtn');
            
            roleInputs.forEach(input => {
                input.addEventListener('change', () => {
                    continueBtn.disabled = false;
                });
            });
        </script>

    <?php elseif(!isset($_SESSION['admin_verified'])): ?>
        <!-- Login Screen -->
        <div class="login-screen">
            <div class="login-container">
                <div class="login-icon <?php echo $selected_role; ?>">
                    <?php if($selected_role === 'admin'): ?>
                        <i class="fas fa-user-shield"></i>
                    <?php else: ?>
                        <i class="fas fa-chalkboard-teacher"></i>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: center;">
                    <span class="role-badge <?php echo $selected_role; ?>">
                        <?php echo strtoupper($selected_role); ?> LOGIN
                    </span>
                </div>
                
                <h2><?php echo ucfirst($selected_role); ?> Dashboard</h2>
                <p>Enter your password to continue</p>

                <?php if($error): ?>
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required autofocus>
                    </div>
                    <button type="submit" name="verify" class="login-btn <?php echo $selected_role; ?>">
                        <i class="fas fa-lock"></i> Verify & Access
                    </button>
                </form>

                <div class="back-link">
                    <a href="?change_role">
                        <i class="fas fa-arrow-left"></i> Change Role
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Dashboard Layout -->
        <div class="dashboard-layout">
            <!-- Sidebar -->
            <aside class="sidebar <?php echo $current_role; ?>">
                <div class="sidebar-header">
                    <div class="sidebar-logo <?php echo $current_role; ?>">
                        <?php if($current_role === 'admin'): ?>
                            <i class="fas fa-user-shield"></i>
                        <?php else: ?>
                            <i class="fas fa-chalkboard-teacher"></i>
                        <?php endif; ?>
                    </div>
                    <div class="sidebar-title"><?php echo strtoupper($current_role); ?> JomCoding</div>
                    <div class="sidebar-subtitle">LMS • Learning Management System</div>
                </div>

                <ul class="sidebar-menu">
                    <li><a href="admin_manage_subscriptions.php" class="active"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                    <li><a href="analytics.php"><i class="fas fa-chart-line"></i> <span>Analytics</span></a></li>
                </ul>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Top Navigation -->
                <nav class="top-nav">
                    <h1 class="page-title">Subscription Management</h1>
                    <div class="user-info">
                        <div class="notification-icon">
                            <!-- notification icon placeholder -->
                        </div>
                        <div class="user-avatar <?php echo $current_role; ?>">
                            <?php echo strtoupper(substr($current_role, 0, 2)); ?>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo ucfirst($current_role); ?></span>
                            <span class="user-email"><?php echo $current_role; ?>@jomcoding.edu</span>
                        </div>
                        <a href="?logout" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </nav>

                <!-- Dashboard Content -->
                <div class="dashboard-content">
                    
                    <?php if($current_role === 'instructor'): ?>
                        <div class="read-only-notice">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>Instructor View:</strong> You have read-only access. You can view all student data but cannot modify subscriptions.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error && isset($_SESSION['admin_verified'])): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-times-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
                                    <div class="stat-label">Total Students</div>
                                </div>
                                <div class="stat-icon blue">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo number_format($stats['premium_users']); ?></div>
                                    <div class="stat-label">Premium Users</div>
                                </div>
                                <div class="stat-icon purple">
                                    <i class="fas fa-crown"></i>
                                </div>
                            </div>
                            <div class="stat-change">
                                <i class="fas fa-users"></i>
                                <?php echo $stats['free_users']; ?> free users
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo number_format($stats['total_classes']); ?></div>
                                    <div class="stat-label">Total Classes</div>
                                </div>
                                <div class="stat-icon orange">
                                    <i class="fas fa-book"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value">RM<?php echo number_format($stats['total_revenue'], 2); ?></div>
                                    <div class="stat-label">Total Revenue</div>
                                </div>
                                <div class="stat-icon teal">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                            <div class="stat-change">
                                <i class="fas fa-check-circle"></i>
                                <?php echo $stats['active_subscriptions']; ?> active subscriptions
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-section">
                        <div class="filter-header">
                            <h3><i class="fas fa-filter"></i> Filter Users</h3>
                        </div>
                        
                        <form method="GET" id="filterForm">
                            <div class="filter-controls">
                                <div class="filter-group">
                                    <label>Subscription Type</label>
                                    <select name="filter_type" id="filterType">
                                        <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Users</option>
                                        <option value="premium" <?php echo $filter_type === 'premium' ? 'selected' : ''; ?>>Premium Only</option>
                                        <option value="free" <?php echo $filter_type === 'free' ? 'selected' : ''; ?>>Free Only</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label>Search</label>
                                    <input type="text" name="search" placeholder="Search by username, email, or name..." value="<?php echo htmlspecialchars($search_query); ?>">
                                </div>
                                
                                <div class="filter-buttons">
                                    <button type="submit" class="apply-filter-btn">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    <a href="admin_manage_subscriptions.php" class="reset-filter-btn">
                                        <i class="fas fa-redo"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Subscription Management Section -->
                    <div class="section-header">
                        <i class="fas fa-users-cog"></i>
                        <h2>User Subscriptions Management</h2>
                    </div>

                    <?php if(count($users) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Full Name</th>
                                        <th>Status</th>
                                        <th>Premium Period</th>
                                        <?php if($current_role === 'admin'): ?>
                                            <th>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                        <tr>
                                            <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td style="color: #6c757d; font-size: 13px;"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $user['subscription_type']; ?>">
                                                    <?php if($user['subscription_type'] === 'premium'): ?>
                                                        <i class="fas fa-crown"></i>
                                                    <?php endif; ?>
                                                    <?php echo strtoupper($user['subscription_type']); ?>
                                                </span>
                                            </td>
                                            <td style="font-size: 13px;">
                                                <?php 
                                                if($user['subscription_type'] === 'premium' && $user['start_date'] && $user['end_date']) {
                                                    $end_date = strtotime($user['end_date']);
                                                    $today = strtotime(date('Y-m-d'));
                                                    $days_left = round(($end_date - $today) / (60 * 60 * 24));
                                                    
                                                    echo date('M d', strtotime($user['start_date'])) . ' - ' . date('M d, Y', $end_date);
                                                    echo '<br>';
                                                    
                                                    if($days_left <= 0) {
                                                        echo '<span class="status-indicator status-expired"><i class="fas fa-times-circle"></i> Expired</span>';
                                                    } elseif($days_left <= 7) {
                                                        echo '<span class="status-indicator status-expiring"><i class="fas fa-exclamation-triangle"></i> ' . $days_left . ' days left</span>';
                                                    } else {
                                                        echo '<span class="status-indicator status-active"><i class="fas fa-check-circle"></i> ' . $days_left . ' days left</span>';
                                                    }
                                                } else {
                                                    echo '<span style="color: #adb5bd;">No active subscription</span>';
                                                }
                                                ?>
                                            </td>
                                            <?php if($current_role === 'admin'): ?>
                                                <td>
                                                    <form method="POST" class="update-form">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                        <select name="subscription_type">
                                                            <option value="free" <?php if($user['subscription_type'] === 'free') echo 'selected'; ?>>Free</option>
                                                            <option value="premium" <?php if($user['subscription_type'] === 'premium') echo 'selected'; ?>>Premium</option>
                                                        </select>
                                                        <button type="submit" name="update_subscription">
                                                            <i class="fas fa-sync-alt"></i> Update
                                                        </button>
                                                    </form>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <div class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>No users found matching your filters.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    <?php endif; ?>
</body>
</html>