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

$club_id = (int) $_GET["id"];

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

$membresQuery = "
  SELECT
    mc.id,
    mc.role_membre,
    mc.date_entree,
    u.id AS utilisateur_id,
    u.nom,
    u.prenom,
    u.email
  FROM membres_club mc
  INNER JOIN utilisateurs u ON mc.utilisateur_id = u.id
  WHERE mc.club_id = :club_id
  ORDER BY mc.id DESC
";

$membresStatement = $pdo->prepare($membresQuery);

$membresStatement->execute([
  "club_id" => $club_id
]) or die(print_r($pdo->errorInfo()));

$membres = $membresStatement->fetchAll(PDO::FETCH_ASSOC);

$membre_error   = $_SESSION["membre_error"] ?? "";
$membre_success = $_SESSION["membre_success"] ?? "";

unset($_SESSION["membre_error"]);
unset($_SESSION["membre_success"]);

$titrePage = "Gestion des membres";

include "includes/header.php";

?>

<section class="entete-page">
  <h1>Gestion des membres</h1>
  <p class="texte-gris">
    Club : <?= htmlspecialchars($club["nom_club"]) ?>
  </p>
</section>

<section class="carte">

  <h2>Membres du club</h2>

  <?php if (!empty($membre_error)) : ?>
    <p class="texte-gris" style="color:#a11212;">
      <?= htmlspecialchars($membre_error) ?>
    </p>
  <?php endif; ?>

  <?php if (!empty($membre_success)) : ?>
    <p class="texte-gris" style="color:#0f7a39;">
      <?= htmlspecialchars($membre_success) ?>
    </p>
  <?php endif; ?>

  <?php if (empty($membres)) : ?>

    <p class="texte-gris">
      Aucun membre dans ce club pour le moment.
    </p>

  <?php else : ?>

    <table class="table">
      <thead>
        <tr>
          <th>Nom</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Date entrée</th>
          <th>Action</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($membres as $membre) : ?>
          <tr>
            <td><?= htmlspecialchars($membre["prenom"] . " " . $membre["nom"]) ?></td>
            <td><?= htmlspecialchars($membre["email"]) ?></td>
            <td><?= htmlspecialchars($membre["role_membre"]) ?></td>
            <td><?= htmlspecialchars($membre["date_entree"]) ?></td>
            <td>
              <div class="carte-actions">
                <a class="bouton petit" href="changer_role_membre.php?id=<?= htmlspecialchars($membre["id"]) ?>&club_id=<?= htmlspecialchars($club_id) ?>">
                  Changer rôle
                </a>

                <a
                  class="bouton bouton-secondaire petit"
                  href="retirer_membre.php?id=<?= htmlspecialchars($membre["id"]) ?>&club_id=<?= htmlspecialchars($club_id) ?>"
                  onclick="return confirm('Voulez-vous vraiment retirer ce membre ?');"
                >
                  Retirer
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <?php endif; ?>

  <div class="carte-actions">
    <a class="bouton bouton-secondaire" href="gestion_club.php">
      Retour à mes clubs
    </a>
  </div>

</section>

<?php include "includes/footer.php"; ?>