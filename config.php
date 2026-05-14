<?php
/**
 * Configuration de l'Explorateur TeamSpeak
 * Renommez ce fichier en 'config.php' et remplissez vos informations.
 */

return [
    // Paramètres de connexion au serveur
    'server' => [
        'host'       => '127.0.0.1', // IP du serveur (ex: 192.168.1.50)
        'port'       => '10080',     // Port HTTP WebQuery (TS6)
        'api_key'    => 'VOTRE_CLE_API_ICI',
        'server_id'  => '1',
        'ts_domain'  => 'ts.votre-domaine.com', // Utilisé pour le bouton de connexion
        'cache_time' => 30,          // Actualisation en secondes (anti-spam)
        'cache_file' => __DIR__ . '/cache.json'
    ],

    // Design et Textes principaux
    'ui' => [
        'titre'           => 'Mon Serveur TeamSpeak',
        'sous_titre'      => 'Bienvenue sur notre espace de discussion.',
        'couleur_fond'    => '#070a10', // Couleur de fond principale
        'couleur_panneau' => '#131823', // Couleur des blocs de canaux
        'couleur_accent'  => '#3b82f6', // Couleur des boutons et de la barre de temps
        'couleur_texte'   => '#e2e8f0', 
        'banniere_url'    => '',        // Laissez vide ('') pour ne pas afficher d'en-tête
        'activer_etoiles' => false,     // Passez à true pour activer une animation spatiale en arrière-plan
    ],

    // Textes et liens de l'interface
    'boutons' => [
        'rejoindre'         => 'Se connecter au serveur',
        'telecharger_texte' => 'Le logiciel n\'est pas installé sur votre PC ?',
        'telecharger_lien'  => 'Télécharger TeamSpeak',
        'telecharger_url'   => 'https://www.teamspeak.com/fr/downloads/' // Lien officiel au cas où il change
    ],

    // Liens utiles (Ajoutez ou retirez autant de lignes que vous le souhaitez)
    'liens_externes' => [
        'Notre Site Web' => 'https://www.votre-site.com',
        'Discord / Forum'=> 'https://discord.gg/votre-lien'
    ],

    // Pied de page
    'footer' => [
        'texte'       => 'Hébergé par',
        'auteur'      => 'VotrePseudo',
        'lien_auteur' => 'https://github.com/votre-pseudo'
    ],

    // Personnalisation des canaux (Nom exact du canal => nom du fichier image)
    'bannieres_canaux' => [
        // 'Accueil' => 'accueil_fond.png',
        // 'Général' => 'general_fond.png',
    ],
    
    'icones_canaux' => [
        // 'Accueil' => 'accueil_icone.png',
        // 'Général' => 'general_icone.png',
    ],
    
    'images_generiques' => [
        'fond'  => '', // Image appliquée par défaut aux canaux inconnus
        'icone' => ''  // Icône appliquée par défaut aux canaux inconnus
    ],

    // Groupes de serveur (Rôles) : 'ID_DU_GROUPE' => 'Image'
    'icones_roles' => [
        // '6' => 'admin.png',
        // '9' => 'vip.png',
    ]
];