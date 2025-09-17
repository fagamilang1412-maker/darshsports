<?php
include 'config.php';
session_start();

// Define fabric prices
$fabric_prices = [
    'MICRO' => 210,
    'SOFTY' => 220,
    'DOTNET' => 230,
    'COMBOLINE' => 240,
    'REBOOKNET' => 250,
    'ROLEX' => 260,
    'SUPPERPOLY' => 270,
    'JACQUARD' => 280
];

// Define sublimation charges
$sublimation_collar_charge = 10;
$sublimation_sleeve_charge = 40;

// Get party ID from URL
$party_id = isset($_GET['party_id']) ? intval($_GET['party_id']) : 0;

if ($party_id > 0) {
    // Load party details
    $stmt = $conn->prepare("SELECT * FROM parties WHERE id = ?");
    $stmt->bind_param("i", $party_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $party_details = $result->fetch_assoc();
    $stmt->close();
    
    // Load t-shirt entries
    $stmt = $conn->prepare("SELECT * FROM tshirt_orders WHERE party_id = ? ORDER BY id");
    $stmt->bind_param("i", $party_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tshirt_entries = [];
    while ($row = $result->fetch_assoc()) {
        $tshirt_entries[] = $row;
    }
    $stmt->close();
    
    // Calculate price per unit with sublimation charges
    $fabric_type = $party_details['fabric_type'];
    $price_per_unit = isset($fabric_prices[$fabric_type]) ? $fabric_prices[$fabric_type] : 0;
    
    // Add sublimation charges
    $sublimation_charges = 0;
    if ($party_details['sublimation_collar']) {
        $sublimation_charges += $sublimation_collar_charge;
    }
    if ($party_details['sublimation_sleeve']) {
        $sublimation_charges += $sublimation_sleeve_charge;
    }
    
    $final_price_per_unit = $price_per_unit + $sublimation_charges;
    
    // Calculate total amount if not already set in database
    if ($party_details['total_amount'] == 0) {
        $total_amount = count($tshirt_entries) * $final_price_per_unit;
        
        // Update the database with calculated total
        $update_stmt = $conn->prepare("UPDATE parties SET total_amount = ? WHERE id = ?");
        $update_stmt->bind_param("di", $total_amount, $party_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        $total_amount = $party_details['total_amount'];
    }
    
    // Calculate remaining amount
    $remaining_amount = $total_amount - $party_details['amount_paid'];
} else {
    header("Location: billing.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bill Details - DARSH SPORTS</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', Arial, sans-serif;
  }
  
  body {
    background-color: #f4f4f9;
    padding: 20px;
    color: #333;
  }
  
  .container {
    max-width: 1000px;
    margin: 0 auto;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  }
  
  .header {
    text-align: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #007bff;
  }
  
  .header h1 {
    color: #007bff;
    font-size: 32px;
    margin-bottom: 5px;
  }
  
  .header h2 {
    color: #555;
    font-size: 22px;
    font-weight: 500;
  }
  
  .bill-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
  }
  
  .bill-info, .shop-info {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
  }
  
  .shop-info {
    background-color: #e6f7ff;
    border-left: 4px solid #007bff;
  }
  
  .info-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #007bff;
    display: flex;
    align-items: center;
  }
  
  .info-title i {
    margin-right: 8px;
  }
  
  .info-item {
    margin-bottom: 8px;
    display: flex;
  }
  
  .info-label {
    flex: 1;
    font-weight: 500;
  }
  
  .info-value {
    flex: 2;
  }
  
  .pricing-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
  }
  
  .price-details, .payment-details {
    padding: 15px;
    border-radius: 8px;
  }
  
  .price-details {
    background-color: #f8f9fa;
  }
  
  .payment-details {
    background-color: #e6f7ff;
  }
  
  .sublimation-info {
    margin-top: 10px;
    padding: 10px;
    background-color: #fff3cd;
    border-radius: 5px;
    border-left: 4px solid #ffc107;
  }
  
  .price-item {
    margin-bottom: 8px;
    display: flex;
  }
  
  .price-label {
    flex: 1;
    font-weight: 500;
  }
  
  .price-value {
    flex: 1;
    text-align: right;
  }
  
  .divider {
    height: 1px;
    background-color: #ddd;
    margin: 10px 0;
  }
  
  .total-amount {
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    margin: 25px 0;
    padding: 15px;
    background: linear-gradient(to right, #007bff, #0056b3);
    color: white;
    border-radius: 8px;
  }
  
  .action-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 20px;
    flex-wrap: wrap;
  }
  
  .action-btn {
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    transition: all 0.3s;
    text-decoration: none;
  }
  
  .action-btn i {
    margin-right: 8px;
  }
  
  .back-btn {
    background-color: #6c757d;
    color: white;
  }
  
  .back-btn:hover {
    background-color: #5a6268;
  }
  
  .print-btn {
    background-color: #28a745;
    color: white;
  }
  
  .print-btn:hover {
    background-color: #218838;
  }
  
  .pdf-btn {
    background-color: #dc3545;
    color: white;
  }
  
  .pdf-btn:hover {
    background-color: #c82333;
  }
  
  .whatsapp-btn {
    background-color: #25D366;
    color: white;
  }
  
  .whatsapp-btn:hover {
    background-color: #128C7E;
  }
  
  .payment-status-badge {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
  }
  
  .payment-unpaid {
    background-color: #dc3545;
    color: white;
  }
  
  .payment-remain {
    background-color: #ffc107;
    color: black;
  }
  
  .payment-fully-paid {
    background-color: #28a745;
    color: white;
  }
  
  .payment-method-badge {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
  }
  
  .payment-cash {
    background-color: #17a2b8;
    color: white;
  }
  
  .payment-online {
    background-color: #6f42c1;
    color: white;
  }
  
  .amount.remaining {
    color: #dc3545;
    font-weight: 600;
  }
  
  @media print {
    body {
      padding: 0;
      background-color: white;
    }
    
    .container {
      box-shadow: none;
      padding: 15px;
    }
    
    .action-buttons {
      display: none;
    }
  }
  
  @media (max-width: 768px) {
    .bill-container, .pricing-section {
      grid-template-columns: 1fr;
    }
    
    .action-buttons {
      flex-direction: column;
      align-items: center;
    }
    
    .action-btn {
      width: 100%;
      justify-content: center;
    }
  }
</style>
</head>
<body>
<div class="container" id="bill-content">
  <div class="header">
    <h1>DARSH SPORTS</h1>
    <h2>Bill Details</h2>
  </div>
  <div class="bill-container">
    <div class="bill-info">
      <div class="info-title">
        <i class="fas fa-user"></i> Party Information
      </div>
      <div class="info-item">
        <span class="info-label">Order ID:</span>
        <span class="info-value"><?php echo $party_details['id']; ?></span>
      </div>
      <div class="info-item">
        <span class="info-label">Party Name:</span>
        <span class="info-value"><?php echo $party_details['party_name']; ?></span>
      </div>
      <div class="info-item">
        <span class="info-label">Mobile Number:</span>
        <span class="info-value"><?php echo !empty($party_details['mobile_number']) ? $party_details['mobile_number'] : 'N/A'; ?></span>
      </div>
      <div class="info-item">
        <span class="info-label">Delivery Date:</span>
        <span class="info-value"><?php echo date('d-m-Y', strtotime($party_details['delivery_date'])); ?></span>
      </div>
      <div class="info-item">
        <span class="info-label">Fabric Type:</span>
        <span class="info-value"><?php echo $party_details['fabric_type']; ?></span>
      </div>
    </div>
    <div class="shop-info">
      <div class="info-title">
        <i class="fas fa-store"></i> Shop Information
      </div>
      <div class="info-item">
        <span class="info-label">Proprietor:</span>
        <span class="info-value">Kuldeep Ahir</span>
      </div>
      <div class="info-item">
        <span class="info-label">Mobile:</span>
        <span class="info-value">9913992847</span>
      </div>
      <div class="info-item">
        <span class="info-label">Address:</span>
        <span class="info-value">At Darsh Sports, Opp sorathiya samaj wadi</span>
      </div>
      <div class="info-item">
        <span class="info-label">City:</span>
        <span class="info-value">madhapar bhuj-370020</span>
      </div>
    </div>
  </div>
  <div class="pricing-section">
    <div class="price-details">
      <div class="info-title">
        <i class="fas fa-tag"></i> Pricing Details
      </div>
      <div class="price-item">
        <span class="price-label">Base Price:</span>
        <span class="price-value">₹<?php echo $price_per_unit; ?>/-</span>
      </div>
      <?php if ($party_details['sublimation_collar'] || $party_details['sublimation_sleeve']): ?>
      <div class="sublimation-info">
        <div class="info-title">
          <i class="fas fa-plus-circle"></i> Sublimation Charges
        </div>
        <?php if ($party_details['sublimation_collar']): ?>
        <div class="price-item">
          <span class="price-label">Collar:</span>
          <span class="price-value">+₹<?php echo $sublimation_collar_charge; ?>/-</span>
        </div>
        <?php endif; ?>
        <?php if ($party_details['sublimation_sleeve']): ?>
        <div class="price-item">
          <span class="price-label">Sleeve:</span>
          <span class="price-value">+₹<?php echo $sublimation_sleeve_charge; ?>/-</span>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <div class="divider"></div>
      <div class="price-item">
        <span class="price-label">Final Price per Unit:</span>
        <span class="price-value">₹<?php echo $final_price_per_unit; ?>/-</span>
      </div>
      <div class="price-item">
        <span class="price-label">Quantity:</span>
        <span class="price-value"><?php echo count($tshirt_entries); ?></span>
      </div>
    </div>
    <div class="payment-details">
      <div class="info-title">
        <i class="fas fa-credit-card"></i> Payment Details
      </div>
      <div class="price-item">
        <span class="price-label">Total Amount:</span>
        <span class="price-value">₹<?php echo $total_amount; ?>/-</span>
      </div>
      <div class="price-item">
        <span class="price-label">Amount Paid:</span>
        <span class="price-value">₹<?php echo $party_details['amount_paid']; ?>/-</span>
      </div>
      <div class="price-item">
        <span class="price-label">Remaining Amount:</span>
        <span class="price-value amount remaining">₹<?php echo $remaining_amount; ?>/-</span>
      </div>
      <div class="divider"></div>
      <div class="price-item">
        <span class="price-label">Payment Status:</span>
        <span class="price-value">
          <span class="payment-status-badge payment-<?php echo strtolower(str_replace(' ', '-', $party_details['payment_status'])); ?>">
            <?php echo $party_details['payment_status']; ?>
          </span>
        </span>
      </div>
      <div class="price-item">
        <span class="price-label">Payment Method:</span>
        <span class="price-value">
          <?php if ($party_details['payment_method']): ?>
            <span class="payment-method-badge payment-<?php echo strtolower($party_details['payment_method']); ?>">
              <?php echo $party_details['payment_method']; ?>
            </span>
          <?php else: ?>
            N/A
          <?php endif; ?>
        </span>
      </div>
    </div>
  </div>
  <div class="total-amount">
    Total Amount: ₹<?php echo $total_amount; ?>/-
  </div>
  
  <div class="action-buttons">
    <a href="billing.php" class="action-btn back-btn">
      <i class="fas fa-arrow-left"></i> Back to Billing
    </a>
    <button onclick="window.print()" class="action-btn print-btn">
      <i class="fas fa-print"></i> Print Bill
    </button>
    <button id="download-pdf" class="action-btn pdf-btn">
      <i class="fas fa-file-pdf"></i> Download PDF
    </button>
    <button id="share-whatsapp" class="action-btn whatsapp-btn">
      <i class="fab fa-whatsapp"></i> Share via WhatsApp
    </button>
  </div>
</div>

<script>
  document.getElementById('download-pdf').addEventListener('click', function() {
    const element = document.getElementById('bill-content');
    const opt = {
      margin: 10,
      filename: 'darsh_sports_bill.pdf',
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2 },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(element).save();
  });

  // WhatsApp sharing functionality
  document.getElementById('share-whatsapp').addEventListener('click', function() {
    // First generate the PDF
    const element = document.getElementById('bill-content');
    const opt = {
      margin: 10,
      filename: 'darsh_sports_bill.pdf',
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2 },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(element).outputPdf('blob').then(function(pdfBlob) {
      // Create a FormData object to send the PDF to our server
      const formData = new FormData();
      formData.append('pdf', pdfBlob, 'darsh_sports_bill.pdf');
      formData.append('party_id', '<?php echo $party_id; ?>');
      formData.append('mobile_number', '<?php echo $party_details["mobile_number"]; ?>');
      
      // Show a loading message
      const originalText = document.getElementById('share-whatsapp').innerHTML;
      document.getElementById('share-whatsapp').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
      
      // Send the PDF to the server for WhatsApp sharing
      fetch('share_whatsapp.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Open WhatsApp with the phone number pre-filled
          const phoneNumber = '<?php echo $party_details["mobile_number"]; ?>';
          const message = encodeURIComponent('Hello! Here is your bill from DARSH SPORTS. Thank you for your business!');
          window.open(`https://wa.me/${phoneNumber}?text=${message}`, '_blank');
        } else {
          alert('Error: ' + data.message);
        }
        // Restore button text
        document.getElementById('share-whatsapp').innerHTML = originalText;
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while preparing the WhatsApp share.');
        document.getElementById('share-whatsapp').innerHTML = originalText;
      });
    });
  });
</script>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>