DROP DATABASE IF EXISTS mycampus_udm;

CREATE DATABASE mycampus_udm
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE mycampus_udm;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    role ENUM('etudiant','responsable','admin') DEFAULT 'etudiant',
    statut ENUM('actif','inactif') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_club VARCHAR(150) NOT NULL,
    description TEXT,
    responsable_id INT,
    statut ENUM('actif','inactif','attente') DEFAULT 'attente',
    date_creation DATE,
    FOREIGN KEY (responsable_id) REFERENCES utilisateurs(id)
);

CREATE TABLE demandes_adhesion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    club_id INT NOT NULL,
    statut ENUM('en_attente','acceptee','refusee') DEFAULT 'en_attente',
    date_demande DATE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (club_id) REFERENCES clubs(id)
);

CREATE TABLE membres_club (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    club_id INT NOT NULL,
    role_membre ENUM('president','secretaire','membre') DEFAULT 'membre',
    date_entree DATE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (club_id) REFERENCES clubs(id)
);

CREATE TABLE evenements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    date_event DATE NOT NULL,
    lieu VARCHAR(200),
    statut ENUM('actif','annule') DEFAULT 'actif',
    FOREIGN KEY (club_id) REFERENCES clubs(id)
);