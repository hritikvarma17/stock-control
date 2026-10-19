<?php
session_start();
$host = 'localhost';
$user = 'root'; 
$pass = '';     
$dbname = 'Stock_Control'; 
$conn = new mysqli($host, $user, $pass, $dbname);

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $pin = $_POST['pin']; // New input field
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    // Verify Username AND the Secret PIN
    $stmt = $conn->prepare("SELECT UserID FROM user_dtl WHERE Username = ? AND ResetPIN = ? AND Status = 'Active'");
    $stmt->bind_param("ss", $username, $pin);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Only update if PIN matches
        $update = $conn->prepare("UPDATE user_dtl SET PasswordHash = ? WHERE Username = ?");
        $update->bind_param("ss", $new_password, $username);
        if ($update->execute()) {
            $message = "Password updated successfully!";
        }
    } else {
        $error = "Invalid Username or Secret PIN.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | Stock Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="log_in.css"> </head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Reset Password</h1>
                <p>Enter your username to set a new password.</p>
            </div>

            <?php if($message): ?>
                <div style="background: #dcfce7; color: #166534; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; text-align: center;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; text-align: center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="forgot_password.php" method="POST" class="login-form">
                <div class="input-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="username" placeholder="Confirm your username" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>New Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-key"></i>
                        <input type="password" name="new_password" placeholder="Enter new password" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>Secret Reset PIN</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-key"></i>
                        <input type="password" name="pin" maxlength="6" placeholder="Enter your 6-digit PIN" required>
                    </div>
                </div>
                <button type="submit" class="btn-login">Update Password</button>
            </form>
            <div class="login-footer">
                <p><a href="log_in.php" style="color: #3b82f6; text-decoration: none;">Back to Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>