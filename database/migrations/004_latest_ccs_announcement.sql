INSERT INTO announcements (title, content, source_url, publish_at, status)
SELECT
    'Departmental Fee Payment – 1,400 pesos',
    'The departmental fee is 1,400 pesos.\n\nAuthorized Payment Channels of the CCS Central Council:\n\nGCASH – EXECUTIVE TREASURER\nName: Miss Renalyn Ubalde\nPosition: Executive Treasurer, CCS Central Council\nGCash Number: 0907 919 2632\n\nGCASH – DEPUTY TREASURER\nName: Miss Anika Kaye Arjite\nPosition: Deputy Treasurer, CCS Central Council\nGCash Number: 0967 188 7631\n\nPlease refer to the official CCS Facebook page or the approved CCS communication channel for the original post and further updates.',
    NULL,
    NOW(),
    'published'
WHERE NOT EXISTS (SELECT 1 FROM announcements WHERE title = 'Departmental Fee Payment – 1,400 pesos');
