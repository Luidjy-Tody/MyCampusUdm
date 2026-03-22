<?php

session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "responsable")
{
  header("Location: connexion.php");
  exit();
}

require "config/database.php";

$responsableId = (int) $_SESSION["id"];
$clubId = isset($_GET["club_id"]) ? (int) $_GET["club_id"] : 0;
$editId = isset($_GET["edit"]) ? (int) $_GET["edit"] : 0;
$deleteId = isset($_GET["delete"]) ? (int) $_GET["delete"] : 0;
$statusId = isset($_GET["toggle"]) ? (int) $_GET["toggle"] : 0;

$flashError = $_SESSION["event_error"] ?? "";
$flashSuccess = $_SESSION["event_success"] ?? "";
unset($_SESSION["event_error"], $_SESSION["event_success"]);

$mesClubsStatement = $pdo->prepare("
  SELECT id, nom_club, statut
  FROM clubs
  WHERE responsable_id = :responsable_id
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
    SELECT e.id, e.statut, e.club_id
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
    $_SESSION["event_success"] = "Statut de l’événement mis à jour.";
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

  $checkClub = $pdo->prepare("SELECT id FROM clubs WHERE id = :club_id AND responsable_id = :responsable_id");
  $checkClub->execute([
    "club_id" => $clubPostId,
    "responsable_id" => $responsableId
  ]);

  if (!$checkClub->fetch())
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
    $_SESSION["event_success"] = "Événement créé avec succès.";
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
    $_SESSION["event_success"] = "Événement mis à jour.";
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
    <form method="get" action="gestion_evenements.php" class="barre-filtres">
      <select class="champ" name="club_id" required>
        <?php foreach ($mesClubs as $clubOption) : ?>
          <option value="<?= (int) $clubOption["id"] ?>" <?= $clubId === (int) $clubOption["id"] ? "selected" : "" ?>>
            <?= htmlspecialchars($clubOption["nom_club"]) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button class="bouton" type="submit">Afficher</button>
    </form>
  <?php endif; ?>
</section>

<?php if ($clubActuel) : ?>
  <section class="carte">
    <h2><?= $eventToEdit ? "Modifier l’événement" : "Créer un événement" ?></h2>

    <form method="post" action="gestion_evenements.php?club_id=<?= (int) $clubId ?><?= $eventToEdit ? '&edit=' . (int) $eventToEdit['id'] : '' ?>">
      <input type="hidden" name="action" value="<?= $eventToEdit ? 'update' : 'create' ?>">
      <input type="hidden" name="club_id" value="<?= (int) $clubId ?>">
      <input type="hidden" name="event_id" value="<?= (int) ($eventToEdit['id'] ?? 0) ?>">

      <label>Titre</label>
      <input class="champ" type="text" name="titre" required value="<?= htmlspecialchars($eventToEdit['titre'] ?? '') ?>">

      <label>Description</label>
      <textarea class="champ" name="description" rows="4"><?= htmlspecialchars($eventToEdit['description'] ?? '') ?></textarea>

      <div class="grille-3">
        <div>
          <label>Date</label>
          <input class="champ" type="date" name="date_event" required value="<?= htmlspecialchars($eventToEdit['date_event'] ?? '') ?>">
        </div>
        <div>
          <label>Lieu</label>
          <input class="champ" type="text" name="lieu" value="<?= htmlspecialchars($eventToEdit['lieu'] ?? '') ?>">
        </div>
        <div>
          <label>Statut</label>
          <select class="champ" name="statut" required>
            <option value="actif" <?= (($eventToEdit['statut'] ?? 'actif') === 'actif') ? 'selected' : '' ?>>Actif</option>
            <option value="annule" <?= (($eventToEdit['statut'] ?? '') === 'annule') ? 'selected' : '' ?>>Annulé</option>
          </select>
        </div>
      </div>

      <div class="carte-actions">
        <button class="bouton" type="submit"><?= $eventToEdit ? 'Mettre à jour' : 'Créer l’événement' ?></button>
        <?php if ($eventToEdit) : ?>
          <a class="bouton bouton-secondaire" href="gestion_evenements.php?club_id=<?= (int) $clubId ?>">Annuler la modification</a>
        <?php endif; ?>
      </div>
    </form>
  </section>

  <section class="carte">
    <h2>Événements du club</h2>

    <?php if (empty($evenements)) : ?>
      <p class="texte-gris">Aucun événement enregistré pour ce club.</p>
    <?php else : ?>
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Titre</th>
            <th>Lieu</th>
            <th>Statut</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($evenements as $evenement) : ?>
            <tr>
              <td><?= htmlspecialchars($evenement['date_event']) ?></td>
              <td>
                <strong><?= htmlspecialchars($evenement['titre']) ?></strong>
                <?php if (!empty($evenement['description'])) : ?>
                  <br><span class="texte-gris petit"><?= htmlspecialchars($evenement['description']) ?></span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($evenement['lieu'] ?: 'Non précisé') ?></td>
              <td>
                <?php if ($evenement['statut'] === 'actif') : ?>
                  <span class="badge badge-actif">ACTIF</span>
                <?php else : ?>
                  <span class="badge badge-annule">ANNULÉ</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="carte-actions">
                  <a class="bouton petit" href="gestion_evenements.php?club_id=<?= (int) $clubId ?>&edit=<?= (int) $evenement['id'] ?>">Modifier</a>
                  <a class="bouton bouton-secondaire petit" href="gestion_evenements.php?club_id=<?= (int) $clubId ?>&toggle=<?= (int) $evenement['id'] ?>">
                    <?= $evenement['statut'] === 'actif' ? 'Annuler' : 'Réactiver' ?>
                  </a>
                  <a
                    class="bouton bouton-secondaire petit"
                    href="gestion_evenements.php?club_id=<?= (int) $clubId ?>&delete=<?= (int) $evenement['id'] ?>"
                    onclick="return confirm('Voulez-vous vraiment supprimer cet événement ?');"
                  >
                    Supprimer
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
