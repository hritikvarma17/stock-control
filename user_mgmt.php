<?php
include('auth_check.php'); 
$host = 'localhost';
$user = 'root'; 
$pass = '';     
$dbname = 'Stock_Control'; 

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
$isAdmin = (isset($_SESSION['role']) && stripos($_SESSION['role'], 'Admin') !== false);

$msg = "";
 
if (isset($_GET['delete_id'])) {
    if (!$isAdmin) {
        $msg = "<div style='padding:15px; background:#f8d7da; color:#842029; border-radius:8px; margin-bottom:20px;'><i class='fa-solid fa-shield-halved'></i> Access Denied.</div>";
    } else {
        $id = intval($_GET['delete_id']);
        if ($id == $_SESSION['user_id']) {
            $msg = "<div style='padding:15px; background:#f8d7da; color:#842029; border-radius:8px; margin-bottom:20px;'>You cannot delete your own account!</div>";
        } else {
            $del_stmt = $conn->prepare("DELETE FROM user_dtl WHERE UserID = ?");
            $del_stmt->bind_param("i", $id);
            $del_stmt->execute();
            $del_stmt->close();
            $msg = "<div style='padding:15px; background:#d1e7dd; color:#0f5132; border-radius:8px; margin-bottom:20px;'>User removed.</div>";
        }
    }
}
 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    if (!$isAdmin) {
        $msg = "<div style='padding:15px; background:#f8d7da; color:#842029; border-radius:8px; margin-bottom:20px;'><i class='fa-solid fa-shield-halved'></i> Unauthorized action.</div>";
    } else {
        $username = htmlspecialchars($_POST['username']);
        $role = $_POST['role'];
        $status = 'Active';
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);  
 
        $check = $conn->prepare("SELECT UserID FROM user_dtl WHERE Username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $msg = "<div style='padding:15px; background:#f8d7da; color:#842029; border-radius:8px; margin-bottom:20px;'><i class='fa-solid fa-triangle-exclamation'></i> Username already taken!</div>";
        } else { 
            $stmt = $conn->prepare("INSERT INTO user_dtl (Username, PasswordHash, Role, Status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $password, $role, $status);
            
            if ($stmt->execute()) {
                $msg = "<div style='padding:15px; background:#d1e7dd; color:#0f5132; border-radius:8px; margin-bottom:20px;'><i class='fa-solid fa-circle-plus'></i> New user account activated.</div>";
            } else {
                $msg = "<div style='padding:15px; background:#f8d7da; color:#842029; border-radius:8px; margin-bottom:20px;'>Database Error: " . $conn->error . "</div>";
            }
            $stmt->close();
        }
        $check->close();
    }
}
 
$roleFilter = isset($_POST['role_filter']) ? $_POST['role_filter'] : 'all';
$whereClause = "";

if ($roleFilter !== 'all') {
    $safeRole = $conn->real_escape_string($roleFilter);
    $whereClause = " WHERE Role = '$safeRole' ";
}
 
$users_result = $conn->query("SELECT * FROM user_dtl $whereClause ORDER BY CreatedOn DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management | Stock Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="user_mgmt.css">  
    <script src="theme-loader.js"></script>
</head>
<body>

<div class="layout-index">
    <aside class="sidebar-index">
        <div class="brand-index">
            <i class="fa-solid fa-box-archive logo-icon"></i>&nbsp;<span>Stock Control</span>
        </div>
        <nav class="menu-index">
            <a href="index.php" class="nav-item-index"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="stock_management.php" class="nav-item-index"><i class="fa-solid fa-boxes-stacked"></i> Stock Management</a>
            <a href="stock_alerts.php" class="nav-item-index"><i class="fa-solid fa-triangle-exclamation"></i> Stock Alerts</a>
            <a href="dispatch_items.php" class="nav-item-index"><i class="fa-solid fa-truck-fast"></i> Dispatch</a>
            <a href="inward_items.php" class="nav-item-index"><i class="fa-solid fa-arrow-right-to-bracket"></i> Inward</a>
            <a href="generate_barcode.php" class="nav-item-index"><i class="fa-solid fa-barcode"></i> Generate Barcode</a>
            <a href="generate_pdf.php" class="nav-item-index"><i class="fa-solid fa-file-pdf"></i> Generate PDF</a>
            <a href="logs.php" class="nav-item-index"><i class="fa-solid fa-clock-rotate-left"></i> Logs</a>
            <div class="nav-divider-index"></div>
            <a href="user_mgmt.php" class="nav-item-index active"><i class="fa-solid fa-user-gear"></i> User Management</a> 
            <a href="settings.php" class="nav-item-index"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="logout.php" class="nav-item-index logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h1 style="margin:0;"><i class="fa-solid fa-user-gear"></i> User Management</h1>
            <div class="filter-wrapper">
                <i class="fa-solid fa-filter filter-icon"></i>
                <select id="roleFilter" onchange="filterByRole()" class="permission-select">
                    <option value="all" <?php echo ($roleFilter == 'all') ? 'selected' : ''; ?>>All Permissions</option>
                    <option value="Admin" <?php echo ($roleFilter == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="Inventory Manager" <?php echo ($roleFilter == 'Inventory Manager') ? 'selected' : ''; ?>>Inventory Manager</option>
                    <option value="Viewer" <?php echo ($roleFilter == 'Viewer') ? 'selected' : ''; ?>>Viewer</option>
                </select>
            </div>
            
            <div class="current-user">
                <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 
                (<small><?php echo $_SESSION['role']; ?></small>)</span>
            </div>
        </header>

        <section class="content-container">
            <?php echo $msg; ?>

            <?php if ($isAdmin): ?>
                <div class="config-card" style="background:white; padding: 25px; border-radius: 12px; border: 1px solid var(--border-color);">
                    <div class="card-header" style="margin-bottom: 20px;">
                        <h2 style="margin:0;"><i class="fa-solid fa-user-shield" style="color: var(--primary);"></i> User Configuration</h2>
                        <p style="color:#64748b; font-size:0.9rem;">Create and manage internal system credentials.</p>
                    </div>
                    <form method="POST">
                        <div class="form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div class="input-group">
                                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.85rem;">Username</label>
                                <input type="text" name="username" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--border-color);">
                            </div>
                            <div class="input-group">
                                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.85rem;">User Role</label>
                                <select name="role" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--border-color);">
                                    <option value="Admin">Admin</option>
                                    <option value="Inventory Manager">Inventory Manager</option>
                                    <option value="Viewer">Viewer</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.85rem;">Access Password</label>
                                <input type="password" name="password" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--border-color);">
                            </div>
                            <div class="input-group btn-container" style="display:flex; align-items:flex-end;">
                                <button type="submit" name="create_user" class="btn-save-user" style="background: var(--primary); color:white; border:none; padding:11px 20px; border-radius:8px; cursor:pointer; font-weight:700; width:100%;">Create Account</button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div style="background: #eff6ff; padding: 20px; border-radius: 12px; border: 1px solid #dbeafe; color: #1e40af; margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-info"></i> <strong>View Only Mode:</strong> Only Admin can create and delete users.
                </div>
            <?php endif; ?>

            <div class="table-wrapper">
                <table class="stock-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Account Name</th>
                            <th>Permission Level</th>
                            <th>Account Status</th>
                            <th>Registration Date</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-family:monospace; color:#64748b;">#<?php echo sprintf("%03d", $user['UserID']); ?></td>
                                <td>
                                    <div class="user-identity">
                                        <div class="user-avatar"><?php echo strtoupper(substr($user['Username'], 0, 2)); ?></div>
                                        <div style="font-weight:700; color:#1e293b;"><?php echo htmlspecialchars($user['Username']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $roleClass = (stripos($user['Role'], 'Admin') !== false) ? 'admin' : 
                                                     ((stripos($user['Role'], 'Manager') !== false) ? 'manager' : 'viewer');
                                    ?>
                                    <span class="role-badge <?php echo $roleClass; ?>">
                                        <i class="fa-solid <?php echo ($roleClass == 'admin' ? 'fa-crown' : 'fa-user'); ?>"></i>
                                        <?php echo $user['Role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="status-pill">
                                        <span class="dot dot-active"></span>
                                        <?php echo $user['Status']; ?>
                                    </div>
                                </td>
                                <td class="time-info">
                                    <i class="fa-regular fa-calendar" style="font-size:11px;"></i> <?php echo date('d M, Y', strtotime($user['CreatedOn'])); ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php if ($isAdmin): ?>
                                        <button class="btn-delete-icon" onclick="confirmDelete(<?php echo $user['UserID']; ?>)" title="Revoke Access">
                                            <i class="fa-solid fa-user-xmark"></i>
                                        </button>
                                    <?php else: ?>
                                        <i class="fa-solid fa-lock" style="color:#cbd5e1;" title="Access Restricted"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
    function filterByRole() {
        let role = document.getElementById("roleFilter").value;
        
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "user_mgmt.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                let parser = new DOMParser();
                let doc = parser.parseFromString(this.responseText, "text/html");
                let newRows = doc.querySelector(".stock-table tbody").innerHTML;
                document.querySelector(".stock-table tbody").innerHTML = newRows;
            }
        };
        xhr.send("role_filter=" + role);
    }

    function confirmDelete(id) {
        if (confirm("Are you sure you want to revoke system access for this user?")) {
            window.location.href = "user_mgmt.php?delete_id=" + id;
        }
    }
</script>
</body>
</html>