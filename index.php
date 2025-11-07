<?php
  session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous" ></script>

    <link rel="stylesheet" href="./css/login.css">

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
          <a class="nav-link active" aria-current="page" href="./index.php">Login</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="./register.php">Register</a>
        </li>
        </li>
      </ul>
    </div>
  </div>
</nav>

    <div class="main-container">
        <form action="index.php" method="post" class="form">
            <h1>Login</h1>
            <input type="email" placeholder="Email" id="email" name="email">
            <input type="password" placeholder="Password" id="password" name="password">
            <input type="submit" value="Login" id="submit">
            <p id="error-msg" id="username-error"></p>
        </form>

        <div class="image-container">
            <img src="https://static.vecteezy.com/system/resources/previews/013/166/322/original/woman-with-glasses-with-laptop-sits-flat-cartoon-style-illustration-for-working-freelancing-studying-education-work-from-home-png.png" id="image">
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

  $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_SPECIAL_CHARS);//removes speical characters to prevent CSS or injection attacks
  $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);//removes speical characters to prevent CSS or injection attacks
  
  if(empty($email) || empty($password)){
    echo "<script>document.getElementById('error-msg').innerText = 'Email or Password is invalid';</script>";;
    $conn->close();
    exit();
  }

  try{
    $select_user_stmt = $conn->prepare("SELECT first_name, last_name, password, role FROM Users WHERE email = ?");
    $select_user_stmt->bind_param("s", $email);
    $select_user_stmt->execute();
    $result = $select_user_stmt->get_result();
  }
  catch(Exception $e){
    echo $e;
  }

  if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    $db_password = $row["password"];

    // Check if the entered password matches the one in the DB
    if($password == $db_password || password_verify($password, $db_password)){
      $_SESSION["email"] = $email;
      $_SESSION["first_name"] = $row["first_name"];;
      $_SESSION["last_name"] = $row["last_name"];
      $_SESSION["role"] = $row["role"];

      // Get the user_id
      $query_get_new_id = "SELECT user_id FROM Users WHERE email = '$email'";
      $result = $conn->query($query_get_new_id);
      $row = $result->fetch_assoc();

      $_SESSION['user_id'] = $row['user_id'];

      header("Location: ./home.php");
      exit();
    }
    else{
      echo "<script>document.getElementById('error-msg').innerText = 'Email or Password is invalid';</script>";
    }
  }
  else{
    echo "<script>document.getElementById('error-msg').innerText = 'Email or Password is invalid';</script>";;
  }
  $select_user_stmt->close();

  mysqli_close($conn);
?>