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

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = trim($_POST['captcha'] ?? '');

    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if ($captcha != ($_SESSION['captcha_answer'] ?? null)) {
        $message = "Captcha incorrect!";
        $message_type = "error";
    } else {
        $stmt = $mysqli->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $username, $hashed_password, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;

                header("Location: index.php");
                exit();
            } else {
                $message = "Incorrect password!";
                $message_type = "error";
            }
        } else {
            $message = "No user found with that email.";
            $message_type = "error";
        }

        $stmt->close();
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
    <title>Login</title>
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

        .login-container {
            background: #ffffff;
            padding: 30px 25px;
            width: 320px;
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

        input[type="email"],
        input[type="password"],
        input[type="text"] {
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
            background: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
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

<div class="login-container">
    <h2>Login</h2>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

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