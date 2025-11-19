<?php
require_once 'config.php';

// Get active session
$activeSession = $conn->query("SELECT * FROM sessions WHERE is_active = 1 ORDER BY start_time DESC LIMIT 1");
$hasActiveSession = $activeSession->num_rows > 0;
$session = $hasActiveSession ? $activeSession->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Attendance System</title>
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
            text-align: center;
        }
        
        .admin-link {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .status-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .session-status {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0;
        }
        
        .session-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .session-inactive {
            background: #fee;
            color: #c33;
        }
        
        .current-scan {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            min-height: 200px;
            display: none;
        }
        
        .current-scan.show {
            display: block;
            animation: slideIn 0.5s;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .user-details {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 20px;
            align-items: center;
        }
        
        .user-photo-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
        }
        
        .user-info h2 {
            margin-bottom: 10px;
        }
        
        .user-info p {
            margin: 5px 0;
            font-size: 16px;
        }
        
        .attendance-table {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #333;
        }
        
        .user-photo-small {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .time-badge {
            background: #e0e7ff;
            color: #4338ca;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }
        
        h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="login.php" class="admin-link">Admin Login</a>
        <h1>RFID Attendance System</h1>
        <p>Scan your card to mark attendance</p>
    </div>
    
    <div class="container">
        <div class="status-card">
            <h2>Session Status</h2>
            <?php if ($hasActiveSession): ?>
                <div class="session-status session-active">
                    ✓ Active Session: <?php echo $session['session_name']; ?>
                </div>
                <p>Started at: <?php echo date('d M Y, h:i A', strtotime($session['start_time'])); ?></p>
            <?php else: ?>
                <div class="session-status session-inactive">
                    ✗ No Active Session
                </div>
                <p>Please wait for admin to start a session</p>
            <?php endif; ?>
        </div>
        
        <div class="current-scan" id="currentScan">
            <h2 style="margin-bottom: 20px;">Welcome!</h2>
            <div class="user-details" id="userDetails">
                <!-- User details will be inserted here -->
            </div>
        </div>
        
        <div class="attendance-table">
            <h2>Today's Attendance</h2>
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>USN</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Email</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody id="attendanceBody">
                    <tr>
                        <td colspan="6" class="no-data">No attendance records yet</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Poll for new attendance records
        setInterval(fetchAttendance, 2000);
        
        function fetchAttendance() {
            fetch('api.php?action=get_latest')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.records.length > 0) {
                        updateAttendanceTable(data.records);
                        
                        // Show the latest scan
                        const latest = data.records[0];
                        if (latest && !document.getElementById('scan-' + latest.id)) {
                            showCurrentScan(latest);
                        }
                    }
                });
        }
        
        function showCurrentScan(user) {
            const currentScan = document.getElementById('currentScan');
            const userDetails = document.getElementById('userDetails');
            
            const photoUrl = user.photo ? 'uploads/' + user.photo : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="150" height="150"%3E%3Crect fill="%23e0e0e0" width="150" height="150"/%3E%3C/svg%3E';
            
            userDetails.innerHTML = `
                <img src="${photoUrl}" class="user-photo-large" alt="Photo">
                <div class="user-info">
                    <h2>${user.name}</h2>
                    <p><strong>USN:</strong> ${user.usn}</p>
                    <p><strong>Gender:</strong> ${user.gender}</p>
                    <p><strong>Email:</strong> ${user.email}</p>
                    <p style="margin-top: 15px; font-size: 18px;">
                        <strong>✓ Attendance Marked Successfully!</strong>
                    </p>
                </div>
            `;
            
            currentScan.classList.add('show');
            
            setTimeout(() => {
                currentScan.classList.remove('show');
            }, 5000);
        }
        
        function updateAttendanceTable(records) {
            const tbody = document.getElementById('attendanceBody');
            
            if (records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="no-data">No attendance records yet</td></tr>';
                return;
            }
            
            tbody.innerHTML = '';
            
            records.forEach(record => {
                const photoUrl = record.photo ? 'uploads/' + record.photo : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="50" height="50"%3E%3Crect fill="%23e0e0e0" width="50" height="50"/%3E%3C/svg%3E';
                const time = new Date(record.timestamp).toLocaleTimeString('en-IN', { 
                    hour: '2-digit', 
                    minute: '2-digit'
                });
                
                const row = document.createElement('tr');
                row.id = 'scan-' + record.id;
                row.innerHTML = `
                    <td><img src="${photoUrl}" class="user-photo-small" alt="Photo"></td>
                    <td>${record.usn}</td>
                    <td>${record.name}</td>
                    <td>${record.gender}</td>
                    <td>${record.email}</td>
                    <td><span class="time-badge">${time}</span></td>
                `;
                
                tbody.appendChild(row);
            });
        }
        
        // Initial fetch
        fetchAttendance();
    </script>
</body>
</html>