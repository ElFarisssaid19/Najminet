# Refactoring: Environment Configuration

- [x] Step 0: Analyze current state (`.env` empty, `.env.local` has Google OAuth only)
- [x] Step 1: Create `.env` as template with placeholder values
- [x] Step 2: Update `.env.local` with all required real values
- [x] Step 3: Update `docker-compose.yml` to use env vars instead of hardcoded values
- [x] Step 4: Update `includes/db.php` to remove hardcoded fallback defaults
- [x] Step 5: Verify no hardcoded secrets remain

