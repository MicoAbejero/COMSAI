INSERT INTO knowledge_base (category_id, title, content, search_terms, source_title, status)
SELECT id,
       'What is the latest announcement?',
       'The latest currently published announcement is available in the Announcements and Activities panel. Ask “What is the latest announcement?” to retrieve the newest eligible announcement by publication date. Scheduled announcements are shown after their publication time, and expired announcements are excluded.',
       'What is the latest announcement?, latest announcement, newest announcement, current announcement, latest notice, current notice, announcements and activities',
       'COMSAI Announcements and Activities panel',
       'published'
FROM categories
WHERE name = 'Announcements and Activities'
AND NOT EXISTS (SELECT 1 FROM knowledge_base WHERE title = 'What is the latest announcement?');
