<?php

session_start();
require_once "includes/lang.php";
require "config/database.php";

if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin")
{
  header("Location: admin.php");
  exit();
}

$titrePage = t("calendar");

$clubFiltre   = isset($_GET["club_id"]) ? (int) $_GET["club_id"] : 0;
$dateFiltre   = trim($_GET["date_event"] ?? "");
$searchFiltre = trim($_GET["q"] ?? "");

$clubsQuery = "
  SELECT id, nom_club
  FROM clubs
  WHERE statut = 'actif'
  ORDER BY nom_club ASC
";
$clubsStatement = $pdo->prepare($clubsQuery);
$clubsStatement->execute();
$clubsFiltres = $clubsStatement->fetchAll(PDO::FETCH_ASSOC);

$where = ["c.statut = 'actif'"];
$params = [];

if ($clubFiltre > 0)
{
  $where[] = "e.club_id = :club_id";
  $params["club_id"] = $clubFiltre;
}

if (!empty($dateFiltre))
{
  $where[] = "e.date_event = :date_event";
  $params["date_event"] = $dateFiltre;
}

if (!empty($searchFiltre))
{
  $where[] = "(
    e.titre LIKE :search
    OR e.description LIKE :search
    OR e.lieu LIKE :search
    OR c.nom_club LIKE :search
  )";
  $params["search"] = "%" . $searchFiltre . "%";
}

$evenementsQuery = "
  SELECT
    e.*,
    c.nom_club
  FROM evenements e
  INNER JOIN clubs c ON e.club_id = c.id
  WHERE " . implode(" AND ", $where) . "
  ORDER BY e.date_event ASC, e.id DESC
";

$evenementsStatement = $pdo->prepare($evenementsQuery);
$evenementsStatement->execute($params) or die(print_r($pdo->errorInfo()));
$evenements = $evenementsStatement->fetchAll(PDO::FETCH_ASSOC);

$statsQuery = "
  SELECT
    COUNT(*) AS total_evenements,
    SUM(CASE WHEN e.statut = 'actif' THEN 1 ELSE 0 END) AS total_actifs,
    SUM(CASE WHEN e.statut = 'annule' THEN 1 ELSE 0 END) AS total_annules
  FROM evenements e
  INNER JOIN clubs c ON e.club_id = c.id
  WHERE c.statut = 'actif'
";
$statsStatement = $pdo->prepare($statsQuery);
$statsStatement->execute();
$stats = $statsStatement->fetch(PDO::FETCH_ASSOC);

include "includes/header.php";

?>

<section class="entete-page">
  <h1><?= t("clubs_calendar") ?></h1>
  <p class="texte-gris">
    <?= t("global_events_view") ?>
  </p>
</section>

<section class="grille-3">
  <article class="carte">
    <h2><?= t("total_events") ?></h2>
    <p><strong><?= (int) ($stats["total_evenements"] ?? 0) ?></strong></p>
  </article>

  <article class="carte">
    <h2><?= t("active_events") ?></h2>
    <p><strong><?= (int) ($stats["total_actifs"] ?? 0) ?></strong></p>
  </article>

  <article class="carte">
    <h2><?= t("cancelled_events") ?></h2>
    <p><strong><?= (int) ($stats["total_annules"] ?? 0) ?></strong></p>
  </article>
</section>

<section class="carte">
  <h2><?= t("filters") ?></h2>

  <form method="get" action="calendrier.php" class="barre-filtres">
    <select class="champ" name="club_id">
      <option value="0"><?= t("all_clubs") ?></option>
      <?php foreach ($clubsFiltres as $clubFiltreOption) : ?>
        <option
          value="<?= (int) $clubFiltreOption["id"] ?>"
          <?= $clubFiltre === (int) $clubFiltreOption["id"] ? "selected" : "" ?>
        >
          <?= htmlspecialchars($clubFiltreOption["nom_club"]) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <input class="champ" type="date" name="date_event" value="<?= htmlspecialchars($dateFiltre) ?>">

    <input
      class="champ"
      type="search"
      name="q"
      placeholder="<?= t("search_event_placeholder") ?>"
      value="<?= htmlspecialchars($searchFiltre) ?>"
    >

    <button class="bouton" type="submit"><?= t("filter") ?></button>
    <a class="bouton bouton-secondaire" href="calendrier.php"><?= t("reset") ?></a>
  </form>
</section>

<section class="carte">

  <h2><?= t("events_list") ?></h2>

  <?php if (empty($evenements)) : ?>

    <p class="texte-gris">
      <?= t("no_event_with_filters") ?>
    </p>

  <?php else : ?>

    <table class="table">
      <thead>
        <tr>
          <th><?= t("date") ?></th>
          <th><?= t("event") ?></th>
          <th><?= t("club") ?></th>
          <th><?= t("location") ?></th>
          <th><?= t("status") ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($evenements as $evenement) : ?>
          <tr>
            <td><?= htmlspecialchars($evenement["date_event"]) ?></td>
            <td>
              <strong><?= htmlspecialchars($evenement["titre"]) ?></strong>
              <?php if (!empty($evenement["description"])) : ?>
                <br>
                <span class="texte-gris petit">
                  <?= nl2br(htmlspecialchars($evenement["description"])) ?>
                </span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($evenement["nom_club"]) ?></td>
            <td><?= htmlspecialchars($evenement["lieu"] ?: t("not_specified")) ?></td>
            <td>
              <?php if ($evenement["statut"] === "actif") : ?>
                <span class="badge badge-actif"><?= t("active") ?></span>
              <?php else : ?>
                <span class="badge badge-annule"><?= t("cancelled") ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <?php endif; ?>

</section>

<?php include "includes/footer.php"; ?>