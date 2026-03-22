<?php

session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "responsable")
{
  header("Location: connexion.php");
  exit();
}

require "variables_club.php";
require "config/database.php";

$titrePage = "Mes clubs";

$club_error      = $_SESSION["club_error"] ?? "";
$club_success    = $_SESSION["club_success"] ?? "";
$demande_error   = $_SESSION["demande_error"] ?? "";
$demande_success = $_SESSION["demande_success"] ?? "";

unset($_SESSION["club_error"]);
unset($_SESSION["club_success"]);
unset($_SESSION["demande_error"]);
unset($_SESSION["demande_success"]);

$demandesQuery = "SELECT da.id, da.date_demande, da.statut, da.club_id, c.nom_club, u.id AS utilisateur_id, u.nom, u.prenom, u.email
  FROM demandes_adhesion da
  INNER JOIN clubs c ON da.club_id = c.id
  INNER JOIN utilisateurs u ON da.utilisateur_id = u.id
  WHERE c.responsable_id = :responsable_id AND 
  da.statut = 'en_attente'
  ORDER BY da.id DESC ";

$demandesStatement = $pdo->prepare($demandesQuery);

$demandesStatement->execute([
  "responsable_id" => $_SESSION["id"]
]) or die(print_r($pdo->errorInfo()));

$demandes = $demandesStatement->fetchAll(PDO::FETCH_ASSOC);

include "includes/header.php";

?>

<section class="entete-page">
  <h1>Mes clubs</h1>
  <p class="texte-gris">
    Créer, consulter et gérer les clubs dont vous êtes responsable.
  </p>
</section>

<section class="carte">

  <h2>Créer un club</h2>

  <?php if (!empty($club_error)) : ?>
    <p class="texte-gris" style="color:#a11212;">
      <?= htmlspecialchars($club_error) ?>
    </p>
  <?php endif; ?>

  <?php if (!empty($club_success)) : ?>
    <p class="texte-gris" style="color:#0f7a39;">
      <?= htmlspecialchars($club_success) ?>
    </p>
  <?php endif; ?>

  <form method="post" action="insert_club.php">

    <label>Nom du club</label>
    <input class="champ" type="text" name="nom_club" required>

    <label>Description</label>
    <textarea class="champ" name="description" rows="4" required></textarea>

    <label>Statut</label>
    <select class="champ" name="statut" required>
      <option value="">-- Choisir un statut --</option>
      <option value="attente">En attente</option>
      <option value="actif">Actif</option>
      <option value="inactif">Inactif</option>
    </select>

    <button class="bouton" type="submit" name="ajouter_club">
      Ajouter le club
    </button>

  </form>

</section>

<section class="carte">

  <h2>Demandes d’adhésion</h2>

  <?php if (!empty($demande_error)) : ?>
    <p class="texte-gris" style="color:#a11212;">
      <?= htmlspecialchars($demande_error) ?>
    </p>
  <?php endif; ?>

  <?php if (!empty($demande_success)) : ?>
    <p class="texte-gris" style="color:#0f7a39;">
      <?= htmlspecialchars($demande_success) ?>
    </p>
  <?php endif; ?>

  <?php if (empty($demandes)) : ?>

    <p class="texte-gris">
      Aucune demande en attente pour le moment.
    </p>

  <?php else : ?>

    <table class="table">
      <thead>
        <tr>
          <th>Club</th>
          <th>Étudiant</th>
          <th>Email</th>
          <th>Date demande</th>
          <th>Action</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($demandes as $demande) : ?>
          <tr>
            <td><?= htmlspecialchars($demande["nom_club"]) ?></td>
            <td><?= htmlspecialchars($demande["prenom"] . " " . $demande["nom"]) ?></td>
            <td><?= htmlspecialchars($demande["email"]) ?></td>
            <td><?= htmlspecialchars($demande["date_demande"]) ?></td>
            <td>
              <div class="carte-actions">
                <a class="bouton petit" href="accepter_demande.php?id=<?= htmlspecialchars($demande["id"]) ?>">
                  Accepter
                </a>

                <a
                  class="bouton bouton-secondaire petit"
                  href="refuser_demande.php?id=<?= htmlspecialchars($demande["id"]) ?>"
                  onclick="return confirm('Voulez-vous vraiment refuser cette demande ?');"
                >
                  Refuser
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <?php endif; ?>

</section>

<section class="carte">

  <h2>Liste de mes clubs</h2>

  <?php if (empty($clubs)) : ?>

    <p class="texte-gris">
      Vous n’avez encore créé aucun club.
    </p>

  <?php else : ?>

    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nom du club</th>
          <th>Description</th>
          <th>Statut</th>
          <th>Date création</th>
          <th>Action</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($clubs as $club) : ?>
          <tr>
            <td><?= htmlspecialchars($club["id"]) ?></td>
            <td><?= htmlspecialchars($club["nom_club"]) ?></td>
            <td><?= htmlspecialchars($club["description"]) ?></td>
            <td><?= htmlspecialchars($club["statut"]) ?></td>
            <td><?= htmlspecialchars($club["date_creation"]) ?></td>
            <td>
              <div class="carte-actions">
                <a class="bouton petit" href="club_detail.php?id=<?= htmlspecialchars($club["id"]) ?>">
                  Voir
                </a>

                <a class="bouton petit" href="update_club.php?id=<?= htmlspecialchars($club["id"]) ?>">
                  Modifier
                </a>

                <a class="bouton petit" href="gestion_membres.php?id=<?= htmlspecialchars($club["id"]) ?>">
                  Membres
                </a>

                <a class="bouton petit" href="gestion_evenements.php?club_id=<?= htmlspecialchars($club["id"]) ?>">
                  Événements
                </a>

                <a class="bouton bouton-secondaire petit" href="delete_club.php?id=<?= htmlspecialchars($club["id"]) ?>">
                  Supprimer
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <?php endif; ?>

</section>

<?php include "includes/footer.php"; ?>