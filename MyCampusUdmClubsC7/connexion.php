<?php

session_start();
require_once "includes/lang.php";

$titrePage = t("login");
$cssPage   = "auth.css";
$bodyClass = "page-auth";

$login_error    = $_SESSION["login_error"] ?? "";
$register_error = $_SESSION["register_error"] ?? "";
$success        = $_SESSION["success"] ?? "";
$active_form    = $_SESSION["active_form"] ?? ($_GET["form"] ?? "login");
$success_reset  = $_SESSION["success_reset"] ?? "";

unset($_SESSION["login_error"]);
unset($_SESSION["register_error"]);
unset($_SESSION["success"]);
unset($_SESSION["active_form"]);
unset($_SESSION["success_reset"]);

include "includes/header.php";

?>

<section class="auth-wrapper">

  <div class="container">

    <div class="form-box <?= $active_form === "login" ? "active" : "" ?>" id="login-form">

      <form method="post" action="auth.php">

        <h2><?= t("login") ?></h2>

        <?php if (!empty($login_error)) : ?>
          <p class="error-message"><?= htmlspecialchars($login_error) ?></p>
        <?php endif; ?>

        <?php if (!empty($success) && $active_form === "login") : ?>
          <p class="success-message"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php if (!empty($success_reset)) : ?>
          <p class="success-message"><?= htmlspecialchars($success_reset) ?></p>
        <?php endif; ?>

        <input type="email" name="email" placeholder="<?= t("email") ?>" required>

        <input type="password" name="motdepasse" placeholder="<?= t("password") ?>" required>

        <button type="submit" name="login"><?= t("login") ?></button>

        <p style="margin-top:10px;">
          <a href="mot_de_passe_oublie.php"><?= t("forgot_password") ?> ?</a>
        </p>

        <p>
          <?= t("dont_have_account") ?>
          <a href="#" onclick="showForm('register-form'); return false;"><?= t("register") ?></a>
        </p>

      </form>

    </div>

    <div class="form-box <?= $active_form === "register" ? "active" : "" ?>" id="register-form">

      <form method="post" action="auth.php">

        <h2><?= t("register") ?></h2>

        <?php if (!empty($register_error)) : ?>
          <p class="error-message"><?= htmlspecialchars($register_error) ?></p>
        <?php endif; ?>

        <input type="text" name="nom" placeholder="<?= t("lastname") ?>" required>

        <input type="text" name="prenom" placeholder="<?= t("firstname") ?>" required>

        <input type="email" name="email" placeholder="<?= t("email") ?>" required>

        <input type="password" name="motdepasse" placeholder="<?= t("password") ?>" required>

        <input type="hidden" name="role" value="etudiant">

        <p class="texte-gris petit"><?= t("student_account_info") ?></p>

        <button type="submit" name="register"><?= t("register") ?></button>

        <p>
          <?= t("already_have_account") ?>
          <a href="#" onclick="showForm('login-form'); return false;"><?= t("login") ?></a>
        </p>

      </form>

    </div>

  </div>

</section>

<script src="js/auth.js"></script>

<?php include "includes/footer.php"; ?>