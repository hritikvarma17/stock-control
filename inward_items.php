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

function recordLog($conn, $userId, $itemId, $action, $qtyChanged, $performedBy) { 
    $uId = (!empty($userId) && $userId > 0) ? $userId : NULL;
    
    $stmt = $conn->prepare("INSERT INTO logs_dtl (UserID, ItemID, ActionType, QuantityChanged, PerformedBy) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisis", $uId, $itemId, $action, $qtyChanged, $performedBy);
    $stmt->execute();
    $stmt->close();
}
 
$msg = "";
if (isset($_GET['action']) && $_GET['action'] == 'receive') {
    $order_id = $conn->real_escape_string($_GET['order_id']);

    $conn->begin_transaction();

    try { 
        $items_query = $conn->query("SELECT ItemID, QtyDispatched FROM dispatch_history WHERE OrderID = '$order_id'");
        $total_order_qty = 0; 
        while ($item = $items_query->fetch_assoc()) {
            $qty = $item['QtyDispatched'];
            $total_order_qty += $qty;  
 
            $update_stock = $conn->prepare("UPDATE stock_dtl SET Quantity = Quantity + ? WHERE ItemID = ?");
            $update_stock->bind_param("ii", $qty, $item['ItemID']);
            $update_stock->execute();
        }
 
        recordLog(
            $conn, 
            $_SESSION['user_id'], 
            null, 
            "Inward: Order #$order_id", 
            $total_order_qty, 
            $_SESSION['username']  
        );
 
        $delete_dispatch = $conn->prepare("DELETE FROM dispatch_history WHERE OrderID = ?");
        $delete_dispatch->bind_param("s", $order_id);
        $delete_dispatch->execute();

        $conn->commit();
        $msg = "<div class='incoming-count' style='background:#d1e7dd; color:#0f5132; border-color:#badbcc; margin-top:20px; margin-bottom:10px;'>
                <i class='fa-solid fa-circle-check'></i> Order #$order_id received! Stock levels restored.
                </div>";
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div class='incoming-count' style='background:#f8d7da; color:#842029; border-color:#f5c2c7; margin-top:20px; margin-bottom:10px;'>
                <i class='fa-solid fa-circle-xmark'></i> Error processing inward for Order #$order_id.
                </div>";
    }
}
 
$sql = "SELECT OrderID, RecipientName, Location, DispatchDate, 
        MAX(ReturnDate) as EstReturnDate,
        SUM(QtyDispatched) as TotalQty 
        FROM dispatch_history 
        GROUP BY OrderID 
        ORDER BY DispatchDate DESC";

$result = $conn->query($sql);  
$in_transit_count = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inward Items</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="inward_items.css">
    <link rel="stylesheet" href="settings.css">  
    <script src="theme-loader.js"></script>
    <style>
    </style>
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
                <a href="stock_alerts.php" class="nav-item-index"><i class="fa-solid fa-triangle-exclamation"></i> Stock Alerts</a>
                <a href="dispatch_items.php" class="nav-item-index"><i class="fa-solid fa-truck-fast"></i> Dispatch</a>
                <a href="inward_items.php" class="nav-item-index active"><i class="fa-solid fa-arrow-right-to-bracket"></i> Inward</a>
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
                <h1><i class="fa-solid fa-arrow-right-to-bracket"></i> Inward Items</h1>
                <div class="current-user">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 
                    (<small><?php echo $_SESSION['role']; ?></small>)</span>
                </div>
            </header>

            <section class="content-container">
                <div class="page-header">
                    
                    <div class="header-actions" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span class="incoming-count"><?php echo $in_transit_count; ?> Pending Inward Orders</span>
                        </div>
                    </div>
                </div>

                <?php echo $msg; ?>

                <div class="table-wrapper">
                    <table class="stock-table" id="inwardTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Recipient</th>
                                <th>Location</th>
                                <th>Total Quantity</th>
                                <th>Dispatch Date</th>
                                <th>Est. Return Date</th>
                                <th class="action-btns">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['OrderID']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['RecipientName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Location']); ?></td>
                                    <td><strong style="color: #0d6efd;"><?php echo $row['TotalQty']; ?> Units</strong></td>
                                    <td class="date-label"><?php echo date('d-M-Y', strtotime($row['DispatchDate'])); ?></td>
                                    <td class="date-label">
                                        <?php 
                                            if (!empty($row['EstReturnDate']) && $row['EstReturnDate'] != '0000-00-00') {
                                                $returnDate = strtotime($row['EstReturnDate']);
                                                $today = strtotime(date('Y-m-d'));
                                                $class = ($returnDate < $today) ? 'return-warning' : '';
                                                echo "<span class='$class'>" . date('d-M-Y', $returnDate) . "</span>";
                                            } else {
                                                echo "<span style='color:#cbd5e1;'>N/A</span>";
                                            }
                                        ?>
                                    </td>
                                    <td class="action-btns">
                                        <button class="btn-receive" onclick="confirmInward('<?php echo $row['OrderID']; ?>', <?php echo $row['TotalQty']; ?>)">
                                            <i class="fa-solid fa-square-check"></i> Receive
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="padding: 40px; text-align: center; color: #64748b; font-style: italic;">
                                        No active orders waiting for inward processing.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
    function confirmInward(orderId, totalQty) {
        if (confirm("Confirm Inward for Order #" + orderId + "?\n\nThis will return " + totalQty + " total units back to stock inventory and close the dispatch record.")) {
            window.location.href = 'inward_items.php?action=receive&order_id=' + orderId;
        }
    }
    </script>
</body>
</html>