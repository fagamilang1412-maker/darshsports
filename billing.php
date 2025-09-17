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

// Get all orders for billing
$all_orders = [];
$result = $conn->query("SELECT id, party_name, mobile_number, delivery_date, fabric_type, collar_type, sleeve_type, sublimation_collar, sublimation_sleeve, status, payment_status, payment_method, amount_paid, total_amount, created_at FROM parties ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Get order count and calculate total for each party
        $party_id = $row['id'];
        $count_result = $conn->query("SELECT COUNT(*) as order_count FROM tshirt_orders WHERE party_id = $party_id");
        $count_data = $count_result->fetch_assoc();
        $row['order_count'] = $count_data['order_count'];
        
        // Calculate total amount if not already set
        $fabric_type = $row['fabric_type'];
        $price_per_unit = isset($fabric_prices[$fabric_type]) ? $fabric_prices[$fabric_type] : 0;
        
        // Add sublimation charges
        $sublimation_charges = 0;
        if ($row['sublimation_collar']) {
            $sublimation_charges += $sublimation_collar_charge;
        }
        if ($row['sublimation_sleeve']) {
            $sublimation_charges += $sublimation_sleeve_charge;
        }
        
        $row['price_per_unit'] = $price_per_unit;
        $row['sublimation_charges'] = $sublimation_charges;
        
        // Calculate total amount if not already set in database
        if ($row['total_amount'] == 0) {
            $row['total_amount'] = $row['order_count'] * ($price_per_unit + $sublimation_charges);
            
            // Update the database with calculated total
            $update_stmt = $conn->prepare("UPDATE parties SET total_amount = ? WHERE id = ?");
            $update_stmt->bind_param("di", $row['total_amount'], $party_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        // Calculate remaining amount
        $row['remaining_amount'] = $row['total_amount'] - $row['amount_paid'];
        
        $all_orders[] = $row;
    }
}

// Calculate grand total
$grand_total = 0;
$total_received = 0;
$total_remaining = 0;
foreach ($all_orders as $order) {
    $grand_total += $order['total_amount'];
    $total_received += $order['amount_paid'];
    $total_remaining += $order['remaining_amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billing - DARSH SPORTS</title>
<style>
  /* General Styles */
  body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f4f4f9;
  }
  h1, h2 {
    text-align: center;
    color: #333;
  }
  .container {
    max-width: 1800px;
    margin: 0 auto;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
  }
  th, td {
    padding: 12px;
    text-align: center;
    border: 1px solid #ccc;
  }
  th {
    background-color: #007bff;
    color: white;
  }
  tr:nth-child(even) {
    background-color: #e6f7ff;
  }
  .action-btn {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
  }
  .action-btn:hover {
    background-color: #0056b3;
  }
  .back-btn {
    background-color: #6c757d;
    margin-bottom: 20px;
  }
  .back-btn:hover {
    background-color: #5a6268;
  }
  .search-container {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }
  .search-container input, .search-container select {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    flex: 1;
    min-width: 150px;
  }
  .search-container button {
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  .no-orders {
    text-align: center;
    padding: 20px;
    color: #666;
  }
  .status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
  }
  .status-progress {
    background-color: #ff9800;
    color: white;
  }
  .status-ready {
    background-color: #2196f3;
    color: white;
  }
  .status-delivered {
    background-color: #4caf50;
    color: white;
  }
  .payment-status-badge {
    padding: 5px 10px;
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
    padding: 5px 10px;
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
  .grand-total {
    text-align: right;
    font-size: 18px;
    font-weight: bold;
    margin-top: 20px;
    padding: 10px;
    background-color: #e6f7ff;
    border-radius: 5px;
  }
  .price-list {
    margin-bottom: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
  }
  .price-list h3 {
    margin-top: 0;
  }
  .price-item {
    display: inline-block;
    margin-right: 15px;
    padding: 5px 10px;
    background-color: #e9ecef;
    border-radius: 3px;
  }
  .amount {
    font-weight: bold;
    color: #28a745;
  }
  .amount.remaining {
    color: #dc3545;
  }
  .sublimation-info {
    margin-top: 10px;
    padding: 10px;
    background-color: #fff3cd;
    border-radius: 5px;
    border-left: 4px solid #ffc107;
  }
  .payment-summary {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
  }
  .payment-summary-item {
    text-align: center;
  }
  .payment-summary-item h3 {
    margin: 0;
    font-size: 16px;
  }
  .payment-summary-item p {
    margin: 5px 0 0;
    font-size: 18px;
    font-weight: bold;
  }
  .update-payment-form {
    display: flex;
    gap: 5px;
    justify-content: center;
  }
  .update-payment-form input, .update-payment-form select {
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 3px;
    width: 80px;
  }
  .update-payment-btn {
    padding: 5px 10px;
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
  }
</style>
</head>
<body>
<h1>DARSH SPORTS</h1>
<h2>Billing System</h2>

<a href="index.php" class="action-btn back-btn">Back to Order System</a>
<a href="history.php" class="action-btn back-btn">View Order History</a>


  
  <div class="payment-summary">
    <div class="payment-summary-item">
      <h3>Grand Total</h3>
      <p>₹<?php echo $grand_total; ?>/-</p>
    </div>
    <div class="payment-summary-item">
      <h3>Total Received</h3>
      <p>₹<?php echo $total_received; ?>/-</p>
    </div>
    <div class="payment-summary-item">
      <h3>Total Remaining</h3>
      <p class="amount remaining">₹<?php echo $total_remaining; ?>/-</p>
    </div>
  </div>
  
  <div class="search-container">
    <input type="text" id="searchInput" placeholder="Search by party name, mobile number, or fabric type...">
    <select id="statusFilter">
      <option value="">All Statuses</option>
      <option value="progress">In Progress</option>
      <option value="ready">Ready</option>
      <option value="delivered">Delivered</option>
    </select>
    <select id="paymentStatusFilter">
      <option value="">All Payment Statuses</option>
      <option value="Unpaid">Unpaid</option>
      <option value="Remain">Remain</option>
      <option value="Fully Paid">Fully Paid</option>
    </select>
    <button onclick="searchOrders()">Search</button>
  </div>
  
  <table id="ordersTable">
    <thead>
      <tr>
        <th>Order ID</th>
        <th>Party Name</th>
        <th>Mobile Number</th>
        <th>Delivery Date</th>
        <th>Fabric Type</th>
        <th>Base Price</th>
        <th>Sublimation Charges</th>
        <th>Final Price/Unit</th>
        <th>Quantity</th>
        <th>Total Amount</th>
        <th>Amount Paid</th>
        <th>Remaining Amount</th>
        <th>Payment Status</th>
        <th>Payment Method</th>
        <th>Order Status</th>
        <th>Created At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($all_orders)): ?>
        <?php foreach ($all_orders as $order): ?>
          <tr>
            <td><?php echo $order['id']; ?></td>
            <td><?php echo $order['party_name']; ?></td>
            <td><?php echo !empty($order['mobile_number']) ? $order['mobile_number'] : 'N/A'; ?></td>
            <td><?php echo date('d-m-Y', strtotime($order['delivery_date'])); ?></td>
            <td><?php echo $order['fabric_type']; ?></td>
            <td>₹<?php echo $order['price_per_unit']; ?>/-</td>
            <td>
              <?php 
              $sublimation_text = [];
              if ($order['sublimation_collar']) {
                  $sublimation_text[] = "Collar: +₹{$sublimation_collar_charge}/-";
              }
              if ($order['sublimation_sleeve']) {
                  $sublimation_text[] = "Sleeve: +₹{$sublimation_sleeve_charge}/-";
              }
              echo !empty($sublimation_text) ? implode('<br>', $sublimation_text) : 'None';
              ?>
            </td>
            <td>₹<?php echo ($order['price_per_unit'] + $order['sublimation_charges']); ?>/-</td>
            <td><?php echo $order['order_count']; ?></td>
            <td class="amount">₹<?php echo $order['total_amount']; ?>/-</td>
            <td class="amount">₹<?php echo $order['amount_paid']; ?>/-</td>
            <td class="amount <?php echo $order['remaining_amount'] > 0 ? 'remaining' : ''; ?>">₹<?php echo $order['remaining_amount']; ?>/-</td>
            <td>
              <span class="payment-status-badge payment-<?php echo strtolower(str_replace(' ', '-', $order['payment_status'])); ?>">
                <?php echo $order['payment_status']; ?>
              </span>
            </td>
            <td>
              <?php if ($order['payment_method']): ?>
                <span class="payment-method-badge payment-<?php echo strtolower($order['payment_method']); ?>">
                  <?php echo $order['payment_method']; ?>
                </span>
              <?php else: ?>
                N/A
              <?php endif; ?>
            </td>
            <td>
              <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                <?php echo strtoupper($order['status']); ?>
              </span>
            </td>
            <td><?php echo date('d-m-Y H:i', strtotime($order['created_at'])); ?></td>
            <td>
              <a href="index.php?party_id=<?php echo $order['id']; ?>" class="action-btn">View/Edit</a>
              <a href="bill_details.php?party_id=<?php echo $order['id']; ?>" class="action-btn" style="background-color: #28a745;">View Bill</a>
              <br><br>
              <form class="update-payment-form" action="update_payment.php" method="POST">
                <input type="hidden" name="party_id" value="<?php echo $order['id']; ?>">
                <input type="number" name="amount_paid" placeholder="Amount" min="0" max="<?php echo $order['total_amount']; ?>" step="0.01" value="<?php echo $order['amount_paid']; ?>">
                <select name="payment_method">
                  <option value="">Select Method</option>
                  <option value="Cash" <?php echo $order['payment_method'] == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                  <option value="Online" <?php echo $order['payment_method'] == 'Online' ? 'selected' : ''; ?>>Online</option>
                </select>
                <button type="submit" class="update-payment-btn">Update</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="17" class="no-orders">No orders found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function searchOrders() {
  const input = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const paymentStatusFilter = document.getElementById('paymentStatusFilter');
  const filter = input.value.toUpperCase();
  const statusValue = statusFilter.value.toUpperCase();
  const paymentStatusValue = paymentStatusFilter.value.toUpperCase();
  const table = document.getElementById('ordersTable');
  const tr = table.getElementsByTagName('tr');
  
  // Loop through all table rows, and hide those that don't match the search query
  for (let i = 1; i < tr.length; i++) {
    let found = false;
    let statusMatch = false;
    let paymentStatusMatch = false;
    const td = tr[i].getElementsByTagName('td');
    
    // Text search
    for (let j = 0; j < td.length; j++) {
      if (td[j]) {
        const txtValue = td[j].textContent || td[j].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
          found = true;
          break;
        }
      }
    }
    
    // Status filter
    if (statusValue) {
      // Order status is in the 15th column (index 14)
      const statusTd = td[14];
      if (statusTd) {
        const statusText = statusTd.textContent || statusTd.innerText;
        if (statusText.toUpperCase().indexOf(statusValue) > -1) {
          statusMatch = true;
        }
      }
    } else {
      statusMatch = true;
    }
    
    // Payment status filter
    if (paymentStatusValue) {
      // Payment status is in the 13th column (index 12)
      const paymentStatusTd = td[12];
      if (paymentStatusTd) {
        const paymentStatusText = paymentStatusTd.textContent || paymentStatusTd.innerText;
        if (paymentStatusText.toUpperCase().indexOf(paymentStatusValue) > -1) {
          paymentStatusMatch = true;
        }
      }
    } else {
      paymentStatusMatch = true;
    }
    
    if (found && statusMatch && paymentStatusMatch) {
      tr[i].style.display = "";
    } else {
      tr[i].style.display = "none";
    }
  }
}

// Add event listener for Enter key in search input
document.getElementById('searchInput').addEventListener('keyup', function(event) {
  if (event.key === 'Enter') {
    searchOrders();
  }
});

// Add event listener for status filter change
document.getElementById('statusFilter').addEventListener('change', function() {
  searchOrders();
});

// Add event listener for payment status filter change
document.getElementById('paymentStatusFilter').addEventListener('change', function() {
  searchOrders();
});
</script>
</body>
</html>
<?php
$conn->close();
?>