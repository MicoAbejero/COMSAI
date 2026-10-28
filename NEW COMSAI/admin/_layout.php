<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

function admin_layout_start(string $title, string $active): array
{
    app_security_headers();
    $admin = require_admin();
    $nav = [
        'index.php' => ['Dashboard', '⌂'],
        'knowledge.php' => ['Knowledge base', '▤'],
        'announcements.php' => ['Announcements', '◷'],
        'categories.php' => ['Categories', '⌘'],
        'activity.php' => ['Chatbot activity', '◉'],
        'account.php' => ['Administrator account', '◎'],
    ];
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h($title) ?> | COMSAI</title><link rel="stylesheet" href="../assets/css/admin.css?v=20261026-1"><link rel="stylesheet" href="../assets/css/admin-overrides.css?v=20261017-4"></head><body class="admin-page"><header class="admin-topbar"><button class="menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button></header><div class="admin-shell"><aside class="admin-sidebar"><a class="admin-brand" href="index.php" aria-label="COMSAI dashboard"><img class="admin-mark" src="../assets/comsai-icon.png" alt=""><span><strong>COMSAI</strong><small>CCS information management</small></span></a><nav aria-label="Administrator navigation">
    <?php foreach ($nav as $route => [$label, $icon]): if ($route === 'categories.php' && $admin['role'] !== 'admin') continue; ?>
      <a class="nav-link <?= $active === $route ? 'active' : '' ?>" href="<?= h($route) ?>"><span aria-hidden="true"><?= h($icon) ?></span><?= h($label) ?></a>
    <?php endforeach; ?>
    <a class="nav-link" href="../?new_chat=1" target="_blank" rel="noopener"><span aria-hidden="true">↗</span>Student interface</a>
    </nav><form method="post" action="../logout.php" class="logout-form" data-confirm="Are you sure you want to log out?"><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><button class="nav-link logout-link" type="submit"><span aria-hidden="true">⇥</span>Log out</button></form></aside><main class="admin-content"><div class="content-heading"><div><p class="eyebrow">ADMINISTRATION</p><h1><?= h($title) ?></h1></div><span class="admin-identity"><?= h($admin['name']) ?> <small><?= h($admin['role']) ?></small></span></div>
    <?php return $admin;
}

function admin_layout_end(): void
{
    ?></main></div><script src="../assets/js/admin.js?v=20261027-4" defer></script></body></html><?php
}

function admin_log(PDO $pdo, int $adminId, string $action, string $entity, ?int $entityId = null): void
{
    $statement = $pdo->prepare('INSERT INTO activity_logs (admin_id, action, entity_type, entity_id) VALUES (:admin, :action, :entity, :id)');
    $statement->execute(['admin' => $adminId, 'action' => $action, 'entity' => $entity, 'id' => $entityId]);
}
