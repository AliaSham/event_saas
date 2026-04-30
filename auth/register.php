<?php
include "../config/db.php";

$error = "";

if(isset($_POST['register'])){

    if(!isset($_POST['agree'])){
        $error = "Please agree to Privacy Policy & Terms!";
    } else {

        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = "customer";

        $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

        if(mysqli_num_rows($check) > 0){
            $error = "Email already exists!";
        } else {

            $query = "INSERT INTO users (name, email, password, role, company_id)
                      VALUES ('$name', '$email', '$password', '$role', NULL)";

            if(mysqli_query($conn, $query)){
                header("Location: login.php");
                exit();
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>

    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
</head>

<body>

<div class="auth-wrapper">

    <div class="overlay"></div>

    <!-- KEEP ANIMATION -->
    <h1 class="hello-text">hello</h1>

    <div class="auth-card">

        <h2 class="auth-title">Create Account</h2>
        <p class="auth-sub">Start using the platform</p>

        <?php if ($error != "") { ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php } ?>

        <form method="POST">

            <input type="text" name="name" placeholder="Enter Name" required>
            <input type="email" name="email" placeholder="Enter Email" required>
            <input type="password" name="password" placeholder="Enter Password" required>

            <label class="checkbox">
                <input type="checkbox" name="agree" required>
                I agree to 
                <a href="../privacy/privacy.php" target="_blank">Privacy Policy</a>
                and
                <a href="../privacy/terms.php" target="_blank">Terms</a>
            </label>

            <button class="btn btn-primary" name="register">Register</button>

        </form>

        <p class="auth-link">
            Already have an account?
            <a href="login.php">Login</a>
        </p>

    </div>

</div>

</body>
</html>