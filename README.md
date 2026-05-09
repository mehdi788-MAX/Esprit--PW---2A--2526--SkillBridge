<div align="center">
  <img src="view/frontoffice/EasyFolio/assets/img/skillbridge-logo.png" alt="SkillBridge" height="80">

  # SkillBridge

  **Marketplace freelance · Tunisie**

  *Publiez votre demande · Recevez des propositions ciblées · Collaborez en messagerie temps réel — tout au même endroit.*

  ![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
  ![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
  ![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
  ![Ollama](https://img.shields.io/badge/AI-Ollama%20local-F5C842?logoColor=0F0F0F)
  ![License](https://img.shields.io/badge/license-Academic-1F5F4D)
  ![Status](https://img.shields.io/badge/status-Production%20Ready-1F5F4D)

</div>

---

## Sommaire

1. [À propos](#à-propos)
2. [Fonctionnalités](#fonctionnalités)
3. [Stack technique](#stack-technique)
4. [Architecture du projet](#architecture-du-projet)
5. [Installation](#installation)
6. [Mise en route de l'IA (optionnel)](#mise-en-route-de-lia-optionnel)
7. [URLs principales](#urls-principales)
8. [Comptes de démo](#comptes-de-démo)
9. [Design system](#design-system)
10. [Mettre à jour le projet](#mettre-à-jour-le-projet)
11. [Équipe](#équipe)
12. [Licence](#licence)

---

## À propos

**SkillBridge** est une plateforme web qui connecte clients et freelancers vérifiés en Tunisie. Le client publie une **demande** (titre, budget, échéance, brief), reçoit des **propositions** ciblées, en accepte une — et la conversation s'ouvre automatiquement avec le freelancer retenu.

Le tout est augmenté par un **assistant IA local** qui aide :
- côté **client** à formuler un brief solide (synthèse, verdict prix vs benchmark, verdict délai, suggestions),
- côté **freelancer** à rédiger une proposition convaincante (score de compatibilité, conseils pitch, verdicts dataset).

Projet académique (Esprit School of Engineering · 2A · semestre PW) bâti **sans framework** en PHP / MySQL / Bootstrap, avec un design system maison sage & honey.

---

## Fonctionnalités

### Pour les clients & freelancers (Frontoffice)

- 🔐 **Auth complète** : email + mot de passe, OAuth (Google · GitHub · Discord), reconnaissance faciale
- 👤 **Profil enrichi** : photo, bio, compétences, localisation, site web, complétion en %
- 💬 **Messagerie temps réel** : polling intelligent, indicateurs de saisie, accusés de réception, réactions emoji, upload fichiers/photos, notifications de bureau
- 📝 **Demandes & propositions** : CRUD complet, modération, filtre par statut
- ✅ **Cycle de vie des propositions** : un clic « Accepter » ferme la demande, refuse les autres propositions, ouvre la conversation, dispatche les notifications
- 🧠 **Assistant IA (Ollama local)** :
  - sur la création de demande : synthèse du brief, verdict prix/délai vs benchmark, suggestions
  - sur la création de proposition : score de compatibilité freelancer ↔ demande, conseils pitch
  - sur la liste des demandes (freelancer) : section « Recommandées pour vous » avec top 3 alignés sur le profil
- 🔔 **Cloche de notifications** : toasts in-app, sons, notifications natives, persistance cross-onglets

### Pour les administrateurs (Backoffice)

- 📊 **Dashboard KPI** : signups du jour / 7 derniers jours, indicateurs de santé (comptes incomplets, inactifs, non vérifiés)
- 👥 **Gestion utilisateurs** : liste DataTable, profils complets, recherche par rôle, édition
- 💬 **Modération chat** : conversations, messages, recherche full-text, vue détaillée avec réactions
- 📋 **Modération marketplace** : liste demandes & propositions avec filtres et suppression
- 🎨 **UI cohérente** : sidebar sage, topbar cream-blur, palette unifiée avec le frontoffice

---

## Stack technique

| Domaine | Technologie |
|---|---|
| **Backend** | PHP 8.x — pattern MVC manuel, sans framework, sans Composer |
| **Base de données** | MySQL 8.x (XAMPP / phpMyAdmin) — fallback SQLite local pour le dev |
| **Auth** | Sessions natives, OAuth 2.0, FaceAPI.js (vérification faciale) |
| **Frontend** | Bootstrap 5, Bootstrap Icons, AOS (animations on scroll), DataTables (admin) |
| **Temps réel** | Polling adaptatif (chatbus.js singleton), BroadcastChannel API cross-onglets |
| **IA** | [Ollama](https://ollama.com) local + modèle `qwen3:0.6b` (~530 MB) + dataset CSV de benchmarking (100 lignes) |
| **Typographie** | Manrope (400 → 800) — Google Fonts |

---

## Architecture du projet

```
SkillBridge/
├── api/                     # Endpoints JSON / AJAX
│   ├── chat.php             #   chat polling + actions (send, react, edit, delete, upload)
│   └── ai_advice.php        #   assistant IA pour la création de demande
│
├── controller/              # Logique métier MVC
│   ├── DemandeController.php        # CRUD demandes/propositions + acceptProposition
│   ├── ChatController.php           # CRUD conversations/messages
│   ├── utilisateurcontroller.php    # Auth + profil + OAuth
│   └── AiRecommendationService.php  # Moteur IA (Ollama + dataset)
│
├── model/                   # Classes PDO (Demande, Proposition, Conversation,
│                            # Message, Notification, Utilisateur)
│
├── view/
│   ├── frontoffice/
│   │   ├── EasyFolio/       # Pages publiques (landing, login, profil, demandes)
│   │   └── chat/            # Module messagerie temps réel
│   ├── backoffice/          # Espace admin (dashbord, users, modération chat & marketplace)
│   └── shared/chatbus.js    # SDK temps réel (polling, BroadcastChannel, notifications)
│
├── data/
│   └── skillbridge_benchmark.csv   # Dataset prix/délai par service (100 lignes)
│
├── database/
│   ├── skillbridge.sql              # Schéma complet — à importer dans XAMPP
│   └── migrations/                   # Migrations idempotentes (cycle propositions, etc.)
│
├── lib/                     # Helpers OAuth, FaceAPI
├── uploads/                 # Photos profil + pièces jointes chat (générés)
│
├── admin/index.php          # Raccourci /admin → backoffice
├── index.php                # Raccourci / → frontoffice
├── .htaccess                # Réécritures Apache (clean URLs, cache)
├── config.php               # Helpers d'URL + connexion BDD + helpers IA + nav unifiée
├── .env                     # Secrets OAuth + config Ollama (à créer depuis .env.example)
└── README.md
```

### Helpers d'URL (config.php)

| Helper | Retour |
|---|---|
| `base_url()` | `http://localhost/skillbridge` (auto-détecté) |
| `home_url()` | `base_url() . '/'` |
| `admin_url()` | `base_url() . '/admin/'` |
| `frontoffice_url($section)` | `base_url() . '/view/frontoffice/EasyFolio'` |
| `backoffice_url()` | `base_url() . '/view/backoffice'` |
| `api_url()` | `base_url() . '/api'` |
| `uploads_url()` | `base_url() . '/uploads'` |
| `frontoffice_main_nav($activeKey, …)` | Bloc `<a>` du menu principal — ordre canonique partagé |
| `frontoffice_nav_avatar($pdo, $userId)` | `['name', 'src', 'fallback']` pour le chip de profil nav |
| `chat_message_preview($raw)` | Aperçu lisible (📷 Photo / 📎 Fichier) au lieu du JSON brut |

---

## Installation

### Prérequis

| Outil | Version conseillée | Notes |
|---|---|---|
| **XAMPP** | 8.x (Apache + MySQL) | macOS / Windows / Linux |
| **PHP** | 8.0 ou plus | inclus avec XAMPP |
| **Git** | n'importe quelle version récente | |
| **Ollama** *(optionnel)* | dernière version | uniquement pour l'assistant IA |

### Étapes

#### 1. Récupérer le code

**Si vous n'avez pas encore cloné le projet :**

```bash
# Placez-vous dans le dossier htdocs de XAMPP
cd /Applications/XAMPP/htdocs        # macOS
# cd C:\xampp\htdocs                 # Windows

git clone https://github.com/mehdi788-MAX/Esprit--PW---2A20--2026--SkillBridge.git skillbridge
cd skillbridge
```

**Si vous avez déjà cloné précédemment, mettez à jour :**

```bash
cd /Applications/XAMPP/htdocs/skillbridge

# Si vous êtes sur une autre branche, repassez sur main
git checkout main

# Récupérez les derniers commits
git pull origin main
```

> **Note** : le repo a été renommé. L'ancien `Esprit--PW---2A--2526--SkillBridge` redirige automatiquement vers le nouveau, donc les anciens clones continuent de fonctionner.

#### 2. Démarrer XAMPP

Lancez **Apache** + **MySQL** depuis le panneau XAMPP. Vérifiez ensuite :
- Apache : <http://localhost> répond
- MySQL : phpMyAdmin accessible sur <http://localhost/phpmyadmin>

#### 3. Importer la base de données

Deux options :

**Option A — via phpMyAdmin (recommandée) :**

1. Ouvrez <http://localhost/phpmyadmin>
2. Créez la base : `Nouvelle base` → nom `skillbridge` → interclassement `utf8mb4_unicode_ci`
3. Sélectionnez la base `skillbridge` → onglet **Importer**
4. Choisissez le fichier `database/skillbridge.sql` → **Exécuter**

**Option B — via la ligne de commande :**

```bash
# Créer la base
mysql -u root -e "CREATE DATABASE IF NOT EXISTS skillbridge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importer le schéma
mysql -u root skillbridge < database/skillbridge.sql
```

> ⚠️ Le script `skillbridge.sql` **supprime les tables existantes** avant de les recréer. Faites une sauvegarde si vous avez des données importantes.

#### 4. Configurer les variables d'environnement

```bash
cp .env.example .env
```

Ouvrez `.env` et renseignez les clés OAuth dont vous voulez activer le bouton (Google, GitHub, Discord). Les clés vides désactivent simplement le bouton correspondant — l'auth email/mot de passe fonctionne sans aucune clé.

Pour l'IA, gardez les valeurs par défaut sauf si vous voulez utiliser un autre modèle Ollama.

#### 5. Vérifier l'accès

Le projet est servi sous `htdocs/skillbridge`, donc accessible à :

- **Frontoffice** : <http://localhost/skillbridge/>
- **Backoffice** : <http://localhost/skillbridge/admin>

Le serveur intégré PHP fonctionne aussi (utile pour le dev) :

```bash
php -S localhost:8000 -t .
```

→ <http://localhost:8000> et <http://localhost:8000/admin>

---

## Mise en route de l'IA (optionnel)

L'assistant IA tourne **localement** via [Ollama](https://ollama.com) — aucune donnée ne quitte votre machine. Le projet **fonctionne entièrement sans Ollama** : les verdicts prix/délai sont alors calculés depuis le dataset CSV uniquement (la synthèse & les suggestions LLM sont remplacées par des fallbacks).

### Installation d'Ollama

```bash
# macOS (Homebrew)
brew install ollama

# Linux
curl -fsSL https://ollama.com/install.sh | sh

# Windows : télécharger l'installeur sur https://ollama.com/download
```

Démarrez le service :

```bash
ollama serve   # tourne en tâche de fond sur 127.0.0.1:11434
```

### Pull du modèle

```bash
ollama pull qwen3:0.6b
```

Le modèle pèse ~530 MB. Une fois pull, l'assistant est immédiatement opérationnel — rafraîchissez la page de création de demande, le panneau IA passe de « hors ligne » à « ✅ Analyse à jour ».

### Vérification

Le statut s'affiche en haut du panneau IA sur :
- `add_demande.php` / `edit_demande.php` (côté client)
- `add_proposition.php` / `edit_proposition.php` (côté freelancer — Coach IA)
- `browse_demandes.php` (recommandations pour vous)

---

## URLs principales

### Raccourcis (recommandés)

Le projet expose des URLs propres à la racine pour éviter d'avoir à taper les chemins complets `/view/...` :

| Raccourci | Redirige vers |
|---|---|
| `/skillbridge/` | `view/frontoffice/EasyFolio/index.php` |
| `/skillbridge/admin/` | `view/backoffice/login.php` *(ou dashbord si déjà connecté)* |
| `/skillbridge/login` | `view/frontoffice/EasyFolio/login.php` |
| `/skillbridge/register` | `view/frontoffice/EasyFolio/register.php` |
| `/skillbridge/profil` | `view/frontoffice/EasyFolio/profil.php` |
| `/skillbridge/chat` | `view/frontoffice/chat/conversations.php` |

> Les raccourcis `/login`, `/register`, `/profil`, `/chat` nécessitent `mod_rewrite` (activé par défaut dans XAMPP). Sous le serveur intégré PHP, seuls `/` et `/admin/` fonctionnent — les chemins canoniques `view/...` restent toujours valides.

### Frontoffice (chemins canoniques)

| Page | URL |
|---|---|
| Accueil | `view/frontoffice/EasyFolio/index.php` |
| Connexion | `view/frontoffice/EasyFolio/login.php` |
| Inscription | `view/frontoffice/EasyFolio/register.php` |
| OAuth — choix du rôle | `view/frontoffice/EasyFolio/oauth_role.php` |
| Mon profil | `view/frontoffice/EasyFolio/profil.php` |
| Mes conversations | `view/frontoffice/chat/conversations.php` |
| Nouvelle conversation | `view/frontoffice/chat/new_conversation.php` |
| **Marketplace — client** | |
| Mes demandes | `view/frontoffice/EasyFolio/mes_demandes.php` |
| Publier une demande | `view/frontoffice/EasyFolio/add_demande.php` *(panneau IA live)* |
| Modifier une demande | `view/frontoffice/EasyFolio/edit_demande.php` *(panneau IA live)* |
| Propositions reçues | `view/frontoffice/EasyFolio/demande_propositions.php` *(bouton Accepter)* |
| **Marketplace — freelancer** | |
| Parcourir les demandes | `view/frontoffice/EasyFolio/browse_demandes.php` *(recommandations IA)* |
| Faire une proposition | `view/frontoffice/EasyFolio/add_proposition.php` *(coach IA)* |
| Mes propositions | `view/frontoffice/EasyFolio/mes_propositions.php` |
| Modifier ma proposition | `view/frontoffice/EasyFolio/edit_proposition.php` *(coach IA)* |

### Backoffice (chemins canoniques)

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
| Demandes (modération) | `view/backoffice/demandes_list.php` |
| Propositions (modération) | `view/backoffice/propositions_list.php` |

---

## Comptes de démo

Le dump SQL initialise trois comptes de test :

| Rôle | Email | Mot de passe |
|---|---|---|
| 👨‍💼 **Client** | `client@skillbridge.tn` | `client123` |
| 👩‍💻 **Freelancer** | `freelancer@skillbridge.tn` | `freelancer123` |
| 🛡️ **Admin** | `admin@skillbridge.tn` | `admin123` |

> ⚠️ Mots de passe en clair pour la démo uniquement. Changez-les avant tout déploiement.

---

## Design system

### Palette

| Couleur | Hex | Usage |
|---|---|---|
| 🟢 Sage | `#1F5F4D` | Couleur principale, CTAs, accents |
| 🟢 Sage Dark | `#134438` | Hover, gradients |
| 🟢 Sage Soft | `#E8F0EC` | Fonds, badges légers |
| 🟡 Honey | `#F5C842` | Accent secondaire, badges premium, boutons hot |
| 🟡 Honey Soft | `#FBE9A0` | Fonds chauds, KPIs |
| ⚪ Cream | `#F7F4ED` | Fond global (ink-cream) |
| ⚫ Ink | `#0F0F0F` | Texte principal |

### Typographie

- **Display** : Manrope 700-800 (titres, hero)
- **Body** : Manrope 400-600 (corps, formulaires)
- **Mono** : monospace pour les badges/labels uppercase

### Composants partagés

- **`.btn-sage`** : CTA principal (sage filled)
- **`.btn-honey`** : CTA accent (honey filled)
- **`.btn-ghost`** : CTA secondaire (paper outlined)
- **`.kpi`** : carte KPI avec icône + label uppercase + valeur
- **`.demande-card`**, **`.proposition-card`**, **`.reco-card`** : cards marketplace
- **`.ai-panel`**, **`.ai-coach`** : panneaux assistant IA (statut idle/thinking/ready/offline)

---

## Mettre à jour le projet

```bash
# Si vous êtes sur une branche de travail, sauvegardez vos changements
git stash                # ou git commit avant

# Repassez sur main si nécessaire
git checkout main

# Récupérez les derniers commits
git pull origin main

# Si une nouvelle migration SQL est arrivée, importez-la depuis phpMyAdmin
# (les migrations sont rangées dans database/migrations/, idempotentes)

# Restaurez vos changements éventuels
git stash pop
```

### Migrations à appliquer après pull

Les migrations sont idempotentes (on peut les exécuter plusieurs fois sans erreur). À importer via phpMyAdmin → base `skillbridge` → onglet **Importer** :

| Fichier | Apporte |
|---|---|
| `database/migrations/2026_chat_realtime.sql` | Tables temps réel chat (réactions, typing, notifications) |
| `database/migrations/2026-05-09_add_user_id_to_propositions.sql` | FK `propositions.user_id` |
| `database/migrations/2026-05-09_proposition_lifecycle.sql` | Statuts `propositions.status` + `demandes.status` + FK acceptée |

---

## Équipe

| Rôle | Membre | GitHub |
|---|---|---|
| 👥 Gestion Utilisateurs · Auth · Profils | **Mehdi** | [@mehdi788-MAX](https://github.com/mehdi788-MAX) |
| 💬 Gestion Chat · Messagerie temps réel | **Oussema Hedhli** | [@oussemahedhli](https://github.com/oussemahedhli) |
| 📋 Gestion Demandes/Propositions · IA matching | **Essil Hamdi** | [@essil92](https://github.com/essil92) |

> **Esprit School of Engineering** — 2A · semestre PW · 2025-2026

---

## Licence

Ce projet est un travail académique. Tous droits réservés à l'équipe et à Esprit School of Engineering.

---

<div align="center">
Développé avec ❤️ à Tunis · <strong>SkillBridge</strong> · 2026
</div>
