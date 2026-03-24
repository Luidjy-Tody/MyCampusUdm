<?php

session_start();
require "config/database.php";

header("Content-Type: application/json");

$email = trim($_POST["email"] ?? "");

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Veuillez entrer une adresse email valide."]);
    exit();
}

// Créer la table newsletter si elle n'existe pas
$pdo->exec("
    CREATE TABLE IF NOT EXISTS newsletter_abonnes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        token VARCHAR(64) NOT NULL,
        date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
        actif TINYINT(1) DEFAULT 1
    )
");

// Vérifier si déjà abonné
$check = $pdo->prepare("SELECT id, actif FROM newsletter_abonnes WHERE email = :email");
$check->execute(["email" => $email]);
$existing = $check->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    if ($existing["actif"]) {
        echo json_encode(["success" => false, "message" => "Cette adresse est déjà abonnée à la newsletter."]);
    } else {
        // Réactiver
        $pdo->prepare("UPDATE newsletter_abonnes SET actif = 1 WHERE email = :email")->execute(["email" => $email]);
        echo json_encode(["success" => true, "message" => "Vous êtes de nouveau abonné à la newsletter ! 🎉"]);
    }
    exit();
}

$token = bin2hex(random_bytes(32));

$stmt = $pdo->prepare("INSERT INTO newsletter_abonnes (email, token) VALUES (:email, :token)");
$stmt->execute(["email" => $email, "token" => $token]);

echo json_encode(["success" => true, "message" => "Merci ! Vous êtes maintenant abonné à la newsletter MyCampusUDM 🎉"]);
