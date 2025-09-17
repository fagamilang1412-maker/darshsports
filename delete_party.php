<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $party_id = isset($_POST['partyId']) ? intval($_POST['partyId']) : 0;
    $redirect_to = isset($_POST['redirectTo']) ? $_POST['redirectTo'] : 'index.php';
    
    if ($party_id > 0) {
        // Delete party and all associated t-shirt orders (cascade delete)
        $stmt = $conn->prepare("DELETE FROM parties WHERE id = ?");
        $stmt->bind_param("i", $party_id);
        
        if ($stmt->execute()) {
            header("Location: " . $redirect_to);
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>