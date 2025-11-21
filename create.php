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
    <title>Creation</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="./css/home.css">
    
    
</head>

<?php 
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        //Section Form
        if (isset($_POST['submit_section'])) {
            $instructor_id = $_POST['instructor_id'] ?? '';
            $course_code = $_POST['course_code_a'] ?? '';
            $location = $_POST['location'];
            $semester = $_POST['semester'];
            $capacity = $_POST['capacity'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $days = isset($_POST['days']) ? $_POST['days'] : [];

            $errors = [];
            $sectionMessage = "";

            //Database Validation
            //Instructor ID Check
            if (!isset($errors['instructor_id'])) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM instructors WHERE instructor_id = ?");
                $stmt->bind_param("i", $_POST['instructor_id']);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count == 0) {
                    $errors['instructor_id'] = "Instructor ID not found";
                }
            }

            //Course Code Check and Parse
            $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_name = ?");
            $stmt->bind_param("s", $course_code);
            $stmt->execute();
            $stmt->bind_result($course_id);
            if (!$stmt->fetch()) {
                // No course found
                $errors['course_code_a'] = "Course code not found";
                $stmt->close();
                $course_id = null;
            } else {
                $stmt->close();
            }


            // Convert days array to a string like "MWF"
            $daysStr = implode('', $days);

            //Error validation and INSERT
            if (empty($errors)) {
                $stmt = $conn->prepare('INSERT INTO sections (instructor_id, course_id, location, semester, capacity, start_time, end_time, days) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('iississs', $instructor_id, $course_id, $location, $semester, $capacity, $start_time, $end_time, $daysStr);
                if ($stmt->execute()) {
                    $sectionMessage = "Section Created Successfully";
                }
                else {
                    $sectionMessage = "Error creating section: " . $stmt->error;
                }
                $stmt->close();
            }
        }

        //Course Form
        if (isset($_POST['submit_course'])) {
            $course_name = $_POST['course_name'] ?? '';
            $course_code = $_POST['course_code_b'] ?? '';
            $credits = $_POST['credits'] ?? '';

            $errors = [];
            $courseMessage = "";

            //Validation
            if (empty($course_name)) {
                $errors['course_name'] = "Course name is required";
            }

            if (empty($course_code)) {
                $errors['course_code_b'] = "Course code is required";
            }

            if (!is_numeric($credits) || $credits <= 0) {
                $errors['credits'] = "Credits must be a positive number";
            }

            //Course code check
            $stmt = $conn->prepare("SELECT COUNT(*) FROM courses WHERE course_name = ?");
            $stmt->bind_param("s", $course_code);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $errors['course_code_b'] = "Course code already exists";
            }

            //Only insert if no errors
            if (empty($errors)) {
                $stmt = $conn->prepare("INSERT INTO courses (course_description, course_name, credits) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $course_name, $course_code, $credits);
                if ($stmt->execute()) {
                    $courseMessage = "Course created successfully";
                } else {
                    $courseMessage = "Error creating course: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    $stmt = $conn->prepare("
        SELECT I.instructor_id, CONCAT(U.first_name, ' ', U.last_name) AS instructor_name
        FROM instructors I
        JOIN users U ON I.instructor_id = U.user_id;");
    $stmt->execute();
    $instructors_result = $stmt->get_result();
    $stmt->close();

    $stmt = $conn->prepare("SELECT course_name FROM courses;");
    $stmt->execute();
    $courses_result = $stmt->get_result();
    $stmt->close();
?>

<body>
    <?php require_once('./templates/header.php'); ?>
    
    <div class="name-container">
            <?php echo "<p>" . $_SESSION['first_name'] . " " . $_SESSION["last_name"] . " - " . $_SESSION['role'] . "</p>";?>
    </div>

    <div class="wrapper-container">

    <!-- Upon succesful creation of a site-->
    <?php if(!empty($sectionMessage)): ?>
        <div class="alert alert-success"><?= $sectionMessage ?></div>
    <?php endif; ?>

    <?php if(!empty($courseMessage)): ?>
        <div class="alert alert-success"><?= $courseMessage ?></div>
    <?php endif; ?>

    <div class="title-container">
        <h1>Catalog Setup</h1>
        <hr>
    </div>

    <div class="card shadow-sm " style= "width: 700px;">
    <!-- Toggle header -->
    <div class="card-header p-0">
        <div class="btn-group w-100" role="group">
            <button id="btnSection" type="button" class="btn btn-primary active"> Section </button>
            <button id="btnCourse" type="button" class="btn btn-outline-primary"> Course </button>
        </div>
    </div>

    
    <!-- Form Spacing -->
    <div class="card-body p-4"></div>
    
    <!-- Form Container -->
    <div class="card-body p-2"> 
        <!-- Section Form -->
        <form id="sectionForm" method="POST">
            <h4 class="mb-3">New Section</h4>

            <div class="mb-3">
                <label class="form-label">Instructor ID</label>
                <select type="number" name="instructor_id" class="form-select" required>
                    <?php if ($instructors_result->num_rows > 0): ?>
                        <?php while ($row = $instructors_result->fetch_assoc()): ?>
                            <option value='<?php echo $row['instructor_id']; ?>'><?php echo $row['instructor_name']; ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <?php if(isset($errors['instructor_id'])): ?>
                    <div class="text-danger mt-1"><?= $errors['instructor_id'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Course Code</label>
                <select type="number" name="course_code_a" class="form-select" required>
                    <?php if ($courses_result->num_rows > 0): ?>
                        <?php while ($row = $courses_result->fetch_assoc()): ?>
                            <option value='<?php echo $row['course_name']; ?>'><?php echo $row['course_name']; ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <?php if(isset($errors['course_code_a'])): ?>
                    <div class="text-danger mt-1"><?= $errors['course_code_a'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" class="form-control" name="location" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Semester</label>
                <input type="text" class="form-control" name="semester" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Capacity</label>
                <input type="number" class="form-control" name="capacity" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Start Time</label>
                <input type="time" class="form-control" name="start_time" required>
            </div>

            <div class="mb-3">
                <label class="form-label">End Time</label>
                <input type="time" class="form-control" name="end_time" required>
            </div>

            <div class="mb-3">
                <Label class="form-label d-block">Class Days</Label>
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
            
            <button type="submit" name="submit_section" class="btn btn-success">Create Section</button>
        </form>

        <!-- COURSE FORM -->
        <form id="courseForm" class="d-none" method="POST">
            <h4 class="mb-3">New Course</h4>

            <div class="mb-3">
                <label class="form-label">Course Name</label>
                <input type="text" class="form-control" name="course_name" required>
                <?php if(isset($errors['course_name'])): ?>
                    <div class="text-danger mt-1"><?= $errors['course_name'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Course Code</label>
                <input type="text" class="form-control" name="course_code_b" required>
                <?php if(isset($errors['course_code_b'])): ?>
                    <div class="text-danger mt-1"><?= $errors['course_code_b'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Credits</label>
                <input type="number" class="form-control" name="credits" required>
                <?php if(isset($errors['credits'])): ?>
                    <div class="text-danger mt-1"><?= $errors['credits'] ?></div>
                <?php endif; ?>
            </div>

            <button type="course" name="submit_course" class="btn btn-success">Create Course</button>
        </form>
    </div>

    <!-- Javascript for switching buttons -->
    <script>
        // UI elements
        const btnSection = document.getElementById("btnSection");
        const btnCourse = document.getElementById("btnCourse");
        const sectionForm = document.getElementById("sectionForm");
        const courseForm = document.getElementById("courseForm");

        // Switch to Section
        btnSection.addEventListener("click", () => {
        btnSection.classList.add("btn-primary", "active");
        btnSection.classList.remove("btn-outline-primary");

        btnCourse.classList.add("btn-outline-primary");
        btnCourse.classList.remove("btn-primary", "active");

        sectionForm.classList.remove("d-none");
        courseForm.classList.add("d-none");
        });

        // Switch to Course
        btnCourse.addEventListener("click", () => {
        btnCourse.classList.add("btn-primary", "active");
        btnCourse.classList.remove("btn-outline-primary");

        btnSection.classList.add("btn-outline-primary");
        btnSection.classList.remove("btn-primary", "active");

        courseForm.classList.remove("d-none");
        sectionForm.classList.add("d-none");
        });
    </script>
</body>

<?php
  $conn->close();
?>