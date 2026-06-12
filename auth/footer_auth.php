<!-- === KANDUNGAN PENUH FAIL: auth/footer_auth.php === -->
<footer class="saas-footer">
    <div class="footer-container">
        <!-- Bahagian Kiri: Info Jenama -->
        <div class="footer-brand-section">
            <div class="footer-logo">
                <span class="logo-icon">❖</span> 
                <strong>Event Pro</strong>
            </div>
            <p class="footer-desc">
                The ultimate marketplace dashboard built for seamless event planning, vendor management, and premium business insights.
            </p>
        </div>

        <!-- Bahagian Kanan: Pautan Navigasi yang Dah Berfungsi -->
        <div class="footer-links-grid">
            <div class="footer-column">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="login.php">User Login</a></li>
                    <li><a href="register.php">Customer Register</a></li>
                    <li><a href="vendor_register.php">Vendor Register</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>Support</h4>
                <ul>
                    <li><a href="mailto:support@eventpro.com">Contact Support</a></li>
                    <li><a href="#">Help Center</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>Legal</h4>
                <ul>
                    <!-- DAH DIUBAH: Menghala ke fail privacy.php dan terms.php -->
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms of Service</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Bahagian Bawah: Hak Cipta & Media Sosial -->
    <div class="footer-bottom">
        <p>&copy; 2026 Event Pro Node Engine. All rights reserved.</p>
        <div class="footer-socials">
            <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <a href="https://tiktok.com" target="_blank"><i class="fab fa-tiktok"></i></a>
            <a href="https://x.com" target="_blank"><i class="fab fa-x-twitter"></i></a>
        </div>
    </div>
</footer>

<style>
    .saas-footer {
        background-color: #0b1329; 
        color: #94a3b8;
        font-family: 'Poppins', sans-serif;
        padding: 60px 40px 30px;
        margin-top: 80px; 
        border-top: 4px solid #8b5cf6; 
        width: 100%;
        box-sizing: border-box;
    }
    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between; 
        align-items: flex-start;
        gap: 40px;
        padding-bottom: 40px;
        border-bottom: 1px solid #1e293b;
    }
    .footer-brand-section { 
        flex: 1; 
        min-width: 280px; 
        max-width: 360px; 
    }
    .footer-logo { 
        font-size: 22px; 
        color: #ffffff; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        margin-bottom: 15px; 
    }
    .logo-icon { color: #a78bfa; font-size: 24px; }
    .footer-desc { font-size: 14px; line-height: 1.6; color: #94a3b8; }
    .footer-links-grid { 
        display: flex; 
        gap: 80px; 
        flex-wrap: wrap; 
    }
    .footer-column h4 { 
        color: #ffffff; 
        font-size: 15px; 
        font-weight: 600; 
        margin-bottom: 20px; 
    }
    .footer-column ul { 
        list-style: none; 
        padding: 0; 
        margin: 0; 
    }
    .footer-column ul li { margin-bottom: 12px; }
    .footer-column ul li a { 
        color: #94a3b8; 
        text-decoration: none; 
        font-size: 14px; 
        transition: color 0.2s ease; 
    }
    .footer-column ul li a:hover { color: #a78bfa; }
    .footer-bottom { 
        max-width: 1200px; 
        margin: 0 auto; 
        padding-top: 30px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        flex-wrap: wrap; 
        gap: 20px; 
        font-size: 13px; 
    }
    .footer-socials { display: flex; gap: 20px; }
    .footer-socials a { 
        color: #94a3b8; 
        font-size: 16px; 
        text-decoration: none; 
        transition: color 0.2s ease; 
    }
    .footer-socials a:hover { color: #ffffff; }
    @media (max-width: 768px) {
        .footer-container { flex-direction: column; justify-content: flex-start; }
        .footer-links-grid { gap: 40px; width: 100%; justify-content: space-between; }
        .footer-bottom { flex-direction: column; text-align: center; }
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">