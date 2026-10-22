-- Timetable posting columns for production (tich_erp database)
-- Paste the whole file into phpMyAdmin > SQL > Go. Already-present columns are skipped, so it is safe to run twice.

-- 1. Where a timetable came from, and how far the semester posting has got
SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetables' AND column_name = 'source'),
    'SELECT ''program_timetables.source already exists''', 'ALTER TABLE program_timetables ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT ''generated'' AFTER status');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetables' AND column_name = 'source_file'),
    'SELECT ''skip source_file''', 'ALTER TABLE program_timetables ADD COLUMN source_file VARCHAR(255) NULL AFTER source');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetables' AND column_name = 'source_name'),
    'SELECT ''skip source_name''', 'ALTER TABLE program_timetables ADD COLUMN source_name VARCHAR(255) NULL AFTER source_file');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetables' AND column_name = 'source_uploaded_at'),
    'SELECT ''skip source_uploaded_at''', 'ALTER TABLE program_timetables ADD COLUMN source_uploaded_at DATETIME NULL AFTER source_name');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetables' AND column_name = 'source_uploaded_by'),
    'SELECT ''skip source_uploaded_by''', 'ALTER TABLE program_timetables ADD COLUMN source_uploaded_by BIGINT UNSIGNED NULL AFTER source_uploaded_at');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetables' AND column_name = 'progress_note'),
    'SELECT ''skip progress_note''', 'ALTER TABLE program_timetables ADD COLUMN progress_note VARCHAR(255) NULL AFTER generation_notes');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetables' AND column_name = 'week_span'),
    'SELECT ''skip week_span''', 'ALTER TABLE program_timetables ADD COLUMN week_span TINYINT UNSIGNED NULL AFTER progress_note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- 2. Each class on a timetable: which week or month of the semester, online or physical, and its contact hours
SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetable_sessions' AND column_name = 'session_date'),
    'SELECT ''skip session_date''', 'ALTER TABLE program_timetable_sessions ADD COLUMN session_date DATE NULL AFTER end_time');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetable_sessions' AND column_name = 'week_no'),
    'SELECT ''skip week_no''', 'ALTER TABLE program_timetable_sessions ADD COLUMN week_no TINYINT UNSIGNED NULL AFTER session_date');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetable_sessions' AND column_name = 'source'),
    'SELECT ''skip sessions.source''', 'ALTER TABLE program_timetable_sessions ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT ''generated'' AFTER week_no');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetable_sessions' AND column_name = 'external_ref'),
    'SELECT ''skip external_ref''', 'ALTER TABLE program_timetable_sessions ADD COLUMN external_ref VARCHAR(120) NULL AFTER source');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetable_sessions' AND column_name = 'delivery_mode'),
    'SELECT ''skip delivery_mode''', 'ALTER TABLE program_timetable_sessions ADD COLUMN delivery_mode VARCHAR(20) NOT NULL DEFAULT ''physical'' AFTER session_type');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetable_sessions' AND column_name = 'recurrence'),
    'SELECT ''skip recurrence''', 'ALTER TABLE program_timetable_sessions ADD COLUMN recurrence VARCHAR(20) NOT NULL DEFAULT ''weekly'' AFTER delivery_mode');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetable_sessions' AND column_name = 'contact_hours'),
    'SELECT ''skip contact_hours''', 'ALTER TABLE program_timetable_sessions ADD COLUMN contact_hours DECIMAL(6,2) NULL AFTER recurrence');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- 4. Last week or month a class runs to, so week 1 and week 2 timetables can sit side by side
SET @s = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'program_timetable_sessions' AND column_name = 'week_to'),
    'SELECT ''skip week_to''', 'ALTER TABLE program_timetable_sessions ADD COLUMN week_to TINYINT UNSIGNED NULL AFTER week_no');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- 5. Check the result
SELECT table_name, column_name, column_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND ((table_name = 'program_timetables' AND column_name IN ('source', 'source_file', 'source_name', 'source_uploaded_at', 'source_uploaded_by', 'progress_note', 'week_span'))
    OR (table_name = 'program_timetable_sessions' AND column_name IN ('session_date', 'week_no', 'week_to', 'source', 'external_ref', 'delivery_mode', 'recurrence', 'contact_hours')))
ORDER BY table_name, ordinal_position;
