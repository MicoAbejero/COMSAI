<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
app_security_headers();
start_app_session();
$categories = [];
$databaseReady = false;
try {
    $pdo = db();
    $categories = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
    $databaseReady = true;
} catch (Throwable $error) {
    error_log('NEW COMSAI homepage database check failed: ' . $error->getMessage());
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
  <meta name="theme-color" content="#f7faf8">
  <title>COMSAI | CCS Student Information Assistant</title>
  <link rel="stylesheet" href="assets/css/style.css?v=20261026-1">
  <script src="assets/js/app.js" defer></script>
</head>
<body>
<main class="app-shell">
  <aside class="side-panel">
    <a class="brand" href="./" aria-label="COMSAI home"><img class="brand-mark" src="assets/comsai-icon.png" alt=""><span><strong>COMSAI</strong><small>CCS Student Information Assistant</small></span></a>
    <button class="new-chat-button" id="new-chat" type="button"><span aria-hidden="true">&#43;</span> New chat</button>
    <div class="panel-heading"><div><span class="eyebrow">EXPLORE CCS</span><h2>Information topics</h2></div></div>
    <div class="topic-list">
      <?php foreach ($categories as $category): ?>
      <button type="button" class="topic-button" data-question="<?= h($category['name']) ?>" data-category-id="<?= (int) $category['id'] ?>"><span class="topic-dot" aria-hidden="true"></span><?= h($category['name']) ?><span class="topic-arrow" aria-hidden="true">&#8250;</span></button>
      <?php endforeach; ?>
      <?php if ($categories === []): ?><p class="muted">Import the database schema to load categories.</p><?php endif; ?>
    </div>
    <a class="admin-link" href="login.php">Administrator sign in <span aria-hidden="true">&#8599;</span></a>
  </aside>
  <section class="chat-panel" aria-label="COMSAI chat">
    <div class="chat-heading"><div><span class="eyebrow">YOUR CCS GUIDE</span><h1>Ask COMSAI</h1></div><div class="chat-heading-badges"><span class="ai-pill"><span aria-hidden="true">&#10038;</span> Verified sources</span><span class="service <?= $databaseReady ? 'is-online' : 'is-offline' ?>"><i></i><?= $databaseReady ? 'Service available' : 'Setup required' ?></span></div></div>
    <div class="conversation" id="conversation" aria-live="polite" aria-relevant="additions text">
      <section class="welcome" id="welcome">
        <h2>Hello! Welcome to COMSAI.</h2>
        <p>I'm your CCS Student Information Assistant. Ask me about department announcements, faculty and officers, laboratories, academic requirements, schedules, and other CCS-related information.</p>
        <p class="welcome-notice">Answers depend on verified information currently available in the knowledge base.</p>
        <div class="suggestions" aria-label="Suggested questions">
          <button type="button" data-question="Who are the CCS faculty and officers?">Faculty and officers <span>&#8599;</span></button>
          <button type="button" data-question="What announcements and activities are current?">Announcements and activities <span>&#8599;</span></button>
          <button type="button" data-question="What rooms and laboratories are available?">Rooms and laboratories <span>&#8599;</span></button>
          <button type="button" data-question="What academic procedures should I know?">Academic procedures <span>&#8599;</span></button>
        </div>
      </section>
    </div>
    <form class="composer" id="chat-form">
      <label class="sr-only" for="question">Ask a CCS question</label>
      <textarea id="question" name="question" rows="1" maxlength="1000" placeholder="Ask a question about CCS..." required></textarea>
      <div class="composer-bottom"><span><kbd>Enter</kbd> to send <span class="composer-separator">&#183;</span> <kbd>Shift</kbd>+<kbd>Enter</kbd> for a new line</span><span class="composer-controls"><span class="char-count" id="char-count">0 / 1000</span><button class="button button-primary" id="send-button" type="submit"><span>Send</span><span aria-hidden="true">&#8593;</span></button></span></div>
    </form>
    <p class="chat-footnote">AI responses, when enabled, are generated only from matching published sources. Always check the references.</p>
  </section>
</main>
</body>
</html>
