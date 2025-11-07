<?php
  session_start();

  require_once("./db.php");

  // sends the user to login if they are not logged in
  if($_SESSION == []){
    header("Location: ./logout.php");
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home Page</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous" ></script>

  <link rel="stylesheet" href="./css/home.css">
</head>

<?php

  // If the user added a course
  if(isset($_POST["section_id"])){

    $user_id = intval($_SESSION['user_id']);
    $section_id = intval($_POST['section_id']);
    $auto_add = 1;

    // Check is user is already in course
    $stmt = $conn->prepare("SELECT student_id FROM Enrollments WHERE student_id = ? AND section_id = ?");
    $stmt->bind_param("ii", $user_id, $section_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // User is alreay enrolled
    if($result->num_rows > 0){
      $message = "You are already enrolled in that section";
      echo "<script type='text/javascript'>alert('$message');</script>";
    }
    else{
    // Enroll Student
    $stmt = $conn->prepare("CALL EnrollStudent(?,?,?);");
    $stmt->bind_param("iii", $user_id, $section_id, $auto_add);
    $stmt->execute();
    $stmt->close();
    }
    
  }

  // If the user entered something in the search bar
  if(isset($_GET["searchInput"])){
    $stmt = $conn->prepare("CALL GetCourseTableInformation(?);");
    $stmt->bind_param("s", $_GET["searchInput"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
  }
  else{
    // Gets all courses
    $stmt = "CALL GetCourseTableInformation(NULL);";
    $result = $conn->query($stmt);
  }
?>

<body>
  <?php require_once('./templates/header.php'); ?>
  <div class="wrapper-container">
    <div class="title-container">
      <h1>Available Course</h1>
      <hr>
    </div>

  <div class="search-container d-flex">
    <form class="form-container d-flex" action="home.php" method="get">
      <input class="form-control me-2" id="searchInput" name="searchInput" type="text" placeholder="Search by Course Name">
      <button type="submit" class="btn btn-primary">Search</button>
    </form>
    <button class="btn btn-info" id="resetButton" onclick="window.location = window.location.pathname;">Reset</button>
  </div>

  <div class="table-container">
    <table class="table table-hover" id="classTable">
      <thead class="table-light">
        <tr>
          <th scope="col">Course Name</th>
          <th scope="col">Description</th>
          <th scope="col">Location</th>
          <th scope="col">Capacity</th>
          <th scope="col">Days</th>
          <th scope="col">Duration</th>
          <th scope="col">Instructor</th>
          <th scope="col">Credits</th>
          <th scope="col">Add</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo $row['course_name']; ?></td>
                <td><?php echo $row['course_description']; ?></td>
                <td><?php echo $row['location']; ?></td>
                <td><?php echo $row['capacity']; ?></td>
                <td><?php echo $row['days']; ?></td>
                <td><?php echo $row['duration']; ?></td>
                <td><?php echo $row['instructor_name']; ?></td>
                <td><?php echo $row['credits']; ?></td>
                <td><form action="home.php" method="post">
                  <button type="submit" class="btn btn-success" <?php if($_SESSION['role'] != 'student'){echo "disabled";}?>>Add</button> <!-- Only students can add courses -->
                  <input type='hidden' name='section_id' value=<?php echo $row['section_id'];?>> <!-- sends the section_id in the POST request -->
                </form></td>
              </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
              <td colspan="9" class="text-center text-danger">No class sections found.</td>
          </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

  </div>
  <!-- <?php require_once("./templates/footer.php"); ?> -->
</body>
</html>

<?php
  $conn->close();
?>