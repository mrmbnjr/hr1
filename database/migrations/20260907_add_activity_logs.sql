CREATE TABLE activity_logs (
    activity_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    employee_id INT NULL,
    action VARCHAR(150) NOT NULL,
    page VARCHAR(100) NULL,
    request_method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_activity_employee_created (employee_id, created_at),
    INDEX idx_activity_user_created (user_id, created_at),

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,

    FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE SET NULL
);
