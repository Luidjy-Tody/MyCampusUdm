<?php

session_start();
require "config/database.php";
require_once "includes/app_helpers.php";

header("Content-Type: application/json");

$email = trim($_POST["email"] ?? "");
$nom = trim($_POST["nom"] ?? "");

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Veuillez entrer une adresse email valide."]);
    exit();
}

$check = $pdo->prepare("SELECT id, est_actif FROM newsletter_abonnes WHERE email = :email");
$check->execute(["email" => $email]);
$existing = $check->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    if ((int) $existing["est_actif"] === 1) {
        echo json_encode(["success" => false, "message" => "Cette adresse est déjà abonnée à la newsletter."]);
    } else {
        $pdo->prepare("UPDATE newsletter_abonnes SET est_actif = 1 WHERE email = :email")->execute(["email" => $email]);
        echo json_encode(["success" => true, "message" => "Vous êtes de nouveau abonné à la newsletter !"]);
    }
    exit();
}

$token = bin2hex(random_bytes(32));
$stmt = $pdo->prepare("INSERT INTO newsletter_abonnes (nom, email, token_desabonnement) VALUES (:nom, :email, :token)");
$stmt->execute(["nom" => ($nom !== "" ? $nom : null), "email" => $email, "token" => $token]);

if (isset($_SESSION["id"])) {
    createNotification($pdo, (int) $_SESSION["id"], "Newsletter", "Votre abonnement à la newsletter a bien été pris en compte.", "newsletter");
}

echo json_encode(["success" => true, "message" => "Merci ! Vous êtes maintenant abonné à la newsletter MyCampusUDM."]);
