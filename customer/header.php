<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: /event_saas/auth/login.php");
    exit;
}
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:'Poppins',sans-serif;
}

.customer-navbar{
    width:100%;
    height:78px;
    padding:0 42px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position:sticky;
    top:0;
    z-index:9999;
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(18px);
    border-bottom:1px solid rgba(226,232,240,.9);
    box-shadow:0 12px 35px rgba(15,23,42,.08);
}

.customer-brand{
    display:flex;
    align-items:center;
    gap:13px;
    text-decoration:none;
}

.brand-icon{
    width:44px;
    height:44px;
    border-radius:16px;
    display:flex;
    justify-content:center;
    align-items:center;
    color:white;
    font-size:20px;
    background:linear-gradient(135deg,#1d4ed8,#7c3aed,#06b6d4);
    box-shadow:0 12px 25px rgba(59,130,246,.35);
}

.brand-text{
    line-height:1.1;
}

.brand-title{
    font-size:22px;
    font-weight:800;
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.brand-subtitle{
    font-size:11px;
    color:#64748b;
    margin-top:3px;
}

.customer-menu{
    display:flex;
    align-items:center;
    gap:10px;
}

.customer-menu a{
    text-decoration:none;
    color:#334155;
    font-size:14px;
    font-weight:600;
    padding:12px 16px;
    border-radius:999px;
    transition:.25s ease;
}

.customer-menu a:hover{
    background:#eef2ff;
    color:#4f46e5;
    transform:translateY(-1px);
}

.customer-menu a.active{
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    color:white;
    box-shadow:0 12px 25px rgba(79,70,229,.25);
}

.logout-link{
    color:#ef4444 !important;
    background:#fff1f2;
}

.logout-link:hover{
    background:#ef4444 !important;
    color:white !important;
}

@media(max-width:768px){
    .customer-navbar{
        height:auto;
        padding:14px 18px;
        flex-direction:column;
        gap:14px;
        align-items:flex-start;
    }

    .customer-menu{
        width:100%;
        overflow-x:auto;
        padding-bottom:4px;
    }

    .customer-menu a{
        white-space:nowrap;
        font-size:13px;
        padding:10px 13px;
    }
}

.mailbox{font-size:20px; position:relative; padding:6px 8px; border-radius:8px;}
.mailbox:hover{background:#f8fafc}
.mailbox-badge{position:absolute; top:-6px; right:-6px; background:#ef4444; color:#fff; width:20px; height:20px; border-radius:50%; display:inline-grid; place-items:center; font-size:12px; font-weight:800;}
</style>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="customer-navbar">

    <a href="/event_saas/customer/dashboard.php" class="customer-brand">
        <div class="brand-icon">🎉</div>

        <div class="brand-text">
            <div class="brand-title">Event Booking</div>
            <div class="brand-subtitle">Premium Event System</div>
        </div>
    </a>

    <div class="customer-menu">

        <?php
        // Fetch unread payment notifications (both deposits and balance reminders)
        $user_id = $_SESSION['user_id'];
        $mailQ = mysqli_query($conn, "SELECT an.* FROM admin_notifications an JOIN bookings b ON an.booking_id = b.id WHERE an.type IN ('payment_deposit', 'payment_balance', 'payment_balance_due') AND b.user_id = '$user_id' AND an.is_read = 0 ORDER BY an.created_at DESC LIMIT 10");
        $mailCount = ($mailQ) ? mysqli_num_rows($mailQ) : 0;
        ?>

        <div style="position:relative; display:inline-block; margin-right:8px;">
            <div class="mailbox" onclick="toggleMailboxDropdown()" style="cursor:pointer;">
                📫
                <?php if ($mailCount > 0): ?>
                    <span class="mailbox-badge" id="mailboxBadge"><?= $mailCount ?></span>
                <?php endif; ?>
            </div>

            <div class="mailbox-dropdown" id="mailboxDropdown" style="display:none; position:absolute; right:0; top:120%; min-width:320px; background:white; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 10px 30px rgba(2,6,23,.08); z-index:2000;">
                <div style="padding:12px 14px; border-bottom:1px solid #f3f4f6; font-weight:800;">Payment Reminders</div>
                <div style="max-height:320px; overflow:auto;">
                    <?php if ($mailCount > 0): while($m = mysqli_fetch_assoc($mailQ)): ?>
                        <div class="mail-item" style="padding:10px 14px; border-bottom:1px solid #f8fafc; display:flex; justify-content:space-between; gap:12px; align-items:center;">
                            <div style="flex:1; font-size:13px; color:#0f172a;">
                                <?= htmlspecialchars($m['message']) ?>
                                <div style="font-size:12px; color:#64748b; margin-top:6px;"><?= date('M d, Y H:i', strtotime($m['created_at'])) ?></div>
                            </div>
                            <div style="white-space:nowrap;">
                                <a href="/event_saas/customer/payment.php?booking_id=<?= intval($m['booking_id']) ?>&pay_type=balance" class="btn-pay-now" style="padding:8px 12px;border-radius:12px;font-weight:800;background:linear-gradient(135deg,#ef4444,#f97316);color:#fff;text-decoration:none;margin-right:8px;">Pay</a>
                                <button onclick="markMailRead(<?= intval($m['id']) ?>)" style="padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb;background:white;cursor:pointer;">Mark</button>
                            </div>
                        </div>
                    <?php endwhile; else: ?>
                        <div style="padding:16px;color:#64748b;">No balance reminders</div>
                    <?php endif; ?>
                </div>
                <div style="padding:8px 12px; text-align:center; border-top:1px solid #f3f4f6;"><a href="/event_saas/customer/my_bookings.php" style="text-decoration:none;color:#2563eb;font-weight:700;">View all reminders</a></div>
            </div>
        </div>

        <a href="/event_saas/customer/dashboard.php"
           class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            Browse
        </a>

        <a href="/event_saas/customer/my_bookings.php"
           class="<?= $current_page == 'my_bookings.php' ? 'active' : '' ?>">
            My Bookings
        </a>

        <a href="/event_saas/customer/account.php"
           class="<?= $current_page == 'account.php' ? 'active' : '' ?>">
            My Account
        </a>

        <a href="/event_saas/auth/logout.php" class="logout-link">
            Logout
        </a>

    </div>

</div>

<script>
function toggleMailboxDropdown(){
    var dd = document.getElementById('mailboxDropdown');
    if(!dd) return;
    dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', function(e){
    var dd = document.getElementById('mailboxDropdown');
    var box = document.querySelector('.mailbox');
    if(!dd || !box) return;
    if(!box.contains(e.target) && !dd.contains(e.target)){
        dd.style.display = 'none';
    }
});

function markMailRead(id){
    fetch('/event_saas/customer/mark_notification_read.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'notification_id='+id
    }).then(r=>r.json()).then(data=>{
        if(data.success){
            var badge = document.getElementById('mailboxBadge');
            if(badge){
                var n = parseInt(badge.innerText||'0') - 1;
                if(n <= 0) badge.remove(); else badge.innerText = n;
            }
            // remove the mail item element
            event.target.closest('.mail-item').remove();
        } else {
            alert('Could not mark notification');
        }
    }).catch(()=> alert('Network error'));
}
</script>