<?php

/**
 * (translate with claude.ai)
 * Advanced Diagnostic Tool - TeamSpeak 6 Web Explorer
 * Author: Guraz
 * ⚠️ WARNING: DO NOT LEAVE THIS FILE ON A PRODUCTION SERVER ⚠️
 */

function e($string) { return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8'); }

$config_file = __DIR__ . '/config.php';
$has_config  = file_exists($config_file);

if ($has_config) {
    $config = require $config_file;
    $cfg    = $config['server'];
    $api_key_masked = empty($cfg['api_key'])
        ? "NOT SET"
        : substr($cfg['api_key'], 0, 6) . str_repeat('•', 20) . substr($cfg['api_key'], -4);
}

// Diagnostic utilities
function check_system_reqs() {
    $reqs = [];

    $reqs['PHP Version'] = [
        'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'value'  => PHP_VERSION,
        'msg'    => 'PHP 7.4 or higher is recommended.'
    ];

    $reqs['allow_url_fopen'] = [
        'status' => ini_get('allow_url_fopen'),
        'value'  => ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled',
        'msg'    => 'Required to communicate with the remote API.'
    ];

    $reqs['Folder Permissions'] = [
        'status' => is_writable(__DIR__),
        'value'  => is_writable(__DIR__) ? 'Writable' : 'Read-only',
        'msg'    => 'Required to create the cache.json file.'
    ];

    return $reqs;
}

function test_tcp_connection($host, $port) {
    $start = microtime(true);
    $fp    = @fsockopen($host, $port, $errno, $errstr, 2);
    $time  = round((microtime(true) - $start) * 1000);
    if (!$fp) {
        return ['status' => false, 'msg' => "Connection refused ({$errstr})", 'time' => '-'];
    } else {
        fclose($fp);
        return ['status' => true, 'msg' => 'Port open and accessible', 'time' => $time . ' ms'];
    }
}

function test_endpoint($url, $api_key, $endpoint_name) {
    $options = [
        'http' => [
            'header'        => "x-api-key: {$api_key}\r\n",
            'method'        => 'GET',
            'timeout'       => 3,
            'ignore_errors' => true
        ]
    ];
    $context    = stream_context_create($options);
    $start_time = microtime(true);
    $response   = @file_get_contents($url, false, $context);
    $ping       = round((microtime(true) - $start_time) * 1000);
    $http_code  = "Unknown";

    if (isset($http_response_header) && is_array($http_response_header)) {
        if (preg_match('#HTTP/[0-9\.]+\s+([0-9]+)#', $http_response_header[0], $matches)) {
            $http_code = intval($matches[1]);
        }
    }

    $html  = "<div class='endpoint-card'><div class='endpoint-header'>";
    $html .= "<h2>➔ /{$endpoint_name}</h2><div class='endpoint-meta'>";

    if ($response === false) {
        $html .= "<span class='badge error'>HTTP Failure (Timeout)</span></div></div>";
        $html .= "<div class='endpoint-body'><p style='color: #f87171;'>Unable to fetch HTTP data.</p></div>";
    } else {
        $data    = json_decode($response, true);
        $ts_code = isset($data['status']['code']) ? $data['status']['code'] : 'N/A';

        if ($http_code == 200 && $ts_code === 0) {
            $html .= "<span class='badge success'>HTTP 200</span> <span class='badge success'>TS Code: 0 (OK)</span>";
        } elseif ($ts_code === 5120) {
            $html .= "<span class='badge warning'>HTTP {$http_code}</span> <span class='badge warning'>TS Code: 5120 (Out of Scope)</span>";
        } elseif ($http_code == 401 || $ts_code === 2568) {
            $html .= "<span class='badge error'>HTTP {$http_code}</span> <span class='badge error'>TS Code: {$ts_code} (Invalid Key)</span>";
        } else {
            $html .= "<span class='badge error'>HTTP {$http_code}</span> <span class='badge error'>TS Code: {$ts_code}</span>";
        }

        $html .= "<span class='badge info'>{$ping} ms</span></div></div>";

        if ($ts_code === 5120 && $endpoint_name == 'serverinfo') {
            $html .= "<div class='help-box'>ℹ️ <b>Note:</b> Error 5120 is normal if your API key is configured as read-only (scope=read).</div>";
        }

        $pretty_json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $html .= "<div class='endpoint-body'><pre><code>" . htmlspecialchars($pretty_json) . "</code></pre></div>";
    }

    $html .= "</div>";
    return $html;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TS6 API Diagnostic</title>
    <style>
        :root { --bg: #0f172a; --panel: #1e293b; --text: #f8fafc; --text-muted: #94a3b8; --border: #334155; }
        body { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; background-color: var(--bg); color: var(--text); margin: 0; padding: 2rem; line-height: 1.5; }
        .container { max-width: 900px; margin: 0 auto; }
        .alert-danger { background-color: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; text-align: center; font-weight: bold; margin-bottom: 2rem; }
        .section-title { font-size: 1.5rem; color: #38bdf8; border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-top: 2rem; margin-bottom: 1rem; }
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .card { background: var(--panel); border: 1px solid var(--border); border-radius: 8px; padding: 1.25rem; }
        .card h3 { margin-top: 0; font-size: 1.1rem; color: #e2e8f0; margin-bottom: 1rem; }
        .item-row { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 0.5rem 0; }
        .item-row:last-child { border-bottom: none; }
        .item-label { color: var(--text-muted); font-size: 0.9rem; }
        .item-val { font-weight: bold; }
        .val-ok  { color: #10b981; } .val-err { color: #ef4444; } .val-warn { color: #f59e0b; }
        .endpoint-card { background: var(--panel); border: 1px solid var(--border); border-radius: 8px; margin-bottom: 1.5rem; overflow: hidden; }
        .endpoint-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--border); }
        .endpoint-header h2 { margin: 0; font-size: 1.1rem; color: #eab308; }
        .endpoint-meta { display: flex; gap: 0.5rem; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .badge.success { background: rgba(16,185,129,0.2); color: #6ee7b7; border: 1px solid #10b981; }
        .badge.error   { background: rgba(239,68,68,0.2);  color: #fca5a5; border: 1px solid #ef4444; }
        .badge.warning { background: rgba(245,158,11,0.2); color: #fcd34d; border: 1px solid #f59e0b; }
        .badge.info    { background: rgba(56,189,248,0.2); color: #7dd3fc; border: 1px solid #38bdf8; }
        .help-box { margin: 1rem 1.5rem 0 1.5rem; padding: 0.75rem; background: rgba(56,189,248,0.1); border-left: 4px solid #38bdf8; font-size: 0.9rem; color: #bae6fd; }
        .endpoint-body pre { margin: 0; padding: 1.5rem; overflow-x: auto; font-size: 0.85rem; color: #a7f3d0; max-height: 400px; }
    </style>
</head>
<body>
<div class="container">

    <div class="alert-danger">
        ⚠️ DANGER: NEVER LEAVE THIS FILE ON YOUR PUBLIC WEB SERVER.<br>
        Delete 'debug.php' from your hosting after use.
    </div>

    <?php if (!$has_config): ?>
    <div class="card" style="border-color: #ef4444;">
        <h3 style="color: #fca5a5;">❌ config.php file not found</h3>
        <p>Please create your <code>config.php</code> file before running the diagnostic.</p>
    </div>
    <?php else: ?>

    <h2 class="section-title">🔍 Environment Diagnostic</h2>

    <div class="grid-cards">
        <div class="card">
            <h3>⚙️ Detected Configuration</h3>
            <div class="item-row"><span class="item-label">API Host</span><span class="item-val"><?php echo e($cfg['host']); ?></span></div>
            <div class="item-row"><span class="item-label">API Port</span><span class="item-val"><?php echo e($cfg['port']); ?></span></div>
            <div class="item-row"><span class="item-label">Server ID</span><span class="item-val"><?php echo e($cfg['server_id']); ?></span></div>
            <div class="item-row"><span class="item-label">API Key</span><span class="item-val" style="color: #fb923c;"><?php echo e($api_key_masked); ?></span></div>
        </div>

        <div class="card">
            <h3>💻 Web Server (PHP)</h3>
            <?php foreach (check_system_reqs() as $name => $req): ?>
            <div class="item-row">
                <span class="item-label" title="<?php echo e($req['msg']); ?>"><?php echo e($name); ?></span>
                <span class="item-val <?php echo $req['status'] ? 'val-ok' : 'val-err'; ?>">
                    <?php echo $req['status'] ? '✓ ' : '✗ '; ?><?php echo e($req['value']); ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h3>📡 Network & Cache</h3>
            <?php
                $net          = test_tcp_connection($cfg['host'], $cfg['port']);
                $cache_exists = file_exists($cfg['cache_file']);
            ?>
            <div class="item-row">
                <span class="item-label">TCP Ping (<?php echo e($cfg['port']); ?>)</span>
                <span class="item-val <?php echo $net['status'] ? 'val-ok' : 'val-err'; ?>" title="<?php echo e($net['msg']); ?>">
                    <?php echo $net['status'] ? '✓ ' : '✗ '; ?><?php echo e($net['time']); ?>
                </span>
            </div>
            <div class="item-row">
                <span class="item-label">Cache File</span>
                <span class="item-val <?php echo $cache_exists ? 'val-ok' : 'val-warn'; ?>">
                    <?php echo $cache_exists ? '✓ Present' : 'Not yet generated'; ?>
                </span>
            </div>
            <?php if ($cache_exists): ?>
            <div class="item-row">
                <span class="item-label">Cache Age</span>
                <span class="item-val"><?php echo (time() - filemtime($cfg['cache_file'])) . ' sec'; ?></span>
            </div>
            <div class="item-row">
                <span class="item-label">Cache Size</span>
                <span class="item-val"><?php echo round(filesize($cfg['cache_file']) / 1024, 1) . ' KB'; ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <h2 class="section-title">🔌 TeamSpeak API Tests</h2>

    <?php
        $base_url = "http://{$cfg['host']}:{$cfg['port']}/{$cfg['server_id']}";
        echo test_endpoint("{$base_url}/channellist",        $cfg['api_key'], "channellist");
        echo test_endpoint("{$base_url}/clientlist?-groups", $cfg['api_key'], "clientlist?-groups");
        echo test_endpoint("{$base_url}/serverinfo",         $cfg['api_key'], "serverinfo");
    ?>

    <?php endif; ?>
</div>
</body>
</html>
