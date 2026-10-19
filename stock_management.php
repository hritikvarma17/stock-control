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

// DELETE
if (isset($_GET['delete_id'])) {
    if ($_SESSION['role'] == 'Viewer') {
        header("Location: stock_management.php?msg=denied");
        exit;
    }
    $delete_id = intval($_GET['delete_id']);
    
    $conn->begin_transaction();

    try {
        $delete_logs = $conn->prepare("DELETE FROM logs_dtl WHERE ItemID = ?");
        $delete_logs->bind_param("i", $delete_id);
        $delete_logs->execute();
        $delete_logs->close();

        $delete_item = $conn->prepare("DELETE FROM stock_dtl WHERE ItemID = ?");
        $delete_item->bind_param("i", $delete_id);
        $delete_item->execute();
        $delete_item->close();

        $conn->commit();
        header("Location: stock_management.php?msg=deleted");
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error deleting record: " . $e->getMessage();
    }
    exit;
}

// ADD / UPDATE 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_item'])) {
    if ($_SESSION['role'] == 'Viewer') {
        header("Location: stock_management.php?msg=denied");
        exit;
    }
    $id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $item_name = $_POST['item_name'];
    $category = $_POST['item_category'];
    $qty = intval($_POST['item_qty']);
    $min_qty = intval($_POST['item_min_qty']);
    $location = $_POST['item_location'];
    $remarks = $_POST['item_remarks'];

    $imageContent = null;
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == UPLOAD_ERR_OK) {
        $imageContent = file_get_contents($_FILES['item_image']['tmp_name']);
    }

    if ($id > 0) {
        if ($imageContent !== null) {
            $stmt = $conn->prepare("UPDATE stock_dtl SET ItemName=?, Category=?, Quantity=?, ReorderLevel=?, Location=?, Remarks=?, ItemImage=? WHERE ItemID=?");
            $stmt->bind_param("ssiisssi", $item_name, $category, $qty, $min_qty, $location, $remarks, $imageContent, $id);
        } else {
            $stmt = $conn->prepare("UPDATE stock_dtl SET ItemName=?, Category=?, Quantity=?, ReorderLevel=?, Location=?, Remarks=? WHERE ItemID=?");
            $stmt->bind_param("ssiissi", $item_name, $category, $qty, $min_qty, $location, $remarks, $id);
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO stock_dtl (ItemName, Category, Quantity, ReorderLevel, Location, Remarks, ItemImage) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiisss", $item_name, $category, $qty, $min_qty, $location, $remarks, $imageContent);
    }
    
    // EXECUTE the item save first
    if ($stmt->execute()) {

        $actionType = ($id > 0) ? "Stock Updated: $item_name" : "New Item Added: $item_name";
        
        $final_id = ($id > 0) ? $id : $stmt->insert_id;
        
        recordLog($conn, $_SESSION['user_id'], $final_id, $actionType, $qty, $_SESSION['username']);

        $stmt->close();
        header("Location: stock_management.php");
        exit;
    } else {
        die("Error updating stock: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="stock_management.css">
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
                <a href="stock_management.php" class="nav-item-index active"><i class="fa-solid fa-boxes-stacked"></i> Stock Management</a>
                <a href="stock_alerts.php" class="nav-item-index"><i class="fa-solid fa-triangle-exclamation"></i> Stock Alerts</a>
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
            <h1><i class="fa-solid fa-boxes-stacked"></i> Stock Management</h1>    
            
                <div class="current-user">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 
                    (<small><?php echo $_SESSION['role']; ?></small>)</span>
                </div>
            </header>

            <section class="content-container">
                <?php if (isset($_GET['msg'])): ?>
                    <?php if ($_GET['msg'] == 'denied'): ?>
                        <div style="background: #fdf2f2; color: #9b1c1c; padding: 15px; border-radius: 8px; border: 1px solid #fbd5d5; margin-bottom: 20px;">
                            <i class="fa-solid fa-circle-xmark"></i> <strong>Access Denied:</strong> Viewers are not allowed to add, edit, or delete items.
                        </div>
                    <?php elseif ($_GET['msg'] == 'success'): ?>
                        <div style="background: #f0fdf4; color: #166534; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 20px;">
                            <i class="fa-solid fa-circle-check"></i> Stock updated successfully!
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="page-header">
                    <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" onkeyup="searchStock()" placeholder="Search by item name or category...">
                </div>
                    <?php if ($_SESSION['role'] !== 'Viewer'): ?>
                        <button class="btn-add" onclick="openModalForAdd()">
                            <i class="fa-solid fa-plus"></i> Add New Item
                        </button>
                    <?php endif; ?>
                </div>

                <div class="table-wrapper">
                    <table class="stock-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Min. Qty</th>
                                <th>Location</th>
                                <th>Remarks</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            $search = "";
                            $whereClause = "";

                            if (isset($_POST['search_query']) && !empty($_POST['search_query'])) {
                                $search = $conn->real_escape_string($_POST['search_query']);

                                $whereClause = " WHERE s.ItemName LIKE '%$search%' OR s.Category LIKE '%$search%' ";
                            }

                            $sql = "SELECT s.*, 
                                    GROUP_CONCAT(
                                        CONCAT(dh.QtyDispatched, '|', dh.Location, '|', dh.DispatchDate) 
                                        SEPARATOR ';;'
                                    ) as all_dispatches
                                    FROM stock_dtl s 
                                    LEFT JOIN dispatch_history dh ON s.ItemID = dh.ItemID 
                                    $whereClause 
                                    GROUP BY s.ItemID
                                    ORDER BY s.ItemID DESC";

                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $img_src = 'https://via.placeholder.com/50';
                                    $has_image = false;
                                    if (!empty($row['ItemImage'])) {
                                        $base64 = base64_encode($row['ItemImage']);
                                        $img_src = 'data:image/jpeg;base64,' . $base64;
                                        $has_image = true;
                                    }
                                    
                                    $json_data_array = $row;
                                    unset($json_data_array['ItemImage']); 
                                    $json_data_array['has_image'] = $has_image;
                                    $json_data = htmlspecialchars(json_encode($json_data_array), ENT_QUOTES, 'UTF-8');

                                    echo "<tr>";
                                    echo "<td>" . $row['ItemID'] . "</td>";
                                    echo "<td><img src='" . $img_src . "' alt='item' class='img-preview' style='width:50px; height:50px; object-fit:cover; border-radius:4px;'></td>";
                                    echo "<td><div class='item-primary'>" . htmlspecialchars($row['ItemName']) . "</div></td>";
                                    echo "<td><span class='category-label'>" . htmlspecialchars($row['Category']) . "</span></td>";
                                    echo "<td><span class='qty-count' style='color:#0d6efd; font-weight:bold;'>" . $row['Quantity'] . "</span></td>";
                                    echo "<td><span class='min-qty' style='color:#dc3545; font-weight:bold;'>" . $row['ReorderLevel'] . "</span></td>";
                                    echo "<td>" . htmlspecialchars($row['Location'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['Remarks'] ?? '') . "</td>";
                                    echo "<td class='time-label'>" . date('Y-m-d', strtotime($row['LastUpdated'])) . " <br><small>" . date('H:i:s', strtotime($row['LastUpdated'])) . "</small></td>";
                                    echo "<td>";
                                    if ($_SESSION['role'] !== 'Viewer') {
                                        echo "<div class='action-btns'>
                                                <button type='button' class='edit-btn' style='background:none; border:none; color:#0d6efd; cursor:pointer;' onclick='editItem(" . $json_data . ")'><i class='fa-solid fa-pen-to-square'></i></button>
                                                <a href='stock_management.php?delete_id=" . $row['ItemID'] . "' class='delete-btn' onclick='return confirm(\"Sure, you want to delete?\")' style='color:#dc3545; margin-left:10px;'><i class='fa-solid fa-trash'></i></a>
                                            </div>";
                                    } else {
                                        echo "<span style='color: #bbb; font-style: italic;'>Read Only</span>";
                                    }
                                    echo "</td>";
                                    
                                    
                                    echo "<td>";
                                    if (!empty($row['all_dispatches'])) {
                                        echo "<div style='max-height: 80px; overflow-y: auto; font-size: 0.75rem;'>";
                                        $dispatches = explode(';;', $row['all_dispatches']);
                                        foreach ($dispatches as $entry) {
                                            $details = explode('|', $entry);
                                            $qty = $details[0];
                                            $loc = htmlspecialchars($details[1]);
                                            $date = date('d M', strtotime($details[2]));

                                            echo "<div style='border-bottom: 1px solid #eee; padding: 2px 0;'>";
                                            echo "<b>Out: {$qty}</b> to <span style='color: #0d6efd;'>{$loc}</span>";
                                            echo "<br><small style='color: #888;'>Date: {$date}</small>";
                                            echo "</div>";
                                        }
                                        echo "</div>";
                                    } else {
                                        echo "<span style='color: #bbb; font-style: italic;'>No dispatches</span>";
                                    }
                                    echo "</td>";
                                    echo "</tr>"; 
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <div id="itemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fa-solid fa-box-open"></i> Add Stock Item</h2>
                <span class="close" onclick="toggleModal(false)">&times;</span>
            </div>
            
            <form class="stock-form" id="stockForm" method="POST" action="stock_management.php" enctype="multipart/form-data">
                <input type="hidden" name="item_id" id="item_id" value="0">

                <div class="form-grid">
                    <div class="form-group full">
                        <label>Item Image</label>
                        <div class="upload-box" onclick="document.getElementById('itemImage').click()" style="cursor:pointer; border: 2px dashed #ccc; padding: 20px; text-align: center;">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p id="uploadText">Click to upload image</p>
                            <input type="file" id="itemImage" name="item_image" accept="image/*" hidden onchange="updateFileName(this)">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" id="itemName" name="item_name" placeholder="e.g. Wireless Keyboard" required>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select id="itemCategory" name="item_category" required>
                            <option value="Hardware">Hardware</option>
                            <option value="Stationary">Stationary</option>
                            <option value="Electrical">Electrical</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Current Quantity</label>
                        <input type="number" id="itemQty" name="item_qty" placeholder="0" required>
                    </div>

                    <div class="form-group">
                        <label>Min. Qty</label>
                        <input type="number" id="itemMinQty" name="item_min_qty" placeholder="10">
                    </div>

                    <div class="form-group">
                        <label>Storage Location</label>
                        <input type="text" id="itemLocation" name="item_location" placeholder="e.g. Warehouse A, Shelf 4">
                    </div>

                    <div class="form-group full">
                        <label>Remarks</label>
                        <textarea id="itemRemarks" name="item_remarks" placeholder="Add any specific notes about this item..." rows="3" style="width:100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="toggleModal(false)">Cancel</button>
                    <button type="submit" name="save_item" class="btn-save">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            if (input.files && input.files.length > 0) {
                document.getElementById('uploadText').innerText = "Selected: " + input.files[0].name;
            }
        }

        function toggleModal(show) {
            const modal = document.getElementById('itemModal');
            modal.style.display = show ? 'flex' : 'none';
        }

        function openModalForAdd() {
            document.getElementById('stockForm').reset();
            document.getElementById('item_id').value = "0";
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-box-open"></i> Add Stock Item';
            document.getElementById('uploadText').innerText = "Click to upload image";
            toggleModal(true);
        }

        function editItem(data) {
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Edit Stock Item';
            document.getElementById('item_id').value = data.ItemID;
            
            document.getElementById('itemName').value = data.ItemName;
            document.getElementById('itemCategory').value = data.Category;
            document.getElementById('itemQty').value = data.Quantity;
            document.getElementById('itemMinQty').value = data.ReorderLevel;
            document.getElementById('itemLocation').value = data.Location;
            document.getElementById('itemRemarks').value = data.Remarks;
            
            if (data.has_image) {
                document.getElementById('uploadText').innerText = "Image attached. Click to replace.";
            } else {
                document.getElementById('uploadText').innerText = "Click to upload image";
            }

            toggleModal(true);
        }

        window.onclick = function(event) {
            const modal = document.getElementById('itemModal');
            if (event.target == modal) {
                toggleModal(false);
            }
        }
        function searchStock() {
        let input = document.getElementById("searchInput").value;
        
        
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "stock_management.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                let parser = new DOMParser();
                let doc = parser.parseFromString(this.responseText, "text/html");
                let newBody = doc.querySelector(".stock-table tbody").innerHTML;
                document.querySelector(".stock-table tbody").innerHTML = newBody;
            }
        };
        
        xhr.send("search_query=" + input);
    }
    </script>
    <?php 
    if (isset($_GET['auto_edit']) && is_numeric($_GET['auto_edit'])) {
        $auto_edit_id = intval($_GET['auto_edit']);
        $auto_query = $conn->query("SELECT * FROM stock_dtl WHERE ItemID = $auto_edit_id");
        
        if ($auto_query && $auto_query->num_rows > 0) {
            $auto_row = $auto_query->fetch_assoc();
            
            
            $auto_has_image = !empty($auto_row['ItemImage']) ? true : false;
            $auto_json_array = $auto_row;
            unset($auto_json_array['ItemImage']); 
            $auto_json_array['has_image'] = $auto_has_image;
            
            $auto_json = json_encode($auto_json_array);
            
            echo "
            <script>
                window.addEventListener('DOMContentLoaded', (event) => {
                    let itemData = $auto_json;
                    editItem(itemData);
                });
            </script>
            ";
        }
    }
    ?>
    
</body>
</html>