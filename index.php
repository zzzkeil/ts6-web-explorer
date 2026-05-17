<?php

/**
 * (translate with claude.ai)
 * Web Explorer - TeamSpeak 6
 */

$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    die("Error: The 'config.php' file could not be found. Please create it from 'config.example.php'.");
}
$config = require $config_file;

// Utilities
function e($string) { return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8'); }
function safeUrl($filename) { return str_replace('%2F', '/', rawurlencode($filename)); }
function generateStars($count, $opacity) {
    $shadows = [];
    for ($i = 0; $i < $count; $i++) {
        $x = rand(0, 2000); $y = rand(0, 2000);
        $shadows[] = "{$x}px {$y}px rgba(255, 255, 255, {$opacity})";
    }
    return implode(", ", $shadows);
}

// API fetching (strictly READ-ONLY - scope=read)
function fetch_teamspeak_data($cfg) {
    $cache_file = $cfg['cache_file'];
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cfg['cache_time']) {
        $cached_data = file_get_contents($cache_file);
        if ($cached_data) return json_decode($cached_data, true);
    }
    $options = ['http' => ['header' => "x-api-key: {$cfg['api_key']}\r\n", 'method' => 'GET', 'timeout' => 2, 'ignore_errors' => true]];
    $context = stream_context_create($options);
    $base_url = "http://{$cfg['host']}:{$cfg['port']}/{$cfg['server_id']}";
    $channels_res = @file_get_contents("$base_url/channellist", false, $context);
    $clients_res  = @file_get_contents("$base_url/clientlist?-groups", false, $context);
    if ($channels_res !== false && $clients_res !== false) {
        $data = [
            'channels' => json_decode($channels_res, true)['body'] ?? [],
            'clients'  => json_decode($clients_res,  true)['body'] ?? []
        ];
        file_put_contents($cache_file, json_encode($data));
        return $data;
    }
    return ['channels' => [], 'clients' => []];
}

// Hierarchical sorting
function sort_ts_channels(array $channels, $pid = 0) {
    $ordered = []; $by_order = [];
    foreach ($channels as $c) { if (($c['pid'] ?? 0) == $pid) $by_order[$c['channel_order'] ?? 0] = $c; }
    $current_order = 0; $max_iterations = count($channels) + 1; $i = 0;
    while (isset($by_order[$current_order]) && $i < $max_iterations) {
        $channel = $by_order[$current_order]; $ordered[] = $channel;
        $sub_channels = sort_ts_channels($channels, $channel['cid']);
        foreach ($sub_channels as $sc) $ordered[] = $sc;
        $current_order = $channel['cid']; $i++;
    }
    return $ordered;
}

// Data processing
$start_time = microtime(true);
$ts_data    = fetch_teamspeak_data($config['server']);
$api_ping   = round((microtime(true) - $start_time) * 1000); // Calculates local API response time

$raw_channels = $ts_data['channels'];
$raw_clients  = $ts_data['clients'];
$is_online    = !empty($raw_channels);
$channels     = sort_ts_channels($raw_channels, 0);

$clients_by_channel  = [];
$total_real_clients  = 0;
if ($is_online) {
    foreach ($raw_clients as $client) {
        if (($client['client_type'] ?? 0) == 1) continue; // Skip bots/ServerQuery
        $clients_by_channel[$client['cid']][] = $client;
        $total_real_clients++;
    }
}

// Render AJAX fragment
ob_start();
if ($is_online): ?>

<div class="server-stats">
    <div class="stat-item">
        <span class="stat-label">Online</span>
        <span class="stat-value"><?php echo $total_real_clients; ?></span>
    </div>
    <div class="stat-item">
        <span class="stat-label">Channels</span>
        <span class="stat-value"><?php echo count($channels); ?></span>
    </div>
    <div class="stat-item">
        <span class="stat-label">API Latency</span>
        <span class="stat-value"><?php echo $api_ping; ?> ms</span>
    </div>
</div>

<div class="channel-list">
    <?php foreach ($channels as $channel):
    $c_name = $channel['channel_name'];
    if (strpos($c_name, '[spacer') !== false) continue;

    // Check if it's a centered spacer and strip the tag
    $is_cspacer = (strpos($c_name, '[cspacer]') !== false);
    if ($is_cspacer) {
        // This removes '[cspacer]' or '[cspacer123]' if numbering is used
        $c_name = preg_replace('/\[cspacer\d*\]/', '', $c_name);
    }

    $cid = $channel['cid'];
    $channel_clients = $clients_by_channel[$cid] ?? [];

    $bg_img = $config['bannieres_canaux'][$c_name] ?? ($config['images_generiques']['fond'] ?? '');
    $icon_img = $config['icones_canaux'][$c_name] ?? ($config['images_generiques']['icone'] ?? '');

    $bg_style = "";
    if (!empty($bg_img)) {
        $bg_style = "background-image: linear-gradient(90deg, rgba(19, 24, 35, 0.95) 0%, rgba(19, 24, 35, 0.7) 40%, rgba(19, 24, 35, 0.9) 100%), url('" . safeUrl($bg_img) . "');";
    }
    ?>
    <article class="channel <?php echo $is_cspacer ? 'channel-spacer' : ''; ?>">
    <header class="channel-header" style="<?php echo $bg_style; ?>">
    <div class="channel-title-group">
    <?php if (!empty($icon_img) && !$is_cspacer): ?>
    <img src="<?php echo safeUrl($icon_img); ?>" class="channel-icon" alt="" aria-hidden="true">
    <?php endif; ?>
    <h2><?php echo e($c_name); ?></h2>
    </div>
    <?php if (!$is_cspacer): ?>
    <span class="channel-meta">
    <?php echo count($channel_clients); ?> / <?php echo ($channel['channel_maxclients'] == -1) ? '∞' : e($channel['channel_maxclients']); ?>
    </span>
    <?php endif; ?>
    </header>

    <?php if (!empty($channel_clients)): ?>
    <ul class="client-list">
    <?php foreach ($channel_clients as $client): ?>
    <li class="client">
    <?php
    $role_icon = '<span class="client-no-role-icon"></span>';
    if (isset($client['client_servergroups'])) {
        $groups = explode(',', $client['client_servergroups']);
        foreach ($groups as $g_id) {
            if (isset($config['icones_roles'][$g_id])) {
                $role_icon = '<img src="' . safeUrl($config['icones_roles'][$g_id]) . '" class="role-icon" alt="Role">';
                break;
            }
        }
    }
    echo $role_icon;
    ?>
    <span><?php echo e($client['client_nickname']); ?></span>
    </li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    </article>
    <?php endforeach; ?>
    </div>

<?php else: ?>
<div class="empty-server">Unable to establish contact with the server.</div>
<?php endif;
$html_fragment = ob_get_clean();

// Handle AJAX response
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: text/html; charset=utf-8');
    echo $html_fragment;
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo e($config['ui']['sous_titre']); ?>">
    <title><?php echo e($config['ui']['titre']); ?></title>
    <link rel="icon" type="image/png" href="logo.png">
    <style>
        :root {
            --bg-color:    <?php echo e($config['ui']['couleur_fond']);    ?>;
            --panel-bg:    <?php echo e($config['ui']['couleur_panneau']); ?>;
            --accent:      <?php echo e($config['ui']['couleur_accent']);  ?>;
            --text-main:   <?php echo e($config['ui']['couleur_texte']);   ?>;
            --text-muted:  #64748b;
            --channel-bg:  #1e2433;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 2rem; display: flex; justify-content: center; min-height: 100vh; box-sizing: border-box; }

        <?php if ($config['ui']['activer_etoiles'] ?? false): ?>
        #space-bg { position: fixed; inset: 0; z-index: -1; background-color: var(--bg-color); overflow: hidden; }
        .star-layer { position: absolute; background: transparent; }
        .star-layer::after { content: ""; position: absolute; top: 2000px; background: inherit; box-shadow: inherit; }
        .stars-s { width: 1px; height: 1px; box-shadow: <?php echo generateStars(150, 0.4); ?>; animation: animStar 250s linear infinite; }
        .stars-m { width: 2px; height: 2px; box-shadow: <?php echo generateStars(50,  0.6); ?>; animation: animStar 180s linear infinite; }
        .stars-l { width: 2px; height: 2px; box-shadow: <?php echo generateStars(15,  0.8); ?>; animation: animStar 100s linear infinite; }
        @keyframes animStar { from { transform: translateY(0); } to { transform: translateY(-2000px); } }
        <?php endif; ?>

        .app-container { background: var(--panel-bg); border-radius: 12px; width: 100%; max-width: 650px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7); border: 1px solid rgba(255,255,255,0.04); position: relative; overflow: hidden; height: fit-content; }
        .banner { width: 100%; height: auto; display: block; border-bottom: 2px solid var(--accent); opacity: 0.95; }
        .content-wrapper { padding: 2rem; }

        /* Smooth progress bar */
        .progress-bar { position: absolute; top: 0; left: 0; height: 2px; background-color: var(--accent); width: 100%; z-index: 10; }

        .header { text-align: center; margin-bottom: 2rem; }
        .header h1 { margin: 0 0 0.25rem 0; font-size: 1.5rem; color: #f8fafc; }
        .header p  { color: var(--text-muted); margin: 0; font-size: 0.9rem; }

        .status-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; margin-top: 15px; letter-spacing: 1px; }
        .status-online  { color: #a7f3d0; background-color: rgba(6,78,59,0.4);    border: 1px solid rgba(16,185,129,0.2); }
        .status-offline { color: #fca5a5; background-color: rgba(127,29,29,0.4); border: 1px solid rgba(239,68,68,0.2); }
        .pulse-dot { width: 6px; height: 6px; background-color: #10b981; border-radius: 50%; margin-right: 8px; animation: pulse 2s infinite; }
        .status-offline .pulse-dot { background-color: #ef4444; animation: none; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.7); } 70% { box-shadow: 0 0 0 6px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }

        .server-stats { display: flex; justify-content: space-around; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid rgba(255,255,255,0.03); }
        .stat-item  { display: flex; flex-direction: column; align-items: center; }
        .stat-label { font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .stat-value { font-size: 1rem; font-weight: 700; color: var(--text-main); font-variant-numeric: tabular-nums; }

        .btn-primary { display: block; padding: 12px; background-color: var(--accent); color: #000; text-align: center; text-decoration: none; border-radius: 6px; font-weight: 800; text-transform: uppercase; font-size: 0.85rem; transition: filter 0.2s; margin-bottom: 0.5rem; }
        .btn-primary:hover { filter: brightness(1.1); }
        .help-text { text-align: center; font-size: 0.8rem; color: var(--text-muted); margin: 0.5rem 0 2rem 0; }
        .help-text a { color: var(--text-main); text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.2); transition: border-color 0.2s; }
        .help-text a:hover { border-color: var(--accent); color: var(--accent); }

        .channel-list { display: flex; flex-direction: column; gap: 6px; }
        .channel { border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.03); background-color: var(--channel-bg); }
        .channel-header { padding: 8px 14px; display: flex; justify-content: space-between; align-items: center; color: #f1f5f9; background-size: cover; background-position: center; background-repeat: no-repeat; }
        .channel-title-group { display: flex; align-items: center; margin: 0; font-size: 0.9rem; font-weight: 600; }
        .channel-title-group h2 { font-size: 1rem; margin: 0; font-weight: 600; }
        .channel-icon { width: 20px; height: 20px; margin-right: 10px; border-radius: 4px; object-fit: contain; }
        .channel-meta { font-size: 0.8rem; color: rgba(255,255,255,0.7); }

        .client-list { list-style: none; padding: 4px 0 8px 0; margin: 0; }
        .client { padding: 4px 15px 4px 38px; font-size: 0.85rem; color: #cbd5e1; display: flex; align-items: center; font-weight: 500; }
        .role-icon { width: 16px; height: 16px; object-fit: contain; margin-right: 10px; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.5)); }
        .client-no-role-icon { display: inline-block; width: 8px; height: 8px; border: 2px solid #475569; border-radius: 50%; margin-right: 10px; }

        .empty-server { text-align: center; color: var(--text-muted); padding: 3rem 0; font-size: 0.9rem; }

        .footer-links { text-align: center; padding-top: 1.5rem; margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); }
        .footer-links a { color: var(--text-muted); text-decoration: none; font-size: 0.85rem; margin: 0 10px; transition: color 0.2s; }
        .footer-links a:hover { color: var(--text-main); }

        .credits { text-align: center; margin-top: 1rem; font-size: 0.75rem; color: #475569; }
        .credits a { color: var(--text-muted); font-weight: 600; text-decoration: none; transition: color 0.2s; }
        .credits a:hover { color: var(--accent); }

        #ajax-container { transition: opacity 0.3s ease-in-out; }

        /* Styles for centered spacer channels */
        .channel-spacer .channel-header { justify-content: center; text-align: center; }
        .channel-spacer .channel-title-group { justify-content: center; width: 100%; }
        
    </style>
</head>
<body>

<?php if ($config['ui']['activer_etoiles'] ?? false): ?>
<div id="space-bg" aria-hidden="true">
    <div class="star-layer stars-s"></div>
    <div class="star-layer stars-m"></div>
    <div class="star-layer stars-l"></div>
</div>
<?php endif; ?>

<main class="app-container">
    <?php if ($is_online): ?><div class="progress-bar" id="refresh-bar"></div><?php endif; ?>

    <?php if (!empty($config['ui']['banniere_url'])): ?>
        <img src="<?php echo safeUrl($config['ui']['banniere_url']); ?>" alt="Header Banner" class="banner">
    <?php endif; ?>

    <div class="content-wrapper">
        <header class="header">
            <h1><?php echo e($config['ui']['titre']); ?></h1>
            <p><?php echo e($config['ui']['sous_titre']); ?></p>
            <?php if ($is_online): ?>
                <div class="status-badge status-online"><span class="pulse-dot"></span> ACTIVE TRANSMISSION</div>
            <?php else: ?>
                <div class="status-badge status-offline"><span class="pulse-dot"></span> SIGNAL LOST</div>
            <?php endif; ?>
        </header>

        <a href="ts3server://<?php echo e($config['server']['ts_domain']); ?>" class="btn-primary"><?php echo e($config['boutons']['rejoindre']); ?></a>
        <p class="help-text"><?php echo e($config['boutons']['telecharger_texte']); ?> <a href="<?php echo e($config['boutons']['telecharger_url']); ?>" target="_blank" rel="noopener"><?php echo e($config['boutons']['telecharger_lien']); ?></a></p>

        <section id="ajax-container" aria-live="polite">
            <?php echo $html_fragment; ?>
        </section>

        <?php if (!empty($config['liens_externes'])): ?>
        <nav class="footer-links">
            <?php foreach ($config['liens_externes'] as $name => $url): ?>
                <a href="<?php echo e($url); ?>" target="_blank" rel="noopener"><?php echo e($name); ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <footer class="credits">
            <?php echo e($config['footer']['texte']); ?> <a href="<?php echo e($config['footer']['lien_auteur']); ?>" target="_blank" rel="noopener"><?php echo e($config['footer']['auteur']); ?></a> — Radar scan: <span id="timer-text"><?php echo $config['server']['cache_time']; ?></span>s
        </footer>
    </div>
</main>

<script>
<?php if ($is_online): ?>
document.addEventListener("DOMContentLoaded", () => {
    let timeLeft  = <?php echo (int)$config['server']['cache_time']; ?>;
    const totalTime   = <?php echo (int)$config['server']['cache_time']; ?>;
    const progressBar = document.getElementById('refresh-bar');
    const timerText   = document.getElementById('timer-text');
    const container   = document.getElementById('ajax-container');

    // Smooth progress bar animation
    function resetProgressBar() {
        progressBar.style.transition = 'none';
        progressBar.style.width = '100%';
        void progressBar.offsetWidth;
        progressBar.style.transition = `width ${totalTime}s linear`;
        progressBar.style.width = '0%';
    }
    resetProgressBar();

    setInterval(() => {
        timeLeft--;
        timerText.innerText = timeLeft;

        if (timeLeft <= 0) {
            container.style.opacity = '0.5';
            fetch(window.location.href.split('?')[0] + '?ajax=1', { cache: 'no-store', headers: {'X-Requested-With': 'XMLHttpRequest'} })
                .then(r => {
                    if (!r.ok) throw new Error("HTTP error " + r.status);
                    return r.text();
                })
                .then(html => {
                    container.innerHTML = html;
                    container.style.opacity = '1';
                    timeLeft = totalTime;
                    timerText.innerText = timeLeft;
                    resetProgressBar();
                })
                .catch(err => {
                    console.error("Refresh error:", err);
                    container.style.opacity = '1';
                    timeLeft = totalTime;
                    resetProgressBar();
                });
        }
    }, 1000);
});
<?php endif; ?>
</script>
</body>
</html>
