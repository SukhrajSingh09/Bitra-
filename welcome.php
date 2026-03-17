<?php
session_start();

// Protect the page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<h2>Welcome</h2>
<p>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>! You are logged in.</p>

<a href="logout.php">Logout</a>
