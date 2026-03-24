  </main>

  <!-- Section Newsletter -->
  <?php if (!isset($_SESSION["id"]) || (isset($_SESSION["role"]) && $_SESSION["role"] === "etudiant")) : ?>
  <section class="conteneur">
    <div class="newsletter-section">
      <i class="fas fa-envelope" style="font-size:2rem;margin-bottom:0.5rem;opacity:0.9;"></i>
      <h2>Restez informé des clubs &amp; événements !</h2>
      <p>Abonnez-vous à la newsletter MyCampusUDM et ne manquez plus aucune activité.</p>
      <div class="newsletter-form">
        <input type="email" id="newsletter-email" class="newsletter-input" placeholder="Votre adresse email...">
        <button class="newsletter-btn" onclick="abonnerNewsletter()">
          <i class="fas fa-paper-plane"></i> S'abonner
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
        <h3>Navigation</h3>
        <a href="clubs.php">Clubs</a>
        <a href="calendrier.php">Calendrier</a>
        <?php if (isset($_SESSION["id"])) : ?>
          <a href="<?= (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") ? "admin.php" : "dashboard.php" ?>">Tableau de bord</a>
        <?php else : ?>
          <a href="connexion.php">Connexion</a>
        <?php endif; ?>
      </div>

      <div class="footer-column">
        <h3>Partenaire académique</h3>
        <a href="https://www.unilim.fr/" target="_blank" rel="noopener noreferrer">
          <img src="images/partner-limoges.png" class="partner-logo" alt="Université de Limoges">
        </a>
      </div>

      <div class="footer-column">
        <h3>MyCampusUDM</h3>
        <p>Plateforme de gestion des clubs étudiants de l’Université des Mascareignes.</p>
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
      <small class="footer-note">Mentions légales • Luidjy</small>
    </div>

  </footer>

  <script src="js/site-main-footer.js"></script>
  <script>
  function abonnerNewsletter() {
    const email = document.getElementById("newsletter-email").value.trim();
    const msg   = document.getElementById("newsletter-msg");
    if (!email) { msg.style.color = "#ffe"; msg.textContent = "Veuillez entrer votre email."; return; }
    msg.textContent = "Inscription en cours...";
    const fd = new FormData();
    fd.append("email", email);
    fetch("newsletter_subscribe.php", { method: "POST", body: fd })
      .then(r => r.json())
      .then(data => {
        msg.style.color = data.success ? "#c6ffe6" : "#ffd0d0";
        msg.textContent = data.message;
        if (data.success) document.getElementById("newsletter-email").value = "";
      })
      .catch(() => { msg.style.color="#ffd0d0"; msg.textContent = "Une erreur est survenue."; });
  }
  document.getElementById("newsletter-email")?.addEventListener("keypress", e => {
    if (e.key === "Enter") abonnerNewsletter();
  });
  </script>

</body>
</html>
