<?php
include 'db.php'; 

if (isset($_GET['dispatch_id']) && isset($_GET['item_id']) && isset($_GET['qty'])) {
    $dispatch_id = intval($_GET['dispatch_id']);
    $item_id = intval($_GET['item_id']);
    $qty = intval($_GET['qty']);
 
    $conn->begin_transaction();

    try { 
        $update_sql = "UPDATE stock_dtl SET Quantity = Quantity + ? WHERE ItemID = ?";
        $stmt1 = $conn->prepare($update_sql);
        $stmt1->bind_param("ii", $qty, $item_id);
        $stmt1->execute();
 
        $delete_sql = "DELETE FROM dispatch_history WHERE DispatchID = ?";
        $stmt2 = $conn->prepare($delete_sql);
        $stmt2->bind_param("i", $dispatch_id);
        $stmt2->execute();

        $conn->commit();
        header("Location: inward_items.php?msg=success");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: inward_items.php?msg=error");
    }
}
?>