# Payment Confirmation & Admin Notification Setup

## Overview
This setup enables:
1. ✅ Payment confirmation page after customer submits payment
2. ✅ Admin notifications when deposits are received
3. ✅ Notification bell icon in admin header
4. ✅ Full notifications management page

---

## 🔧 Setup Instructions

### Step 1: Create Admin Notifications Table
Run the SQL file to create the notifications table:

```sql
-- In phpMyAdmin or your MySQL client, run:
CREATE TABLE IF NOT EXISTS admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL COMMENT 'payment_deposit, payment_completed, payment_failed, booking_created, etc',
    message TEXT NOT NULL,
    company_id INT,
    booking_id INT,
    payment_id INT,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    INDEX idx_type (type),
    INDEX idx_company (company_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);
```

Or simply upload and execute: `config/admin_notifications_setup.sql`

---

## 📋 Files Created/Modified

### New Files:
- `admin/notification_widget.php` - Notification bell icon widget for header
- `admin/mark_notification_read.php` - AJAX handler to mark notifications read
- `admin/admin_notifications.php` - Full notifications management page
- `config/admin_notifications_setup.sql` - SQL setup file

### Modified Files:
- `customer/payment.php` - Now redirects to payment_confirmation and sends admin notification
- `admin/header.php` - Added notification widget and Payment management link

---

## 🔄 Payment Flow

### Customer:
1. Customer visits booking payment page → `/customer/payment.php?booking_id=X`
2. Selects payment method and submits form
3. **NEW:** Gets redirected to `/customer/payment_confirmation.php?payment_id=X` with success message
4. Can upload proof of payment or confirm
5. Booking marked as "Payment Pending" until admin approves

### Admin:
1. **NEW:** Gets immediate notification when payment deposit received
2. Notification shows:
   - Customer email
   - Deposit amount (10% of total)
   - Booking ID
3. Can click notification to view details
4. Can view all notifications in `/admin/admin_notifications.php`
5. Approves payment in payment management page → booking becomes "Confirmed"

---

## 📌 What Happens When Payment Submitted

### Customer Side:
```
Payment Form Submitted
    ↓
Payment record created/updated (status: 'Pending')
    ↓
Admin notification created
    ↓
Booking status updated to 'Payment Pending'
    ↓
Redirect to Payment Confirmation Page
    ↓
Shows success message + payment details
```

### Admin Side:
```
Notification bell shows badge with unread count
    ↓
Can click bell to see dropdown of recent notifications
    ↓
Click notification to mark as read or view all
    ↓
Go to Payments page to approve/reject payment
```

---

## 🔔 Notification Features

### Notification Widget (Admin Header):
- ✅ Red badge with unread notification count
- ✅ Dropdown showing last 10 notifications
- ✅ Click notifications to mark as read
- ✅ Link to full notifications page

### Admin Notifications Page:
- ✅ View all notifications (max 100 recent)
- ✅ Filter by type (payment_deposit, etc.)
- ✅ Mark all as read
- ✅ Delete individual notifications
- ✅ Clear all notifications
- ✅ Shows booking ID and timestamp

---

## 🧪 Testing

### To Test:
1. Execute the SQL file to create `admin_notifications` table
2. Login as customer
3. Go to booking and submit payment
4. Verify redirected to payment confirmation page
5. Login as admin
6. Check admin header for notification bell
7. Should see new notification about the deposit payment

---

## 💡 Customization

### Add More Notification Types:
Edit `customer/payment.php` to add different notification types:

```php
// Different payment notifications:
'payment_deposit' - Initial 10% deposit received
'payment_full' - Full payment received
'payment_refund' - Refund issued
```

### Email Notifications (Optional):
Add email sending to `notification_widget.php` or create a separate notification service.

---

## ⚠️ Important Notes

1. **Database Required**: Admin must have `admin_notifications` table before payments can be processed
2. **Session**: Notifications only appear for logged-in admin users
3. **Soft Delete**: Deleting notifications doesn't affect payments data
4. **Time Display**: Uses "time ago" format (e.g., "5 minutes ago")

---

## 📞 Support

If notifications don't appear:
1. ✓ Check that `admin_notifications` table exists
2. ✓ Verify `admin/notification_widget.php` is included in header
3. ✓ Check browser console for JavaScript errors
4. ✓ Verify `mark_notification_read.php` file exists

