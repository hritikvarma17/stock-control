<?php
include('auth_check.php'); 
include 'db.php'; 
 
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$sql = "SELECT l.*, u.Username as UserDisplay, s.ItemName
        FROM logs_dtl l 
        LEFT JOIN user_dtl u ON l.UserID = u.UserID
        LEFT JOIN stock_dtl s ON l.ItemID = s.ItemID";

if ($filter !== 'all') {
    $sql .= " WHERE l.ActionType LIKE '%" . $conn->real_escape_string($filter) . "%'";
}
$sql .= " ORDER BY l.Timestamp DESC LIMIT 100";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs | Stock Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="logs.css">  
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
                <a href="stock_alerts.php" class="nav-item-index"><i class="fa-solid fa-triangle-exclamation"></i> Stock Alerts</a>
                <a href="dispatch_items.php" class="nav-item-index"><i class="fa-solid fa-truck-fast"></i> Dispatch</a>
                <a href="inward_items.php" class="nav-item-index"><i class="fa-solid fa-arrow-right-to-bracket"></i> Inward</a>
                <a href="generate_barcode.php" class="nav-item-index"><i class="fa-solid fa-barcode"></i> Generate Barcode</a>
                <a href="generate_pdf.php" class="nav-item-index"><i class="fa-solid fa-file-pdf"></i> Generate PDF</a>
                <a href="logs.php" class="nav-item-index active"><i class="fa-solid fa-clock-rotate-left"></i> Logs</a>
                <div class="nav-divider-index"></div>
                <a href="user_mgmt.php" class="nav-item-index"><i class="fa-solid fa-user-gear"></i> User Management</a>
                <a href="settings.php" class="nav-item-index"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="logout.php" class="nav-item-index logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <h1 style="margin:0;"><i class="fa-solid fa-clock-rotate-left"></i> System Activity Logs</h1>
                <div class="current-user">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 
                    (<small><?php echo $_SESSION['role']; ?></small>)</span>
                </div>
            </header>

            <section class="content-container">
                <div class="log-header-flex">
                    <div style="text-align: right;">
                        <label style="display:block; font-size: 11px; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-bottom:5px;">Filter Category</label>
                        <select class="filter-select" onchange="location.href='logs.php?filter=' + this.value;">
                            <option value="all" <?php if($filter == 'all') echo 'selected'; ?>>All Transactions</option>
                            <option value="Stock" <?php if($filter == 'Stock') echo 'selected'; ?>>Stock Updates</option>
                            <option value="Dispatch" <?php if($filter == 'Dispatch') echo 'selected'; ?>>Dispatch Activity</option>
                            <option value="Inward" <?php if($filter == 'Inward') echo 'selected'; ?>>Inward Returns</option>
                        </select>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="stock-table" id="logsTable">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Movement</th>
                                <th>Operator</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $isStock = stripos($row['ActionType'], 'Stock') !== false;
                                    $isDispatch = stripos($row['ActionType'], 'Dispatch') !== false;
                                    $isInward = stripos($row['ActionType'], 'Inward') !== false;
                                    $badgeClass = $isStock ? 'badge-stock' : ($isDispatch ? 'badge-dispatch' : ($isInward ? 'badge-inward' : ''));
                                    $icon = $isStock ? 'fa-boxes-stacked' : ($isDispatch ? 'fa-truck-arrow-right' : ($isInward ? 'fa-rotate-left' : 'fa-circle-info'));
                                ?>
                                    <tr>
                                        <td class="time-cell">
                                            <strong><?php echo date('d M, Y', strtotime($row['Timestamp'])); ?></strong>
                                            <br><small><?php echo date('h:i A', strtotime($row['Timestamp'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="log-badge <?php echo $badgeClass; ?>">
                                                <i class="fa-solid <?php echo $icon; ?>"></i>
                                                <?php echo strtoupper(trim(explode(':', $row['ActionType'])[0])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="item-name-cell"><?php echo htmlspecialchars($row['ItemName'] ?? 'Multiple Items'); ?></span>
                                            <span class="action-text"><?php echo htmlspecialchars($row['ActionType']); ?></span>
                                        </td>
                                        <td>
                                            <?php if($row['QuantityChanged'] != 0): ?>
                                                <span class="movement-pill <?php echo $row['QuantityChanged'] > 0 ? 'movement-up' : 'movement-down'; ?>">
                                                    <?php echo ($row['QuantityChanged'] > 0 ? '+' : '') . $row['QuantityChanged']; ?> Units
                                                </span>
                                            <?php else: ?>
                                                <span style="color:#cbd5e1; font-size:11px; font-weight:700;">METADATA CHANGE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['PerformedBy']); ?>&background=random&color=fff&size=50" class="user-avatar">
                                                <span style="font-weight:600; font-size:0.85rem; color:#475569;"><?php echo htmlspecialchars($row['PerformedBy']); ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; padding:60px; color:#94a3b8; font-style:italic;">No records found matching the current criteria.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        function searchLogs() {
            let input = document.getElementById("logSearch").value.toLowerCase();
            let rows = document.querySelectorAll("#logsTable tbody tr");
            rows.forEach(row => {
                let description = row.cells[2].textContent.toLowerCase();
                row.style.display = description.includes(input) ? "" : "none";
            });
        }
    </script>
</body>
</html>