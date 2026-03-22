<?php

session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "admin")
{
  header("Location: connexion.php");
  exit();
}

$titrePage = "Administration";

include "includes/header.php";

?>

<section class="entete-page">
  <h1>Administration</h1>
  <p class="texte-gris">
    Validation des clubs et gestion des utilisateurs.
  </p>
</section>

<section class="carte">

  <h2>Clubs en attente</h2>

  <p class="texte-gris">
    Aucun club en attente.
  </p>

</section>

<section class="carte">

  <h2>Utilisateurs</h2>

  <p class="texte-gris">
    Aucun utilisateur affiché pour le moment.
  </p>

</section>

<?php include "includes/footer.php"; ?>