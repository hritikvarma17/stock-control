<?php
include('auth_check.php');
$host = 'localhost';
$user = 'root'; 
$pass = '';     
$dbname = 'stock_control';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$outOfStockCount = 0;
$lowStockCount = 0;


$statsQuery = "SELECT 
                SUM(CASE WHEN Quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN Quantity > 0 AND Quantity <= ReorderLevel THEN 1 ELSE 0 END) as low_stock
               FROM stock_dtl";
$statsResult = $conn->query($statsQuery);

if ($statsResult && $row = $statsResult->fetch_assoc()) {
    $outOfStockCount = $row['out_of_stock'] ?? 0;
    $lowStockCount = $row['low_stock'] ?? 0;
}

$filter = isset($_POST['status_filter']) ? $_POST['status_filter'] : 'all';
$whereClause = "WHERE Quantity <= ReorderLevel";  

if ($filter === 'out') {
    $whereClause .= " AND Quantity <= 0";
} elseif ($filter === 'low') {
    $whereClause .= " AND Quantity > 0";
}

$alertsQuery = "SELECT * FROM stock_dtl $whereClause ORDER BY Quantity ASC";
$alertsResult = $conn->query($alertsQuery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Alerts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="stock_alerts.css">
    <link rel="stylesheet" href="settings.css">  
    <script src="theme-loader.js"></script>
</head>

<body>

    <div class="layout-index">
        <aside class="sidebar-index">
            <div class="brand-index">
                <i class="fa-solid fa-box-archive logo-icon"></i>&nbsp;
                <span>Stock Control</span>
            </div>

            <nav class="menu-index">
                <a href="index.php" class="nav-item-index"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="stock_management.php" class="nav-item-index"><i class="fa-solid fa-boxes-stacked"></i> Stock Management</a>
                <a href="stock_alerts.php" class="nav-item-index active"><i class="fa-solid fa-triangle-exclamation"></i> Stock Alerts</a>
                <a href="dispatch_items.php" class="nav-item-index"><i class="fa-solid fa-truck-fast"></i> Dispatch</a>
                <a href="inward_items.php" class="nav-item-index"><i class="fa-solid fa-arrow-right-to-bracket"></i> Inward</a>
                <a href="generate_barcode.php" class="nav-item-index"><i class="fa-solid fa-barcode"></i> Generate Barcode</a>
                <a href="generate_pdf.php" class="nav-item-index"><i class="fa-solid fa-file-pdf"></i> Generate PDF</a>
                <a href="logs.php" class="nav-item-index"><i class="fa-solid fa-clock-rotate-left"></i> Logs</a>
                <div class="nav-divider-index"></div>
                <a href="user_mgmt.php" class="nav-item-index"><i class="fa-solid fa-user-gear"></i> User Management</a>
                <a href="settings.php" class="nav-item-index"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="logout.php" class="nav-item-index logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                
                <h1><i class="fa-solid fa-triangle-exclamation"></i> Low Stock Alerts</h1>
                <div class="current-user">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 
                    (<small><?php echo $_SESSION['role']; ?></small>)</span>
                </div>
            </header>

            <section class="content-container">
                <div class="page-header">
                    <div class="filter-container">
                        <i class="fa-solid fa-filter"></i>
                        <select id="statusFilter" onchange="filterByStatus()" class="status-select">
                            <option value="all">All Alerts</option>
                            <option value="out">Out of Stock</option>
                            <option value="low">Low Stock</option>
                        </select>
                    </div>
                    <div class="header-actions">
                        <button class="btn-export" onclick="window.print()">
                            <i class="fa-solid fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>

                <div class="alert-stats">
                    <div class="stat-card critical">
                        <span class="stat-label">Out of Stock</span>
                        <span class="stat-value"><?php echo $outOfStockCount; ?></span>
                    </div>
                    <div class="stat-card warning">
                        <span class="stat-label">Low Stock</span>
                        <span class="stat-value"><?php echo $lowStockCount; ?></span>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="stock-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Current Qty</th>
                                <th>Min. Qty</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($alertsResult && $alertsResult->num_rows > 0) {
                                while ($item = $alertsResult->fetch_assoc()) { 
                                    $isOutOfStock = ($item['Quantity'] <= 0);
                                     
                                    $badgeClass = $isOutOfStock ? "critical" : "low";
                                    $badgeText = $isOutOfStock ? "Out of Stock" : "Low Stock";
                                    $qtyClass = $isOutOfStock ? "text-critical" : "text-danger";

                                    echo '<tr class="alert-row">';
                                    echo '  <td><span class="status-badge ' . $badgeClass . '">' . $badgeText . '</span></td>';
                                    echo '  <td><div class="item-primary">' . htmlspecialchars($item['ItemName']) . '</div></td>';
                                    echo '  <td><span class="category-label">' . htmlspecialchars($item['Category']) . '</span></td>';
                                    echo '  <td><strong class="' . $qtyClass . '">' . $item['Quantity'] . '</strong></td>';
                                    echo '  <td>' . htmlspecialchars($item['ReorderLevel'] ?? '0') . '</td>';
                                    echo '  <td>' . htmlspecialchars($item['Location'] ?? 'N/A') . '</td>';
                                    echo '  <td>
                                                <div class="action-btns">
                                                    <a href="stock_management.php?auto_edit=' . $item['ItemID'] . '" class="restock-btn" style="text-decoration:none; display:inline-block; padding: 6px 12px; background-color: #0d6efd; color: white; border-radius: 4px;" title="Edit Item to Restock">
                                                        <i class="fa-solid fa-cart-plus"></i> Restock
                                                    </a>
                                                </div>
                                            </td>';
                                    echo '</tr>';
                                }
                            } else { 
                                echo '<tr><td colspan="7" style="text-align:center; padding: 20px;">All stock levels are healthy. No alerts at this time.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        function filterByStatus() {
            let status = document.getElementById("statusFilter").value;
            
            let xhr = new XMLHttpRequest();
            xhr.open("POST", "stock_alerts.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) { 
                    let parser = new DOMParser();
                    let doc = parser.parseFromString(this.responseText, "text/html");
                    let newRows = doc.querySelector(".stock-table tbody").innerHTML;
                    document.querySelector(".stock-table tbody").innerHTML = newRows;
                }
            };
            xhr.send("status_filter=" + status);
        }
    </script>
</body>
</html>