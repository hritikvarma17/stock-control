<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: log_in.php");
    exit();
}
?>