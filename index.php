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
    <title>Dashboard | Stock Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="settings.css">  
    <script src="theme-loader.js"></script>
</head>
<body>
    <div class="layout-index" style="display: flex;">
        <aside class="sidebar-index">
            <div class="brand-index"><i class="fa-solid fa-box-archive logo-icon"></i>&nbsp;<span>Stock Control</span></div>
            <nav class="menu-index">
                <a href="index.php" class="nav-item-index active"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="stock_management.php" class="nav-item-index"><i class="fa-solid fa-boxes-stacked"></i> Stock Management</a>
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
                <div class="page-title"><h2><i class="fa-solid fa-chart-line"></i> Operations Dashboard</h2></div>
                <div class="user-profile">
                    <i class="fa-solid fa-circle-user"></i>
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    <small style="color:#94a3b8; margin-left:5px;">(<?php echo $_SESSION['role']; ?>)</small>
                </div>
            </header>

            <div class="dashboard-container">
                <div class="dashboard-grid">
                    <div class="kpi-card" id="kpi-total" onclick="location.href='stock_management.php'"><div class="kpi-icon blue"><i class="fa-solid fa-layer-group"></i></div><div class="kpi-info"><h3>Total Items</h3><span>0</span></div></div>
                    <div class="kpi-card" id="kpi-low" onclick="location.href='stock_alerts.php'"><div class="kpi-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="kpi-info"><h3>Low Stock</h3><span>0</span></div></div>
                    <div class="kpi-card" id="kpi-transit" onclick="location.href='inward_items.php'"><div class="kpi-icon orange"><i class="fa-solid fa-truck-moving"></i></div><div class="kpi-info"><h3>In Transit</h3><span>0</span></div></div>
                    <div class="kpi-card" id="kpi-users" onclick="location.href='user_mgmt.php'"><div class="kpi-icon green"><i class="fa-solid fa-user-shield"></i></div><div class="kpi-info"><h3>Users</h3><span>0</span></div></div>
                </div>

                <div class="chart-grid">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h2><i class="fa-solid fa-chart-pie"></i> Category Distribution</h2>
                            <a href="stock_management.php" class="btn-view-all">View Inventory</a>
                        </div>
                        <div style="height: 300px;"><canvas id="categoryChart"></canvas></div>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-header">
                            <h2><i class="fa-solid fa-arrow-trend-down"></i> Low-Stock Ranking</h2>
                            <a href="stock_alerts.php" class="btn-view-all">View Alerts</a>
                        </div>
                        <div style="height: 300px;"><canvas id="lowStockChart"></canvas></div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-header">
                            <h2><i class="fa-solid fa-truck-fast"></i> In-Transit Distribution</h2>
                            <a href="inward_items.php" class="btn-view-all">Track Shipments</a>
                        </div>
                        <div style="height: 300px;"><canvas id="transitChart"></canvas></div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-header">
                            <h2><i class="fa-solid fa-user-gear"></i> User Role Distribution</h2>
                            <a href="user_mgmt.php" class="btn-view-all">Manage Users</a>
                        </div>
                        <div style="height: 300px;"><canvas id="userChart"></canvas></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let charts = {};
        const opt = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } } };

        async function updateDashboard() {
            try {
                const res = await fetch('dashboard_data.php'); const data = await res.json();
                document.querySelector('#kpi-total span').innerText = data.kpis.total;
                document.querySelector('#kpi-low span').innerText = data.kpis.low;
                document.querySelector('#kpi-transit span').innerText = data.kpis.transit;
                document.querySelector('#kpi-users span').innerText = data.kpis.users;

                charts.category.data.labels = data.catLabels; charts.category.data.datasets[0].data = data.catData; charts.category.update();
                charts.low.data.labels = data.lowLabels; charts.low.data.datasets[0].data = data.lowData; charts.low.update();
                charts.transit.data.labels = data.transitLabels; charts.transit.data.datasets[0].data = data.transitData; charts.transit.update();
                charts.user.data.labels = data.userLabels; charts.user.data.datasets[0].data = data.totalUsers; charts.user.update();
            } catch (e) { console.error(e); }
        }

        window.onload = () => {
            charts.category = new Chart(document.getElementById('categoryChart'), { type: 'doughnut', data: { labels: [], datasets: [{ data: [], backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'], borderWidth: 0 }] }, options: { ...opt, cutout: '70%' } });
            charts.low = new Chart(document.getElementById('lowStockChart'), { type: 'line', data: { labels: [], datasets: [{ label: 'Remaining', data: [], borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', fill: true, tension: 0.4 }] }, options: opt });
            charts.transit = new Chart(document.getElementById('transitChart'), { type: 'bar', data: { labels: [], datasets: [{ label: 'Units', data: [], backgroundColor: '#f59e0b', borderRadius: 4 }] }, options: opt });
            charts.user = new Chart(document.getElementById('userChart'), { type: 'pie', data: { labels: [], datasets: [{ data: [], backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'], borderWidth: 0 }] }, options: opt });
            
            updateDashboard(); setInterval(updateDashboard, 5000);
        };
    </script>
</body>
</html>