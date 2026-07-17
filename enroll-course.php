<?php
// enroll_course.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to enroll']);
    exit();
}

require_once 'config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    
    if($course_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
        exit();
    }
    
    // Check if already enrolled
    $check_query = "SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You are already enrolled in this course']);
        $stmt->close();
        exit();
    }
    $stmt->close();
    
    // Enroll the user
    $enroll_query = "INSERT INTO enrollments (student_id, course_id, enrolled_at, status, progress_percentage) VALUES (?, ?, NOW(), 'active', 0)";
    $stmt = $conn->prepare($enroll_query);
    $stmt->bind_param("ii", $user_id, $course_id);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Successfully enrolled in the course']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to enroll. Please try again.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>