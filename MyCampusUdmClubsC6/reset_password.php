<?php

session_start();
require_once __DIR__ . "/config/database.php";

$titrePage = "Nouveau mot de passe";
$cssPage   = "auth.css";
$bodyClass = "page-auth";

$token = $_GET["token"] ?? "";
$email = trim($_GET["email"] ?? "");

if ($token === "" || $email === "")
{
    die("Lien invalide.");
}

$stmtUser = $pdo->prepare("
    SELECT id, email
    FROM utilisateurs
    WHERE email = :email
    LIMIT 1
");

$stmtUser->execute([
    "email" => $email
]);

$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user)
{
    die("Lien invalide.");
}

$stmtReset = $pdo->prepare("
    SELECT *
    FROM password_resets
    WHERE user_id = :user_id
      AND used = 0
      AND expire_at >= NOW()
    ORDER BY id DESC
    LIMIT 1
");

$stmtReset->execute([
    "user_id" => $user["id"]
]);

$reset = $stmtReset->fetch(PDO::FETCH_ASSOC);

if (!$reset)
{
    die("Lien expiré ou invalide.");
}

if (!password_verify($token, $reset["token_hash"]))
{
    die("Lien invalide.");
}

$message_new_password = $_SESSION["message_new_password"] ?? "";
unset($_SESSION["message_new_password"]);

include "includes/header.php";
?>

<section class="auth-wrapper">
  <div class="container">
    <div class="form-box active">

      <form method="post" action="traitement_reset_password.php">
        <h2>Nouveau mot de passe</h2>

        <?php if (!empty($message_new_password)) : ?>
          <p class="error-message"><?= htmlspecialchars($message_new_password) ?></p>
        <?php endif; ?>

        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <input type="password" name="motdepasse" placeholder="Nouveau mot de passe" required>
        <input type="password" name="confirm_motdepasse" placeholder="Confirmer le mot de passe" required>

        <button type="submit">Modifier le mot de passe</button>
      </form>

    </div>
  </div>
</section>

<?php include "includes/footer.php"; ?>