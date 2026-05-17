<?php

/**
 * (translate with claude.ai)
 * TeamSpeak Explorer Configuration
 * Rename this file to 'config.php' and fill in your details.
 */

return [

    // Server connection settings
    'server' => [
        'host'       => '127.0.0.1', // Server IP (e.g. 192.168.1.50)
        'port'       => '10080',      // HTTP WebQuery port (TS6)
        'api_key'    => 'YOUR_API_KEY_HERE',
        'server_id'  => '1',
        'ts_domain'  => 'ts.your-domain.com', // Used for the connect button
        'cache_time' => 30,                    // Refresh interval in seconds (anti-spam)
        'cache_file' => __DIR__ . '/cache.json'
    ],

    // Design and main UI text
    'ui' => [
        'titre'           => 'My TeamSpeak Server',
        'sous_titre'      => 'Welcome to our community voice server.',
        'couleur_fond'    => '#070a10', // Main background color
        'couleur_panneau' => '#131823', // Channel block background color
        'couleur_accent'  => '#3b82f6', // Button and progress bar color
        'couleur_texte'   => '#e2e8f0',
        'banniere_url'    => '',        // Leave empty ('') to hide the header banner
        'activer_etoiles' => false,     // Set to true to enable a space-themed background animation
    ],

    // Button and UI text
    'boutons' => [
        'rejoindre'         => 'Connect to the server',
        'telecharger_texte' => 'Don\'t have the app installed?',
        'telecharger_lien'  => 'Download TeamSpeak',
        'telecharger_url'   => 'https://www.teamspeak.com/en/downloads/' // Official link in case it changes
    ],

    // External links (add or remove as many lines as you like)
    'liens_externes' => [
        'Our Website' => 'https://www.your-site.com',
        'Discord / Forum' => 'https://discord.gg/your-link'
    ],

    // Footer
    'footer' => [
        'texte'       => 'Hosted by',
        'auteur'      => 'YourUsername',
        'lien_auteur' => 'https://github.com/your-username'
    ],

    // Channel customization (exact channel name => image filename)
    'bannieres_canaux' => [
        // 'Lobby'   => 'lobby_bg.png',
        // 'General' => 'general_bg.png',
    ],

    'icones_canaux' => [
        // 'Lobby'   => 'lobby_icon.png',
        // 'General' => 'general_icon.png',
    ],

    'images_generiques' => [
        'fond'  => '', // Default background image applied to unknown channels
        'icone' => ''  // Default icon applied to unknown channels
    ],

    // Server groups (Roles): 'GROUP_ID' => 'image_file'
    'icones_roles' => [
        // '6' => 'admin.png',
        // '9' => 'vip.png',
    ]

];
