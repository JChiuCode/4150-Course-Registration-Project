<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
  if(isset($_POST["add_section_id"])){

    $user_id = intval($_SESSION['user_id']);
    $section_id = intval($_POST['add_section_id']);
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

      $message = "Successfully enrolled or added to waitlist if full!";
      echo "<script type='text/javascript'>alert('$message');</script>";
    }
    
  }

  if(isset($_POST["remove_section_id"])){

    $section_id = intval($_POST['remove_section_id']);

    // Delete course
    $stmt = $conn->prepare("DELETE FROM Sections WHERE section_id = ?");
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $result = $stmt->get_result();

    
    if($result){
      $message = "Section deleted!";
      echo "<script type='text/javascript'>alert('$message');</script>";
    }
    else{
      $message = "Section could not be deleted!";
      echo "<script type='text/javascript'>alert('$message');</script>";
    }
    $stmt->close(); 
  }

  // If an Admin updated a course
  if(isset($_POST['new_course_name']) && isset($_POST['new_course_description'])  && isset($_POST['new_credits'])){
    $new_course_name = $_POST['new_course_name'];
    $new_course_description = $_POST['new_course_description'];
    $new_credits = number_format($_POST['new_credits'],2);
    $old_course_name = $_POST['old_course_name'];

    try{
      $stmt = $conn->prepare("UPDATE Courses SET course_name = ?, course_description = ?, credits = ? WHERE course_name = ?;");
      $stmt->bind_param("ssds", $new_course_name, $new_course_description, $new_credits, $old_course_name);
      $stmt->execute();
      $stmt->close();
    }catch (Exception $e) {
      die($e->getMessage()); 
    }
  }

  // If the Admin Updated a Section
  if(isset($_POST['section_id']) && isset($_POST['new_instructor_id']) && isset($_POST['new_location']) && isset($_POST['new_semester']) && isset($_POST['new_capacity']) && isset($_POST['new_start_time']) && isset($_POST['new_end_time'])){
    $section_id = $_POST['section_id'] ?? '';
    $new_instructor_id = $_POST['new_instructor_id'] ?? '';
    $new_location = $_POST['new_location'];
    $new_semester = $_POST['new_semester'];
    $new_capacity = $_POST['new_capacity'];
    $new_start_time = $_POST['new_start_time'];
    $new_end_time = $_POST['new_end_time'];
    $days = isset($_POST['days']) ? $_POST['days'] : [];
    $daysStr = implode('', $days);

    try{
      $stmt = $conn->prepare("UPDATE Sections SET instructor_id = ?, location = ?, semester = ?, capacity = ?, start_time = ?, end_time = ?, days = ? WHERE section_id = ?;");
      $stmt->bind_param("ississsi", $new_instructor_id, $new_location, $new_semester, $new_capacity, $new_start_time, $new_end_time, $daysStr, $section_id);
      $stmt->execute();
      $stmt->close();
    }catch (Exception $e) {
      die($e->getMessage()); 
    }
  }

  // If the user entered something in the search bar
  if(isset($_GET["searchInput"]) && !empty($_GET["searchInput"])){
    $searchInput = $_GET["searchInput"];
    $stmt = $conn->prepare("CALL GetCourseTableInformation(?);");
    $stmt->bind_param("s", $searchInput);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
  }
  else{
    // Gets all courses - use empty string instead of NULL
    $stmt = $conn->prepare("CALL GetCourseTableInformation(?);");
    $empty = "";
    $stmt->bind_param("s", $empty);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
  }

  $stmt = $conn->prepare("
      SELECT I.instructor_id, CONCAT(U.first_name, ' ', U.last_name) AS instructor_name
      FROM Instructors I
      JOIN Users U ON I.instructor_id = U.user_id;");
  $stmt->execute();
  $instructors_result = $stmt->get_result();
  $stmt->close();

  $stmt = $conn->prepare("SELECT course_name FROM Courses;");
  $stmt->execute();
  $courses_result = $stmt->get_result();
  $stmt->close();

  $stmt = $conn->prepare("SELECT S.section_id, C.course_name FROM Sections S
    JOIN Courses C ON C.course_id = S.course_id;");
  $stmt->execute();
  $section_id_result = $stmt->get_result();
  $stmt->close();

?>

<body>
  <?php require_once('./templates/header.php'); ?>

  <div class="name-container">
    <?php echo "<p>" . $_SESSION['first_name'] . " " . $_SESSION["last_name"] . " - " . $_SESSION['role'] . "</p>";?>
  </div>

  <div class="wrapper-container">
    <div class="title-container">
      <h1>Available Course Sections</h1>
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
          <th scope="col">Section ID</th>
          <?php if($_SESSION['role'] == 'student' || $_SESSION['role'] == 'instructor'): ?>
          <th scope="col">Add</th>
          <?php endif; ?>
          <?php if($_SESSION['role'] == 'administrator'): ?>
          <th scope="col">Remove</th>
          <?php endif; ?>
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
                <td><?php echo $row['section_id']; ?></td>

                <?php if($_SESSION['role'] == 'student' || $_SESSION['role'] == 'instructor'): ?>
                <td><form action="home.php" method="post">
                  <button type="submit" class="btn btn-success" <?php if($_SESSION['role'] != 'student'){echo "disabled";}?>>Add</button> <!-- Only students can add courses -->
                  <input type='hidden' name='add_section_id' value=<?php echo $row['section_id'];?>> <!-- sends the section_id in the POST request -->
                </form></td>
                <?php endif; ?>

                  <!-- Only admins can see the delete option -->
                <?php if($_SESSION['role'] == 'administrator'): ?>
                <td><form action="home.php" method="post">
                  <button type="submit" class="btn btn-danger">Remove</button> <!-- Only students can add courses -->
                  <input type='hidden' name='remove_section_id' value=<?php echo $row['section_id'];?>> <!-- sends the section_id in the POST request -->
                </form></td>

              </tr>
              <?php endif; ?>
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

<?php if ($_SESSION['role'] == 'administrator') : ?>
<div class="container mt-5">
  <h2>Update Course Information</h2>
  <form action="home.php" method="post" class="border p-4 rounded shadow-sm bg-light">

    <div class="mb-3">
      <label for="course_id" class="form-label">Course Code</label>
      <select type="text" name="old_course_name" class="form-select" required>
        <?php if ($courses_result->num_rows > 0): ?>
            <?php while ($row = $courses_result->fetch_assoc()): ?>
                <option value='<?php echo $row['course_name']; ?>'><?php echo $row['course_name']; ?></option>
            <?php endwhile; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="mb-3">
      <label for="course_name" class="form-label">New Course Code</label>
      <input type="text" name="new_course_name" id="new_course_name" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="description" class="form-label">New Description</label>
      <textarea name="new_course_description" id="new_course_description" class="form-control" rows="3" required></textarea>
    </div>

    <div class="mb-3">
      <label for="credits" class="form-label">New Credits</label>
      <input type="number" name="new_credits" id="new_credits" class="form-control" max="3.00" value="3.00" required>
    </div>

    <button type="submit" class="btn btn-primary">Update Course</button>
  </form>
</div>

<div class="container mt-5">
  <h2>Update Section Information</h2>
  <form action="home.php" method="post" class="border p-4 rounded shadow-sm bg-light">

    <div class="mb-3">
      <label for="section_id" class="form-label">Section ID</label>
      <select type="number" name="section_id" class="form-select" required>
        <?php if ($section_id_result->num_rows > 0): ?>
            <?php while ($row = $section_id_result->fetch_assoc()): ?>
                <option value='<?php echo $row['section_id']; ?>'><?php echo $row['section_id']." - (".$row['course_name'].")"; ?></option>
            <?php endwhile; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="mb-3">
      <label for="new_instructor_id" class="form-label">New Instructor ID</label>
      <select type="number" name="new_instructor_id" class="form-select" required>
        <?php if ($instructors_result->num_rows > 0): ?>
            <?php while ($row = $instructors_result->fetch_assoc()): ?>
                <option value='<?php echo $row['instructor_id']; ?>'><?php echo $row['instructor_name']; ?></option>
            <?php endwhile; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="mb-3">
      <label for="new_location" class="form-label">New Location</label>
      <input type="text" name="new_location" id="new_location" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="new_semester" class="form-label">New Semester</label>
      <input type="text" name="new_semester" id="new_semester" class="form-control" placeholder="Ex. F2025" required>
    </div>

    <div class="mb-3">
      <label for="new_capacity" class="form-label">New Capacity</label>
      <input type="number" name="new_capacity" id="new_capacity" class="form-control" required>
    </div>

    <div class="mb-3">
        <Label class="form-label d-block">New Class Days</Label>
        <div class="btn-group" role="group" aria-label="Days of the week">
            <input type="checkbox" class="btn-check" id="mondayBtn" autocomplete="off" name="days[]" value="M">
            <label class="btn btn-outline-primary" for="mondayBtn">Mon</label>

            <input type="checkbox" class="btn-check" id="tuesdayBtn" autocomplete="off" name="days[]" value="T">
            <label class="btn btn-outline-primary" for="tuesdayBtn">Tue</label>

            <input type="checkbox" class="btn-check" id="wednesdayBtn" autocomplete="off" name="days[]" value="W">
            <label class="btn btn-outline-primary" for="wednesdayBtn">Wed</label>
            
            <input type="checkbox" class="btn-check" id="thursdayBtn" autocomplete="off" name="days[]" value="T">
            <label class="btn btn-outline-primary" for="thursdayBtn">Thu</label>
            
            <input type="checkbox" class="btn-check" id="fridayBtn" autocomplete="off" name="days[]" value="F">
            <label class="btn btn-outline-primary" for="fridayBtn">Fri</label>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">New Start Time</label>
        <input type="time" class="form-control" name="new_start_time" required>
    </div>

    <div class="mb-3">
        <label class="form-label">New End Time</label>
        <input type="time" class="form-control" name="new_end_time" required>
    </div>

    <button type="submit" class="btn btn-warning">Update Session</button>
  </form>
</div>
<?php endif; ?>

</body>
</html>

<?php
  $conn->close();
?>