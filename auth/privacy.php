<?php
$back_url = "login.php";
$back_text = "← Back to Login";

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

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(139, 92, 246, 0.22), transparent 35%),
                radial-gradient(circle at bottom right, rgba(59, 130, 246, 0.18), transparent 35%),
                linear-gradient(135deg, #f8fafc, #eef2ff);
            color: #0f172a;
            min-height: 100vh;
        }

        .legal-header {
            position: relative;
            background: linear-gradient(135deg, #070b1f, #111827, #312e81);
            color: white;
            padding: 55px 20px 90px;
            text-align: center;
            overflow: hidden;
        }

        .legal-header::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            background: rgba(139, 92, 246, 0.35);
            border-radius: 50%;
            top: -100px;
            left: -70px;
            filter: blur(10px);
        }

        .legal-header::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            background: rgba(59, 130, 246, 0.25);
            border-radius: 50%;
            bottom: -90px;
            right: 80px;
            filter: blur(8px);
        }

        .legal-header h1 {
            position: relative;
            margin: 0;
            font-size: 2.4rem;
            font-weight: 800;
            letter-spacing: -1px;
            z-index: 1;
        }

        .legal-header p {
            position: relative;
            margin-top: 10px;
            color: #cbd5e1;
            font-size: 0.95rem;
            z-index: 1;
        }

        .legal-container {
            position: relative;
            max-width: 880px;
            margin: -50px auto 50px;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(18px);
            padding: 42px;
            border-radius: 26px;
            box-shadow: 0 25px 70px rgba(15, 23, 42, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.7);
            z-index: 2;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 28px;
            padding: 10px 18px;
            border-radius: 999px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            box-shadow: 0 12px 25px rgba(99, 102, 241, 0.28);
            transition: 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 35px rgba(99, 102, 241, 0.38);
        }

        .updated {
            display: inline-block;
            background: #eef2ff;
            color: #4338ca;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .intro {
            font-size: 0.96rem;
            color: #475569;
            line-height: 1.9;
            margin-bottom: 28px;
        }

        .policy-card {
            display: flex;
            gap: 18px;
            padding: 22px;
            margin-top: 20px;
            border-radius: 20px;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            transition: 0.3s ease;
        }

        .policy-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.1);
            border-color: #c4b5fd;
        }

        .icon-box {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 16px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            box-shadow: 0 10px 22px rgba(99, 102, 241, 0.3);
        }

        .policy-content h2 {
            margin: 0 0 10px;
            font-size: 1.18rem;
            color: #0f172a;
            font-weight: 800;
        }

        .policy-content p {
            margin: 0;
            line-height: 1.8;
            font-size: 0.92rem;
            color: #475569;
        }

        .highlight-box {
            margin-top: 28px;
            padding: 20px 24px;
            border-radius: 18px;
            background: linear-gradient(135deg, #eef2ff, #f5f3ff);
            border-left: 5px solid #8b5cf6;
        }

        .highlight-box p {
            margin: 0;
            color: #334155;
            line-height: 1.8;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .legal-header {
                padding: 45px 18px 80px;
            }

            .legal-header h1 {
                font-size: 1.9rem;
            }

            .legal-container {
                margin: -45px 18px 40px;
                padding: 28px;
                border-radius: 22px;
            }

            .policy-card {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <div class="legal-header">
        <h1>Privacy Policy</h1>
        <p>Your privacy matters to us at Event Pro</p>
    </div>

    <div class="legal-container">
        <a href="<?php echo $back_url; ?>" class="back-btn">
            <?php echo $back_text; ?>
        </a>

        <div class="updated">Last updated: June 12, 2026</div>

        <p class="intro">
            Welcome to Event Pro. We value your privacy and are committed to protecting your personal data.
            This Privacy Policy explains how we collect, use, and safeguard your information when you use our
            Event Marketplace platform.
        </p>

        <div class="policy-card">
            <div class="icon-box">🔐</div>
            <div class="policy-content">
                <h2>1. Information We Collect</h2>
                <p>
                    We collect information that you provide directly to us when creating an account,
                    such as your name, email address, and account passwords.
                </p>
            </div>
        </div>

        <div class="policy-card">
            <div class="icon-box">⚙️</div>
            <div class="policy-content">
                <h2>2. How We Use Your Information</h2>
                <p>
                    We process your data to ensure the platform operates smoothly,
                    facilitating bookings between customers and event vendors.
                </p>
            </div>
        </div>

        <div class="policy-card">
            <div class="icon-box">🛡️</div>
            <div class="policy-content">
                <h2>3. Data Security</h2>
                <p>
                    We implement a variety of security measures, including data encryption algorithms,
                    to maintain the safety of your personal information.
                </p>
            </div>
        </div>

        <div class="highlight-box">
            <p>
                By using Event Pro, you agree that your information will be handled securely and responsibly
                to support a safer event booking experience.
            </p>
        </div>
    </div>

    <?php include('footer_auth.php'); ?>

</body>
</html>