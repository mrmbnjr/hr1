ALTER TABLE users
    ADD COLUMN email VARCHAR(150) NULL AFTER username;

UPDATE users
    SET email = 'admin@ramyum.com'
    WHERE username = 'admin';

ALTER TABLE users
    ADD COLUMN email_verified_at DATETIME NULL AFTER email;

ALTER TABLE users
    ADD CONSTRAINT uq_users_employee UNIQUE (employee_id);

CREATE TABLE login_otps (
    otp_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    INDEX idx_login_otps_user (user_id),
    INDEX idx_login_otps_expires (expires_at)
);

