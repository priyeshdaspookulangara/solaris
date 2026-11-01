-- Solaris / Solaria 40 Database Schema
CREATE DATABASE IF NOT EXISTS solaris CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE solaris;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    wallet_balance DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    total_invested DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    total_earned DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    total_withdrawn DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    referral_code VARCHAR(20) NOT NULL UNIQUE,
    referred_by_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS investments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    units INT NOT NULL DEFAULT 1,
    capital_amount DECIMAL(12, 2) NOT NULL, -- Units * 10.00
    daily_investor_benefit DECIMAL(10, 2) NOT NULL, -- Units * 0.50
    daily_referral_income DECIMAL(10, 2) NOT NULL, -- Units * 0.25
    cycle_total_days INT NOT NULL DEFAULT 40,
    days_completed INT NOT NULL DEFAULT 0,
    total_benefit_distributed DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    total_referral_distributed DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    last_distribution_date DATE NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS daily_distributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investment_id INT NOT NULL,
    user_id INT NOT NULL,
    day_number INT NOT NULL, -- 1 to 40
    investor_benefit DECIMAL(10, 2) NOT NULL,
    referral_income DECIMAL(10, 2) NOT NULL,
    referrer_id INT NULL,
    liquidity_status VARCHAR(50) NOT NULL DEFAULT 'Withdrawable daily',
    distribution_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investment_id) REFERENCES investments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_inv_day (investment_id, day_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    payment_method VARCHAR(50) NOT NULL DEFAULT 'USDT-TRC20',
    destination_address VARCHAR(255) NOT NULL,
    note VARCHAR(255) NULL,
    processed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('deposit', 'investment', 'daily_benefit', 'referral_income', 'withdrawal', 'withdrawal_refund') NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    balance_after DECIMAL(12, 2) NOT NULL,
    description TEXT NULL,
    reference_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
