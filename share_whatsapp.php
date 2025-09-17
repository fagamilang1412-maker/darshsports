<?php
// share_whatsapp.php
include 'config.php';

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

// Check if PDF file was uploaded
if (!isset($_FILES['pdf'])) {
    echo json_encode(['success' => false, 'message' => 'No PDF file received']);
    exit;
}

$party_id = $_POST['party_id'];
$mobile_number = $_POST['mobile_number'];

// Create bills directory if it doesn't exist
$billsDir = 'bills';
if (!file_exists($billsDir)) {
    mkdir($billsDir, 0777, true);
}

// Generate a unique filename
$filename = 'bill_' . $party_id . '_' . time() . '.pdf';
$filepath = $billsDir . '/' . $filename;

// Move the uploaded file to the bills directory
if (move_uploaded_file($_FILES['pdf']['tmp_name'], $filepath)) {
    // Store the file path in the database for future reference
    $stmt = $conn->prepare("UPDATE parties SET bill_pdf_path = ? WHERE id = ?");
    $stmt->bind_param("si", $filepath, $party_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'PDF saved successfully', 'filepath' => $filepath]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save PDF']);
}
?>