<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $party_id = isset($_POST['partyId']) ? intval($_POST['partyId']) : 0;
    $player_name = $_POST['playerName'];
    $t_no = $_POST['tNo'];
    $size = $_POST['tShirtSize'];
    
    // Get sleeve type from form or from party details
    if (isset($_POST['sleeveType']) && !empty($_POST['sleeveType'])) {
        $sleeve_type = $_POST['sleeveType'];
    } else {
        // Get sleeve type from party details
        $stmt = $conn->prepare("SELECT sleeve_type FROM parties WHERE id = ?");
        $stmt->bind_param("i", $party_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $party = $result->fetch_assoc();
            $sleeve_type = $party['sleeve_type'];
        } else {
            $sleeve_type = "HALF SLEEVE"; // Default value
        }
        $stmt->close();
    }
    
    if ($party_id > 0) {
        $stmt = $conn->prepare("INSERT INTO tshirt_orders (party_id, player_name, t_no, size, sleeve_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $party_id, $player_name, $t_no, $size, $sleeve_type);
        
        if ($stmt->execute()) {
            header("Location: index.php?party_id=" . $party_id);
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        echo "Error: No party selected";
    }
}

$conn->close();
?>