<?php

session_start();

$titrePage = "Vérification du code";
$cssPage   = "auth.css";
$bodyClass = "page-auth";

$message_code = $_SESSION["message_code"] ?? "";
$error_code   = $_SESSION["error_code"] ?? "";
$reset_email  = $_SESSION["reset_email"] ?? "";

unset($_SESSION["message_code"]);
unset($_SESSION["error_code"]);

include "includes/header.php";
?>

<section class="auth-wrapper">
  <div class="container">
    <div class="form-box active">

      <form method="post" action="traitement_reset_password.php">
        <h2>Vérification du code</h2>

        <?php if (!empty($message_code)) : ?>
          <p class="success-message"><?= htmlspecialchars($message_code) ?></p>
        <?php endif; ?>

        <?php if (!empty($error_code)) : ?>
          <p class="error-message"><?= htmlspecialchars($error_code) ?></p>
        <?php endif; ?>

        <input
          type="email"
          name="email"
          placeholder="Votre email"
          value="<?= htmlspecialchars($reset_email) ?>"
          required
        >

        <input
          type="text"
          name="code"
          placeholder="Code à 6 chiffres"
          maxlength="6"
          required
        >

        <input
          type="password"
          name="motdepasse"
          placeholder="Nouveau mot de passe"
          required
        >

        <input
          type="password"
          name="confirm_motdepasse"
          placeholder="Confirmer le mot de passe"
          required
        >

        <button type="submit">Valider</button>

        <p style="margin-top:12px;">
          <a href="connexion.php">Retour à la connexion</a>
        </p>
      </form>

    </div>
  </div>
</section>

<?php include "includes/footer.php"; ?>