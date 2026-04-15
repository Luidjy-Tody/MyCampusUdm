<?php

session_start();
require "config/database.php";
require_once "includes/app_helpers.php";
require_once "includes/mail_helpers.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin")
{
    header("Location: connexion.php");
    exit();
}

$messageSucces = "";
$messageErreur = "";

$sujetForm = "";
$contenuForm = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "envoyer")
{
    $sujet = trim($_POST["sujet"] ?? "");
    $contenu = trim($_POST["contenu"] ?? "");

    $sujetForm = $sujet;
    $contenuForm = $contenu;

    if ($sujet === "" || $contenu === "")
    {
        $messageErreur = "Le sujet et le contenu sont obligatoires.";
    }
    else
    {
        try
        {
            $pdo->beginTransaction();

            $save = $pdo->prepare("
                INSERT INTO newsletter_envois (admin_id, sujet, contenu)
                VALUES (:admin_id, :sujet, :contenu)
            ");
            $save->execute([
                "admin_id" => (int) $_SESSION["id"],
                "sujet" => $sujet,
                "contenu" => $contenu
            ]);

            $abonnesPlateformeStmt = $pdo->query("
                SELECT DISTINCT u.id
                FROM utilisateurs u
                INNER JOIN newsletter_abonnes na ON na.email = u.email
                WHERE na.est_actif = 1
                  AND u.statut = 'actif'
            ");
            $abonnesPlateforme = $abonnesPlateformeStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($abonnesPlateforme as $utilisateurId)
            {
                $contenuNotification = "Sujet : " . $sujet . "\n\n" . $contenu;

                createNotification(
                    $pdo,
                    (int) $utilisateurId,
                    "Nouvelle newsletter",
                    $contenuNotification,
                    "newsletter"
                );
            }

            $pdo->commit();

            $abonnesEmailStmt = $pdo->query("
                SELECT nom, email, token_desabonnement
                FROM newsletter_abonnes
                WHERE est_actif = 1
                ORDER BY date_inscription DESC
            ");
            $abonnesEmail = $abonnesEmailStmt->fetchAll(PDO::FETCH_ASSOC);

            $emailsEnvoyes = 0;

            foreach ($abonnesEmail as $abonne)
            {
                $nomAbonne = trim((string) ($abonne["nom"] ?? ""));
                $emailAbonne = trim((string) ($abonne["email"] ?? ""));
                $tokenDesabonnement = trim((string) ($abonne["token_desabonnement"] ?? ""));

                if ($emailAbonne === "")
                {
                    continue;
                }

                $lienDesabonnement = "";
                $urlDesabonnement = "";

                if (defined('APP_BASE_URL') && trim((string) APP_BASE_URL) !== '' && $tokenDesabonnement !== "")
                {
                    $urlDesabonnement = rtrim((string) APP_BASE_URL, '/') . '/desabonnement.php?token=' . urlencode($tokenDesabonnement);
                    $lienDesabonnement = "<p style='margin-top:20px;font-size:13px;color:#666;'>Si vous ne souhaitez plus recevoir ces e-mails, utilisez ce lien : <a href='" . htmlspecialchars($urlDesabonnement, ENT_QUOTES, 'UTF-8') . "'>Se désabonner</a></p>";
                }

                $htmlBody = "
                    <p>Bonjour " . htmlspecialchars($nomAbonne !== "" ? $nomAbonne : "abonné", ENT_QUOTES, 'UTF-8') . ",</p>
                    <p>Une nouvelle newsletter a été publiée sur <strong>MyCampusClubsUDM</strong>.</p>
                    <p><strong>Sujet :</strong> " . nl2br(htmlspecialchars($sujet, ENT_QUOTES, 'UTF-8')) . "</p>
                    <div style='padding:12px;border-left:4px solid #0d4b8f;background:#f7f9fc;'>" . nl2br(htmlspecialchars($contenu, ENT_QUOTES, 'UTF-8')) . "</div>
                    <p style='margin-top:20px;'>Merci de suivre les actualités de la plateforme.</p>
                    " . $lienDesabonnement . "
                ";

                $plainBody = "Bonjour " . ($nomAbonne !== "" ? $nomAbonne : "abonné") . ",\n\n"
                    . "Une nouvelle newsletter a été publiée sur MyCampusClubsUDM.\n\n"
                    . "Sujet : " . $sujet . "\n\n"
                    . $contenu;

                if ($tokenDesabonnement !== "")
                {
                    $plainBody .= "\n\nPour vous désabonner : desabonnement.php?token=" . $tokenDesabonnement;
                }

                if (sendPlatformEmail($emailAbonne, $nomAbonne, $sujet, $htmlBody, $plainBody))
                {
                    $emailsEnvoyes++;
                }
            }

            $messageSucces = "Newsletter enregistrée, notifications plateforme envoyées à " . count($abonnesPlateforme) . " abonné(s) et e-mails envoyés à " . $emailsEnvoyes . " abonné(s).";
            $sujetForm = "";
            $contenuForm = "";
        }
        catch (Throwable $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }

            $messageErreur = "Une erreur est survenue pendant l’envoi de la newsletter.";
        }
    }
}

if (isset($_GET["supprimer"]))
{
    $id = (int) $_GET["supprimer"];
    $pdo->prepare("DELETE FROM newsletter_abonnes WHERE id = :id")->execute(["id" => $id]);
    header("Location: newsletter_admin.php");
    exit();
}

$abonnesStmt = $pdo->query("SELECT * FROM newsletter_abonnes ORDER BY date_inscription DESC");
$abonnes = $abonnesStmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($abonnes);
$actifs = count(array_filter($abonnes, fn($a) => (int) $a["est_actif"] === 1));

$titrePage = "Gestion Newsletter";
include "includes/header.php";
?>

<section class="entete-page">
    <h1><i class="fas fa-envelope-open-text" style="color:#0d4b8f;margin-right:10px;"></i>Gestion Newsletter</h1>
    <p class="texte-gris">Gérez les abonnés et préparez vos newsletters.</p>
</section>

<?php if ($messageSucces !== "") : ?>
<section class="carte" style="border-left:4px solid #0f7a39;">
    <p style="color:#0f7a39;margin:0;">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($messageSucces) ?>
    </p>
</section>
<?php endif; ?>

<?php if ($messageErreur !== "") : ?>
<section class="carte" style="border-left:4px solid #a11212;">
    <p style="color:#a11212;margin:0;">
        <i class="fas fa-times-circle"></i> <?= htmlspecialchars($messageErreur) ?>
    </p>
</section>
<?php endif; ?>

<section class="grille-3">
    <div class="carte" style="text-align:center;">
        <p class="texte-gris petit">Total abonnés</p>
        <h2 style="color:#0d4b8f;font-size:2rem;margin:0;"><?= $total ?></h2>
    </div>

    <div class="carte" style="text-align:center;">
        <p class="texte-gris petit">Abonnés actifs</p>
        <h2 style="color:#0f7a39;font-size:2rem;margin:0;"><?= $actifs ?></h2>
    </div>

    <div class="carte" style="text-align:center;">
        <p class="texte-gris petit">Désabonnés</p>
        <h2 style="color:#a11212;font-size:2rem;margin:0;"><?= $total - $actifs ?></h2>
    </div>
</section>

<section class="carte">
    <h2><i class="fas fa-paper-plane" style="color:#0d4b8f;margin-right:8px;"></i>Préparer une newsletter</h2>
    <form method="POST">
        <input type="hidden" name="action" value="envoyer">

        <label>Sujet</label>
        <input
            type="text"
            name="sujet"
            class="champ"
            style="width:100%;margin-bottom:10px;"
            placeholder="Ex: Nouvel événement au Club Sport !"
            value="<?= htmlspecialchars($sujetForm) ?>"
            required
        >

        <label>Contenu du message</label>
        <textarea
            name="contenu"
            class="champ"
            style="width:100%;min-height:150px;"
            placeholder="Votre message ici..."
            required
        ><?= htmlspecialchars($contenuForm) ?></textarea>

        <p class="petit texte-gris" style="margin-top:10px;">
            La newsletter sera publiée sur la plateforme et envoyée aussi directement par e-mail aux abonnés actifs.
        </p>

        <div class="carte-actions" style="margin-top:12px;">
            <button type="submit" class="bouton">
                <i class="fas fa-paper-plane"></i> Publier la newsletter
            </button>
        </div>
    </form>
</section>

<section class="carte">
    <h2><i class="fas fa-list" style="color:#0d4b8f;margin-right:8px;"></i>Liste des abonnés</h2>

    <?php if (empty($abonnes)) : ?>
        <p class="texte-gris">Aucun abonné pour le moment.</p>
    <?php else : ?>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Date inscription</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($abonnes as $a) : ?>
            <tr>
                <td><?= (int) $a["id"] ?></td>
                <td><?= htmlspecialchars($a["nom"] ?? "") ?></td>
                <td><?= htmlspecialchars($a["email"]) ?></td>
                <td><?= htmlspecialchars($a["date_inscription"]) ?></td>
                <td>
                    <?php if ((int) $a["est_actif"] === 1) : ?>
                        <span class="badge badge-actif">Actif</span>
                    <?php else : ?>
                        <span class="badge badge-annule">Désabonné</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a
                        class="bouton bouton-secondaire petit"
                        href="newsletter_admin.php?supprimer=<?= (int) $a["id"] ?>"
                        onclick="return confirm('Supprimer cet abonné ?');"
                    >
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>

<?php include "includes/footer.php"; ?>