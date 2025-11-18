<?php
  session_start();
  require_once("./db.php");

  // Check if user is logged in and is an administrator
  if($_SESSION == [] || $_SESSION['role'] != 'administrator'){
    header("Location: ./logout.php");
    exit();
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enrollment Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
  <style>
    .name-container{
      margin: 1em;
      font-size: large;
    }

    .wrapper-container{
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .title-container{
      width: 80%;
      margin-top: 3em;
      margin-bottom: 1em;
      text-align: center;
    }

    .search-container{
      margin: 1em; 
      width: 80%;
    }

    .form-container{
      width: 100%;   
    }

    #searchInput{
      margin-right: 1em;
    }

    .table-container{
      width: 80%;
    }

    #resetButton{
      margin-left: 1em;
      color: white;
    }
  </style>
</head>

<?php
  // Handle enrollment removal
  if(isset($_POST["remove_enrollment"])){
    $student_id = intval($_POST['student_id']);
    $section_id = intval($_POST['section_id']);
    
    // Drop student from course
    $stmt = $conn->prepare("CALL DropStudent(?,?)");
    $stmt->bind_param("ii", $student_id, $section_id);
    
    if($stmt->execute()){
      $message = "Student successfully removed from course!";
      echo "<script type='text/javascript'>alert('$message');</script>";
    } else {
      $message = "Error removing student from course!";
      echo "<script type='text/javascript'>alert('$message');</script>";
    }
    
    $stmt->close();
  }

  // Get all enrollments with search filter if provided
  if(isset($_GET["searchInput"]) && !empty($_GET["searchInput"])){
    $search = "%" . $_GET["searchInput"] . "%";
    $stmt = $conn->prepare("
      SELECT 
        e.enrollment_id,
        e.student_id,
        e.section_id,
        u.first_name, 
        u.last_name, 
        c.course_name,
        c.course_description,
        s.location,
        s.days,
        s.start_time,
        s.end_time,
        CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name
      FROM Enrollments e
      JOIN Students st ON e.student_id = st.student_id
      JOIN Users u ON st.student_id = u.user_id
      JOIN Sections s ON e.section_id = s.section_id
      JOIN Courses c ON s.course_id = c.course_id
      LEFT JOIN Instructors i ON s.instructor_id = i.instructor_id
      LEFT JOIN Users iu ON i.instructor_id = iu.user_id
      WHERE c.course_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?
      ORDER BY c.course_name, u.last_name, u.first_name
    ");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
  } else {
    $stmt = $conn->prepare("
      SELECT 
        e.enrollment_id,
        e.student_id,
        e.section_id,
        u.first_name, 
        u.last_name, 
        c.course_name,
        c.course_description,
        s.location,
        s.days,
        s.start_time,
        s.end_time,
        CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name
      FROM Enrollments e
      JOIN Students st ON e.student_id = st.student_id
      JOIN Users u ON st.student_id = u.user_id
      JOIN Sections s ON e.section_id = s.section_id
      JOIN Courses c ON s.course_id = c.course_id
      LEFT JOIN Instructors i ON s.instructor_id = i.instructor_id
      LEFT JOIN Users iu ON i.instructor_id = iu.user_id
      ORDER BY c.course_name, u.last_name, u.first_name
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
  }
?>

<body>
  <?php require_once('./templates/header.php'); ?>

  <div class="name-container">
    <?php echo "<p>" . $_SESSION['first_name'] . " " . $_SESSION["last_name"] . " - " . $_SESSION['role'] . "</p>";?>
  </div>

  <div class="wrapper-container">
    <div class="title-container">
      <h1>Enrollment Management</h1>
      <hr>
    </div>

    <div class="search-container d-flex">
      <form class="form-container d-flex" action="enrollments.php" method="get">
        <input class="form-control me-2" id="searchInput" name="searchInput" type="text" placeholder="Search by Course Name or Student Name">
        <button type="submit" class="btn btn-primary">Search</button>
      </form>
      <button class="btn btn-info" id="resetButton" onclick="window.location = window.location.pathname;">Reset</button>
    </div>

    <div class="table-container">
      <table class="table table-hover" id="enrollmentTable">
        <thead class="table-light">
          <tr>
            <th scope="col">Course Name</th>
            <th scope="col">Description</th>
            <th scope="col">Student Name</th>
            <th scope="col">Location</th>
            <th scope="col">Days</th>
            <th scope="col">Time</th>
            <th scope="col">Instructor</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?php echo $row['course_name']; ?></td>
                  <td><?php echo $row['course_description']; ?></td>
                  <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                  <td><?php echo $row['location']; ?></td>
                  <td><?php echo $row['days']; ?></td>
                  <td><?php echo $row['start_time'] . ' - ' . $row['end_time']; ?></td>
                  <td><?php echo $row['instructor_name']; ?></td>
                  <td>
                    <form action="enrollments.php" method="post" onsubmit="return confirm('Are you sure you want to remove this student from the course?');">
                      <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                      <input type='hidden' name='student_id' value='<?php echo $row['student_id']; ?>'>
                      <input type='hidden' name='section_id' value='<?php echo $row['section_id']; ?>'>
                      <input type='hidden' name='remove_enrollment' value='1'>
                    </form>
                  </td>
                </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
                <td colspan="8" class="text-center text-danger">No enrollments found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>

<?php
  $conn->close();
?>