<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

  <h2>Admin Dashboard</h2>
  <p>Welcome, <?php echo $_SESSION['username']; ?></p>

  <div class="row mt-4">

    <div class="col-md-4">
      <div class="card p-3 shadow-sm">
        <h5>Add Event</h5>
        <a href="index.php" class="btn btn-primary">Go</a>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card p-3 shadow-sm">
        <h5>Event Requests</h5>
        <a href="event_requests.php" class="btn btn-success">View</a>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card p-3 shadow-sm">
        <h5>Admin Requests</h5>
        <a href="admin_requests.php" class="btn btn-warning">View</a>
      </div>
    </div>

  </div>

</div>

</body>
</html>