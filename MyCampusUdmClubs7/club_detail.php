<?php

session_start();

$estConnecte = isset($_SESSION["id"]);
$estAdmin = $estConnecte && isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
$utilisateurId = $estConnecte ? (int) $_SESSION["id"] : 0;

require "config/database.php";

if (!isset($_GET["id"]) || empty($_GET["id"]))
{
  header("Location: clubs.php");
  exit();
}

$id = (int) $_GET["id"];

$sqlQuery = "
  SELECT c.*, u.nom, u.prenom
  FROM clubs c
  LEFT JOIN utilisateurs u ON c.responsable_id = u.id
  WHERE c.id = :id
";

$clubStatement = $pdo->prepare($sqlQuery);

$clubStatement->execute([
  "id" => $id
]) or die(print_r($pdo->errorInfo()));

$club = $clubStatement->fetch(PDO::FETCH_ASSOC);

if (!$club)
{
  $retour = (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") ? "admin.php" : "clubs.php";
  header("Location: " . $retour);
  exit();
}

if (($club["statut"] ?? "") === "supprime" && !$estAdmin)
{
  header("Location: clubs.php");
  exit();
}

$estResponsableDuClub = (
  isset($_SESSION["id"]) &&
  isset($_SESSION["role"]) &&
  $_SESSION["role"] === "responsable" &&
  (int) $club["responsable_id"] === (int) $_SESSION["id"]
);

$estMembre = false;

if (
  isset($_SESSION["id"]) &&
  isset($_SESSION["role"]) &&
  in_array($_SESSION["role"], ["etudiant", "responsable"], true)
)
{
  $membreQuery = "
    SELECT id
    FROM membres_club
    WHERE utilisateur_id = :utilisateur_id
      AND club_id = :club_id
  ";

  $membreStatement = $pdo->prepare($membreQuery);

  $membreStatement->execute([
    "utilisateur_id" => $_SESSION["id"],
    "club_id"        => $id
  ]) or die(print_r($pdo->errorInfo()));

  if ($membreStatement->fetch())
  {
    $estMembre = true;
  }
}

$demandeEnvoyee = false;

if (
  isset($_SESSION["id"]) &&
  isset($_SESSION["role"]) &&
  in_array($_SESSION["role"], ["etudiant", "responsable"], true)
)
{
  $demandeQuery = "
    SELECT id
    FROM demandes_adhesion
    WHERE utilisateur_id = :utilisateur_id
      AND club_id = :club_id
      AND statut = 'en_attente'
  ";

  $demandeStatement = $pdo->prepare($demandeQuery);

  $demandeStatement->execute([
    "utilisateur_id" => $_SESSION["id"],
    "club_id"        => $id
  ]) or die(print_r($pdo->errorInfo()));

  if ($demandeStatement->fetch())
  {
    $demandeEnvoyee = true;
  }
}

$peutVoirMembres = $estMembre || $estResponsableDuClub || $estAdmin;
$membres = [];

if ($peutVoirMembres)
{
  $membresQuery = "
    SELECT mc.*, u.nom, u.prenom
    FROM membres_club mc
    INNER JOIN utilisateurs u ON mc.utilisateur_id = u.id
    WHERE mc.club_id = :club_id
    ORDER BY mc.id DESC
  ";

  $membresStatement = $pdo->prepare($membresQuery);

  $membresStatement->execute([
    "club_id" => $id
  ]) or die(print_r($pdo->errorInfo()));

  $membres = $membresStatement->fetchAll(PDO::FETCH_ASSOC);
}

$evenementsQuery = "
  SELECT *
  FROM evenements
  WHERE club_id = :club_id
  ORDER BY date_event ASC
";

$evenementsStatement = $pdo->prepare($evenementsQuery);

$evenementsStatement->execute([
  "club_id" => $id
]) or die(print_r($pdo->errorInfo()));

$evenements = $evenementsStatement->fetchAll(PDO::FETCH_ASSOC);

$titrePage = "Détail du club";

include "includes/header.php";

?>

<section class="entete-page">

  <h1><?= htmlspecialchars($club["nom_club"]) ?></h1>

  <div class="ligne-info">

    <?php if ($club["statut"] === "actif") : ?>
      <span class="badge badge-actif">ACTIF</span>
    <?php elseif ($club["statut"] === "attente") : ?>
      <span class="badge badge-attente">EN ATTENTE</span>
    <?php else : ?>
      <span class="badge badge-annule">INACTIF</span>
    <?php endif; ?>

    <?php if (
       $estResponsableDuClub 
    ) : ?>
      <span class="badge badge-actif">MON CLUB</span>
    <?php endif; ?>

    <span class="texte-gris">
      Créé le : <?= htmlspecialchars($club["date_creation"] ?? "") ?>
    </span>

  </div>

  <p class="texte-gris">
    <?= nl2br(htmlspecialchars($club["description"])) ?>
  </p>

  <p class="petit texte-gris">
    Responsable :
    <?php
      if (!empty($club["prenom"]) || !empty($club["nom"]))
      {
        echo htmlspecialchars($club["prenom"] . " " . $club["nom"]);
      }
      else
      {
        echo "Non attribué";
      }
    ?>
  </p>

  <div class="bloc-actions">

    <?php if (!isset($_SESSION["id"])) : ?>

      <a class="bouton" href="connexion.php?form=register">
        S’inscrire
      </a>

    <?php elseif (
      in_array($_SESSION["role"], ["etudiant", "responsable"], true) &&
      !(
        $_SESSION["role"] === "responsable" &&
        (int) $club["responsable_id"] === (int) $_SESSION["id"]
      )
    ) : ?>

      <?php if ($estMembre) : ?>

        <span class="badge badge-actif">Vous êtes membre</span>

      <?php elseif ($demandeEnvoyee) : ?>

        <span class="badge badge-attente">Demande envoyée</span>

        <a
          class="bouton bouton-secondaire"
          href="annuler_demande.php?id=<?= htmlspecialchars($club["id"]) ?>"
          onclick="return confirm('Voulez-vous vraiment annuler cette demande ?');"
        >
          Annuler la demande
        </a>

      <?php else : ?>

        <a class="bouton" href="demande_adhesion.php?id=<?= htmlspecialchars($club["id"]) ?>">
          Demander adhésion
        </a>

      <?php endif; ?>

    <?php elseif (
      $_SESSION["role"] === "responsable" &&
      (int) $club["responsable_id"] === (int) $_SESSION["id"]
    ) : ?>

      <a class="bouton" href="gestion_club.php">
        Gérer ce club
      </a>

      <a class="bouton bouton-secondaire" href="update_club.php?id=<?= htmlspecialchars($club["id"]) ?>">
        Modifier
      </a>

    <?php endif; ?>

    <?php if ($estAdmin) : ?>
      <a class="bouton bouton-secondaire" href="admin.php">
        Retour à l’administration
      </a>
    <?php endif; ?>

  </div>

</section>

<section class="grille-2">

  <div class="carte">

    <h2>Membres</h2>

    <?php if (!$peutVoirMembres) : ?>

      <p class="texte-gris">
        Connectez-vous et rejoignez ce club pour consulter la liste des membres.
      </p>

    <?php elseif (empty($membres)) : ?>

      <p class="texte-gris">
        Aucun membre affiché pour le moment.
      </p>

    <?php else : ?>

      <table class="table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Rôle</th>
            <th>Date entrée</th>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($membres as $membre) : ?>
            <tr>
              <td><?= htmlspecialchars($membre["prenom"] . " " . $membre["nom"]) ?></td>
              <td><?= htmlspecialchars($membre["role_membre"]) ?></td>
              <td><?= htmlspecialchars($membre["date_entree"]) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php endif; ?>

  </div>

  <div class="carte">

    <h2>Événements</h2>

    <?php if (empty($evenements)) : ?>

      <p class="texte-gris">
        Aucun événement affiché pour le moment.
      </p>

    <?php else : ?>

      <ul class="liste-simple">
        <?php foreach ($evenements as $evenement) : ?>
          <li>
            <strong><?= htmlspecialchars($evenement["titre"]) ?></strong>
            — <?= htmlspecialchars($evenement["date_event"]) ?>

            <?php if ($evenement["statut"] === "actif") : ?>
              <span class="badge badge-actif">ACTIF</span>
            <?php else : ?>
              <span class="badge badge-annule">ANNULÉ</span>
            <?php endif; ?>

            <?php if (!empty($evenement["lieu"])) : ?>
              <br>
              <span class="texte-gris"><?= htmlspecialchars($evenement["lieu"]) ?></span>
            <?php endif; ?>

            <?php if (!empty($evenement["description"])) : ?>
              <br>
              <span class="texte-gris"><?= htmlspecialchars($evenement["description"]) ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>

    <?php endif; ?>

  </div>

</section>

<?php include "includes/footer.php"; ?>