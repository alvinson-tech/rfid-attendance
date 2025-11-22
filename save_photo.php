<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['photo']) || !isset($_POST['attendance_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing photo or attendance ID']);
    exit;
}

$attendanceId = intval($_POST['attendance_id']);
$photo = $_FILES['photo'];

// Validate file
if ($photo['error'] !== 0) {
    echo json_encode(['status' => 'error', 'message' => 'File upload error']);
    exit;
}

// Check file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
if (!in_array($photo['type'], $allowedTypes)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type']);
    exit;
}

// Create session_photos directory if it doesn't exist
$photoDir = 'session_photos/';
if (!file_exists($photoDir)) {
    mkdir($photoDir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
$filename = 'session_' . $attendanceId . '_' . time() . '.' . $extension;
$filepath = $photoDir . $filename;

// Move uploaded file
if (move_uploaded_file($photo['tmp_name'], $filepath)) {
    // Update attendance record with photo
    $stmt = $conn->prepare("UPDATE attendance SET session_photo = ?, photo_captured = 1 WHERE id = ?");
    $stmt->bind_param("si", $filename, $attendanceId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Photo saved successfully',
            'filename' => $filename
        ]);
    } else {
        // Delete uploaded file if database update fails
        unlink($filepath);
        echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save photo file']);
}
?>