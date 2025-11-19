<?php
  session_start();
  require_once("./db.php");

  // Check if user is logged in and is an administrator
  if($_SESSION == [] || $_SESSION['role'] != 'administrator'){
    header("Location: ./home.php");
    exit();
  }

  // Add User
  if(isset($_POST['action']) && $_POST['action'] == 'add_user'){
      
      $fname = filter_input(INPUT_POST, "first_name", FILTER_SANITIZE_SPECIAL_CHARS);
      $lname = filter_input(INPUT_POST, "last_name", FILTER_SANITIZE_SPECIAL_CHARS);
      $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_SPECIAL_CHARS);
      $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);
      $role = $_POST['role'];
      
      $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

      // Check if email exists
      $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if($result->num_rows > 0){
          $message = "Error: That email is already in use.";
          echo "<script type='text/javascript'>alert('$message');</script>";
      } else {
          $stmt = $conn->prepare("INSERT INTO Users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
          $stmt->bind_param("sssss", $fname, $lname, $email, $hashed_pass, $role);
          
          if($stmt->execute()){
              $message = "User created successfully!";
              echo "<script type='text/javascript'>alert('$message');</script>";
          } else {
              $message = "Error creating user.";
              echo "<script type='text/javascript'>alert('$message');</script>";
          }
      }
      $stmt->close();
  }

  // Delete User
  if(isset($_POST['action']) && $_POST['action'] == 'delete_user'){
      $user_id_to_delete = intval($_POST['user_id']);
      
      if($user_id_to_delete == $_SESSION['user_id']){
          $message = "You cannot delete your own account.";
          echo "<script type='text/javascript'>alert('$message');</script>";
      } else {
          $stmt = $conn->prepare("DELETE FROM Users WHERE user_id = ?");
          $stmt->bind_param("i", $user_id_to_delete);
          
          if($stmt->execute()){
             $message = "User deleted successfully!";
             echo "<script type='text/javascript'>alert('$message');</script>"; 
          }
          $stmt->close();
      }
  }

  // Get all users
  $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, role FROM Users ORDER BY role, last_name");
  $stmt->execute();
  $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous" ></script>

  <link rel="stylesheet" href="./css/users.css">
</head>
<body>
  <?php require_once('./templates/header.php'); ?>
  
  <div class="name-container">
    <?php echo "<p>" . $_SESSION['first_name'] . " " . $_SESSION["last_name"] . " - " . $_SESSION['role'] . "</p>";?>
  </div>

  <div class="wrapper-container">
    <div class="title-container">
      <h1>User Management</h1>
      <hr>
    </div>

    <div class="content-container">
      
      <div class="form-container">
        <h3>Create New User</h3>
        <hr>
        <form action="users.php" method="post">
          <input type="hidden" name="action" value="add_user">
          
          <div class="mb-3">
            <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
          </div>
          
          <div class="mb-3">
            <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
          </div>

          <div class="mb-3">
            <input type="email" name="email" class="form-control" placeholder="Email" required>
          </div>

          <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
          </div>

          <div class="mb-3">
            <select name="role" class="form-select" required>
              <option value="student">Student</option>
              <option value="instructor">Instructor</option>
              <option value="administrator">Administrator</option>
            </select>
          </div>

          <button type="submit" class="btn btn-success w-100">Create User</button>
        </form>
      </div>

      <div class="table-container">
        <h3>All Users</h3>
        <table class="table table-hover table-bordered">
        <thead class="table-light">
            <tr>
            <th>Role</th>
            <th>Name</th>
            <th>Email</th>
            <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                <td><?php echo ucfirst($row['role']); ?></td>
                <td><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td>
                    <?php if($row['user_id'] != $_SESSION['user_id']): ?>
                    <form action="users.php" method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                    <?php else: ?>
                    <span>(You)</span>
                    <?php endif; ?>
                </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
        </table>
      </div>

    </div>
  </div>

  <?php require_once('./templates/footer.php'); ?>
</body>
</html>
<?php
  $conn->close();
?>
