<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tshirt_id = isset($_POST['tshirtId']) ? intval($_POST['tshirtId']) : 0;
    $party_id = isset($_POST['partyId']) ? intval($_POST['partyId']) : 0;
    
    if ($tshirt_id > 0) {
        $stmt = $conn->prepare("DELETE FROM tshirt_orders WHERE id = ?");
        $stmt->bind_param("i", $tshirt_id);
        
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