<?php
session_start();
include 'db.php';

$message = "";
$message_type = "";

// Generate captcha on GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $number1 = rand(1, 10);
    $number2 = rand(1, 10);

    $_SESSION['captcha_answer'] = $number1 + $number2;
    $_SESSION['captcha_num1'] = $number1;
    $_SESSION['captcha_num2'] = $number2;
} else {
    $number1 = $_SESSION['captcha_num1'] ?? rand(1, 10);
    $number2 = $_SESSION['captcha_num2'] ?? rand(1, 10);
}

if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = trim($_POST['captcha'] ?? '');

    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if ($captcha != ($_SESSION['captcha_answer'] ?? null)) {
        $message = "Captcha incorrect!";
        $message_type = "error";
    } elseif (empty($username) || empty($email) || empty($password)) {
        $message = "Please fill in all fields.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } else {
        $check = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "That email is already registered.";
            $message_type = "error";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';

            $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $message = "Registration successful! You can now log in.";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }

            $stmt->close();
        }

        $check->close();
    }

    // Regenerate captcha after submit
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
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .register-container {
            background: #ffffff;
            padding: 30px 25px;
            width: 340px;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
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
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #28a745;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #1f7f35;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
        }

        .error {
            background: #ffe5e5;
            color: #b30000;
        }

        .success {
            background: #e6ffea;
            color: #187a2f;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        p {
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="register-container">
    <h2>Register</h2>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

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