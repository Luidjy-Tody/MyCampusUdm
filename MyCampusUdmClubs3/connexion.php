<?php

session_start();

$titrePage = "Connexion";
$cssPage   = "auth.css";
$bodyClass = "page-auth";

$login_error    = $_SESSION["login_error"] ?? "";
$register_error = $_SESSION["register_error"] ?? "";
$success        = $_SESSION["success"] ?? "";
$active_form    = $_SESSION["active_form"] ?? ($_GET["form"] ?? "login");

unset($_SESSION["login_error"]);
unset($_SESSION["register_error"]);
unset($_SESSION["success"]);
unset($_SESSION["active_form"]);

include "includes/header.php";

?>

<section class="auth-wrapper">

  <div class="container">

    <div class="form-box <?= $active_form === "login" ? "active" : "" ?>" id="login-form">

      <form method="post" action="auth.php">

        <h2>Login</h2>

        <?php if (!empty($login_error)) : ?>
          <p class="error-message"><?= htmlspecialchars($login_error) ?></p>
        <?php endif; ?>

        <?php if (!empty($success) && $active_form === "login") : ?>
          <p class="success-message"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <input type="email" name="email" placeholder="Email" required>

        <input type="password" name="motdepasse" placeholder="Password" required>

        <button type="submit" name="login">
          Login
        </button>

        <p>
          Don't have an account ?
          <a href="#" onclick="showForm('register-form'); return false;">Register</a>
        </p>

      </form>

    </div>

    <div class="form-box <?= $active_form === "register" ? "active" : "" ?>" id="register-form">

      <form method="post" action="auth.php">

        <h2>Register</h2>

        <?php if (!empty($register_error)) : ?>
          <p class="error-message"><?= htmlspecialchars($register_error) ?></p>
        <?php endif; ?>

        <input type="text" name="nom" placeholder="Nom" required>

        <input type="text" name="prenom" placeholder="Prénom" required>

        <input type="email" name="email" placeholder="Email" required>

        <input type="password" name="motdepasse" placeholder="Password" required>

        <select name="role" required>
          <option value="">--Select Role--</option>
          <option value="etudiant">Étudiant</option>
        </select>

        <button type="submit" name="register">
          Register
        </button>

        <p>
          Already have an account ?
          <a href="#" onclick="showForm('login-form'); return false;">Login</a>
        </p>

      </form>

    </div>

  </div>

</section>

<script src="js/auth.js"></script>

<?php include "includes/footer.php"; ?>