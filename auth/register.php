<?php
include "../config/db.php";

$error = "";
$success = "";

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
    <title>Customer Register</title>

    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
</head>

<body>

<div class="login-wrapper">

    <div class="overlay"></div>

    <h1 class="hello-text">hello</h1>

    <div class="login-box">
        <div class="login-box-inner">

            <h2>Register</h2>

            <?php if ($error != "") { ?>
                <p style="color:red;"><?php echo $error; ?></p>
            <?php } ?>

            <form method="POST">
                <input type="text" name="name" placeholder="Enter Name" required>
                <input type="email" name="email" placeholder="Enter Email" required>
                <input type="password" name="password" placeholder="Enter Password" required>

                <label style="font-size: 13px; display:block; margin: 10px 0;">
                    <input type="checkbox" name="agree" required>
                    I agree to the 
                    <a href="../privacy/privacy.php" target="_blank">Privacy Policy</a>
                    and
                    <a href="../privacy/terms.php" target="_blank">Terms & Conditions</a>
                </label>

                <button class="btn btn-primary" type="submit" name="register">Register Now</button>
            </form>

            <br>
            <a href="login.php">Already have account? Login</a>

        </div>
    </div>

</div>

</body>
</html>