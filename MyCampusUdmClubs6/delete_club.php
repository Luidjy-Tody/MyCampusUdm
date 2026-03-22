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

$sqlQuery = "
  SELECT *
  FROM clubs
  WHERE id = :id
    AND responsable_id = :responsable_id
";

$clubStatement = $pdo->prepare($sqlQuery);

$clubStatement->execute([
  "id"             => $id,
  "responsable_id" => $_SESSION["id"]
]) or die(print_r($pdo->errorInfo()));

$club = $clubStatement->fetch(PDO::FETCH_ASSOC);

if (!$club)
{
  $_SESSION["club_error"] = "Club introuvable ou accès refusé.";
  header("Location: gestion_club.php");
  exit();
}

if (isset($_POST["confirmer_suppression"]))
{
  $motdepasse = $_POST["motdepasse"];

  $userQuery = "
    SELECT *
    FROM utilisateurs
    WHERE id = :id
      AND role = 'responsable'
      AND statut = 'actif'
  ";

  $userStatement = $pdo->prepare($userQuery);

  $userStatement->execute([
    "id" => $_SESSION["id"]
  ]) or die(print_r($pdo->errorInfo()));

  $user = $userStatement->fetch(PDO::FETCH_ASSOC);

  if (!$user || !password_verify($motdepasse, $user["mot_de_passe_hash"]))
  {
    $_SESSION["club_error"] = "Mot de passe incorrect. Suppression annulée.";
    header("Location: delete_club.php?id=" . $id);
    exit();
  }

  $deleteQuery = "
    UPDATE clubs
    SET statut = 'supprime'
    WHERE id = :id
      AND responsable_id = :responsable_id
  ";

  $deleteClubStatement = $pdo->prepare($deleteQuery);

  $deleteClubStatement->execute([
    "id"             => $id,
    "responsable_id" => $_SESSION["id"]
  ]) or die(print_r($pdo->errorInfo()));

  $_SESSION["club_success"] = "Club supprimé avec succès.";
  header("Location: gestion_club.php");
  exit();
}

$titrePage = "Confirmer la suppression";

$club_error = $_SESSION["club_error"] ?? "";
unset($_SESSION["club_error"]);

include "includes/header.php";

?>

<section class="entete-page">
  <h1>Confirmer la suppression</h1>
  <p class="texte-gris">
    Pour supprimer ce club, veuillez confirmer votre mot de passe.
  </p>
</section>

<section class="carte">

  <h2>Club à supprimer</h2>

  <p><strong>Nom :</strong> <?= htmlspecialchars($club["nom_club"]) ?></p>
  <p><strong>Description :</strong> <?= htmlspecialchars($club["description"]) ?></p>
  <p><strong>Statut :</strong> <?= htmlspecialchars($club["statut"]) ?></p>

</section>

<section class="carte">

  <h2>Authentification requise</h2>

  <?php if (!empty($club_error)) : ?>
    <p class="texte-gris" style="color:#a11212;">
      <?= htmlspecialchars($club_error) ?>
    </p>
  <?php endif; ?>

  <form method="post" action="delete_club.php?id=<?= htmlspecialchars($club["id"]) ?>">

    <label>Mot de passe</label>
    <input class="champ" type="password" name="motdepasse" required>

    <div class="carte-actions">
      <button class="bouton bouton-secondaire" type="submit" name="confirmer_suppression">
        Confirmer la suppression
      </button>

      <a class="bouton" href="gestion_club.php">
        Annuler
      </a>
    </div>

  </form>

</section>

<?php include "includes/footer.php"; ?>