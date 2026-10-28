-- Support repeatable interview and assessment rounds plus explicit withdrawal.
ALTER TABLE applications
    MODIFY COLUMN application_status ENUM(
        'Submitted',
        'Under Review',
        'Interview',
        'Selection',
        'Offer',
        'Hired',
        'Rejected',
        'Withdrawn'
    ) DEFAULT 'Submitted';

ALTER TABLE interviews
    ADD COLUMN round_type ENUM('Interview', 'Assessment') NOT NULL DEFAULT 'Interview' AFTER interviewer_id,
    MODIFY COLUMN result ENUM('Pending', 'Completed', 'Passed', 'Failed') DEFAULT 'Pending';

UPDATE interviews
SET result = 'Completed'
WHERE result = 'Pending'
  AND EXISTS (
      SELECT 1 FROM interview_evaluations ev WHERE ev.interview_id = interviews.interview_id
  );

ALTER TABLE interviews
    ADD INDEX idx_interviews_application_id (application_id);

ALTER TABLE interviews
    DROP INDEX application_id;
