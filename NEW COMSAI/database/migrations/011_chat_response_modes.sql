-- Preserve the type of each assistant answer so chat history can label it clearly.
ALTER TABLE chat_messages
    ADD COLUMN response_mode VARCHAR(24) NULL AFTER message;

UPDATE chat_messages
SET response_mode = CASE
    WHEN sender = 'student' THEN NULL
    WHEN message LIKE 'General AI response (not based on CCS knowledge-base sources)%' THEN 'ai-general'
    WHEN source_references IS NOT NULL AND JSON_LENGTH(source_references) > 0 THEN 'legacy-sourced'
    ELSE 'unverified'
END
WHERE response_mode IS NULL;

UPDATE chat_messages
SET message = TRIM(SUBSTRING(message, CHAR_LENGTH('General AI response (not based on CCS knowledge-base sources):') + 1))
WHERE sender = 'assistant'
  AND message LIKE 'General AI response (not based on CCS knowledge-base sources):%';
