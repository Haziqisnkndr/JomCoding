<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config.php';

// Admin password verification
$admin_password = 'jomcoding2025';

$error = '';

// Handle login
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    if($_POST['password'] === $admin_password) {
        $_SESSION['admin_verified'] = true;
    } else {
        $error = 'Incorrect password!';
    }
}

// Handle logout
if(isset($_GET['logout'])) {
    unset($_SESSION['admin_verified']);
    header("Location: admin_manage_subscriptions.php");
    exit();
}

// Handle Excel Export
if(isset($_GET['export']) && $_GET['export'] === 'excel' && isset($_SESSION['admin_verified'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="analytics_report_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<h1>JomCoding Analytics Report - ' . date('F d, Y') . '</h1>';
    
    // Get filter parameters
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $custom_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $custom_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    
    // Build WHERE clause based on filter
    $where_clause = "WHERE u.username != 'admin'";
    
    if($filter === 'custom' && $custom_start && $custom_end) {
        $where_clause .= " AND DATE(u.created_at) BETWEEN '$custom_start' AND '$custom_end'";
    } else if($filter !== 'all') {
        $days_map = ['week' => 7, 'month' => 30, 'quarter' => 90, 'year' => 365];
        if(isset($days_map[$filter])) {
            $where_clause .= " AND u.created_at >= DATE_SUB(NOW(), INTERVAL {$days_map[$filter]} DAY)";
        }
    }
    
    // Summary Statistics
    echo '<h2>Summary Statistics</h2>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>Metric</th><th>Value</th></tr>';
    
    // Total Users in Period
    $total_query = "SELECT COUNT(*) as total FROM user u $where_clause";
    $total_result = mysqli_query($conn, $total_query);
    $total_row = mysqli_fetch_assoc($total_result);
    echo '<tr><td>Total Users (Filtered)</td><td>' . $total_row['total'] . '</td></tr>';
    
    // Premium Users
    $premium_query = "SELECT COUNT(*) as total FROM user u $where_clause AND u.subscription_type = 'premium'";
    $premium_result = mysqli_query($conn, $premium_query);
    $premium_row = mysqli_fetch_assoc($premium_result);
    echo '<tr><td>Premium Users</td><td>' . $premium_row['total'] . '</td></tr>';
    
    // Free Users
    $free_query = "SELECT COUNT(*) as total FROM user u $where_clause AND u.subscription_type = 'free'";
    $free_result = mysqli_query($conn, $free_query);
    $free_row = mysqli_fetch_assoc($free_result);
    echo '<tr><td>Free Users</td><td>' . $free_row['total'] . '</td></tr>';
    
    // Total Revenue
    $revenue_query = "SELECT SUM(s.amount) as total FROM subscriptions s 
                     INNER JOIN user u ON s.student_id = u.user_id 
                     $where_clause AND s.status = 'active'";
    $revenue_result = mysqli_query($conn, $revenue_query);
    $revenue_row = mysqli_fetch_assoc($revenue_result);
    $total_revenue = $revenue_row['total'] ?? 0;
    echo '<tr><td>Total Revenue (RM)</td><td>RM ' . number_format($total_revenue, 2) . '</td></tr>';
    
    echo '</table><br><br>';
    
    // Detailed User List
    echo '<h2>Detailed User List</h2>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr>';
    echo '<th>User ID</th>';
    echo '<th>Username</th>';
    echo '<th>Email</th>';
    echo '<th>Full Name</th>';
    echo '<th>Subscription Type</th>';
    echo '<th>Joined Date</th>';
    echo '<th>Premium Start</th>';
    echo '<th>Premium End</th>';
    echo '<th>Revenue Contribution (RM)</th>';
    echo '</tr>';
    
    $users_query = "SELECT u.user_id, u.username, u.email, u.full_name, u.subscription_type, u.created_at,
                    s.start_date, s.end_date, s.amount
                    FROM user u 
                    LEFT JOIN subscriptions s ON u.user_id = s.student_id AND s.status = 'active'
                    $where_clause
                    ORDER BY u.created_at DESC";
    
    $users_result = mysqli_query($conn, $users_query);
    while($user = mysqli_fetch_assoc($users_result)) {
        echo '<tr>';
        echo '<td>' . $user['user_id'] . '</td>';
        echo '<td>' . htmlspecialchars($user['username']) . '</td>';
        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
        echo '<td>' . htmlspecialchars($user['full_name']) . '</td>';
        echo '<td>' . strtoupper($user['subscription_type']) . '</td>';
        echo '<td>' . date('M d, Y', strtotime($user['created_at'])) . '</td>';
        echo '<td>' . ($user['start_date'] ? date('M d, Y', strtotime($user['start_date'])) : '-') . '</td>';
        echo '<td>' . ($user['end_date'] ? date('M d, Y', strtotime($user['end_date'])) : '-') . '</td>';
        echo '<td>RM ' . number_format($user['amount'] ?? 0, 2) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit();
}

// Get filter from URL (default to 'all')
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$valid_filters = ['all', 'week', 'month', 'quarter', 'year', 'custom'];
if(!in_array($filter, $valid_filters)) {
    $filter = 'all';
}

// Get custom date range if set
$custom_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$custom_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Initialize analytics data
$analytics = [
    'total_users' => 0,
    'total_users_filtered' => 0,
    'new_users_period' => 0,
    'growth_percentage' => 0,
    'premium_users' => 0,
    'free_users' => 0,
    'premium_percentage' => 0,
    'total_revenue' => 0,
    'active_subscriptions' => 0,
    'average_revenue_per_user' => 0,
    'chart_labels' => [],
    'chart_data' => [],
    'revenue_chart_data' => [],
    'premium_chart_data' => [],
    'video_watch_chart_data' => [],
    'recent_users' => []
];

if(isset($_SESSION['admin_verified'])) {
    
    // Get TOTAL users (all time)
    $total_query = "SELECT COUNT(*) as total FROM user WHERE username != 'admin'";
    $total_result = mysqli_query($conn, $total_query);
    if($total_result && $row = mysqli_fetch_assoc($total_result)) {
        $analytics['total_users'] = $row['total'];
    }
    
    // Build WHERE clause based on filter
    $where_clause = "";
    $period_name = "All Time";
    
    if($filter === 'custom' && $custom_start_date && $custom_end_date) {
        $where_clause = " AND DATE(u.created_at) BETWEEN '$custom_start_date' AND '$custom_end_date'";
        $period_name = date('M d, Y', strtotime($custom_start_date)) . ' - ' . date('M d, Y', strtotime($custom_end_date));
    } else if($filter !== 'all') {
        $days_map = [
            'week' => ['days' => 7, 'name' => 'Last 7 Days'],
            'month' => ['days' => 30, 'name' => 'Last 30 Days'],
            'quarter' => ['days' => 90, 'name' => 'Last 90 Days'],
            'year' => ['days' => 365, 'name' => 'Last 12 Months']
        ];
        
        if(isset($days_map[$filter])) {
            $days = $days_map[$filter]['days'];
            $where_clause = " AND u.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)";
            $period_name = $days_map[$filter]['name'];
        }
    }
    
    // Get filtered user count
    $filtered_query = "SELECT COUNT(*) as total FROM user u WHERE u.username != 'admin' $where_clause";
    $filtered_result = mysqli_query($conn, $filtered_query);
    if($filtered_result && $row = mysqli_fetch_assoc($filtered_result)) {
        $analytics['total_users_filtered'] = $row['total'];
        $analytics['new_users_period'] = $row['total'];
    }
    
    // Get premium vs free users (filtered)
    $sub_query = "SELECT subscription_type, COUNT(*) as count 
                  FROM user u
                  WHERE u.username != 'admin' $where_clause 
                  GROUP BY subscription_type";
    $sub_result = mysqli_query($conn, $sub_query);
    while($row = mysqli_fetch_assoc($sub_result)) {
        if($row['subscription_type'] === 'premium') {
            $analytics['premium_users'] = $row['count'];
        } else {
            $analytics['free_users'] = $row['count'];
        }
    }
    
    if($analytics['total_users_filtered'] > 0) {
        $analytics['premium_percentage'] = round(($analytics['premium_users'] / $analytics['total_users_filtered']) * 100, 1);
    }
    
    // Calculate total revenue (filtered by user join date)
    $revenue_query = "SELECT SUM(s.amount) as total, COUNT(DISTINCT s.subscription_id) as count 
                     FROM subscriptions s
                     INNER JOIN user u ON s.student_id = u.user_id
                     WHERE u.username != 'admin' $where_clause AND s.status = 'active'";
    $revenue_result = mysqli_query($conn, $revenue_query);
    if($revenue_result && $row = mysqli_fetch_assoc($revenue_result)) {
        $analytics['total_revenue'] = $row['total'] ?? 0;
        $analytics['active_subscriptions'] = $row['count'] ?? 0;
    }
    
    // Calculate average revenue per user
    if($analytics['premium_users'] > 0) {
        $analytics['average_revenue_per_user'] = $analytics['total_revenue'] / $analytics['premium_users'];
    }
    
    // Calculate growth percentage (compare to previous period)
    if($filter !== 'all' && $filter !== 'custom') {
        $days_map = ['week' => 7, 'month' => 30, 'quarter' => 90, 'year' => 365];
        if(isset($days_map[$filter])) {
            $days = $days_map[$filter];
            $prev_period_query = "SELECT COUNT(*) as prev_users 
                                  FROM user 
                                  WHERE username != 'admin' 
                                  AND created_at >= DATE_SUB(NOW(), INTERVAL " . ($days * 2) . " DAY)
                                  AND created_at < DATE_SUB(NOW(), INTERVAL $days DAY)";
            $prev_result = mysqli_query($conn, $prev_period_query);
            if($prev_result && $row = mysqli_fetch_assoc($prev_result)) {
                $prev_users = $row['prev_users'];
                if($prev_users > 0) {
                    $analytics['growth_percentage'] = round((($analytics['new_users_period'] - $prev_users) / $prev_users) * 100, 1);
                } else if($analytics['new_users_period'] > 0) {
                    $analytics['growth_percentage'] = 100;
                }
            }
        }
    }
    
    // Get chart data based on filter
    if($filter === 'all' || $filter === 'year') {
        // Monthly breakdown for all time or last year
        $chart_interval = ($filter === 'all') ? '730 DAY' : '365 DAY';
        $chart_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                        FROM user 
                        WHERE username != 'admin' 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL $chart_interval)
                        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                        ORDER BY month ASC";
        
        $chart_result = mysqli_query($conn, $chart_query);
        $chart_data = [];
        
        while($row = mysqli_fetch_assoc($chart_result)) {
            $chart_data[$row['month']] = $row['count'];
        }
        
        // Fill in missing months with 0
        $months_back = ($filter === 'all') ? 24 : 12;
        for($i = $months_back - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $analytics['chart_labels'][] = date('M Y', strtotime($month . '-01'));
            $analytics['chart_data'][] = isset($chart_data[$month]) ? $chart_data[$month] : 0;
        }
        
    } else if($filter === 'week' || $filter === 'month') {
        // Daily breakdown
        $days = ($filter === 'week') ? 7 : 30;
        $chart_query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                        FROM user 
                        WHERE username != 'admin' 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";
        
        $chart_result = mysqli_query($conn, $chart_query);
        $chart_data = [];
        
        while($row = mysqli_fetch_assoc($chart_result)) {
            $chart_data[$row['date']] = $row['count'];
        }
        
        // Fill in missing dates with 0
        for($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $analytics['chart_labels'][] = date('M d', strtotime($date));
            $analytics['chart_data'][] = isset($chart_data[$date]) ? $chart_data[$date] : 0;
        }
        
    } else if($filter === 'quarter') {
        // Weekly breakdown for 90 days
        $chart_query = "SELECT YEARWEEK(created_at) as week, COUNT(*) as count 
                        FROM user 
                        WHERE username != 'admin' 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                        GROUP BY YEARWEEK(created_at)
                        ORDER BY week ASC";
        
        $chart_result = mysqli_query($conn, $chart_query);
        while($row = mysqli_fetch_assoc($chart_result)) {
            $week = substr($row['week'], 4);
            $analytics['chart_labels'][] = "Week $week";
            $analytics['chart_data'][] = $row['count'];
        }
        
    } else if($filter === 'custom' && $custom_start_date && $custom_end_date) {
        // Daily breakdown for custom range
        $chart_query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                        FROM user 
                        WHERE username != 'admin' 
                        AND DATE(created_at) BETWEEN '$custom_start_date' AND '$custom_end_date'
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";
        
        $chart_result = mysqli_query($conn, $chart_query);
        while($row = mysqli_fetch_assoc($chart_result)) {
            $analytics['chart_labels'][] = date('M d', strtotime($row['date']));
            $analytics['chart_data'][] = $row['count'];
        }
    }
    
    // ============================================
    // REVENUE CHART DATA (Money Over Time)
    // ============================================
    if($filter === 'all' || $filter === 'year') {
        // Monthly revenue
        $chart_interval = ($filter === 'all') ? '730 DAY' : '365 DAY';
        $revenue_chart_query = "SELECT DATE_FORMAT(s.created_at, '%Y-%m') as month, SUM(s.amount) as revenue
                                FROM subscriptions s
                                INNER JOIN user u ON s.student_id = u.user_id
                                WHERE u.username != 'admin'
                                AND s.created_at >= DATE_SUB(NOW(), INTERVAL $chart_interval)
                                AND s.status = 'active'
                                GROUP BY DATE_FORMAT(s.created_at, '%Y-%m')
                                ORDER BY month ASC";
        
        $revenue_result = mysqli_query($conn, $revenue_chart_query);
        $revenue_data = [];
        while($row = mysqli_fetch_assoc($revenue_result)) {
            $revenue_data[$row['month']] = $row['revenue'];
        }
        
        $months_back = ($filter === 'all') ? 24 : 12;
        for($i = $months_back - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $analytics['revenue_chart_data'][] = isset($revenue_data[$month]) ? $revenue_data[$month] : 0;
        }
        
    } else if($filter === 'week' || $filter === 'month') {
        $days = ($filter === 'week') ? 7 : 30;
        $revenue_chart_query = "SELECT DATE(s.created_at) as date, SUM(s.amount) as revenue
                                FROM subscriptions s
                                INNER JOIN user u ON s.student_id = u.user_id
                                WHERE u.username != 'admin'
                                AND s.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
                                AND s.status = 'active'
                                GROUP BY DATE(s.created_at)
                                ORDER BY date ASC";
        
        $revenue_result = mysqli_query($conn, $revenue_chart_query);
        $revenue_data = [];
        while($row = mysqli_fetch_assoc($revenue_result)) {
            $revenue_data[$row['date']] = $row['revenue'];
        }
        
        for($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $analytics['revenue_chart_data'][] = isset($revenue_data[$date]) ? $revenue_data[$date] : 0;
        }
    } else if($filter === 'custom' && $custom_start_date && $custom_end_date) {
        $revenue_chart_query = "SELECT DATE(s.created_at) as date, SUM(s.amount) as revenue
                                FROM subscriptions s
                                INNER JOIN user u ON s.student_id = u.user_id
                                WHERE u.username != 'admin'
                                AND DATE(s.created_at) BETWEEN '$custom_start_date' AND '$custom_end_date'
                                AND s.status = 'active'
                                GROUP BY DATE(s.created_at)
                                ORDER BY date ASC";
        
        $revenue_result = mysqli_query($conn, $revenue_chart_query);
        while($row = mysqli_fetch_assoc($revenue_result)) {
            $analytics['revenue_chart_data'][] = $row['revenue'];
        }
    }
    
    // ============================================
    // PREMIUM USERS CHART DATA
    // ============================================
    if($filter === 'all' || $filter === 'year') {
        $chart_interval = ($filter === 'all') ? '730 DAY' : '365 DAY';
        $premium_chart_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                                COUNT(*) as count
                                FROM user
                                WHERE username != 'admin'
                                AND subscription_type = 'premium'
                                AND created_at >= DATE_SUB(NOW(), INTERVAL $chart_interval)
                                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                                ORDER BY month ASC";
        
        $premium_result = mysqli_query($conn, $premium_chart_query);
        $premium_data = [];
        while($row = mysqli_fetch_assoc($premium_result)) {
            $premium_data[$row['month']] = $row['count'];
        }
        
        $months_back = ($filter === 'all') ? 24 : 12;
        for($i = $months_back - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $analytics['premium_chart_data'][] = isset($premium_data[$month]) ? $premium_data[$month] : 0;
        }
    } else if($filter === 'week' || $filter === 'month') {
        $days = ($filter === 'week') ? 7 : 30;
        $premium_chart_query = "SELECT DATE(created_at) as date, COUNT(*) as count
                                FROM user
                                WHERE username != 'admin'
                                AND subscription_type = 'premium'
                                AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
                                GROUP BY DATE(created_at)
                                ORDER BY date ASC";
        
        $premium_result = mysqli_query($conn, $premium_chart_query);
        $premium_data = [];
        while($row = mysqli_fetch_assoc($premium_result)) {
            $premium_data[$row['date']] = $row['count'];
        }
        
        for($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $analytics['premium_chart_data'][] = isset($premium_data[$date]) ? $premium_data[$date] : 0;
        }
    } else if($filter === 'custom' && $custom_start_date && $custom_end_date) {
        $premium_chart_query = "SELECT DATE(created_at) as date, COUNT(*) as count
                                FROM user
                                WHERE username != 'admin'
                                AND subscription_type = 'premium'
                                AND DATE(created_at) BETWEEN '$custom_start_date' AND '$custom_end_date'
                                GROUP BY DATE(created_at)
                                ORDER BY date ASC";
        
        $premium_result = mysqli_query($conn, $premium_chart_query);
        while($row = mysqli_fetch_assoc($premium_result)) {
            $analytics['premium_chart_data'][] = $row['count'];
        }
    }
    
    // Get recent users (filtered)
    $recent_query = "SELECT user_id, username, email, subscription_type, created_at 
                     FROM user u
                     WHERE u.username != 'admin' $where_clause
                     ORDER BY u.created_at DESC 
                     LIMIT 50";
    $recent_result = mysqli_query($conn, $recent_query);
    while($row = mysqli_fetch_assoc($recent_result)) {
        $analytics['recent_users'][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - JomCoding LMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            color: #667eea;
            margin-bottom: 20px;
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
        }

        .login-btn:hover {
            transform: translateY(-2px);
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

        .export-btn {
            padding: 10px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .export-btn:hover {
            background: #229954;
        }

        .filter-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            padding: 10px;
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
        }

        .apply-filter-btn:hover {
            background: #5568d3;
        }

        .reset-filter-btn {
            padding: 10px 24px;
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
        }

        .reset-filter-btn:hover {
            background: #7f8c8d;
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
            margin-bottom: 15px;
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
        .stat-icon.red { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
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
        }

        .stat-change.positive {
            color: #27ae60;
        }

        .stat-change.negative {
            color: #e74c3c;
        }

        /* Chart Container */
        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .chart-header {
            margin-bottom: 25px;
        }

        .chart-header h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .chart-header p {
            font-size: 14px;
            color: #7f8c8d;
        }

        canvas {
            max-height: 400px;
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e0e6ed;
        }

        .table-header h3 {
            font-size: 18px;
            color: #2c3e50;
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
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
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

        .time-ago {
            color: #7f8c8d;
            font-size: 13px;
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

        .custom-dates {
            display: none;
        }

        .custom-dates.active {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        }
    </style>
</head>
<body>
    <?php if(!isset($_SESSION['admin_verified'])): ?>
        <!-- Login Screen -->
        <div class="login-screen">
            <div class="login-container">
                <div class="login-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h2>Analytics Dashboard</h2>
                <p>Enter admin password to continue</p>

                <?php if($error): ?>
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Admin Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" name="verify" class="login-btn">
                        <i class="fas fa-lock"></i> Verify & Access
                    </button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Dashboard Layout -->
        <div class="dashboard-layout">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <div class="sidebar-logo">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="sidebar-title">ADMIN JomCoding</div>
                    <div class="sidebar-subtitle">LMS • Learning Management System</div>
                </div>

                <ul class="sidebar-menu">
                    <li><a href="admin_manage_subscriptions.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                    <li><a href="analytics.php" class="active"><i class="fas fa-chart-line"></i> <span>Analytics</span></a></li>
                </ul>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Top Navigation -->
                <nav class="top-nav">
                    <h1 class="page-title">Analytics Dashboard</h1>
                    <div class="user-info">
                        <div class="notification-icon">
                        
                        </div>
                        <div class="user-avatar">AD</div>
                        <div class="user-details">
                            <span class="user-name">Admin</span>
                            <span class="user-email">admin@jomcoding.edu</span>
                        </div>
                        <a href="?logout" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </nav>

                <!-- Dashboard Content -->
                <div class="dashboard-content">
                    
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <div class="filter-header">
                            <h3><i class="fas fa-filter"></i> Filter Analytics Data</h3>
                            <a href="?export=excel&filter=<?php echo $filter; ?><?php echo ($filter === 'custom' && $custom_start_date && $custom_end_date) ? '&start_date=' . $custom_start_date . '&end_date=' . $custom_end_date : ''; ?>" class="export-btn">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </a>
                        </div>
                        
                        <form method="GET" id="filterForm" action="analytics.php">
                            <div class="filter-controls">
                                <div class="filter-group">
                                    <label>Time Period</label>
                                    <select name="filter" id="filterSelect">
                                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                                        <option value="week" <?php echo $filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                        <option value="month" <?php echo $filter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                                        <option value="quarter" <?php echo $filter === 'quarter' ? 'selected' : ''; ?>>Last 90 Days</option>
                                        <option value="year" <?php echo $filter === 'year' ? 'selected' : ''; ?>>Last 12 Months</option>
                                        <option value="custom" <?php echo $filter === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                    </select>
                                </div>
                                
                                <div class="filter-buttons">
                                    <button type="submit" class="apply-filter-btn">
                                        <i class="fas fa-check"></i> Apply Filter
                                    </button>
                                    <a href="analytics.php" class="reset-filter-btn">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                </div>
                            </div>
                            
                            <div class="custom-dates <?php echo $filter === 'custom' ? 'active' : ''; ?>" id="customDates">
                                <div class="filter-group">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($custom_start_date); ?>">
                                </div>
                                <div class="filter-group">
                                    <label>End Date</label>
                                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($custom_end_date); ?>">
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo number_format($analytics['total_users']); ?></div>
                                    <div class="stat-label">Total Users (All Time)</div>
                                </div>
                                <div class="stat-icon blue">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo number_format($analytics['total_users_filtered']); ?></div>
                                    <div class="stat-label">New Sign Ups (<?php echo $period_name; ?>)</div>
                                </div>
                                <div class="stat-icon green">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                            </div>
                            <?php if($filter !== 'all' && $filter !== 'custom'): ?>
                            <div class="stat-change <?php echo $analytics['growth_percentage'] >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-arrow-<?php echo $analytics['growth_percentage'] >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($analytics['growth_percentage']); ?>% from previous period
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo number_format($analytics['premium_users']); ?></div>
                                    <div class="stat-label">Premium Users</div>
                                </div>
                                <div class="stat-icon purple">
                                    <i class="fas fa-crown"></i>
                                </div>
                            </div>
                            <div class="stat-change positive">
                                <i class="fas fa-percentage"></i>
                                <?php echo $analytics['premium_percentage']; ?>% of filtered users
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value"><?php echo number_format($analytics['free_users']); ?></div>
                                    <div class="stat-label">Free Users</div>
                                </div>
                                <div class="stat-icon orange">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value">RM<?php echo number_format($analytics['total_revenue'], 2); ?></div>
                                    <div class="stat-label">Total Revenue</div>
                                </div>
                                <div class="stat-icon teal">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                            <div class="stat-change positive">
                                <i class="fas fa-coins"></i>
                                <?php echo $analytics['active_subscriptions']; ?> active subscriptions
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-value">RM<?php echo number_format($analytics['average_revenue_per_user'], 2); ?></div>
                                    <div class="stat-label">Avg Revenue/User</div>
                                </div>
                                <div class="stat-icon red">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Growth Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>📈 User Growth Trend</h3>
                            <p>New user registrations over time (<?php echo $period_name; ?>)</p>
                        </div>
                        <canvas id="userGrowthChart"></canvas>
                    </div>

                    <!-- Revenue Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>💰 Revenue Trend</h3>
                            <p>Total revenue from subscriptions (<?php echo $period_name; ?>)</p>
                        </div>
                        <canvas id="revenueChart"></canvas>
                    </div>

                    <!-- Premium Users Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>👑 Premium Subscriptions</h3>
                            <p>Premium user growth over time (<?php echo $period_name; ?>)</p>
                        </div>
                        <canvas id="premiumChart"></canvas>
                    </div>

                    <!-- Recent Users Table -->
                    <div class="table-container">
                        <div class="table-header">
                            <h3>👥 User Registrations (<?php echo $period_name; ?>)</h3>
                        </div>

                        <?php if(count($analytics['recent_users']) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Subscription</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($analytics['recent_users'] as $user): ?>
                                        <tr>
                                            <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $user['subscription_type']; ?>">
                                                    <?php if($user['subscription_type'] === 'premium'): ?>
                                                        <i class="fas fa-crown"></i>
                                                    <?php endif; ?>
                                                    <?php echo ucfirst($user['subscription_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="time-ago">
                                                    <?php 
                                                    $created = strtotime($user['created_at']);
                                                    $now = time();
                                                    $diff = $now - $created;
                                                    
                                                    if($diff < 3600) {
                                                        echo round($diff / 60) . ' mins ago';
                                                    } else if($diff < 86400) {
                                                        echo round($diff / 3600) . ' hours ago';
                                                    } else if($diff < 604800) {
                                                        echo round($diff / 86400) . ' days ago';
                                                    } else {
                                                        echo date('M d, Y', $created);
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-users"></i>
                                <p>No users found for the selected period</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>

        <!-- Chart.js Script -->
        <script>
            const ctx = document.getElementById('userGrowthChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($analytics['chart_labels']); ?>,
                    datasets: [{
                        label: 'New Users',
                        data: <?php echo json_encode($analytics['chart_data']); ?>,
                        borderColor: 'rgb(102, 126, 234)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgb(102, 126, 234)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($analytics['chart_labels']); ?>,
                    datasets: [{
                        label: 'Revenue (RM)',
                        data: <?php echo json_encode($analytics['revenue_chart_data']); ?>,
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgb(16, 185, 129)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return 'RM ' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 12
                                },
                                callback: function(value) {
                                    return 'RM ' + value;
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Premium Users Chart
            const premiumCtx = document.getElementById('premiumChart').getContext('2d');
            const premiumChart = new Chart(premiumCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($analytics['chart_labels']); ?>,
                    datasets: [{
                        label: 'Premium Users',
                        data: <?php echo json_encode($analytics['premium_chart_data']); ?>,
                        borderColor: 'rgb(168, 85, 247)',
                        backgroundColor: 'rgba(168, 85, 247, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgb(168, 85, 247)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });


            function toggleCustomDates() {
                const filterSelect = document.getElementById('filterSelect');
                const customDates = document.getElementById('customDates');
                
                if(filterSelect.value === 'custom') {
                    customDates.classList.add('active');
                } else {
                    customDates.classList.remove('active');
                    // Auto-submit when non-custom option is selected
                    document.getElementById('filterForm').submit();
                }
            }
            
            // Update the select onchange to call toggleCustomDates
            document.getElementById('filterSelect').onchange = toggleCustomDates;
        </script>
    <?php endif; ?>
</body>
</html>