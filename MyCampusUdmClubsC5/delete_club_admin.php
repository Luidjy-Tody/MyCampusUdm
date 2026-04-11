<?php

session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "admin")
{
  header("Location: connexion.php");
  exit();
}

require "config/database.php";

if (!isset($_GET["id"]) || empty($_GET["id"]))
{
  header("Location: admin.php");
  exit();
}

$id = (int) $_GET["id"];

$clubStatement = $pdo->prepare("
  SELECT c.*, u.nom, u.prenom
  FROM clubs c
  LEFT JOIN utilisateurs u ON c.responsable_id = u.id
  WHERE c.id = :id
    AND c.statut <> 'supprime'
");
$clubStatement->execute([
  "id" => $id
]);
$club = $clubStatement->fetch(PDO::FETCH_ASSOC);

if (!$club)
{
  $_SESSION["admin_error"] = "Club introuvable.";
  header("Location: admin.php");
  exit();
}

if (isset($_POST["confirmer_suppression"]))
{
  $motdepasse = $_POST["motdepasse"] ?? "";

  $userStatement = $pdo->prepare("
    SELECT *
    FROM utilisateurs
    WHERE id = :id
      AND role = 'admin'
      AND statut = 'actif'
  ");
  $userStatement->execute([
    "id" => $_SESSION["id"]
  ]);
  $user = $userStatement->fetch(PDO::FETCH_ASSOC);

  if (!$user || !password_verify($motdepasse, $user["mot_de_passe_hash"]))
  {
    $_SESSION["admin_error"] = "Mot de passe incorrect. Suppression annulée.";
    header("Location: delete_club_admin.php?id=" . $id);
    exit();
  }

  $deleteStatement = $pdo->prepare("
    UPDATE clubs
    SET statut = 'supprime'
    WHERE id = :id
  ");
  $deleteStatement->execute([
    "id" => $id
  ]);

  $_SESSION["admin_success"] = "Club supprimé avec succès.";
  header("Location: admin.php");
  exit();
}

$titrePage = "Supprimer un club";

$admin_error = $_SESSION["admin_error"] ?? "";
unset($_SESSION["admin_error"]);

include "includes/header.php";
?>

<section class="entete-page">
  <h1>Confirmer la suppression</h1>
  <p class="texte-gris">
    Pour supprimer ce club, veuillez confirmer votre mot de passe administrateur.
  </p>
</section>

<section class="carte">
  <h2>Club à supprimer</h2>

  <p><strong>Nom :</strong> <?= htmlspecialchars($club["nom_club"]) ?></p>
  <p><strong>Description :</strong> <?= htmlspecialchars($club["description"]) ?></p>
  <p><strong>Statut :</strong> <?= htmlspecialchars($club["statut"]) ?></p>
  <p><strong>Responsable :</strong>
    <?php
      if (!empty($club["prenom"]) || !empty($club["nom"]))
      {
        echo htmlspecialchars(trim(($club["prenom"] ?? "") . " " . ($club["nom"] ?? "")));
      }
      else
      {
        echo "Non attribué";
      }
    ?>
  </p>
</section>

<section class="carte">
  <h2>Authentification requise</h2>

  <?php if (!empty($admin_error)) : ?>
    <p class="texte-gris" style="color:#a11212;">
      <?= htmlspecialchars($admin_error) ?>
    </p>
  <?php endif; ?>

  <form method="post" action="delete_club_admin.php?id=<?= (int) $club["id"] ?>">
    <label>Mot de passe</label>
    <input class="champ" type="password" name="motdepasse" required>

    <div class="carte-actions">
      <button class="bouton bouton-secondaire" type="submit" name="confirmer_suppression">
        Confirmer la suppression
      </button>

      <a class="bouton" href="admin.php">
        Annuler
      </a>
    </div>
  </form>
</section>

<?php include "includes/footer.php"; ?>
