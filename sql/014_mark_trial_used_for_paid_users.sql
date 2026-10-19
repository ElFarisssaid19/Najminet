-- =====================================================================
-- 014 — users.trial_used for accounts that already paid
-- A successful payment now sets trial_used = 1, so a paying customer can
-- never swap the paid plan for the free trial. This marks the accounts
-- that paid before that change: the Professional plan (current or
-- expired) or a completed payment. No new column. Safe to re-run.
-- =====================================================================

UPDATE users
SET trial_used = 1
WHERE trial_used = 0
  AND (plan = 'professional'
       OR id IN (SELECT user_id FROM payments WHERE status = 'COMPLETED'));
