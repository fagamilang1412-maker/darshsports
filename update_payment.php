<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $party_id = isset($_POST['party_id']) ? intval($_POST['party_id']) : 0;
    $amount_paid = isset($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : null;
    
    if ($party_id > 0) {
        // Get total amount
        $stmt = $conn->prepare("SELECT total_amount FROM parties WHERE id = ?");
        $stmt->bind_param("i", $party_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $party = $result->fetch_assoc();
        $stmt->close();
        
        $total_amount = $party['total_amount'];
        
        // Determine payment status
        if ($amount_paid == 0) {
            $payment_status = 'Unpaid';
        } elseif ($amount_paid < $total_amount) {
            $payment_status = 'Remain';
        } else {
            $payment_status = 'Fully Paid';
        }
        
        // Update payment information
        $stmt = $conn->prepare("UPDATE parties SET amount_paid = ?, payment_method = ?, payment_status = ? WHERE id = ?");
        $stmt->bind_param("dssi", $amount_paid, $payment_method, $payment_status, $party_id);
        
        if ($stmt->execute()) {
            header("Location: billing.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>