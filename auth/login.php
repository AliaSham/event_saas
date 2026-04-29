<?php
session_start();
include('../config/db.php');

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['company_id'] = $user['company_id'];

        header("Location: ../admin/dashboard.php");
        exit;

    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>

    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
</head>

<body>

<div class="login-wrapper">

    <div class="overlay"></div>

    <!-- HELLO -->
    <h1 class="hello-text">hello</h1>

    <!-- LOGIN -->
    <div class="login-box">
        <div class="login-box-inner">

            <h2>Login</h2>

            <?php if ($error != "") { ?>
                <p style="color:red;"><?php echo $error; ?></p>
            <?php } ?>

            <form method="POST">
                <input type="email" name="email" placeholder="Enter Email" required>
                <input type="password" name="password" placeholder="Enter Password" required>

                <button class="btn btn-primary">Login</button>
            </form>

        </div>
    </div>

</div>

</body>
</html>