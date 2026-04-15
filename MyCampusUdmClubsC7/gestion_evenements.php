<?php

session_start();

if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin")
{
    header("Location: admin.php");
    exit();
}

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "responsable")
{
    header("Location: connexion.php");
    exit();
}

require "config/database.php";
require_once "includes/app_helpers.php";
require_once "includes/mail_helpers.php";

$responsableId = (int) $_SESSION["id"];
$clubId = isset($_GET["club_id"]) ? (int) $_GET["club_id"] : 0;
$editId = isset($_GET["edit"]) ? (int) $_GET["edit"] : 0;
$deleteId = isset($_GET["delete"]) ? (int) $_GET["delete"] : 0;
$statusId = isset($_GET["toggle"]) ? (int) $_GET["toggle"] : 0;

$flashError = $_SESSION["event_error"] ?? "";
$flashSuccess = $_SESSION["event_success"] ?? "";
unset($_SESSION["event_error"], $_SESSION["event_success"]);

function notifierMembresClubEvenement(PDO $pdo, int $clubId, string $nomClub, string $titreEvenement, string $dateEvent, string $lieu, string $description, string $typeAction): int
{
    $membresStmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.nom, u.prenom, u.pseudo, u.email
        FROM membres_club mc
        INNER JOIN utilisateurs u ON u.id = mc.utilisateur_id
        WHERE mc.club_id = :club_id
          AND u.statut = 'actif'
    ");
    $membresStmt->execute(["club_id" => $clubId]);
    $membres = $membresStmt->fetchAll(PDO::FETCH_ASSOC);

    $titreNotification = "Mise à jour événement";
    $contenuNotification = "";
    $sujetMail = "";
    $introMail = "";

    if ($typeAction === "create")
    {
        $titreNotification = "Nouvel événement";
        $contenuNotification = "Un nouvel événement a été ajouté dans votre club " . $nomClub . " : " . $titreEvenement;
        $sujetMail = "Nouvel événement - " . $nomClub;
        $introMail = "Un nouvel événement a été ajouté dans votre club";
    }
    elseif ($typeAction === "update")
    {
        $titreNotification = "Événement modifié";
        $contenuNotification = "Un événement de votre club " . $nomClub . " a été modifié : " . $titreEvenement;
        $sujetMail = "Événement modifié - " . $nomClub;
        $introMail = "Un événement de votre club a été modifié";
    }
    elseif ($typeAction === "cancel")
    {
        $titreNotification = "Événement annulé";
        $contenuNotification = "Un événement de votre club " . $nomClub . " a été annulé : " . $titreEvenement;
        $sujetMail = "Événement annulé - " . $nomClub;
        $introMail = "Un événement de votre club a été annulé";
    }
    elseif ($typeAction === "reactivate")
    {
        $titreNotification = "Événement réactivé";
        $contenuNotification = "Un événement de votre club " . $nomClub . " a été réactivé : " . $titreEvenement;
        $sujetMail = "Événement réactivé - " . $nomClub;
        $introMail = "Un événement de votre club a été réactivé";
    }

    $emailsEnvoyes = 0;

    foreach ($membres as $membre)
    {
        createNotification(
            $pdo,
            (int) $membre["id"],
            $titreNotification,
            $contenuNotification,
            "evenement"
        );

        $nomMembre = trim((string) (($membre["prenom"] ?? "") . " " . ($membre["nom"] ?? "")));
        if ($nomMembre === "")
        {
            $nomMembre = trim((string) ($membre["pseudo"] ?? ""));
        }

        $emailMembre = trim((string) ($membre["email"] ?? ""));

        if ($emailMembre === "")
        {
            continue;
        }

        $htmlBody = "
            <p>Bonjour " . htmlspecialchars($nomMembre !== "" ? $nomMembre : "membre", ENT_QUOTES, 'UTF-8') . ",</p>
            <p>" . htmlspecialchars($introMail, ENT_QUOTES, 'UTF-8') . " <strong>" . htmlspecialchars($nomClub, ENT_QUOTES, 'UTF-8') . "</strong> sur <strong>MyCampusClubsUDM</strong>.</p>
            <p><strong>Titre :</strong> " . htmlspecialchars($titreEvenement, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Date :</strong> " . htmlspecialchars($dateEvent, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Lieu :</strong> " . htmlspecialchars($lieu !== "" ? $lieu : "À confirmer", ENT_QUOTES, 'UTF-8') . "</p>
            <div style='padding:12px;border-left:4px solid #0d4b8f;background:#f7f9fc;'>
                " . nl2br(htmlspecialchars($description !== "" ? $description : "Consultez la plateforme pour voir les détails de l’événement.", ENT_QUOTES, 'UTF-8')) . "
            </div>
            <p style='margin-top:20px;'>Connectez-vous à la plateforme pour consulter les détails.</p>
        ";

        $plainBody = "Bonjour " . ($nomMembre !== "" ? $nomMembre : "membre") . ",

"
            . $introMail . " " . $nomClub . " sur MyCampusClubsUDM.

"
            . "Titre : " . $titreEvenement . "
"
            . "Date : " . $dateEvent . "
"
            . "Lieu : " . ($lieu !== "" ? $lieu : "À confirmer") . "

"
            . ($description !== "" ? $description : "Consultez la plateforme pour voir les détails de l’événement.") . "

"
            . "Connectez-vous à la plateforme pour consulter les détails.";

        if (sendPlatformEmail($emailMembre, $nomMembre, $sujetMail, $htmlBody, $plainBody))
        {
            $emailsEnvoyes++;
        }
    }

    return $emailsEnvoyes;
}

$mesClubsStatement = $pdo->prepare("
    SELECT id, nom_club, statut
    FROM clubs
    WHERE responsable_id = :responsable_id
      AND statut <> 'supprime'
    ORDER BY nom_club ASC
");
$mesClubsStatement->execute(["responsable_id" => $responsableId]);
$mesClubs = $mesClubsStatement->fetchAll(PDO::FETCH_ASSOC);

if ($clubId === 0 && !empty($mesClubs))
{
    $clubId = (int) $mesClubs[0]["id"];
}

$clubActuel = null;
foreach ($mesClubs as $clubOption)
{
    if ((int) $clubOption["id"] === $clubId)
    {
        $clubActuel = $clubOption;
        break;
    }
}

if ($clubId > 0 && !$clubActuel)
{
    $_SESSION["event_error"] = "Club introuvable.";
    header("Location: gestion_evenements.php");
    exit();
}

if ($deleteId > 0)
{
    $deleteQuery = $pdo->prepare("
        DELETE e
        FROM evenements e
        INNER JOIN clubs c ON e.club_id = c.id
        WHERE e.id = :id AND c.responsable_id = :responsable_id
    ");
    $deleteQuery->execute([
        "id" => $deleteId,
        "responsable_id" => $responsableId
    ]);

    $_SESSION["event_success"] = "Événement supprimé.";
    $redirectClub = $clubId > 0 ? "?club_id=" . $clubId : "";
    header("Location: gestion_evenements.php" . $redirectClub);
    exit();
}

if ($statusId > 0)
{
    $statusQuery = $pdo->prepare("
        SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.statut, e.club_id, c.nom_club
        FROM evenements e
        INNER JOIN clubs c ON e.club_id = c.id
        WHERE e.id = :id AND c.responsable_id = :responsable_id
    ");
    $statusQuery->execute([
        "id" => $statusId,
        "responsable_id" => $responsableId
    ]);
    $eventStatus = $statusQuery->fetch(PDO::FETCH_ASSOC);

    if ($eventStatus)
    {
        $newStatus = $eventStatus["statut"] === "actif" ? "annule" : "actif";

        $updateStatus = $pdo->prepare("UPDATE evenements SET statut = :statut WHERE id = :id");
        $updateStatus->execute([
            "statut" => $newStatus,
            "id" => $statusId
        ]);

        $emailsEnvoyes = notifierMembresClubEvenement(
            $pdo,
            (int) $eventStatus["club_id"],
            (string) $eventStatus["nom_club"],
            (string) $eventStatus["titre"],
            (string) $eventStatus["date_event"],
            (string) ($eventStatus["lieu"] ?? ""),
            (string) ($eventStatus["description"] ?? ""),
            $newStatus === "annule" ? "cancel" : "reactivate"
        );

        $_SESSION["event_success"] = "Statut de l’événement mis à jour. E-mails envoyés à " . $emailsEnvoyes . " membre(s).";
        header("Location: gestion_evenements.php?club_id=" . (int) $eventStatus["club_id"]);
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST")
{
    $action = $_POST["action"] ?? "";
    $titre = trim($_POST["titre"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $dateEvent = trim($_POST["date_event"] ?? "");
    $lieu = trim($_POST["lieu"] ?? "");
    $statut = $_POST["statut"] ?? "actif";
    $clubPostId = (int) ($_POST["club_id"] ?? 0);
    $eventId = (int) ($_POST["event_id"] ?? 0);

    $statutsAutorises = ["actif", "annule"];

    $checkClub = $pdo->prepare("
        SELECT id, nom_club
        FROM clubs
        WHERE id = :club_id
          AND responsable_id = :responsable_id
          AND statut <> 'supprime'
    ");
    $checkClub->execute([
        "club_id" => $clubPostId,
        "responsable_id" => $responsableId
    ]);
    $clubVerifie = $checkClub->fetch(PDO::FETCH_ASSOC);

    if (!$clubVerifie)
    {
        $_SESSION["event_error"] = "Club non autorisé.";
        header("Location: gestion_evenements.php");
        exit();
    }

    if ($titre === "" || $dateEvent === "" || !in_array($statut, $statutsAutorises, true))
    {
        $_SESSION["event_error"] = "Veuillez remplir les champs obligatoires de l’événement.";
        header("Location: gestion_evenements.php?club_id=" . $clubPostId . ($eventId > 0 ? "&edit=" . $eventId : ""));
        exit();
    }

    if ($action === "create")
    {
        $insert = $pdo->prepare("
            INSERT INTO evenements (club_id, titre, description, date_event, lieu, statut)
            VALUES (:club_id, :titre, :description, :date_event, :lieu, :statut)
        ");
        $insert->execute([
            "club_id" => $clubPostId,
            "titre" => $titre,
            "description" => $description,
            "date_event" => $dateEvent,
            "lieu" => $lieu,
            "statut" => $statut
        ]);

        $emailsEnvoyes = notifierMembresClubEvenement(
            $pdo,
            $clubPostId,
            (string) $clubVerifie["nom_club"],
            $titre,
            $dateEvent,
            $lieu,
            $description,
            "create"
        );

        $_SESSION["event_success"] = "Événement créé avec succès. Notifications envoyées aux membres et e-mails envoyés à " . $emailsEnvoyes . " membre(s).";
        header("Location: gestion_evenements.php?club_id=" . $clubPostId);
        exit();
    }

    if ($action === "update" && $eventId > 0)
    {
        $update = $pdo->prepare("
            UPDATE evenements e
            INNER JOIN clubs c ON e.club_id = c.id
            SET e.club_id = :club_id,
                e.titre = :titre,
                e.description = :description,
                e.date_event = :date_event,
                e.lieu = :lieu,
                e.statut = :statut
            WHERE e.id = :id AND c.responsable_id = :responsable_id
        ");
        $update->execute([
            "club_id" => $clubPostId,
            "titre" => $titre,
            "description" => $description,
            "date_event" => $dateEvent,
            "lieu" => $lieu,
            "statut" => $statut,
            "id" => $eventId,
            "responsable_id" => $responsableId
        ]);

        $emailsEnvoyes = notifierMembresClubEvenement(
            $pdo,
            $clubPostId,
            (string) $clubVerifie["nom_club"],
            $titre,
            $dateEvent,
            $lieu,
            $description,
            "update"
        );

        $_SESSION["event_success"] = "Événement mis à jour. Notifications envoyées aux membres et e-mails envoyés à " . $emailsEnvoyes . " membre(s).";
        header("Location: gestion_evenements.php?club_id=" . $clubPostId);
        exit();
    }
}

$eventToEdit = null;
if ($editId > 0)
{
    $editStatement = $pdo->prepare("
        SELECT e.*
        FROM evenements e
        INNER JOIN clubs c ON e.club_id = c.id
        WHERE e.id = :id AND c.responsable_id = :responsable_id
    ");
    $editStatement->execute([
        "id" => $editId,
        "responsable_id" => $responsableId
    ]);
    $eventToEdit = $editStatement->fetch(PDO::FETCH_ASSOC);

    if (!$eventToEdit)
    {
        $_SESSION["event_error"] = "Événement introuvable.";
        header("Location: gestion_evenements.php" . ($clubId > 0 ? "?club_id=" . $clubId : ""));
        exit();
    }

    $clubId = (int) $eventToEdit["club_id"];
}

$evenements = [];
if ($clubId > 0)
{
    $eventsStatement = $pdo->prepare("
        SELECT e.*, c.nom_club
        FROM evenements e
        INNER JOIN clubs c ON e.club_id = c.id
        WHERE c.responsable_id = :responsable_id
          AND e.club_id = :club_id
        ORDER BY e.date_event ASC, e.id DESC
    ");
    $eventsStatement->execute([
        "responsable_id" => $responsableId,
        "club_id" => $clubId
    ]);
    $evenements = $eventsStatement->fetchAll(PDO::FETCH_ASSOC);
}

$titrePage = "Gestion des événements";
include "includes/header.php";
?>

<section class="entete-page">
    <h1>Gestion des événements</h1>
    <p class="texte-gris">Créer, modifier et suivre les événements de vos clubs.</p>
</section>

<?php if (!empty($flashError)) : ?>
    <section class="carte"><p class="texte-gris" style="color:#a11212;"><?= htmlspecialchars($flashError) ?></p></section>
<?php endif; ?>

<?php if (!empty($flashSuccess)) : ?>
    <section class="carte"><p class="texte-gris" style="color:#0f7a39;"><?= htmlspecialchars($flashSuccess) ?></p></section>
<?php endif; ?>

<section class="carte">
    <h2>Choisir un club</h2>

    <?php if (empty($mesClubs)) : ?>
        <p class="texte-gris">Vous n’êtes responsable d’aucun club pour le moment.</p>
    <?php else : ?>
        <form method="get" class="ligne-filtres">
            <label for="club_id">Club :</label>
            <select name="club_id" id="club_id" class="champ" onchange="this.form.submit()">
                <?php foreach ($mesClubs as $clubOption) : ?>
                    <option value="<?= (int) $clubOption["id"] ?>" <?= (int) $clubOption["id"] === $clubId ? "selected" : "" ?>>
                        <?= htmlspecialchars($clubOption["nom_club"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</section>

<?php if ($clubId > 0) : ?>
<section class="grille-2">
    <section class="carte">
        <h2><?= $eventToEdit ? "Modifier l’événement" : "Créer un événement" ?></h2>

        <form method="post">
            <input type="hidden" name="action" value="<?= $eventToEdit ? "update" : "create" ?>">
            <input type="hidden" name="club_id" value="<?= (int) $clubId ?>">

            <?php if ($eventToEdit) : ?>
                <input type="hidden" name="event_id" value="<?= (int) $eventToEdit["id"] ?>">
            <?php endif; ?>

            <label for="titre">Titre</label>
            <input
                type="text"
                id="titre"
                name="titre"
                class="champ"
                required
                value="<?= htmlspecialchars($eventToEdit["titre"] ?? "") ?>"
            >

            <label for="description">Description</label>
            <textarea
                id="description"
                name="description"
                class="champ"
                rows="5"
            ><?= htmlspecialchars($eventToEdit["description"] ?? "") ?></textarea>

            <label for="date_event">Date</label>
            <input
                type="datetime-local"
                id="date_event"
                name="date_event"
                class="champ"
                required
                value="<?= !empty($eventToEdit["date_event"]) ? date('Y-m-d\\TH:i', strtotime($eventToEdit["date_event"])) : "" ?>"
            >

            <label for="lieu">Lieu</label>
            <input
                type="text"
                id="lieu"
                name="lieu"
                class="champ"
                value="<?= htmlspecialchars($eventToEdit["lieu"] ?? "") ?>"
            >

            <label for="statut">Statut</label>
            <select id="statut" name="statut" class="champ">
                <option value="actif" <?= (($eventToEdit["statut"] ?? "actif") === "actif") ? "selected" : "" ?>>Actif</option>
                <option value="annule" <?= (($eventToEdit["statut"] ?? "") === "annule") ? "selected" : "" ?>>Annulé</option>
            </select>

            <div class="carte-actions" style="margin-top:12px;">
                <button type="submit" class="bouton">
                    <?= $eventToEdit ? "Mettre à jour" : "Créer l’événement" ?>
                </button>

                <?php if ($eventToEdit) : ?>
                    <a href="gestion_evenements.php?club_id=<?= (int) $clubId ?>" class="bouton bouton-secondaire">
                        Annuler
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="carte">
        <h2>Liste des événements</h2>

        <?php if (empty($evenements)) : ?>
            <p class="texte-gris">Aucun événement enregistré pour ce club.</p>
        <?php else : ?>
            <div class="liste-cartes">
                <?php foreach ($evenements as $event) : ?>
                    <article class="carte" style="margin-bottom:12px;">
                        <div class="carte-titre">
                            <h3 style="margin:0;"><?= htmlspecialchars($event["titre"]) ?></h3>
                            <span class="badge <?= $event["statut"] === "actif" ? "badge-actif" : "badge-annule" ?>">
                                <?= htmlspecialchars($event["statut"]) ?>
                            </span>
                        </div>

                        <p class="texte-gris"><strong>Date :</strong> <?= htmlspecialchars($event["date_event"]) ?></p>
                        <p class="texte-gris"><strong>Lieu :</strong> <?= htmlspecialchars($event["lieu"] ?: "Non précisé") ?></p>

                        <?php if (!empty($event["description"])) : ?>
                            <p class="texte-gris"><?= nl2br(htmlspecialchars($event["description"])) ?></p>
                        <?php endif; ?>

                        <div class="carte-actions">
                            <a href="gestion_evenements.php?club_id=<?= (int) $clubId ?>&edit=<?= (int) $event["id"] ?>" class="bouton bouton-secondaire">
                                Modifier
                            </a>

                            <a href="gestion_evenements.php?club_id=<?= (int) $clubId ?>&toggle=<?= (int) $event["id"] ?>" class="bouton bouton-secondaire">
                                <?= $event["statut"] === "actif" ? "Annuler" : "Réactiver" ?>
                            </a>

                            <a
                                href="gestion_evenements.php?club_id=<?= (int) $clubId ?>&delete=<?= (int) $event["id"] ?>"
                                class="bouton bouton-danger"
                                onclick="return confirm('Supprimer cet événement ?');"
                            >
                                Supprimer
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
<?php endif; ?>

<?php include "includes/footer.php"; ?>