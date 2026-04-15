<?php
include "config.php";

if(isset($_POST['register'])){

    // CHECK PRIVACY FIRST
    if(!isset($_POST['agree'])){
        echo "Please agree to Privacy Policy & Terms!";
        exit(); // STOP terus
    }

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // role customer
    $role = "customer";

    // check email duplicate
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

    if(mysqli_num_rows($check) > 0){
        echo "Email already exists!";
    } else {

        // insert without company
        $query = "INSERT INTO users (name, email, password, role, company_id)
                  VALUES ('$name', '$email', '$password', '$role', NULL)";

        if(mysqli_query($conn, $query)){
            
            // redirect ke login
            header("Location: login.php");
            exit();

        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>

<h2>Customer Register</h2>

<form method="POST">
    Name: <input type="text" name="name" required><br><br>

    Email: <input type="email" name="email" required><br><br>

    Password: <input type="password" name="password" required><br><br>

    <label>
        <input type="checkbox" name="agree" required>
        I agree to the 
        <a href="privacy/privacy.php" target="_blank">Privacy Policy</a> 
        and 
        <a href="privacy/terms.php" target="_blank">Terms & Conditions</a>
    </label>
    <br><br>

    <button type="submit" name="register">Register Now</button>
</form>

<br>
<a href="login.php">Already have account? Login</a>