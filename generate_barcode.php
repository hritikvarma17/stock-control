<?php
include('auth_check.php');
$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'stock_control';
$conn = new mysqli($host, $user, $pass, $dbname);
 
$items = $conn->query("SELECT ItemID, ItemName, Category, Quantity FROM stock_dtl ORDER BY ItemName ASC");
$total_items = $conn->query("SELECT COUNT(*) as total FROM stock_dtl")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="generate_barcode.css"> 
    <link rel="stylesheet" href="settings.css">  
    <script src="theme-loader.js"></script> 
</head>
<body>
<div class="layout-index" style="display: flex;">
    <aside class="sidebar-index">
        <div class="brand-index"><i class="fa-solid fa-box-archive logo-icon"></i>&nbsp;<span>Stock Control</span></div>
        <nav class="menu-index">
            <a href="index.php" class="nav-item-index"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="stock_management.php" class="nav-item-index"><i class="fa-solid fa-boxes-stacked"></i> Stock Management</a>
            <a href="stock_alerts.php" class="nav-item-index"><i class="fa-solid fa-triangle-exclamation"></i> Stock Alerts</a>
            <a href="dispatch_items.php" class="nav-item-index"><i class="fa-solid fa-truck-fast"></i> Dispatch</a>
            <a href="inward_items.php" class="nav-item-index"><i class="fa-solid fa-arrow-right-to-bracket"></i> Inward</a>
            <a href="generate_barcode.php" class="nav-item-index active"><i class="fa-solid fa-barcode"></i> Generate Barcode</a>
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
            <div style="font-size: 1.25rem; font-weight: 700; color: #1e293b;"><h2><i class="fa-solid fa-barcode"></i> Barcode Center</h2></div>
            <div class="current-user">
                <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 
                (<small><?php echo $_SESSION['role']; ?></small>)</span>
            </div>
        </header>

        <section class="content-container">
            <div class="setup-section">
                <form method="GET">
                    <label style="display:block; margin-bottom:12px; font-weight:600;">Inventory Item Selection</label>
                    <div style="display:flex; gap:15px;">
                        <select name="item_id" required style="flex:1; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1;">
                            <option value="">-- Choose Item from Stock --</option>
                            <?php $items->data_seek(0); while($row = $items->fetch_assoc()): ?>
                                <option value="<?php echo $row['ItemID']; ?>" <?php echo (isset($_GET['item_id']) && $_GET['item_id'] == $row['ItemID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['ItemName']); ?> | <?php echo htmlspecialchars($row['Category']); ?> (Qty: <?php echo $row['Quantity']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" class="btn-generate">Generate</button>
                    </div>
                </form>
            </div>

            <?php if(isset($_GET['item_id'])): 
                $id = intval($_GET['item_id']);
                $item = $conn->query("SELECT ItemName, Category, Quantity FROM stock_dtl WHERE ItemID = $id")->fetch_assoc();
                if($item):
            ?>
                <div class="print-header-text" style="display:flex; justify-content:space-between; align-items:flex-end; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
                    <div>
                        <h2 style="margin:0;"><?php echo htmlspecialchars($item['ItemName']); ?> Labels</h2>
                        <p style="color:#64748b; margin-top:5px;">Printing <?php echo $item['Quantity']; ?> labels (18 per page).</p>
                    </div>
                    <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Print Labels</button>
                </div>

                <div class="barcode-grid">
                    <?php for($i = 1; $i <= $item['Quantity']; $i++): 
                        $text = urlencode($item['ItemName']);
                        $url = "https://bwipjs-api.metafloor.com/?bcid=code128&text=$text&scale=2&rotate=N&includetext";
                    ?>
                        <div class="barcode-card">
                            <img src="<?php echo $url; ?>" alt="Barcode">
                            <span class="info-cat"><?php echo htmlspecialchars($item['Category']); ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; endif; ?>
        </section>
    </main>
</div>
</body>
</html>