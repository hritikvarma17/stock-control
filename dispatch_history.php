<?php 
$host = 'localhost';
$user = 'root'; 
$pass = '';     
$dbname = 'stock_control';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
$sql = "SELECT ArchiveID, OrderID, RecipientName, Location, DispatchDate 
        FROM dispatch_archives 
        ORDER BY ArchiveID DESC";  
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dispatch History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; }
  
        .header-container { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px; 
            background: white; 
            padding: 15px 25px; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .btn-back { 
            background: #6c757d; 
            color: white; 
            text-decoration: none; 
            padding: 8px 15px; 
            border-radius: 5px; 
            font-size: 14px; 
            transition: 0.3s;
        }
        
        .btn-back:hover { background: #5a6268; }

        .history-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .history-table th { background: #212529; color: white; padding: 12px; text-align: left; }
        .history-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .btn-view { background: #0d6efd; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>

    <div class="header-container">
        <h2 style="margin:0;"><i class="fa-solid fa-history"></i> Dispatch History</h2>
        <a href="dispatch_items.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Dispatch
        </a>
    </div>

    <table class="history-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Recipient</th>
                <th>Location</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo $row['OrderID']; ?></strong></td>
                <td><?php echo htmlspecialchars($row['RecipientName']); ?></td>
                <td><?php echo htmlspecialchars($row['Location']); ?></td>
                <td><?php echo date('d-M-Y', strtotime($row['DispatchDate'])); ?></td>
                <td>
                    <a href="javascript:void(0);" onclick="viewPdf('<?php echo $row['OrderID']; ?>')" class="btn-view">
                        <i class="fa-solid fa-file-pdf"></i> View Challan
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
    function viewPdf(id) {
        window.open('view_stored_pdf.php?id=' + id, '_blank', 'width=900,height=800');
    }
    </script>
</body>
</html>