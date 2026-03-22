<?php

session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "responsable")
{
  header("Location: connexion.php");
  exit();
}

require "config/database.php";

if (!isset($_GET["id"]) || empty($_GET["id"]))
{
  header("Location: gestion_club.php");
  exit();
}

$id = (int) $_GET["id"];

if (isset($_POST["modifier_club"]))
{
  $nom_club    = trim($_POST["nom_club"]);
  $description = trim($_POST["description"]);
  $statut      = trim($_POST["statut"]);

  if (empty($nom_club) || empty($description) || empty($statut))
  {
    $_SESSION["club_error"] = "Veuillez remplir tous les champs.";
    header("Location: update_club.php?id=" . $id);
    exit();
  }

  $sqlQuery = "UPDATE clubs
    SET nom_club = :nom_club,
    description = :description,
    statut = :statut
    WHERE id = :id
    AND responsable_id = :responsable_id";

  $updateClubStatement = $pdo->prepare($sqlQuery);

  $updateClubStatement->execute([
    "nom_club" => $nom_club,
    "description" => $description,
    "statut" => $statut,
    "id" => $id,
    "responsable_id" => $_SESSION["id"]
  ]) or die(print_r($pdo->errorInfo()));

  $_SESSION["club_success"] = "Club modifié avec succès.";
  header("Location: gestion_club.php");
  exit();
}

$sqlQuery = "SELECT * FROM clubs
  WHERE id = :id
  AND responsable_id = :responsable_id ";

$clubStatement = $pdo->prepare($sqlQuery);

$clubStatement->execute([
  "id" => $id,
  "responsable_id" => $_SESSION["id"]
]) or die(print_r($pdo->errorInfo()));

$club = $clubStatement->fetch();

if (!$club)
{
  header("Location: gestion_club.php");
  exit();
}

$titrePage = "Modifier un club";

$club_error = $_SESSION["club_error"] ?? "";
unset($_SESSION["club_error"]);

include "includes/header.php";

?>

<section class="entete-page">
  <h1>Modifier le club</h1>
  <p class="texte-gris">
    Mettez à jour les informations du club.
  </p>
</section>

<section class="carte">

  <h2>Formulaire de modification</h2>

  <?php if (!empty($club_error)) : ?>
    <p class="texte-gris" style="color:#a11212;">
      <?= htmlspecialchars($club_error) ?>
    </p>
  <?php endif; ?>

  <form method="post" action="update_club.php?id=<?= htmlspecialchars($club["id"]) ?>">

    <label>Nom du club</label>
    <input
      class="champ"
      type="text"
      name="nom_club"
      value="<?= htmlspecialchars($club["nom_club"]) ?>"
      required
    >

    <label>Description</label>
    <textarea class="champ" name="description" rows="4" required><?= htmlspecialchars($club["description"]) ?></textarea>

    <label>Statut</label>
    <select class="champ" name="statut" required>
      <option value="attente" <?= $club["statut"] === "attente" ? "selected" : "" ?>>En attente</option>
      <option value="actif" <?= $club["statut"] === "actif" ? "selected" : "" ?>>Actif</option>
      <option value="inactif" <?= $club["statut"] === "inactif" ? "selected" : "" ?>>Inactif</option>
    </select>

    <div class="carte-actions">
      <button class="bouton" type="submit" name="modifier_club">
        Enregistrer les modifications
      </button>

      <a class="bouton bouton-secondaire" href="clubs.php">
        Retour
      </a>
    </div>

  </form>

</section>

<?php include "includes/footer.php"; ?>