<?php

session_start();

if (!isset($_SESSION["id"]))
{
  header("Location: connexion.php");
  exit();
}

$titrePage = "Tableau de bord";

include "includes/header.php";

?>

<section class="entete-page bonjour-zone">

  <h1 class="bonjour-anime">
    Bonjour <?= htmlspecialchars($_SESSION["prenom"] . " " . $_SESSION["nom"]) ?> 👋
  </h1>

  <p class="texte-gris">
    Bienvenue sur votre tableau de bord MyCampusUDM.
  </p>

</section>

<section class="carte">

  <h2>Mes clubs</h2>

  <p class="texte-gris">
    Aucun club pour le moment.
  </p>

</section>

<section class="carte">

  <h2>Mes demandes</h2>

  <p class="texte-gris">
    Aucune demande pour le moment.
  </p>

</section>

<section class="carte">

  <h2>Événements à venir</h2>

  <p class="texte-gris">
    Aucun événement disponible.
  </p>

</section>

<?php include "includes/footer.php"; ?>