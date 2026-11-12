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
4. Optional AI: set `OPENAI_API_KEY` in `.env`. Keep the key server-side. With AI enabled, COMSAI sends matched questions and approved excerpts to OpenAI for source-grounded answers. When no source matches, it can respond naturally to greetings and general, educational, personal, or conversational questions; it is instructed to decline unverified CCS-specific facts. Without a key or if the API request fails, COMSAI uses its knowledge-base fallback or asks students to check official CCS channels.
5. Create the first administrator from PowerShell:

   `C:\xampp\php\php.exe "C:\xampp\htdocs\NEW COMSAI\bin\create_admin.php"`

   Enter a name and email. The script generates a random one-time password and stores only its `password_hash`. Save the password securely, sign in, and change it under **Administrator account**. The script refuses to create more accounts after the first administrator; there is no public registration.
6. Check PHP/PDO connectivity:

   `C:\xampp\php\php.exe "C:\xampp\htdocs\NEW COMSAI\bin\test_db.php"`

For an existing installation, apply `database/migrations/003_search_terms.sql` once in phpMyAdmin before using the updated knowledge-base editor. Admins can then add synonyms and example questions to each entry; COMSAI ranks published sources by title, those terms, and content matches.

For an existing installation, also apply `database/migrations/006_knowledge_datetime.sql` once to give knowledge-base publication and expiration fields the same date-and-time precision as announcements. New databases created from the current schema already use these fields.

Apply `database/migrations/007_latest_announcement_knowledge.sql` once on an existing installation to add a published latest-announcement entry to **Announcements and Activities**. Latest-announcement questions are routed to the newest currently published, non-expired item in **Announcements**, sorted by publication date and time.

Apply `database/migrations/008_latest_announcement_question.sql` once on an existing installation to save the exact question **What is the latest announcement?** as the knowledge-base entry title and search term. The chat still retrieves the answer dynamically from the latest eligible announcement.

Apply `database/migrations/009_dataset_knowledge_cleanup.sql` once after loading the updated COMSAI dataset. It refreshes search terms and wording, adds missing student Q&A entries, and leaves items marked as placeholders in draft status.

Apply `database/migrations/010_category_source_titles.sql` once to label each knowledge entry with its category-specific section from the COMSAI dataset.

For databases created before `response_mode` was added to the schema, apply `database/migrations/011_chat_response_modes.sql` once to preserve answer labels in chat history (general AI, source-grounded AI, knowledge base, and announcements). The student panel formats answers as readable paragraphs and lists, links URLs safely, and labels general replies separately from CCS-sourced answers.

Apply `database/migrations/012_complete_dataset_details.sql` once to restore the full profile and procedure details from `COMSAI_dataset-UPDATED.pdf` and add its remaining placeholder questions as drafts.

Apply `database/migrations/013_precise_knowledge_search_terms.sql` once to replace broad or weak aliases with focused student wording across the existing knowledge entries. Draft entries receive keywords too, ready for review if they are later published.

Clicking a student-panel topic lists all currently published entries in that category. The Announcements and Activities topic reads from the announcements panel and excludes drafts, scheduled, or expired announcements.

Apply `database/migrations/004_latest_ccs_announcement.sql` once to add the provided departmental-fee notice to the **Announcements** panel on an existing installation. It is published immediately and is inserted only if its title is not already present. For databases that already applied migration 004, apply `database/migrations/005_neutral_fee_announcement.sql` once to remove the “latest” wording from the fee notice. Questions asking for the latest announcement are answered from the newest currently published, non-expired announcement, ordered by publication time. To keep that answer current, publish new notices in **Admin → Announcements**; the announcement panel, not the knowledge base, is the source of truth for latest-announcement questions.

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
- Student chat is limited to 30 distinct browser sessions per calendar day in Asia/Taipei; each admitted browser can continue chatting that day. Since students do not log in, this is a browser-session limit rather than a verified person count. The existing 30-questions-per-IP-per-minute limit also remains active.
- Changing an administrator password requires the account email, current password, and a matching new password of at least 12 characters.
- `.env`, SQL exports, configuration, test scripts, and setup scripts are denied through Apache rules. Ensure `AllowOverride All` is enabled and never expose this project on an unreviewed public server.
- Student questions leave the local server for OpenAI when AI is enabled, including unmatched general questions; matching approved excerpts are also sent for source-grounded responses. Obtain institutional privacy approval before enabling that integration for real students.
- This is a local academic demonstration foundation, not a security certification. Use verified content and review hosting, retention, consent, and backups before production.

## Existing database update

For a database created from an earlier copy of this new project schema (before chat citations were added), import `database/migrations/001_chat_message_sources.sql` once in phpMyAdmin. Do not run that one-time migration after importing the current full schema because the column already exists.

To add the user-provided CCS Central Council officer roster to an existing database, import `database/migrations/002_ccs_council_officers.sql` once in phpMyAdmin. A fresh database created from the current full schema already includes it. The roster is saved as a draft in **CCS Council Officers** for administrator review before publication.

For an existing installation, import `database/migrations/014_daily_student_limit.sql` once in phpMyAdmin to enable the daily student limit. Fresh databases created from the current full schema already include the required tables.

For an existing installation, import `database/migrations/015_student_interface_toggle.sql` once in phpMyAdmin to enable the administrator control for the student interface. Fresh databases created from the current full schema already include the setting.


## Remaining prerequisites

Real CCS knowledge sources, school-approved operational policies, optional OpenAI credentials, and institutional approval for student-data handling are not bundled or invented.
