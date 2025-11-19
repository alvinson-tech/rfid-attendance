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
            padding: 32px 20px;
            text-align: center;
            position: relative;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-weight: 600;
            font-size: 32px;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .header p {
            font-weight: 300;
            font-size: 16px;
            opacity: 0.9;
        }
        
        .header-buttons {
            position: absolute;
            top: 32px;
            right: 20px;
            display: flex;
            gap: 12px;
        }
        
        .admin-link {
            background: white;
            color: #2c3e50;
            padding: 10px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        
        .admin-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .pdf-button {
            background: #27ae60;
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 14px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .pdf-button:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        .pdf-button:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            opacity: 0.6;
            transform: none;
        }
        
        .pdf-button:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .container {
            max-width: 1400px;
            margin: 32px auto;
            padding: 0 20px;
        }
        
        .status-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .status-card h2 {
            font-weight: 600;
            font-size: 20px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .session-status {
            display: inline-block;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            margin: 20px 0;
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
        
        .status-card p {
            color: #6c757d;
            font-size: 14px;
            font-weight: 400;
        }
        
        .current-scan {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 24px;
            min-height: 200px;
            display: none;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
        }
        
        .current-scan.show {
            display: block;
            animation: slideIn 0.5s ease-out;
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
        
        .current-scan h2 {
            font-weight: 600;
            margin-bottom: 24px;
            font-size: 24px;
        }
        
        .user-details {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 24px;
            align-items: center;
        }
        
        .user-photo-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .user-info h2 {
            margin-bottom: 12px;
            font-weight: 600;
            font-size: 26px;
        }
        
        .user-info p {
            margin: 8px 0;
            font-size: 16px;
            font-weight: 400;
        }
        
        .attendance-section {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .section-header h2 {
            color: #2c3e50;
            font-weight: 600;
            font-size: 22px;
        }
        
        .section-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .attendance-count {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            letter-spacing: 0.3px;
        }
        
        .id-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        
        .id-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            color: #2c3e50;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            animation: cardAppear 0.5s ease-out;
        }
        
        @keyframes cardAppear {
            from {
                transform: scale(0.95);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .id-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border-color: #3498db;
        }
        
        .id-card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .id-card-photo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .id-card-name {
            flex: 1;
        }
        
        .id-card-name h3 {
            font-size: 18px;
            margin-bottom: 6px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .id-card-usn {
            font-size: 13px;
            color: #6c757d;
            font-weight: 500;
        }
        
        .id-card-body {
            display: grid;
            gap: 12px;
        }
        
        .id-card-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: #495057;
        }
        
        .id-card-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .id-card-label {
            font-weight: 400;
        }
        
        .id-card-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .time-badge {
            background: #f8f9fa;
            color: #495057;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .status-badge {
            background: #d4edda;
            color: #155724;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.3px;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #adb5bd;
        }
        
        .no-data-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-data h3 {
            font-weight: 500;
            font-size: 18px;
            margin-bottom: 8px;
            color: #6c757d;
        }
        
        .no-data p {
            font-weight: 400;
            font-size: 14px;
            color: #adb5bd;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 24px;
            }
            
            .header-buttons {
                position: static;
                justify-content: center;
                margin-top: 16px;
            }
            
            .id-cards-grid {
                grid-template-columns: 1fr;
            }
            
            .user-details {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .section-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
            
            .section-header-right {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-buttons">
            <a href="login.php" class="admin-link">Admin Login</a>
        </div>
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
            <h2>Welcome!</h2>
            <div class="user-details" id="userDetails">
                <!-- User details will be inserted here -->
            </div>
        </div>
        
        <div class="attendance-section">
            <div class="section-header">
                <h2>Today's Attendance</h2>
                <div class="section-header-right">
                    <div class="attendance-count" id="attendanceCount">0 Present</div>
                    <?php if ($hasActiveSession): ?>
                        <a href="download_pdf.php" class="pdf-button">📥 Download PDF</a>
                    <?php else: ?>
                        <button class="pdf-button" disabled title="Start a session to download PDF">📥 Download PDF</button>
                    <?php endif; ?>
                </div>
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
        // Audio Context for beep sounds
        let audioContext = null;
        
        function initAudioContext() {
            if (!audioContext) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
        }
        
        function playBeep(times, frequency = 800, duration = 100) {
            initAudioContext();
            
            let delay = 0;
            for (let i = 0; i < times; i++) {
                setTimeout(() => {
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.value = frequency;
                    oscillator.type = 'sine';
                    
                    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration / 1000);
                    
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + duration / 1000);
                }, delay);
                
                delay += duration + 150; // 150ms gap between beeps
            }
        }
        
        // Poll for new attendance records
        let lastStatus = '';
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
                            playBeep(1, 800, 200); // 1 beep for success
                        }
                    }
                });
        }
        
        // Listen for card scan events from API
        let lastRfidCheck = '';
        setInterval(() => {
            fetch('api.php?action=get_pending')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.rfid) {
                        if (data.rfid !== lastRfidCheck) {
                            lastRfidCheck = data.rfid;
                            // Card detected but not registered - 2 beeps
                            playBeep(2, 600, 150);
                            console.log('Unregistered card detected:', data.rfid);
                        }
                    }
                })
                .catch(error => {
                    // Card read error - 3 beeps (only on significant errors)
                    if (error.message !== lastStatus) {
                        lastStatus = error.message;
                        // Uncomment if you want error beeps:
                        // playBeep(3, 400, 100);
                    }
                });
        }, 3000);
        
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
            return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23e9ecef" width="200" height="200"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="60" fill="%23adb5bd"%3E👤%3C/text%3E%3C/svg%3E';
        }
        
        // Initial fetch
        fetchAttendance();
    </script>
</body>
</html>