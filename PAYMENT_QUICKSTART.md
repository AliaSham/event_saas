# 🚀 PAYMENT GATEWAY - QUICK START GUIDE

## For Customers 👤

### How to Complete Your Booking

**Your booking has been created! Now let's complete the payment...**

```
STEP 1: Choose Payment Method 💳
├─ Bank Transfer (FPX)
├─ Credit Card (Visa/Mastercard)
├─ Online Banking
└─ E-Wallet (GCash, Boost, etc.)

STEP 2: Calculate What You Pay 💰
├─ Total Booking: RM 1,000
├─ Deposit (10%): RM 100 ← YOU PAY NOW
└─ Remaining (90%): RM 900 ← DUE LATER

STEP 3: Submit Payment 📤
├─ Select your preferred method
├─ Enter reference number (optional)
└─ Click "Proceed to Payment"

STEP 4: Confirm & Upload Proof 📸
├─ Receive bank details
├─ Make the payment
├─ Upload receipt/screenshot (optional)
└─ Click "Confirm Payment Received"

STEP 5: Your Booking is Confirmed! ✅
├─ Receive confirmation email
├─ Check My Bookings status
└─ See deposit and remaining balance
```

---

## For Admins 👨‍💼

### Payment Management Dashboard

**Access:** `/admin/payment_management.php`

```
Dashboard Overview:
├─ Total Revenue (All Deposits)
├─ Pending Payments (Awaiting Approval)
├─ Completed Payments ✓
├─ Failed/Rejected Payments
└─ All Transactions List

For Each Payment:
├─ View customer details
├─ See booking information
├─ View uploaded proof
├─ Approve or Reject
└─ Add notes/reason if rejecting
```

---

## Payment Methods Setup

### Default Methods Configured:

```
1. BANK TRANSFER (FPX)
   ├─ Fastest option in Malaysia
   ├─ Account: MAYBANK BERHAD
   ├─ Holder: EVENT SAAS MANAGEMENT
   └─ Number: 170098765432

2. CREDIT CARD
   ├─ Visa/Mastercard accepted
   ├─ Direct online payment
   └─ Instant confirmation

3. ONLINE BANKING
   ├─ All major banks supported
   ├─ Transfer via mobile app
   └─ Manual verification

4. E-WALLET
   ├─ GCash (Philippines)
   ├─ Boost (Malaysia)
   ├─ Touch n Go (Malaysia)
   └─ Other regional wallets
```

---

## Database Tables Reference

### payments table
Stores all payment transactions

```
Fields:
├─ id (Primary Key)
├─ booking_id (Links to booking)
├─ user_id (Customer ID)
├─ amount (Total booking amount)
├─ deposit_amount (10% amount)
├─ remaining_amount (90% balance)
├─ payment_method (Type of payment)
├─ transaction_id (Gateway reference)
├─ payment_status (Pending/Completed/Failed)
├─ payment_proof_file (Receipt upload)
├─ bank_ref_number (Customer reference)
├─ notes (Admin notes)
├─ created_at (When created)
└─ updated_at (When updated)
```

### payment_methods table
Configuration for available payment methods

```
Fields:
├─ id (Primary Key)
├─ method_code (Unique identifier)
├─ method_name (Display name)
├─ description (What it is)
├─ bank_name (For transfers)
├─ bank_account_number (For transfers)
├─ bank_account_holder (Account owner)
├─ is_active (Enable/disable method)
└─ created_at (When added)
```

### payment_history table
Tracks payment history and future balance payments

```
Fields:
├─ id (Primary Key)
├─ booking_id (Links to booking)
├─ payment_type (Deposit/Balance/Full/Refund)
├─ amount (Payment amount)
├─ payment_method (How paid)
├─ transaction_id (Reference)
├─ payment_status (Completed status)
├─ payment_proof_file (Receipt)
└─ created_at (When paid)
```

---

## API Integration Points

### For Future Enhancements:

```php
// Stripe Integration
$stripe = new \Stripe\StripeClient($stripe_key);
$charge = $stripe->charges->create([
    'amount' => $deposit_amount * 100,
    'currency' => 'myr',
    'source' => $stripe_token
]);

// PayPal Integration
$client = new PayPalClient(...);
$request = new OrdersCreateRequest();
$request->body = [...];
$response = $client->execute($request);

// Email Notification
mail($customer_email, 
     'Payment Received',
     'Your deposit has been received...');

// SMS Reminder
send_sms($customer_phone,
         'Reminder: Balance of RM 900 due on ' . $date);
```

---

## Key URLs

### Customer Pages:
```
Booking: /customer/booking.php?company_id=X&category_id=Y
Payment: /customer/payment.php?booking_id=X
Confirm: /customer/payment_confirmation.php?payment_id=X
Bookings: /customer/my_bookings.php
```

### Admin Pages:
```
Management: /admin/payment_management.php
Details: /admin/payment_details.php?id=X
View Proof: /uploads/payments/[filename]
```

---

## File Locations

```
event_saas/
├─ config/
│  └─ payment_setup.sql          ← Database setup
├─ customer/
│  ├─ booking.php                 ← Modified (redirect to payment)
│  ├─ payment.php                 ← NEW (payment gateway)
│  ├─ payment_confirmation.php    ← NEW (confirm payment)
│  └─ my_bookings.php             ← Modified (show status)
├─ admin/
│  ├─ payment_management.php      ← NEW (admin dashboard)
│  └─ payment_details.php         ← NEW (view payment)
├─ uploads/
│  └─ payments/                   ← NEW (proof uploads)
├─ PAYMENT_IMPLEMENTATION_GUIDE.md ← Complete guide
└─ PAYMENT_GATEWAY_SUMMARY.md     ← This file
```

---

## Status Codes

```
Payment Status:
├─ Pending      = Awaiting admin approval
├─ Completed    = Approved and confirmed
├─ Failed       = Rejected by admin
└─ Refunded     = Refund issued

Booking Status:
├─ Payment Pending  = Awaiting payment
├─ Confirmed        = Payment received
└─ [Other statuses] = Existing statuses
```

---

## Common Tasks

### As a Customer:
1. **Make a payment**
   - Book event → Select package → Make deposit payment

2. **View payment status**
   - Go to "My Bookings" → See payment badge

3. **Upload proof**
   - After selecting method → Upload receipt/screenshot

### As an Admin:
1. **Approve payment**
   - Go to Payment Management → Click "Approve"

2. **Reject payment**
   - Click "Reject" → Enter reason → Confirm

3. **View details**
   - Click "View" on any transaction → See full details

4. **Download proof**
   - In details page → Click "View/Download Proof"

---

## Troubleshooting Quick Tips

| Problem | Solution |
|---------|----------|
| Payment page not loading | Check booking_id in URL |
| Can't upload file | Check file size (max 5MB) & format (PDF/JPG/PNG/GIF) |
| Payment not in admin dashboard | Run database setup SQL script |
| Can't approve payment | Check if status is "Pending", you're logged in as admin |
| Upload directory error | Create `/uploads/payments/` folder with 755 permissions |

---

## Support Resources

```
📖 Complete Guide:     PAYMENT_IMPLEMENTATION_GUIDE.md
📋 Database Schema:    config/payment_setup.sql
📊 Implementation:     PAYMENT_GATEWAY_SUMMARY.md
🆘 This Quick Guide:   PAYMENT_QUICKSTART.md
```

---

**Version:** 1.0 | **Updated:** May 2026 | **Status:** ✅ Ready to Use
