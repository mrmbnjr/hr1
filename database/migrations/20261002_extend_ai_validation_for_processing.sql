ALTER TABLE application_validations
MODIFY validation_status ENUM(
    'PROCESSING',
    'VALID',
    'REVIEW',
    'INVALID'
) NOT NULL;
