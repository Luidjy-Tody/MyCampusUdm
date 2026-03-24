<?php

function currentUserDisplayName(): string
{
    if (session_status() === PHP_SESSION_NONE)
    {
        session_start();
    }

    $pseudo = trim((string) ($_SESSION["pseudo"] ?? ""));
    if ($pseudo !== "")
    {
        return $pseudo;
    }

    $prenom = trim((string) ($_SESSION["prenom"] ?? ""));
    $nom = trim((string) ($_SESSION["nom"] ?? ""));
    $nomComplet = trim($prenom . " " . $nom);

    return $nomComplet !== "" ? $nomComplet : "Utilisateur";
}

function currentUserPhotoPath(): string
{
    if (session_status() === PHP_SESSION_NONE)
    {
        session_start();
    }

    $photo = trim((string) ($_SESSION["photo_profil"] ?? ""));
    if ($photo !== "")
    {
        return $photo;
    }

    return "images/logo.png";
}

function createNotification(PDO $pdo, int $userId, string $title, string $message, string $type = "systeme"): void
{
    if ($userId <= 0 || $title === '' || $message === '')
    {
        return;
    }

    $typesAutorises = ["adhesion", "evenement", "newsletter", "role", "systeme", "profil"];
    if (!in_array($type, $typesAutorises, true))
    {
        $type = "systeme";
    }

    $stmt = $pdo->prepare('
        INSERT INTO notifications (utilisateur_id, titre, message, type_notification)
        VALUES (:utilisateur_id, :titre, :message, :type_notification)
    ');

    $stmt->execute([
        "utilisateur_id" => $userId,
        "titre" => $title,
        "message" => $message,
        "type_notification" => $type
    ]);
}

function getUnreadNotificationCount(PDO $pdo, int $userId): int
{
    if ($userId <= 0)
    {
        return 0;
    }

    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM notifications
        WHERE utilisateur_id = :utilisateur_id
          AND est_lue = 0
    ');
    $stmt->execute(["utilisateur_id" => $userId]);

    return (int) $stmt->fetchColumn();
}


function currentUserInitials(): string
{
    if (session_status() === PHP_SESSION_NONE)
    {
        session_start();
    }

    $pseudo = trim((string) ($_SESSION["pseudo"] ?? ""));
    if ($pseudo !== "")
    {
        return strtoupper(substr($pseudo, 0, 2));
    }

    $prenom = trim((string) ($_SESSION["prenom"] ?? ""));
    $nom = trim((string) ($_SESSION["nom"] ?? ""));
    $initiales = '';

    if ($prenom !== '')
    {
        $initiales .= mb_strtoupper(mb_substr($prenom, 0, 1));
    }
    if ($nom !== '')
    {
        $initiales .= mb_strtoupper(mb_substr($nom, 0, 1));
    }

    return $initiales !== '' ? $initiales : 'U';
}
