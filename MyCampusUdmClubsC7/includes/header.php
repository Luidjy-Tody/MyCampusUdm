<?php

if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/app_helpers.php';
require_once __DIR__ . '/lang.php';

$estAdmin = isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
$logoHref = $estAdmin ? "admin.php" : "clubs.php";
$clubsHref = $estAdmin ? "admin.php" : "clubs.php";
$calendrierHref = $estAdmin ? "admin.php" : "calendrier.php";
$tableauHref = $estAdmin ? "admin.php" : "dashboard.php";
$profilHref = "profil.php";
$notificationCount = isset($_SESSION["id"]) ? getUnreadNotificationCount($pdo, (int) $_SESSION["id"]) : 0;
$displayName = currentUserDisplayName();
$photoProfil = trim((string) currentUserPhotoPath());
$photoExists = $photoProfil !== "" && $photoProfil !== "images/logo.png";
$userInitials = currentUserInitials();
$userEmail = trim((string) ($_SESSION["email"] ?? ""));
$langActuelle = $_SESSION["lang"] ?? "fr";

?>
<!doctype html>
<html lang="<?= htmlspecialchars($langActuelle) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $titrePage ?? "MyCampusUDM" ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/site-main-footer.css">
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
<div id="udm-progress-bar"></div>

<header class="barre-haut">
    <div class="conteneur nav">
        <a class="logo" href="<?= $logoHref ?>">
            <img src="images/footer-logo-udm.png" alt="Logo MyCampus">
        </a>

        <nav class="menu">
            <?php if ($estAdmin) : ?>
                <a href="admin.php" class="nav-link">
                    <i class="fas fa-shield-alt nav-icon"></i>
                    <span><?= t("administration") ?></span>
                </a>
                <a href="newsletter_admin.php" class="nav-link">
                    <i class="fas fa-envelope nav-icon"></i>
                    <span><?= t("newsletter") ?></span>
                </a>
                <a href="messagerie_admin.php" class="nav-link">
                    <i class="fas fa-comments nav-icon"></i>
                    <span><?= t("messaging") ?></span>
                </a>
            <?php else : ?>
                <a href="<?= $clubsHref ?>" class="nav-link">
                    <i class="fas fa-users nav-icon"></i>
                    <span><?= t("clubs") ?></span>
                </a>

                <a href="<?= $calendrierHref ?>" class="nav-link">
                    <i class="fas fa-calendar-alt nav-icon"></i>
                    <span><?= t("calendar") ?></span>
                </a>

                <?php if (isset($_SESSION["id"])) : ?>
                    <a href="<?= $tableauHref ?>" class="nav-link">
                        <i class="fas fa-chart-bar nav-icon"></i>
                        <span><?= t("dashboard") ?></span>
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "responsable") : ?>
                    <a href="gestion_club.php" class="nav-link">
                        <i class="fas fa-cog nav-icon"></i>
                        <span><?= t("my_clubs") ?></span>
                    </a>
                    <a href="gestion_evenements.php" class="nav-link">
                        <i class="fas fa-star nav-icon"></i>
                        <span><?= t("events") ?></span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>

        <div class="actions">

            <div class="lang-switch">
                <a href="?lang=fr" class="<?= $langActuelle === 'fr' ? 'active' : '' ?>">FR</a>
                <span>|</span>
                <a href="?lang=en" class="<?= $langActuelle === 'en' ? 'active' : '' ?>">EN</a>
            </div>

            <?php if (isset($_SESSION["id"]) && !$estAdmin &&
                (!isset($_SESSION["role"]) || $_SESSION["role"] !== "responsable")) : ?>
                <a class="bouton bouton-secondaire" href="contact_admin.php">
                    <i class="fas fa-paper-plane" style="margin-right:5px;"></i>
                    <?= t("contact_admin") ?>
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION["id"])) : ?>
                <div class="profile-menu-wrap" id="profileMenuWrap">
                    <button
                        type="button"
                        class="profile-trigger"
                        id="profileMenuTrigger"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="profileDropdown"
                        title="<?= t("open_profile_menu") ?>"
                    >
                        <?php if ($photoExists) : ?>
                            <img src="<?= htmlspecialchars($photoProfil) ?>" alt="Photo de profil" class="profile-trigger-photo">
                        <?php else : ?>
                            <span class="profile-trigger-avatar"><?= htmlspecialchars($userInitials) ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown" hidden>
                        <div class="profile-dropdown-header">
                            <div class="profile-dropdown-avatar-wrap">
                                <?php if ($photoExists) : ?>
                                    <img src="<?= htmlspecialchars($photoProfil) ?>" alt="Photo de profil" class="profile-dropdown-photo">
                                <?php else : ?>
                                    <span class="profile-dropdown-avatar"><?= htmlspecialchars($userInitials) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="profile-dropdown-user">
                                <strong><?= htmlspecialchars($displayName) ?></strong>
                                <span><?= htmlspecialchars($userEmail) ?></span>
                            </div>
                        </div>

                        <div class="profile-dropdown-actions">
                            <a href="<?= $profilHref ?>" class="profile-dropdown-link">
                                <i class="fa-solid fa-user-pen"></i>
                                <span><?= t("edit_profile") ?></span>
                            </a>
                            <a href="deconnexion.php" class="profile-dropdown-link profile-dropdown-link-danger">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                <span><?= t("logout") ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <a class="bouton bouton-secondaire" href="connexion.php">
                    <i class="fas fa-sign-in-alt" style="margin-right:5px;"></i>
                    <?= t("login") ?>
                </a>
            <?php endif; ?>

            <div class="header-partner">
                <span class="partner-sep"></span>
                <a href="https://www.unilim.fr/" target="_blank" rel="noopener noreferrer" title="<?= t("partner_title") ?>">
                    <img src="images/partner-limoges.png" alt="<?= t("academic_partner") ?>" class="header-partner-logo">
                </a>
            </div>
        </div>
    </div>
</header>

<main class="conteneur contenu udm-main">
<?php if ($notificationCount > 0 && isset($_SESSION["id"])) : ?>
    <a href="notifications.php" class="floating-notification-btn" title="<?= t("notifications") ?> : <?= $notificationCount ?>">
        <i class="fa-regular fa-bell"></i>
        <span class="floating-notification-count"><?= $notificationCount ?></span>
        <span class="floating-notification-label"><?= t("notifications") ?></span>
    </a>
<?php endif; ?>

<script>
(function() {
    const trigger = document.getElementById('profileMenuTrigger');
    const dropdown = document.getElementById('profileDropdown');
    const wrap = document.getElementById('profileMenuWrap');

    if (trigger && dropdown && wrap) {
        trigger.addEventListener('click', function(event) {
            event.stopPropagation();
            const isOpen = !dropdown.hasAttribute('hidden');

            if (isOpen) {
                dropdown.setAttribute('hidden', 'hidden');
                trigger.setAttribute('aria-expanded', 'false');
            } else {
                dropdown.removeAttribute('hidden');
                trigger.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function(event) {
            if (!wrap.contains(event.target)) {
                dropdown.setAttribute('hidden', 'hidden');
                trigger.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                dropdown.setAttribute('hidden', 'hidden');
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    }
})();

function confirmLogout() {
    if (confirm("Voulez-vous vraiment vous déconnecter ?")) {
        window.location.href = "deconnexion.php";
    }
}

(function() {
    const bar = document.getElementById('udm-progress-bar');
    if (!bar) return;

    let width = 0;

    const interval = setInterval(() => {
        width += Math.random() * 18;

        if (width >= 85) {
            clearInterval(interval);
            width = 85;
        }

        bar.style.width = width + '%';
    }, 80);

    window.addEventListener('load', () => {
        clearInterval(interval);
        bar.style.width = '100%';

        setTimeout(() => {
            bar.style.opacity = '0';
            bar.style.transition = 'opacity 0.4s';
        }, 300);

        setTimeout(() => {
            bar.style.width = '0';
            bar.style.opacity = '1';
            bar.style.transition = '';
        }, 800);
    });
})();
</script>