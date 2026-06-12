<?php
// Set butang "Back" asal lalai (default) ke login.php jika tiada parameter
$back_url = "login.php";
$back_text = "← Back to Login";

// Semak jika user datang dari page customer atau vendor
if (isset($_GET['from'])) {
    if ($_GET['from'] == 'customer') {
        $back_url = "register.php";
        $back_text = "← Back to Customer Register";
    } elseif ($_GET['from'] == 'vendor') {
        $back_url = "vendor_register.php";
        $back_text = "← Back to Vendor Register";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Event Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .legal-header { background-color: #0b1329; color: white; padding: 40px 20px; text-align: center; }
        .legal-header h1 { margin: 0; font-size: 2rem; font-weight: 700; }
        .legal-container { max-width: 800px; margin: 40px auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); flex: 1; }
        h2 { color: #0b1329; font-size: 1.4rem; margin-top: 30px; border-left: 4px solid #8b5cf6; padding-left: 10px; }
        p, li { line-height: 1.8; font-size: 0.95rem; color: #475569; }
        .back-btn { display: inline-block; margin-bottom: 20px; color: #8b5cf6; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .back-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="legal-header">
        <h1>Terms of Service</h1>
    </div>

    <div class="legal-container">
        <a href="<?php echo $back_url; ?>" class="back-btn"><?php echo $back_text; ?></a>
        <p><em>Last updated: June 12, 2026</em></p>

        <p>By accessing and registering an account with Event Pro, you agree to be bound by these Terms of Service. Please read them carefully before using our application.</p>

        <h2>1. Account Registration</h2>
        <p>Users must provide accurate and complete details during sign-up. Admins retain full authority over checking active subscription statuses before providing system privileges.</p>

        <h2>2. Marketplace Booking Rules</h2>
        <p>Event Pro operates as a decentralized multi-vendor network. All event services entered via the interface represent an agreement directly between the designated vendor and customer.</p>
    </div>

    <?php include('footer_auth.php'); ?>

</body>
</html>