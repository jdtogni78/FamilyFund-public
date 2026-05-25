-- =============================================================================
-- PROD TO DEV DATA ANONYMIZATION SCRIPT
-- =============================================================================
-- Purpose: Sanitize production data after loading into a dev environment.
--          Rotates ALL user passwords (no real prod credential survives) and
--          scrubs ALL personally-identifiable information (PII): names, emails,
--          birthdays, addresses, phone numbers, government ID numbers, 2FA
--          secrets, free-text notes, login/operation logs, sessions, tokens.
-- Usage:   mysql -h 127.0.0.1 -u famfun_dev -p1234 familyfund_dev < prod_to_dev.sql
--          (normally invoked via app1/family-fund-app/bin/prod-to-dev.sh)
--
-- Login after running (NO real personal email survives, not even the admin's):
--   * admin@dev.familyfund.local  password = devpassword123    (admin; was admin@dev.familyfund.local)
--   * claude@test.local           password = claude-test-2024  (CLI test user)
--   * every other user            password = devpassword123    (name/email anonymized)
--   * local dev also has the /dev-login/* auto-login route (see CLAUDE.md); its
--     `admin` alias resolves to admin@dev.familyfund.local (falling back to the
--     pre-anonymization admin@dev.familyfund.local if present).
--
-- Section 1-6 touch tables that have always existed and run unconditionally.
-- Section 7 covers newer (post-2026-01) tables/columns and is GUARDED with
-- information_schema checks: if a column/table is absent (older prod dump) the
-- statement is skipped instead of aborting the script — so a missing newer
-- object can never stop the earlier core PII scrubs from completing.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. ROTATE ALL USER PASSWORDS
-- -----------------------------------------------------------------------------
-- Rotate EVERY user (including the admin) to a known dev hash so no real
-- production password hash is ever copied into a dev database.
-- Hash below = password_hash('devpassword123', PASSWORD_BCRYPT).
UPDATE users
SET password = '$2y$12$/xiO6GzuEI.7s/u.KHDNx.4LK7DlhbKXNEqVypLVKPGGYO8PZ0rPu';

-- -----------------------------------------------------------------------------
-- 2. ANONYMIZE USER IDENTITY
-- -----------------------------------------------------------------------------
-- Anonymize everyone. The admin and CLI test user are handled explicitly below
-- so the dev-login aliases keep working; the bulk pass skips them here.
UPDATE users
SET name = CONCAT(LEFT(name, 1), 'User', id)
WHERE email NOT IN ('admin@dev.familyfund.local', 'claude@test.local');

UPDATE users
SET email = CONCAT('user', id, '@dev.familyfund.local')
WHERE email NOT IN ('admin@dev.familyfund.local', 'claude@test.local');

-- Replace the admin's real personal identity with a non-PII dev admin identity.
-- Login still works via this address (devpassword123) and the dev-login `admin`
-- alias resolves to it.
UPDATE users
SET name  = 'Dev Admin',
    email = 'admin@dev.familyfund.local'
WHERE email = 'admin@dev.familyfund.local';

-- Create/refresh the CLI test user (claude@test.local / claude-test-2024).
-- Runs AFTER the bulk password rotation so it keeps its dedicated test password.
-- Hash = bcrypt('claude-test-2024').
INSERT INTO users (name, email, password, created_at, updated_at)
VALUES ('Claude Test', 'claude@test.local', '$2y$12$RPdNTcpwuZxPZvSSqkpWUeX0MZNOvzPMMcS3uxi1hqHVWlbpW4cHG', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = 'Claude Test',
    password = '$2y$12$RPdNTcpwuZxPZvSSqkpWUeX0MZNOvzPMMcS3uxi1hqHVWlbpW4cHG',
    updated_at = NOW();

-- -----------------------------------------------------------------------------
-- 3. ANONYMIZE PERSON PII (persons + dependent contact tables)
-- -----------------------------------------------------------------------------

-- Names + email + birthday on the person record.
UPDATE persons
SET first_name = CONCAT('First', id),
    last_name  = CONCAT('Last', id);

UPDATE persons
SET email = CONCAT('person', id, '@dev.familyfund.local')
WHERE email IS NOT NULL AND email != '';

-- Neutralize date of birth to a fixed, non-identifying date.
UPDATE persons
SET birthday = '1990-01-01'
WHERE birthday IS NOT NULL;

-- Physical addresses.
UPDATE addresses
SET street     = CONCAT(id, ' Dev Street'),
    number     = id,
    complement = NULL,
    city       = 'Devville',
    state      = 'DV',
    zip_code   = '00000-000';

-- Phone numbers.
UPDATE phones
SET number = CONCAT('+15550000', LPAD(id, 4, '0'));

-- Government ID documents (CPF / RG / CNH / Passport / SSN). Highest sensitivity.
UPDATE iddocuments
SET number = CONCAT('DEV-', type, '-', id);

-- -----------------------------------------------------------------------------
-- 4. ANONYMIZE ACCOUNT / PORTFOLIO LABELS
-- -----------------------------------------------------------------------------
-- NOTE: accounts.code is intentionally preserved (opaque display/join key used
--       across reports and tests; low PII value). See issue #79.

UPDATE accounts
SET nickname = CONCAT('Acct', id)
WHERE nickname IS NOT NULL AND nickname != '';

UPDATE accounts
SET email_cc = CONCAT('account', id, '@dev.familyfund.local')
WHERE email_cc IS NOT NULL AND email_cc != '';

-- Brokerage account holder name on trade portfolios.
UPDATE trade_portfolios
SET account_name = CONCAT('Portfolio Acct ', id)
WHERE account_name IS NOT NULL AND account_name != '';

-- -----------------------------------------------------------------------------
-- 5. GENERICIZE FREE-TEXT NOTES (may quote real people/details)
-- -----------------------------------------------------------------------------

UPDATE transactions
SET descr = CONCAT(type, ' transaction ', id)
WHERE descr IS NOT NULL AND descr != '';

UPDATE cash_deposits
SET description = CONCAT('Cash deposit ', id)
WHERE description IS NOT NULL AND description != '';

UPDATE deposit_requests
SET description = CONCAT('Deposit request ', id)
WHERE description IS NOT NULL AND description != '';

-- Change log mirrors model field changes (incl. person/account edits) as free
-- text — not needed in dev and a PII leak vector. Clear it.
DELETE FROM change_log WHERE 1=1;

-- -----------------------------------------------------------------------------
-- 6. CLEAR SENSITIVE LOGS / TOKENS / SESSIONS
-- -----------------------------------------------------------------------------

-- Remember-me tokens.
UPDATE users SET remember_token = NULL;

-- Password reset tokens.
DELETE FROM password_resets WHERE 1=1;

-- API tokens.
DELETE FROM personal_access_tokens WHERE 1=1;

-- Sessions carry ip_address, user_agent and a serialized payload — clear them
-- (sessions regenerate on next login).
DELETE FROM sessions WHERE 1=1;

-- -----------------------------------------------------------------------------
-- 7. NEWER SCHEMA (post-2026-01) — GUARDED so a missing object is skipped,
--    not fatal. Each block runs only if the table/column is present.
-- -----------------------------------------------------------------------------

-- 7a. Two-factor auth secrets on users (added 2026-01-11). These are real
--     encrypted secrets — clear them.
SET @stmt := IF(
  EXISTS(SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'users'
           AND column_name = 'two_factor_secret'),
  'UPDATE users SET two_factor_secret = NULL, two_factor_recovery_codes = NULL, two_factor_confirmed_at = NULL',
  'DO 0');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- 7b. Login activity log: ip_address, user_agent, browser, platform, device,
--     location. Clear the table.
SET @stmt := IF(
  EXISTS(SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'login_activities'),
  'DELETE FROM login_activities', 'DO 0');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- 7c. Operation log: free-text `message` + JSON `details` may quote user data.
SET @stmt := IF(
  EXISTS(SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'operation_logs'),
  'DELETE FROM operation_logs', 'DO 0');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- 7d. Matching reminder log: JSON `rule_details` may embed account/person data.
SET @stmt := IF(
  EXISTS(SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'matching_reminder_logs'),
  'DELETE FROM matching_reminder_logs', 'DO 0');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- 7e. Credit-line user-authored nickname + description.
SET @stmt := IF(
  EXISTS(SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'account_credit_lines'
           AND column_name = 'nickname'),
  'UPDATE account_credit_lines SET nickname = CONCAT(''Credit line '', id) WHERE nickname IS NOT NULL AND nickname != ''''',
  'DO 0');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

SET @stmt := IF(
  EXISTS(SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'account_credit_lines'
           AND column_name = 'descr'),
  'UPDATE account_credit_lines SET descr = CONCAT(''Credit line '', id) WHERE descr IS NOT NULL AND descr != ''''',
  'DO 0');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- 7f. Credit-line adjustment reason (free text).
SET @stmt := IF(
  EXISTS(SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'credit_line_adjustments'
           AND column_name = 'reason'),
  'UPDATE credit_line_adjustments SET reason = CONCAT(''Adjustment '', id) WHERE reason IS NOT NULL AND reason != ''''',
  'DO 0');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- 7g. Transaction reversal reason (free text).
SET @stmt := IF(
  EXISTS(SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'transaction_reversals'
           AND column_name = 'reason'),
  'UPDATE transaction_reversals SET reason = CONCAT(''Reversal '', id) WHERE reason IS NOT NULL AND reason != ''''',
  'DO 0');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- 7h. Credit-line delay notification recipient email.
SET @stmt := IF(
  EXISTS(SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'credit_line_delay_notifications'
           AND column_name = 'recipient_email'),
  'UPDATE credit_line_delay_notifications SET recipient_email = CONCAT(''notify'', id, ''@dev.familyfund.local'') WHERE recipient_email IS NOT NULL AND recipient_email != ''''',
  'DO 0');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- -----------------------------------------------------------------------------
-- 8. VERIFICATION QUERIES (optional - comment out in automated scripts)
-- -----------------------------------------------------------------------------

-- SELECT id, name, email FROM users LIMIT 10;
-- SELECT id, first_name, last_name, email, birthday FROM persons LIMIT 10;
-- SELECT id, street, city, state, zip_code FROM addresses LIMIT 10;
-- SELECT id, type, number FROM iddocuments LIMIT 10;
-- SELECT id, nickname, email_cc FROM accounts LIMIT 10;

-- =============================================================================
-- END OF SCRIPT
-- =============================================================================
