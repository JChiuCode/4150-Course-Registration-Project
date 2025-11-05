<h1>Home page</h1>

<?php
  session_start();

  echo "Email: " . $_SESSION['email'] . "<br>";
  echo "first name: " . $_SESSION['first_name'] . "<br>";
  echo "last name: " . $_SESSION['last_name'] . "<br>";
  echo "role: " . $_SESSION['role'] . "<br>";
?>