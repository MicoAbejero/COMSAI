INSERT INTO knowledge_base (category_id, title, content, source_title, status)
SELECT id,
       'CCS Central Council Officers',
       'Governor: Selene E. Tychingco\nVice Governor - Internal: Angel Ayessa O. Cacho\nVice Governor - External: Giedhell Ann M. Serrano\nExecutive Secretary: Abiegail T. Rellermo\nAssistant Secretary: Lorren Joyce L. Erandio\nChief Treasurer: Renalyn T. Ubalde\nDeputy Treasurer: Anika Kaye E. Arjite\nAuditor: Renz Darrel O. Gomez\nChief Peace Officer: Justine D. Canaria\nDeputy Peace Officer: Jose Russel M. Cabunias\nDeputy Peace Officer: John Kevin B. Abogado\nChief Communication and Media Officer: John Bernard C. De Leon\nDeputy Communication and Media Officer: Francis Kevin Y. Calvario\nCommunication and Media Officer: Shalom Emannuel M. Abiera\nCommunication and Media Officer: Richard A. Boribor\nCommunication and Media Officer: Condrad Jaimar G. Sale\nCommunication and Media Officer: Candice Fine Y. Montemayor\nFourth Year Representative: Eya Mae R. Bana\nThird Year Representative: Gian Carlo D. Bacia\nSecond Year Representative: Chelsey Joelle B. Esguerra\nFirst Year Representative: John Mark N. Molina\nFirst Year Representative: Mark Zedrix Zantua\nCCS Central Council Adviser: Aeron Dave C. Enova',
       'User-provided CCS council officer roster',
       'draft'
FROM categories
WHERE name = 'CCS Council Officers'
AND NOT EXISTS (SELECT 1 FROM knowledge_base WHERE title = 'CCS Central Council Officers');
