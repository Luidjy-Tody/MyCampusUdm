<?php

session_start();
require "config/database.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: connexion.php");
    exit();
}

// Créer la table si elle n'existe pas
$pdo->exec("
    CREATE TABLE IF NOT EXISTS newsletter_abonnes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        token VARCHAR(64) NOT NULL,
        date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
        actif TINYINT(1) DEFAULT 1
    )
");

$message = "";

// Envoi d'une newsletter (simulation - affiche le message)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "envoyer") {
    $sujet  = trim($_POST["sujet"] ?? "");
    $contenu = trim($_POST["contenu"] ?? "");
    if ($sujet && $contenu) {
        // En production : utiliser PHPMailer ou sendmail
        // Ici on simule avec mail()
        $abonnesStmt = $pdo->query("SELECT email FROM newsletter_abonnes WHERE actif = 1");
        $abonnes = $abonnesStmt->fetchAll(PDO::FETCH_COLUMN);
        $envois = 0;
        foreach ($abonnes as $email) {
            $headers = "From: noreply@mycampusudm.mu\r\nContent-Type: text/plain; charset=UTF-8";
            if (@mail($email, $sujet, $contenu, $headers)) $envois++;
        }
        $message = "Newsletter envoyée à $envois abonné(s).";
    }
}

// Suppression d'un abonné
if (isset($_GET["supprimer"])) {
    $id = (int)$_GET["supprimer"];
    $pdo->prepare("DELETE FROM newsletter_abonnes WHERE id = :id")->execute(["id" => $id]);
    header("Location: newsletter_admin.php");
    exit();
}

$abonnesStmt = $pdo->query("SELECT * FROM newsletter_abonnes ORDER BY date_inscription DESC");
$abonnes = $abonnesStmt->fetchAll(PDO::FETCH_ASSOC);
$total   = count($abonnes);
$actifs  = count(array_filter($abonnes, fn($a) => $a["actif"]));

$titrePage = "Gestion Newsletter";
include "includes/header.php";
?>

<section class="entete-page">
    <h1><i class="fas fa-envelope-open-text" style="color:#0d4b8f;margin-right:10px;"></i>Gestion Newsletter</h1>
    <p class="texte-gris">Gérez les abonnés et envoyez des newsletters aux étudiants.</p>
</section>

<?php if ($message) : ?>
<section class="carte" style="border-left:4px solid #0f7a39;">
    <p style="color:#0f7a39;margin:0;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></p>
</section>
<?php endif; ?>

<!-- Stats -->
<section class="grille-3">
    <div class="carte" style="text-align:center;">
        <p class="texte-gris petit">Total abonnés</p>
        <h2 style="color:#0d4b8f;font-size:2rem;margin:0;"><?= $total ?></h2>
    </div>
    <div class="carte" style="text-align:center;">
        <p class="texte-gris petit">Abonnés actifs</p>
        <h2 style="color:#0f7a39;font-size:2rem;margin:0;"><?= $actifs ?></h2>
    </div>
    <div class="carte" style="text-align:center;">
        <p class="texte-gris petit">Désabonnés</p>
        <h2 style="color:#a11212;font-size:2rem;margin:0;"><?= $total - $actifs ?></h2>
    </div>
</section>

<!-- Envoyer Newsletter -->
<section class="carte">
    <h2><i class="fas fa-paper-plane" style="color:#0d4b8f;margin-right:8px;"></i>Envoyer une newsletter</h2>
    <form method="POST">
        <input type="hidden" name="action" value="envoyer">
        <label>Sujet</label>
        <input type="text" name="sujet" class="champ" style="width:100%;margin-bottom:10px;" placeholder="Ex: Nouvel événement au Club Sport !" required>
        <label>Contenu du message</label>
        <textarea name="contenu" class="champ" style="width:100%;min-height:150px;" placeholder="Votre message ici..." required></textarea>
        <div class="carte-actions" style="margin-top:12px;">
            <button type="submit" class="bouton"><i class="fas fa-paper-plane"></i> Envoyer à <?= $actifs ?> abonné(s)</button>
        </div>
    </form>
</section>

<!-- Liste abonnés -->
<section class="carte">
    <h2><i class="fas fa-list" style="color:#0d4b8f;margin-right:8px;"></i>Liste des abonnés</h2>
    <?php if (empty($abonnes)) : ?>
        <p class="texte-gris">Aucun abonné pour le moment.</p>
    <?php else : ?>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Email</th>
                <th>Date inscription</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($abonnes as $a) : ?>
            <tr>
                <td><?= $a["id"] ?></td>
                <td><?= htmlspecialchars($a["email"]) ?></td>
                <td><?= htmlspecialchars($a["date_inscription"]) ?></td>
                <td>
                    <?php if ($a["actif"]) : ?>
                        <span class="badge badge-actif">Actif</span>
                    <?php else : ?>
                        <span class="badge badge-annule">Désabonné</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="bouton bouton-secondaire petit"
                       href="newsletter_admin.php?supprimer=<?= $a['id'] ?>"
                       onclick="return confirm('Supprimer cet abonné ?');">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>

<?php include "includes/footer.php"; ?>
