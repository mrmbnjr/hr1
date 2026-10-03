-- Password reset: emailed verification codes and HR contact requests.
--
-- Kept separate from `login_otps` so that requesting a password reset can
-- never invalidate a pending sign-in code (and vice versa).

CREATE TABLE password_reset_codes (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_password_reset_codes_user_active (user_id, used_at),
    INDEX idx_password_reset_codes_expires (expires_at),

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE password_reset_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    identifier VARCHAR(150) NOT NULL,
    contact VARCHAR(150) NOT NULL,
    message TEXT NULL,
    status ENUM('Pending', 'Resolved') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolved_by INT NULL,

    INDEX idx_password_reset_requests_status (status, created_at),

    FOREIGN KEY (resolved_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);
