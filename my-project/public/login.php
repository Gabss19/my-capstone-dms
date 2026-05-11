<?php
    session_start();
    // If already logged in, skip the login page
    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit();
    }
    require_once '../backend/auth_logic.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DocuManager Login</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .login-container { display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f4; }
        .login-box { width: 350px; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; }
        .login-box h2 { margin-bottom: 20px; color: #333; }
        .login-box input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; }
        .login-box button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .login-box button:hover { background: #0056b3; }
        .error-msg { color: #d9534f; font-size: 14px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>DocuManager</h2>
            
            <?php if($error): ?>
                <p class="error-msg"><?= $error ?></p>
            <?php endif; ?>

            <form method="POST">
                <input type="email" name="email" placeholder="Email Address" required autofocus>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
