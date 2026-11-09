-- Apply only to a database created before source_references was added to chat_messages.
USE new_comsai_db;
ALTER TABLE chat_messages ADD COLUMN source_references JSON NULL AFTER message;
