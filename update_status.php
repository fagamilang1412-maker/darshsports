<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $party_id = isset($_POST['partyId']) ? intval($_POST['partyId']) : 0;
    $status = $_POST['status'];
    
    if ($party_id > 0) {
        $stmt = $conn->prepare("UPDATE parties SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $party_id);
        
        if ($stmt->execute()) {
            header("Location: index.php?party_id=" . $party_id);
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>