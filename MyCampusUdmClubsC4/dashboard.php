<?php

session_start();

if (!isset($_SESSION["id"]))
{
  header("Location: connexion.php");
  exit();
}

if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin")
{
  header("Location: admin.php");
  exit();
}

require "config/database.php";
require_once "includes/app_helpers.php";

$titrePage = "Tableau de bord";
$userId = (int) $_SESSION["id"];

$statsStatement = $pdo->prepare("
  SELECT
    (SELECT COUNT(*) FROM membres_club WHERE utilisateur_id = :user_id) AS total_clubs,
    (SELECT COUNT(*) FROM demandes_adhesion WHERE utilisateur_id = :user_id AND statut = 'en_attente') AS demandes_attente,
    (SELECT COUNT(*) FROM evenements e
      INNER JOIN membres_club mc ON e.club_id = mc.club_id
      INNER JOIN clubs c ON e.club_id = c.id
      WHERE mc.utilisateur_id = :user_id
        AND e.statut = 'actif'
        AND e.date_event >= CURDATE()
        AND c.statut <> 'supprime') AS evenements_a_venir
");
$statsStatement->execute(["user_id" => $userId]);
$stats = $statsStatement->fetch(PDO::FETCH_ASSOC);

$clubsStatement = $pdo->prepare("
  SELECT
    mc.club_id,
    c.id,
    c.nom_club,
    c.description,
    c.statut AS statut_club,
    mc.role_membre,
    mc.date_entree
  FROM membres_club mc
  LEFT JOIN clubs c ON mc.club_id = c.id
  WHERE mc.utilisateur_id = :user_id
  ORDER BY mc.id DESC
");
$clubsStatement->execute(["user_id" => $userId]);
$mesClubs = $clubsStatement->fetchAll(PDO::FETCH_ASSOC);

$demandesStatement = $pdo->prepare("
  SELECT da.*, c.nom_club
  FROM demandes_adhesion da
  INNER JOIN clubs c ON da.club_id = c.id
  WHERE da.utilisateur_id = :user_id
    AND c.statut <> 'supprime'
  ORDER BY da.id DESC
");
$demandesStatement->execute(["user_id" => $userId]);
$mesDemandes = $demandesStatement->fetchAll(PDO::FETCH_ASSOC);

$evenementsStatement = $pdo->prepare("
  SELECT DISTINCT e.*, c.nom_club
  FROM evenements e
  INNER JOIN clubs c ON e.club_id = c.id
  LEFT JOIN membres_club mc ON e.club_id = mc.club_id
  WHERE e.statut = 'actif'
    AND e.date_event >= CURDATE()
    AND c.statut <> 'supprime'
    AND (
      mc.utilisateur_id = :user_id
      OR c.responsable_id = :user_id
    )
  ORDER BY e.date_event ASC, e.id DESC
  LIMIT 10
");
$evenementsStatement->execute(["user_id" => $userId]);
$evenements = $evenementsStatement->fetchAll(PDO::FETCH_ASSOC);

include "includes/header.php";
?>

<section class="entete-page bonjour-zone">
  <h1 class="bonjour-anime">
    Bonjour <?= htmlspecialchars(currentUserDisplayName()) ?>
  </h1>

  <p class="texte-gris">
    Bienvenue sur votre tableau de bord MyCampusUDM.
  </p>
</section>

<section class="grille-3">
  <article class="carte">
    <h2>Mes clubs</h2>
    <p><strong><?= (int) ($stats["total_clubs"] ?? 0) ?></strong></p>
  </article>
  <article class="carte">
    <h2>Mes demandes en attente</h2>
    <p><strong><?= (int) ($stats["demandes_attente"] ?? 0) ?></strong></p>
  </article>
  <article class="carte">
    <h2>Événements à venir</h2>
    <p><strong><?= (int) ($stats["evenements_a_venir"] ?? 0) ?></strong></p>
  </article>
</section>

<section class="carte">
  <h2>Mes clubs</h2>

  <?php if (empty($mesClubs)) : ?>
    <p class="texte-gris">Aucun club pour le moment.</p>
  <?php else : ?>
    <table class="table">
      <thead>
        <tr>
          <th>Club</th>
          <th>Rôle</th>
          <th>Date entrée</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($mesClubs as $club) : ?>
          <?php $clubSupprime = !isset($club["statut_club"]) || $club["statut_club"] === "supprime"; ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($club["nom_club"] ?? "Club supprimé") ?></strong>
              <?php if ($clubSupprime) : ?>
                <br><span class="texte-gris petit">Statut : Club supprimé</span>
              <?php elseif (!empty($club["description"])) : ?>
                <br><span class="texte-gris petit"><?= htmlspecialchars($club["description"]) ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($club["role_membre"]) ?></td>
            <td><?= htmlspecialchars($club["date_entree"]) ?></td>
            <td>
              <?php if ($clubSupprime) : ?>
                <span class="texte-gris petit">Club supprimé</span>
              <?php else : ?>
                <a class="bouton petit" href="club_detail.php?id=<?= (int) $club["id"] ?>">Voir</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="carte">
  <h2>Mes demandes</h2>

  <?php if (empty($mesDemandes)) : ?>
    <p class="texte-gris">Aucune demande pour le moment.</p>
  <?php else : ?>
    <table class="table">
      <thead>
        <tr>
          <th>Club</th>
          <th>Statut</th>
          <th>Date demande</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($mesDemandes as $demande) : ?>
          <tr>
            <td><?= htmlspecialchars($demande["nom_club"]) ?></td>
            <td><?= htmlspecialchars($demande["statut"]) ?></td>
            <td><?= htmlspecialchars($demande["date_demande"]) ?></td>
            <td>
              <?php if ($demande["statut"] === "en_attente") : ?>
                <a
                  class="bouton bouton-secondaire petit"
                  href="annuler_demande.php?id=<?= (int) $demande["club_id"] ?>"
                  onclick="return confirm('Voulez-vous vraiment annuler cette demande ?');"
                >
                  Annuler
                </a>
              <?php else : ?>
                <span class="texte-gris petit">Aucune action</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="carte">
  <h2>Événements à venir</h2>

  <?php if (empty($evenements)) : ?>
    <p class="texte-gris">Aucun événement disponible.</p>
  <?php else : ?>
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Événement</th>
          <th>Club</th>
          <th>Lieu</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($evenements as $evenement) : ?>
          <tr>
            <td><?= htmlspecialchars($evenement["date_event"]) ?></td>
            <td>
              <strong><?= htmlspecialchars($evenement["titre"]) ?></strong>
              <?php if (!empty($evenement["description"])) : ?>
                <br><span class="texte-gris petit"><?= htmlspecialchars($evenement["description"]) ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($evenement["nom_club"]) ?></td>
            <td><?= htmlspecialchars($evenement["lieu"] ?: "Non précisé") ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php include "includes/footer.php"; ?>
