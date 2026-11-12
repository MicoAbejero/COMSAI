-- Replace broad or missing aliases with focused wording that reflects each Q&A.
-- Draft rows are included so their terms are ready when the content is verified.
START TRANSACTION;

UPDATE knowledge_base
SET search_terms = CASE title
    WHEN 'How do I comply with an INC grade?' THEN 'INC grade, incomplete grade, remove an INC, complete an incomplete, finish INC requirements, INC form, missing coursework, professor requirements, Registrar submission'
    WHEN 'How do I process a COG (Certificate of Grades)?' THEN 'COG, certificate of grades, request grades certificate, get my COG, COG application, Registrar request slip, pay COG fee, Cashier, school seal, collect grades certificate'
    WHEN 'How do I settle my portal balance?' THEN 'pay student portal balance, settle outstanding balance, where to pay school balance, payment help, cashier location, council officer payment guidance, account balance support'
    WHEN 'When is the student portal available or scheduled?' THEN 'student portal operating hours, portal access schedule, portal availability, portal maintenance, portal downtime, portal login hours, student portal timetable'
    WHEN 'Where can I ask about my grades?' THEN 'check grades, view grades, grade concern, incorrect grade, question about a subject mark, ask my professor, instructor contact, student portal grades, professor office hours'
    WHEN 'Who should I contact about an INC grade?' THEN 'INC grade contact, incomplete grade help, professor handling my subject, ask instructor about INC, who handles INC requirements, incomplete coursework contact'
    WHEN 'How many times is clearance done in one academic year?' THEN 'clearance frequency, how often clearance happens, number of clearances per year, first semester clearance, second semester clearance, twice each academic year'
    WHEN 'What are the requirements for [specific CCS process]?' THEN 'requirements checklist, documents needed, required documents, admission requirements, enrollment requirements, CCS process requirements, verified process checklist'
    WHEN 'What are the requirements for the semester clearance?' THEN 'semester clearance requirements, clearance checklist, clearance obligations, outstanding payments, departmental fee clearance, pending activity obligations, what must be completed for clearance'
    WHEN 'What is the clearance for the end of the semester?' THEN 'meaning of semester clearance, end of semester clearance, what clearance means, student accountabilities, school obligations, semester closing clearance'
    WHEN 'When is the clearance processed?' THEN 'clearance processing period, clearance schedule, clearance deadline, when to complete semester clearance, end of semester processing, semester closing date'
    WHEN 'Where can I get the clearance form?' THEN 'semester clearance form, obtain clearance paperwork, where to request clearance form, Property Custodian, one form per student'
    WHEN 'Where can I get the form for [specific process]?' THEN 'process application form, request form, where to get a form, official form download, office form, submit process paperwork, specific CCS form'
    WHEN 'Are there any upcoming CCS activities?' THEN 'upcoming CCS events, future department activities, event calendar, activity dates, next CCS event, planned activities, scheduled events, upcoming programs'
    WHEN 'Has the schedule of the event changed?' THEN 'event date changed, schedule update, rescheduled event, postponed activity, cancelled event, delayed event, new start time, changed venue, event postponement'
    WHEN 'What is the latest announcement?' THEN 'latest announcement, newest announcement, most recent CCS notice, current department announcement, recent notice, newest activity update'
    WHEN 'Where can I view the full details of an announcement?' THEN 'read complete announcement, announcement full text, official announcement post, announcement source link, original CCS notice, view announcement details'
    WHEN 'CCS Central Council Officers' THEN 'CCS council roster, central council members, student council officers, student government, list all officers, council positions, governor, vice governor, secretary, treasurer, auditor, peace officer, communications and media officer, year representatives, council adviser, academic year 2026 2027, Selene Tychingco, Angel Cacho, Giedhell Serrano, Abiegail Rellermo, Lorren Erandio, Renalyn Ubalde, Anika Arjite, Renz Gomez, Justine Canaria, Jose Cabunias, John Abogado, John De Leon, Francis Calvario, Shalom Abiera, Richard Boribor, Condrad Sale, Candice Montemayor, Eya Bana, Gian Bacia, Chelsey Esguerra, John Molina, Mark Zantua, Aeron Enova'
    WHEN 'How can I check if my name is on the department shirt list?' THEN 'check my name on shirt list, department shirt order list, verify shirt order, shirt sizing list, shirt purchase name, ask council secretaries, shirt order status, Lorren Erandio, Abiegail Rellermo'
    WHEN 'How can I pay the CCS department fee?' THEN 'pay CCS departmental fee, department dues payment, fee amount, 1400 pesos, GCash payment number, authorized payment channel, executive treasurer payment, deputy treasurer payment, Renalyn Ubalde, Anika Arjite'
    WHEN 'Who can help with a CCS concern that is not listed?' THEN 'unlisted CCS question, other department concern, who can help me, ask a CCS council officer, council adviser contact, Aeron Enova, help with a CCS issue'
    WHEN 'Who can I contact about department shirt concerns?' THEN 'department shirt inquiry, shirt order update, shirt concern contact, shirt availability, shirt distribution, contact council secretary, Lorren Erandio, Abiegail Rellermo'
    WHEN 'Who is the current CCS Governor?' THEN 'current student council governor, CCS governor name, council president, student government president, governor for academic year 2026 2027, Selene Tychingco'
    WHEN 'Who should I contact about department fee payments?' THEN 'department fee payment contact, who receives CCS fee, ask about departmental dues, treasurer contact, GCash payment inquiry, payment confirmation, Renalyn Ubalde, Anika Arjite'
    WHEN 'Can COMSAI confirm my personal academic or payment records?' THEN 'private student records, personal grades, individual account balance, payment record lookup, account-specific information, student privacy, password safety, confidential academic information'
    WHEN 'What are the CCS rules for using the laboratories?' THEN 'computer laboratory rules, lab safety policy, laboratory conduct, rules for using computers, equipment use policy, lab access requirements, classroom computer room rules'
    WHEN 'What should I do if I cannot find the information I need?' THEN 'missing CCS information, question has no answer, where to ask an unlisted question, contact CCS office, ask a faculty member, ask council, official contact channel'
    WHEN 'How can I contact a CCS faculty member?' THEN 'faculty email address, instructor contact details, contact my teacher, reach a professor, faculty directory, CCS instructor email, faculty office hours'
    WHEN 'Where can I find the CCS Dean?' THEN 'Dean office location, locate CCS Dean, where is the Dean, CCS office location, second floor, beside the President office, Dean office directions'
    WHEN 'Who are the CCS faculty members?' THEN 'CCS faculty roster, faculty directory, list of instructors, names of teachers, teaching staff, faculty roles and qualifications, OIC Dean, administrator, lab head, instructors, technical support staff, Frank Kiven Ablao, Christian Drigon Aga, Edcel Martinez, Ian Godwin Agapito, Aeron Dave Enova, Kevin Ezer Tanierla, John Eddie Ablao, John Virgel Lo, Ferdinand Postre, Annabel Nagera'
    WHEN 'Who is the CCS Dean?' THEN 'CCS Dean name, who leads the department, department head, OIC Dean, technical head, Frank Kiven B Ablao, Sir Frank Ablao'
    WHEN 'Can I ask a question in my own words?' THEN 'ask COMSAI naturally, different wording, paraphrase a question, use my own words, how to phrase a question, ask in plain language'
    WHEN 'I don''t understand the answer. Can you explain it more simply?' THEN 'explain again, simpler explanation, clarify the answer, rephrase in simple words, easier explanation, make the response clearer'
    WHEN 'What if COMSAI does not know the answer?' THEN 'COMSAI cannot answer, no verified answer, unknown CCS question, missing knowledge-base information, ask CCS personnel, official channel, do not guess'
    WHEN 'Where can I report incorrect information in COMSAI?' THEN 'report wrong answer, report incorrect CCS information, submit correction, fix knowledge-base entry, COMSAI error report, contact administrator'
    WHEN 'Is COMSAI an official source of CCS information?' THEN 'COMSAI official status, can I rely on this answer, verify CCS information, information accuracy, confirm with the college, official authority, urgent official matters'
    WHEN 'What information can I ask COMSAI about?' THEN 'topics COMSAI can answer, supported student questions, what can I ask, available information, faculty officers rooms labs announcements academic processes, COMSAI capabilities'
    WHEN 'What is COMSAI?' THEN 'COMSAI definition, meaning of COMSAI, what does COMSAI do, AI student assistant, student information chatbot, College of Computer Studies assistant, Mabini Colleges'
    WHEN 'Where is the Annex Laboratory?' THEN 'Annex Lab location, find Annex Laboratory, Annex computer lab room, MRI Building, second floor, room 203, directions to Annex lab'
    WHEN 'Where is the Key Laboratory?' THEN 'Key Lab location, KeyLab room, find Key Laboratory, MRI Building, second floor, room 212, directions to Key lab'
    WHEN 'Where is the Main Laboratory?' THEN 'Main Lab location, find Main Laboratory, main computer lab room, MRI Building, second floor, room 207, directions to Main lab'
    WHEN 'What does A or B mean in my class schedule?' THEN 'class schedule A meaning, class schedule B meaning, A B modality, face to face online rotation, hybrid class arrangement, weekly subject A or B, online first or second meeting'
    WHEN 'Where can I find the CCS class schedule?' THEN 'view CCS timetable, where to see class schedule, student portal timetable, subject meeting times, course schedule, room assignments in schedule, instructor schedule'
    ELSE search_terms
END
WHERE title IN (
    'How do I comply with an INC grade?', 'How do I process a COG (Certificate of Grades)?', 'How do I settle my portal balance?', 'When is the student portal available or scheduled?', 'Where can I ask about my grades?', 'Who should I contact about an INC grade?',
    'How many times is clearance done in one academic year?', 'What are the requirements for [specific CCS process]?', 'What are the requirements for the semester clearance?', 'What is the clearance for the end of the semester?', 'When is the clearance processed?', 'Where can I get the clearance form?', 'Where can I get the form for [specific process]?',
    'Are there any upcoming CCS activities?', 'Has the schedule of the event changed?', 'What is the latest announcement?', 'Where can I view the full details of an announcement?',
    'CCS Central Council Officers', 'How can I check if my name is on the department shirt list?', 'How can I pay the CCS department fee?', 'Who can help with a CCS concern that is not listed?', 'Who can I contact about department shirt concerns?', 'Who is the current CCS Governor?', 'Who should I contact about department fee payments?',
    'Can COMSAI confirm my personal academic or payment records?', 'What are the CCS rules for using the laboratories?', 'What should I do if I cannot find the information I need?',
    'How can I contact a CCS faculty member?', 'Where can I find the CCS Dean?', 'Who are the CCS faculty members?', 'Who is the CCS Dean?',
    'Can I ask a question in my own words?', 'I don''t understand the answer. Can you explain it more simply?', 'What if COMSAI does not know the answer?', 'Where can I report incorrect information in COMSAI?',
    'Is COMSAI an official source of CCS information?', 'What information can I ask COMSAI about?', 'What is COMSAI?',
    'Where is the Annex Laboratory?', 'Where is the Key Laboratory?', 'Where is the Main Laboratory?', 'What does A or B mean in my class schedule?', 'Where can I find the CCS class schedule?'
);

COMMIT;
