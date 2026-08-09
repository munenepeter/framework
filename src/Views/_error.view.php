<?php
/**
 * Error view
 *
 * Available variables (@see abort() in helpers.php):
 * @var int         $code    HTTP-ish status/error code
 * @var string      $message Raw error/exception message
 * @var string|null $file    File the error originated in (dev only)
 * @var int|null    $line    Line the error originated on (dev only)
 * @var array|null  $trace   Exception::getTrace() style trace (dev only, thrown exceptions only)
 */

$dev = is_dev();

$errors = getErrorMessages();
$info = $errors[(string) $code] ?? $errors['500'];

$title = $info['title'];
$clientMessage = $info['client_message'];
$devMessage = $message ?: $info['developer_message'];

$logId = substr(md5(($file ?? '') . ($line ?? '') . microtime()), 0, 12);
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$timestamp = date('D, d M Y H:i:s');

$codeContext = ($dev && $file && $line) ? getCodeContext($file, (int) $line, 6) : [];

// A trace is only shown when it exists and actually came from a thrown
// Exception (see App::handleHttp). PHP-level errors (warnings, notices,
// undefined vars, etc.) never pass a trace here — debug_backtrace() inside
// the error handler would just show its own internals, not where the
// error occurred, so file/line + the snippet above are the source of truth.
$hasUsefulTrace = $dev && !empty($trace);

$requestInfo = [
    'Method'     => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'URL'        => !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A',
    'IP'         => $ip,
    'User Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'GET'        => !empty($_GET) ? json_encode($_GET) : '—',
    'POST'       => !empty($_POST) ? json_encode(array_map(fn($v) => is_string($v) ? $v : '[data]', $_POST)) : '—',
];

if ($dev) {
    logger('error', "[{$logId}] {$code} {$title}: {$devMessage}" . ($file ? " in {$file}:{$line}" : ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($code) ?> - <?= htmlspecialchars($title) ?></title>
    <style>
        :root {
            --primary-dark: #1a2b4a;
            --primary-medium: #33518c;
            --primary-lighter: #dbe4f3;
            --bg-page: #f7f9fc;
            --bg-white: #ffffff;
            --bg-header: #eef2fb;
            --text-body: #33518c;
            --text-muted: #6b7a99;
            --accent-orange: #e07a3f;
            --border: #dbe4f3;
            --code-bg: #101828;
            --code-text: #d7e0f5;
            --code-line-highlight: rgba(224, 122, 63, 0.18);
            --code-line-highlight-border: #e07a3f;
            --font-sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --font-mono: 'SF Mono', 'Fira Code', Menlo, Consolas, monospace;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
       body{font-family:var(--font-sans);background:var(--bg-page);color:var(--text-body);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem;line-height:1.6}.card{width:100%;max-width:46rem;background:var(--bg-white);border-radius:.75rem;border:1px solid var(--border);box-shadow:0 10px 30px -10px rgb(26 43 74 / .15);overflow:hidden}.card-header{padding:1.75rem 2rem;background:var(--bg-header);border-bottom:1px solid var(--border)}.code-badge{display:inline-block;font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--accent-orange);background:#fdf1e8;border:1px solid #f6dcc4;padding:.25rem .625rem;border-radius:999px;margin-bottom:.75rem}.title{font-size:1.75rem;font-weight:700;color:var(--primary-dark);margin-bottom:.375rem}.subtitle{color:var(--text-muted);font-size:.9375rem}.card-body{padding:1.75rem 2rem}.section-label{font-size:.8125rem;font-weight:600;color:var(--primary-dark);margin-bottom:.375rem}.section-text{font-size:.875rem;color:var(--text-muted);margin-bottom:1.25rem}.chip-row{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)}.chip{display:inline-flex;align-items:center;gap:.375rem;font-size:.75rem;font-family:var(--font-mono);padding:.3125rem .625rem;border-radius:.375rem;background:var(--primary-lighter);color:var(--primary-dark)}.chip b{font-family:var(--font-sans);font-weight:600;color:var(--text-muted)}.chip.chip-time{background:#eef2fb}.chip.chip-ip{background:#eef7f0;color:#1f6b3a}.chip.chip-log{background:#fdf1e8;color:#8a4a1f}.chip.chip-php{background:#eef2fb;color:var(--primary-medium)}.chip.chip-fw{background:#f3edfb;color:#5a3c8a}.chip.chip-db{background:#fbf0f3;color:#9c3a55}.dev-block{margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--border)}.location{font-family:var(--font-mono);font-size:.8125rem;color:var(--primary-medium);margin-bottom:.75rem}.env-tag{display:inline-block;font-size:.6875rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--primary-medium);background:var(--primary-lighter);padding:.125rem .5rem;border-radius:.25rem}.snippet{background:var(--code-bg);color:var(--code-text);border-radius:.5rem;overflow:hidden;font-family:var(--font-mono);font-size:.75rem;margin-bottom:1rem}.snippet-file{padding:.5rem 1rem;font-size:.6875rem;color:#9fb3d9;border-bottom:1px solid rgb(255 255 255 / .08);word-break:break-all}.snippet-line{display:flex;padding:.0625rem 0}.snippet-line.is-error{background:var(--code-line-highlight);border-left:3px solid var(--code-line-highlight-border)}.snippet-line:not(.is-error){border-left:3px solid #fff0}.snippet-num{flex:0 0 3rem;text-align:right;padding-right:.75rem;color:#5b6b8c;user-select:none}.snippet-line.is-error .snippet-num{color:var(--accent-orange);font-weight:600}.snippet-code{white-space:pre;padding-right:1rem}.snippet-code .code-keyword{color:#c99bf0}.snippet-code .code-string{color:#8fd68f}.snippet-code .code-number{color:#f3b27a}.snippet-code .code-variable{color:#f0879b}.snippet-code .code-comment{color:#6b7a99;font-style:italic}.trace{background:var(--code-bg);color:var(--code-text);border-radius:.5rem;padding:.25rem 0;max-height:16rem;overflow-y:auto;font-family:var(--font-mono);font-size:.75rem;margin-bottom:1rem}.trace-frame{padding:.5rem 1rem;border-bottom:1px solid rgb(255 255 255 / .06)}.trace-frame:last-child{border-bottom:none}.trace-num{color:#6b7a99;margin-right:.5rem}.trace-fn{color:#f3c78a}.trace-file{color:#9fb3d9;display:block;margin-top:.125rem}details.request-info{margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--border)}details.request-info summary{cursor:pointer;font-size:.8125rem;font-weight:600;color:var(--primary-dark);list-style:none;display:flex;align-items:center;gap:.375rem}details.request-info summary::-webkit-details-marker{display:none}details.request-info summary::before{content:'▸';color:var(--text-muted);transition:transform 0.15s ease;font-size:.75rem}details.request-info[open] summary::before{transform:rotate(90deg)}.request-table{margin-top:.75rem;width:100%;border-collapse:collapse;font-size:.8125rem}.request-table tr{border-bottom:1px solid var(--border)}.request-table tr:last-child{border-bottom:none}.request-table th{text-align:left;padding:.5rem .75rem .5rem 0;color:var(--text-muted);font-weight:600;white-space:nowrap;vertical-align:top;width:7rem}.request-table td{padding:.5rem 0;font-family:var(--font-mono);color:var(--primary-dark);word-break:break-all}
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <span class="code-badge">Error <?= htmlspecialchars($code) ?></span>
            <h1 class="title"><?= htmlspecialchars($title) ?></h1>
            <p class="subtitle"><?= htmlspecialchars($clientMessage) ?></p>
        </div>

        <div class="card-body">
            <p class="section-label">What happened?</p>
            <p class="section-text"><?= htmlspecialchars($dev ? $devMessage : $clientMessage) ?></p>

            <?php if ($dev): ?>
                <div class="dev-block">
                    <p class="section-label">
                        Debug context <span class="env-tag">development</span>
                    </p>

                    <?php if ($file): ?>
                        <p class="location"><?= htmlspecialchars($file) ?><?= $line ? ':' . (int) $line : '' ?></p>
                    <?php endif; ?>

                    <?php if (!empty($codeContext)): ?>
                        <div class="snippet">
                            <div class="snippet-file"><?= htmlspecialchars($file) ?></div>
                            <?php foreach ($codeContext as $row): ?>
                                <div class="snippet-line<?= $row['isError'] ? ' is-error' : '' ?>">
                                    <span class="snippet-num"><?= $row['number'] ?></span>
                                    <span class="snippet-code"><?= highlightCode($row['content']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasUsefulTrace): ?>
                        <div class="trace">
                            <?php foreach (array_slice($trace, 0, 15) as $i => $frame): ?>
                                <?php
                                    $fn = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
                                    $frameFile = $frame['file'] ?? null;
                                    $frameLine = $frame['line'] ?? null;
                                ?>
                                <div class="trace-frame">
                                    <span class="trace-num">#<?= $i ?></span><span class="trace-fn"><?= htmlspecialchars($fn ?: '{closure}') ?>()</span>
                                    <?php if ($frameFile): ?>
                                        <span class="trace-file"><?= htmlspecialchars($frameFile) ?><?= $frameLine ? ':' . (int) $frameLine : '' ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="chip-row">
                <span class="chip chip-time"><b>Time</b> <?= htmlspecialchars($timestamp) ?></span>
                <span class="chip chip-ip"><b>IP</b> <?= htmlspecialchars($ip) ?></span>
                <span class="chip chip-log"><b>Log</b> <?= htmlspecialchars($logId) ?></span>
                <?php if ($dev): ?>
                    <span class="chip chip-php"><b>PHP</b> <?= htmlspecialchars(phpversion()) ?></span>
                    <span class="chip chip-fw"><b>Tabel</b> <?= htmlspecialchars(app()->version()) ?></span>
                    <?php if ($dbDriver = app()->get('config.db.driver') ?? null): ?>
                        <span class="chip chip-db"><b>DB</b> <?= htmlspecialchars($dbDriver) ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if ($dev): ?>
                <details class="request-info">
                    <summary>Request information</summary>
                    <table class="request-table">
                        <?php foreach ($requestInfo as $key => $value): ?>
                            <tr>
                                <th><?= htmlspecialchars($key) ?></th>
                                <td><?= htmlspecialchars($value) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </details>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>