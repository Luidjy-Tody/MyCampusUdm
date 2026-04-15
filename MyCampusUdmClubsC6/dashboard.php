<?php

session_start();
require_once "includes/lang.php";

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

$titrePage = t("dashboard");
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
    <?= t("hello") ?> <?= htmlspecialchars(currentUserDisplayName()) ?>
  </h1>

  <p class="texte-gris">
    <?= t("welcome_dashboard") ?>
  </p>
</section>

<section class="grille-3">
  <article class="carte">
    <h2><?= t("my_clubs_count") ?></h2>
    <p><strong><?= (int) ($stats["total_clubs"] ?? 0) ?></strong></p>
  </article>
  <article class="carte">
    <h2><?= t("my_pending_requests") ?></h2>
    <p><strong><?= (int) ($stats["demandes_attente"] ?? 0) ?></strong></p>
  </article>
  <article class="carte">
    <h2><?= t("upcoming_events") ?></h2>
    <p><strong><?= (int) ($stats["evenements_a_venir"] ?? 0) ?></strong></p>
  </article>
</section>

<section class="carte">
  <h2><?= t("my_clubs_count") ?></h2>

  <?php if (empty($mesClubs)) : ?>
    <p class="texte-gris"><?= t("no_club_yet") ?></p>
  <?php else : ?>
    <table class="table">
      <thead>
        <tr>
          <th><?= t("club") ?></th>
          <th><?= t("role") ?></th>
          <th><?= t("join_date") ?></th>
          <th><?= t("action") ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($mesClubs as $club) : ?>
          <?php $clubSupprime = !isset($club["statut_club"]) || $club["statut_club"] === "supprime"; ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($club["nom_club"] ?? t("deleted_club")) ?></strong>
              <?php if ($clubSupprime) : ?>
                <br><span class="texte-gris petit"><?= t("status_deleted_club") ?></span>
              <?php elseif (!empty($club["description"])) : ?>
                <br><span class="texte-gris petit"><?= htmlspecialchars($club["description"]) ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($club["role_membre"]) ?></td>
            <td><?= htmlspecialchars($club["date_entree"]) ?></td>
            <td>
              <?php if ($clubSupprime) : ?>
                <span class="texte-gris petit"><?= t("deleted_club") ?></span>
              <?php else : ?>
                <a class="bouton petit" href="club_detail.php?id=<?= (int) $club["id"] ?>"><?= t("view") ?></a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="carte">
  <h2><?= t("my_requests") ?></h2>

  <?php if (empty($mesDemandes)) : ?>
    <p class="texte-gris"><?= t("no_request_yet") ?></p>
  <?php else : ?>
    <table class="table">
      <thead>
        <tr>
          <th><?= t("club") ?></th>
          <th><?= t("status") ?></th>
          <th><?= t("request_date") ?></th>
          <th><?= t("action") ?></th>
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
                  onclick="return confirm('<?= t("cancel_request_confirm") ?>');"
                >
                  <?= t("cancel") ?>
                </a>
              <?php else : ?>
                <span class="texte-gris petit"><?= t("no_action") ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="carte">
  <h2><?= t("upcoming_events") ?></h2>

  <?php if (empty($evenements)) : ?>
    <p class="texte-gris"><?= t("no_event_available") ?></p>
  <?php else : ?>
    <table class="table">
      <thead>
        <tr>
          <th><?= t("date") ?></th>
          <th><?= t("event") ?></th>
          <th><?= t("club") ?></th>
          <th><?= t("location") ?></th>
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
            <td><?= htmlspecialchars($evenement["lieu"] ?: t("not_specified")) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php include "includes/footer.php"; ?>