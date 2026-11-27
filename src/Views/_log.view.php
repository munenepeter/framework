<?php
/**
 * Log viewer
 *
 * @var string[] $logs newest first
 */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>System logs</title>
  <style>
    body { font: 14px/1.5 system-ui, sans-serif; margin: 0; background: #f6f6f8; color: #222; }
    header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: #fff; border-bottom: 1px solid #ddd; }
    h1 { font-size: 1.1rem; margin: 0; }
    main { padding: 1.5rem; }
    pre { margin: 0; padding: .35rem .75rem; border-bottom: 1px solid #eee; white-space: pre-wrap; word-break: break-word; font: 12px/1.5 ui-monospace, Menlo, monospace; background: #fff; }
    button { font: inherit; padding: .4rem .9rem; border: 1px solid #c33; background: #fff; color: #c33; border-radius: 4px; cursor: pointer; }
    .empty { padding: 2rem; text-align: center; color: #777; background: #fff; }
  </style>
</head>
<body>
<header>
  <h1>System logs <small style="color:#777">(<?= count($logs) ?> lines, newest first)</small></h1>
  <form method="post" action="/system/logs/delete" onsubmit="return confirm('Delete all logs?')">
    <?= csrf_field() ?>
    <button type="submit">Delete logs</button>
  </form>
</header>
<main>
  <?php if (!$logs): ?>
    <div class="empty">No log entries.</div>
  <?php else: foreach ($logs as $line): ?>
    <pre><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></pre>
  <?php endforeach; endif; ?>
</main>
</body>
</html>
