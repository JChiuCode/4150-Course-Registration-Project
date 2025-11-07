<?php
  $current_page = basename($_SERVER['PHP_SELF']);
?>


<nav class="navbar navbar-expand-lg bg-body-tertiary"> 
  <div class="container-fluid">
    <a class="navbar-brand" href="#">4150 - University Course Registration</a>
    <div class="collapse navbar-collapse nav justify-content-end" id="navbarNavDropdown">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'home.php') ? 'active' : ''; ?>" href="./home.php">Courses</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'mycourses.php') ? 'active' : ''; ?>" href="./mycourses.php">My Courses</a>
        </li>

        <?php if ($_SESSION['role'] == 'administrator') : ?>
          <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>" href="./users.php">Users</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'create_course.php') ? 'active' : ''; ?>" href="./create_course.php">Create Course</a>
          </li>
        <?php endif; ?>

        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'logout.php') ? 'active' : ''; ?>" href="./logout.php">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
