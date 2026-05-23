-- Payment Tables Setup

-- Create payments table to track payment transactions
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    deposit_amount DECIMAL(10, 2) NOT NULL COMMENT '10% of total amount',
    remaining_amount DECIMAL(10, 2) NOT NULL COMMENT '90% remaining',
    payment_method VARCHAR(50) NOT NULL DEFAULT 'bank_transfer' COMMENT 'bank_transfer, credit_card, online_banking, e_wallet',
    transaction_id VARCHAR(100),
    payment_status VARCHAR(50) NOT NULL DEFAULT 'Pending' COMMENT 'Pending, Completed, Failed, Refunded',
    payment_proof_file VARCHAR(255),
    bank_ref_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create payment methods configuration table
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_code VARCHAR(50) NOT NULL UNIQUE,
    method_name VARCHAR(100) NOT NULL,
    description TEXT,
    bank_name VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_account_holder VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default payment methods
INSERT IGNORE INTO payment_methods (method_code, method_name, description, is_active) VALUES
('bank_transfer', 'Bank Transfer (FPX)', 'Fast & easy bank transfer via FPX', 1),
('credit_card', 'Credit Card', 'Visa, Mastercard payment', 1),
('online_banking', 'Online Banking', 'Direct online banking transfer', 1),
('e_wallet', 'E-Wallet', 'GCash, Boost, Touch n Go', 1);

-- Add payment status to bookings table (optional, for quick reference)
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_status VARCHAR(50) DEFAULT 'Unpaid' COMMENT 'Unpaid, Partial, Paid';

-- Create payment history for additional payments (balance payment)
CREATE TABLE IF NOT EXISTS payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    payment_type VARCHAR(50) NOT NULL COMMENT 'Deposit, Balance, Full, Refund',
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100),
    payment_status VARCHAR(50) NOT NULL DEFAULT 'Completed',
    payment_proof_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Add index for faster queries
CREATE INDEX idx_booking_payment ON payments(booking_id);
CREATE INDEX idx_user_payment ON payments(user_id);
CREATE INDEX idx_payment_status ON payments(payment_status);
