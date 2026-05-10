-- =========================================================
-- DSWD MAX PAYOUT QUEUEING SYSTEM
-- MEMBER 1 OUTPUT: DATABASE REVISION / DATA STRUCTURE
-- =========================================================
-- Purpose:
-- Update the database so the system can support the revised DSWD workflow:
-- Step 1 Verifier -> Step 2 Assessment -> Step 3 Release -> Paid
--
-- Main revisions covered:
-- 1. Queue type: regular / priority
-- 2. Queue number format: PAL-0001 / PRIO-0001
-- 3. Workflow status: Step 2, Step 3, Paid
-- 4. Table/counter number
-- 5. Called, assessed, and paid timestamps
-- 6. Beneficiary/master list fields from the DSWD spreadsheet layout
-- 7. Sample records for testing
-- =========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";

-- =========================================================
-- PART 1: BENEFICIARIES TABLE REVISION
-- =========================================================
-- These columns support the DSWD verifier/master list layout.

ALTER TABLE beneficiaries
ADD COLUMN middle_name VARCHAR(100) NULL AFTER first_name,
ADD COLUMN ext_name VARCHAR(50) NULL AFTER last_name,
ADD COLUMN region VARCHAR(100) NULL AFTER program_type,
ADD COLUMN province VARCHAR(100) NULL AFTER region,
ADD COLUMN city_municipality VARCHAR(150) NULL AFTER province,
ADD COLUMN barangay VARCHAR(150) NULL AFTER city_municipality,
ADD COLUMN birthday_month TINYINT NULL AFTER contact_number,
ADD COLUMN birthday_day TINYINT NULL AFTER birthday_month,
ADD COLUMN birthday_year SMALLINT NULL AFTER birthday_day,
ADD COLUMN age INT NULL AFTER birthday_year,
ADD COLUMN sex VARCHAR(20) NULL AFTER age,
ADD COLUMN lgu VARCHAR(150) NULL AFTER sex;

-- =========================================================
-- PART 2: QUEUE ENTRIES TABLE REVISION
-- =========================================================
-- These columns support the new DSWD queue workflow.

ALTER TABLE queue_entries
ADD COLUMN queue_type VARCHAR(20) DEFAULT 'regular' AFTER queue_number,
ADD COLUMN workflow_status VARCHAR(50) DEFAULT 'WAITING_STEP_2' AFTER status,
ADD COLUMN table_number INT NULL AFTER workflow_status,
ADD COLUMN called_at DATETIME NULL AFTER table_number,
ADD COLUMN assessed_at DATETIME NULL AFTER called_at,
ADD COLUMN paid_at DATETIME NULL AFTER assessed_at;

-- =========================================================
-- PART 3: PAYOUTS TABLE REVISION
-- =========================================================
-- This records the final paid timestamp for payout history.

ALTER TABLE payouts
ADD COLUMN paid_at DATETIME NULL AFTER released_at;

-- =========================================================
-- PART 4: BACKFILL OLD DATA
-- =========================================================
-- This converts the old waiting/serving/released status into the new workflow.

UPDATE queue_entries
SET workflow_status =
    CASE
        WHEN status = 'waiting' THEN 'WAITING_STEP_2'
        WHEN status = 'serving' THEN 'CALLED_STEP_2'
        WHEN status = 'released' THEN 'PAID'
        ELSE 'WAITING_STEP_2'
    END;

UPDATE payouts
SET paid_at = released_at
WHERE paid_at IS NULL
AND released_at IS NOT NULL;

-- =========================================================
-- PART 5: PERFORMANCE INDEXES
-- =========================================================
-- These indexes help speed up verifier, counter, display, and payout queries.

CREATE INDEX idx_beneficiaries_name_contact
ON beneficiaries (last_name, first_name, contact_number);

CREATE INDEX idx_queue_workflow_date
ON queue_entries (transaction_date, workflow_status);

CREATE INDEX idx_queue_type_date
ON queue_entries (transaction_date, queue_type);

CREATE INDEX idx_queue_table_status
ON queue_entries (table_number, workflow_status);

CREATE INDEX idx_payout_paid_at
ON payouts (paid_at);

-- =========================================================
-- PART 6: SAMPLE RECORDS FOR TESTING
-- =========================================================
-- Run this part only for testing/demo purposes.
-- Do not use sample records if real DSWD data is already loaded.

INSERT INTO beneficiaries (
    first_name,
    middle_name,
    last_name,
    ext_name,
    contact_number,
    program_type,
    region,
    province,
    city_municipality,
    barangay,
    birthday_month,
    birthday_day,
    birthday_year,
    age,
    sex,
    lgu,
    sms_opt_in
)
VALUES
(
    'Juan',
    'Santos',
    'Dela Cruz',
    '',
    '09171234567',
    'AICS',
    'NCR',
    'Metro Manila',
    'Quezon City',
    'Commonwealth',
    5,
    12,
    1985,
    40,
    'Male',
    'Quezon City',
    0
),
(
    'Maria',
    'Reyes',
    'Santos',
    '',
    '09181234567',
    'AICS',
    'NCR',
    'Metro Manila',
    'Quezon City',
    'Batasan',
    8,
    20,
    1990,
    35,
    'Female',
    'Quezon City',
    0
);

-- Sample queue entries for testing.
-- Change beneficiary_id values if your database already has different IDs.

INSERT INTO queue_entries (
    queue_number,
    queue_type,
    beneficiary_id,
    transaction_date,
    status,
    workflow_status,
    table_number
)
VALUES
(
    'PAL-0001',
    'regular',
    1,
    CURDATE(),
    'waiting',
    'WAITING_STEP_2',
    NULL
),
(
    'PRIO-0001',
    'priority',
    2,
    CURDATE(),
    'waiting',
    'WAITING_STEP_2',
    NULL
);

-- =========================================================
-- FINAL STANDARD VALUES FOR ALL MEMBERS
-- =========================================================
-- queue_type values:
-- regular
-- priority
--
-- queue_number format:
-- regular  = PAL-0001, PAL-0002, PAL-0003
-- priority = PRIO-0001, PRIO-0002, PRIO-0003
--
-- workflow_status values:
-- WAITING_STEP_2  = waiting for assessment
-- CALLED_STEP_2   = called to assessment table
-- WAITING_STEP_3  = assessed, waiting for release
-- CALLED_STEP_3   = called to release table
-- PAID            = payout completed
-- CANCELLED       = removed/cancelled queue entry
-- =========================================================
