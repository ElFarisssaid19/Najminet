CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    company_name VARCHAR(150) DEFAULT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    plan VARCHAR(50) DEFAULT NULL,
    subscription_status VARCHAR(20) DEFAULT NULL,
    subscription_start DATETIME DEFAULT NULL,
    subscription_end DATETIME DEFAULT NULL,
    trial_used TINYINT(1) NOT NULL DEFAULT 0,
    stripe_customer_id VARCHAR(255) DEFAULT NULL,
    stripe_subscription_id VARCHAR(255) DEFAULT NULL,
    INDEX idx_subscription_status (subscription_status),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
