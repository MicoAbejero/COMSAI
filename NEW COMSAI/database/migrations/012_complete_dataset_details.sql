-- Restore the full detail from COMSAI_dataset-UPDATED.pdf and add its omitted draft items.
START TRANSACTION;

UPDATE knowledge_base
SET content = CASE title
    WHEN 'Who are the CCS faculty members?' THEN 'The current CCS faculty and staff listed in the COMSAI dataset are:\n\n1. Frank Kiven B. Ablao, LPT, MIS\nPosition: OIC Dean, CCS and MC Technical Head.\nQualifications: Civil Service Professional; Trainer Methodology Level 1; Computer Programming NC IV; Visual Graphics Design NC III; Automotive Service (Engine Repair) NC II; Electronic Products Assembly and Servicing NC II.\n\n2. Christian Drigon F. Aga, LPT, JD\nPosition: CCS Administrator, MIT (Unit Earner).\nEducation: Bachelor of Arts in Economics, Polytechnic University of the Philippines; Certificate in Computer Education, De La Salle University; Certificate in Teaching, Philippine Normal University - Manila.\n\n3. Edcel S. Martinez\nPosition: Head, Computer Internet Lab.\nEducation and qualifications: Bachelor of Science in Computer Science; Professional Education; 2D Animation NC III; Computer Systems Servicing NC II; Visual Graphics Design NC III.\n\n4. Ian Godwin G. Agapito\nPosition: CCS Instructor.\nEducation and qualifications: Professional Education; Bachelor of Science in Computer Science.\n\n5. Aeron Dave C. Enova, LPT, CRS, CAWS\nPositions: CCS Central Council Adviser, Practicum Coordinator, and Research Coordinator.\nEducation and qualifications: Master of Science in Computer Science (ongoing); Master of Science in Information Technology (27 units); Juris Doctor (64 units); Certified Academic Writing Specialist; Certified Research Specialist; Civil Service Professional; Data Analyst.\n\n6. Kevin-Ezer F. Tanierla\nPosition: CCS Instructor and Software Engineer.\nEducation and qualifications: Bachelor of Science in Computer Science; Programming NC IV; Animation NC III.\nMC Portal projects: Digital ID, Digital Permit, Attendance Monitoring System, and Digital Tickets.\n\n7. John Eddie Ablao\nPosition: Software Engineer.\nEducation: Bachelor of Science in Information Technology.\n\n8. John Virgel Lo\nPosition: System Administrator.\nEducation: Bachelor of Science in Computer Science.\n\n9. Ferdinand O. Postre\nPosition: Technical Support Assistant.\nEducation and qualifications: Bachelor of Science in Computer Science; Computer Systems Servicing NC II.\n\n10. Annabel Dichoso Nagera, LPT\nPosition: Part-Time Instructor; DepEd Teacher III.\nEducation and qualifications: Bachelor of Science in Computer Science; Master in Information Technology (Unit Earner); Professional Education; Master in Education (Unit Earner).'
    WHEN 'How do I comply with an INC grade?' THEN 'The verified CCS/Mabini Colleges procedure for completing an INC grade is:\n\n1. Get the INC Form from the Registrar''s Office.\n2. Ask your professor for the remaining requirements needed to complete your INC grade.\n3. Complete all missing requirements given by your professor.\n4. Have your professor sign the INC Form and submit the required grade or completion details.\n5. Return the INC Form to the Registrar''s Office for processing and updating your grade.'
    WHEN 'How do I process a COG (Certificate of Grades)?' THEN 'To process a Certificate of Grades (COG):\n\n1. Request Form: Get the official request slip from the Registrar''s window. Fill it out with your full name, student ID number, academic year, semester, and purpose.\n2. Initial Processing: Submit the completed form to the Registrar''s window for verification and printing of your COG.\n3. Payment: Pay the required COG processing fee at the Cashier''s Office.\n4. Validation and Stamping: Return to the Registrar''s Office with the printed COG and payment receipt. Staff will verify the document and apply the official school seal.\n5. Collection: Follow the Registrar staff''s instructions about the collection schedule and release of your validated COG.'
    WHEN 'Where can I ask about my grades?' THEN 'You can view your official grades in the student portal. For concerns, questions, or clarification about a specific subject grade, use either of these options:\n\nInstitutional email: Contact the subject instructor using the official email address in the student portal directory.\n\nIn-person inquiry: Visit your subject instructor during scheduled office hours to discuss the grade.'
    WHEN 'How do I settle my portal balance?' THEN 'If you need help finding where to settle an outstanding balance shown in the student portal, approach any CCS Central Council officer or the CCS Central Council Adviser for guidance. For account-specific balances or payment issues, contact the authorized school office. Do not share passwords or other sensitive payment details in COMSAI.'
    WHEN 'Are there any upcoming CCS activities?' THEN 'The dataset does not list verified upcoming CCS activity dates. Check the official CCS announcement channel for updates: https://www.facebook.com/share/196SduubPS/'
    WHEN 'Where can I view the full details of an announcement?' THEN 'View the full announcement details through the official Facebook page of the College of Computer Studies. COMSAI should provide the source link whenever one is available: https://www.facebook.com/share/196SduubPS/'
    WHEN 'What is the clearance for the end of the semester?' THEN 'Semester clearance is a process completed at the end of each semester. It confirms that a student has no pending obligations or accountabilities with school offices and departments.'
    WHEN 'When is the clearance processed?' THEN 'Semester clearance is processed at the end of each semester, before the semester officially closes.'
    WHEN 'How many times is clearance done in one academic year?' THEN 'Clearance is done twice in one academic year: once for the First Semester and once for the Second Semester.'
    WHEN 'What are the requirements for the semester clearance?' THEN 'The dataset lists these semester-clearance requirements: no pending payments, such as the departmental fee, and no pending obligations for activities. Confirm any additional or updated requirements with the office responsible for clearance.'
    WHEN 'Where can I get the clearance form?' THEN 'The semester clearance form is available from the Property Custodian. One form is issued for each student.'
    WHEN 'Where can I find the CCS class schedule?' THEN 'The CCS class schedule is available through the school''s official student portal. After logging in, students can view their complete course schedule, including assigned subjects, class dates and times, room assignments, and the corresponding subject instructors.'
    WHEN 'What if COMSAI does not know the answer?' THEN 'If an answer is not available in the verified knowledge base, COMSAI should say it cannot verify the information and direct the student to the appropriate CCS Council officer, faculty member, or office. COMSAI should not invent an answer.'
    ELSE content
END,
search_terms = CASE title
    WHEN 'Who are the CCS faculty members?' THEN 'CCS faculty, faculty members, faculty list, organizational chart, faculty and staff directory, instructors, teachers, CCS personnel, who teaches, faculty qualifications, degrees, certificates, roles, Frank Kiven Ablao, Christian Drigon Aga, Edcel Martinez, Ian Godwin Agapito, Aeron Dave Enova, Kevin-Ezer Tanierla, John Eddie Ablao, John Virgel Lo, Ferdinand Postre, Annabel Nagera'
    WHEN 'What if COMSAI does not know the answer?' THEN 'unknown answer, no answer found, missing knowledge, unsupported CCS question, COMSAI cannot verify, ask CCS personnel, do not invent, official CCS channel'
    ELSE search_terms
END,
source_url = CASE title
    WHEN 'Are there any upcoming CCS activities?' THEN 'https://www.facebook.com/share/196SduubPS/'
    WHEN 'Where can I view the full details of an announcement?' THEN 'https://www.facebook.com/share/196SduubPS/'
    ELSE source_url
END,
status = CASE
    WHEN title IN ('How can I contact a CCS faculty member?', 'Where is the Main Laboratory?', 'Where is the Annex Laboratory?', 'Where is the Key Laboratory?', 'Are there any upcoming CCS activities?', 'Where can I view the full details of an announcement?', 'I don''t understand the answer. Can you explain it more simply?', 'Can I ask a question in my own words?', 'Where can I report incorrect information in COMSAI?', 'Can COMSAI confirm my personal academic or payment records?') THEN 'draft'
    ELSE status
END
WHERE title IN (
    'Who are the CCS faculty members?', 'How do I comply with an INC grade?',
    'How do I process a COG (Certificate of Grades)?', 'Where can I ask about my grades?',
    'How do I settle my portal balance?', 'Are there any upcoming CCS activities?',
    'Where can I view the full details of an announcement?', 'What is the clearance for the end of the semester?',
    'When is the clearance processed?', 'How many times is clearance done in one academic year?',
    'What are the requirements for the semester clearance?', 'Where can I get the clearance form?',
    'Where can I find the CCS class schedule?', 'What if COMSAI does not know the answer?',
    'How can I contact a CCS faculty member?', 'Where is the Main Laboratory?',
    'Where is the Annex Laboratory?', 'Where is the Key Laboratory?',
    'I don''t understand the answer. Can you explain it more simply?',
    'Can I ask a question in my own words?', 'Where can I report incorrect information in COMSAI?',
    'Can COMSAI confirm my personal academic or payment records?'
);

-- Add all unresolved dataset entries as drafts so their full questions and TODOs are retained for staff.
INSERT INTO knowledge_base (category_id, title, content, search_terms, source_title, status)
SELECT c.id, d.title, d.content, d.search_terms, CONCAT('COMSAI Dataset - ', c.name), 'draft'
FROM categories c
CROSS JOIN (
    SELECT 'What are the requirements for [specific CCS process]?' AS title,
           'The requirements for [process] are [insert verified checklist]. Please confirm the latest requirements with the office or personnel responsible for the process.' AS content,
           'CCS process requirements, requirements checklist, admission requirements, enrollment requirements, clearance requirements, process checklist, documents needed, required documents, verified requirements' AS search_terms,
           'Academic Requirements' AS category_name
    UNION ALL SELECT 'Where can I get the form for [specific process]?',
           'The form for [process] is available at [insert verified office, official link, or platform]. If no form is listed, contact the office responsible for the process.',
           'CCS process form, application form, request form, where to get form, official form link, office form, download form, submit form',
           'Academic Requirements'
    UNION ALL SELECT 'When is the student portal available or scheduled?',
           'The current student portal schedule is [insert verified days, times, and purpose]. If the schedule is not listed or has changed, contact the office responsible for the student portal.',
           'student portal schedule, portal availability, portal hours, portal downtime, portal maintenance, when can I log in, schedule of portal access',
           'Academic Procedures'
    UNION ALL SELECT 'What are the CCS rules for using the laboratories?',
           'The approved laboratory rules are [insert verified rules]. Follow posted laboratory instructions and directions from authorized faculty or laboratory personnel.',
           'laboratory rules, computer lab rules, lab policy, lab safety, laboratory use, computer room rules, equipment rules, lab conduct',
           'Department Policies'
    UNION ALL SELECT 'What should I do if I cannot find the information I need?',
           'I could not find a verified answer in the CCS knowledge base. Please contact the CCS Council through [insert official contact channel] or ask the appropriate CCS faculty member or office.',
           'cannot find information, missing answer, unknown CCS question, contact CCS, official contact channel, ask faculty, ask office, help finding information',
           'Department Policies'
) AS d
WHERE c.name = d.category_name
  AND NOT EXISTS (SELECT 1 FROM knowledge_base k WHERE k.title = d.title);

COMMIT;
