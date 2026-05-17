(translate with claude.ai)
# 🚀 TeamSpeak 6 Web Explorer (WebQuery)

A lightweight, modern, 100% AJAX TeamSpeak server viewer, designed specifically for the new TeamSpeak 6 API (WebQuery).

It allows you to display channels and players on your website in real time. No database required. The script uses a caching system to protect your TeamSpeak server against spam.

---

## 📸 Screenshots <https://i.imgur.com/ie9SaXu.jpeg>

---

## ✨ Features

- **Smooth Refresh:** The page updates in real time without ever flickering.
- **Respects Your Server:** Displays channels and sub-channels in exactly the same order as in your TeamSpeak client.
- **Role System:** Displays your group icons (Admins, VIP, etc.) next to usernames.
- **Zero Risk:** Works with a read-only API key. No risk of your voice server being compromised.
- **Diagnostic Tool:** A `debug.php` script included to help you if the connection fails.

---

## 🛠️ Requirements

To run this script, you need:

1. **A web host** (or a local server such as Unraid/Docker) supporting **PHP 7.4 or higher**.
2. An active **TeamSpeak 6** server.
3. The **WebQuery** port of your TeamSpeak accessible (default port `10080`).

---

## 📦 Beginner Installation Guide

### Step 1: Download the files

1. At the top of this GitHub page, click the green **"Code"** button, then **"Download ZIP"**.
2. Extract the `.zip` file on your computer.
3. Upload all these files to your web host (via your FTP client such as FileZilla, or your host's file manager).

### Step 2: Create your configuration file

1. Go to the folder where you uploaded the files.
2. Find the file named `config.example.php`.
3. **Rename it** to `config.php`.
4. Open this new `config.php` file with a text editor (such as Notepad++, Notepad, or VS Code). You will enter your information in the next step.

---

## 🔑 Getting Your TeamSpeak 6 API Key

For the website to be able to read the player list, it needs an "API Key". For security reasons, we will create a key that has **read-only permissions**, with no right to modify your server.

**Here is how to create it step by step:**

1. Open your computer's terminal (or use software like **PuTTY**).

2. Connect to your TeamSpeak server's IP via **SSH** on the ServerQuery port (default port: **10022**). *Example command:* `ssh serveradmin@YOUR_IP -p 10022`

3. A console opens and asks for your `serveradmin` password. Type it (it won't appear on screen — that's normal) and press *Enter*.

4. Type the following commands, pressing *Enter* after each line:

    *To select your main voice server:* `use 1`

    *To generate a permanent read-only key:* `apikeyadd scope=read lifetime=0`

5. The server will respond with a line containing `apikey=YOUR_KEY_HERE`. Copy this long string of characters.

6. Type `quit` to close the console.

### Finalizing

Go back to your `config.php` file opened previously, make sure the API port is set to **10080**, and paste your key here: `'api_key' => 'YOUR_KEY_HERE',` Save the file. You're all set! 🎉

---

## 🛡️ Security Tip

If you are using an advanced host or a VPS (with Nginx), it is strongly recommended to block direct access to system files to prevent unwanted users from reading your configuration.

Add this to your site's configuration:

```
location ~* /(config\.php|cache\.json|debug\.php)$ {
    deny all;
    return 403;
}
```
