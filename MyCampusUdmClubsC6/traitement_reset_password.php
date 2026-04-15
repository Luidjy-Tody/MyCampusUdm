<?php

session_start();
require_once __DIR__ . "/config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST")
{
    header("Location: connexion.php");
    exit();
}

$email              = trim($_POST["email"] ?? "");
$code               = trim($_POST["code"] ?? "");
$motdepasse         = $_POST["motdepasse"] ?? "";
$confirm_motdepasse = $_POST["confirm_motdepasse"] ?? "";

$_SESSION["reset_email"] = $email;

if ($email === "" || $code === "" || $motdepasse === "" || $confirm_motdepasse === "")
{
    $_SESSION["error_code"] = "Tous les champs sont obligatoires.";
    header("Location: verification_code.php");
    exit();
}

if (!preg_match('/^\d{6}$/', $code))
{
    $_SESSION["error_code"] = "Le code doit contenir 6 chiffres.";
    header("Location: verification_code.php");
    exit();
}

if ($motdepasse !== $confirm_motdepasse)
{
    $_SESSION["error_code"] = "Les mots de passe ne correspondent pas.";
    header("Location: verification_code.php");
    exit();
}

if (strlen($motdepasse) < 8)
{
    $_SESSION["error_code"] = "Le mot de passe doit contenir au moins 8 caractères.";
    header("Location: verification_code.php");
    exit();
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
    $_SESSION["error_code"] = "Email introuvable.";
    header("Location: verification_code.php");
    exit();
}

$stmtReset = $pdo->prepare("
    SELECT *
    FROM password_resets
    WHERE user_id = :user_id
      AND used = 0
    ORDER BY id DESC
    LIMIT 1
");

$stmtReset->execute([
    "user_id" => $user["id"]
]);

$reset = $stmtReset->fetch(PDO::FETCH_ASSOC);

if (!$reset)
{
    $_SESSION["error_code"] = "Aucun code trouvé pour cet utilisateur.";
    header("Location: verification_code.php");
    exit();
}

if ($reset["reset_code"] !== $code)
{
    $_SESSION["error_code"] = "Code incorrect.";
    header("Location: verification_code.php");
    exit();
}

if (strtotime($reset["expire_at"]) < time())
{
    $_SESSION["error_code"] = "Code expiré.";
    header("Location: verification_code.php");
    exit();
}

$newHash = password_hash($motdepasse, PASSWORD_DEFAULT);

$updatePassword = $pdo->prepare("
    UPDATE utilisateurs
    SET mot_de_passe_hash = :mot_de_passe_hash
    WHERE id = :id
");

$updatePassword->execute([
    "mot_de_passe_hash" => $newHash,
    "id"                => $user["id"]
]);

$updateReset = $pdo->prepare("
    UPDATE password_resets
    SET used = 1
    WHERE id = :id
");

$updateReset->execute([
    "id" => $reset["id"]
]);

unset($_SESSION["reset_email"]);

$_SESSION["success_reset"] = "Mot de passe modifié avec succès. Vous pouvez maintenant vous connecter.";
header("Location: connexion.php");
exit();