-- Create database
CREATE DATABASE IF NOT EXISTS fina_fins;
USE fina_fins;

-- Create accounts table
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Income', 'Expense') NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) NOT NULL,
    transaction_date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    status ENUM('Posted', 'Pending', 'Voided') DEFAULT 'Pending',
    created_by VARCHAR(100) DEFAULT 'System',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create transaction_details table
CREATE TABLE IF NOT EXISTS transaction_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    account_id INT NOT NULL,
    debit_amount DECIMAL(15, 2) DEFAULT 0.00,
    credit_amount DECIMAL(15, 2) DEFAULT 0.00,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT
);

-- Create disbursements table
CREATE TABLE IF NOT EXISTS disbursements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_number VARCHAR(20) NOT NULL,
    disbursement_date DATE NOT NULL,
    payee VARCHAR(100) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    status ENUM('Posted', 'Pending', 'Voided') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(20) NOT NULL,
    payment_date DATE NOT NULL,
    payer VARCHAR(100) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Accountant', 'Viewer') NOT NULL DEFAULT 'Viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default accounts
INSERT INTO accounts (account_code, account_name, account_type, balance) VALUES
('1000', 'Cash', 'Asset', 600.00),
('1100', 'Accounts Receivable', 'Asset', 0.00),
('2000', 'Accounts Payable', 'Liability', 0.00),
('3000', 'Capital', 'Equity', 600.00),
('4000', 'Revenue', 'Income', 0.00),
('5000', 'Expenses', 'Expense', 0.00);

-- Insert sample transactions
INSERT INTO transactions (reference_number, transaction_date, description, amount, status) VALUES
('JE2025040002', '2025-04-03', 'Initial deposit', 600.00, 'Posted'),
('JE2025040001', '2025-04-03', 'Initial deposit', 600.00, 'Posted'),
('JE2025030010', '2025-03-02', 'Electric Bill', 5000.00, 'Posted'),
('JE2025030009', '2025-03-02', 'Salary for eb 16-28', 40000.00, 'Posted');

-- Insert sample transaction details
INSERT INTO transaction_details (transaction_id, account_id, debit_amount, credit_amount) VALUES
(1, 1, 600.00, 0.00),  -- Debit Cash
(1, 4, 0.00, 600.00),  -- Credit Capital
(3, 6, 5000.00, 0.00), -- Debit Expenses
(3, 1, 0.00, 5000.00); -- Credit Cash

-- Insert sample disbursement
INSERT INTO disbursements (voucher_number, disbursement_date, payee, amount, status) VALUES
('CD2025040001', '2025-04-03', 'Electric Company', 5000.00, 'Posted'),
('CD2025030005', '2025-03-15', 'Office Supplies Inc', 1200.00, 'Posted'),
('CD2025030004', '2025-03-02', 'Payroll', 40000.00, 'Posted'),
('CD2025030003', '2025-02-28', 'Internet Provider', 1500.00, 'Posted');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$8KzO1f6TgWVJU7j0YX9kT.RA9KkXS5RlvioGfMN9Z/XCVv9yLQp4W', 'System Administrator', 'admin@example.com', 'Admin');
