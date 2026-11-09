UPDATE announcements
SET content = CONCAT(
    'The departmental fee is 1,400 pesos.',
    SUBSTRING(content, LENGTH('The latest verified CCS announcement is the payment for the departmental fee amounting to 1,400 pesos.') + 1)
)
WHERE title = 'Departmental Fee Payment – 1,400 pesos'
  AND content LIKE 'The latest verified CCS announcement is the payment for the departmental fee amounting to 1,400 pesos.%';
