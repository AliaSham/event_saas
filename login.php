<?php
include 'config.php';
session_start();

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $query = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        echo "<script>alert('Login Successful!'); window.location='dashboard.php';</script>";
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Event SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; display: flex; align-items: center; height: 100vh; }
        .login-card { width: 100%; max-width: 400px; padding: 15px; margin: auto; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="text-center mb-4">Event SaaS</h3>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger text-center"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100">Login Now</button>
            </form>

            <div class="text-center mt-3">
                <a href="register.php">Don't have an account? Register</a>
            </div>

        </div>
    </div>
    <p class="mt-3 text-muted text-center">&copy; 2026 Event Pro System</p>
</div>

</body>
</html>