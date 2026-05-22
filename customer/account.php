<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

/* GET CUSTOMER DATA */
$userQ = mysqli_query($conn, "
    SELECT * FROM users
    WHERE id='$user_id'
    AND role='customer'
");

$user = mysqli_fetch_assoc($userQ);

if (!$user) {
    header("Location: ../auth/login.php");
    exit;
}

/* UPDATE ACCOUNT */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);       // [TAMBAHAN BARU]
    $address = trim($_POST['address']);   // [TAMBAHAN BARU]
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Pastikan telefon dan alamat tidak dibiarkan kosong
    if ($name == "" || $email == "" || $phone == "" || $address == "") {
        $error = "Name, email, phone number, and address are required.";
    } 
    else {

        /* CHECK EMAIL DUPLICATE EXCEPT CURRENT USER */
        $checkEmail = mysqli_query($conn, "
            SELECT * FROM users
            WHERE email='$email'
            AND id != '$user_id'
        ");

        if (mysqli_num_rows($checkEmail) > 0) {
            $error = "Email already exists.";
        }
        elseif ($new_password != "" && $new_password != $confirm_password) {
            $error = "New password and confirm password do not match.";
        }
        else {

            // SQL UPDATE: Termasuk kolum phone dan address yang baru dimasukkan
            if ($new_password != "") {
                mysqli_query($conn, "
                    UPDATE users SET
                        name='$name',
                        email='$email',
                        phone='$phone',
                        address='$address',
                        password='$new_password'
                    WHERE id='$user_id'
                    AND role='customer'
                ");
            } else {
                mysqli_query($conn, "
                    UPDATE users SET
                        name='$name',
                        email='$email',
                        phone='$phone',
                        address='$address'
                    WHERE id='$user_id'
                    AND role='customer'
                ");
            }

            $success = "Account updated successfully.";

            /* REFRESH USER DATA */
            $userQ = mysqli_query($conn, "
                SELECT * FROM users
                WHERE id='$user_id'
                AND role='customer'
            ");

            $user = mysqli_fetch_assoc($userQ);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/event_saas/assets/css/style.css">
</head>

<body>

<?php include('header.php'); ?>

<div class="container">

    <div class="form-container">

        <div class="form-box modern-form">

            <h2>My Account</h2>
            <p class="muted">View and update your account information</p>

            <?php if ($error != "") { ?>
                <div class="alert error"><?= $error ?></div>
            <?php } ?>

            <?php if ($success != "") { ?>
                <div class="alert success"><?= $success ?></div>
            <?php } ?>

            <form method="POST">

                <label>Name</label>
                <input type="text"
                       name="name"
                       value="<?= htmlspecialchars($user['name']) ?>"
                       required>

                <label>Email</label>
                <input type="email"
                       name="email"
                       value="<?= htmlspecialchars($user['email']) ?>"
                       required>

                <label>Phone Number</label>
                <input type="text"
                       name="phone"
                       placeholder="e.g., 0123456789"
                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                       required>

                <label>Address</label>
                <textarea name="address" 
                          rows="3" 
                          placeholder="Enter your full shipping or event address"
                          style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: 'Poppins', sans-serif; box-sizing: border-box; margin-bottom: 15px;"
                          required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                <label>Role</label>
                <input type="text"
                       value="<?= ucfirst($user['role']) ?>"
                       disabled>

                <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">

                <p class="muted">Change password only if needed</p>

                <label>New Password</label>
                <input type="password"
                       name="new_password"
                       placeholder="Leave blank if you do not want to change password">

                <label>Confirm New Password</label>
                <input type="password"
                       name="confirm_password"
                       placeholder="Confirm new password">

                <button class="btn btn-primary">
                    Save Changes
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>