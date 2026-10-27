CREATE DATABASE IF NOT EXISTS new_comsai_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE new_comsai_db;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor') NOT NULL DEFAULT 'admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uq_category_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS knowledge_base (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    content MEDIUMTEXT NOT NULL,
    source_title VARCHAR(200) NULL,
    source_url VARCHAR(2048) NULL,
    effective_date DATE NULL,
    expiration_date DATE NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id), KEY idx_kb_status_dates (status, effective_date, expiration_date),
    FULLTEXT KEY ft_kb_content (title, content),
    CONSTRAINT fk_kb_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_kb_admin FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content MEDIUMTEXT NOT NULL,
    source_url VARCHAR(2048) NULL,
    publish_at DATETIME NULL,
    expiration_date DATETIME NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id), KEY idx_announcement_current (status, publish_at, expiration_date),
    CONSTRAINT fk_announcement_admin FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_token_hash CHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uq_chat_token_hash (session_token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    chat_session_id BIGINT UNSIGNED NOT NULL,
    sender ENUM('student', 'assistant') NOT NULL,
    message MEDIUMTEXT NOT NULL,
    source_references JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), KEY idx_chat_session_time (chat_session_id, created_at),
    CONSTRAINT fk_chat_message_session FOREIGN KEY (chat_session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), KEY idx_activity_time (created_at),
    CONSTRAINT fk_activity_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    key_hash CHAR(64) NOT NULL,
    window_started_at DATETIME NOT NULL,
    request_count INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (key_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categories (name, description) VALUES
('Faculty and Staff', 'Verified faculty and department personnel information.'),
('Dean and Department Personnel', 'Verified dean and department personnel information.'),
('CCS Council Officers', 'Verified College of Computer Studies council information.'),
('Student Organizations', 'Verified College of Computer Studies student organizations.'),
('Rooms and Laboratories', 'Verified facilities and room information.'),
('Announcements and Activities', 'Current department notices and activities.'),
('Academic Requirements', 'Verified academic requirements.'),
('Schedules and Important Dates', 'Verified schedules and dates.'),
('Academic Procedures', 'Documented academic procedures.'),
('Department Policies', 'Approved College of Computer Studies department policies.'),
('Contact Information', 'Verified department contacts.'),
('Frequently Asked Questions', 'Approved common questions.');

INSERT INTO knowledge_base (category_id, title, content, source_title, status)
SELECT id, 'FICTIONAL TEST ONLY: Example draft', 'This is fictional test content and is not official Mabini Colleges information. Replace with verified CCS information before publishing.', 'Fictional development sample', 'draft'
FROM categories WHERE name = 'Frequently Asked Questions'
AND NOT EXISTS (SELECT 1 FROM knowledge_base WHERE title = 'FICTIONAL TEST ONLY: Example draft');

INSERT INTO knowledge_base (category_id, title, content, source_title, status)
SELECT id,
       'CCS Central Council Officers',
       'Governor: Selene E. Tychingco\nVice Governor - Internal: Angel Ayessa O. Cacho\nVice Governor - External: Giedhell Ann M. Serrano\nExecutive Secretary: Abiegail T. Rellermo\nAssistant Secretary: Lorren Joyce L. Erandio\nChief Treasurer: Renalyn T. Ubalde\nDeputy Treasurer: Anika Kaye E. Arjite\nAuditor: Renz Darrel O. Gomez\nChief Peace Officer: Justine D. Canaria\nDeputy Peace Officer: Jose Russel M. Cabunias\nDeputy Peace Officer: John Kevin B. Abogado\nChief Communication and Media Officer: John Bernard C. De Leon\nDeputy Communication and Media Officer: Francis Kevin Y. Calvario\nCommunication and Media Officer: Shalom Emannuel M. Abiera\nCommunication and Media Officer: Richard A. Boribor\nCommunication and Media Officer: Condrad Jaimar G. Sale\nCommunication and Media Officer: Candice Fine Y. Montemayor\nFourth Year Representative: Eya Mae R. Bana\nThird Year Representative: Gian Carlo D. Bacia\nSecond Year Representative: Chelsey Joelle B. Esguerra\nFirst Year Representative: John Mark N. Molina\nFirst Year Representative: Mark Zedrix Zantua\nCCS Central Council Adviser: Aeron Dave C. Enova',
       'User-provided CCS council officer roster',
       'draft'
FROM categories
WHERE name = 'CCS Council Officers'
AND NOT EXISTS (SELECT 1 FROM knowledge_base WHERE title = 'CCS Central Council Officers');
