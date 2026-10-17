<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/knowledge.php';

$passed = 0;
$failed = 0;
$check = static function (string $name, callable $test) use (&$passed, &$failed): void {
    try {
        $test();
        ++$passed;
        fwrite(STDOUT, "PASS: {$name}\n");
    } catch (Throwable $error) {
        ++$failed;
        fwrite(STDERR, "FAIL: {$name} — {$error->getMessage()}\n");
    }
};
$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

try {
    $pdo = db();
    $required = ['admins','categories','knowledge_base','announcements','chat_sessions','chat_messages','activity_logs','rate_limits'];
    $tableRows = $pdo->query('SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
    $check('database schema and citation column exist', static function () use ($assert, $required, $tableRows, $pdo): void {
        foreach ($required as $table) $assert(in_array($table, $tableRows, true), "Missing table {$table}.");
        $columns = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='chat_messages'")->fetchAll(PDO::FETCH_COLUMN);
        $assert(in_array('source_references', $columns, true), 'Chat source references column is missing.');
    });
    $categoryNames = $pdo->query('SELECT name FROM categories')->fetchAll(PDO::FETCH_COLUMN);
    $check('all requested starter categories are seeded', static function () use ($assert, $categoryNames): void {
        foreach (['Faculty and Staff','Dean and Department Personnel','CCS Council Officers','Student Organizations','Rooms and Laboratories','Announcements and Activities','Academic Requirements','Schedules and Important Dates','Academic Procedures','Department Policies','Contact Information','Frequently Asked Questions'] as $name) {
            $assert(in_array($name, $categoryNames, true), "Missing starter category {$name}.");
        }
    });
    $sample = $pdo->query("SELECT status FROM knowledge_base WHERE title='FICTIONAL TEST ONLY: Example draft'")->fetchColumn();
    $check('fictional sample remains unpublished', static function () use ($assert, $sample): void { $assert($sample === 'draft', 'Sample must remain a draft.'); });

    $token = 'test' . bin2hex(random_bytes(8));
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO categories (name,description) VALUES (:name,:description)')->execute(['name'=>'TEST '.$token,'description'=>'Temporary test category']);
    $category = (int) $pdo->lastInsertId();
    $insert = $pdo->prepare('INSERT INTO knowledge_base (category_id,title,content,source_title,effective_date,expiration_date,status) VALUES (:category,:title,:content,:source,:effective,:expires,:status)');
    $fixtures = [];
    foreach ([
        ['current', null, '2099-12-31', 'published', 'Verified test source'],
        ['future', '2099-01-01', '2099-12-31', 'published', 'Verified test source'],
        ['expired', null, '2000-01-01', 'published', 'Verified test source'],
        ['draft', null, '2099-12-31', 'draft', 'Verified test source'],
        ['unsourced', null, '2099-12-31', 'published', ''],
    ] as [$label,$effective,$expires,$status,$source]) {
        $insert->execute(['category'=>$category,'title'=>'AUTOMATED '.$token.' '.$label,'content'=>'Keyword '.$token.' test evidence.','source'=>$source,'effective'=>$effective,'expires'=>$expires,'status'=>$status]);
        $fixtures[$label] = (int) $pdo->lastInsertId();
    }
    $announcement = $pdo->prepare("INSERT INTO announcements (title,content,publish_at,expiration_date,status) VALUES (:title,:content,NOW(),:expires,'published')");
    $announcement->execute(['title'=>'AUTOMATED '.$token.' notice','content'=>'Keyword '.$token.' announcement evidence.','expires'=>'2099-12-31 23:59:59']);
    $announcementId = (int) $pdo->lastInsertId();

    $check('retrieval filters drafts, expired, future, and unsourced knowledge', static function () use ($pdo,$assert,$token,$fixtures,$announcementId): void {
        $rows=chat_sources($pdo,$token);$ids=array_column($rows,'title');
        $assert(in_array('AUTOMATED '.$token.' current',$ids,true),'Current published sourced knowledge was not retrieved.');
        $assert(in_array('AUTOMATED '.$token.' notice',$ids,true),'Current published announcement was not retrieved.');
        foreach(['future','expired','draft','unsourced'] as $label) $assert(!in_array('AUTOMATED '.$token.' '.$label,$ids,true),"Ineligible {$label} item was retrieved.");
        $assert(count(array_filter($rows,static fn(array $row):bool=>str_contains($row['title'],'AUTOMATED '.$token)))>=2,'Expected knowledge and announcement fixtures.');
    });
    $check('unmatched question produces no verified results', static function () use ($pdo,$assert): void { $assert(chat_sources($pdo,'qzv-no-match-'.bin2hex(random_bytes(6)))===[],'An unmatched query returned results.'); });

    $chatToken=hash('sha256',bin2hex(random_bytes(32)));
    $pdo->prepare('INSERT INTO chat_sessions (session_token_hash) VALUES (:token)')->execute(['token'=>$chatToken]);
    $session=(int)$pdo->lastInsertId();
    $refs=json_encode([['title'=>'Test source','url'=>'https://example.org/source']]);
    $pdo->prepare("INSERT INTO chat_messages (chat_session_id,sender,message,source_references) VALUES (:session,'assistant','Test answer',:refs)")->execute(['session'=>$session,'refs'=>$refs]);
    $stored=$pdo->prepare('SELECT source_references FROM chat_messages WHERE chat_session_id=:id');$stored->execute(['id'=>$session]);
    $check('chat citations are retained with anonymous session messages', static function () use ($assert,$stored): void { $value=json_decode((string)$stored->fetchColumn(),true);$assert(($value[0]['title']??'')==='Test source','Citation did not round-trip.'); });

    $check('HTML output escaping works', static function () use ($assert): void { $assert(h('<script>')==='&lt;script&gt;','HTML was not escaped.'); });
    start_app_session();
    $csrf=csrf_token();$_POST['_csrf']=$csrf;
    $check('CSRF tokens validate securely', static function () use ($assert): void { $assert(verify_csrf(),'Valid token rejected.');$_POST['_csrf']='invalid';$assert(!verify_csrf(),'Invalid token accepted.');unset($_POST['_csrf']); });
    $scope='test-'.$token;
    $check('database rate limiting stops excess requests', static function () use ($pdo,$assert,$scope): void { $assert(check_rate_limit($pdo,$scope,'anonymous',2,60),'First request blocked.');$assert(check_rate_limit($pdo,$scope,'anonymous',2,60),'Second request blocked.');$assert(!check_rate_limit($pdo,$scope,'anonymous',2,60),'Excess request allowed.'); });
} catch (Throwable $error) {
    ++$failed;
    fwrite(STDERR, 'FAIL: test setup — '.$error->getMessage()."\n");
} finally {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
}

fwrite(STDOUT, sprintf("\n%d passed, %d failed. Test fixtures were rolled back.\n",$passed,$failed));
exit($failed===0?0:1);
