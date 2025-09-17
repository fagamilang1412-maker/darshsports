<?php
include 'config.php';
session_start();

// Check if we're editing an existing party
$party_id = isset($_GET['party_id']) ? intval($_GET['party_id']) : 0;
$party_details = [];
$tshirt_entries = [];

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
    while ($row = $result->fetch_assoc()) {
        $tshirt_entries[] = $row;
    }
    $stmt->close();
    
    // Store in session for later use
    $_SESSION['party_id'] = $party_id;
}

// Get all orders for history
$all_orders = [];
$result = $conn->query("SELECT id, party_name, mobile_number, delivery_date, created_at FROM parties ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>T-Shirt Order System</title>
<div style="text-align: center; margin-bottom: 20px; display: flex; justify-content: center; gap: 10px;">
  <a href="history.php" class="action-btn" style="background-color: #6c757d;">View Order History</a>
  <a href="billing.php" class="action-btn" style="background-color: #28a745;">Billing</a>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
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
  .action-btn {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
.action-btn:hover {
    background-color: #0056b3;
}
  .container {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
  }
  form {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  label {
    font-weight: bold;
  }
  input, select, button, .checkbox-group {
    padding: 10px;
    font-size: 1rem;
    border: 1px solid #ccc;
    border-radius: 5px;
  }
  button {
    background-color: #007bff;
    color: white;
    border: none;
    cursor: pointer;
  }
  button:hover {
    background-color: #0056b3;
  }
  #whatsappShare {
    background-color: #25D366;
  }
  #whatsappShare:hover {
    background-color: #1da851;
  }
  #downloadPDF {
    background-color: #6c757d;
  }
  #downloadPDF:hover {
    background-color: #5a6268;
  }
  #partyPhotoPreview {
    max-width: 200px;
    height: auto;
    margin-top: 10px;
    border-radius: 5px;
  }
  .summary-totals {
    margin-top: 20px;
    font-weight: bold;
    text-align: center;
  }
  .photo-preview {
    max-width: 150px;
    height: auto;
    border-radius: 5px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
  }
  th, td {
    padding: 10px;
    text-align: center;
    border: 1px solid #ccc;
  }
  th {
    background-color: #f4f4f9;
  }
  tr:nth-child(even) {
    background-color: #e6f7ff;
  }
  .delete-btn {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
  }
  .delete-btn:hover {
    background-color: #c82333;
  }
  .checkbox-group {
    display: flex;
    gap: 10px;
    align-items: center;
  }
  #newOrder {
    background-color: #28a745;
    margin-top: 10px;
  }
  #newOrder:hover {
    background-color: #218838;
  }
  button:disabled {
    background-color: #ccc;
    cursor: not-allowed;
  }
  .message {
    color: red;
    text-align: center;
    margin-top: 10px;
    font-weight: bold;
  }
  .collapsible {
    cursor: pointer;
    padding: 10px;
    width: 100%;
    border: none;
    text-align: left;
    outline: none;
    font-size: 1rem;
    background-color: #007bff;
    color: white;
    border-radius: 5px;
    margin-bottom: 10px;
  }
  .collapsible:hover {
    background-color: #0056b3;
  }
  .content {
    padding: 0 18px;
    overflow: hidden;
    background-color: #f9f9f9;
    border-radius: 5px;
  }
  .sleeve-type-indicator {
    font-weight: bold;
    color: #007bff;
    margin-left: 10px;
  }
  .button-group {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
  }
  .button-group button {
    flex: 1;
  }
</style>
</head>
<body>
<h1>DARSH SPORTS</h1><h2> T-Shirt Order System</h2>


<div class="container">
  <button type="button" class="collapsible">Party Details</button>
  <div class="content" id="partyDetailsContent">
    <h2>Party Details</h2>
    <form id="partyDetailsForm" action="save_party.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" id="partyId" name="partyId" value="<?php echo $party_id; ?>">
      <label for="partyName">Party Name:</label>
      <input type="text" id="partyName" name="partyName" placeholder="Enter party name" value="<?php echo isset($party_details['party_name']) ? $party_details['party_name'] : ''; ?>" required>
      <label for="mobileNumber">Mobile Number:</label>
      <input type="text" id="mobileNumber" name="mobileNumber" placeholder="Enter mobile number" value="<?php echo isset($party_details['mobile_number']) ? $party_details['mobile_number'] : ''; ?>" required>
      <label for="deliveryDate">Delivery Date:</label>
      <input type="date" id="deliveryDate" name="deliveryDate" value="<?php echo isset($party_details['delivery_date']) ? $party_details['delivery_date'] : ''; ?>" required>
      <label for="fabricType">Fabric Type:</label>
      <select id="fabricType" name="fabricType" required>
        <option value="" disabled selected>Select fabric type</option>
        <option value="MICRO" <?php echo (isset($party_details['fabric_type']) && $party_details['fabric_type'] == 'MICRO') ? 'selected' : ''; ?>>Micro</option>
        <option value="SOFTY" <?php echo (isset($party_details['fabric_type']) && $party_details['fabric_type'] == 'SOFTY') ? 'selected' : ''; ?>>Softy</option>
        <option value="DOTNET" <?php echo (isset($party_details['fabric_type']) && $party_details['fabric_type'] == 'DOTNET') ? 'selected' : ''; ?>>Dotnet</option>
        <option value="COMBOLINE" <?php echo (isset($party_details['fabric_type']) && $party_details['fabric_type'] == 'COMBOLINE') ? 'selected' : ''; ?>>Comboline</option>
        <option value="REBOOKNET" <?php echo (isset($party_details['fabric_type']) && $party_details['fabric_type'] == 'REBOOKNET') ? 'selected' : ''; ?>>Rebooknet</option>
        <option value="ROLEX" <?php echo (isset($party_details['fabric_type']) && $party_details['fabric_type'] == 'ROLEX') ? 'selected' : ''; ?>>Rolex</option>
        <option value="SUPPERPOLY" <?php echo (isset($party_details['fabric_type']) && $party_details['fabric_type'] == 'SUPPERPOLY') ? 'selected' : ''; ?>>Supperpoly</option>
        <option value="JACQUARD" <?php echo (isset($party_details['fabric_type']) && $party_details['fabric_type'] == 'JACQUARD') ? 'selected' : ''; ?>>Jacquard</option>
      </select>
      <label for="collarType">Collar Type:</label>
      <select id="collarType" name="collarType" required>
        <option value="" disabled selected>Select collar type</option>
        <option value="COLLAR BUTTON PREMIUM" <?php echo (isset($party_details['collar_type']) && $party_details['collar_type'] == 'COLLAR BUTTON PREMIUM') ? 'selected' : ''; ?>>COLLAR BUTTON PREMIUM</option>
        <option value="V NECK COLLAR" <?php echo (isset($party_details['collar_type']) && $party_details['collar_type'] == 'V NECK COLLAR') ? 'selected' : ''; ?>>V NECK COLLAR</option>
        <option value="ROUND NECK" <?php echo (isset($party_details['collar_type']) && $party_details['collar_type'] == 'ROUND NECK') ? 'selected' : ''; ?>>ROUND NECK</option>
        <option value="SENDO SLEEVELESS" <?php echo (isset($party_details['collar_type']) && $party_details['collar_type'] == 'SENDO SLEEVELESS') ? 'selected' : ''; ?>>SENDO SLEEVELESS</option>
        <option value="CHINESE STAND PATTI CHAIN" <?php echo (isset($party_details['collar_type']) && $party_details['collar_type'] == 'CHINESE STAND PATTI CHAIN') ? 'selected' : ''; ?>>CHINESE STAND PATTI + CHAIN</option>
        <option value="CHINESE TUKDI COLLAR" <?php echo (isset($party_details['collar_type']) && $party_details['collar_type'] == 'CHINESE TUKDI COLLAR') ? 'selected' : ''; ?>>CHINESE TUKDI COLLAR</option>
        <option value="CHINESE COLLAR BUTTON" <?php echo (isset($party_details['collar_type']) && $party_details['collar_type'] == 'CHINESE COLLAR BUTTON') ? 'selected' : ''; ?>>CHINESE STAND COLLAR + BUTTON</option>
        <option value="ZIP + COLLAR" <?php echo (isset($party_details['collar_type']) && $party_details['collar_type'] == 'ZIP + COLLAR') ? 'selected' : ''; ?>>ZIP + COLLAR</option>
        <option value="STAND PATTI + BUTTON" <?php echo (isset($party_details['collar_type']) && $party_details['collar_type'] == 'STAND PATTI + BUTTON') ? 'selected' : ''; ?>>STAND PATTI + BUTTON</option>
      </select>
      <label for="partySleeveType">Sleeve Type:</label>
      <select id="partySleeveType" name="partySleeveType" required>
        <option value="" disabled selected>Select sleeve type</option>
        <option value="HALF SLEEVE" <?php echo (isset($party_details['sleeve_type']) && $party_details['sleeve_type'] == 'HALF SLEEVE') ? 'selected' : ''; ?>>ALL HALF SLEEVE</option>
        <option value="FULL SLEEVE" <?php echo (isset($party_details['sleeve_type']) && $party_details['sleeve_type'] == 'FULL SLEEVE') ? 'selected' : ''; ?>>ALL FULL SLEEVE</option>
        <option value="MIX SLEEVE" <?php echo (isset($party_details['sleeve_type']) && $party_details['sleeve_type'] == 'MIX SLEEVE') ? 'selected' : ''; ?>>MIX SLEEVE</option>
      </select>
      <div class="checkbox-group">
        <label>
          <input type="checkbox" id="sublimationCollar" name="sublimationCollar" <?php echo (isset($party_details['sublimation_collar']) && $party_details['sublimation_collar']) ? 'checked' : ''; ?>> Sublimation Collar
        </label>
        <label>
          <input type="checkbox" id="sublimationSleeve" name="sublimationSleeve" <?php echo (isset($party_details['sublimation_sleeve']) && $party_details['sublimation_sleeve']) ? 'checked' : ''; ?>> Sublimation Sleeve
        </label>
      </div>
      <label for="partyPhoto">Upload OK DESIGN Photo (IF AVAILABLE):</label>
      <input type="file" id="partyPhoto" name="partyPhoto" accept="image/*">
      <?php if (isset($party_details['party_photo']) && !empty($party_details['party_photo'])): ?>
        <img id="partyPhotoPreview" src="data:image/jpeg;base64,<?php echo base64_encode($party_details['party_photo']); ?>" alt="Party Photo Preview">
      <?php else: ?>
        <img id="partyPhotoPreview" src="#" alt="Party Photo Preview" style="display: none;">
      <?php endif; ?>
      <button type="submit">Save Party Details</button>
    </form>
  </div>
</div>
<div class="container">
  <h2>T-Shirt Details</h2>
  <form id="tShirtForm" action="save_tshirt.php" method="POST">
    <input type="hidden" id="partyIdField" name="partyId" value="<?php echo $party_id; ?>">
    <label for="playerName">Player Name:</label>
    <input type="text" id="playerName" name="playerName" placeholder="Enter Player Name">
    <label for="tNo">T-No:</label>
    <input type="text" id="tNo" name="tNo" placeholder="Enter T-No">
    <label for="tShirtSize">T-Shirt Size:</label>
    <select id="tShirtSize" name="tShirtSize" required>
      <option value="" disabled selected>Select size</option>
      <option value="20">20 (1 Year)</option>
      <option value="22">22 (2-3 Year)</option>
      <option value="24">24 (4-5 Year)</option>
      <option value="26">26 (6-7 Year)</option>
      <option value="28">28 (8-9 Year)</option>
      <option value="30">30 (10-11 Year)</option>
      <option value="32">32 (12-13 Year)</option>
      <option value="34">34 (14-15 Year)</option>
      <option value="36">36 (S)</option>
      <option value="38">38 (M)</option>
      <option value="40">40 (L)</option>
      <option value="42">42 (XL)</option>
      <option value="44">44 (XXL)</option>
      <option value="46">46 (XXXL)</option>
      <option value="48">48 (XXXXL)</option>
      <option value="50">50 (5XL)</option>
      <option value="52">52 (6XL)</option>
      <option value="54">54 (7XL)</option>
    </select>
    <label for="sleeveType">Sleeve Type <span class="sleeve-type-indicator" id="sleeveTypeIndicator"></span>:</label>
    <select id="sleeveType" name="sleeveType" required>
      <option value="" disabled selected>Select Sleeve</option>
      <option value="FULL SLEEVE">FULL SLEEVE</option>
      <option value="HALF SLEEVE">HALF SLEEVE</option>
    </select>
    <button type="submit">Add Entry</button>
  </form>
</div>
<div class="container">
  <h2>Summary</h2>
  <div id="partyDetailsDisplay" style="text-align: center;">
    <?php if (!empty($party_details)): ?>
      <p><strong>Party Name:</strong> <?php echo $party_details['party_name']; ?></p>
      <p><strong>Mobile Number:</strong> <?php echo !empty($party_details['mobile_number']) ? $party_details['mobile_number'] : 'N/A'; ?></p>
      <p><strong>Delivery Date:</strong> <?php echo date('d-m-Y', strtotime($party_details['delivery_date'])); ?></p>
      <p><strong>Fabric Type:</strong> <?php echo $party_details['fabric_type']; ?></p>
      <p><strong>Collar Type:</strong> <?php echo $party_details['collar_type']; ?></p>
      <p><strong>Sleeve Type:</strong> <?php echo $party_details['sleeve_type']; ?></p>
      <p><strong>Sublimation Collar:</strong> <?php echo $party_details['sublimation_collar'] ? 'YES' : 'NO'; ?></p>
      <p><strong>Sublimation Sleeve:</strong> <?php echo $party_details['sublimation_sleeve'] ? 'YES' : 'NO'; ?></p>
      <!-- Add this to the party details display section -->
    <p><strong>Status:</strong> 
      <span class="status-badge status-<?php echo isset($party_details['status']) ? strtolower($party_details['status']) : 'progress'; ?>">
        <?php echo isset($party_details['status']) ? strtoupper($party_details['status']) : 'PROGRESS'; ?>
      </span>
    </p>
    <!-- Add this to the summary section after the status display -->
    <form action="update_status.php" method="POST" style="margin-top: 10px;">
      <input type="hidden" name="partyId" value="<?php echo $party_id; ?>">
      <select name="status" style="padding: 5px;">
        <option value="progress" <?php echo (isset($party_details['status']) && $party_details['status'] == 'progress') ? 'selected' : ''; ?>>In Progress</option>
        <option value="ready" <?php echo (isset($party_details['status']) && $party_details['status'] == 'ready') ? 'selected' : ''; ?>>Ready</option>
        <option value="delivered" <?php echo (isset($party_details['status']) && $party_details['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
      </select>
      <button type="submit" style="padding: 5px 10px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">Update Status</button>
    </form>
      <?php if (!empty($party_details['party_photo'])): ?>
        <img src="data:image/jpeg;base64,<?php echo base64_encode($party_details['party_photo']); ?>" alt="Party Photo" class="photo-preview">
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <table>
    <thead>
      <tr>
        <th>Sr. No</th>
        <th>Player Name</th>
        <th>T-No</th>
        <th>Size</th>
        <th>Sleeve Type</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody id="summaryTable">
      <?php if (!empty($tshirt_entries)): ?>
        <?php foreach ($tshirt_entries as $index => $entry): ?>
          <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo $entry['player_name']; ?></td>
            <td><?php echo $entry['t_no']; ?></td>
            <td><?php echo $entry['size']; ?></td>
            <td><?php echo $entry['sleeve_type']; ?></td>
            <td>
              <form action="delete_tshirt.php" method="POST" style="display:inline;">
                <input type="hidden" name="tshirtId" value="<?php echo $entry['id']; ?>">
                <input type="hidden" name="partyId" value="<?php echo $party_id; ?>">
                <button type="submit" class="delete-btn">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  <div class="summary-totals">
    <p>Total Orders: <span id="totalOrders"><?php echo count($tshirt_entries); ?></span></p>
    <p>FULL SLEEVE: <span id="totalFullSleeve"><?php echo !empty($tshirt_entries) ? count(array_filter($tshirt_entries, function($entry) { return $entry['sleeve_type'] === 'FULL SLEEVE'; })) : 0; ?></span></p>
    <p>HALF SLEEVE: <span id="totalHalfSleeve"><?php echo !empty($tshirt_entries) ? count(array_filter($tshirt_entries, function($entry) { return $entry['sleeve_type'] === 'HALF SLEEVE'; })) : 0; ?></span></p>
    <p>Sublimation Collar: <span id="totalSublimationCollar"><?php echo isset($party_details['sublimation_collar']) ? ($party_details['sublimation_collar'] ? 'YES' : 'NO') : 'No'; ?></span></p>
    <p>Sublimation Sleeve: <span id="totalSublimationSleeve"><?php echo isset($party_details['sublimation_sleeve']) ? ($party_details['sublimation_sleeve'] ? 'YES' : 'NO') : 'No'; ?></span></p>
  </div>
  <h3>Size Counts</h3>
  <table>
    <thead>
      <tr>
        <th>Size</th>
        <th>Count</th>
      </tr>
    </thead>
    <tbody id="sizeCountsTable">
      <?php
      if (!empty($tshirt_entries)) {
        $sizeCounts = [];
        foreach ($tshirt_entries as $entry) {
          $size = $entry['size'];
          if (!isset($sizeCounts[$size])) {
            $sizeCounts[$size] = 0;
          }
          $sizeCounts[$size]++;
        }
        
        foreach ($sizeCounts as $size => $count) {
          echo "<tr><td>$size</td><td>$count</td></tr>";
        }
      }
      ?>
    </tbody>
  </table>
  <div class="button-group">
    <button id="whatsappShare" <?php echo (empty($party_details) || empty($tshirt_entries)) ? 'disabled' : ''; ?>>Share via WhatsApp</button>
    <button id="downloadPDF" <?php echo (empty($party_details) || empty($tshirt_entries)) ? 'disabled' : ''; ?>>Download PDF</button>
  </div>
  <div id="shareMessage" class="message" style="<?php echo (empty($party_details) || empty($tshirt_entries)) ? 'display: block;' : 'display: none;'; ?>">Please enter details to share.</div>
  <a href="index.php" id="newOrder" class="action-btn" style="background-color: #28a745; margin-top: 10px;">New Order</a>
</div>
<script>
const { jsPDF } = window.jspdf;
const partyDetailsForm = document.getElementById("partyDetailsForm");
const tShirtForm = document.getElementById("tShirtForm");
const summaryTable = document.getElementById("summaryTable");
const partyDetailsDisplay = document.getElementById("partyDetailsDisplay");
const whatsappShareButton = document.getElementById("whatsappShare");
const downloadPDFButton = document.getElementById("downloadPDF");
const tShirtSizeSelect = document.getElementById("tShirtSize");
const partyPhotoInput = document.getElementById("partyPhoto");
const partyPhotoPreview = document.getElementById("partyPhotoPreview");
const totalOrdersDisplay = document.getElementById("totalOrders");
const totalFullSleeveDisplay = document.getElementById("totalFullSleeve");
const totalHalfSleeveDisplay = document.getElementById("totalHalfSleeve");
const totalSublimationCollarDisplay = document.getElementById("totalSublimationCollar");
const totalSublimationSleeveDisplay = document.getElementById("totalSublimationSleeve");
const sizeCountsTable = document.getElementById("sizeCountsTable");
const partySleeveTypeSelect = document.getElementById("partySleeveType");
const sleeveTypeSelect = document.getElementById("sleeveType");
const shareMessage = document.getElementById("shareMessage");
const collapsible = document.querySelector(".collapsible");
const content = document.getElementById("partyDetailsContent");
const sleeveTypeIndicator = document.getElementById("sleeveTypeIndicator");

// Define T-shirt sizes with labels
const tShirtSizes = [
  { size: "20", label: "20 (1 Year)" },
  { size: "22", label: "22 (2-3 Year)" },
  { size: "24", label: "24 (4-5 Year)" },
  { size: "26", label: "26 (6-7 Year)" },
  { size: "28", label: "28 (8-9 Year)" },
  { size: "30", label: "30 (10-11 Year)" },
  { size: "32", label: "32 (12-13 Year)" },
  { size: "34", label: "34 (14-15 Year)" },
  { size: "36", label: "36 (S)" },
  { size: "38", label: "38 (M)" },
  { size: "40", label: "40 (L)" },
  { size: "42", label: "42 (XL)" },
  { size: "44", label: "44 (XXL)" },
  { size: "46", label: "46 (XXXL)" },
  { size: "48", label: "48 (XXXXL)" },
  { size: "50", label: "50 (5XL)" },
  { size: "52", label: "52 (6XL)" },
  { size: "54", label: "54 (7XL)" }
];
// Add this code to your existing JavaScript in index.php

// Hide party details section after form submission
partyDetailsForm.addEventListener('submit', function() {
    // Hide the content immediately for better UX
    content.style.display = 'none';
    
    // Store in localStorage to maintain state after page reload
    localStorage.setItem('partyDetailsHidden', 'true');
});

// Check if party details should be hidden on page load
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('partyDetailsHidden') === 'true') {
        content.style.display = 'none';
        // Clear the flag so it doesn't affect future page loads
        localStorage.removeItem('partyDetailsHidden');
    }
    
    // Also hide if we're editing an existing party
    if (<?php echo !empty($party_details) ? 'true' : 'false'; ?>) {
        content.style.display = 'none';
    }
});
// Handle party photo upload
partyPhotoInput.addEventListener("change", (event) => {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      partyPhotoPreview.src = e.target.result;
      partyPhotoPreview.style.display = "block";
    };
    reader.readAsDataURL(file);
  }
});

// Initialize sleeveType dropdown based on partySleeveType
function initializeSleeveType() {
  const selectedSleeveType = partySleeveTypeSelect.value;
  if (selectedSleeveType === "MIX SLEEVE") {
    sleeveTypeSelect.disabled = false; // Enable sleeveType dropdown
  } else {
    sleeveTypeSelect.disabled = true; // Disable sleeveType dropdown
    sleeveTypeSelect.value = selectedSleeveType; // Set value based on partySleeveType
  }
  sleeveTypeIndicator.textContent = `(${selectedSleeveType})`; // Update sleeve type indicator
}

// Handle party sleeve type selection
partySleeveTypeSelect.addEventListener("change", (event) => {
  const selectedSleeveType = event.target.value;
  if (selectedSleeveType === "MIX SLEEVE") {
    sleeveTypeSelect.disabled = false; // Enable sleeveType dropdown
  } else {
    sleeveTypeSelect.disabled = true; // Disable sleeveType dropdown
    sleeveTypeSelect.value = selectedSleeveType; // Set value based on partySleeveType
  }
  sleeveTypeIndicator.textContent = `(${selectedSleeveType})`; // Update sleeve type indicator
});

// Initialize sleeveType dropdown on page load
initializeSleeveType();

// Generate PDF document
function generatePDF() {
  const partyName = "<?php echo isset($party_details['party_name']) ? $party_details['party_name'] : ''; ?>";  
  const deliveryDate = "<?php echo isset($party_details['delivery_date']) ? date('d-m-Y', strtotime($party_details['delivery_date'])) : ''; ?>";
  const fabricType = "<?php echo isset($party_details['fabric_type']) ? $party_details['fabric_type'] : ''; ?>";
  const collarType = "<?php echo isset($party_details['collar_type']) ? $party_details['collar_type'] : ''; ?>";
  const sleeveType = "<?php echo isset($party_details['sleeve_type']) ? $party_details['sleeve_type'] : ''; ?>";
  const sublimationCollar = "<?php echo isset($party_details['sublimation_collar']) ? ($party_details['sublimation_collar'] ? 'YES' : 'NO') : 'NO'; ?>";
  const sublimationSleeve = "<?php echo isset($party_details['sublimation_sleeve']) ? ($party_details['sublimation_sleeve'] ? 'YES' : 'NO') : 'NO'; ?>";
  const partyPhoto = "<?php echo isset($party_details['party_photo']) && !empty($party_details['party_photo']) ? 'data:image/jpeg;base64,' . base64_encode($party_details['party_photo']) : ''; ?>";

  const tshirtEntries = <?php echo json_encode($tshirt_entries); ?>;

  if (!partyName || tshirtEntries.length === 0) {
    alert("Please enter details to generate PDF.");
    return;
  }

  // Sort entries by size (low to high)
  const sortedEntries = tshirtEntries.slice().sort((a, b) => a.size - b.size);
  const doc = new jsPDF("p", "mm", "a4"); // Portrait mode
  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();

  // Set margins and initial y position
  const margin = 20;
  let yPos = margin;

  // Add title
  doc.setFontSize(24); // Increased font size
  doc.setFont("helvetica", "bold");
  doc.text("T-Shirt Order Summary", pageWidth / 2, yPos, { align: "center" });
  yPos += 15;

  // Add party details in a table
  const partyDetailsColumns = ["Detail", "Value"];
  const partyDetailsRows = [
    ["Party Name", partyName.toUpperCase()], // Convert party name to uppercase
   // ["Mobile Number", mobileNumber || "N/A"],
    ["Delivery Date", deliveryDate],
    ["Fabric Type", fabricType],
    ["Collar Type", collarType],
    ["Sleeve Type", sleeveType.toUpperCase()],
    ["Sublimation Collar", sublimationCollar],
    ["Sublimation Sleeve", sublimationSleeve]
  ];
  
  doc.autoTable({
    startY: yPos,
    head: [partyDetailsColumns],
    body: partyDetailsRows,
    theme: "grid",
    styles: {fontStyle: "bold", fontSize: 16, halign: "left" },
    headStyles: { fontSize: 14, fillColor: [241, 241, 241], textColor: [0, 0, 0] },
    margin: { top: 20 },
    tableWidth: 'auto',
    columnStyles: {
      0: { cellWidth: 'auto' },
      1: { cellWidth: 'auto' }
    }
  });
  
  yPos = doc.lastAutoTable.finalY + 10;

  // Add party photo if available
  if (partyPhoto) {
    const img = new Image();
    img.src = partyPhoto;
    const imgWidth = 700; // Desired width in pixels
    const imgHeight = 350; // Desired height in pixels
    const aspectRatio = imgWidth / imgHeight;
    const maxWidth = pageWidth - 40; // Max width with margins
    const maxHeight = 80; // Max height for the image
    let finalWidth = maxWidth;
    let finalHeight = maxWidth / aspectRatio;
    if (finalHeight > maxHeight) {
      finalHeight = maxHeight;
      finalWidth = maxHeight * aspectRatio;
    }
    
    // Check if we need a new page for the image
    if (yPos + finalHeight > pageHeight - margin) {
      doc.addPage();
      yPos = margin;
    }
    
    doc.addImage(img, "JPEG", (pageWidth - finalWidth) / 2, yPos, finalWidth, finalHeight);
    yPos += finalHeight + 10;
  }

  // Add T-Shirt details table
    const columns = ["Sr. No", "Player Name", "Size", "Sleeve Type"];
  const rows = sortedEntries.map((entry, index) => [
    index + 1,
    entry.player_name || "-", // Replace blank with "-"
    entry.t_no || "-", // Replace blank with "-"
    tShirtSizes.find(s => s.size === entry.size)?.label || entry.size,
    entry.sleeve_type
  ]);
  
  doc.autoTable({
    startY: yPos,
    head: [columns],
    body: rows,
    theme: "grid",
    styles: { fontSize: 12, halign: "center" },
    headStyles: { fontSize: 14, fillColor: [241, 241, 241], textColor: [0, 0, 0] },
    alternateRowStyles: { fillColor: [230, 247, 255] }, // Light sky blue for alternating rows
    margin: { top: 20 },
    tableWidth: 'auto',
    columnStyles: {
      0: { cellWidth: 'auto' },
      1: { cellWidth: 'auto' },
      2: { cellWidth: 'auto' },
      3: { cellWidth: 'auto' },
      4: { cellWidth: 'auto' }
    },
    didDrawPage: (data) => {
      // Add footer on each page
      doc.setFontSize(12);
      doc.text(`Page ${doc.internal.getNumberOfPages()}`, pageWidth / 2, pageHeight - 10, { align: "center" });
    },
  });
  
  yPos = doc.lastAutoTable.finalY + 10;

  // Add totals
  doc.setFontSize(16);
  doc.setFont("helvetica", "bold");
  
  // Check if we need a new page for totals
  if (yPos + 60 > pageHeight - margin) {
    doc.addPage();
    yPos = margin;
  }
  
  doc.text(`Total Orders: ${tshirtEntries.length}`, pageWidth / 2, yPos, { align: "center" });
  yPos += 10;
  doc.text(`FULL SLEEVE: ${tshirtEntries.filter(entry => entry.sleeve_type === "FULL SLEEVE").length}`, pageWidth / 2, yPos, { align: "center" });
  yPos += 10;
  doc.text(`HALF SLEEVE: ${tshirtEntries.filter(entry => entry.sleeve_type === "HALF SLEEVE").length}`, pageWidth / 2, yPos, { align: "center" });
  yPos += 10;
  doc.text(`Sublimation Collar: ${sublimationCollar}`, pageWidth / 2, yPos, { align: "center" });
  yPos += 10;
  doc.text(`Sublimation Sleeve: ${sublimationSleeve}`, pageWidth / 2, yPos, { align: "center" });
  yPos += 20;

  // Add size counts table
  const sizeCounts = {};
  tshirtEntries.forEach(entry => {
    if (sizeCounts[entry.size]) {
      sizeCounts[entry.size]++;
    } else {
      sizeCounts[entry.size] = 1;
    }
  });
  const sizeCountsColumns = ["Size", "Count"];
  const sizeCountsRows = Object.entries(sizeCounts).map(([size, count]) => [
    tShirtSizes.find(s => s.size === size)?.label || size,
    count
  ]);
  
  // Check if we need a new page for size counts
  if (yPos + 100 > pageHeight - margin) {
    doc.addPage();
    yPos = margin;
  }
  
  doc.autoTable({
    startY: yPos,
    head: [sizeCountsColumns],
    body: sizeCountsRows,
    theme: "grid",
    styles: { fontSize: 12, halign: "center" },
    headStyles: { fontSize: 14, fillColor: [241, 241, 241], textColor: [0, 0, 0] },
    alternateRowStyles: { fillColor: [230, 247, 255] }, // Light sky blue for alternating rows
    margin: { top: 20 },
    tableWidth: 'auto',
    columnStyles: {
      0: { cellWidth: 'auto' },
      1: { cellWidth: 'auto' }
    }
  });
  
  yPos = doc.lastAutoTable.finalY + 10;

  // Add sleeve type breakdown if MIX SLEEVE is selected
  if (sleeveType === "MIX SLEEVE") {
    // Check if we need a new page for sleeve type breakdown
    if (yPos + 100 > pageHeight - margin) {
      doc.addPage();
      yPos = margin;
    }
    
    // Add title for sleeve type breakdown
    doc.setFontSize(18);
    doc.setFont("helvetica", "bold");
    doc.text("Sleeve Type Breakdown", pageWidth / 2, yPos, { align: "center" });
    yPos += 15;

    // Add FULL SLEEVE entries table
    const fullSleeveEntries = tshirtEntries.filter(entry => entry.sleeve_type === "FULL SLEEVE").sort((a, b) => a.size - b.size);
    if (fullSleeveEntries.length > 0) {
      doc.setFontSize(16);
      doc.text("FULL SLEEVE", pageWidth / 2, yPos, { align: "center" });
      yPos += 10;
      
      const fullSleeveRows = fullSleeveEntries.map((entry, index) => [
        index + 1,
        entry.player_name || "-",
        entry.t_no || "-",
        tShirtSizes.find(s => s.size === entry.size)?.label || entry.size
      ]);
      
      doc.autoTable({
        startY: yPos,
        head: [["Sr. No", "Player Name", "T-No", "Size"]],
        body: fullSleeveRows,
        theme: "grid",
        styles: { fontSize: 12, halign: "center" },
        headStyles: { fontSize: 14, fillColor: [241, 241, 241], textColor: [0, 0, 0] },
        alternateRowStyles: { fillColor: [230, 247, 255] },
        margin: { top: 20 },
        tableWidth: 'auto',
        columnStyles: {
          0: { cellWidth: 'auto' },
          1: { cellWidth: 'auto' },
          2: { cellWidth: 'auto' },
          3: { cellWidth: 'auto' }
        }
      });
      
      yPos = doc.lastAutoTable.finalY + 10;
    }

    // Check if we need a new page for HALF SLEEVE entries
    if (yPos + 100 > pageHeight - margin) {
      doc.addPage();
      yPos = margin;
    }

    // Add HALF SLEEVE entries table
    const halfSleeveEntries = tshirtEntries.filter(entry => entry.sleeve_type === "HALF SLEEVE").sort((a, b) => a.size - b.size);
    if (halfSleeveEntries.length > 0) {
      doc.setFontSize(16);
      doc.text("HALF SLEEVE", pageWidth / 2, yPos, { align: "center" });
      yPos += 10;
      
      const halfSleeveRows = halfSleeveEntries.map((entry, index) => [
        index + 1,
        entry.player_name || "-",
        entry.t_no || "-",
        tShirtSizes.find(s => s.size === entry.size)?.label || entry.size
      ]);
      
      doc.autoTable({
        startY: yPos,
        head: [["Sr. No", "Player Name", "T-No", "Size"]],
        body: halfSleeveRows,
        theme: "grid",
        styles: { fontSize: 12, halign: "center" },
        headStyles: { fontSize: 14, fillColor: [241, 241, 241], textColor: [0, 0, 0] },
        alternateRowStyles: { fillColor: [230, 247, 255] },
        margin: { top: 20 },
        tableWidth: 'auto',
        columnStyles: {
          0: { cellWidth: 'auto' },
          1: { cellWidth: 'auto' },
          2: { cellWidth: 'auto' },
          3: { cellWidth: 'auto' }
        }
      });
    }
  }

  return doc;
}

// Share via WhatsApp
whatsappShareButton.addEventListener("click", async () => {
  const doc = generatePDF();
  if (!doc) return;

  const pdfBlob = doc.output("blob");
  
  // Generate filename in format: partyname_Deliverydate_totalorders
  const partyName = "<?php echo isset($party_details['party_name']) ? $party_details['party_name'] : ''; ?>";
  const deliveryDate = "<?php echo isset($party_details['delivery_date']) ? date('d-m-Y', strtotime($party_details['delivery_date'])) : ''; ?>";
  const totalOrders = "<?php echo count($tshirt_entries); ?>";
  const partyNameForFilename = partyName.replace(/\s+/g, '_'); // Replace spaces with underscores
  const pdfFileName = `${partyNameForFilename}_${deliveryDate}_${totalOrders}_orders.pdf`;
  
  const pdfFile = new File([pdfBlob], pdfFileName, { type: "application/pdf" });

  if (navigator.canShare && navigator.canShare({ files: [pdfFile] })) {
    try {
      await navigator.share({
        title: "T-Shirt Order Summary",
        files: [pdfFile]
      });
    } catch (error) {
      alert("Error sharing file. Try downloading it manually.");
    }
  } else {
    const pdfURL = URL.createObjectURL(pdfBlob);
    const a = document.createElement("a");
    a.href = pdfURL;
    a.download = pdfFileName;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    alert("Download the PDF and share it manually.");
  }
});

// Download PDF
downloadPDFButton.addEventListener("click", () => {
  const doc = generatePDF();
  if (!doc) return;

  // Generate filename in format: partyname_Deliverydate_totalorders
  const partyName = "<?php echo isset($party_details['party_name']) ? $party_details['party_name'] : ''; ?>";
  const deliveryDate = "<?php echo isset($party_details['delivery_date']) ? date('d-m-Y', strtotime($party_details['delivery_date'])) : ''; ?>";
  const totalOrders = "<?php echo count($tshirt_entries); ?>";
  const partyNameForFilename = partyName.replace(/\s+/g, '_'); // Replace spaces with underscores
  const pdfFileName = `${partyNameForFilename}_${deliveryDate}_${totalOrders}_orders.pdf`;
  
  doc.save(pdfFileName);
});

// Collapsible functionality
collapsible.addEventListener("click", () => {
  if (content.style.display === "block") {
    content.style.display = "none";
  } else {
    content.style.display = "block";
  }
});
</script>
</body>
</html>

