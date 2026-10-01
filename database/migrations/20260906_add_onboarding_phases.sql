-- NOTE: no CREATE DATABASE / USE here. Select your database in phpMyAdmin first, then import.
-- Add phase tracking columns to onboarding table
ALTER TABLE onboarding
    ADD COLUMN system_access_setup_completed BOOLEAN NOT NULL DEFAULT FALSE AFTER onboarding_status,
    ADD COLUMN orientation_setup_completed BOOLEAN NOT NULL DEFAULT FALSE AFTER system_access_setup_completed,
    ADD COLUMN document_submission_completed BOOLEAN NOT NULL DEFAULT FALSE AFTER orientation_setup_completed,
    ADD COLUMN document_verification_completed BOOLEAN NOT NULL DEFAULT FALSE AFTER document_submission_completed;

-- Create onboarding_phases table to track phase progression
CREATE TABLE IF NOT EXISTS onboarding_phases (
    phase_id INT AUTO_INCREMENT PRIMARY KEY,
    onboarding_id INT NOT NULL,
    
    phase_number INT NOT NULL,
    phase_name VARCHAR(100) NOT NULL,
    phase_description TEXT,
    
    status ENUM(
        'pending',
        'in-progress',
        'completed'
    ) DEFAULT 'pending',
    
    completed_at TIMESTAMP NULL DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uq_onboarding_phase (onboarding_id, phase_number),
    FOREIGN KEY (onboarding_id)
        REFERENCES onboarding(onboarding_id)
        ON DELETE CASCADE
);

-- Initialize phases for existing onboarding records
INSERT INTO onboarding_phases (onboarding_id, phase_number, phase_name, phase_description, status, completed_at)
SELECT 
    o.onboarding_id,
    phase.num,
    phase.name,
    phase.description,
    CASE 
        WHEN o.onboarding_status = 'Completed' THEN 'completed'
        WHEN o.onboarding_status = 'In Progress' THEN 'in-progress'
        ELSE 'pending'
    END,
    CASE 
        WHEN o.onboarding_status = 'Completed' THEN COALESCE(o.completed_at, NOW())
        ELSE NULL
    END
FROM onboarding o
CROSS JOIN (
    SELECT 1 AS num, 'System & Access Setup' AS name, 'System automatically checks that the employee account and access are properly created' AS description
    UNION ALL
    SELECT 2, 'Orientation & Setup', 'System checks the employee\'s orientation/setup information'
    UNION ALL
    SELECT 3, 'Document Submission', 'System automatically checks whether all required documents have been submitted'
    UNION ALL
    SELECT 4, 'Document Verification', 'HR/Admin verifies the submitted documents'
    UNION ALL
    SELECT 5, 'Final Onboarding Completion', 'System automatically completes onboarding when everything required is finished'
) AS phase
WHERE NOT EXISTS (
    SELECT 1 FROM onboarding_phases op 
    WHERE op.onboarding_id = o.onboarding_id
);
