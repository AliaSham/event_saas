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
    <title>Privacy Policy - Event Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .legal-header { background-color: #0b1329; color: white; padding: 40px 20px; text-align: center; }
        .legal-header h1 { margin: 0; font-size: 2rem; font-weight: 700; }
        .legal-container { max-width: 800px; margin: 40px auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); flex: 1; }
        h2 { color: #0b1329; font-size: 1.4rem; margin-top: 30px; border-left: 4px solid #8b5cf6; padding-left: 10px; }
        p, li { line-height: 1.8; font-size: 0.95rem; color: #475569; }
        ul { padding-left: 20px; }
        .back-btn { display: inline-block; margin-bottom: 20px; color: #8b5cf6; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .back-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="legal-header">
        <h1>Privacy Policy</h1>
    </div>

    <div class="legal-container">
        <a href="<?php echo $back_url; ?>" class="back-btn"><?php echo $back_text; ?></a>
        <p><em>Last updated: June 12, 2026</em></p>

        <p>Welcome to Event Pro. We value your privacy and are committed to protecting your personal data. This Privacy Policy explains how we collect, use, and safeguard your information when you use our Event Marketplace platform.</p>

        <h2>1. Information We Collect</h2>
        <p>We collect information that you provide directly to us when creating an account, such as your name, email address, and account passwords.</p>

        <h2>2. How We Use Your Information</h2>
        <p>We process your data to ensure the platform operates smoothly, facilitating bookings between customers and event vendors.</p>

        <h2>3. Data Security</h2>
        <p>We implement a variety of security measures, including data encryption algorithms, to maintain the safety of your personal information.</p>
    </div>

    <?php include('footer_auth.php'); ?>

</body>
</html>