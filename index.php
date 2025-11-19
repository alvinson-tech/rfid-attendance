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
            position: relative;
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
            max-width: 1400px;
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
        
        .attendance-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        h2 {
            color: #333;
        }
        
        .attendance-count {
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .id-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .id-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s, box-shadow 0.3s;
            animation: cardAppear 0.5s ease-out;
        }
        
        @keyframes cardAppear {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .id-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .id-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .id-card-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .id-card-name {
            flex: 1;
        }
        
        .id-card-name h3 {
            font-size: 20px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .id-card-usn {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .id-card-body {
            display: grid;
            gap: 10px;
        }
        
        .id-card-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .id-card-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
        }
        
        .id-card-label {
            font-weight: 500;
            opacity: 0.9;
        }
        
        .id-card-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .time-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-badge {
            background: #10b981;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-data-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            .id-cards-grid {
                grid-template-columns: 1fr;
            }
            
            .user-details {
                grid-template-columns: 1fr;
                text-align: center;
            }
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
        
        <div class="attendance-section">
            <div class="section-header">
                <h2>Today's Attendance</h2>
                <div class="attendance-count" id="attendanceCount">0 Present</div>
            </div>
            
            <div class="id-cards-grid" id="idCardsContainer">
                <div class="no-data">
                    <div class="no-data-icon">📋</div>
                    <h3>No attendance records yet</h3>
                    <p>Scan your RFID card to mark attendance</p>
                </div>
            </div>
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
                        updateAttendanceCards(data.records);
                        
                        // Show the latest scan
                        const latest = data.records[0];
                        if (latest && !document.getElementById('card-' + latest.id)) {
                            showCurrentScan(latest);
                        }
                    }
                });
        }
        
        function showCurrentScan(user) {
            const currentScan = document.getElementById('currentScan');
            const userDetails = document.getElementById('userDetails');
            
            const photoUrl = user.photo ? 'uploads/' + user.photo : getPlaceholderImage();
            
            userDetails.innerHTML = `
                <img src="${photoUrl}" class="user-photo-large" alt="Photo" onerror="this.src='${getPlaceholderImage()}'">
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
        
        let existingRecordIds = new Set();
        
        function updateAttendanceCards(records) {
            const container = document.getElementById('idCardsContainer');
            const countBadge = document.getElementById('attendanceCount');
            
            if (records.length === 0) {
                if (!container.querySelector('.no-data')) {
                    container.innerHTML = `
                        <div class="no-data">
                            <div class="no-data-icon">📋</div>
                            <h3>No attendance records yet</h3>
                            <p>Scan your RFID card to mark attendance</p>
                        </div>
                    `;
                }
                countBadge.textContent = '0 Present';
                return;
            }
            
            // Remove no-data message if it exists
            const noData = container.querySelector('.no-data');
            if (noData) {
                noData.remove();
            }
            
            countBadge.textContent = records.length + ' Present';
            
            // Only add new cards that don't exist
            records.forEach(record => {
                if (existingRecordIds.has(record.id)) {
                    return; // Skip if already exists
                }
                
                existingRecordIds.add(record.id);
                
                const photoUrl = record.photo ? 'uploads/' + record.photo : getPlaceholderImage();
                const time = new Date(record.timestamp).toLocaleTimeString('en-IN', { 
                    hour: '2-digit', 
                    minute: '2-digit'
                });
                
                const card = document.createElement('div');
                card.className = 'id-card';
                card.id = 'card-' + record.id;
                card.innerHTML = `
                    <div class="id-card-header">
                        <img src="${photoUrl}" class="id-card-photo" alt="Photo" onerror="this.src='${getPlaceholderImage()}'">
                        <div class="id-card-name">
                            <h3>${record.name}</h3>
                            <div class="id-card-usn">USN: ${record.usn}</div>
                        </div>
                    </div>
                    
                    <div class="id-card-body">
                        <div class="id-card-info">
                            <div class="id-card-icon">👤</div>
                            <span class="id-card-label">${record.gender}</span>
                        </div>
                        <div class="id-card-info">
                            <div class="id-card-icon">📧</div>
                            <span class="id-card-label">${record.email}</span>
                        </div>
                        <div class="id-card-info">
                            <div class="id-card-icon">🆔</div>
                            <span class="id-card-label">${record.rfid_code}</span>
                        </div>
                    </div>
                    
                    <div class="id-card-footer">
                        <span class="time-badge">⏰ ${time}</span>
                        <span class="status-badge">✓ Present</span>
                    </div>
                `;
                
                container.prepend(card);
            });
        }
        
        function getPlaceholderImage() {
            return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23e0e0e0" width="200" height="200"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="60" fill="%23999"%3E👤%3C/text%3E%3C/svg%3E';
        }
        
        // Initial fetch
        fetchAttendance();
    </script>
</body>
</html>