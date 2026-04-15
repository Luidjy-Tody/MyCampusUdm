<?php

session_start();

require_once __DIR__ . "/config/database.php";

require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";
require "PHPMailer/src/Exception.php";

require_once "mail_config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] !== "POST")
{
    header("Location: mot_de_passe_oublie.php");
    exit();
}

$email = trim($_POST["email"] ?? "");

if ($email === "")
{
    $_SESSION["error_reset"] = "Veuillez entrer votre adresse email.";
    header("Location: mot_de_passe_oublie.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT id, nom, prenom, email
    FROM utilisateurs
    WHERE email = :email
    LIMIT 1
");

$stmt->execute([
    "email" => $email
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user)
{
    $code = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
    $expire_at = date("Y-m-d H:i:s", strtotime("+30 minutes"));

    $deleteOld = $pdo->prepare("
        DELETE FROM password_resets
        WHERE user_id = :user_id
    ");

    $deleteOld->execute([
        "user_id" => $user["id"]
    ]);

    $insert = $pdo->prepare("
        INSERT INTO password_resets (user_id, reset_code, expire_at, used)
        VALUES (:user_id, :reset_code, :expire_at, 0)
    ");

    $insert->execute([
        "user_id"    => $user["id"],
        "reset_code" => $code,
        "expire_at"  => $expire_at
    ]);

    $mail = new PHPMailer(true);

    try
    {
        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom(MAIL_USERNAME, "MyCampusClubsUDM");
        $mail->addAddress($user["email"], $user["prenom"] . " " . $user["nom"]);

        $mail->isHTML(true);
        $mail->Subject = "Code de réinitialisation de votre mot de passe";
        $mail->Body = "
            <p>Bonjour " . htmlspecialchars($user["prenom"]) . ",</p>
            <p>Voici votre code de réinitialisation :</p>
            <h2 style='letter-spacing:3px;'>" . $code . "</h2>
            <p>Ce code expire dans 30 minutes.</p>
            <p>Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.</p>
        ";

        $mail->send();
    }
    catch (Exception $e)
    {
        
    }
}

$_SESSION["message_reset"] = "Si cette adresse existe, un code de réinitialisation a été envoyé.";
$_SESSION["reset_email"] = $email;

header("Location: verification_code.php");
exit();