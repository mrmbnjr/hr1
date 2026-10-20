-- 2026-10-18: Interviewer evaluations for the Interview stage.
--
-- One row per submitted evaluation. No unique constraint on
-- (interview_id, evaluator_id) so Further Assessment rounds can record
-- another evaluation against the same interview; the latest row is the
-- current one.

CREATE TABLE interview_evaluations (
    evaluation_id INT AUTO_INCREMENT PRIMARY KEY,

    interview_id INT NOT NULL,
    evaluator_id INT NOT NULL,

    evaluator_role VARCHAR(30),

    technical_skills TINYINT NOT NULL,
    communication TINYINT NOT NULL,
    problem_solving TINYINT NOT NULL,
    culture_fit TINYINT NOT NULL,

    strengths TEXT,
    weaknesses TEXT,
    remarks TEXT,

    recommendation ENUM(
        'Recommend',
        'Further Assessment',
        'Do Not Recommend'
    ) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (interview_id)
        REFERENCES interviews(interview_id)
        ON DELETE CASCADE,

    FOREIGN KEY (evaluator_id)
        REFERENCES users(user_id)
);
