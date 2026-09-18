# SafeLine — Anonymous Campus Feedback & Safety Reporting

Plain HTML, CSS, JS on the frontend. PHP + MySQL (PDO) on the backend. No frameworks.

## Requirements
- PHP 7.4+ (with the `pdo_mysql` extension)
- MySQL or MariaDB
- Any local server stack works: XAMPP, MAMP, WAMP, or PHP's built-in server

## Setup

1. **Create the database.**
   ```
   mysql -u root -p < sql/schema.sql
   ```
   This creates a `safeline` database with three tables: `admins`, `reports`, `report_status_history`.

2. **Set your DB credentials** in `config.php` (host, username, password) — defaults are `root` with no password, matching a fresh XAMPP/MAMP install.

3. **Run the app.**
   - XAMPP/MAMP: drop this `safeline-php` folder into `htdocs`, then visit `http://localhost/safeline-php/`
   - Or with PHP's built-in server, from inside the folder:
     ```
     php -S localhost:8000
     ```
     then visit `http://localhost:8000/`

## How it works

- **Report tab** — `api/submit_report.php` inserts into `reports` + `report_status_history`, returns a tracking code (e.g. `SL-7F2K9`).
- **Track tab** — `api/track_report.php` looks up a report by code and returns its status timeline.
- **Admin tab**
  - `api/admin_register.php` — creates an account (hashed password + hashed security answer via `password_hash`)
  - `api/admin_login.php` — verifies credentials, starts a PHP session
  - `api/admin_forgot_find.php` / `api/admin_forgot_reset.php` — two-step password reset via security question
  - `api/admin_reports.php` / `api/admin_update_status.php` — session-gated (`require_admin()` in `helpers.php`), list and update reports
  - `api/admin_logout.php` — destroys the session

Session state is checked server-side on page load (`index.php` reads `$_SESSION`), so a logged-in admin lands straight on the dashboard on refresh.

## AI Assistant (SafeLine chatbot)
The chat widget (💬 button, bottom right) is powered by a real LLM
(Google Gemini) so it can answer any phrasing of a question about the
site, not just a fixed keyword list. Gemini was chosen because Google AI
Studio gives every developer a **free API key with no credit card required**.

**Setup:**
1. Get a free key at https://aistudio.google.com/apikey (sign in with any
   Google account — no card needed).
2. Open the `.env` file in the project root and paste your key:
   ```
   GEMINI_API_KEY=AIza...
   ```
   (No shell `export` needed — `config.php` loads `.env` automatically.
   If you don't see a `.env` file, copy `.env.example` to `.env` first.)
   If the key isn't set, the widget shows a friendly "not configured"
   error instead of crashing. `.env` is already in `.gitignore` so your
   key won't get committed if you push this to git.

- `api/chatbot.php` — sends the conversation to the Gemini API server-side
  and returns the reply (using the `gemini-3.6-flash` model, which is on
  Gemini's free tier).
- `api/chatbot_reset.php` — clears the chat history kept in `$_SESSION`
  when the language dropdown changes.
- Conversation history lives only in the PHP session — it is never written
  to the `reports` table or any other DB table, and disappears when the
  session ends, matching the "identity never stored" promise elsewhere on
  the site.
- The language dropdown (English / தமிழ் / Tanglish) is passed to the LLM
  on every request so replies come back in the selected language.
- The assistant is scoped by its system prompt to only answer questions
  about SafeLine — it won't help with unrelated topics.
- Free tiers can be rate-limited or change over time — check current
  limits at https://ai.google.dev/gemini-api/docs/rate-limits before
  relying on this for real traffic.

## Notes
- Passwords and security answers are hashed with `password_hash()` / verified with `password_verify()` — never stored in plain text.
- All SQL uses PDO prepared statements (no string-concatenated queries).
- This is a working prototype: there's no rate-limiting, email verification, or HTTPS enforcement. Add those before using it for a real deployment.

## Photo evidence (optional)
Students can attach one photo when submitting a report — either from their gallery or straight from the camera on mobile (the file input uses `capture="environment"`). It's fully optional; the form submits fine without one.

- Accepted types: JPG, PNG, WEBP, GIF — max 5MB, validated server-side by actual file content (not just the extension).
- Files are saved to the `uploads/` folder with a random filename and linked in `reports.image_path`.
- The `uploads/.htaccess` blocks script execution in that folder so an uploaded file can never run as PHP.
- **If you already created the database before this feature was added**, run `sql/alter_add_image.sql` to add the missing column — a fresh import of `sql/schema.sql` already includes it.
- Make sure the `uploads/` folder is writable by the web server (on shared hosting like InfinityFree this is usually automatic; on your own Linux server you may need `chmod 755 uploads`).
