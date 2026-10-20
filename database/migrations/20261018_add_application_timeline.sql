-- 2026-10-18: Application timeline - one row per pipeline action.
--
-- The internal audit trail for application-stage transitions and
-- rejection reasons (kept out of candidate emails).

CREATE TABLE application_timeline (
    event_id BIGINT AUTO_INCREMENT PRIMARY KEY,

    application_id INT NOT NULL,

    from_status VARCHAR(30) NULL,
    to_status VARCHAR(30) NULL,

    action VARCHAR(150) NOT NULL,

    actor_user_id INT NULL,
    note TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (application_id)
        REFERENCES applications(application_id)
        ON DELETE CASCADE,

    FOREIGN KEY (actor_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,

    INDEX idx_application_timeline_application (application_id, created_at)
);
