# 🚀 Explorateur Web TeamSpeak 6 (WebQuery)

Un visualiseur de serveur TeamSpeak léger, moderne et 100% AJAX, conçu spécifiquement pour la nouvelle API (WebQuery) de TeamSpeak 6. 

Il permet d'afficher en temps réel les salons et les joueurs sur votre site web. Aucune base de données n'est requise. Le script utilise un système de cache pour protéger votre serveur TeamSpeak contre le spam.

---

##📸 Screenshots
https://i.imgur.com/ie9SaXu.jpeg

---

## ✨ Fonctionnalités
* **Actualisation Fluide :** La page se met à jour en temps réel sans jamais clignoter.
* **Respect de votre Serveur :** Affiche les salons et sous-canaux exactement dans le même ordre que sur votre TeamSpeak.
* **Système de Rôles :** Affiche les icônes de vos groupes (Admins, VIP, etc.) à côté des pseudos.
* **Zéro Risque :** Fonctionne avec une clé API en lecture seule. Aucun risque de piratage de votre serveur vocal.
* **Outil de Diagnostic :** Un script `debug.php` inclus pour vous aider si la connexion échoue.

---

## 🛠️ Prérequis
Pour faire fonctionner ce script, vous avez besoin de :
1. **Un hébergement web** (ou un serveur local type Unraid/Docker) supportant **PHP 7.4 ou supérieur**.
2. Un serveur **TeamSpeak 6** actif.
3. Le port **WebQuery** de votre TeamSpeak accessible (par défaut le port `10080`).

---

## 📦 Guide d'Installation pour Débutants

### Étape 1 : Télécharger les fichiers
1. En haut de cette page GitHub, cliquez sur le bouton vert **"Code"**, puis sur **"Download ZIP"**.
2. Décompressez le fichier `.zip` sur votre ordinateur.
3. Envoyez tous ces fichiers sur votre hébergement web (via votre logiciel FTP comme FileZilla, ou le gestionnaire de fichiers de votre hébergeur).

### Étape 2 : Créer votre fichier de configuration
1. Allez dans le dossier où vous avez envoyé les fichiers.
2. Cherchez le fichier nommé `config.example.php`.
3. **Renommez-le** simplement en `config.php`.
4. Ouvrez ce nouveau fichier `config.php` avec un éditeur de texte (comme Notepad++, Bloc-notes ou VS Code). Vous y entrerez vos informations à l'étape suivante.

---

## 🔑 Obtenir votre Clé API TeamSpeak 6

Pour que le site web puisse lire la liste des joueurs, il lui faut une "Clé API". Pour des raisons de sécurité, nous allons créer une clé qui a **uniquement le droit de lire**, et aucun droit de modifier votre serveur.

**Voici comment la créer pas à pas :**
1. Ouvrez le terminal de votre ordinateur (ou utilisez un logiciel comme **PuTTY**).
2. Connectez-vous à l'IP de votre serveur TeamSpeak en mode **SSH** sur le port ServerQuery (port par défaut : **10022**).
   *Exemple de commande :* `ssh serveradmin@VOTRE_IP -p 10022`
3. Une console s'ouvre et vous demande votre mot de passe `serveradmin`. Tapez-le (il ne s'affiche pas à l'écran, c'est normal) et appuyez sur *Entrée*.
4. Tapez les commandes suivantes, en appuyant sur la touche *Entrée* après chaque ligne :
   
   *Pour sélectionner votre serveur vocal principal :*
   `use 1`
   
   *Pour générer une clé en lecture seule permanente :*
   `apikeyadd scope=read lifetime=0`

5. Le serveur va vous répondre avec une ligne contenant `apikey=VOTRE_CLE_ICI`. Copiez cette longue suite de caractères.
6. Tapez `quit` pour fermer la console.

### Finalisation
Retournez dans votre fichier `config.php` ouvert précédemment, assurez-vous que le port API est bien configuré sur **10080**, et collez votre clé à cet endroit :
`'api_key' => 'VOTRE_CLE_ICI',`
Enregistrez le fichier. C'est prêt, votre site fonctionne ! 🎉

---

## 🛡️ Conseil de Sécurité
Si vous utilisez un hébergement avancé ou un VPS (avec Nginx), il est fortement recommandé de bloquer l'accès direct aux fichiers système pour empêcher les curieux de lire votre configuration.

Ajoutez ceci dans la configuration de votre site :
```nginx
location ~* /(config\.php|cache\.json|debug\.php)$ {
    deny all;
    return 403;
}
