<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
$admin=admin_layout_start('Chatbot activity','activity.php');
if($admin['role']!=='admin'){http_response_code(403);echo '<div class="alert">Administrator access required.</div>';admin_layout_end();exit;}
$pdo=db();
$daily=$pdo->query("SELECT DATE(created_at) AS day,COUNT(*) AS question_count FROM chat_messages WHERE sender='student' AND created_at>=DATE_SUB(CURDATE(),INTERVAL 13 DAY) GROUP BY DATE(created_at) ORDER BY day DESC")->fetchAll();
$recent=$pdo->query("SELECT message,created_at FROM chat_messages WHERE sender='student' ORDER BY id DESC LIMIT 30")->fetchAll();
?>
<section class="stats-grid"><article class="stat-card"><span>All recorded questions</span><strong><?=(int)$pdo->query("SELECT COUNT(*) FROM chat_messages WHERE sender='student'")->fetchColumn()?></strong><small>Anonymous session records</small></article><article class="stat-card"><span>Active conversations</span><strong><?=(int)$pdo->query('SELECT COUNT(*) FROM chat_sessions')->fetchColumn()?></strong><small>Session-scoped, no student accounts</small></article><article class="stat-card"><span>Questions in last 14 days</span><strong><?=(int)$pdo->query("SELECT COUNT(*) FROM chat_messages WHERE sender='student' AND created_at>=DATE_SUB(NOW(),INTERVAL 14 DAY)")->fetchColumn()?></strong><small>Usage only, not student identity</small></article></section>
<section class="admin-section activity-section"><div class="section-title"><div><h2>Recent student questions</h2><p>Questions are retained for up to 30 days; avoid collecting personal details.</p></div></div><div class="table-wrap"><table><thead><tr><th>Question</th><th>Received</th></tr></thead><tbody><?php foreach($recent as $item):?><tr><td><?=h($item['message'])?></td><td><?=h($item['created_at'])?></td></tr><?php endforeach;?><?php if(!$recent):?><tr><td colspan="2" class="empty-state">No questions have been recorded.</td></tr><?php endif;?></tbody></table></div></section>
<?php admin_layout_end(); ?>
