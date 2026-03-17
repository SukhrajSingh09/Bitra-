<?php
session_start();
// Ensure captcha_text exists BEFORE any HTML
if (!isset($_SESSION['captcha_num1']) || !isset($_SESSION['captcha_num2'])) {
    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);
    $_SESSION['captcha_answer'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
}

$captcha_text = $_SESSION['captcha_num1'] . " + " . $_SESSION['captcha_num2'];

include 'dbex.php';

// Generate captcha ONLY when displaying the form (GET), not when submitting POST
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $number1 = rand(1, 10);
    $number2 = rand(1, 10);
    $_SESSION['captcha_answer'] = $number1 + $number2;

    // Save numbers for displaying again
    $_SESSION['captcha_num1'] = $number1;
    $_SESSION['captcha_num2'] = $number2;
} else {
    // For POST requests, use the previous numbers from session
    $number1 = $_SESSION['captcha_num1'];
    $number2 = $_SESSION['captcha_num2'];
}

if (isset($_POST['register'])) {

    // Filter & sanitize input
    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $captcha = $_POST['captcha'];

    // Check captcha
    if ($captcha != $_SESSION['captcha_answer']) {
        echo "<p style='color:red;'>Captcha incorrect!</p>";
    } else {

        // Encrypt password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert using prepared statements
        $stmt = $mysqli->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            echo "Registration successful! <a href='login.php'>Login here</a>";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();

        // Generate new captcha for the next form display
        $number1 = rand(1, 10);
        $number2 = rand(1, 10);
        $_SESSION['captcha_answer'] = $number1 + $number2;
        $_SESSION['captcha_num1'] = $number1;
        $_SESSION['captcha_num2'] = $number2;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>

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

        .register-container {
            background: #ffffff;
            padding: 30px 25px;
            width: 340px;
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

        input[type="text"],
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
            background: #28a745;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 5px;
        }

        button:hover {
            background: #1e7e34;
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

<div class="register-container">
    <h2>Register</h2>

    <form method="post">
        <label>Username:</label>
        <input type="text" name="username" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Captcha: What is <?php echo $number1; ?> + <?php echo $number2; ?> ?</label>
        <input type="text" name="captcha" required>

        <button type="submit" name="register">Register</button>

        <p>Already have an account? <a href="login.php">Login here</a></p>
    </form>
</div>

</body>
</html>
