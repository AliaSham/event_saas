<?php
session_start();

// PROTECT PAGE
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

?>

<h1>Customer Dashboard</h1>
<p>Welcome, customer!</p>
<p>Company ID: <?php echo htmlspecialchars($_SESSION['company_id']); ?></p>

<hr>

<p><a href="../auth/logout.php">Logout</a></p>
