-- Admin Notifications Table
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
