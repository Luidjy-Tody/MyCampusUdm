<?php

if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}

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

    <?php if (!empty($cssPage)) : ?>
        <link rel="stylesheet" href="css/<?= $cssPage ?>">
    <?php endif; ?>

</head>

<body class="<?= $bodyClass ?? '' ?>">

<header class="barre-haut">

    <div class="conteneur nav">

        <a class="logo" href="index.php">

            <img src="images/logo.png" alt="Logo MyCampus">

            <span>MyCampusUDM</span>

        </a>

        <nav class="menu">

            <a href="index.php">
                Clubs
            </a>

            <a href="calendrier.php">
                Calendrier
            </a>

            <?php if (isset($_SESSION["id"])) : ?>

                <a href="dashboard.php">
                    Tableau de bord
                </a>

            <?php endif; ?>

            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "responsable") : ?>

                <a href="gestion_club.php">
                    Mes clubs
                </a>

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

<main class="conteneur contenu">

<script>

function confirmLogout()
{
    if (confirm("Voulez-vous vraiment vous déconnecter ?"))
    {
        window.location.href = "deconnexion.php";
    }
}

</script>