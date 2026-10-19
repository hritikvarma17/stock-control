<?php
session_start();
$host = 'localhost';
$user = 'root'; 
$pass = '';     
$dbname = 'Stock_Control'; 

$conn = new mysqli($host, $user, $pass, $dbname);

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT UserID, Username, PasswordHash, Role FROM user_dtl WHERE Username = ? AND Status = 'Active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user_data = $result->fetch_assoc()) {

        if (password_verify($password, $user_data['PasswordHash'])) {

            $_SESSION['user_id'] = $user_data['UserID'];
            $_SESSION['username'] = $user_data['Username'];
            $_SESSION['role'] = $user_data['Role'];

            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Account not found or inactive.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Stock Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="log_in.css">
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-wrapper"><i class="fa-solid fa-box-archive"></i></div>
                <h1>Stock Control</h1>
                <p>Please enter your details to sign in.</p>
            </div>

            <?php if($error): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; text-align: center;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="log_in.php" method="POST" class="login-form">
                <div class="input-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="username" id="username" placeholder="Enter your username" required>
                    </div>
                </div>

                <div class="input-group">
                    <div class="label-row">
                        <label for="password">Password</label>
                        <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                    </div>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Sign In <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="login-footer">
                <p>New user? Contact to the the Admin</p>
            </div>
        </div>
    </div>

</body>
</html>