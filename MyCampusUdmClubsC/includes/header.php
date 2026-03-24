<?php

if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}

$estAdmin = isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
$logoHref = $estAdmin ? "admin.php" : "clubs.php";
$clubsHref = $estAdmin ? "admin.php" : "clubs.php";
$calendrierHref = $estAdmin ? "admin.php" : "calendrier.php";
$tableauHref = $estAdmin ? "admin.php" : "dashboard.php";

?>

<!doctype html>

<html lang="fr">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= $titrePage ?? "MyCampusUDM" ?>
    </title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/site-main-footer.css">
    <!-- Barre de progression de navigation -->
    <style>
    #udm-progress-bar {
      position: fixed;
      top: 0; left: 0;
      width: 0%;
      height: 3px;
      background: linear-gradient(90deg, #0d4b8f, #25cdf3, #0d4b8f);
      z-index: 9999;
      transition: width 0.3s ease;
      border-radius: 0 2px 2px 0;
    }
    </style>

    <?php if (!empty($cssPage)) : ?>
        <link rel="stylesheet" href="css/<?= $cssPage ?>">
    <?php endif; ?>

</head>

<body class="<?= $bodyClass ?? '' ?>">

<!-- Barre de progression -->
<div id="udm-progress-bar"></div>

<header class="barre-haut">

    <div class="conteneur nav">

        <!-- Logo UdM gauche -->
        <a class="logo" href="<?= $logoHref ?>">
            <img src="images/footer-logo-udm.png" alt="Logo MyCampus">
        </a>

        <!-- Navigation centrale avec icônes -->
        <nav class="menu">

            <?php if ($estAdmin) : ?>

                <a href="admin.php" class="nav-link">
                    <i class="fas fa-shield-alt nav-icon"></i>
                    <span>Administration</span>
                </a>

                <a href="newsletter_admin.php" class="nav-link">
                    <i class="fas fa-envelope nav-icon"></i>
                    <span>Newsletter</span>
                </a>

            <?php else : ?>

                <a href="<?= $clubsHref ?>" class="nav-link">
                    <i class="fas fa-users nav-icon"></i>
                    <span>Clubs</span>
                </a>

                <a href="<?= $calendrierHref ?>" class="nav-link">
                    <i class="fas fa-calendar-alt nav-icon"></i>
                    <span>Calendrier</span>
                </a>

                <?php if (isset($_SESSION["id"])) : ?>

                    <a href="<?= $tableauHref ?>" class="nav-link">
                        <i class="fas fa-chart-bar nav-icon"></i>
                        <span>Tableau de bord</span>
                    </a>

                <?php endif; ?>

                <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "responsable") : ?>

                    <a href="gestion_club.php" class="nav-link">
                        <i class="fas fa-cog nav-icon"></i>
                        <span>Mes clubs</span>
                    </a>

                    <a href="gestion_evenements.php" class="nav-link">
                        <i class="fas fa-star nav-icon"></i>
                        <span>Événements</span>
                    </a>

                <?php endif; ?>

            <?php endif; ?>

        </nav>

        <!-- Actions + Logo Limoges droite -->
        <div class="actions">

            <?php if (isset($_SESSION["id"])) : ?>

                <a class="bouton bouton-secondaire"
                   href="#"
                   onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt" style="margin-right:5px;"></i>
                    Déconnexion
                </a>

            <?php else : ?>

                <a class="bouton bouton-secondaire"
                   href="connexion.php">
                    <i class="fas fa-sign-in-alt" style="margin-right:5px;"></i>
                    Se connecter
                </a>

            <?php endif; ?>

            <!-- Séparateur + Logo Limoges -->
            <div class="header-partner">
                <span class="partner-sep"></span>
                <a href="https://www.unilim.fr/" target="_blank" rel="noopener noreferrer" title="Université de Limoges - Partenaire académique">
                    <img src="images/partner-limoges.png" alt="Université de Limoges" class="header-partner-logo">
                </a>
            </div>

        </div>

    </div>

</header>

<main class="conteneur contenu udm-main">

<script>
function confirmLogout() {
    if (confirm("Voulez-vous vraiment vous déconnecter ?")) {
        window.location.href = "deconnexion.php";
    }
}

// Barre de progression de navigation
(function() {
    const bar = document.getElementById('udm-progress-bar');
    if (!bar) return;
    let width = 0;
    const interval = setInterval(() => {
        width += Math.random() * 18;
        if (width >= 85) { clearInterval(interval); width = 85; }
        bar.style.width = width + '%';
    }, 80);
    window.addEventListener('load', () => {
        clearInterval(interval);
        bar.style.width = '100%';
        setTimeout(() => { bar.style.opacity = '0'; bar.style.transition = 'opacity 0.4s'; }, 300);
        setTimeout(() => { bar.style.width = '0'; bar.style.opacity = '1'; bar.style.transition = ''; }, 800);
    });
})();
</script>
