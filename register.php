<?php
  session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous" ></script>

    <link rel="stylesheet" href="./css/register.css">

    <script>
    // Stop the form resubmission on page refresh
    if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
    }
    </script>

</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">4150 - University Course Registration</a>
    <div class="collapse navbar-collapse nav justify-content-end" id="navbarNavDropdown">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" aria-current="page" href="./index.php">Login</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="./register.php">Register</a>
        </li>
        </li>
      </ul>
    </div>
  </div>
</nav>

    <div class="main-container">
        <form action="register.php" method="post" class="form">
            <h1>REGISTER</h1>
            <div id="names">
                <input type="text" placeholder="First Name" name="first_name" id="firstNameInput">
                <input type="text" placeholder="Last Name" name="last_name" id="lastNameInput">
            </div>
            <input type="email" placeholder="Email" id="email" name="email">
            <input type="password" placeholder="Password" id="password" name="password">
            <input type="submit" value="Create Account" id="submit">
            <p id="error-msg" id="username-error"></p>
        </form>

        <div class="image-container">
            <img src="https://www.canada2036.com/wp-content/uploads/2021/08/studyback.svg" id="image">
        </div>
    </div>
    
    <?php require_once("./templates/footer.php"); ?>
</body>
</html>

<?php
require_once './db.php';

  if(empty($_POST)){
    $conn->close();
    exit();
  }

  $first_name = filter_input(INPUT_POST, "first_name", FILTER_SANITIZE_SPECIAL_CHARS);//removes speical characters to prevent CSS or injection attacks
  $last_name = filter_input(INPUT_POST, "last_name", FILTER_SANITIZE_SPECIAL_CHARS);//removes speical characters to prevent CSS or injection attacks
  $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_SPECIAL_CHARS);//removes speical characters to prevent CSS or injection attacks
  $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);//removes speical characters to prevent CSS or injection attacks

  // adds hash to password so the password isn't stored in plain text
  $password_hash = password_hash($password, PASSWORD_DEFAULT);
  $role = 'student'; //Only students can create accounts for themselves. Instructor or admin will haft to be made by an admin.

  if(empty($first_name) || empty($last_name) || empty($email) || empty($password)){
    echo "<script>
            var err = document.getElementById('error-msg');
            err.innerText = 'A required field is invalid';
        </script>";
    $conn->close();
    exit();
  }

  //checks if the email already exists
  $query_email_valid = "SELECT email FROM Users WHERE email = '$email'";
  $result = $conn->query($query_email_valid);

  if (!$result) {
      echo "Error: " . $conn->error;
      $conn->close();
      exit();
  }

  if ($result->num_rows > 0) {
      echo "<script>
                  var err = document.getElementById('error-msg');
                  err.innerText = 'Email already exists';
              </script>";
      $result->close();
      $conn->close();
      exit(); 
  }

  // Insert user info into DB. The after_user_insert Trigger will add the user_id to Students.
  try{
    $query_insert_user = $conn->prepare("INSERT INTO Users(first_name, last_name, password, email, role) VALUES(?, ?, ?, ?, ?)");
    $query_insert_user->bind_param("sssss", $first_name, $last_name, $password_hash, $email, $role);

    $query_insert_user->execute();
    $query_insert_user->close();

    // Get the new user_id
    $query_get_new_id = "SELECT user_id FROM Users WHERE email = '$email'";
    $result = $conn->query($query_get_new_id);
    $row = $result->fetch_assoc();

    $_SESSION['user_id'] = $row['user_id'];
  }
  catch(Exception $e){
    echo $e;
  }
  
  $_SESSION["email"] = $email;
  $_SESSION["first_name"] = $first_name;
  $_SESSION["last_name"] = $last_name;
  $_SESSION["role"] = $role;

  header("Location: ./home.php");

  mysqli_close($conn);
?>