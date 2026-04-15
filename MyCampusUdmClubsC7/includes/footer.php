  </main>

  <!-- Section Newsletter -->
<?php

require_once "includes/lang.php";

$pageActuelle = basename($_SERVER["PHP_SELF"]);
$masquerNewsletter = in_array($pageActuelle, [
    "connexion.php",
    "register.php",
    "mot_de_passe_oublie.php",
    "verification_code.php"
], true);
?>

<?php if (
    !$masquerNewsletter &&
    (
        !isset($_SESSION["id"]) ||
        (isset($_SESSION["role"]) && $_SESSION["role"] === "etudiant")
    )
) : ?>

<section class="conteneur">
    <div class="newsletter-section">
      <i class="fas fa-envelope" style="font-size:2rem;margin-bottom:0.5rem;opacity:0.9;"></i>
      <h2><?= t("stay_informed") ?></h2>
      <p><?= t("newsletter_text") ?></p>
      <div class="newsletter-form">
        <input type="email" id="newsletter-email" class="newsletter-input" placeholder="<?= t("newsletter_email_placeholder") ?>">
        <button class="newsletter-btn" onclick="abonnerNewsletter()">
          <i class="fas fa-paper-plane"></i> <?= t("subscribe") ?>
        </button>
      </div>
      <p class="newsletter-msg" id="newsletter-msg"></p>
    </div>
  </section>
<?php endif; ?>

  <footer class="udm-footer">

    <div class="footer-container">

      <div class="footer-logo">
        <a href="clubs.php">
          <img src="images/footer-logo-udm.png" alt="Université des Mascareignes">
        </a>
      </div>

      <div class="footer-column">
        <h3><?= t("navigation") ?></h3>
        <a href="clubs.php"><?= t("clubs") ?></a>
        <a href="calendrier.php"><?= t("calendar") ?></a>
        <?php if (isset($_SESSION["id"])) : ?>
          <a href="<?= (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") ? "admin.php" : "dashboard.php" ?>"><?= t("dashboard") ?></a>
        <?php else : ?>
          <a href="connexion.php"><?= t("login") ?></a>
        <?php endif; ?>
      </div>

      <div class="footer-column">
        <h3><?= t("academic_partner") ?></h3>
        <a href="https://www.unilim.fr/" target="_blank" rel="noopener noreferrer">
          <img src="images/partner-limoges.png" class="partner-logo" alt="Université de Limoges">
        </a>
      </div>

      <div class="footer-column">
        <h3>MyCampusUDM</h3>
        <p><?= t("platform_description") ?></p>
        <div class="social-icons">
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
        </div>
      </div>

    </div>

    <div class="footer-bottom">
      <small class="footer-note">© <span data-footer-year><?= date("Y") ?></span> MyCampusUDM — UdM</small>
      <small class="footer-note"><?= t("legal_notice") ?> • Luidjy</small>
    </div>

  </footer>

  <script src="js/site-main-footer.js"></script>
  
</body>
</html>