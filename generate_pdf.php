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
 
if (isset($_GET['render_preview'])) {
    $type = $_GET['render_preview'];
    $report_title = "";
    $query = "";

    switch ($type) {
        case 'stock':
            $report_title = "Inventory Summary Report";
            $query = "SELECT ItemID, ItemName, Category, Quantity, Location FROM stock_dtl ORDER BY Category ASC";
            break;
        case 'alerts':
            $report_title = "Low Stock Alert Report";
            $query = "SELECT ItemName, Category, Quantity, ReorderLevel, Location FROM stock_dtl WHERE Quantity <= ReorderLevel ORDER BY Quantity ASC";
            break;
        case 'inbound':
            $report_title = "Inbound Logistics Report (In Transit)"; 
            $query = "SELECT OrderID, RecipientName, Location, SUM(QtyDispatched) as TotalQty, DispatchDate 
                      FROM dispatch_history 
                      GROUP BY OrderID 
                      ORDER BY DispatchDate DESC";
            break;
        case 'outbound':
            $report_title = "Outbound Fulfillment History";
            $query = "SELECT OrderID, RecipientName, Location, DispatchDate, DataSummary FROM dispatch_archives ORDER BY DispatchDate DESC";
            break;
        default:
            die("Invalid Report Type");
    }

    $result = $conn->query($query);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; color: #333; background: #fff; }
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background-color: #f2f2f2; font-size: 12px; text-transform: uppercase; }
            .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #888; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="header">
            <h1><?php echo $report_title; ?></h1>
            <p>Generated on: <?php echo date('d-M-Y H:i'); ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <?php if ($type == 'stock'): ?>
                        <th>ID</th><th>Item</th><th>Category</th><th>Qty</th><th>Location</th>
                    <?php elseif ($type == 'alerts'): ?>
                        <th>Item</th><th>Category</th><th>Current</th><th>Min Level</th><th>Location</th>
                    <?php elseif ($type == 'inbound'): ?>
                        <th>Order ID</th><th>Recipient</th><th>Destination</th><th>Total Qty</th><th>Dispatch Date</th>
                    <?php elseif ($type == 'outbound'): ?>
                        <th>Order ID</th><th>Recipient</th><th>Destination</th><th>Date</th><th>Details</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr><?php foreach($row as $val) echo "<td>".htmlspecialchars($val)."</td>"; ?></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="footer">Stock Control System - Internal Management Report</div>
    </body>
    </html>
    <?php
    exit;
}
 
$total_items = $conn->query("SELECT COUNT(*) as total FROM stock_dtl")->fetch_assoc()['total'];
$total_dispatches = $conn->query("SELECT COUNT(*) as total FROM dispatch_archives")->fetch_assoc()['total'];
$in_transit = $conn->query("SELECT COUNT(*) as total FROM dispatch_history")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="generate_pdf.css">
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
                <a href="generate_pdf.php" class="nav-item-index active"><i class="fa-solid fa-file-pdf"></i> Generate PDF</a>
                <a href="logs.php" class="nav-item-index"><i class="fa-solid fa-clock-rotate-left"></i> Logs</a>
                <div class="nav-divider-index"></div>
                <a href="user_mgmt.php" class="nav-item-index"><i class="fa-solid fa-user-gear"></i> User Management</a>
                <a href="settings.php" class="nav-item-index"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="logout.php" class="nav-item-index logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <h1><i class="fa-solid fa-file-pdf"></i> Report Generation Center</h1>
                <div class="current-user">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 
                    (<small><?php echo $_SESSION['role']; ?></small>)</span>
                </div>
            </header>

            <section class="content-container">
                <div class="page-header">
                    
                    <p>Select a category below to generate and download a real-time PDF report.</p>
                </div>

                <div class="report-grid">
                    <div class="report-card">
                        <div class="report-icon stock"><i class="fa-solid fa-boxes-stacked"></i></div>
                        <h3>Inventory Summary</h3>
                        <p>List of all <?php echo $total_items; ?> stock items.</p>
                        <button class="btn-pdf" onclick="openPreview('stock')">Generate PDF</button>
                    </div>

                    <div class="report-card">
                        <div class="report-icon alerts"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <h3>Low Stock Report</h3>
                        <p>Items below reorder levels.</p>
                        <button class="btn-pdf" onclick="openPreview('alerts')">Generate PDF</button>
                    </div>

                    <div class="report-card">
                        <div class="report-icon outbound"><i class="fa-solid fa-plane-departure"></i></div>
                        <h3>Dispatch History</h3>
                        <p>Full fulfillment history log.</p>
                        <button class="btn-pdf" onclick="openPreview('outbound')">Generate PDF</button>
                    </div>

                    <div class="report-card">
                        <div class="report-icon inbound"><i class="fa-solid fa-truck-moving"></i></div>
                        <h3>In-Transit Report</h3>
                        <p><?php echo $in_transit; ?> shipments currently in transit.</p>
                        <button class="btn-pdf" onclick="openPreview('inbound')">Generate PDF</button>
                    </div>

                </div>
            </section>
        </main>
    </div>

    <div id="modalOverlay" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="modalTitle">Report Preview</h3>
                <div style="display:flex; gap:10px;">
                    <button class="btn-modal-print" onclick="printReport()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
                    <button class="btn-modal-close" onclick="closePreview()">Close</button>
                </div>
            </div>
            <div class="modal-body">
                <iframe id="previewFrame" src=""></iframe>
            </div>
        </div>
    </div>

    <script>
        function openPreview(type) {
            const overlay = document.getElementById('modalOverlay');
            const frame = document.getElementById('previewFrame');
            const title = document.getElementById('modalTitle');
            
            title.innerHTML = '<i class="fa-solid fa-file-lines"></i> Preview: ' + type.toUpperCase() + ' REPORT'; 
            frame.src = 'generate_pdf.php?render_preview=' + type;
            overlay.style.display = 'flex';
        }

        function closePreview() {
            document.getElementById('modalOverlay').style.display = 'none';
            document.getElementById('previewFrame').src = '';
        }

        function printReport() {
            const frame = document.getElementById('previewFrame');
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }
 
        window.onclick = function(e) {
            if (e.target == document.getElementById('modalOverlay')) closePreview();
        }
    </script>
</body>
</html>