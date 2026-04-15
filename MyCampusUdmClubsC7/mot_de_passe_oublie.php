<?php
session_start();

require_once "includes/lang.php";


$titrePage = t("forgot_password");
$cssPage   = "auth.css";
$bodyClass = "page-auth";

$message_reset = $_SESSION["message_reset"] ?? "";
$error_reset   = $_SESSION["error_reset"] ?? "";

unset($_SESSION["message_reset"]);
unset($_SESSION["error_reset"]);

include "includes/header.php";
?>

<section class="auth-wrapper">
  <div class="container">
    <div class="form-box active">

      <form method="post" action="traitement_mot_de_passe_oublie.php">
        <h2><?= t("forgot_password") ?></h2>

        <?php if (!empty($message_reset)) : ?>
          <p class="success-message"><?= htmlspecialchars($message_reset) ?></p>
        <?php endif; ?>

        <?php if (!empty($error_reset)) : ?>
          <p class="error-message"><?= htmlspecialchars($error_reset) ?></p>
        <?php endif; ?>

        <input type="email" name="email" placeholder="<?= t("email") ?>" required>

        <button type="submit"><?= t("send_code") ?></button>

        <p style="margin-top:12px;">
          <a href="connexion.php"><?= t("back_to_login") ?></a>
        </p>
      </form>

    </div>
  </div>
</section>

<?php include "includes/footer.php"; ?>