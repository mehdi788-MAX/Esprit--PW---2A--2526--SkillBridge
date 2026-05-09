<div align="center">
  <img src="view/frontoffice/EasyFolio/assets/img/skillbridge-logo.png" alt="SkillBridge" height="80">

  # SkillBridge

  **La marketplace freelance qui connecte les bons talents avec les bons projets.**

  ![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
  ![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
  ![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
  ![License](https://img.shields.io/badge/license-Academic-1F5F4D)
  ![Status](https://img.shields.io/badge/status-MVP-F5C842)

</div>

---

## Sommaire

- [À propos](#à-propos)
- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Architecture du projet](#architecture-du-projet)
- [Installation](#installation)
- [URLs principales](#urls-principales)
- [Comptes de démo](#comptes-de-démo)
- [Design system](#design-system)
- [Équipe](#équipe)
- [Licence](#licence)

---

## À propos

**SkillBridge** est une plateforme freelance qui met en relation des **clients** à la recherche de prestataires et des **freelancers** souhaitant proposer leurs compétences. Pensée et développée à l'**ESPRIT — École Supérieure Privée d'Ingénierie et de Technologies** (Tunis, Tunisie), elle a pour vocation de fournir aux indépendants tunisiens un espace de collaboration moderne, sécurisé et 100 % en ligne.

Le projet a été réalisé dans le cadre du module **Programmation Web — 2ème année (2025/2026)** et couvre l'ensemble des fonctionnalités attendues d'une marketplace : authentification multi-modes, gestion des profils, messagerie temps réel, modération admin et bien plus.

---

## Fonctionnalités

### Pour les clients & freelancers (Frontoffice)

- 🔐 **Authentification multi-modes** — email/mot de passe, OAuth (Google, GitHub, Discord) ou reconnaissance faciale (face-api.js)
- ✉️ **Vérification d'email** par lien unique (PHPMailer)
- 👤 **Profils détaillés** — bio, compétences, localisation, site web, photo
- 💬 **Messagerie temps réel** — messages texte, images, fichiers (jusqu'à 10 Mo), réactions emoji, accusés de lecture, indicateur de saisie
- 🔔 **Notifications instantanées** — cloche avec badge, panneau déroulant, toasts et notifications natives du navigateur
- 🌐 **Synchronisation multi-onglets** via `BroadcastChannel` — un onglet « marqué lu » se reflète dans les autres
- 🎨 **Design system unifié** sur toutes les pages — accueil, connexion, inscription, profil, OAuth role-picker, conversations

### Pour les administrateurs (Backoffice)

- 📊 **Dashboard** — KPIs admin-actionnables (pulse aujourd'hui / 7 jours, santé du parc), graphiques 14 jours, fil d'activité récent
- 👥 **Gestion utilisateurs** — liste, profils complets joints, recherche par rôle, édition, activation/désactivation, suppression
- 💬 **Modération chat** — vue d'ensemble des conversations, lecture seule des fils, suppression de messages problématiques
- 🔒 **Espace séparé** — login admin dédié, garde-fous serveur, jamais de redirection vers le frontoffice

---

## Stack technique

| Couche | Technologies |
|---|---|
| **Backend** | PHP 8 (POO + MVC manuel), PDO |
| **Base de données** | MySQL 8 (XAMPP) avec fallback automatique vers SQLite pour le dev local |
| **Frontend frontoffice** | HTML5, CSS3 (Manrope + Bootstrap Icons), Bootstrap 5, AOS |
| **Frontend backoffice** | Sage + Honey design system maison, Bootstrap 5, DataTables, Chart.js 4 |
| **Real-time** | Polling singleton (`ChatBus`) sur `api/chat.php?action=poll` avec throttling Page Visibility / idle |
| **Auth** | Bcrypt, OAuth 2.0 (Google / GitHub / Discord), face-api.js (descripteurs faciaux) |
| **Email** | PHPMailer (vérification, reset password) |
| **DevOps** | Compatible XAMPP / MAMP / `php -S` |

> **Aucun framework côté backend** — tout est en PHP vanilla pour rester pédagogique et auditable. Aucune dépendance Composer non plus : la stack JS arrive via CDN.

---

## Architecture du projet

```
SkillBridge/
├── api/                    # Endpoints JSON (chat polling, uploads)
├── config/                 # Configuration OAuth (.env-driven)
├── controller/             # Contrôleurs (UtilisateurController, ChatController, OAuthController)
├── model/                  # Modèles PDO (Utilisateur, Profil, Conversation, Message, Notification)
├── lib/                    # Outils internes (Moderator anti-bad-words, etc.)
├── libs/PHPMailer/         # PHPMailer vendoré
├── database/
│   └── skillbridge.sql     # Dump complet — utilisateurs, profils, conversations, messages, notifications, réactions
├── uploads/
│   └── chat/               # Pièces jointes (images, fichiers)
├── view/
│   ├── frontoffice/
│   │   ├── EasyFolio/      # Pages publiques (index, login, register, profil, oauth_role)
│   │   └── chat/           # Conversations, chat thread, nouvelle conversation, édition
│   ├── backoffice/
│   │   ├── _partials/      # Header (sidebar + topbar) + footer partagés
│   │   ├── dashbord.php    # Dashboard admin
│   │   ├── login.php       # Connexion admin (séparée du frontoffice)
│   │   ├── users_list.php  # Liste utilisateurs avec actions
│   │   ├── users_profils.php # Vue jointe utilisateurs ⟶ profils
│   │   ├── search_utilisateurs.php # Recherche par rôle
│   │   ├── edit_user.php   # Édition utilisateur
│   │   └── chat/           # Modération chat (conversations, messages, recherche)
│   └── shared/
│       └── chatbus.js      # Singleton de polling + bell + toasts + cross-tab sync
├── config.php              # Helpers d'URL (base_url, frontoffice_url, backoffice_url, …) + connexion BDD
└── README.md               # Ce fichier
```

### Helpers d'URL

Le fichier `config.php` expose des helpers portables qui détectent automatiquement la racine du projet — vous pouvez déployer SkillBridge dans n'importe quel sous-dossier d'XAMPP sans modifier le code :

```php
base_url();           // → http://localhost/<projet>
frontoffice_url();    // → http://localhost/<projet>/view/frontoffice/EasyFolio
frontoffice_url('chat'); // → http://localhost/<projet>/view/frontoffice/chat
backoffice_url();     // → http://localhost/<projet>/view/backoffice
controller_url();     // → http://localhost/<projet>/controller
api_url();            // → http://localhost/<projet>/api
uploads_url();        // → http://localhost/<projet>/uploads
```

---

## Installation

### Prérequis

- **XAMPP** (Apache + MySQL + PHP ≥ 8.0) ou tout serveur web PHP équivalent
- (Optionnel) Un compte Google / GitHub / Discord developer pour activer l'OAuth

### Étapes

1. **Cloner le projet**
   ```bash
   git clone https://github.com/mehdi788-MAX/Esprit--PW---2A20--2026--SkillBridge.git
   cd Esprit--PW---2A20--2026--SkillBridge
   ```

2. **Placer le projet dans `htdocs/`** (XAMPP) ou démarrer un serveur dédié :
   ```bash
   php -S localhost:8000
   ```

3. **Importer la base de données**

   Démarrer MySQL via le panneau XAMPP, puis dans phpMyAdmin :
   - Créer une base `skillbridge`
   - Importer `database/skillbridge.sql`

   > 💡 **Astuce dev** : si MySQL n'est pas dispo, `config.php` bascule automatiquement sur SQLite (fichier créé sous `database/skillbridge.sqlite` au premier run, avec données de seed).

4. **Configurer les credentials OAuth** (optionnel, mais recommandé)

   Créer un fichier `.env` à la racine :
   ```env
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   GITHUB_CLIENT_ID=...
   GITHUB_CLIENT_SECRET=...
   DISCORD_CLIENT_ID=...
   DISCORD_CLIENT_SECRET=...
   ```

   Sans `.env`, les boutons OAuth restent simplement masqués — le reste de l'app fonctionne.

5. **Configurer SMTP pour les emails** (optionnel)

   Dans `controller/email_helper.php`, renseignez les identifiants SMTP (Gmail App Password recommandé pour le dev).

6. **Lancer !**
   - Frontoffice : [http://localhost/Esprit-PW.../view/frontoffice/EasyFolio/index.php](http://localhost/Esprit-PW.../view/frontoffice/EasyFolio/index.php)
   - Backoffice : [http://localhost/Esprit-PW.../view/backoffice/login.php](http://localhost/Esprit-PW.../view/backoffice/login.php)

---

## URLs principales

### Frontoffice (zone publique)

| Page | URL |
|---|---|
| Accueil | `view/frontoffice/EasyFolio/index.php` |
| Connexion | `view/frontoffice/EasyFolio/login.php` |
| Inscription | `view/frontoffice/EasyFolio/register.php` |
| OAuth — choix du rôle | `view/frontoffice/EasyFolio/oauth_role.php` |
| Mon profil | `view/frontoffice/EasyFolio/profil.php` |
| Mes conversations | `view/frontoffice/chat/conversations.php` |
| Nouveau chat | `view/frontoffice/chat/new_conversation.php` |

### Backoffice (admin)

| Page | URL |
|---|---|
| Connexion admin | `view/backoffice/login.php` |
| Dashboard | `view/backoffice/dashbord.php` |
| Liste utilisateurs | `view/backoffice/users_list.php` |
| Profils complets | `view/backoffice/users_profils.php` |
| Recherche par rôle | `view/backoffice/search_utilisateurs.php` |
| Modération conversations | `view/backoffice/chat/conversations.php` |
| Tous les messages | `view/backoffice/chat/messages.php` |
| Recherche messages | `view/backoffice/chat/searchMessages.php` |

---

## Comptes de démo

Le dump SQL initialise trois comptes de test :

| Rôle | Email | Mot de passe |
|---|---|---|
| **Admin** | `admin@skillbridge.com` | `password` |
| **Freelancer** | `freelancer@test.com` | `password` |
| **Client** | `client@test.com` | `password` |

---

## Design system

SkillBridge utilise un design system maison baptisé **Sage & Honey**, taillé pour un univers freelance moderne et chaleureux.

### Palette

| Couleur | Hex | Usage |
|---|---|---|
| 🟢 Sage | `#1F5F4D` | Action principale, accent éditorial, éléments de confiance |
| 🟡 Honey | `#F5C842` | Pop d'attention, CTA secondaire, badges de mise en valeur |
| ⚫ Ink | `#0F0F0F` | Texte principal, CTA dark, footers |
| 🟤 Cream | `#F7F4ED` | Fond paper, sections atmosphériques |
| 🟫 Rule | `#E8E2D5` | Bordures, séparateurs |

### Typographie

- **Manrope** (400 → 800) — toute l'interface, du body aux display headlines
- Italiques utilisées pour les **mots-accents** dans les titres : *« Trouvez le talent **parfait** »*

### Composants partagés

- `auth-card` — cartes blanches arrondies (24px) avec ombre douce
- `eyebrow` — pills sage (ou honey) au-dessus des titres de section
- `kpi` — cartes statistiques denses (icône, label uppercase, gros chiffre, sous-titre contextuel)
- `ad-table` — tables sand-bordered, hover cream, badges colorés selon le rôle
- `chatbus.js` — composant unique pour la cloche, les toasts, le polling et la sync multi-onglets

---

## Équipe

Projet réalisé en groupe dans le cadre du module **Programmation Web — 2ème année** à **ESPRIT** :

| Contributeur | Module |
|---|---|
| [@mehdi788-MAX](https://github.com/mehdi788-MAX) | Gestion Utilisateurs · authentification & OAuth · refonte UI/UX & design system Sage & Honey · intégration backoffice |
| [@oussemahedhli](https://github.com/oussemahedhli) | Gestion Chat · messagerie temps réel · notifications · réactions emoji |
| [@essil92](https://github.com/essil92) | Modules complémentaires (demandes / propositions) |

> **Encadrement académique** : ESPRIT — École Supérieure Privée d'Ingénierie et de Technologies, Z.I. Charguia 2, 2035 Ariana, Tunisie.

---

## Licence

Projet pédagogique — utilisation académique uniquement.
Le code est livré tel quel, sans garantie. Les dépendances tierces conservent leurs licences respectives (PHPMailer, Bootstrap, face-api.js, Chart.js, DataTables…).

---

<div align="center">
  <sub>Made with 💚 at <strong>ESPRIT</strong> — Tunis, Tunisie · Année universitaire 2025/2026</sub>
</div>
