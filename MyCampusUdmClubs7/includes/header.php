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

    <?php if (!empty($cssPage)) : ?>
        <link rel="stylesheet" href="css/<?= $cssPage ?>">
    <?php endif; ?>

</head>

<body class="<?= $bodyClass ?? '' ?>">

<header class="barre-haut">

    <div class="conteneur nav">

        <a class="logo" href="<?= $logoHref ?>">

            <img src="images/logo.png" alt="Logo MyCampus">

            <span>MyCampusUDM</span>

        </a>

        <nav class="menu">

            <?php if ($estAdmin) : ?>

                <a href="admin.php">
                    Administration
                </a>

                <a href="admin.php">
                    Clubs
                </a>

                <a href="admin.php">
                    Tableau de bord
                </a>

            <?php else : ?>

                <a href="<?= $clubsHref ?>">
                    Clubs
                </a>

                <a href="<?= $calendrierHref ?>">
                    Calendrier
                </a>

                <?php if (isset($_SESSION["id"])) : ?>

                    <a href="<?= $tableauHref ?>">
                        Tableau de bord
                    </a>

                <?php endif; ?>

                <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "responsable") : ?>

                    <a href="gestion_club.php">
                        Mes clubs
                    </a>

                    <a href="gestion_evenements.php">
                        Événements
                    </a>

                <?php endif; ?>

            <?php endif; ?>

        </nav>

        <div class="actions">

            <?php if (isset($_SESSION["id"])) : ?>

                <a class="bouton bouton-secondaire"
                   href="#"
                   onclick="confirmLogout()">

                    Déconnexion

                </a>

            <?php else : ?>

                <a class="bouton bouton-secondaire"
                   href="connexion.php">

                    Se connecter

                </a>

            <?php endif; ?>

        </div>

    </div>

</header>

<main class="conteneur contenu udm-main">

<script>

function confirmLogout()
{
    if (confirm("Voulez-vous vraiment vous déconnecter ?"))
    {
        window.location.href = "deconnexion.php";
    }
}

</script>
