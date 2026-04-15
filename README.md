# MyCampusClubsUDM.mu

Plateforme web universitaire de gestion des clubs étudiants développée en **PHP / MySQL**.

Ce dépôt contient **tout l’historique du projet**, depuis les premières versions jusqu’à la version la plus complète.  
Le projet a été construit progressivement par commits successifs : amélioration de l’administration, gestion des clubs, refonte d’interface, profil utilisateur, messagerie privée, newsletter, notifications, puis récupération du mot de passe par e-mail et traduction FR/EN.

---

## Présentation

**MyCampusClubsUDM.mu** est une plateforme destinée à centraliser la vie associative étudiante à l’Université des Mascareignes.

Le projet permet de :
- consulter les clubs disponibles ;
- demander une adhésion ;
- gérer les membres et leurs rôles ;
- créer et suivre des événements ;
- centraliser des notifications et messages ;
- offrir une interface d’administration des clubs.

---

## Objectifs du projet

Le projet répond à un besoin simple : éviter une gestion dispersée des clubs entre feuilles, groupes de discussion et annonces séparées.

Cette application a pour but de proposer un espace unique pour :
- la gestion des clubs ;
- la gestion des adhésions ;
- la planification des événements ;
- la communication entre utilisateurs et administration.

---

## Technologies utilisées

- **PHP**
- **MySQL**
- **HTML / CSS / JavaScript**
- **PDO**
- **PHPMailer**

---

## Fonctionnalités disponibles

### Authentification
- connexion ;
- inscription ;
- déconnexion ;
- gestion de session ;
- mot de passe oublié ;
- vérification par code envoyé par e-mail ;
- réinitialisation du mot de passe.

### Gestion des clubs
- affichage de la liste des clubs ;
- détail d’un club ;
- création d’un club ;
- modification d’un club ;
- suppression selon les droits ;
- validation / refus / désactivation par l’administrateur ;
- gestion du statut des clubs.

### Gestion des adhésions
- demande d’adhésion ;
- annulation d’une demande ;
- acceptation / refus d’une demande ;
- ajout automatique à la liste des membres après validation ;
- gestion des rôles des membres ;
- retrait d’un membre.

### Gestion des événements
- création d’événements ;
- consultation des événements ;
- calendrier ;
- gestion du statut des événements ;
- inscriptions aux événements via la base de données.

### Profil utilisateur
- pseudo ;
- photo de profil ;
- téléphone ;
- filière ;
- bio ;
- modification des informations personnelles.

### Communication
- contact administrateur ;
- messagerie privée ;
- suppression des messages par utilisateur ;
- notifications ;
- newsletter ;
- désabonnement à la newsletter.

### Internationalisation
- version française ;
- version anglaise ;
- fichiers de langue séparés.

---

## Rôles du système

Le projet gère trois rôles principaux :

- **Étudiant**
- **Responsable de club**
- **Administrateur**

Chaque rôle possède des droits spécifiques selon les pages et les actions disponibles.

---

## Organisation du dépôt

Le dépôt contient plusieurs versions du projet :

```text
MyCampusUdm/
├── MyCampusUdmClubs3
├── MyCampusUdmClubs5
├── MyCampusUdmClubs6
├── MyCampusUdmClubs7
├── MyCampusUdmClubsC
├── MyCampusUdmClubsC4
├── MyCampusUdmClubsC5
└── MyCampusUdmClubsC6
```

La version la plus récente et la plus complète est :

```text
MyCampusUdm/MyCampusUdmClubsC6
```

C’est cette version qu’il faut lancer pour tester le projet final.

---

## Structure principale de la version finale

```text
MyCampusUdmClubsC6/
├── PHPMailer/
├── config/
├── css/
├── images/
├── includes/
├── js/
├── lang/
├── mysqlrequette/
├── uploads/
├── admin.php
├── auth.php
├── calendrier.php
├── club_detail.php
├── clubs.php
├── contact_admin.php
├── dashboard.php
├── gestion_club.php
├── gestion_evenements.php
├── gestion_membres.php
├── messagerie_admin.php
├── newsletter_admin.php
├── newsletter_subscribe.php
├── notifications.php
├── profil.php
├── register.php
├── mot_de_passe_oublie.php
├── verification_code.php
├── reset_password.php
├── mail_config.php
└── mysqlrequette/mysql.sql
```

---

## Base de données

Le script SQL complet se trouve ici :

```text
MyCampusUdm/MyCampusUdmClubsC6/mysqlrequette/mysql.sql
```

Nom de la base défini dans le projet :

```text
mycampus_udm_v3
```

### Tables principales
- `utilisateurs`
- `clubs`
- `demandes_adhesion`
- `membres_club`
- `evenements`
- `inscriptions_evenement`
- `notifications`
- `newsletter_abonnes`
- `newsletter_envois`
- `galerie_club`
- `avis_evenement`
- `messages_prives`
- `password_resets`

### Statuts utilisés dans le projet
**demandes_adhesion**
- `en_attente`
- `acceptee`
- `refusee`
- `annulee`

**clubs**
- `actif`
- `inactif`
- `attente`
- `supprime`

**evenements**
- `actif`
- `annule`
- `termine`

---

## Installation du projet

### 1. Cloner le dépôt
```bash
git clone <url-du-repo>
```

### 2. Placer le projet dans le dossier du serveur local
Exemple avec XAMPP :

```text
htdocs/
```

### 3. Importer la base de données
- ouvrir **phpMyAdmin** ;
- créer une base nommée :

```text
mycampus_udm_v3
```

- importer le fichier :

```text
MyCampusUdm/MyCampusUdmClubsC6/mysqlrequette/mysql.sql
```

### 4. Vérifier la connexion MySQL
Fichier :

```text
MyCampusUdm/MyCampusUdmClubsC6/config/mysql.php
```

Valeurs actuelles :
- host : `localhost`
- database : `mycampus_udm_v3`
- user : `root`
- password : vide

Modifier ces valeurs si nécessaire selon votre environnement local.

### 5. Configurer l’envoi d’e-mail
Fichier :

```text
MyCampusUdm/MyCampusUdmClubsC6/mail_config.php
```

Il faut remplacer les identifiants par vos propres informations de test.

### 6. Lancer le projet
Exemple d’URL locale :

```text
http://localhost/MyCampusUdm/MyCampusUdmClubsC6/auth.php
```

---

## Comptes présents dans le SQL

Le script SQL insère des utilisateurs de démonstration, dont :

- `admin@udm.mu`
- `kevin@udm.mu`
- `marc@udm.mu`

Les mots de passe sont stockés sous forme **hachée**.

---

## Évolution fonctionnelle du projet

### Phase 1
- base du projet ;
- clubs ;
- administration ;
- calendrier ;
- structure générale.

### Phase 2
- correction des anomalies ;
- meilleure séparation des droits ;
- validation et suppression des clubs ;
- amélioration de l’espace administrateur.

### Phase 3
- refonte visuelle du header et du footer ;
- amélioration globale de l’interface.

### Phase 4
- profil utilisateur ;
- nouvelles colonnes utilisateur ;
- meilleure personnalisation du compte.

### Phase 5
- messagerie privée ;
- newsletter ;
- notifications ;
- suppression individuelle des messages.

### Phase 6
- mot de passe oublié ;
- envoi d’un code par e-mail ;
- vérification du code ;
- nouveau mot de passe ;
- traduction FR/EN.

---

## Fichiers de documentation interne déjà présents

Dans la version finale, le dépôt contient aussi :

- `README_MIGRATION.txt`
- `README_REFACTOR.txt`

Ils résument une partie de la migration de la base et du refactor final.

---

## Sécurité

### Important
Le fichier `mail_config.php` contient actuellement des identifiants d’e-mail.  
Pour un dépôt Git propre, il est conseillé de :

- supprimer les identifiants réels du dépôt ;
- utiliser des variables d’environnement ;
- ajouter le fichier sensible dans `.gitignore` si besoin.

Exemple de bonne pratique :
- `mail_config.example.php` versionné ;
- `mail_config.php` local non versionné.

---

## Pistes d’amélioration

- restructurer le projet en MVC complet ;
- renforcer la gestion des permissions ;
- améliorer le responsive mobile ;
- ajouter une recherche et des filtres plus avancés ;
- ajouter des statistiques plus détaillées ;
- mieux séparer la logique métier et l’affichage ;
- externaliser toute la configuration sensible.

---

## Remarque finale

Ce dépôt a une valeur importante car il conserve **tout le cheminement du projet**.  
Il ne contient pas seulement une version finale, mais aussi les différentes étapes de construction à travers plusieurs dossiers et commits.

Pour tester le projet final, utiliser :

```text
MyCampusUdm/MyCampusUdmClubsC6
```
