-- Give departments an explicit lifecycle status so the Department overview can
-- report Active / Inactive departments from real records instead of guessing.
--
-- Run in phpMyAdmin against hr1_db, then append the column to schema.sql.
--
-- NOT NULL + DEFAULT backfills every existing row as 'Active', so no separate
-- UPDATE is required and no department is left in an unknown state.

ALTER TABLE departments
    ADD COLUMN status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'
    AFTER department_name;
