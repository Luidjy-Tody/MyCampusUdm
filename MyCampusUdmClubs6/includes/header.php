<?php

if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}

$estConnecte = isset($_SESSION["id"]);
$roleUtilisateur = $_SESSION["role"] ?? "";
$estAdmin = $roleUtilisateur === "admin";
$estResponsable = $roleUtilisateur === "responsable";

$logoHref = $estAdmin ? "admin.php" : "clubs.php";

$menuLinks = [];

if ($estAdmin)
{
    $menuLinks[] = ["label" => "Administration", "href" => "admin.php"];
    $menuLinks[] = ["label" => "Clubs", "href" => "admin.php"];
    $menuLinks[] = ["label" => "Tableau de bord", "href" => "admin.php"];
}
else
{
    $menuLinks[] = ["label" => "Clubs", "href" => "clubs.php"];
    $menuLinks[] = ["label" => "Calendrier", "href" => "calendrier.php"];

    if ($estConnecte)
    {
        $menuLinks[] = ["label" => "Tableau de bord", "href" => "dashboard.php"];
    }

    if ($estResponsable)
    {
        $menuLinks[] = ["label" => "Mes clubs", "href" => "gestion_club.php"];
        $menuLinks[] = ["label" => "Événements", "href" => "gestion_evenements.php"];
    }
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $titrePage ?? "MyCampusUDM" ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header-footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if (!empty($cssPage)) : ?>
        <link rel="stylesheet" href="css/<?= $cssPage ?>">
    <?php endif; ?>
</head>
<body class="<?= $bodyClass ?? '' ?>">

<header class="site-header">
    <div class="header-inner">
        <a class="logo-area" href="<?= $logoHref ?>">
            <img src="images/udm-logo-header.jpg" alt="Université des Mascareignes">
        </a>

        <button class="menu-btn" aria-label="Ouvrir le menu principal" type="button">
            <span></span>
            <span></span>
        </button>
    </div>
</header>

<nav class="side-menu" id="sideMenu" aria-hidden="true">
    <button class="close-menu" aria-label="Fermer le menu principal" type="button">×</button>

    <div class="side-menu-brand">
        <img src="images/udm-logo-menu.png" class="side-menu-logo" alt="UDM logo">
    </div>

    <ul class="side-menu-nav">
        <?php foreach ($menuLinks as $link) : ?>
            <li><a href="<?= $link['href'] ?>"><?= htmlspecialchars($link['label']) ?></a></li>
        <?php endforeach; ?>
    </ul>

    <hr class="side-menu-divider">

    <ul class="side-menu-actions">
        <?php if ($estConnecte) : ?>
            <li>
                <button class="linkish" type="button" onclick="confirmLogout()">Déconnexion</button>
            </li>
        <?php else : ?>
            <li><a href="connexion.php">Se connecter</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="menu-backdrop" id="menuBackdrop"></div>

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
