DROP DATABASE IF EXISTS mycampus_udm_v3;

CREATE DATABASE mycampus_udm_v3
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE mycampus_udm_v3;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    pseudo VARCHAR(50) DEFAULT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    photo_profil VARCHAR(255) DEFAULT NULL,
    telephone VARCHAR(30) DEFAULT NULL,
    filiere VARCHAR(100) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    role ENUM('etudiant','responsable','admin') DEFAULT 'etudiant',
    statut ENUM('actif','inactif') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_club VARCHAR(150) NOT NULL,
    description TEXT,
    categorie ENUM('sport','culture','musique','informatique','social','entrepreneuriat','autre') DEFAULT 'autre',
    image_club VARCHAR(255) DEFAULT NULL,
    responsable_id INT DEFAULT NULL,
    statut ENUM('actif','inactif','attente','supprime') DEFAULT 'attente',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responsable_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

CREATE TABLE demandes_adhesion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    club_id INT NOT NULL,
    statut ENUM('en_attente','acceptee','refusee','annulee') DEFAULT 'en_attente',
    date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_demande_active (utilisateur_id, club_id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
);

CREATE TABLE membres_club (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    club_id INT NOT NULL,
    role_membre ENUM('president','vice_president','secretaire','tresorier','membre') DEFAULT 'membre',
    date_entree TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_membre_club (utilisateur_id, club_id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
);

CREATE TABLE evenements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    image_event VARCHAR(255) DEFAULT NULL,
    date_event DATETIME NOT NULL,
    lieu VARCHAR(200) DEFAULT NULL,
    capacite_max INT DEFAULT NULL,
    statut ENUM('actif','annule','termine') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
);

CREATE TABLE inscriptions_evenement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    statut ENUM('inscrit','present','absent','annule') DEFAULT 'inscrit',
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_inscription_evenement (evenement_id, utilisateur_id),
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    titre VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type_notification ENUM('adhesion','evenement','newsletter','role','systeme','profil','message') DEFAULT 'systeme',
    est_lue TINYINT(1) DEFAULT 0,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE newsletter_abonnes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) DEFAULT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    est_actif TINYINT(1) DEFAULT 1,
    token_desabonnement VARCHAR(100) NOT NULL UNIQUE,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE newsletter_envois (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT NULL,
    sujet VARCHAR(200) NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

CREATE TABLE galerie_club (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    legende VARCHAR(255) DEFAULT NULL,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
);

CREATE TABLE avis_evenement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    note INT NOT NULL,
    commentaire TEXT DEFAULT NULL,
    date_avis TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_note CHECK (note BETWEEN 1 AND 5),
    UNIQUE KEY unique_avis_evenement (evenement_id, utilisateur_id),
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE messages_prives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expediteur_id INT NOT NULL,
    destinataire_id INT NOT NULL,
    sujet VARCHAR(200) NOT NULL,
    contenu TEXT NOT NULL,
    est_lu TINYINT(1) DEFAULT 0,
    supprime_par_expediteur TINYINT(1) NOT NULL DEFAULT 0,
    supprime_par_destinataire TINYINT(1) NOT NULL DEFAULT 0,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_messages_expediteur (expediteur_id),
    INDEX idx_messages_destinataire (destinataire_id),
    FOREIGN KEY (expediteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (destinataire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

INSERT INTO utilisateurs (nom, prenom, pseudo, email, mot_de_passe_hash, role, statut)
VALUES
('Admin', 'System', 'admin', 'admin@udm.mu', '$2y$12$c84uLZNWBfcMRcKetGZGpOgOy3sYx10YPIyS6Y0yR7o64jifOpjVG', 'admin', 'actif'),
('Raman', 'Kevin', 'kevin_dev', 'kevin@udm.mu', '$2y$12$c84uLZNWBfcMRcKetGZGpOgOy3sYx10YPIyS6Y0yR7o64jifOpjVG', 'responsable', 'actif'),
('Jean', 'Marc', 'marc_info', 'marc@udm.mu', '$2y$12$c84uLZNWBfcMRcKetGZGpOgOy3sYx10YPIyS6Y0yR7o64jifOpjVG', 'etudiant', 'actif');

INSERT INTO clubs (nom_club, description, categorie, responsable_id, statut)
VALUES
('Club Informatique', 'Club pour les passionnés de programmation et IA', 'informatique', 2, 'actif');

INSERT INTO membres_club (utilisateur_id, club_id, role_membre)
VALUES
(2, 1, 'president'),
(3, 1, 'membre');

INSERT INTO evenements (club_id, titre, description, date_event, lieu, capacite_max, statut)
VALUES
(1, 'Hackathon UDM', 'Compétition de programmation 24h', '2026-05-10 09:00:00', 'Salle A', 50, 'actif');

INSERT INTO newsletter_abonnes (nom, email, token_desabonnement, est_actif)
VALUES
('Jean Marc', 'marc@udm.mu', 'token_marc_123', 1);

INSERT INTO newsletter_envois (admin_id, sujet, contenu)
VALUES
(1, 'Bienvenue sur MyCampusUDM', 'Merci de suivre les actualités des clubs de l’université.');

INSERT INTO messages_prives (
    expediteur_id,
    destinataire_id,
    sujet,
    contenu,
    est_lu,
    supprime_par_expediteur,
    supprime_par_destinataire
)
VALUES
(2, 1, 'Demande de validation responsable', 'Bonjour admin, je souhaite devenir responsable officiel du Club Informatique.', 0, 0, 0),
(1, 2, 'Réponse à votre demande', 'Bonjour Kevin, votre demande est bien reçue. Je vais la vérifier.', 0, 0, 0);

INSERT INTO notifications (utilisateur_id, titre, message, type_notification, est_lue)
VALUES
(1, 'Nouveau message privé', 'Kevin vous a envoyé un message : Demande de validation responsable', 'message', 0),
(2, 'Réponse de l’administrateur', 'L’administrateur a répondu à votre message : Réponse à votre demande', 'message', 0),
(3, 'Nouvelle newsletter', 'Sujet : Bienvenue sur MyCampusUDM

Merci de suivre les actualités des clubs de l’université.', 'newsletter', 0);