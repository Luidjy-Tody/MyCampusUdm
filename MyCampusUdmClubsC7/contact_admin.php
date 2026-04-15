<?php

session_start();

if (!isset($_SESSION["id"]))
{
    header("Location: connexion.php");
    exit();
}

if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin")
{
    header("Location: messagerie_admin.php");
    exit();
}

require "config/database.php";
require_once "includes/app_helpers.php";
require_once "includes/mail_helpers.php";

if (!function_exists("nettoyerMessagesSupprimesDesDeuxCotes"))
{
    function nettoyerMessagesSupprimesDesDeuxCotes(PDO $pdo): void
    {
        $delete = $pdo->prepare("
            DELETE FROM messages_prives
            WHERE supprime_par_expediteur = 1
              AND supprime_par_destinataire = 1
        ");
        $delete->execute();
    }
}

$userId = (int) $_SESSION["id"];
$adminId = getMainAdminId($pdo);

$messageSucces = $_SESSION["contact_success"] ?? "";
$messageErreur = $_SESSION["contact_error"] ?? "";
unset($_SESSION["contact_success"], $_SESSION["contact_error"]);

if ($_SERVER["REQUEST_METHOD"] === "POST")
{
    $action = $_POST["action"] ?? "";

    if ($action === "send_message")
    {
        $sujet = trim($_POST["sujet"] ?? "");
        $contenu = trim($_POST["contenu"] ?? "");

        if ($adminId === null)
        {
            $_SESSION["contact_error"] = "Aucun administrateur actif n’est disponible pour le moment.";
        }
        elseif ($sujet === "" || $contenu === "")
        {
            $_SESSION["contact_error"] = "Le sujet et le message sont obligatoires.";
        }
        else
        {
            $insert = $pdo->prepare("
                INSERT INTO messages_prives (expediteur_id, destinataire_id, sujet, contenu)
                VALUES (:expediteur_id, :destinataire_id, :sujet, :contenu)
            ");
            $insert->execute([
                "expediteur_id" => $userId,
                "destinataire_id" => $adminId,
                "sujet" => $sujet,
                "contenu" => $contenu
            ]);

            createNotification(
                $pdo,
                $adminId,
                "Nouveau message privé",
                currentUserDisplayName() . " vous a envoyé un message : " . $sujet,
                "message"
            );

            $adminStmt = $pdo->prepare("
                SELECT nom, prenom, email
                FROM utilisateurs
                WHERE id = :id
                LIMIT 1
            ");
            $adminStmt->execute(["id" => $adminId]);
            $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

            if ($admin)
            {
                $nomAdmin = trim((string) (($admin["prenom"] ?? "") . " " . ($admin["nom"] ?? "")));

                $htmlBody = "
                    <p>Bonjour " . htmlspecialchars($nomAdmin !== "" ? $nomAdmin : "administrateur", ENT_QUOTES, 'UTF-8') . ",</p>
                    <p>Vous avez reçu un nouveau message sur <strong>MyCampusClubsUDM</strong>.</p>
                    <p><strong>Expéditeur :</strong> " . htmlspecialchars(currentUserDisplayName(), ENT_QUOTES, 'UTF-8') . "</p>
                    <p><strong>Sujet :</strong> " . htmlspecialchars($sujet, ENT_QUOTES, 'UTF-8') . "</p>
                    <div style='padding:12px;border-left:4px solid #0d4b8f;background:#f7f9fc;'>" . nl2br(htmlspecialchars($contenu, ENT_QUOTES, 'UTF-8')) . "</div>
                    <p style='margin-top:20px;'>Connectez-vous à la plateforme pour répondre.</p>
                ";

                $plainBody = "Bonjour " . ($nomAdmin !== "" ? $nomAdmin : "administrateur") . ",

"
                    . "Vous avez reçu un nouveau message sur MyCampusClubsUDM.

"
                    . "Expéditeur : " . currentUserDisplayName() . "
"
                    . "Sujet : " . $sujet . "

"
                    . $contenu . "

"
                    . "Connectez-vous à la plateforme pour répondre.";

                sendPlatformEmail((string) $admin["email"], $nomAdmin, "Nouveau message reçu - MyCampusClubsUDM", $htmlBody, $plainBody);
            }

            $_SESSION["contact_success"] = "Votre message a bien été envoyé à l’administrateur.";
        }

        header("Location: contact_admin.php");
        exit();
    }

    if ($action === "delete_message")
    {
        $messageId = (int) ($_POST["message_id"] ?? 0);

        if ($adminId !== null && $messageId > 0)
        {
            $delete = $pdo->prepare("
                UPDATE messages_prives
                SET
                    supprime_par_expediteur = CASE
                        WHEN expediteur_id = :user_id THEN 1
                        ELSE supprime_par_expediteur
                    END,
                    supprime_par_destinataire = CASE
                        WHEN destinataire_id = :user_id THEN 1
                        ELSE supprime_par_destinataire
                    END
                WHERE id = :id
                  AND (
                        (expediteur_id = :user_id AND destinataire_id = :admin_id AND supprime_par_expediteur = 0)
                     OR (expediteur_id = :admin_id AND destinataire_id = :user_id AND supprime_par_destinataire = 0)
                  )
            ");
            $delete->execute([
                "id" => $messageId,
                "user_id" => $userId,
                "admin_id" => $adminId
            ]);

            nettoyerMessagesSupprimesDesDeuxCotes($pdo);

            if ($delete->rowCount() > 0)
            {
                $_SESSION["contact_success"] = "Le message a été supprimé de votre boîte.";
            }
            else
            {
                $_SESSION["contact_error"] = "Suppression impossible.";
            }
        }

        header("Location: contact_admin.php");
        exit();
    }

    if ($action === "delete_all_messages")
    {
        if ($adminId !== null)
        {
            $deleteAll = $pdo->prepare("
                UPDATE messages_prives
                SET
                    supprime_par_expediteur = CASE
                        WHEN expediteur_id = :user_id THEN 1
                        ELSE supprime_par_expediteur
                    END,
                    supprime_par_destinataire = CASE
                        WHEN destinataire_id = :user_id THEN 1
                        ELSE supprime_par_destinataire
                    END
                WHERE (
                        (expediteur_id = :user_id AND destinataire_id = :admin_id AND supprime_par_expediteur = 0)
                     OR (expediteur_id = :admin_id AND destinataire_id = :user_id AND supprime_par_destinataire = 0)
                )
            ");
            $deleteAll->execute([
                "user_id" => $userId,
                "admin_id" => $adminId
            ]);

            nettoyerMessagesSupprimesDesDeuxCotes($pdo);

            $_SESSION["contact_success"] = "Tous les messages visibles ont été supprimés de votre boîte.";
        }

        header("Location: contact_admin.php");
        exit();
    }
}

$messages = [];
$dernierSujet = "Demande de responsable officiel";

if ($adminId !== null)
{
    $markRead = $pdo->prepare("
        UPDATE messages_prives
        SET est_lu = 1
        WHERE expediteur_id = :admin_id
          AND destinataire_id = :user_id
          AND supprime_par_destinataire = 0
    ");
    $markRead->execute([
        "admin_id" => $adminId,
        "user_id" => $userId
    ]);

    $messagesStmt = $pdo->prepare("
        SELECT mp.*, u.nom, u.prenom, u.pseudo
        FROM messages_prives mp
        INNER JOIN utilisateurs u ON u.id = mp.expediteur_id
        WHERE (
                (mp.expediteur_id = :user_id AND mp.destinataire_id = :admin_id AND mp.supprime_par_expediteur = 0)
             OR (mp.expediteur_id = :admin_id AND mp.destinataire_id = :user_id AND mp.supprime_par_destinataire = 0)
        )
        ORDER BY mp.date_envoi ASC, mp.id ASC
    ");
    $messagesStmt->execute([
        "user_id" => $userId,
        "admin_id" => $adminId
    ]);
    $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($messages))
    {
        $dernierMessage = end($messages);
        $dernierSujet = trim((string) $dernierMessage["sujet"]);
        if ($dernierSujet === "")
        {
            $dernierSujet = "Demande de responsable officiel";
        }
    }
}

$titrePage = "Contact administrateur";
include "includes/header.php";
?>

<section class="entete-page">
    <h1>Contacter l’administrateur</h1>
    <p class="texte-gris">
        Utilisez ce formulaire pour envoyer un message privé à l’administrateur,
        par exemple pour demander à devenir responsable officiel.
    </p>
</section>

<?php if ($messageSucces !== "") : ?>
<section class="carte">
    <p style="color:#0f7a39;"><?= htmlspecialchars($messageSucces) ?></p>
</section>
<?php endif; ?>

<?php if ($messageErreur !== "") : ?>
<section class="carte">
    <p style="color:#a11212;"><?= htmlspecialchars($messageErreur) ?></p>
</section>
<?php endif; ?>

<section class="carte">
    <h2>Nouveau message</h2>

    <form method="post" action="contact_admin.php">
        <input type="hidden" name="action" value="send_message">

        <label>Sujet</label>
        <input
            type="text"
            name="sujet"
            class="champ"
            style="width:100%;margin-bottom:10px;"
            value="<?= htmlspecialchars($dernierSujet) ?>"
            required
        >

        <label>Message</label>
        <textarea
            name="contenu"
            class="champ"
            style="width:100%;min-height:140px;"
            placeholder="Écrivez votre message ici..."
            required
        ></textarea>

        <div class="carte-actions" style="margin-top:12px;">
            <button type="submit" class="bouton">
                <i class="fas fa-paper-plane"></i> Envoyer
            </button>
        </div>
    </form>
</section>

<section class="carte">
    <div class="carte-titre">
        <h2>Conversation privée</h2>

        <?php if (!empty($messages)) : ?>
            <form method="post" action="contact_admin.php" onsubmit="return confirm('Supprimer tous les messages visibles de votre boîte ?');" style="margin:0;">
                <input type="hidden" name="action" value="delete_all_messages">
                <button type="submit" class="bouton bouton-secondaire petit">
                    <i class="fas fa-trash"></i> Tout supprimer de ma boîte
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($messages)) : ?>
        <p class="texte-gris">Aucun message pour le moment.</p>
    <?php else : ?>
        <?php foreach ($messages as $message) : ?>
            <?php $estMoi = (int) $message["expediteur_id"] === $userId; ?>
            <article class="carte" style="margin-bottom:12px;border-left:4px solid <?= $estMoi ? '#0d4b8f' : '#0f7a39' ?>;">
                <div class="carte-titre">
                    <h2 style="margin:0;"><?= $estMoi ? "Vous" : "Administrateur" ?></h2>

                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <?php if ($estMoi) : ?>
                            <span class="badge badge-actif">Envoyé</span>
                        <?php else : ?>
                            <span class="badge badge-attente">Réponse</span>
                        <?php endif; ?>

                        <form method="post" action="contact_admin.php" onsubmit="return confirm('Supprimer ce message de votre boîte ?');" style="margin:0;">
                            <input type="hidden" name="action" value="delete_message">
                            <input type="hidden" name="message_id" value="<?= (int) $message["id"] ?>">
                            <button type="submit" class="bouton bouton-secondaire petit">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </form>
                    </div>
                </div>

                <p class="petit texte-gris" style="margin-top:8px;">
                    Sujet : <?= htmlspecialchars($message["sujet"]) ?> • <?= htmlspecialchars($message["date_envoi"]) ?>
                </p>

                <p class="texte-gris"><?= nl2br(htmlspecialchars($message["contenu"])) ?></p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php include "includes/footer.php"; ?>