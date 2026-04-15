<?php

session_start();
require_once "includes/lang.php";


$titrePage = t("verification_code");
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
        <h2><?= t("verification_code") ?></h2>

        <?php if (!empty($message_code)) : ?>
          <p class="success-message"><?= htmlspecialchars($message_code) ?></p>
        <?php endif; ?>

        <?php if (!empty($error_code)) : ?>
          <p class="error-message"><?= htmlspecialchars($error_code) ?></p>
        <?php endif; ?>

        <input
          type="email"
          name="email"
          placeholder="<?= t("email") ?>"
          value="<?= htmlspecialchars($reset_email) ?>"
          required
        >

        <input
          type="text"
          name="code"
          placeholder="<?= t("verification_code_placeholder") ?>"
          maxlength="6"
          required
        >

        <input
          type="password"
          name="motdepasse"
          placeholder="<?= t("new_password") ?>"
          required
        >

        <input
          type="password"
          name="confirm_motdepasse"
          placeholder="<?= t("confirm_password") ?>"
          required
        >

        <button type="submit"><?= t("validate") ?></button>

        <p style="margin-top:12px;">
          <a href="connexion.php"><?= t("back_to_login") ?></a>
        </p>
      </form>

    </div>
  </div>
</section>

<?php include "includes/footer.php"; ?>