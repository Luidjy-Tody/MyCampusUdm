<?php

session_start();
require_once "includes/lang.php";

require "config/database.php";

if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin")
{
  header("Location: admin.php");
  exit();
}

$titrePage = t("clubs");

$demande_success = $_SESSION["demande_success"] ?? "";
$demande_error   = $_SESSION["demande_error"] ?? "";

unset($_SESSION["demande_success"]);
unset($_SESSION["demande_error"]);

$sqlQuery = "
  SELECT c.*, u.nom, u.prenom
  FROM clubs c
  LEFT JOIN utilisateurs u ON c.responsable_id = u.id
  WHERE c.statut = 'actif'
  ORDER BY c.id DESC
";

$clubsStatement = $pdo->prepare($sqlQuery);
$clubsStatement->execute() or die(print_r($pdo->errorInfo()));

$clubs = $clubsStatement->fetchAll(PDO::FETCH_ASSOC);

$mesMembreships = [];

if (
  isset($_SESSION["id"]) &&
  isset($_SESSION["role"]) &&
  in_array($_SESSION["role"], ["etudiant", "responsable"], true)
)
{
  $membresQuery = "
    SELECT club_id
    FROM membres_club
    WHERE utilisateur_id = :utilisateur_id
  ";

  $membresStatement = $pdo->prepare($membresQuery);

  $membresStatement->execute([
    "utilisateur_id" => $_SESSION["id"]
  ]) or die(print_r($pdo->errorInfo()));

  $membresData = $membresStatement->fetchAll(PDO::FETCH_ASSOC);

  foreach ($membresData as $membre)
  {
    $mesMembreships[] = (int) $membre["club_id"];
  }
}

$mesDemandes = [];

if (
  isset($_SESSION["id"]) &&
  isset($_SESSION["role"]) &&
  in_array($_SESSION["role"], ["etudiant", "responsable"], true)
)
{
  $demandesQuery = "
    SELECT club_id
    FROM demandes_adhesion
    WHERE utilisateur_id = :utilisateur_id
      AND statut = 'en_attente'
  ";

  $demandesStatement = $pdo->prepare($demandesQuery);

  $demandesStatement->execute([
    "utilisateur_id" => $_SESSION["id"]
  ]) or die(print_r($pdo->errorInfo()));

  $demandesData = $demandesStatement->fetchAll(PDO::FETCH_ASSOC);

  foreach ($demandesData as $demande)
  {
    $mesDemandes[] = (int) $demande["club_id"];
  }
}

include "includes/header.php";

?>

<section class="entete-page">
  <h1><?= t("student_clubs") ?></h1>
  <p class="texte-gris">
    <?= t("discover_clubs") ?>
  </p>
</section>

<?php if (!empty($demande_success)) : ?>
  <section class="carte">
    <p class="texte-gris" style="color:#0f7a39;">
      <?= htmlspecialchars($demande_success) ?>
    </p>
  </section>
<?php endif; ?>

<?php if (!empty($demande_error)) : ?>
  <section class="carte">
    <p class="texte-gris" style="color:#a11212;">
      <?= htmlspecialchars($demande_error) ?>
    </p>
  </section>
<?php endif; ?>

<?php if (!isset($_SESSION["id"])) : ?>

  <section class="carte">

    <h2><?= t("join_a_club") ?></h2>

    <p class="texte-gris">
      <?= t("create_account_join") ?>
    </p>

    <div class="carte-actions">
      <a class="bouton" href="connexion.php?form=register"><?= t("sign_up") ?></a>
    </div>

  </section>

<?php endif; ?>

<section class="liste-cartes">

  <?php if (empty($clubs)) : ?>

    <article class="carte">

      <h2><?= t("club_list") ?></h2>

      <p class="texte-gris">
        <?= t("no_club_displayed") ?>
      </p>

    </article>

  <?php else : ?>

    <?php foreach ($clubs as $club) : ?>

      <article class="carte">

        <div class="carte-titre">
          <h2><?= htmlspecialchars($club["nom_club"]) ?></h2>

          <div class="carte-actions">

            <?php if ($club["statut"] === "actif") : ?>
              <span class="badge badge-actif"><?= t("active") ?></span>
            <?php elseif ($club["statut"] === "attente") : ?>
              <span class="badge badge-attente"><?= t("pending") ?></span>
            <?php else : ?>
              <span class="badge badge-annule"><?= t("inactive") ?></span>
            <?php endif; ?>

            <?php if (
              isset($_SESSION["id"]) &&
              isset($_SESSION["role"]) &&
              $_SESSION["role"] === "responsable" &&
              (int) $club["responsable_id"] === (int) $_SESSION["id"]
            ) : ?>
              <span class="badge badge-actif"><?= t("my_club") ?></span>
            <?php endif; ?>

          </div>
        </div>

        <p class="texte-gris">
          <?= nl2br(htmlspecialchars($club["description"])) ?>
        </p>

        <?php if (!empty($club["categorie"])) : ?>
        <p class="petit texte-gris"><?= t("category") ?> : <?= htmlspecialchars($club["categorie"]) ?></p>
        <?php endif; ?>

        <p class="petit texte-gris">
          <?= t("manager") ?> :
          <?php
            if (!empty($club["prenom"]) || !empty($club["nom"]))
            {
              echo htmlspecialchars($club["prenom"] . " " . $club["nom"]);
            }
            else
            {
              echo t("not_assigned");
            }
          ?>
        </p>

        <p class="petit texte-gris">
          <?= t("creation_date") ?> : <?= htmlspecialchars($club["date_creation"] ?? "") ?>
        </p>

        <div class="carte-actions">

          <a class="bouton" href="club_detail.php?id=<?= htmlspecialchars($club["id"]) ?>">
            <?= t("view_details") ?>
          </a>

          <?php if (!isset($_SESSION["id"])) : ?>

            <a class="bouton bouton-secondaire" href="connexion.php?form=register">
              <?= t("sign_up") ?>
            </a>

          <?php elseif (
            in_array($_SESSION["role"], ["etudiant", "responsable"], true) &&
            !(
              $_SESSION["role"] === "responsable" &&
              (int) $club["responsable_id"] === (int) $_SESSION["id"]
            )
          ) : ?>

            <?php if (in_array((int) $club["id"], $mesMembreships, true)) : ?>

              <span class="badge badge-actif"><?= t("you_are_member") ?></span>

            <?php elseif (in_array((int) $club["id"], $mesDemandes, true)) : ?>

              <span class="badge badge-attente"><?= t("request_sent") ?></span>

              <a
                class="bouton bouton-secondaire"
                href="annuler_demande.php?id=<?= htmlspecialchars($club["id"]) ?>"
                onclick="return confirm('<?= t("cancel_request_confirm") ?>');"
              >
                <?= t("cancel_request") ?>
              </a>

            <?php else : ?>

              <a class="bouton bouton-secondaire" href="demande_adhesion.php?id=<?= htmlspecialchars($club["id"]) ?>">
                <?= t("request_membership") ?>
              </a>

            <?php endif; ?>

          <?php elseif (
            isset($_SESSION["role"]) &&
            $_SESSION["role"] === "responsable" &&
            (int) $club["responsable_id"] === (int) $_SESSION["id"]
          ) : ?>

            <a class="bouton bouton-secondaire" href="update_club.php?id=<?= htmlspecialchars($club["id"]) ?>">
              <?= t("edit") ?>
            </a>

            <a class="bouton bouton-secondaire" href="gestion_club.php">
              <?= t("manage") ?>
            </a>

          <?php endif; ?>

        </div>

      </article>

    <?php endforeach; ?>

  <?php endif; ?>

</section>

<?php include "includes/footer.php"; ?>