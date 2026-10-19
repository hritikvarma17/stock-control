<?php
include('auth_check.php');
$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'stock_control';
$conn = new mysqli($host, $user, $pass, $dbname);

$data = ['kpis' => [], 'catLabels' => [], 'catData' => [], 'userLabels' => [], 'totalUsers' => [], 'lowLabels' => [], 'lowData' => [], 'transitLabels' => [], 'transitData' => []];

// KPI Fetch
$data['kpis']['total'] = $conn->query("SELECT COUNT(*) as t FROM stock_dtl")->fetch_assoc()['t'];
$data['kpis']['low'] = $conn->query("SELECT COUNT(*) as t FROM stock_dtl WHERE Quantity <= ReorderLevel")->fetch_assoc()['t'];
$data['kpis']['transit'] = $conn->query("SELECT COUNT(DISTINCT OrderID) as t FROM dispatch_history")->fetch_assoc()['t'];
$data['kpis']['users'] = $conn->query("SELECT COUNT(*) as t FROM user_dtl")->fetch_assoc()['t'];

// Categories
$cat = $conn->query("SELECT Category, COUNT(*) as c FROM stock_dtl GROUP BY Category");
while($r = $cat->fetch_assoc()){ $data['catLabels'][] = $r['Category']; $data['catData'][] = $r['c']; }

// User Roles
$roles = $conn->query("SELECT Role, COUNT(*) as total FROM user_dtl GROUP BY Role");
while($r = $roles->fetch_assoc()){ $data['userLabels'][] = $r['Role']; $data['totalUsers'][] = (int)$r['total']; }

// Top 5 Low Stock
$low = $conn->query("SELECT ItemName, Quantity FROM stock_dtl WHERE Quantity <= ReorderLevel ORDER BY Quantity ASC LIMIT 5");
while($r = $low->fetch_assoc()){ $data['lowLabels'][] = $r['ItemName']; $data['lowData'][] = $r['Quantity']; }

// Transit by Recipient
$trans = $conn->query("SELECT RecipientName, SUM(QtyDispatched) as s FROM dispatch_history GROUP BY RecipientName LIMIT 5");
while($r = $trans->fetch_assoc()){ $data['transitLabels'][] = $r['RecipientName']; $data['transitData'][] = $r['s']; }

header('Content-Type: application/json');
echo json_encode($data);