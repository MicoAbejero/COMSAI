-- Label each knowledge entry with its category-specific section in the COMSAI dataset.
START TRANSACTION;

UPDATE knowledge_base AS k
JOIN categories AS c ON c.id = k.category_id
SET k.source_title = CONCAT('COMSAI Dataset - ', c.name);

COMMIT;
