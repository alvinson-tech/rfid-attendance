<?php
require_once 'config.php';

echo "<h1>Photo Upload Diagnostic Tool</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

// Check if uploads directory exists
echo "<h2>1. Upload Directory Check</h2>";
if (file_exists('uploads/')) {
    echo "<p class='success'>✓ uploads/ directory exists</p>";
    
    // Check if writable
    if (is_writable('uploads/')) {
        echo "<p class='success'>✓ uploads/ directory is writable</p>";
    } else {
        echo "<p class='error'>✗ uploads/ directory is NOT writable. Run: chmod 777 uploads/</p>";
    }
    
    // List files in uploads directory
    $files = scandir('uploads/');
    echo "<p class='info'>Files in uploads/: " . count($files) - 2 . " files</p>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p class='error'>✗ uploads/ directory does NOT exist. Creating it now...</p>";
    mkdir('uploads/', 0777, true);
    echo "<p class='success'>✓ Created uploads/ directory</p>";
}

// Check database for users with photos
echo "<h2>2. Database Check</h2>";
$users = $conn->query("SELECT id, name, photo FROM users");

if ($users->num_rows > 0) {
    echo "<p class='info'>Found " . $users->num_rows . " users in database</p>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Photo Filename</th><th>File Exists?</th><th>Preview</th></tr>";
    
    while ($user = $users->fetch_assoc()) {
        $photoPath = 'uploads/' . $user['photo'];
        $fileExists = file_exists($photoPath);
        
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['name'] . "</td>";
        echo "<td>" . ($user['photo'] ? $user['photo'] : '<em>No photo</em>') . "</td>";
        
        if ($user['photo']) {
            if ($fileExists) {
                echo "<td class='success'>✓ YES</td>";
                echo "<td><img src='$photoPath' width='50' height='50' style='border-radius: 50%; object-fit: cover;'></td>";
            } else {
                echo "<td class='error'>✗ NO (File missing!)</td>";
                echo "<td>-</td>";
            }
        } else {
            echo "<td>-</td>";
            echo "<td>-</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='info'>No users found in database</p>";
}

// Check PHP upload settings
echo "<h2>3. PHP Upload Settings</h2>";
echo "<ul>";
echo "<li>upload_max_filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "<li>post_max_size: " . ini_get('post_max_size') . "</li>";
echo "<li>file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "</li>";
echo "</ul>";

// Test image creation
echo "<h2>4. Test Upload Form</h2>";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_photo'])) {
    echo "<h3>Upload Test Result:</h3>";
    
    if ($_FILES['test_photo']['error'] == 0) {
        $testFilename = 'test_' . time() . '.jpg';
        $uploadPath = 'uploads/' . $testFilename;
        
        if (move_uploaded_file($_FILES['test_photo']['tmp_name'], $uploadPath)) {
            echo "<p class='success'>✓ Test upload successful!</p>";
            echo "<p>File saved as: $testFilename</p>";
            echo "<img src='$uploadPath' width='200'>";
        } else {
            echo "<p class='error'>✗ Failed to move uploaded file</p>";
        }
    } else {
        echo "<p class='error'>✗ Upload error: " . $_FILES['test_photo']['error'] . "</p>";
    }
}

?>
<form method="POST" enctype="multipart/form-data">
    <p>Test the upload system:</p>
    <input type="file" name="test_photo" accept="image/*" required>
    <button type="submit">Test Upload</button>
</form>

<hr>
<h2>Quick Fix Commands</h2>
<p>If you're on Linux/Mac, run these commands in your project directory:</p>
<pre style="background: #f0f0f0; padding: 10px; border-radius: 5px;">
chmod 777 uploads/
chown -R www-data:www-data uploads/
</pre>

<p>If you're on Windows, right-click the uploads folder → Properties → Security → Edit → Add "Full Control" for Users</p>