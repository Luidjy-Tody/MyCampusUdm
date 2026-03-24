<?php

session_start();

if (!isset($_SESSION["id"]))
{
    header("Location: connexion.php");
    exit();
}

require "config/database.php";
require_once "includes/app_helpers.php";

$userId = (int) $_SESSION["id"];

if (isset($_GET["mark"]) && $_GET["mark"] === "all")
{
    $update = $pdo->prepare("UPDATE notifications SET est_lue = 1 WHERE utilisateur_id = :utilisateur_id");
    $update->execute(["utilisateur_id" => $userId]);
    header("Location: notifications.php");
    exit();
}

if (isset($_GET["read"]))
{
    $notificationId = (int) $_GET["read"];
    $update = $pdo->prepare("UPDATE notifications SET est_lue = 1 WHERE id = :id AND utilisateur_id = :utilisateur_id");
    $update->execute([
        "id" => $notificationId,
        "utilisateur_id" => $userId
    ]);
    header("Location: notifications.php");
    exit();
}

$notificationsStatement = $pdo->prepare("
    SELECT *
    FROM notifications
    WHERE utilisateur_id = :utilisateur_id
    ORDER BY date_creation DESC, id DESC
");
$notificationsStatement->execute(["utilisateur_id" => $userId]);
$notifications = $notificationsStatement->fetchAll(PDO::FETCH_ASSOC);

$titrePage = "Notifications";
include "includes/header.php";
?>
<section class="entete-page">
    <h1>Mes notifications</h1>
    <p class="texte-gris">Toutes vos alertes liées aux clubs, événements et à votre profil.</p>
</section>
<section class="carte">
    <div class="carte-actions" style="justify-content:space-between;align-items:center;">
        <div>
            <strong><?= count($notifications) ?></strong> notification(s)
        </div>
        <a class="bouton bouton-secondaire" href="notifications.php?mark=all">Tout marquer comme lu</a>
    </div>
</section>
<section class="liste-cartes">
<?php if (empty($notifications)) : ?>
    <article class="carte">
        <h2>Aucune notification</h2>
        <p class="texte-gris">Vous n’avez encore aucune notification.</p>
    </article>
<?php else : ?>
    <?php foreach ($notifications as $notification) : ?>
        <article class="carte notification-card <?= (int) $notification["est_lue"] === 0 ? "notification-non-lue" : "" ?>">
            <div class="carte-titre">
                <h2><?= htmlspecialchars($notification["titre"]) ?></h2>
                <?php if ((int) $notification["est_lue"] === 0) : ?>
                    <span class="badge badge-attente">Non lue</span>
                <?php else : ?>
                    <span class="badge badge-actif">Lue</span>
                <?php endif; ?>
            </div>
            <p class="texte-gris"><?= nl2br(htmlspecialchars($notification["message"])) ?></p>
            <p class="petit texte-gris">Type : <?= htmlspecialchars($notification["type_notification"]) ?> • <?= htmlspecialchars($notification["date_creation"]) ?></p>
            <?php if ((int) $notification["est_lue"] === 0) : ?>
                <div class="carte-actions">
                    <a class="bouton" href="notifications.php?read=<?= (int) $notification["id"] ?>">Marquer comme lue</a>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
</section>
<?php include "includes/footer.php"; ?>
