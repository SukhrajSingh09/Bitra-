<?php
session_start();
include 'dbex.php';

// Generate captcha only when showing the form (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $number1 = rand(1, 10);
    $number2 = rand(1, 10);

    $_SESSION['captcha_answer'] = $number1 + $number2;
    $_SESSION['captcha_num1'] = $number1;
    $_SESSION['captcha_num2'] = $number2;
} else {
    // On POST, reuse the same captcha numbers for display
    $number1 = $_SESSION['captcha_num1'];
    $number2 = $_SESSION['captcha_num2'];
}

if (isset($_POST['login'])) {

    // Sanitize input
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $captcha = $_POST['captcha'];

    // Check captcha
    if ($captcha != $_SESSION['captcha_answer']) {
        echo "<p style='color:red;'>Captcha incorrect!</p>";
    } else {

        // Prepared statement for login
        $stmt = $mysqli->prepare("SELECT id, username, password FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {

            $stmt->bind_result($id, $username, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {

                // Login successful → set session
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;

                // Redirect to main page
                header("Location: expense_tracker.php");
                exit();

            } else {
                echo "<p style='color:red;'>Incorrect password!</p>";
            }

        } else {
            echo "<p style='color:red;'>No user found with this email!</p>";
        }

        $stmt->close();
    }

    // Regenerate captcha for next form load
    $number1 = rand(1, 10);
    $number2 = rand(1, 10);
    $_SESSION['captcha_answer'] = $number1 + $number2;
    $_SESSION['captcha_num1'] = $number1;
    $_SESSION['captcha_num2'] = $number2;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: #ffffff;
            padding: 30px 25px;
            width: 320px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 5px;
            text-align: left;
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        p {
            margin-top: 10px;
            font-size: 14px;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>

</head>
<body>

<div class="login-container">
    <h2>Login</h2>

    <form method="post">
        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Captcha: What is <?php echo $number1; ?> + <?php echo $number2; ?> ?</label>
        <input type="text" name="captcha" required>

        <button type="submit" name="login">Login</button>

        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </form>
</div>

</body>
</html>
