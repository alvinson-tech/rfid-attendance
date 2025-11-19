<?php
require_once 'config.php';

// If already logged in, redirect to admin panel
if (isAdminLoggedIn()) {
    redirect('admin.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            
            // Simple password comparison (no encryption)
            if ($password === $admin['password']) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                redirect('admin.php');
            } else {
                $error = 'Invalid password!';
            }
        } else {
            $error = 'Invalid username!';
        }
    } else {
        $error = 'Please fill all fields!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - RFID Attendance</title>
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
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 48px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            width: 420px;
            max-width: 100%;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 32px;
            font-weight: 600;
            font-size: 28px;
            letter-spacing: -0.5px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 15px;
            font-family: 'Raleway', sans-serif;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Raleway', sans-serif;
            letter-spacing: 0.3px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 24px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid #f5c6cb;
        }
        
        .credentials-hint {
            background: #e7f3ff;
            color: #004085;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #bee5eb;
        }
        
        .credentials-hint strong {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 15px;
        }
        
        .home-link {
            text-align: center;
            margin-top: 24px;
        }
        
        .home-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .home-link a:hover {
            color: #2c3e50;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 32px 24px;
            }
            
            h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        
        <div class="credentials-hint">
            <strong>Default Login</strong>
            Username: admin<br>
            Password: admin@123
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="home-link">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>