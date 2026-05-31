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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($name == "" || $email == "" || $phone == "" || $address == "") {
        $error = "Name, email, phone number, and address are required.";
    } else {

        $checkEmail = mysqli_query($conn, "
            SELECT * FROM users
            WHERE email='$email'
            AND id != '$user_id'
        ");

        if (mysqli_num_rows($checkEmail) > 0) {
            $error = "Email already exists.";
        } elseif ($new_password != "" && $new_password != $confirm_password) {
            $error = "New password and confirm password do not match.";
        } else {

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

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/event_saas/assets/css/style.css">

<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background:
        radial-gradient(circle at top left, rgba(37,99,235,.16), transparent 34%),
        radial-gradient(circle at bottom right, rgba(14,165,233,.14), transparent 36%),
        #f4f7fb;
    color: #0f172a;
}

.account-wrapper {
    max-width: 1180px;
    margin: 0 auto;
    padding: 42px 24px 70px;
}

.account-hero {
    background: linear-gradient(135deg, #0f172a, #1d4ed8, #38bdf8);
    color: white;
    border-radius: 30px;
    padding: 42px;
    margin-bottom: 28px;
    box-shadow: 0 25px 55px rgba(37,99,235,.25);
}

.account-hero span {
    display: inline-block;
    padding: 8px 14px;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.24);
    border-radius: 999px;
    font-size: 13px;
    margin-bottom: 16px;
}

.account-hero h1 {
    margin: 0 0 10px;
    font-size: 38px;
}

.account-hero p {
    margin: 0;
    opacity: .9;
}

.account-layout {
    display: grid;
    grid-template-columns: 330px 1fr;
    gap: 26px;
    align-items: start;
}

.profile-card,
.form-card {
    background: rgba(255,255,255,.96);
    border: 1px solid #e2e8f0;
    border-radius: 28px;
    box-shadow: 0 20px 45px rgba(15,23,42,.08);
}

.profile-card {
    padding: 28px;
    text-align: center;
}

.avatar {
    width: 96px;
    height: 96px;
    margin: 0 auto 16px;
    border-radius: 30px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    font-weight: 800;
    box-shadow: 0 16px 30px rgba(37,99,235,.28);
}

.profile-card h2 {
    margin: 0;
    font-size: 23px;
}

.profile-card p {
    margin: 7px 0 18px;
    color: #64748b;
    font-size: 13px;
    word-break: break-word;
}

.role-badge {
    display: inline-block;
    padding: 9px 15px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 800;
    font-size: 12px;
    text-transform: uppercase;
}

.profile-info {
    margin-top: 24px;
    display: grid;
    gap: 12px;
    text-align: left;
}

.profile-info-box {
    padding: 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
}

.profile-info-box small {
    display: block;
    color: #64748b;
    margin-bottom: 5px;
}

.profile-info-box strong {
    font-size: 13px;
    word-break: break-word;
}

.form-card {
    padding: 30px;
}

.form-title {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: start;
    margin-bottom: 24px;
}

.form-title h2 {
    margin: 0 0 7px;
    font-size: 26px;
}

.form-title p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
}

.alert {
    padding: 15px 18px;
    border-radius: 16px;
    margin-bottom: 22px;
    font-weight: 600;
}

.alert.error {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.alert.success {
    background: #ecfdf5;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

.form-group {
    display: grid;
    gap: 8px;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}

input,
textarea {
    width: 100%;
    border: 1px solid #dbe4f0;
    background: #fff;
    border-radius: 15px;
    padding: 14px 15px;
    font-family: 'Poppins', sans-serif;
    outline: none;
    transition: .2s ease;
}

input:focus,
textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37,99,235,.12);
}

input:disabled {
    background: #f1f5f9;
    color: #64748b;
    cursor: not-allowed;
}

textarea {
    min-height: 120px;
    resize: vertical;
}

.password-section {
    margin-top: 26px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}

.password-header {
    margin-bottom: 18px;
}

.password-header h3 {
    margin: 0 0 6px;
    font-size: 18px;
}

.password-header p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
}

.action-row {
    margin-top: 26px;
    display: flex;
    justify-content: flex-end;
}

.save-btn {
    border: none;
    padding: 14px 24px;
    border-radius: 16px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: white;
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 14px 28px rgba(37,99,235,.26);
}

.save-btn:hover {
    filter: brightness(.96);
}

@media(max-width: 900px) {
    .account-layout {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 600px) {
    .account-wrapper {
        padding: 28px 16px 50px;
    }

    .account-hero {
        padding: 30px 24px;
    }

    .account-hero h1 {
        font-size: 30px;
    }

    .form-card {
        padding: 22px;
    }
}
</style>
</head>

<body>

<?php include('header.php'); ?>

<div class="account-wrapper">

    <section class="account-hero">
        <span>👤 Customer Profile</span>
        <h1>My Account</h1>
        <p>Manage your profile details, contact information and account password in one secure place.</p>
    </section>

    <div class="account-layout">

        <aside class="profile-card">
            <div class="avatar">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>

            <h2><?= htmlspecialchars($user['name']) ?></h2>
            <p><?= htmlspecialchars($user['email']) ?></p>

            <div class="role-badge">
                <?= ucfirst(htmlspecialchars($user['role'])) ?>
            </div>

            <div class="profile-info">
                <div class="profile-info-box">
                    <small>Phone Number</small>
                    <strong><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not provided' ?></strong>
                </div>

                <div class="profile-info-box">
                    <small>Address</small>
                    <strong><?= !empty($user['address']) ? htmlspecialchars($user['address']) : 'Not provided' ?></strong>
                </div>
            </div>
        </aside>

        <main class="form-card">
            <div class="form-title">
                <div>
                    <h2>Account Settings</h2>
                    <p>Update your latest information before making a booking.</p>
                </div>
            </div>

            <?php if ($error != "") { ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php } ?>

            <?php if ($success != "") { ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php } ?>

            <form method="POST">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" placeholder="e.g., 0123456789" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?= ucfirst(htmlspecialchars($user['role'])) ?>" disabled>
                    </div>

                    <div class="form-group full">
                        <label>Address</label>
                        <textarea name="address" placeholder="Enter your full shipping or event address" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="password-section">
                    <div class="password-header">
                        <h3>Password Security</h3>
                        <p>Leave the fields blank if you do not want to change your password.</p>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" placeholder="Enter new password">
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm new password">
                        </div>
                    </div>
                </div>

                <div class="action-row">
                    <button type="submit" class="save-btn">Save Changes</button>
                </div>

            </form>
        </main>

    </div>

</div>

</body>
</html>