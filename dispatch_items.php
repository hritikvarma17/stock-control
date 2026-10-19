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
$dispatch_success = false;
 
$draft_order_id = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
 
$options_html = "";
$result = $conn->query("SELECT ItemID, ItemName, Quantity FROM stock_dtl WHERE Quantity > 0 ORDER BY ItemName ASC");
while ($row = $result->fetch_assoc()) {
    $safe_name = htmlspecialchars($row['ItemName'], ENT_QUOTES);
    $options_html .= "<option value='{$row['ItemID']}'>{$safe_name} (Available: {$row['Quantity']})</option>";
}
 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dispatch_item'])) {
     
    $total_dispatch_qty = 0;  
    $item_ids = isset($_POST['item_id']) ? (array)$_POST['item_id'] : [];
    $dispatch_qtys = isset($_POST['dispatch_qty']) ? (array)$_POST['dispatch_qty'] : [];
    
    $recipient_name = htmlspecialchars($_POST['recipient_name']);
    $dispatched_by = htmlspecialchars($_POST['dispatched_by']);
    $delivery_location = htmlspecialchars($_POST['delivery_location']);
    $remarks = htmlspecialchars($_POST['remarks']);
    $dispatch_date = htmlspecialchars($_POST['dispatch_date']);
    $return_date = !empty($_POST['return_date']) ? htmlspecialchars($_POST['return_date']) : 'N/A';
    $final_order_id = htmlspecialchars($_POST['draft_order_id']);

    $success_items = []; 
    $errors = [];
    $table_rows_html = "";  
    $total_qty = 0;

    for ($i = 0; $i < count($item_ids); $i++) {
        $id = intval($item_ids[$i]);
        $qty = intval($dispatch_qtys[$i]);

        if ($id > 0 && $qty > 0) {
            $check_stmt = $conn->prepare("SELECT ItemName, Quantity FROM stock_dtl WHERE ItemID = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $res = $check_stmt->get_result();
            
            if ($row = $res->fetch_assoc()) {
                if ($row['Quantity'] >= $qty) {
                    $update_stmt = $conn->prepare("UPDATE stock_dtl SET Quantity = Quantity - ? WHERE ItemID = ?");
                    $update_stmt->bind_param("ii", $qty, $id);
                    if ($update_stmt->execute()) {
                        $success_items[] = ['name' => $row['ItemName'], 'qty' => $qty];
                        $total_dispatch_qty += $qty;
                        $total_qty += $qty;  
                        $row_num = count($success_items);
                        $table_rows_html .= "<tr>
                            <td style='text-align:center;'>$row_num</td>
                            <td><strong>{$row['ItemName']}</strong></td>
                            <td style='text-align:center;'>$qty Units</td>
                        </tr>";
 
                        $hist_stmt = $conn->prepare("INSERT INTO dispatch_history (ItemID, QtyDispatched, RecipientName, Location, DispatchDate, ReturnDate, OrderID) VALUES (?, ?, ?, ?, ?, ?, ?)");
                         
                        $hist_stmt->bind_param("iisssss", $id, $qty, $recipient_name, $delivery_location, $dispatch_date, $return_date, $final_order_id);
                        
                        $hist_stmt->execute();
                        $hist_stmt->close();
  
                    }
                    $update_stmt->close();
                } else {
                    $errors[] = "Insufficient stock for <b>{$row['ItemName']}</b>.";
                }
            }
            $check_stmt->close();
        }
    }

    $table_rows_html .= "
            <tr style='background: #eee; font-weight: bold;'>
                <td colspan='2' style='text-align: right; padding: 10px;'>GRAND TOTAL:</td>
                <td style='text-align: center; padding: 10px;'>$total_qty Units</td>
            </tr>";

    if (count($success_items) > 0) {
        $dispatch_success = true; 
        $total_dispatch_qty = 0;
        foreach ($success_items as $si) {
            $total_dispatch_qty += $si['qty'];
        }
 
        recordLog(
            $conn, 
            $_SESSION['user_id'], 
            null, 
            "Dispatch: Order #$final_order_id", 
            -$total_dispatch_qty, 
            $_SESSION['username']  
        ); 
        $full_pdf_html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; padding: 30px; color: #333; line-height: 1.5; }
                .challan-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
                .challan-header h2 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }
                .challan-header p { margin: 5px 0; font-size: 14px; }
                .details-grid { display: flex; justify-content: space-between; margin-bottom: 20px; }
                .meta-block { background: #f8f9fa; padding: 15px; border: 1px solid #eee; width: 48%; box-sizing: border-box; border-radius: 5px; }
                .meta-block p { margin: 5px 0; font-size: 13px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ccc; padding: 10px; text-align: left; font-size: 13px; }
                th { background: #f2f2f2; font-weight: bold; }
                .remarks-box { margin-top: 20px; padding: 12px; border: 1px dashed #bbb; background: #fafafa; font-size: 13px; }
                .sig-section { margin-top: 60px; display: flex; justify-content: space-between; }
                .sig-box { border-top: 1px solid #000; width: 220px; text-align: center; padding-top: 5px; font-weight: bold; font-size: 13px; }
            </style>
        </head>
        <body>
            <div class='challan-header'>
                <h2>DELIVERY CHALLAN</h2>
                <p><strong>Stock Control System Inc.</strong><br>Warehouse Road, Ahmedabad</p>
            </div>
            
            <div class='details-grid'>
                <div class='meta-block'>
                    <p><strong>Order ID:</strong> $final_order_id</p>
                    <p><strong>Dispatch Date:</strong> $dispatch_date</p>
                    <p><strong>Est. Return:</strong> $return_date</p>
                    <p><strong>Dispatched By:</strong> $dispatched_by</p>
                </div>
                <div class='meta-block'>
                    <p><strong>Billed To / Recipient:</strong></p>
                    <p><strong>$recipient_name</strong></p>
                    <p style='margin-top:5px;'><strong>Delivery Location:</strong><br>$delivery_location</p>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style='width:10%; text-align:center;'>#</th>
                        <th style='width:65%;'>Item Description</th>
                        <th style='width:25%; text-align:center;'>Qty</th>
                    </tr>
                </thead>
                <tbody>
                    $table_rows_html
                </tbody>
            </table>

            <div class='remarks-box'>
                <strong>Remarks / Special Instructions:</strong><br>
                " . nl2br($remarks) . "
            </div>

            <div class='sig-section'>
                <div class='sig-box'>Authorized Signature<br><small>($dispatched_by)</small></div>
                <div class='sig-box'>Receiver's Signature<br><small>(Seal & Date)</small></div>
            </div>
        </body>
        </html>";
 
        $summary = count($success_items) . " items dispatched to " . $recipient_name;
        $arch_stmt = $conn->prepare("INSERT INTO dispatch_archives (OrderID, RecipientName, Location, DispatchDate, DataSummary, PdfContent) VALUES (?, ?, ?, ?, ?, ?)");

        if ($arch_stmt === false) {
            die("Prepare failed: " . $conn->error);
        }

        $arch_stmt->bind_param("ssssss", $final_order_id, $recipient_name, $delivery_location, $dispatch_date, $summary, $full_pdf_html);

        if (!$arch_stmt->execute()) { 
            die("Execute failed: " . $arch_stmt->error); 
        }
        $arch_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispatch Items</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="dispatch_items.css"> 
    <link rel="stylesheet" href="settings.css">   
    <script src="theme-loader.js"></script>
</head>
<body>
    <div class="layout-index">
        <aside class="sidebar-index no-print">
            <div class="brand-index">
                <i class="fa-solid fa-box-archive logo-icon"></i>&nbsp;
                <span>Stock Control</span>
            </div>
            <nav class="menu-index">
                <a href="index.php" class="nav-item-index"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="stock_management.php" class="nav-item-index"><i class="fa-solid fa-boxes-stacked"></i> Stock Management</a>
                <a href="stock_alerts.php" class="nav-item-index"><i class="fa-solid fa-triangle-exclamation"></i> Stock Alerts</a>
                <a href="dispatch_items.php" class="nav-item-index active"><i class="fa-solid fa-truck-fast"></i> Dispatch</a>
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
                <h1><i class="fa-solid fa-truck-fast"></i> Dispatch Items</h1>
                <div class="current-user">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 
                    (<small><?php echo $_SESSION['role']; ?></small>)</span>
                </div>
            </header>

            <section class="content-container">
                <div class="page-header no-print">
                    
                    <a href="dispatch_history.php" class="nav-item-index" style="background-color: #6c757d; color: white; text-decoration: none; padding: 10px; border-radius: 5px; display: inline-block; margin-top: 10px;">
                        <i class="fa-solid fa-clock-rotate-left"></i> Dispatch History
                    </a>
                </div>

                <div class="split-layout">
                    
                    <div class="left-panel no-print">
                        <?php echo $msg; ?>

                        <?php if ($dispatch_success): ?>
                            <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Print / Save Challan as PDF</button>
                            <a href="dispatch_items.php" class="btn-new"><i class="fa-solid fa-plus"></i> Start New Dispatch</a>
                        <?php else: ?>
                            <form method="POST" action="">
                                <input type="hidden" name="draft_order_id" value="<?php echo $draft_order_id; ?>">
                                
                                <div class="item-section">
                                    <h3 style="margin-top:0; margin-bottom: 15px; border-bottom: 2px solid #ddd; padding-bottom: 5px;">Items to Dispatch</h3>
                                    
                                    <div id="dynamic-items-container">
                                        <div class="form-grid-2 dynamic-row">
                                            <div class="form-group" style="margin-bottom:0;">
                                                <select name="item_id[]" required onchange="updatePreview()">
                                                    <option value="">-- Choose Item --</option>
                                                    <?php echo $options_html; ?>
                                                </select>
                                            </div>
                                            <div class="form-group" style="margin-bottom:0;">
                                                <input type="number" name="dispatch_qty[]" min="1" required placeholder="Qty" oninput="updatePreview()">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="button" class="btn-add-row" onclick="addDynamicRow()">
                                        <i class="fa-solid fa-plus"></i> Add Another Item
                                    </button>
                                </div>

                                <div class="form-grid-half">
                                    <div class="form-group">
                                        <label>Dispatched By:</label>
                                        <input type="text" id="inp_dispatched_by" name="dispatched_by" value="" required oninput="updatePreview()">
                                    </div>
                                    <div class="form-group">
                                        <label>Recipient Name:</label>
                                        <input type="text" id="inp_recipient" name="recipient_name" required placeholder="e.g. Acme Corp" oninput="updatePreview()">
                                    </div>
                                </div>

                                <div class="form-grid-half">
                                    <div class="form-group">
                                        <label>Dispatch Date:</label>
                                        <input type="date" id="inp_date" name="dispatch_date" value="<?php echo date('Y-m-d'); ?>" required oninput="updatePreview()">
                                    </div>
                                    <div class="form-group">
                                        <label>Est. Return Date:</label>
                                        <input type="date" id="inp_return" name="return_date" oninput="updatePreview()">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Delivery Location:</label>
                                    <textarea id="inp_location" name="delivery_location" rows="2" required placeholder="Destination address..." oninput="updatePreview()"></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Remarks / Notes:</label>
                                    <textarea id="inp_remarks" name="remarks" rows="2" placeholder="Special instructions..." oninput="updatePreview()"></textarea>
                                </div>

                                <button type="submit" name="dispatch_item" class="btn-submit">
                                    <i class="fa-solid fa-paper-plane"></i> Finalize Dispatch & Generate PDF
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="right-panel">
                        <div class="challan-wrapper" id="challanSection">
                            <?php if (!$dispatch_success): ?>
                                <div class="no-print" style="text-align:center; color:#dc3545; font-weight:bold; margin-bottom:10px; border:1px solid #dc3545; padding:5px;">DRAFT PREVIEW</div>
                            <?php endif; ?>

                            <div class="challan-header">
                                <h2>Delivery Challan</h2>
                                <p><strong>Stock Control System Inc.</strong><br>Warehouse Road, Ahmedabad</p>
                            </div>

                            <div class="challan-details">
                                <div class="meta-block">
                                    <p><strong>Order ID:</strong> <span style="color:#0d6efd; font-weight:bold;"><?php echo isset($final_order_id) ? $final_order_id : $draft_order_id; ?></span></p>
                                    <p><strong>Dispatch Date:</strong> <span id="prev_date"><?php echo isset($dispatch_date) ? $dispatch_date : date('d M Y'); ?></span></p>
                                    <p><strong>Est. Return:</strong> <span id="prev_return"><?php echo isset($return_date) ? $return_date : 'N/A'; ?></span></p>
                                    <p><strong>Dispatched By:</strong> <span id="prev_dispatched_by"><?php echo isset($dispatched_by) ? $dispatched_by : ''; ?></span></p>
                                </div>
                                
                                <div class="meta-block">
                                    <p><strong>Billed To / Recipient:</strong></p>
                                    <p><strong id="prev_recipient"><?php echo isset($recipient_name) ? $recipient_name : '[Unknown]'; ?></strong></p>
                                    <p style="margin-top:5px;"><strong>Delivery Location:</strong><br>
                                       <span id="prev_location"><?php echo isset($delivery_location) ? nl2br($delivery_location) : '[Unknown]'; ?></span>
                                    </p>
                                </div>
                            </div>

                            <table class="challan-table">
                                <thead>
                                    <tr>
                                        <th style="width: 10%; text-align: center;">#</th>
                                        <th style="width: 60%;">Item Description</th>
                                        <th style="width: 30%; text-align: center;">Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="prev_items_table">
                                    <?php 
                                    if ($dispatch_success) {
                                        $count = 1;
                                        foreach ($success_items as $item) {
                                            echo "<tr>
                                                    <td style='text-align: center;'>{$count}</td>
                                                    <td><strong>{$item['name']}</strong></td>
                                                    <td style='text-align: center;'>{$item['qty']} Units</td>
                                                </tr>";
                                            $count++;
                                        }
                                    } else { 
                                        echo "<tr><td colspan='3' style='text-align:center; color:#888;'>Select items to preview...</td></tr>";
                                    }
                                    ?>
                                </tbody>
                                <tfoot id="prev_total_footer" style="<?php echo !$dispatch_success ? 'display:none;' : ''; ?>">
                                    <tr style="background: #f8f9fa; font-weight: bold; border-top: 2px solid #333;">
                                        <td colspan="2" style="text-align: right; padding: 12px;">GRAND TOTAL:</td>
                                        <td style="text-align: center; padding: 12px;" id="prev_grand_total">
                                            <?php echo isset($total_qty) ? $total_qty . " Units" : "0 Units"; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="remarks-box" id="prev_remarks_box" style="<?php echo empty($remarks) && !$dispatch_success ? 'display:none;' : ''; ?>">
                                <strong>Remarks / Special Instructions:</strong><br>
                                <span id="prev_remarks"><?php echo isset($remarks) ? nl2br($remarks) : ''; ?></span>
                            </div>

                            <div class="signatures">
                                <div class="sign-box">Authorized Signature<br><small>(<span id="prev_sign_name"><?php echo isset($dispatched_by) ? $dispatched_by : ''; ?></span>)</small></div>
                                <div class="sign-box">Receiver's Signature<br><small>(Seal & Date)</small></div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </main>
    </div>

    <script>
        const itemOptions = `<?php echo $options_html; ?>`;
        const dispatchSuccess = <?php echo $dispatch_success ? 'true' : 'false'; ?>;

        function addDynamicRow() {
            const container = document.getElementById('dynamic-items-container');
            const rowDiv = document.createElement('div');
            rowDiv.className = 'form-grid-2 dynamic-row';
            rowDiv.style.marginTop = '10px';
            
            rowDiv.innerHTML = `
                <div class="form-group" style="margin-bottom:0;">
                    <select name="item_id[]" required onchange="updatePreview()">
                        <option value="">-- Choose Item --</option>
                        ${itemOptions}
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0; display:flex; gap:10px;">
                    <input type="number" name="dispatch_qty[]" min="1" required placeholder="Qty" style="flex-grow:1;" oninput="updatePreview()">
                    <button type="button" class="btn-remove" onclick="removeRow(this)" title="Remove Item">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(rowDiv);
            updatePreview();
        }

        function removeRow(btn) {
            btn.parentElement.parentElement.remove();
            updatePreview();
        }
 
        function updatePreview() { 
            if (dispatchSuccess) return; 
 
            document.getElementById('prev_dispatched_by').innerText = document.getElementById('inp_dispatched_by').value || '';
            document.getElementById('prev_sign_name').innerText = document.getElementById('inp_dispatched_by').value || '';
            document.getElementById('prev_recipient').innerText = document.getElementById('inp_recipient').value || '[Unknown]';
            document.getElementById('prev_date').innerText = document.getElementById('inp_date').value || 'N/A';
            document.getElementById('prev_return').innerText = document.getElementById('inp_return').value || 'N/A';
            document.getElementById('prev_location').innerHTML = document.getElementById('inp_location').value.replace(/\n/g, "<br>") || '[Unknown]';

            const rows = document.querySelectorAll('.dynamic-row');
            let tableHTML = '';
            let count = 1;
            let grandTotal = 0;

            rows.forEach(row => {
                const select = row.querySelector('select');
                const qtyInput = row.querySelector('input[type="number"]');
                const qty = parseInt(qtyInput.value) || 0;
                
                if (select.selectedIndex > 0) {
                    let fullText = select.options[select.selectedIndex].text;
                    let itemName = fullText.split(' (Available:')[0];

                    tableHTML += `<tr>
                                    <td style='text-align: center;'>${count}</td>
                                    <td><strong>${itemName}</strong></td>
                                    <td style='text-align: center;'>${qty} Units</td>
                                </tr>`;
                    count++;
                    grandTotal += qty;
                }
            });

            if (tableHTML === '') {
                tableHTML = "<tr><td colspan='3' style='text-align:center; color:#888;'>Select items to preview...</td></tr>";
                document.getElementById('prev_total_footer').style.display = 'none';
            } else {
                document.getElementById('prev_total_footer').style.display = 'table-footer-group';
                document.getElementById('prev_grand_total').innerText = grandTotal + " Units";
            }
            
            document.getElementById('prev_items_table').innerHTML = tableHTML;
        }
 
        if(!dispatchSuccess) updatePreview();
    </script>
</body>
</html>