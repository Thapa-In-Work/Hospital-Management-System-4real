<?php
include 'connect.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$signup_error = '';
$signup_success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $signup_error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $signup_error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $signup_error = "Password must be at least 6 characters long.";
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");

        if ($check_stmt === false) {
            $signup_error = "Database Error (Check): Could not prepare statement. MySQL Error: " . mysqli_error($conn);
        } else {
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $signup_error = "This email is already registered.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $insert_query = "INSERT INTO users (email, password_hash) VALUES (?, ?)";
                $insert_stmt = $conn->prepare($insert_query);

                if ($insert_stmt === false) {
                    $signup_error = "Database Error (Insert): Could not prepare statement. MySQL Error: " . mysqli_error($conn);
                } else {
                    $insert_stmt->bind_param("ss", $email, $password_hash);

                    if ($insert_stmt->execute()) {
                        $signup_success = "Registration successful! You can now <a href='login.php'>log in</a>.";
                        unset($_POST['email']);
                    } else {
                        $signup_error = "Something went wrong. Please try again later. Error: " . $insert_stmt->error;
                    }
                    $insert_stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
}
mysqli_close($conn); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - HMS</title>
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
        .message-success {
            color:#155724; margin-bottom: 15px; padding: 10px; border: 1px solid #c3e6cb; background-color: #d4edda; border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Sign Up</h2>
        <p class="subtitle">Create your account to get started</p>
        <?php if (!empty($signup_error)): ?>
            <p class="message-error"><?php echo $signup_error; ?></p>
        <?php endif; ?>
        <?php if (!empty($signup_success)): ?>
            <p class="message-success"><?php echo $signup_success; ?></p>
        <?php endif; ?>
        <form action="signup.php" method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <button type="submit" class="btn-primary">Sign Up</button>
        </form>
        <p class="signup-link">Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>