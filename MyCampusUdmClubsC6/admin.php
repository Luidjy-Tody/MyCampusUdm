<?php

session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "admin")
{
  header("Location: connexion.php");
  exit();
}

require "config/database.php";

$titrePage = "Administration";

$admin_error = $_SESSION["admin_error"] ?? "";
$admin_success = $_SESSION["admin_success"] ?? "";
unset($_SESSION["admin_error"], $_SESSION["admin_success"]);

$statsStatement = $pdo->prepare("
  SELECT
    (SELECT COUNT(*) FROM clubs WHERE statut <> 'supprime') AS total_clubs,
    (SELECT COUNT(*) FROM clubs WHERE statut = 'attente') AS clubs_attente,
    (SELECT COUNT(*) FROM utilisateurs) AS total_utilisateurs,
    (SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif') AS utilisateurs_actifs,
    (SELECT COUNT(*) FROM evenements) AS total_evenements
");
$statsStatement->execute();
$stats = $statsStatement->fetch(PDO::FETCH_ASSOC);

$clubsStatement = $pdo->prepare("
  SELECT c.*, u.nom, u.prenom
  FROM clubs c
  LEFT JOIN utilisateurs u ON c.responsable_id = u.id
  WHERE c.statut <> 'supprime'
  ORDER BY
    CASE c.statut WHEN 'attente' THEN 0 WHEN 'actif' THEN 1 ELSE 2 END,
    c.id DESC
");
$clubsStatement->execute();
$clubs = $clubsStatement->fetchAll(PDO::FETCH_ASSOC);

$usersStatement = $pdo->prepare("
  SELECT u.*,
         (SELECT COUNT(*) FROM clubs c WHERE c.responsable_id = u.id) AS total_clubs_responsable,
         (SELECT COUNT(*) FROM membres_club mc WHERE mc.utilisateur_id = u.id) AS total_clubs_membre
  FROM utilisateurs u
  ORDER BY u.id DESC
");
$usersStatement->execute();
$utilisateurs = $usersStatement->fetchAll(PDO::FETCH_ASSOC);

include "includes/header.php";
?>

<section class="entete-page">
  <h1>Administration</h1>
  <p class="texte-gris">
    Validation des clubs, gestion des utilisateurs et suivi global de la plateforme.
  </p>
</section>

<?php if (!empty($admin_error)) : ?>
  <section class="carte"><p class="texte-gris" style="color:#a11212;"><?= htmlspecialchars($admin_error) ?></p></section>
<?php endif; ?>
<?php if (!empty($admin_success)) : ?>
  <section class="carte"><p class="texte-gris" style="color:#0f7a39;"><?= htmlspecialchars($admin_success) ?></p></section>
<?php endif; ?>

<section class="grille-3">
  <article class="carte"><h2>Clubs</h2><p><strong><?= (int) $stats["total_clubs"] ?></strong></p></article>
  <article class="carte"><h2>Clubs en attente</h2><p><strong><?= (int) $stats["clubs_attente"] ?></strong></p></article>
  <article class="carte"><h2>Utilisateurs</h2><p><strong><?= (int) $stats["total_utilisateurs"] ?></strong> dont <?= (int) $stats["utilisateurs_actifs"] ?> actifs</p></article>
</section>

<section class="carte">
  <h2>Clubs</h2>

  <?php if (empty($clubs)) : ?>
    <p class="texte-gris">Aucun club enregistré pour le moment.</p>
  <?php else : ?>
    <table class="table">
      <thead>
        <tr>
          <th>Club</th>
          <th>Responsable</th>
          <th>Statut</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clubs as $club) : ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($club["nom_club"]) ?></strong>
              <?php if (!empty($club["description"])) : ?>
                <br><span class="texte-gris petit"><?= htmlspecialchars($club["description"]) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php
                if (!empty($club["prenom"]) || !empty($club["nom"])) {
                  echo htmlspecialchars(trim(($club["prenom"] ?? "") . " " . ($club["nom"] ?? "")));
                } else {
                  echo "Non attribué";
                }
              ?>
            </td>
            <td><?= htmlspecialchars($club["statut"]) ?></td>
            <td><?= htmlspecialchars($club["date_creation"] ?: "-") ?></td>
            <td>
              <div class="carte-actions">
                <form method="post" action="admin_actions.php">
                  <input type="hidden" name="action" value="club_statut">
                  <input type="hidden" name="club_id" value="<?= (int) $club["id"] ?>">
                  <input type="hidden" name="statut" value="actif">
                  <button class="bouton petit" type="submit">Valider</button>
                </form>
                <form method="post" action="admin_actions.php">
                  <input type="hidden" name="action" value="club_statut">
                  <input type="hidden" name="club_id" value="<?= (int) $club["id"] ?>">
                  <input type="hidden" name="statut" value="inactif">
                  <button class="bouton bouton-secondaire petit" type="submit">Inactif</button>
                </form>
                <a class="bouton bouton-secondaire petit" href="club_detail.php?id=<?= (int) $club["id"] ?>">Voir</a>
                <a class="bouton bouton-secondaire petit" href="delete_club_admin.php?id=<?= (int) $club["id"] ?>">Supprimer</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="carte">
  <h2>Utilisateurs</h2>

  <?php if (empty($utilisateurs)) : ?>
    <p class="texte-gris">Aucun utilisateur affiché pour le moment.</p>
  <?php else : ?>
    <table class="table">
      <thead>
        <tr>
          <th>Nom</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Statut</th>
          <th>Clubs</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($utilisateurs as $utilisateur) : ?>
          <tr>
            <td><?= htmlspecialchars($utilisateur["prenom"] . " " . $utilisateur["nom"]) ?></td>
            <td><?= htmlspecialchars($utilisateur["email"]) ?></td>
            <td><?= htmlspecialchars($utilisateur["role"]) ?></td>
            <td><?= htmlspecialchars($utilisateur["statut"]) ?></td>
            <td>
              Responsable : <?= (int) $utilisateur["total_clubs_responsable"] ?><br>
              Membre : <?= (int) $utilisateur["total_clubs_membre"] ?>
            </td>
            <td>
              <form method="post" action="admin_actions.php" class="carte-actions">
                <input type="hidden" name="action" value="user_update">
                <input type="hidden" name="user_id" value="<?= (int) $utilisateur["id"] ?>">

                <select class="champ" name="role" style="min-width:130px;">
                  <option value="etudiant" <?= $utilisateur["role"] === "etudiant" ? "selected" : "" ?>>Étudiant</option>
                  <option value="responsable" <?= $utilisateur["role"] === "responsable" ? "selected" : "" ?>>Responsable</option>
                  <option value="admin" <?= $utilisateur["role"] === "admin" ? "selected" : "" ?>>Admin</option>
                </select>

                <select class="champ" name="statut" style="min-width:120px;">
                  <option value="actif" <?= $utilisateur["statut"] === "actif" ? "selected" : "" ?>>Actif</option>
                  <option value="inactif" <?= $utilisateur["statut"] === "inactif" ? "selected" : "" ?>>Inactif</option>
                </select>

                <button class="bouton petit" type="submit">Enregistrer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php include "includes/footer.php"; ?>
