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

if (!isset($_GET['id'])) {
    die("Error: No Order ID provided.");
}

$order_id = $_GET['id']; 
 
$stmt = $conn->prepare("SELECT PdfContent FROM dispatch_archives WHERE OrderID = ? LIMIT 1");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$stmt->bind_result($content);
$stmt->fetch();
$stmt->close();

if (empty($content)) {
    echo "<h2>No archived challan found for Order ID: " . htmlspecialchars($order_id) . "</h2>";
} else { 
    echo $content;
     
    echo "<script>window.onload = function() { window.print(); };</script>";
}
?>