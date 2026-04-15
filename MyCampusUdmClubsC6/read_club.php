<?php

require "variables_club.php";

foreach ($clubs as $club)
{
?>

  <p>
    <?php
      echo "Nom du club : " . htmlspecialchars($club["nom_club"]) . "<br>";
      echo "Description : " . htmlspecialchars($club["description"]) . "<br>";
      echo "Statut : " . htmlspecialchars($club["statut"]) . "<br><br>";
    ?>
  </p>

<?php
}

?>