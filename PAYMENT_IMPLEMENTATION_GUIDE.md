# Payment Gateway Implementation - Complete Setup Guide

## 📋 Overview
This implementation adds a 10% deposit payment system to the customer booking flow in your Event SaaS platform.

---

## 🚀 Quick Start

### Step 1: Database Setup
Run the SQL script to create the necessary tables:

```sql
-- Run this file in your database
config/payment_setup.sql
```

This creates:
- `payments` - Main payment transaction table
- `payment_methods` - Payment method configuration
- `payment_history` - Track payment history

---

## 🔄 Customer Booking Flow

### 1. **Booking Creation** 
   - Customer selects date, package, and vendors
   - Booking status: `Payment Pending`
   - **Redirect:** `payment.php?booking_id={id}`

### 2. **Payment Page** (`customer/payment.php`)
   - Shows 10% deposit calculation
   - Lists available payment methods
   - Displays booking summary
   - Calculates:
     * Deposit: 10% of total
     * Remaining: 90% of total
   - **Action:** Submit payment → Creates payment record

### 3. **Payment Confirmation** (`customer/payment_confirmation.php`)
   - Shows payment details
   - Displays bank transfer information
   - Allows upload of payment proof (optional)
   - Updates booking status to `Confirmed`
   - **After:** Payment marked as `Completed`

### 4. **My Bookings** (`customer/my_bookings.php`)
   - Shows payment status badge
   - Display deposit amount and remaining balance
   - **Quick Actions:**
     * "Pay Now" button if payment pending
     * "View Payment Details" if payment completed

---

## 💳 Payment Methods Included

Default payment methods configured:
1. **Bank Transfer (FPX)** - Fast & easy bank transfer
2. **Credit Card** - Visa, Mastercard
3. **Online Banking** - Direct online banking transfer
4. **E-Wallet** - GCash, Boost, Touch n Go

*(Modify in database: `payment_methods` table)*

---

## 💰 Payment Calculations

```
Total Amount: RM 1,000
Deposit (10%): RM 100 ← Customer pays now
Remaining (90%): RM 900 ← Due before event date
```

---

## 📁 Files Created/Modified

### New Files Created:
```
✓ config/payment_setup.sql           - Database tables
✓ customer/payment.php                - Payment gateway page
✓ customer/payment_confirmation.php  - Confirm payment
✓ admin/payment_management.php        - Admin dashboard
```

### Files Modified:
```
✓ customer/booking.php               - Redirect to payment
✓ customer/my_bookings.php          - Show payment status
```

---

## 🔐 Database Tables

### payments
```sql
- id (PRIMARY KEY)
- booking_id (UNIQUE)
- user_id
- amount (total)
- deposit_amount (10%)
- remaining_amount (90%)
- payment_method (bank_transfer, credit_card, etc.)
- transaction_id
- payment_status (Pending, Completed, Failed, Refunded)
- payment_proof_file
- bank_ref_number
- notes
- created_at
- updated_at
```

### payment_methods
```sql
- id (PRIMARY KEY)
- method_code (unique identifier)
- method_name
- description
- bank_name
- bank_account_number
- bank_account_holder
- is_active
- created_at
```

### payment_history
```sql
- id (PRIMARY KEY)
- booking_id
- payment_type (Deposit, Balance, Full, Refund)
- amount
- payment_method
- transaction_id
- payment_status
- payment_proof_file
- created_at
```

---

## 👥 Admin Payment Management

### Access: `/admin/payment_management.php`

**Features:**
- Dashboard with payment statistics
- Filter by status (All, Pending, Completed, Failed)
- View all payment transactions
- Approve pending payments
- Reject payments with reason
- View payment proofs (uploaded files)

**Admin Actions:**
1. **Review Payment** - Check transaction details and proof
2. **Approve Payment** - Mark as completed (auto-confirms booking)
3. **Reject Payment** - Mark as failed + add rejection reason

---

## 🎯 Key Features

### ✓ 10% Deposit System
- Automatic calculation of deposit and remaining amount
- Clear breakdown in UI

### ✓ Multiple Payment Methods
- Bank transfer with account details
- Credit card option
- E-wallet support
- Extensible for more methods

### ✓ Payment Proof Upload
- Customers can upload receipts/screenshots
- Optional (admin can mark manually)
- File size limit: 5MB
- Formats: PDF, JPG, PNG, GIF

### ✓ Status Tracking
- Pending: Awaiting admin approval
- Completed: Payment verified
- Failed: Rejected by admin
- Refunded: Full refund issued

### ✓ Admin Dashboard
- Real-time statistics
- Quick payment approval/rejection
- Payment proof view
- Transaction history

---

## 🔧 Configuration

### Edit Bank Details
File: `customer/payment_confirmation.php` (Line ~230)

```php
<p><span class="label">Bank Name:</span> MAYBANK BERHAD</p>
<p><span class="label">Account Holder:</span> EVENT SAAS MANAGEMENT</p>
<p><span class="label">Account Number:</span> 170098765432</p>
```

### Add/Remove Payment Methods
Database: `payment_methods` table

```sql
INSERT INTO payment_methods 
(method_code, method_name, description, is_active) 
VALUES ('crypto', 'Cryptocurrency', 'Bitcoin payment', 1);
```

### Adjust Deposit Percentage
Current: 10% in calculation

To change to different percentage, modify:
- `customer/payment.php`: Line ~20
- `customer/payment_confirmation.php`: Line ~55
- `customer/my_bookings.php`: Line ~75

```php
$deposit_amount = $total_amount * 0.15; // 15% deposit
$remaining_amount = $total_amount * 0.85; // 85% remaining
```

---

## 📧 Integration Points

### Send Confirmation Email
After payment completion, add email:

```php
// In payment_confirmation.php after payment mark as completed
mail($customer_email, 
     'Payment Received - Booking Confirmed', 
     'Your deposit has been received...');
```

### Webhook Integration
To integrate with actual payment gateway (Stripe, PayPal):

```php
// In process_payment.php
$stripe = new \Stripe\StripeClient($stripe_key);
$charge = $stripe->charges->create([
    'amount' => $deposit_amount * 100,
    'currency' => 'myr',
    'source' => $_POST['stripe_token']
]);
```

---

## 🛠️ Troubleshooting

### Payment Page Not Loading
- Check booking exists: `SELECT * FROM bookings WHERE id = X`
- Verify user permission in session
- Check URL parameter: `?booking_id=X`

### File Upload Not Working
- Verify `/uploads/payments/` directory exists and is writable
- Check file size (max 5MB)
- Verify file extension is allowed (PDF, JPG, PNG, GIF)

### Admin Cannot Approve Payment
- Verify user has admin role: `$_SESSION['role'] = 'admin'`
- Check payment_id parameter

### Booking Not Marked as Confirmed
- Verify SQL UPDATE statement in payment_confirmation.php
- Check booking status column exists in bookings table

---

## 📊 Testing Checklist

- [ ] Create test booking
- [ ] Navigate to payment.php
- [ ] Select payment method
- [ ] Submit payment form
- [ ] View payment confirmation page
- [ ] Upload payment proof (test file)
- [ ] Admin approves payment
- [ ] Booking marked as Confirmed
- [ ] Payment status shows in My Bookings
- [ ] Check admin dashboard statistics

---

## 🔗 Page Reference

```
Customer Journey:
1. Book Event → booking.php
2. Select Package/Vendors → booking.php (form)
3. Redirect to Payment → payment.php?booking_id=X
4. Confirm Payment → payment_confirmation.php?payment_id=X
5. View Bookings → my_bookings.php (shows payment status)

Admin:
1. View Payments → admin/payment_management.php
2. Approve/Reject → payment_management.php (POST action)
3. View Details → admin/payment_management.php
```

---

## 📝 Next Steps

### Recommended Enhancements:
1. [ ] Integrate with Stripe/PayPal API
2. [ ] Send email notifications on payment status
3. [ ] SMS reminders for remaining balance payment
4. [ ] Generate payment receipts (PDF)
5. [ ] Add payment schedule for remaining balance
6. [ ] Implement automated payment status updates
7. [ ] Add refund functionality
8. [ ] Generate financial reports

---

**Version:** 1.0
**Last Updated:** May 2026
**Status:** Ready to Deploy
