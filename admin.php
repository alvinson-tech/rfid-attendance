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
    }
}

// Handle session end
if (isset($_GET['end_session'])) {
    $conn->query("UPDATE sessions SET is_active = 0, end_time = NOW() WHERE is_active = 1");
    $sessionSuccess = "Session ended successfully!";
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="email"],
        select,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
        }
        
        .user-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .session-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .session-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .session-inactive {
            background: #fee;
            color: #c33;
        }
        
        #rfidScanner {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            text-align: center;
            padding: 20px;
            background: #f0f4ff;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin Panel</h1>
        <div>
            <a href="index.php" class="btn btn-success" style="margin-right: 10px;">View Attendance</a>
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
        
        <!-- Session Management -->
        <div class="card">
            <h2>Session Management</h2>
            <?php if ($hasActiveSession): ?>
                <p>
                    <span class="session-status session-active">Active Session</span>
                    <a href="?end_session=1" class="btn btn-danger" style="float: right;">End Session</a>
                </p>
            <?php else: ?>
                <p><span class="session-status session-inactive">No Active Session</span></p>
                <form method="POST" style="margin-top: 20px;">
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
            <div id="rfidScanner">Waiting for RFID card... Scan a card to auto-fill RFID Code</div>
            
            <form method="POST" enctype="multipart/form-data">
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
            <h2>Registered Users</h2>
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
                                    <img src="<?php echo UPLOAD_DIR . $user['photo']; ?>" class="user-photo" alt="Photo">
                                <?php else: ?>
                                    <div style="width:50px;height:50px;background:#e0e0e0;border-radius:50%;"></div>
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
        </div>
    </div>
    
    <script>
        // Poll for new RFID scans
        setInterval(pollRFID, 1000);
        
        function pollRFID() {
            // In a real implementation, you would poll the ESP32 or use WebSockets
            // For now, this is a placeholder for manual entry
        }
        
        // Allow manual RFID entry by clicking the scanner div
        document.getElementById('rfidScanner').addEventListener('click', function() {
            const code = prompt('Enter RFID code manually:');
            if (code) {
                document.getElementById('rfidInput').value = code;
                this.textContent = 'RFID Code captured: ' + code;
                this.style.background = '#d1fae5';
            }
        });
    </script>
</body>
</html>