-- =============================================================================
-- PROD TO DEV DATA ANONYMIZATION SCRIPT
-- =============================================================================
-- Purpose: Sanitize production data after loading into dev environment
-- Usage:   mysql -h 127.0.0.1 -u famfun -p1234 familyfund_dev < prod_to_dev.sql
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. ANONYMIZE USER DATA
-- -----------------------------------------------------------------------------

-- Anonymize user names (keep first name initial + "User" + ID)
UPDATE users
SET name = CONCAT(LEFT(name, 1), 'User', id)
WHERE email != 'admin@dev.familyfund.local'
  AND email != 'claude@test.local';

-- Anonymize user emails (preserve domain structure for testing)
UPDATE users
SET email = CONCAT('user', id, '@dev.familyfund.local')
WHERE email != 'admin@dev.familyfund.local'
  AND email != 'claude@test.local';

-- Reset all passwords to a known dev password: 'devpassword123'
-- Hash: password_hash('devpassword123', PASSWORD_BCRYPT)
UPDATE users
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email != 'admin@dev.familyfund.local';

-- Restore admin password (original hash for jdtogni)
UPDATE users
SET password = '$2y$10$pQnyQtnYUDe5JObhrKQJkOjFyCHUagUJEItv6iNykfXU/K5Dsg4YC'
WHERE email = 'admin@dev.familyfund.local';

-- -----------------------------------------------------------------------------
-- 2. ANONYMIZE ACCOUNT DATA
-- -----------------------------------------------------------------------------

-- Anonymize account nicknames
UPDATE accounts
SET nickname = CONCAT('Acct', id)
WHERE nickname IS NOT NULL AND nickname != '';

-- Anonymize email_cc addresses (redirect to dev domain)
UPDATE accounts
SET email_cc = CONCAT('account', id, '@dev.familyfund.local')
WHERE email_cc IS NOT NULL AND email_cc != '';

-- -----------------------------------------------------------------------------
-- 3. CLEAR SENSITIVE LOGS/TOKENS (if any)
-- -----------------------------------------------------------------------------

-- Clear remember tokens
UPDATE users SET remember_token = NULL;

-- Clear any password reset tokens
DELETE FROM password_resets WHERE 1=1;

-- Clear personal access tokens (API tokens)
DELETE FROM personal_access_tokens WHERE 1=1;

-- -----------------------------------------------------------------------------
-- 4. VERIFICATION QUERIES (optional - comment out in automated scripts)
-- -----------------------------------------------------------------------------

-- SELECT id, name, email FROM users LIMIT 10;
-- SELECT id, nickname, email_cc FROM accounts LIMIT 10;

-- =============================================================================
-- END OF SCRIPT
-- =============================================================================
