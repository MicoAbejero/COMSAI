# NEW COMSAI

A simple, database-backed student information assistant for the College of Computer Studies. The application is PHP 8.2+, MySQL/MariaDB, PDO, HTML, CSS, and JavaScript; it has no PHP framework dependency. It is separate from the existing `comsai` project and uses the database `new_comsai_db`.

## Requirements

- XAMPP with Apache and MySQL/MariaDB running
- PHP 8.2+ with `pdo_mysql` and `mbstring`; `curl` and `openssl` are needed only for OpenAI
- phpMyAdmin (included with XAMPP)

## Install locally

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open `http://localhost/phpmyadmin/`, choose **Import**, select `C:\xampp\htdocs\NEW COMSAI\database\new_comsai_db.sql`, and import. This creates a separate `new_comsai_db` and does not touch the old project's database.
3. Copy `.env.example` to `.env` in this folder and update `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` for your MySQL setup. The included defaults match a common local XAMPP installation. Do not commit `.env`.
4. Optional AI: set `OPENAI_API_KEY` in `.env`. Keep the key server-side. Without a key, COMSAI returns clearly labeled knowledge-base results. If the API request fails, it also falls back to the matching source text. OpenAI is called only when current published sources match.
5. Create the first administrator from PowerShell:

   `C:\xampp\php\php.exe "C:\xampp\htdocs\NEW COMSAI\bin\create_admin.php"`

   Enter a name and email. The script generates a random one-time password and stores only its `password_hash`. Save the password securely, sign in, and change it under **Administrator account**. The script refuses to create more accounts after the first administrator; there is no public registration.
6. Check PHP/PDO connectivity:

   `C:\xampp\php\php.exe "C:\xampp\htdocs\NEW COMSAI\bin\test_db.php"`

7. Run the database-backed tests:

   `C:\xampp\php\php.exe "C:\xampp\htdocs\NEW COMSAI\tests\run.php"`

   Fixtures run inside a transaction and are rolled back.

## Local URLs

- Student chat: `http://localhost/NEW%20COMSAI/`
- Administrator login: `http://localhost/NEW%20COMSAI/login.php`
- Administrator dashboard: `http://localhost/NEW%20COMSAI/admin/index.php`
- phpMyAdmin: `http://localhost/phpmyadmin/`

Apache must allow `.htaccess` overrides for the private-path and header rules. The included directory name contains a space, so `%20` appears in browser URLs.

## Use and privacy

Students can use the chatbot without accounts. Questions and answers are stored under a random anonymous session token whose hash is stored in the database; messages are not tied to student profiles. The administrator's activity page shows recent question text and aggregate counts for legitimate service monitoring. Do not submit personal or sensitive information.

Chat sessions inactive for 30 days are eligible for deletion. To clean expired sessions, old rate-limit rows, and audit rows older than one year, run this CLI-only command daily via Windows Task Scheduler:

`C:\xampp\php\php.exe "C:\xampp\htdocs\NEW COMSAI\bin\purge_expired_data.php"`

This project contains one explicitly fictional, unpublished sample entry. Replace it with information verified by CCS staff; never publish the sample as real. Until authorized CCS content is added, the chatbot correctly reports that it cannot verify an answer.

## Security notes

- PDO uses prepared statements; admin writes use CSRF validation, server-side role checks, and escaped output.
- Admin passwords use `password_hash()` / `password_verify()`. Login rotates the PHP session ID and is rate-limited.
- `.env`, SQL exports, configuration, test scripts, and setup scripts are denied through Apache rules. Ensure `AllowOverride All` is enabled and never expose this project on an unreviewed public server.
- OpenAI questions and matching approved excerpts leave the local server when AI is enabled. Obtain institutional privacy approval before enabling that integration for real students.
- This is a local academic demonstration foundation, not a security certification. Use verified content and review hosting, retention, consent, and backups before production.

## Existing database update

For a database created from an earlier copy of this new project schema (before chat citations were added), import `database/migrations/001_chat_message_sources.sql` once in phpMyAdmin. Do not run that one-time migration after importing the current full schema because the column already exists.

## Remaining prerequisites

Real CCS knowledge sources, school-approved operational policies, optional OpenAI credentials, and institutional approval for student-data handling are not bundled or invented.
