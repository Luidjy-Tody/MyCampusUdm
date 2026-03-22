<?php

session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "responsable")
{
  header("Location: connexion.php");
  exit();
}

require "config/database.php";

if (
  !isset($_GET["id"]) || empty($_GET["id"]) ||
  !isset($_GET["club_id"]) || empty($_GET["club_id"])
)
{
  header("Location: gestion_club.php");
  exit();
}

$membre_id = (int) $_GET["id"];
$club_id   = (int) $_GET["club_id"];

# vérifier que le club appartient au responsable 
$clubQuery = "
  SELECT *
  FROM clubs
  WHERE id = :club_id
    AND responsable_id = :responsable_id
";

$clubStatement = $pdo->prepare($clubQuery);

$clubStatement->execute([
  "club_id"        => $club_id,
  "responsable_id" => $_SESSION["id"]
]) or die(print_r($pdo->errorInfo()));

$club = $clubStatement->fetch(PDO::FETCH_ASSOC);

if (!$club)
{
  header("Location: gestion_club.php");
  exit();
}

#récupérer le membre 
$membreQuery = "
  SELECT mc.*, u.nom, u.prenom, u.email
  FROM membres_club mc
  INNER JOIN utilisateurs u ON mc.utilisateur_id = u.id
  WHERE mc.id = :membre_id
    AND mc.club_id = :club_id
";

$membreStatement = $pdo->prepare($membreQuery);

$membreStatement->execute([
  "membre_id" => $membre_id,
  "club_id"   => $club_id
]) or die(print_r($pdo->errorInfo()));

$membre = $membreStatement->fetch(PDO::FETCH_ASSOC);

if (!$membre)
{
  header("Location: gestion_membres.php?id=" . $club_id);
  exit();
}

if (isset($_POST["modifier_role"]))
{
  $role_membre = $_POST["role_membre"];

  $rolesAutorises = ["president", "secretaire", "membre"];

  if (!in_array($role_membre, $rolesAutorises, true))
  {
    $_SESSION["membre_error"] = "Rôle invalide.";
    header("Location: changer_role_membre.php?id=" . $membre_id . "&club_id=" . $club_id);
    exit();
  }

  $updateQuery = "
    UPDATE membres_club
    SET role_membre = :role_membre
    WHERE id = :membre_id
      AND club_id = :club_id
  ";

  $updateStatement = $pdo->prepare($updateQuery);

  $updateStatement->execute([
    "role_membre" => $role_membre,
    "membre_id"   => $membre_id,
    "club_id"     => $club_id
  ]) or die(print_r($pdo->errorInfo()));

  $_SESSION["membre_success"] = "Rôle du membre mis à jour.";
  header("Location: gestion_membres.php?id=" . $club_id);
  exit();
}

$membre_error = $_SESSION["membre_error"] ?? "";
unset($_SESSION["membre_error"]);

$titrePage = "Changer le rôle du membre";

include "includes/header.php";

?>

<section class="entete-page">
  <h1>Changer le rôle</h1>
  <p class="texte-gris">
    Club : <?= htmlspecialchars($club["nom_club"]) ?>
  </p>
</section>

<section class="carte">

  <h2>Membre concerné</h2>

  <p><strong>Nom :</strong> <?= htmlspecialchars($membre["prenom"] . " " . $membre["nom"]) ?></p>
  <p><strong>Email :</strong> <?= htmlspecialchars($membre["email"]) ?></p>

  <?php if (!empty($membre_error)) : ?>
    <p class="texte-gris" style="color:#a11212;">
      <?= htmlspecialchars($membre_error) ?>
    </p>
  <?php endif; ?>

  <form method="post" action="changer_role_membre.php?id=<?= htmlspecialchars($membre_id) ?>&club_id=<?= htmlspecialchars($club_id) ?>">

    <label>Nouveau rôle</label>
    <select class="champ" name="role_membre" required>
      <option value="president" <?= $membre["role_membre"] === "president" ? "selected" : "" ?>>Président</option>
      <option value="secretaire" <?= $membre["role_membre"] === "secretaire" ? "selected" : "" ?>>Secrétaire</option>
      <option value="membre" <?= $membre["role_membre"] === "membre" ? "selected" : "" ?>>Membre</option>
    </select>

    <div class="carte-actions">
      <button class="bouton" type="submit" name="modifier_role">
        Enregistrer
      </button>

      <a class="bouton bouton-secondaire" href="gestion_membres.php?id=<?= htmlspecialchars($club_id) ?>">
        Retour
      </a>
    </div>

  </form>

</section>

<?php include "includes/footer.php"; ?>