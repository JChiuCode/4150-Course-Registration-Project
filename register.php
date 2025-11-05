<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous" ></script>

    <link rel="stylesheet" href="./css/register.css">

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
    
    <footer>
        <h6>Group #1 - (Aaron Sinn, Jonathan Chiu, Jackie Li, Jacky Zhu)</h6>
    </footer>
</body>
</html>