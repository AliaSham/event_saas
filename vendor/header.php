<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'vendor') {
    header("Location: /event_saas/auth/login.php");
    exit;
}
?>

<style>
.vendor-navbar{
    position:sticky;
    top:0;
    z-index:9999;

    display:flex;
    justify-content:space-between;
    align-items:center;

    padding:0 40px;
    height:75px;

    background:#ffffff;
    border-bottom:1px solid #e5e7eb;

    box-shadow:0 4px 20px rgba(0,0,0,0.05);
    font-family:'Poppins',sans-serif;
}
.vendor-logo{
    display:flex;
    align-items:center;
    gap:0;
}
.vendor-logo-img{

    width:110px;
    height:110px;
    object-fit:contain;
    margin-right:-15px;

}

.logo-icon{
    width:42px;
    height:42px;
    border-radius:12px;

    background:linear-gradient(
        135deg,
        #0f766e,
        #14b8a6,
        #2563eb
    );

    display:flex;
    align-items:center;
    justify-content:center;

    color:#fff;
    font-size:18px;
    font-weight:700;
}

.logo-text{
    font-size:20px;
    font-weight:700;
    color:#111827;
}

.vendor-menu{
    display:flex;
    align-items:center;
    gap:12px;
}

.vendor-menu a{
    text-decoration:none;
    color:#475569;
    font-size:14px;
    font-weight:600;

    padding:10px 18px;
    border-radius:12px;

    transition:.25s;
}

.vendor-menu a:hover{
    background:#f1f5f9;
    color:#0f766e;
}

.vendor-menu a.active{
    background:#ecfeff;
    color:#0f766e;
}

.vendor-right{
    display:flex;
    align-items:center;
    gap:15px;
}

.vendor-user{
    text-align:right;
}

.vendor-user span{
    display:block;
    font-size:12px;
    color:#94a3b8;
}

.vendor-user strong{
    font-size:14px;
    color:#111827;
}

.logout-btn{
    text-decoration:none;
    background:#ef4444;
    color:white;

    padding:10px 18px;
    border-radius:12px;

    font-size:13px;
    font-weight:600;

    transition:.25s;
}

.logout-btn:hover{
    background:#dc2626;
    transform:translateY(-2px);
}

@media(max-width:900px){

    .vendor-navbar{
        flex-direction:column;
        height:auto;
        padding:20px;
        gap:15px;
    }

    .vendor-menu{
        flex-wrap:wrap;
        justify-content:center;
    }

}

.mailbox{font-size:18px; position:relative; padding:6px 8px; border-radius:8px; cursor:pointer;}
.mailbox:hover{background:#f1f5f9}
.mailbox-badge{position:absolute; top:-6px; right:-6px; background:#ef4444; color:#fff; width:20px; height:20px; border-radius:50%; display:inline-grid; place-items:center; font-size:12px; font-weight:800;}
</style>

<div class="vendor-navbar">

 <div class="vendor-logo">

    <img src="/event_saas/assets/images/logo.png"
         alt="Event Booking Logo"
         class="vendor-logo-img">

    <div class="logo-text">
        Event Pro Booking
    </div>

</div>

    <div class="vendor-menu">

        <a href="/event_saas/vendor/dashboard.php">
            🏠 Dashboard
        </a>

        <a href="/event_saas/vendor/profile.php">
            👤 Profile
        </a>

        <a href="/event_saas/vendor/calendar/index.php">
            📅 Calendar
        </a>

        <a href="/event_saas/vendor/jobs/index.php">
            📋 Jobs
        </a>

    </div>

    <?php
    // Fetch unread booking/payment notifications for this vendor
    $vendor_id = $_SESSION['user_id'];
    $mailQ = mysqli_query($conn, "
        SELECT an.* FROM admin_notifications an
        JOIN bookings b ON an.booking_id = b.id
        JOIN booking_vendors bv ON b.id = bv.booking_id
        WHERE an.type IN ('booking_confirmed', 'vendor_booking_added', 'payment_deposit', 'payment_balance')
        AND bv.vendor_id = '$vendor_id'
        AND an.is_read = 0
        ORDER BY an.created_at DESC
        LIMIT 10
    ");
    $mailCount = ($mailQ) ? mysqli_num_rows($mailQ) : 0;
    ?>

    <div style="position:relative; display:inline-block;">
        <div class="mailbox" onclick="toggleVendorMailbox()" title="Notifications">
            📬
            <?php if ($mailCount > 0): ?>
                <span class="mailbox-badge" id="vendorMailboxBadge"><?= $mailCount ?></span>
            <?php endif; ?>
        </div>

        <div class="mailbox-dropdown" id="vendorMailboxDropdown" style="display:none; position:absolute; right:0; top:120%; min-width:340px; background:white; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 10px 30px rgba(2,6,23,.08); z-index:2000;">
            <div style="padding:12px 14px; border-bottom:1px solid #f3f4f6; font-weight:800;">Order & Payment Notifications</div>
            <div style="max-height:320px; overflow:auto;">
                <?php if ($mailCount > 0): while($m = mysqli_fetch_assoc($mailQ)): ?>
                    <div class="mail-item" style="padding:10px 14px; border-bottom:1px solid #f8fafc; display:flex; justify-content:space-between; gap:12px; align-items:center;">
                        <div style="flex:1; font-size:13px; color:#0f172a;">
                            <?= htmlspecialchars($m['message']) ?>
                            <div style="font-size:12px; color:#64748b; margin-top:6px;"><?= date('M d, Y H:i', strtotime($m['created_at'])) ?></div>
                        </div>
                        <div style="white-space:nowrap;">
                            <button onclick="markVendorMailRead(<?= intval($m['id']) ?>)" style="padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb;background:white;cursor:pointer;">Mark</button>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div style="padding:16px;color:#64748b;">No notifications</div>
                <?php endif; ?>
            </div>
            <div style="padding:8px 12px; text-align:center; border-top:1px solid #f3f4f6;"><a href="/event_saas/vendor/jobs/index.php" style="text-decoration:none;color:#2563eb;font-weight:700;">View all jobs</a></div>
        </div>
    </div>

    <div class="vendor-right">

        <div class="vendor-user">
            <span>Vendor Account</span>
            <strong><?= $_SESSION['name'] ?? 'Vendor'; ?></strong>
        </div>

        <a href="/event_saas/auth/logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</div>

<script>
function toggleVendorMailbox(){
    var dd = document.getElementById('vendorMailboxDropdown');
    if(!dd) return;
    dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', function(e){
    var dd = document.getElementById('vendorMailboxDropdown');
    var box = document.querySelector('.mailbox');
    if(!dd || !box) return;
    if(!box.contains(e.target) && !dd.contains(e.target)){
        dd.style.display = 'none';
    }
});

function markVendorMailRead(id){
    fetch('/event_saas/vendor/mark_notification_read.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'notification_id='+id
    }).then(r=>r.json()).then(data=>{
        if(data.success){
            var badge = document.getElementById('vendorMailboxBadge');
            if(badge){
                var n = parseInt(badge.innerText||'0') - 1;
                if(n <= 0) badge.remove(); else badge.innerText = n;
            }
            event.target.closest('.mail-item').remove();
        } else {
            alert('Could not mark notification');
        }
    }).catch(()=> alert('Network error'));
}
</script>