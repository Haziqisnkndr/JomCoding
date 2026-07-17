<?php
// track_video.php - Track when user clicks to watch a video
session_start();

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once 'config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    $video_id = isset($_POST['video_id']) ? intval($_POST['video_id']) : 0;
    
    if($course_id == 0 || $video_id == 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    // Check if already watched
    $check_query = "SELECT id, watched FROM video_progress WHERE student_id = ? AND course_id = ? AND video_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("iii", $user_id, $course_id, $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    if($existing) {
        if($existing['watched'] == 1) {
            // Already watched, don't add progress again
            echo json_encode([
                'success' => true, 
                'message' => 'Already watched',
                'already_watched' => true
            ]);
            exit();
        } else {
            // Update to watched
            $update_query = "UPDATE video_progress SET watched = 1, watched_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $existing['id']);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Insert new watched record
        $insert_query = "INSERT INTO video_progress (student_id, course_id, video_id, watched, watched_at) VALUES (?, ?, ?, 1, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iii", $user_id, $course_id, $video_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Calculate new progress (5 videos × 15% each = 75% max from videos)
    $progress_query = "SELECT COUNT(*) as watched_count FROM video_progress WHERE student_id = ? AND course_id = ? AND watched = 1";
    $stmt = $conn->prepare($progress_query);
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $progress_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $watched_count = $progress_result['watched_count'];
    $progress_percentage = $watched_count * 15; // 15% per video
    
    echo json_encode([
        'success' => true,
        'message' => 'Video tracked',
        'watched_count' => $watched_count,
        'progress_percentage' => $progress_percentage,
        'already_watched' => false
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>