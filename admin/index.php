<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
$admin = admin_layout_start('Dashboard', 'index.php');
$pdo = db();
$stats = [
    'knowledge' => (int) $pdo->query('SELECT COUNT(*) FROM knowledge_base')->fetchColumn(),
    'published' => (int) $pdo->query("SELECT COUNT(*) FROM knowledge_base WHERE status = 'published'")->fetchColumn(),
    'drafts' => (int) $pdo->query("SELECT COUNT(*) FROM knowledge_base WHERE status = 'draft'")->fetchColumn(),
    'questions' => (int) $pdo->query("SELECT COUNT(*) FROM chat_messages WHERE sender = 'student'")->fetchColumn(),
];
$recent = $pdo->query('SELECT title, status, updated_at FROM knowledge_base ORDER BY updated_at DESC LIMIT 6')->fetchAll();
?>
<section class="welcome-admin"><div><span class="eyebrow">CONTENT OPERATIONS</span><h2>Good day, <?= h($admin['name']) ?>.</h2><p>Keep student answers current by publishing only verified CCS information.</p></div><a class="primary-button" href="knowledge.php">＋ Add information</a></section>
<section class="stats-grid" aria-label="Application statistics"><article class="stat-card"><span>Total knowledge entries</span><strong><?= $stats['knowledge'] ?></strong><small>All drafts and published items</small></article><article class="stat-card"><span>Published</span><strong><?= $stats['published'] ?></strong><small>Eligible when within dates</small></article><article class="stat-card"><span>Drafts</span><strong><?= $stats['drafts'] ?></strong><small>Not visible in student answers</small></article><article class="stat-card"><span>Recorded questions</span><strong><?= $stats['questions'] ?></strong><small>Anonymous usage count</small></article></section>
<div class="dashboard-columns"><section class="admin-section"><div class="section-title"><div><h2>Recent knowledge updates</h2><p>Latest edited entries and publication status.</p></div><a href="knowledge.php">Manage</a></div><?php if ($recent === []): ?><p class="empty-state">No entries yet. Add verified information to begin.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Entry</th><th>Status</th><th>Last updated</th></tr></thead><tbody><?php foreach ($recent as $item): ?><tr><td><?= h($item['title']) ?></td><td><span class="status <?= h($item['status']) ?>"><?= h($item['status']) ?></span></td><td><?= h($item['updated_at']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<aside class="admin-section system-card"><h2>System status</h2><p><span class="status-dot"></span>Database connected</p><p><span class="status-dot <?= app_env('OPENAI_API_KEY') === '' ? 'neutral' : '' ?>"></span>AI <?= app_env('OPENAI_API_KEY') === '' ? 'not configured; knowledge search is active' : 'configured' ?></p><p><span class="status-dot"></span><?= $stats['knowledge'] > 0 ? 'Knowledge base available' : 'Knowledge base is empty' ?></p><div class="quick-actions"><a href="announcements.php">Manage announcements <span>↗</span></a><a href="categories.php">View categories <span>↗</span></a><a href="activity.php">Review chat activity <span>↗</span></a></div></aside></div>
<?php admin_layout_end(); ?>
