<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $party_id = isset($_POST['partyId']) ? intval($_POST['partyId']) : 0;
    $party_name = $_POST['partyName'];
    $mobile_number = $_POST['mobileNumber'];
    $delivery_date = $_POST['deliveryDate'];
    $fabric_type = $_POST['fabricType'];
    $collar_type = $_POST['collarType'];
    $sleeve_type = $_POST['partySleeveType'];
    $sublimation_collar = isset($_POST['sublimationCollar']) ? 1 : 0;
    $sublimation_sleeve = isset($_POST['sublimationSleeve']) ? 1 : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'progress';
    
    // Handle file upload
    $party_photo = null;
    if (isset($_FILES['partyPhoto']) && $_FILES['partyPhoto']['error'] == UPLOAD_ERR_OK) {
        $party_photo = file_get_contents($_FILES['partyPhoto']['tmp_name']);
    }
    
    if ($party_id > 0) {
        // Update existing party
        if ($party_photo) {
            $stmt = $conn->prepare("UPDATE parties SET party_name=?, mobile_number=?, delivery_date=?, fabric_type=?, collar_type=?, sleeve_type=?, sublimation_collar=?, sublimation_sleeve=?, party_photo=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssiisss", $party_name, $mobile_number, $delivery_date, $fabric_type, $collar_type, $sleeve_type, $sublimation_collar, $sublimation_sleeve, $party_photo, $status, $party_id);
        } else {
            $stmt = $conn->prepare("UPDATE parties SET party_name=?, mobile_number=?, delivery_date=?, fabric_type=?, collar_type=?, sleeve_type=?, sublimation_collar=?, sublimation_sleeve=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssiiss", $party_name, $mobile_number, $delivery_date, $fabric_type, $collar_type, $sleeve_type, $sublimation_collar, $sublimation_sleeve, $status, $party_id);
        }
    } else {
        // Insert new party
        if ($party_photo) {
            $stmt = $conn->prepare("INSERT INTO parties (party_name, mobile_number, delivery_date, fabric_type, collar_type, sleeve_type, sublimation_collar, sublimation_sleeve, party_photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiiss", $party_name, $mobile_number, $delivery_date, $fabric_type, $collar_type, $sleeve_type, $sublimation_collar, $sublimation_sleeve, $party_photo, $status);
        } else {
            $stmt = $conn->prepare("INSERT INTO parties (party_name, mobile_number, delivery_date, fabric_type, collar_type, sleeve_type, sublimation_collar, sublimation_sleeve, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiis", $party_name, $mobile_number, $delivery_date, $fabric_type, $collar_type, $sleeve_type, $sublimation_collar, $sublimation_sleeve, $status);
        }
    }
    
    if ($stmt->execute()) {
        if ($party_id == 0) {
            $party_id = $stmt->insert_id;
        }
        $_SESSION['party_id'] = $party_id;
        header("Location: index.php?party_id=" . $party_id);
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

$conn->close();
?>