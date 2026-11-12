-- Clean the knowledge entries against COMSAI_dataset-UPDATED.pdf.
-- Placeholder or explicitly unverified answers stay unpublished.
START TRANSACTION;

UPDATE knowledge_base
SET search_terms = CASE title
    WHEN 'CCS Central Council Officers' THEN 'CCS council officers, central council, student council, student government, officer roster, list of officers, governor, vice governor, secretary, treasurer, auditor, peace officer, communications officer, year representatives, academic year 2026 2027, Selene Tychingco, Angel Cacho, Giedhell Serrano, Abiegail Rellermo, Lorren Erandio, Renalyn Ubalde, Anika Arjite'
    WHEN 'What is COMSAI?' THEN 'COMSAI meaning, COMSAI definition, chatbot, AI student assistant, College of Computer Studies, CCS, Mabini Colleges'
    WHEN 'What information can I ask COMSAI about?' THEN 'COMSAI capabilities, topics, examples, ask COMSAI, information available, chatbot scope'
    WHEN 'Is COMSAI an official source of CCS information?' THEN 'official source, reliable information, verified CCS facts, accuracy, current information, confirm with CCS, urgent matters, official authority'
    WHEN 'Who is the CCS Dean?' THEN 'CCS dean, dean name, OIC dean, department head, technical head, Frank Kiven B Ablao, Sir Frank Ablao, Mabini Colleges'
    WHEN 'Where can I find the CCS Dean?' THEN 'dean office location, where is dean office, CCS office, second floor, beside President office, office location, dean schedule'
    WHEN 'Who are the CCS faculty members?' THEN 'CCS faculty, faculty members, faculty list, instructors, teachers, staff directory, CCS personnel, who teaches, faculty and staff, Frank Ablao, Christian Aga, Edcel Martinez, Ian Agapito, Aeron Enova, Kevin Tanierla, John Eddie Ablao, John Virgel Lo, Ferdinand Postre, Annabel Nagera'
    WHEN 'How can I contact a CCS faculty member?' THEN 'contact faculty, faculty email, instructor contact, contact a teacher, faculty member contact details'
    WHEN 'Who is the current CCS Governor?' THEN 'current CCS governor, council president, student council governor, governor name, Selene E Tychingco, academic year 2026 2027'
    WHEN 'Where is the Main Laboratory?' THEN 'main lab, main laboratory, laboratory location, lab room, MRI Building, second floor, room 207'
    WHEN 'Where is the Annex Laboratory?' THEN 'annex lab, annex laboratory, laboratory location, lab room, MRI Building, second floor, room 203'
    WHEN 'Where is the Key Laboratory?' THEN 'key lab, keylab, key laboratory, laboratory location, lab room, MRI Building, second floor, room 212'
    WHEN 'How do I comply with an INC grade?' THEN 'INC, incomplete grade, INC compliance, complete an INC, INC form, professor, Registrar office, missing requirements, submit completion grade'
    WHEN 'How do I process a COG (Certificate of Grades)?' THEN 'COG, Certificate of Grades, request grades certificate, Registrar request form, COG process, Cashier fee, school seal, collect COG'
    WHEN 'Where can I ask about my grades?' THEN 'grade concern, question about grades, professor contact, subject instructor, grade clarification, student portal grades, instructor email, office hours'
    WHEN 'Where can I find the CCS class schedule?' THEN 'class schedule, CCS schedule, view timetable, subject schedule, student portal, subjects, class dates, times, rooms, instructors'
    WHEN 'Can COMSAI confirm my personal academic or payment records?' THEN 'private records, personal grades, student account, payment balance, portal balance, privacy, password, confidential information, account-specific records'
    WHEN 'I don''t understand the answer. Can you explain it more simply?' THEN 'explain simply, simplify answer, clarify, rephrase, explain again, easier explanation'
    WHEN 'What if COMSAI does not know the answer?' THEN 'unknown answer, no answer found, missing knowledge, unsupported CCS question, COMSAI cannot verify, ask CCS personnel, do not invent'
    WHEN 'Can I ask a question in my own words?' THEN 'ask naturally, own words, different wording, paraphrase question, how to ask, rephrase CCS question'
    WHEN 'Where can I report incorrect information in COMSAI?' THEN 'report error, incorrect answer, wrong information, fix COMSAI, report a mistake, knowledge base correction, contact administrator'
    WHEN 'How do I settle my portal balance?' THEN 'portal balance, student portal payment, outstanding balance, where to pay, cashier, ask council officer, council adviser, account payment help'
    WHEN 'Are there any upcoming CCS activities?' THEN 'upcoming activities, future events, CCS events, event dates, activity schedule, calendar, announcements'
    WHEN 'Where can I view the full details of an announcement?' THEN 'announcement details, read full announcement, official post, Facebook announcement, original source, announcement link'
    WHEN 'Has the schedule of the event changed?' THEN 'event schedule change, rescheduled, postponed, cancelled, delayed, new event date, updated time, announcement update'
    WHEN 'What is the clearance for the end of the semester?' THEN 'semester clearance, end of semester clearance, clearance meaning, school obligations, accountabilities, student clearance'
    WHEN 'When is the clearance processed?' THEN 'clearance schedule, when to process clearance, clearance deadline, end of semester, semester closing'
    WHEN 'How many times is clearance done in one academic year?' THEN 'clearance frequency, how often clearance, twice a year, first semester, second semester, annual clearance'
    WHEN 'What are the requirements for the semester clearance?' THEN 'clearance requirements, clearance checklist, pending payments, departmental fee, activity obligations, semester clearance'
    WHEN 'Where can I get the clearance form?' THEN 'clearance form, obtain clearance form, Property Custodian, one form per student, clearance paperwork'
    WHEN 'What is the latest announcement?' THEN 'latest announcement, newest CCS notice, current announcement, recent notice, latest activity, department updates'
    ELSE search_terms
END,
content = CASE title
    WHEN 'Where can I find the CCS Dean?' THEN 'The CCS Dean office is on the second floor, beside the President''s office. Ask the CCS office or CCS Council to confirm the Dean''s schedule.'
    WHEN 'Who are the CCS faculty members?' THEN 'The COMSAI dataset lists these CCS faculty and staff members:\n\nFrank Kiven B. Ablao - OIC Dean, CCS and MC Technical Head\nChristian Drigon F. Aga - CCS Administrator\nEdcel S. Martinez - Head, Computer Internet Lab\nIan Godwin G. Agapito - CCS Instructor\nAeron Dave C. Enova - CCS Central Council Adviser, Practicum Coordinator, and Research Coordinator\nKevin-Ezer F. Tanierla - CCS Instructor and Software Engineer\nJohn Eddie Ablao - Software Engineer\nJohn Virgel Lo - System Administrator\nFerdinand O. Postre - Technical Support Assistant\nAnnabel Dichoso Nagera - Part-Time Instructor'
    WHEN 'How do I comply with an INC grade?' THEN 'To complete an INC grade:\n1. Get the INC form from the Registrar''s Office.\n2. Ask your professor which remaining requirements you must complete.\n3. Complete the missing requirements.\n4. Have your professor sign the form and submit the required completion details.\n5. Return the form to the Registrar''s Office for processing and grade updating.'
    WHEN 'How do I process a COG (Certificate of Grades)?' THEN 'To process a Certificate of Grades (COG):\n1. Get and complete the official request form at the Registrar''s window. Include your name, student ID, academic year, semester, and purpose.\n2. Submit the form to the Registrar for verification and printing.\n3. Pay the processing fee at the Cashier''s Office.\n4. Return to the Registrar with the printed COG and receipt for verification and the official seal.\n5. Follow the Registrar staff''s instructions for collection.'
    WHEN 'Where can I ask about my grades?' THEN 'You can view official grades in the student portal. For a question about a specific grade, contact the subject professor through the institutional email in the portal directory or visit during scheduled office hours.'
    WHEN 'Has the schedule of the event changed?' THEN 'The dataset does not list a verified event schedule change. Check the linked official announcement or contact the event organizer to confirm last-minute changes.'
    WHEN 'What are the requirements for the semester clearance?' THEN 'The dataset lists no pending departmental-fee payments and no pending activity obligations as clearance requirements. Confirm any additional or updated requirements with the office responsible for clearance.'
    WHEN 'Where can I get the clearance form?' THEN 'The semester clearance form is available from the Property Custodian. One form is issued per student.'
    WHEN 'What is the latest announcement?' THEN 'The latest-announcement question is answered dynamically from the newest currently published, non-expired announcement. Check the Announcements and Activities panel for its details.'
    WHEN 'How do I settle my portal balance?' THEN 'For help finding where to settle an outstanding student-portal balance, approach a CCS Central Council officer or the CCS Central Council Adviser. For account-specific balances, contact the authorized school office. Do not share passwords or private payment details in COMSAI.'
    ELSE content
END,
status = CASE
    WHEN title IN ('How can I contact a CCS faculty member?', 'Where is the Main Laboratory?', 'Where is the Annex Laboratory?', 'Where is the Key Laboratory?', 'I don''t understand the answer. Can you explain it more simply?', 'Can I ask a question in my own words?', 'Where can I report incorrect information in COMSAI?') THEN 'draft'
    ELSE status
END,
source_title = CASE WHEN id BETWEEN 28 AND 58 THEN 'COMSAI dataset (updated 2026–2027)' ELSE source_title END
WHERE id BETWEEN 28 AND 58;

-- Add usable Q&A entries from the dataset that were missing from the database.
INSERT INTO knowledge_base (category_id, title, content, search_terms, source_title, status)
SELECT c.id, d.title, d.content, d.search_terms, 'COMSAI dataset (updated 2026–2027)', d.status
FROM categories c
CROSS JOIN (
    SELECT 'Who should I contact about department fee payments?' AS title,
           'For department-fee payment questions, contact the CCS Central Council Treasurers through their Facebook accounts:\n\nChief Treasurer Renalyn T. Ubalde: https://facebook.com/ena.ubalde\nDeputy Treasurer Anika Kaye E. Arjite: https://facebook.com/anika.kaye.19' AS content,
           'department fee, departmental fee, fee payment contact, treasurer contact, CCS council treasurer, payment question, Renalyn Ubalde, Anika Arjite, Facebook' AS search_terms,
           'published' AS status
    UNION ALL SELECT 'How can I pay the CCS department fee?',
           'The dataset lists the CCS department fee as 1,400 pesos. It lists these authorized CCS Central Council GCash channels:\n\nExecutive Treasurer Renalyn Ubalde: 0907 919 2632\nDeputy Treasurer Anika Kaye Arjite: 0967 188 7631\n\nConfirm the amount and payment details through the official CCS announcement channel before sending money.',
           'pay department fee, pay CCS fee, departmental dues, GCash payment, fee amount, 1400 pesos, payment channel, Executive Treasurer, Deputy Treasurer, Renalyn Ubalde, Anika Arjite',
           'published'
    UNION ALL SELECT 'Who can I contact about department shirt concerns?',
           'For department-shirt updates, contact the CCS Central Council Secretaries:\n\nAssistant Secretary Lorren Joyce L. Erandio: https://facebook.com/eljeixcm\nExecutive Secretary Abiegail T. Rellermo: https://facebook.com/aviezx',
           'department shirt, shirt update, shirt order, shirt concern, shirt inquiry, secretary contact, Assistant Secretary, Executive Secretary, Lorren Erandio, Abiegail Rellermo',
           'published'
    UNION ALL SELECT 'How can I check if my name is on the department shirt list?',
           'Contact CCS Central Council Secretaries Lorren Joyce L. Erandio or Abiegail T. Rellermo to check whether your name is on the department-shirt list. Their Facebook accounts are https://facebook.com/eljeixcm and https://facebook.com/aviezx.',
           'department shirt list, check my name, shirt order list, verify shirt order, secretary contact, Lorren Erandio, Abiegail Rellermo',
           'published'
    UNION ALL SELECT 'Who should I contact about an INC grade?',
           'For requirements or procedures for completing an INC grade, contact the professor handling that subject. See the INC-completion steps in the Academic Procedures information.',
           'INC grade, incomplete grade, contact professor, subject instructor, INC requirements, INC procedure, complete INC',
           'published'
    UNION ALL SELECT 'Who can help with a CCS concern that is not listed?',
           'If your CCS concern is not covered in the available information, approach a CCS Central Council officer or contact the CCS Central Council Adviser, Sir Aeron Dave C. Enova.',
           'other CCS concern, unlisted question, who can help, council officer, Central Council Adviser, Aeron Dave Enova, ask CCS',
           'published'
    UNION ALL SELECT 'What does A or B mean in my class schedule?',
           'In a schedule ending in A, the first meeting is face-to-face and the second is online. In a schedule ending in B, the first meeting is online and the second is face-to-face. A schedule without A or B means all meetings are face-to-face. The same A/B arrangement applies to subjects that meet once a week.',
           'schedule A, schedule B, A and B meaning, class mode, face-to-face, online class, hybrid schedule, once a week subject, class timetable',
           'published'
) AS d
WHERE c.name = CASE
    WHEN d.title LIKE 'Who should I contact about department fee%' OR d.title LIKE 'How can I pay the CCS department%' OR d.title LIKE 'Who can I contact about department shirt%' OR d.title LIKE 'How can I check if my name is on the department shirt%' OR d.title LIKE 'Who can help with a CCS concern%' THEN 'CCS Council Officers'
    WHEN d.title LIKE 'Who should I contact about an INC%' THEN 'Academic Procedures'
    ELSE 'Schedules and Important Dates'
END
AND NOT EXISTS (SELECT 1 FROM knowledge_base k WHERE k.title = d.title);

-- Make the published fee notice easy to retrieve with natural student wording.
UPDATE announcements
SET title = 'CCS Department Fee Payment Options - 1,400 pesos',
    content = 'The CCS department fee listed in this announcement is 1,400 pesos. To pay the CCS department fee, use one of the authorized CCS Central Council GCash channels below:\n\nExecutive Treasurer Renalyn Ubalde: 0907 919 2632\nDeputy Treasurer Anika Kaye Arjite: 0967 188 7631\n\nPlease confirm the amount and payment details through the official CCS Facebook page or approved CCS communication channel before sending money.'
WHERE id = 6;

COMMIT;
