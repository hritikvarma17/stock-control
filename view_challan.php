<?php 
$order_id = $_GET['order_id'];
$stmt = $conn->prepare("SELECT * FROM dispatch_archives WHERE OrderID = ?");
$stmt->bind_param("s", $order_id); $stmt->execute();
$data = $stmt->get_result()->fetch_assoc(); 
?>