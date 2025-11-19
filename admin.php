<?php
require_once 'config.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}

// Handle user registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_user'])) {
    $rfidCode = $_POST['rfid_code'];
    $usn = $_POST['usn'];
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    
    // Handle photo upload
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = $_FILES['photo']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo = 'user_' . time() . '.' . $extension;
            move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $photo);
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO users (rfid_code, usn, name, gender, email, photo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $rfidCode, $usn, $name, $gender, $email, $photo);
    
    if ($stmt->execute()) {
        // Remove from pending table
        $conn->query("DELETE FROM pending_rfid WHERE rfid_code = '$rfidCode'");
        $success = "User registered successfully!";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Handle session start
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_session'])) {
    $sessionName = $_POST['session_name'];
    
    // End any active sessions
    $conn->query("UPDATE sessions SET is_active = 0, end_time = NOW() WHERE is_active = 1");
    
    // Start new session
    $stmt = $conn->prepare("INSERT INTO sessions (session_name) VALUES (?)");
    $stmt->bind_param("s", $sessionName);
    
    if ($stmt->execute()) {
        $sessionSuccess = "Session started successfully!";
        // Redirect to refresh the page and show active session
        header("Location: admin.php?session_started=1");
        exit();
    }
}

// Handle session end
if (isset($_GET['end_session'])) {
    // Get the active session ID
    $activeSessionResult = $conn->query("SELECT id FROM sessions WHERE is_active = 1 ORDER BY start_time DESC LIMIT 1");
    
    if ($activeSessionResult->num_rows > 0) {
        $activeSession = $activeSessionResult->fetch_assoc();
        $sessionId = $activeSession['id'];
        
        // Delete all attendance records for this session
        $stmt = $conn->prepare("DELETE FROM attendance WHERE session_id = ?");
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        
        // End the session
        $conn->query("UPDATE sessions SET is_active = 0, end_time = NOW() WHERE id = $sessionId");
        
        $sessionSuccess = "Session ended successfully! All attendance records for this session have been cleared.";
    }
    
    // Redirect to clear the URL parameter
    header("Location: admin.php?session_ended=1");
    exit();
}

// Show success message from URL parameters
if (isset($_GET['session_started'])) {
    $sessionSuccess = "Session started successfully!";
}

if (isset($_GET['session_ended'])) {
    $sessionSuccess = "Session ended successfully! All attendance records have been cleared.";
}

// Get active session
$activeSession = $conn->query("SELECT * FROM sessions WHERE is_active = 1 ORDER BY start_time DESC LIMIT 1");
$hasActiveSession = $activeSession->num_rows > 0;

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - RFID Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DM Sans', sans-serif;
            background: #f8f9fa;
            color: #2c3e50;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-weight: 600;
            font-size: 26px;
            letter-spacing: -0.5px;
        }
        
        .header-buttons {
            display: flex;
            gap: 12px;
        }
        
        .container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        
        h2 {
            margin-bottom: 24px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 22px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="email"],
        select,
        input[type="file"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        input[type="text"]:read-only {
            background: #e9ecef;
            cursor: not-allowed;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-family: 'DM Sans', sans-serif;
            letter-spacing: 0.3px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
            border: 1px solid #f5c6cb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .user-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
        }
        
        .session-status {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.3px;
        }
        
        .session-active {
            background: #d4edda;
            color: #155724;
        }
        
        .session-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        #rfidScanner {
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }
        
        #rfidScanner.waiting {
            background: #e7f3ff;
            color: #004085;
            border: 2px dashed #3498db;
            animation: pulse 2s infinite;
        }
        
        #rfidScanner.detected {
            background: #d4edda;
            color: #155724;
            border: 2px solid #27ae60;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .instruction-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-bottom: 24px;
            border-radius: 8px;
        }
        
        .instruction-box h3 {
            margin-bottom: 12px;
            color: #856404;
            font-weight: 600;
            font-size: 16px;
        }
        
        .instruction-box ol {
            margin-left: 20px;
            color: #856404;
        }
        
        .instruction-box li {
            margin-bottom: 6px;
            font-size: 14px;
        }
        
        .session-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .header-buttons {
                width: 100%;
                justify-content: center;
            }
            
            .two-column {
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
    <div class="header">
        <h1>Admin Panel</h1>
        <div class="header-buttons">
            <a href="index.php" class="btn btn-success">View Attendance</a>
            <a href="?logout=1" class="btn btn-danger">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($sessionSuccess)): ?>
            <div class="success"><?php echo $sessionSuccess; ?></div>
        <?php endif; ?>
        
        <?php if (!$hasActiveSession): ?>
            <div class="instruction-box">
                <h3>⚠️ Important: Start a Session First!</h3>
                <ol>
                    <li>Start a new session below</li>
                    <li>Then scan your RFID card</li>
                    <li>The card number will auto-fill in the registration form</li>
                </ol>
            </div>
        <?php endif; ?>
        
        <!-- Session Management -->
        <div class="card">
            <h2>Session Management</h2>
            <?php if ($hasActiveSession): ?>
                <div class="session-actions">
                    <div>
                        <span class="session-status session-active">Active Session</span>
                    </div>
                    <a href="?end_session=1" class="btn btn-danger" onclick="return confirm('Are you sure? This will delete all attendance records for the current session!');">End Session & Clear Data</a>
                </div>
            <?php else: ?>
                <p><span class="session-status session-inactive">No Active Session</span></p>
                <form method="POST" style="margin-top: 24px;">
                    <div class="form-group">
                        <label>Session Name</label>
                        <input type="text" name="session_name" placeholder="e.g., Morning Session - 19 Nov 2025" required>
                    </div>
                    <button type="submit" name="start_session" class="btn btn-success">Start New Session</button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Register User -->
        <div class="card">
            <h2>Register New User</h2>
            
            <?php if (!$hasActiveSession): ?>
                <div class="error">⚠️ Please start a session first before scanning cards!</div>
            <?php endif; ?>
            
            <div id="rfidScanner" class="waiting">
                🔍 Waiting for RFID card scan...
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="registerForm">
                <div class="form-group">
                    <label>RFID Code</label>
                    <input type="text" id="rfidInput" name="rfid_code" required readonly>
                </div>
                
                <div class="two-column">
                    <div class="form-group">
                        <label>USN</label>
                        <input type="text" name="usn" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" required>
                    </div>
                </div>
                
                <div class="two-column">
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Profile Photo</label>
                    <input type="file" name="photo" accept="image/*">
                </div>
                
                <button type="submit" name="register_user" class="btn btn-primary">Register User</button>
            </form>
        </div>
        
        <!-- Registered Users -->
        <div class="card">
            <h2>Registered Users (<?php echo $users->num_rows; ?>)</h2>
            <?php if ($users->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>USN</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Email</th>
                            <th>RFID Code</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($user['photo']): ?>
                                        <img src="uploads/<?php echo $user['photo']; ?>" class="user-photo" alt="Photo">
                                    <?php else: ?>
                                        <div style="width:50px;height:50px;background:#e9ecef;border-radius:50%;"></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['usn']; ?></td>
                                <td><?php echo $user['name']; ?></td>
                                <td><?php echo $user['gender']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['rfid_code']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #adb5bd; padding: 40px;">No users registered yet</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let lastDetectedRFID = '';
        
        // Poll for unregistered RFID scans every second
        setInterval(checkForPendingRFID, 1000);
        
        function checkForPendingRFID() {
            fetch('api.php?action=get_pending')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.rfid) {
                        // Only update if it's a new RFID
                        if (data.rfid !== lastDetectedRFID) {
                            lastDetectedRFID = data.rfid;
                            setRFIDCode(data.rfid);
                        }
                    }
                })
                .catch(error => {
                    console.log('Polling error:', error);
                });
        }
        
        function setRFIDCode(code) {
            const rfidInput = document.getElementById('rfidInput');
            const scanner = document.getElementById('rfidScanner');
            
            rfidInput.value = code;
            scanner.textContent = '✓ RFID Card Detected: ' + code;
            scanner.className = 'detected';
            
            // Play a beep sound (optional)
            playBeep();
            
            // Focus on USN field for quick data entry
            document.querySelector('input[name="usn"]').focus();
            
            // Reset scanner after 5 seconds
            setTimeout(() => {
                scanner.textContent = '🔍 Waiting for RFID card scan...';
                scanner.className = 'waiting';
            }, 5000);
        }
        
        function playBeep() {
            // Create a simple beep sound
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        }
    </script>
</body>
</html>