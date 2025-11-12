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
  <title>My Courses</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous" ></script>

  <link rel="stylesheet" href="./css/mycourses.css">
</head>

<?php
    $user_id = $_SESSION['user_id'];

    //If student clicked 'Remove' button
    if(isset($_POST["section_id"])){

        $section_id = intval($_POST['section_id']);
        
        // Drop Student From Course
        $stmt = $conn->prepare("CALL DropStudent(?,?);");
        $stmt->bind_param("ii", $user_id, $section_id);
        $stmt->execute();
        $stmt->close();
    }
    
    if($_SESSION['role'] == 'student'){
        $stmt = "CALL GetCoursesByStudentID($user_id);";
        $result = $conn->query($stmt);
    }
    else if($_SESSION['role'] == 'instructor'){
        $stmt = "CALL GetCoursesByInstructorID($user_id);";
        $result = $conn->query($stmt);
    }
?>

<body>
  <?php require_once('./templates/header.php'); ?>

    <div class="name-container">
    <?php echo "<p>" . $_SESSION['first_name'] . " " . $_SESSION["last_name"] . " - " . $_SESSION['role'] . "</p>";?>
  </div>

  <div class="wrapper-container">
    <div class="title-container">
      <?php echo "<h1>" . $_SESSION['first_name'] . " " . $_SESSION['last_name'] . "'s " . " Courses";?>
      <hr>
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
          <th scope="col">Remove</th>
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
                <td><form action="mycourses.php" method="post">
                  <button type="submit" class="btn btn-danger" <?php if($_SESSION['role'] != 'student'){echo "disabled";}?>>Remove</button> <!-- Only students can add courses -->
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

</body>