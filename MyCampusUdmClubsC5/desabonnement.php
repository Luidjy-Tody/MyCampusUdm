<?php
require "config/database.php";
$token = trim($_GET["token"] ?? "");
$message = "Lien invalide.";
if ($token !== "") {
    $stmt = $pdo->prepare("UPDATE newsletter_abonnes SET est_actif = 0 WHERE token_desabonnement = :token");
    $stmt->execute(["token" => $token]);
    $message = $stmt->rowCount() > 0 ? "Vous êtes maintenant désabonné de la newsletter." : "Aucun abonnement correspondant.";
}
?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Désabonnement</title><link rel="stylesheet" href="css/style.css"></head><body><main class="conteneur contenu"><section class="carte"><h1>Désabonnement newsletter</h1><p class="texte-gris"><?= htmlspecialchars($message) ?></p><div class="carte-actions"><a class="bouton" href="clubs.php">Retour au site</a></div></section></main></body></html>
