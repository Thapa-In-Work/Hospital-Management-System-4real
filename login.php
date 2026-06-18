<?php
// Include necessary files
require_once 'connect.php';

// Start session if not already started (always do this first)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$login_error = '';

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $login_error = "Please enter both email and password.";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ?");
        
        if ($stmt === false) {
             $login_error = "Database Error: Could not prepare statement. MySQL Error: " . mysqli_error($conn);
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password_hash'])) {
                    // Password is correct, start session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $email;

                    header('Location: dashboard.php');
                    exit();
                } else {
                    $login_error = "Invalid email or password.";
                }
            } else {
                $login_error = "Invalid email or password.";
            }
            $stmt->close();
        }
    }
}
// Close database connection after use
mysqli_close($conn); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa; 
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h2 { margin-bottom: 5px; font-weight: 600; }
        .subtitle { margin-bottom: 30px; color: #6c757d; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="email"], input[type="password"] {
            width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box;
        }
        .btn-primary {
            width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background-color 0.3s;
        }
        .btn-primary:hover { background-color: #0056b3; }
        .signup-link { margin-top: 25px; font-size: 14px; }
        .signup-link a { color: #007bff; text-decoration: none; font-weight: bold; }
        .message-error {
            color: #dc3545; margin-bottom: 15px; padding: 10px; border: 1px solid #f5c6cb; background-color: #f8d7da; border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Welcome Back!</h2>
        <p class="subtitle">Login with your details to continue</p>

        <?php if (!empty($login_error)): ?>
            <p class="message-error"><?php echo $login_error; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn-primary">Login</button>
        </form>

        <p class="signup-link">Don't have an account? <a href="signup.php">Sign Up</a></p>
    </div>
</body>
</html>