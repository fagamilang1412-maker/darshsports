<?php
include 'config.php';
session_start();

// Get all orders for history
$all_orders = [];
$result = $conn->query("SELECT id, party_name, mobile_number, delivery_date, fabric_type, collar_type, sleeve_type, status, created_at FROM parties ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Get order count for each party
        $party_id = $row['id'];
        $count_result = $conn->query("SELECT COUNT(*) as order_count FROM tshirt_orders WHERE party_id = $party_id");
        $count_data = $count_result->fetch_assoc();
        $row['order_count'] = $count_data['order_count'];
        $all_orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order History - DARSH SPORTS</title>
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
    max-width: 1200px;
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
  .delete-btn {
    background-color: #dc3545;
  }
  .delete-btn:hover {
    background-color: #c82333;
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
  .update-status-form {
    display: inline;
    margin-left: 10px;
  }
  .update-status-form select {
    padding: 5px;
    border-radius: 3px;
    margin-right: 5px;
  }
  .update-status-btn {
    padding: 5px 10px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
  }
</style>
</head>
<body>
<h1>DARSH SPORTS</h1>
<h2>Order History</h2>

<a href="index.php" class="action-btn back-btn">Back to Order System</a>

<div class="container">
  <div class="search-container">
    <input type="text" id="searchInput" placeholder="Search by party name, mobile number, or fabric type...">
    <select id="statusFilter">
      <option value="">All Statuses</option>
      <option value="progress">In Progress</option>
      <option value="ready">Ready</option>
      <option value="delivered">Delivered</option>
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
        <th>Collar Type</th>
        <th>Sleeve Type</th>
        <th>Status</th>
        <th>Order Count</th>
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
            <td><?php echo $order['collar_type']; ?></td>
            <td><?php echo $order['sleeve_type']; ?></td>
            <td>
              <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                <?php echo strtoupper($order['status']); ?>
              </span>
              <form class="update-status-form" action="update_status.php" method="POST">
                <input type="hidden" name="partyId" value="<?php echo $order['id']; ?>">
                <select name="status">
                  <option value="progress" <?php echo ($order['status'] == 'progress') ? 'selected' : ''; ?>>Progress</option>
                  <option value="ready" <?php echo ($order['status'] == 'ready') ? 'selected' : ''; ?>>Ready</option>
                  <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                </select>
                <button type="submit" class="update-status-btn">Update</button>
              </form>
            </td>
            <td><?php echo $order['order_count']; ?></td>
            <td><?php echo date('d-m-Y H:i', strtotime($order['created_at'])); ?></td>
            <td>
              <a href="index.php?party_id=<?php echo $order['id']; ?>" class="action-btn">View/Edit</a>
              <form action="delete_party.php" method="POST" style="display:inline;">
                <input type="hidden" name="partyId" value="<?php echo $order['id']; ?>">
                <input type="hidden" name="redirectTo" value="history.php">
                <button type="submit" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this order?')">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="11" class="no-orders">No orders found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function searchOrders() {
  const input = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const filter = input.value.toUpperCase();
  const statusValue = statusFilter.value.toUpperCase();
  const table = document.getElementById('ordersTable');
  const tr = table.getElementsByTagName('tr');

  // Loop through all table rows, and hide those that don't match the search query
  for (let i = 1; i < tr.length; i++) {
    let found = false;
    let statusMatch = false;
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
      // Status is in the 8th column (index 7)
      const statusTd = td[7];
      if (statusTd) {
        // Get the status badge span inside the statusTd
        const badge = statusTd.querySelector('.status-badge');
        const statusText = badge ? badge.textContent.trim().toUpperCase() : '';
        if (statusText.indexOf(statusValue) > -1) {
          statusMatch = true;
        }
      }
    } else {
      statusMatch = true;
    }

    if (found && statusMatch) {
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
</script>
</body>
</html>
<?php
$conn->close();
?>