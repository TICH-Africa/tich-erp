-- Backfill submission timestamps only for objective submissions that already have a saved grade.
-- Unmarked autosaved drafts are intentionally left unchanged.
START TRANSACTION;

UPDATE `objective_submissions`
SET `student_submitted_at` = COALESCE(`auto_graded_at`, `updated_at`, `created_at`)
WHERE `student_submitted_at` IS NULL
  AND (`auto_graded_at` IS NOT NULL OR `score_obtained` IS NOT NULL);

SELECT ROW_COUNT() AS `backfilled_submissions`;

COMMIT;
