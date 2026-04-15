<?php

session_start();

if (!isset($_SESSION["id"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin")
{
    header("Location: connexion.php");
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

$adminId = (int) $_SESSION["id"];
$selectedUserId = isset($_GET["user"]) ? (int) $_GET["user"] : 0;

$messageSucces = $_SESSION["admin_msg_success"] ?? "";
$messageErreur = $_SESSION["admin_msg_error"] ?? "";
unset($_SESSION["admin_msg_success"], $_SESSION["admin_msg_error"]);

if ($_SERVER["REQUEST_METHOD"] === "POST")
{
    $action = $_POST["action"] ?? "";
    $selectedUserId = (int) ($_POST["user_id"] ?? $selectedUserId);

    if ($action === "reply")
    {
        $sujet = trim($_POST["sujet"] ?? "");
        $contenu = trim($_POST["contenu"] ?? "");

        $userStmt = $pdo->prepare("
            SELECT id, nom, prenom, pseudo, email
            FROM utilisateurs
            WHERE id = :id
              AND id <> :admin_id
              AND role <> 'admin'
            LIMIT 1
        ");
        $userStmt->execute([
            "id" => $selectedUserId,
            "admin_id" => $adminId
        ]);
        $destinataire = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$destinataire)
        {
            $_SESSION["admin_msg_error"] = "Utilisateur introuvable.";
        }
        elseif ($sujet === "" || $contenu === "")
        {
            $_SESSION["admin_msg_error"] = "Le sujet et la réponse sont obligatoires.";
        }
        else
        {
            $insert = $pdo->prepare("
                INSERT INTO messages_prives (expediteur_id, destinataire_id, sujet, contenu)
                VALUES (:expediteur_id, :destinataire_id, :sujet, :contenu)
            ");
            $insert->execute([
                "expediteur_id" => $adminId,
                "destinataire_id" => $selectedUserId,
                "sujet" => $sujet,
                "contenu" => $contenu
            ]);

            createNotification(
                $pdo,
                $selectedUserId,
                "Réponse de l’administrateur",
                "L’administrateur a répondu à votre message : " . $sujet,
                "message"
            );

            $nomDestinataire = trim((string) (($destinataire["prenom"] ?? "") . " " . ($destinataire["nom"] ?? "")));

            $htmlBody = "
                <p>Bonjour " . htmlspecialchars($nomDestinataire !== "" ? $nomDestinataire : "utilisateur", ENT_QUOTES, 'UTF-8') . ",</p>
                <p>L’administrateur a répondu à votre message sur <strong>MyCampusClubsUDM</strong>.</p>
                <p><strong>Sujet :</strong> " . htmlspecialchars($sujet, ENT_QUOTES, 'UTF-8') . "</p>
                <div style='padding:12px;border-left:4px solid #0f7a39;background:#f7f9fc;'>" . nl2br(htmlspecialchars($contenu, ENT_QUOTES, 'UTF-8')) . "</div>
                <p style='margin-top:20px;'>Connectez-vous à la plateforme pour consulter la conversation complète.</p>
            ";

            $plainBody = "Bonjour " . ($nomDestinataire !== "" ? $nomDestinataire : "utilisateur") . ",

"
                . "L’administrateur a répondu à votre message sur MyCampusClubsUDM.

"
                . "Sujet : " . $sujet . "

"
                . $contenu . "

"
                . "Connectez-vous à la plateforme pour consulter la conversation complète.";

            sendPlatformEmail((string) $destinataire["email"], $nomDestinataire, "Réponse de l’administrateur - MyCampusClubsUDM", $htmlBody, $plainBody);

            $_SESSION["admin_msg_success"] = "Réponse envoyée avec succès.";
        }

        header("Location: messagerie_admin.php?user=" . $selectedUserId);
        exit();
    }

    if ($action === "delete_message")
    {
        $messageId = (int) ($_POST["message_id"] ?? 0);

        if ($messageId > 0 && $selectedUserId > 0)
        {
            $delete = $pdo->prepare("
                UPDATE messages_prives
                SET
                    supprime_par_expediteur = CASE
                        WHEN expediteur_id = :admin_id THEN 1
                        ELSE supprime_par_expediteur
                    END,
                    supprime_par_destinataire = CASE
                        WHEN destinataire_id = :admin_id THEN 1
                        ELSE supprime_par_destinataire
                    END
                WHERE id = :id
                  AND (
                        (expediteur_id = :admin_id AND destinataire_id = :user_id AND supprime_par_expediteur = 0)
                     OR (expediteur_id = :user_id AND destinataire_id = :admin_id AND supprime_par_destinataire = 0)
                  )
            ");
            $delete->execute([
                "id" => $messageId,
                "admin_id" => $adminId,
                "user_id" => $selectedUserId
            ]);

            nettoyerMessagesSupprimesDesDeuxCotes($pdo);

            if ($delete->rowCount() > 0)
            {
                $_SESSION["admin_msg_success"] = "Le message a été supprimé de votre boîte.";
            }
            else
            {
                $_SESSION["admin_msg_error"] = "Suppression impossible.";
            }
        }

        header("Location: messagerie_admin.php?user=" . $selectedUserId);
        exit();
    }

    if ($action === "delete_all_messages")
    {
        if ($selectedUserId > 0)
        {
            $deleteAll = $pdo->prepare("
                UPDATE messages_prives
                SET
                    supprime_par_expediteur = CASE
                        WHEN expediteur_id = :admin_id THEN 1
                        ELSE supprime_par_expediteur
                    END,
                    supprime_par_destinataire = CASE
                        WHEN destinataire_id = :admin_id THEN 1
                        ELSE supprime_par_destinataire
                    END
                WHERE (
                        (expediteur_id = :admin_id AND destinataire_id = :user_id AND supprime_par_expediteur = 0)
                     OR (expediteur_id = :user_id AND destinataire_id = :admin_id AND supprime_par_destinataire = 0)
                )
            ");
            $deleteAll->execute([
                "admin_id" => $adminId,
                "user_id" => $selectedUserId
            ]);

            nettoyerMessagesSupprimesDesDeuxCotes($pdo);

            $_SESSION["admin_msg_success"] = "Tous les messages visibles ont été supprimés de votre boîte.";
        }

        header("Location: messagerie_admin.php?user=" . $selectedUserId);
        exit();
    }
}

$contactsStmt = $pdo->prepare("
    SELECT
        u.id,
        u.nom,
        u.prenom,
        u.pseudo,
        u.email,
        MAX(mp.date_envoi) AS dernier_message,
        SUM(
            CASE
                WHEN mp.destinataire_id = ? AND mp.est_lu = 0 AND mp.supprime_par_destinataire = 0 THEN 1
                ELSE 0
            END
        ) AS non_lus
    FROM messages_prives mp
    INNER JOIN utilisateurs u
        ON u.id = CASE
            WHEN mp.expediteur_id = ? THEN mp.destinataire_id
            ELSE mp.expediteur_id
        END
    WHERE (
            (mp.expediteur_id = ? AND mp.supprime_par_expediteur = 0)
         OR (mp.destinataire_id = ? AND mp.supprime_par_destinataire = 0)
    )
      AND u.id <> ?
      AND u.role <> 'admin'
    GROUP BY u.id, u.nom, u.prenom, u.pseudo, u.email
    ORDER BY dernier_message DESC
");
$contactsStmt->execute([$adminId, $adminId, $adminId, $adminId, $adminId]);
$contacts = $contactsStmt->fetchAll(PDO::FETCH_ASSOC);

$utilisateurSelectionne = null;
$messages = [];
$dernierSujet = "Réponse administrateur";

if ($selectedUserId > 0)
{
    $userStmt = $pdo->prepare("
        SELECT id, nom, prenom, pseudo, email
        FROM utilisateurs
        WHERE id = :id
          AND id <> :admin_id
          AND role <> 'admin'
        LIMIT 1
    ");
    $userStmt->execute([
        "id" => $selectedUserId,
        "admin_id" => $adminId
    ]);
    $utilisateurSelectionne = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($utilisateurSelectionne)
    {
        $markRead = $pdo->prepare("
            UPDATE messages_prives
            SET est_lu = 1
            WHERE expediteur_id = :user_id
              AND destinataire_id = :admin_id
              AND supprime_par_destinataire = 0
        ");
        $markRead->execute([
            "user_id" => $selectedUserId,
            "admin_id" => $adminId
        ]);

        $messagesStmt = $pdo->prepare("
            SELECT mp.*, u.nom, u.prenom, u.pseudo
            FROM messages_prives mp
            INNER JOIN utilisateurs u ON u.id = mp.expediteur_id
            WHERE (
                    (mp.expediteur_id = :admin_id AND mp.destinataire_id = :user_id AND mp.supprime_par_expediteur = 0)
                 OR (mp.expediteur_id = :user_id AND mp.destinataire_id = :admin_id AND mp.supprime_par_destinataire = 0)
            )
            ORDER BY mp.date_envoi ASC, mp.id ASC
        ");
        $messagesStmt->execute([
            "admin_id" => $adminId,
            "user_id" => $selectedUserId
        ]);
        $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($messages))
        {
            $dernierMessage = end($messages);
            $dernierSujet = trim((string) $dernierMessage["sujet"]);
            if ($dernierSujet === "")
            {
                $dernierSujet = "Réponse administrateur";
            }
        }
    }
}

$titrePage = "Messagerie privée";
include "includes/header.php";
?>

<section class="entete-page">
    <h1>Messagerie privée administrateur</h1>
    <p class="texte-gris">Seuls l’administrateur et l’utilisateur concerné peuvent voir ces échanges.</p>
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
    <h2>Conversations reçues</h2>

    <?php if (empty($contacts)) : ?>
        <p class="texte-gris">Aucun message privé reçu pour le moment.</p>
    <?php else : ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Non lus</th>
                    <th>Dernier message</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $contact) : ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars(trim(($contact["prenom"] ?? "") . " " . ($contact["nom"] ?? ""))) ?></strong>
                            <?php if (!empty($contact["pseudo"])) : ?>
                                <br><span class="texte-gris petit">@<?= htmlspecialchars($contact["pseudo"]) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($contact["email"]) ?></td>
                        <td>
                            <?php if ((int) $contact["non_lus"] > 0) : ?>
                                <span class="badge badge-attente"><?= (int) $contact["non_lus"] ?></span>
                            <?php else : ?>
                                <span class="badge badge-actif">0</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($contact["dernier_message"]) ?></td>
                        <td>
                            <a class="bouton petit" href="messagerie_admin.php?user=<?= (int) $contact["id"] ?>">
                                Ouvrir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section class="carte">
    <div class="carte-titre">
        <h2>Conversation</h2>

        <?php if ($utilisateurSelectionne && !empty($messages)) : ?>
            <form method="post" action="messagerie_admin.php?user=<?= (int) $utilisateurSelectionne["id"] ?>" onsubmit="return confirm('Supprimer tous les messages visibles de votre boîte ?');" style="margin:0;">
                <input type="hidden" name="action" value="delete_all_messages">
                <input type="hidden" name="user_id" value="<?= (int) $utilisateurSelectionne["id"] ?>">
                <button type="submit" class="bouton bouton-secondaire petit">
                    <i class="fas fa-trash"></i> Tout supprimer de ma boîte
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!$utilisateurSelectionne) : ?>
        <p class="texte-gris">Sélectionnez un utilisateur pour lire la conversation et répondre.</p>
    <?php else : ?>
        <p class="texte-gris">
            Conversation avec
            <strong><?= htmlspecialchars(trim(($utilisateurSelectionne["prenom"] ?? "") . " " . ($utilisateurSelectionne["nom"] ?? ""))) ?></strong>
            (<?= htmlspecialchars($utilisateurSelectionne["email"]) ?>)
        </p>

        <?php if (empty($messages)) : ?>
            <p class="texte-gris">Aucun message dans cette conversation.</p>
        <?php else : ?>
            <?php foreach ($messages as $message) : ?>
                <?php $envoyeParAdmin = (int) $message["expediteur_id"] === $adminId; ?>
                <article class="carte" style="margin-bottom:12px;border-left:4px solid <?= $envoyeParAdmin ? '#0d4b8f' : '#0f7a39' ?>;">
                    <div class="carte-titre">
                        <h2 style="margin:0;"><?= $envoyeParAdmin ? "Vous" : "Utilisateur" ?></h2>

                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <?php if ($envoyeParAdmin) : ?>
                                <span class="badge badge-actif">Réponse admin</span>
                            <?php else : ?>
                                <span class="badge badge-attente">Message reçu</span>
                            <?php endif; ?>

                            <form method="post" action="messagerie_admin.php?user=<?= (int) $utilisateurSelectionne["id"] ?>" onsubmit="return confirm('Supprimer ce message de votre boîte ?');" style="margin:0;">
                                <input type="hidden" name="action" value="delete_message">
                                <input type="hidden" name="user_id" value="<?= (int) $utilisateurSelectionne["id"] ?>">
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

        <hr style="margin:20px 0;opacity:.2;">

        <h3>Répondre</h3>
        <form method="post" action="messagerie_admin.php?user=<?= (int) $utilisateurSelectionne["id"] ?>">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="user_id" value="<?= (int) $utilisateurSelectionne["id"] ?>">

            <label>Sujet</label>
            <input
                type="text"
                name="sujet"
                class="champ"
                style="width:100%;margin-bottom:10px;"
                value="<?= htmlspecialchars($dernierSujet) ?>"
                required
            >

            <label>Réponse</label>
            <textarea
                name="contenu"
                class="champ"
                style="width:100%;min-height:140px;"
                placeholder="Écrivez votre réponse..."
                required
            ></textarea>

            <div class="carte-actions" style="margin-top:12px;">
                <button type="submit" class="bouton">
                    <i class="fas fa-paper-plane"></i> Envoyer la réponse
                </button>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php include "includes/footer.php"; ?>