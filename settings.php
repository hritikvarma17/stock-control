<?php
include('auth_check.php');
$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'stock_control';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="settings.css"> 
    <script src="theme-loader.js"></script>
</head>
<body>
    <div class="layout-index">
        <aside class="sidebar-index" id="sidebar">
            <div class="brand-index"><i class="fa-solid fa-box-archive logo-icon"></i>&nbsp;<span>Stock Control</span></div>
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
                <a href="user_mgmt.php" class="nav-item-index"><i class="fa-solid fa-user-gear"></i> User Management</a>
                <a href="settings.php" class="nav-item-index active"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="logout.php" class="nav-item-index logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar" id="topBar">
                <div class="page-title"><h2><i class="fa-solid fa-gear"></i> Settings</h2></div>
                <div class="current-user">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 
                    (<small><?php echo $_SESSION['role']; ?></small>)</span>
                </div>
            </header>

            <div class="settings-container">
                <div class="settings-card">
                    <h3><i class="fa-solid fa-palette"></i> Interface Appearance</h3>
                    <p style="color: #64748b; font-size: 0.9rem;">Settings are saved automatically to your browser.</p>
                    
                    <div class="theme-grid">
                        <div class="theme-option" onclick="applyTheme('theme-dark')">
                            <strong>Midnight (Default)</strong>
                            <div style="font-size: 0.8rem; color: #64748b;">Dark sidebar, White top-bar</div>
                        </div>
                        <div class="theme-option" onclick="applyTheme('theme-light')">
                            <strong>Snow White</strong>
                            <div style="font-size: 0.8rem; color: #64748b;">Light sidebar, White top-bar</div>
                        </div>
                        <div class="theme-option" onclick="applyTheme('theme-ocean')">
                            <strong>Ocean Deep</strong>
                            <div style="font-size: 0.8rem; color: #64748b;">Blue sidebar, Blue top-bar</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // function applyTheme(themeName) { 
        //     document.body.classList.remove('theme-dark', 'theme-light', 'theme-ocean');
             
        //     document.body.classList.add(themeName);
             
        //     localStorage.setItem('app-theme', themeName);
        // }
        
        function applyTheme(themeName) { 
            // Remove all possible themes from both HTML and Body
            const themes = ['theme-dark', 'theme-light', 'theme-ocean'];
            document.documentElement.classList.remove(...themes);
            document.body.classList.remove(...themes);
            
            // Add the new theme
            document.documentElement.classList.add(themeName);
            document.body.classList.add(themeName);
            
            // Save selection
            localStorage.setItem('app-theme', themeName);
        }
    </script>
</body>
</html>