<?php

session_start();

if (!isset($_SESSION["id"]))
{
    header("Location: connexion.php");
    exit();
}

require "config/database.php";
require_once "includes/app_helpers.php";

$userId = (int) $_SESSION["id"];
$messageSucces = "";
$messageErreur = "";

$uploadsDir = __DIR__ . "/uploads/profils";
if (!is_dir($uploadsDir))
{
    mkdir($uploadsDir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] === "POST")
{
    $pseudo = trim($_POST["pseudo"] ?? "");
    $nom = trim($_POST["nom"] ?? "");
    $prenom = trim($_POST["prenom"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $filiere = trim($_POST["filiere"] ?? "");
    $bio = trim($_POST["bio"] ?? "");

    if ($nom === "" || $prenom === "" || $email === "")
    {
        $messageErreur = "Nom, prénom et email sont obligatoires.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $messageErreur = "Adresse email invalide.";
    }
    else
    {
        $checkEmail = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email AND id <> :id");
        $checkEmail->execute([
            "email" => $email,
            "id" => $userId
        ]);

        if ($checkEmail->fetch())
        {
            $messageErreur = "Cet email est déjà utilisé par un autre compte.";
        }
    }

    if ($messageErreur === "" && $pseudo !== "")
    {
        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $pseudo))
        {
            $messageErreur = "Le pseudo doit contenir entre 3 et 50 caractères autorisés : lettres, chiffres, point, tiret ou underscore.";
        }
    }

    if ($messageErreur === "" && $pseudo !== "")
    {
        $checkPseudo = $pdo->prepare("SELECT id FROM utilisateurs WHERE pseudo = :pseudo AND id <> :id");
        $checkPseudo->execute([
            "pseudo" => $pseudo,
            "id" => $userId
        ]);
        if ($checkPseudo->fetch())
        {
            $messageErreur = "Ce pseudo est déjà utilisé.";
        }
    }

    $photoPath = null;
    if ($messageErreur === "" && isset($_FILES["photo_profil"]) && (int) ($_FILES["photo_profil"]["error"] ?? 4) !== 4)
    {
        if ((int) $_FILES["photo_profil"]["error"] !== 0)
        {
            $messageErreur = "Le fichier de photo n’a pas pu être envoyé.";
        }
        else
        {
            $allowed = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
            $mime = mime_content_type($_FILES["photo_profil"]["tmp_name"]);
            if (!isset($allowed[$mime]))
            {
                $messageErreur = "Formats autorisés : JPG, PNG ou WEBP.";
            }
            else
            {
                $extension = $allowed[$mime];
                $filename = "profil_" . $userId . "_" . time() . "." . $extension;
                $target = $uploadsDir . "/" . $filename;
                if (!move_uploaded_file($_FILES["photo_profil"]["tmp_name"], $target))
                {
                    $messageErreur = "Impossible d’enregistrer la photo.";
                }
                else
                {
                    $currentPhoto = trim((string) ($_SESSION["photo_profil"] ?? ""));
                    if ($currentPhoto !== "" && str_starts_with($currentPhoto, "uploads/profils/") && is_file(__DIR__ . "/" . $currentPhoto))
                    {
                        @unlink(__DIR__ . "/" . $currentPhoto);
                    }

                    $photoPath = "uploads/profils/" . $filename;
                }
            }
        }
    }

    if ($messageErreur === "")
    {
        $sql = "
            UPDATE utilisateurs
            SET pseudo = :pseudo,
                nom = :nom,
                prenom = :prenom,
                email = :email,
                telephone = :telephone,
                filiere = :filiere,
                bio = :bio
        ";
        $params = [
            "pseudo" => $pseudo !== "" ? $pseudo : null,
            "nom" => $nom,
            "prenom" => $prenom,
            "email" => $email,
            "telephone" => $telephone !== "" ? $telephone : null,
            "filiere" => $filiere !== "" ? $filiere : null,
            "bio" => $bio !== "" ? $bio : null,
            "id" => $userId
        ];

        if ($photoPath !== null)
        {
            $sql .= ", photo_profil = :photo_profil";
            $params["photo_profil"] = $photoPath;
        }

        $sql .= " WHERE id = :id";

        $update = $pdo->prepare($sql);
        $update->execute($params);

        $_SESSION["nom"] = $nom;
        $_SESSION["prenom"] = $prenom;
        $_SESSION["email"] = $email;
        $_SESSION["pseudo"] = $pseudo;
        if ($photoPath !== null)
        {
            $_SESSION["photo_profil"] = $photoPath;
        }

        createNotification($pdo, $userId, "Profil mis à jour", "Vos informations personnelles ont bien été modifiées.", "profil");
        $messageSucces = "Profil mis à jour avec succès.";
    }
}

$userStatement = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
$userStatement->execute(["id" => $userId]);
$user = $userStatement->fetch(PDO::FETCH_ASSOC);

$titrePage = "Mon profil";
include "includes/header.php";
?>
<section class="entete-page">
    <h1>Mon profil</h1>
    <p class="texte-gris">Modifiez votre pseudo, votre photo et vos informations personnelles.</p>
</section>
<?php if ($messageSucces !== "") : ?>
<section class="carte"><p style="color:#0f7a39;"><?= htmlspecialchars($messageSucces) ?></p></section>
<?php endif; ?>
<?php if ($messageErreur !== "") : ?>
<section class="carte"><p style="color:#a11212;"><?= htmlspecialchars($messageErreur) ?></p></section>
<?php endif; ?>
<section class="grille-2 profil-grid">
    <article class="carte profil-side">
        <div class="profil-hero">
            <img src="<?= htmlspecialchars(!empty($user["photo_profil"]) ? $user["photo_profil"] : "images/logo.png") ?>" alt="Photo de profil" class="profil-photo-large">
            <h2><?= htmlspecialchars(!empty($user["pseudo"]) ? $user["pseudo"] : trim(($user["prenom"] ?? "") . " " . ($user["nom"] ?? ""))) ?></h2>
            <p class="texte-gris"><?= htmlspecialchars($user["email"] ?? "") ?></p>
            <p class="badge badge-actif"><?= htmlspecialchars(ucfirst($user["role"] ?? "etudiant")) ?></p>
        </div>
    </article>
    <article class="carte">
        <form method="post" enctype="multipart/form-data" class="formulaire-profil">
            <label>Pseudo</label>
            <input type="text" name="pseudo" class="champ plein" maxlength="50" pattern="[A-Za-z0-9_.-]{3,50}" value="<?= htmlspecialchars($user["pseudo"] ?? "") ?>" placeholder="Choisissez un pseudo (3 à 50 caractères, lettres/chiffres/_.-)">
            <p class="texte-gris petit">Laissez vide si vous préférez afficher simplement votre prénom et votre nom.</p>

            <label>Photo de profil</label>
            <input type="file" name="photo_profil" class="champ plein" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

            <label>Nom</label>
            <input type="text" name="nom" class="champ plein" value="<?= htmlspecialchars($user["nom"] ?? "") ?>" required>

            <label>Prénom</label>
            <input type="text" name="prenom" class="champ plein" value="<?= htmlspecialchars($user["prenom"] ?? "") ?>" required>

            <label>Email</label>
            <input type="email" name="email" class="champ plein" value="<?= htmlspecialchars($user["email"] ?? "") ?>" required>

            <label>Téléphone</label>
            <input type="text" name="telephone" class="champ plein" value="<?= htmlspecialchars($user["telephone"] ?? "") ?>">

            <label>Filière</label>
            <input type="text" name="filiere" class="champ plein" value="<?= htmlspecialchars($user["filiere"] ?? "") ?>">

            <label>Bio</label>
            <textarea name="bio" class="champ plein"><?= htmlspecialchars($user["bio"] ?? "") ?></textarea>

            <div class="carte-actions">
                <button type="submit" class="bouton"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
            </div>
        </form>
    </article>
</section>
<?php include "includes/footer.php"; ?>
