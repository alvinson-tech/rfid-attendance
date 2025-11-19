<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

// Get active session
$activeSession = $conn->query("SELECT * FROM sessions WHERE is_active = 1 ORDER BY start_time DESC LIMIT 1");

if ($activeSession->num_rows == 0) {
    die("No active session found!");
}

$session = $activeSession->fetch_assoc();
$sessionId = $session['id'];
$sessionName = $session['session_name'];
$sessionDate = date('d M Y, h:i A', strtotime($session['start_time']));

// Get attendance records
$query = "SELECT a.*, u.name, u.usn, u.gender, u.email 
          FROM attendance a 
          JOIN users u ON a.user_id = u.id 
          WHERE a.session_id = ? 
          ORDER BY a.timestamp ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $sessionId);
$stmt->execute();
$result = $stmt->get_result();

// Create PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('RFID Attendance System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Attendance Report - ' . $sessionName);
$pdf->SetSubject('Attendance Report');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Title
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(102, 126, 234);
$pdf->Cell(0, 15, 'RFID Attendance Report', 0, 1, 'C');

// Session details
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, 'Session: ' . $sessionName, 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, 'Date & Time: ' . $sessionDate, 0, 1, 'L');
$pdf->Cell(0, 8, 'Total Present: ' . $result->num_rows, 0, 1, 'L');

$pdf->Ln(5);

// Table header
$pdf->SetFillColor(102, 126, 234);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 11);

$pdf->Cell(15, 10, 'No.', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'USN', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'Name', 1, 0, 'C', true);
$pdf->Cell(25, 10, 'Gender', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Time', 1, 1, 'C', true);

// Table content
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 10);

$counter = 1;
$fill = false;

while ($row = $result->fetch_assoc()) {
    $time = date('h:i A', strtotime($row['timestamp']));
    
    $pdf->Cell(15, 8, $counter, 1, 0, 'C', $fill);
    $pdf->Cell(35, 8, $row['usn'], 1, 0, 'L', $fill);
    $pdf->Cell(50, 8, $row['name'], 1, 0, 'L', $fill);
    $pdf->Cell(25, 8, $row['gender'], 1, 0, 'C', $fill);
    $pdf->Cell(40, 8, $time, 1, 1, 'C', $fill);
    
    $fill = !$fill;
    $counter++;
}

// Footer
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 9);
$pdf->SetTextColor(128, 128, 128);
$pdf->Cell(0, 8, 'Generated on: ' . date('d M Y, h:i A'), 0, 1, 'R');
$pdf->Cell(0, 8, 'RFID Attendance System', 0, 1, 'R');

// Output PDF
$filename = 'Attendance_' . date('Y-m-d_His') . '.pdf';
$pdf->Output($filename, 'D');
?>