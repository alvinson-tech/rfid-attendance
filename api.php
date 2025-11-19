<?php
require_once 'config.php';

header('Content-Type: application/json');

// Handle RFID scan
if (isset($_GET['action']) && $_GET['action'] == 'scan') {
    $rfidCode = $_GET['rfid'] ?? '';
    
    if (empty($rfidCode)) {
        echo json_encode(['status' => 'error', 'message' => 'No RFID code provided']);
        exit;
    }
    
    // Check if there's an active session
    $sessionQuery = "SELECT id FROM sessions WHERE is_active = 1 ORDER BY start_time DESC LIMIT 1";
    $sessionResult = $conn->query($sessionQuery);
    
    if ($sessionResult->num_rows == 0) {
        echo json_encode(['status' => 'no_session', 'message' => 'No active session']);
        exit;
    }
    
    $session = $sessionResult->fetch_assoc();
    $sessionId = $session['id'];
    
    // Check if user exists
    $userQuery = "SELECT * FROM users WHERE rfid_code = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("s", $rfidCode);
    $stmt->execute();
    $userResult = $stmt->get_result();
    
    if ($userResult->num_rows == 0) {
        echo json_encode(['status' => 'not_registered', 'message' => 'Card not registered', 'rfid' => $rfidCode]);
        exit;
    }
    
    $user = $userResult->fetch_assoc();
    
    // Check if already marked present in this session
    $checkQuery = "SELECT * FROM attendance WHERE session_id = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $sessionId, $user['id']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode([
            'status' => 'already_present', 
            'message' => 'Already marked present',
            'user' => $user
        ]);
        exit;
    }
    
    // Mark attendance
    $insertQuery = "INSERT INTO attendance (session_id, user_id, rfid_code) VALUES (?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("iis", $sessionId, $user['id'], $rfidCode);
    
    if ($insertStmt->execute()) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Attendance marked',
            'user' => $user
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to mark attendance']);
    }
    
    exit;
}

// Get latest attendance records for display
if (isset($_GET['action']) && $_GET['action'] == 'get_latest') {
    $sessionQuery = "SELECT id FROM sessions WHERE is_active = 1 ORDER BY start_time DESC LIMIT 1";
    $sessionResult = $conn->query($sessionQuery);
    
    if ($sessionResult->num_rows == 0) {
        echo json_encode(['status' => 'no_session', 'records' => []]);
        exit;
    }
    
    $session = $sessionResult->fetch_assoc();
    $sessionId = $session['id'];
    
    $query = "SELECT a.*, u.name, u.usn, u.gender, u.email, u.photo 
              FROM attendance a 
              JOIN users u ON a.user_id = u.id 
              WHERE a.session_id = ? 
              ORDER BY a.timestamp DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'records' => $records]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>