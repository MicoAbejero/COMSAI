CREATE TABLE IF NOT EXISTS daily_chat_limits (
    usage_date DATE NOT NULL,
    visitor_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (usage_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS daily_chat_visitors (
    usage_date DATE NOT NULL,
    visitor_hash CHAR(64) NOT NULL,
    PRIMARY KEY (usage_date, visitor_hash),
    KEY idx_daily_chat_visitors_date (usage_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
