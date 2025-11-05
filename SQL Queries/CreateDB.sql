CREATE TABLE IF NOT EXISTS Courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_description VARCHAR(255) NOT NULL,
    course_name VARCHAR(100) NOT NULL UNIQUE,
    credits DECIMAL(3,2) NOT NULL CHECK(credits <= 3.00)
);

CREATE TABLE IF NOT EXISTS Prerequisites(
	prerequisite_id INT NOT NULL,
    course_id INT NOT NULL,
    PRIMARY KEY (prerequisite_id, course_id),
    FOREIGN KEY (prerequisite_id) REFERENCES Courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Users(
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(20) NOT NULL,
    last_name VARCHAR(20) NOT NULL,
   	`password` VARCHAR(255) NOT NULL, /* Needs to be long enough to hold password hash*/
    email VARCHAR(60) NOT NULL,
    role VARCHAR(15) NOT NULL
);

CREATE TABLE IF NOT EXISTS Instructors(
	instructor_id INT PRIMARY KEY,
    FOREIGN KEY (instructor_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Administrators(
	admin_id INT PRIMARY KEY,
    FOREIGN KEY (admin_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Students(
	student_id INT PRIMARY KEY,
    FOREIGN KEY (student_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Sections(
	section_id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT,
    course_id INT NOT NULL,
    location VARCHAR(100) NOT NULL,
    semester VARCHAR(10) NOT NULL CHECK (semester REGEXP '^[FS][0-9]{4}$'), /*stored in format like W2025 or F2019*/
    capacity INT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    days VARCHAR(3) NOT NULL, /*Stored as code. W = Wednesday, TT = Tuesday Thursday, MW = Monday Wednesday*/
    FOREIGN KEY (instructor_id) REFERENCES Instructors(instructor_id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Waitlist(
	waitlist_id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    student_id INT NOT NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,  /*Using position(INT) will be diffcult due to having to update the entire table everytime someone leaves*/
    FOREIGN KEY (section_id) REFERENCES Sections(section_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE
);

/* Table of sections the students are enrolled in*/
CREATE TABLE IF NOT EXISTS Enrollments(
	enrollment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    section_id INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES Sections(section_id) ON DELETE CASCADE, 
    CONSTRAINT Student_Section_Constraint UNIQUE (student_id, section_id)
);