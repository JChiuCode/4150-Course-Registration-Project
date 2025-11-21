-- =============================================
-- COMPLETE DATABASE SETUP FOR COURSE REGISTRATION
-- =============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS 4150_project;
USE 4150_project;

-- =============================================
-- DROP TABLES (Clean Slate)
-- =============================================
DROP TABLE IF EXISTS Enrollments;
DROP TABLE IF EXISTS Waitlist;
DROP TABLE IF EXISTS Sections;
DROP TABLE IF EXISTS Prerequisites;
DROP TABLE IF EXISTS Students;
DROP TABLE IF EXISTS Instructors;
DROP TABLE IF EXISTS Administrators;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS Courses;

-- =============================================
-- CREATE TABLES
-- =============================================
CREATE TABLE Courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_description VARCHAR(255) NOT NULL,
    course_name VARCHAR(100) NOT NULL UNIQUE,
    credits DECIMAL(3,2) NOT NULL CHECK(credits <= 3.00)
);

CREATE TABLE Prerequisites(
    prerequisite_id INT NOT NULL,
    course_id INT NOT NULL,
    PRIMARY KEY (prerequisite_id, course_id),
    FOREIGN KEY (prerequisite_id) REFERENCES Courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE
);

CREATE TABLE Users(
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(20) NOT NULL,
    last_name VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    email VARCHAR(60) NOT NULL UNIQUE,
    role VARCHAR(15) NOT NULL
);

CREATE TABLE Instructors(
    instructor_id INT PRIMARY KEY,
    FOREIGN KEY (instructor_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Administrators(
    admin_id INT PRIMARY KEY,
    FOREIGN KEY (admin_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Students(
    student_id INT PRIMARY KEY,
    FOREIGN KEY (student_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Sections(
    section_id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT,
    course_id INT NOT NULL,
    location VARCHAR(100) NOT NULL,
    semester VARCHAR(10) NOT NULL,
    capacity INT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    days VARCHAR(3) NOT NULL,
    FOREIGN KEY (instructor_id) REFERENCES Instructors(instructor_id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE
);

CREATE TABLE Waitlist(
    waitlist_id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    student_id INT NOT NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES Sections(section_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE
);

CREATE TABLE Enrollments(
    enrollment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    section_id INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES Sections(section_id) ON DELETE CASCADE, 
    CONSTRAINT Student_Section_Constraint UNIQUE (student_id, section_id)
);

-- =============================================
-- INSERT SAMPLE DATA
-- =============================================
INSERT INTO Courses (course_description, course_name, credits) VALUES
('Agile Software Development', 'COMP4220', 3.00),
('Advanced & Practical Database Systems', 'COMP4150', 3.00),
('Project Management Techniques and Tools', 'COMP4990A', 3.00),
('Project Management Techniques and Tools', 'COMP4990B', 3.00),
('Computer Architecture II', 'COMP2660', 3.00);

INSERT INTO Users (first_name, last_name, password, email, role) VALUES
('Jackie', 'Li', 'pass123', 'lijacki@uwindsor.ca', 'student'),
('Aaron', 'Sinn', 'pass123', 'sinna@uwindsor.ca', 'student'),
('Jacky', 'Zhu', 'pass123', 'zhu4e@uwindsor.ca', 'student'),
('Jonathan', 'Chiu', 'pass123', 'chiu41@uwindsor.ca', 'student'),
('Saeed', 'Samet', 'pass321', 'Saeed.Samet@uwindsor.ca', 'instructor'),
('Xiaobu', 'Yuan', 'pass321', 'X.Yuan@uwindsor.ca', 'instructor'),
('Arunita', 'Jaekal', 'pass321', 'arunita@uwindsor.ca', 'instructor'),
('Admin', 'User', 'admin123', 'admin@uwindsor.ca', 'administrator');

INSERT INTO Students (student_id) VALUES (1), (2), (3), (4);

INSERT INTO Instructors (instructor_id) VALUES (5), (6), (7);

INSERT INTO Administrators (admin_id) VALUES (8);

INSERT INTO Sections (instructor_id, course_id, location, semester, capacity, start_time, end_time, days) VALUES
(6, 1, 'Room 2', 'F2025', 75, '08:30:00', '09:50:00', 'TT'),  -- COMP4220
(5, 2, 'Room 1', 'F2025', 60, '11:30:00', '14:50:00', 'W'),   -- COMP4150
(7, 3, 'Room 3', 'F2025', 50, '10:00:00', '11:20:00', 'MW'),  -- COMP4990A
(7, 4, 'Room 4', 'F2025', 40, '13:00:00', '14:20:00', 'TT'),  -- COMP4990B
(6, 5, 'Toldo 102', 'F2025', 1, '08:30:00', '09:50:00', 'MW'); -- COMP2660

INSERT INTO Enrollments (student_id, section_id) VALUES
(1, 2), (2, 2), (3, 2), (4, 2), (2,5);

INSERT INTO Waitlist (student_id, section_id) VALUES
(4, 5), (3, 5);  

-- =============================================
-- STORED PROCEDURES
-- =============================================
DELIMITER $$

-- Drop existing procedures
DROP PROCEDURE IF EXISTS EnrollStudent$$
DROP PROCEDURE IF EXISTS DropStudent$$
DROP PROCEDURE IF EXISTS GetCourseTableInformation$$
DROP PROCEDURE IF EXISTS GetCoursesByStudentID$$
DROP PROCEDURE IF EXISTS GetWaitlistForStudentID$$
DROP PROCEDURE IF EXISTS GetCoursesByInstructorID$$
DROP PROCEDURE IF EXISTS GetWaitlistForInstructorID$$
DROP PROCEDURE IF EXISTS GetWaitlistForEveryone$$

-- EnrollStudent: Enrolls student or adds to waitlist if full
CREATE PROCEDURE EnrollStudent(
    IN p_student_id INT,
    IN p_section_id INT,
    IN auto_add_waitlist BOOLEAN
)
BEGIN
    DECLARE current_enrollment INT;
    DECLARE max_capacity INT;

    SELECT COUNT(*) INTO current_enrollment
    FROM Enrollments
    WHERE section_id = p_section_id;

    SELECT capacity INTO max_capacity
    FROM Sections
    WHERE section_id = p_section_id;

    IF current_enrollment < max_capacity THEN
        INSERT INTO Enrollments(student_id, section_id)
        VALUES (p_student_id, p_section_id);
    ELSEIF auto_add_waitlist THEN
        INSERT INTO Waitlist(student_id, section_id)
        VALUES (p_student_id, p_section_id);
    END IF;
END$$

-- DropStudent: Drops student and enrolls next waitlisted student
CREATE PROCEDURE DropStudent(IN p_student_id INT, IN p_section_id INT)
BEGIN
    DECLARE next_student INT;

    DELETE FROM Enrollments
    WHERE student_id = p_student_id AND section_id = p_section_id;

    SELECT student_id INTO next_student
    FROM Waitlist
    WHERE section_id = p_section_id
    ORDER BY joined_at ASC
    LIMIT 1;

    IF next_student IS NOT NULL THEN
        INSERT INTO Enrollments (student_id, section_id)
        VALUES (next_student, p_section_id);
        
        DELETE FROM Waitlist 
        WHERE section_id = p_section_id 
        AND student_id = next_student;
    END IF;
END$$

-- GetCourseTableInformation: Gets courses with search filter
CREATE PROCEDURE GetCourseTableInformation(IN input_course_name VARCHAR(100))
BEGIN
    IF input_course_name IS NOT NULL AND input_course_name != '' THEN 
        SELECT C.course_id, C.course_name, C.course_description, S.location, S.days, S.start_time, S.end_time, S.section_id, 
               CONCAT(U.first_name, ' ', U.last_name) AS instructor_name, 
               CONCAT(S.start_time, ' - ', S.end_time) AS duration, 
               C.credits, 
               CONCAT(COUNT(E.student_id), '/', S.capacity) AS capacity
        FROM courses C
        INNER JOIN sections S ON C.course_id = S.course_id
        INNER JOIN users U ON U.user_id = S.instructor_id
        LEFT JOIN enrollments E ON S.section_id = E.section_id
        WHERE C.course_name LIKE CONCAT('%', input_course_name, '%')
        GROUP BY C.course_id, C.course_name, C.course_description, S.location, S.capacity, S.days, S.start_time, S.end_time, S.section_id, U.first_name, U.last_name, C.credits
        ORDER BY C.course_name;
    ELSE
        SELECT C.course_id, C.course_name, C.course_description, S.location, S.days, S.start_time, S.end_time, S.section_id, 
               CONCAT(U.first_name, ' ', U.last_name) AS instructor_name, 
               CONCAT(S.start_time, ' - ', S.end_time) AS duration, 
               C.credits, 
               CONCAT(COUNT(E.student_id), '/', S.capacity) AS capacity
        FROM courses C
        INNER JOIN sections S ON C.course_id = S.course_id
        INNER JOIN users U ON U.user_id = S.instructor_id
        LEFT JOIN enrollments E ON S.section_id = E.section_id
        GROUP BY C.course_id, C.course_name, C.course_description, S.location, S.capacity, S.days, S.start_time, S.end_time, S.section_id, U.first_name, U.last_name, C.credits
        ORDER BY C.course_name;
    END IF;
END$$

-- GetCoursesByStudentID: Gets courses for a specific student
CREATE PROCEDURE GetCoursesByStudentID(IN input_student_id INT)
BEGIN
    SELECT C.course_id, C.course_name, C.course_description, S.location, S.days, 
           CONCAT(S.start_time, ' - ', S.end_time) AS duration, S.section_id, 
           CONCAT(U.first_name, ' ', U.last_name) AS instructor_name, C.credits, 
           CONCAT(COUNT(E.student_id), '/', S.capacity) AS capacity
    FROM courses C
    INNER JOIN sections S ON C.course_id = S.course_id
    LEFT JOIN enrollments E ON S.section_id = E.section_id
    LEFT JOIN instructors I ON S.instructor_id = I.instructor_id
    LEFT JOIN users U ON I.instructor_id = U.user_id
    WHERE S.section_id IN (
        SELECT section_id 
        FROM enrollments 
        WHERE student_id = input_student_id
    )
    GROUP BY C.course_id, C.course_name, C.course_description, S.location, S.days, S.start_time, S.end_time, S.section_id, S.capacity, U.first_name, U.last_name, C.credits
    ORDER BY C.course_name;
END$$

/* Get Waitist information by student_id */
CREATE PROCEDURE GetWaitlistForStudentID(IN input_student_id INT)
BEGIN
    SELECT 
        C.course_id, C.course_name, C.course_description, S.location, S.days, S.start_time, S.end_time, S.section_id, CONCAT(U.first_name, ' ', U.last_name) AS instructor_name, CONCAT(S.start_time, ' - ', S.end_time) AS duration, C.credits, wl_position.position AS `position`, W.student_id
    FROM Waitlist W
    JOIN Sections S ON W.section_id = S.section_id
    JOIN Courses C ON S.course_id = C.course_id
    LEFT JOIN Instructors I ON S.instructor_id = I.instructor_id
    LEFT JOIN Users U ON I.instructor_id = U.user_id
    JOIN (
        SELECT 
            W1.waitlist_id,
            COUNT(*) AS position
        FROM Waitlist W1
        JOIN Waitlist W2
            ON W1.section_id = W2.section_id 
            AND W2.joined_at <= W1.joined_at
        GROUP BY W1.waitlist_id
    ) AS wl_position ON W.waitlist_id = wl_position.waitlist_id
    WHERE W.student_id = input_student_id
    ORDER BY s.semester, c.course_name;
END $$

/* Get Waitist information by instructor_id */
CREATE PROCEDURE GetWaitlistForInstructorID(IN input_instructor_id INT)
BEGIN
    SELECT 
        C.course_id, C.course_name, U2.first_name, U2.last_name, wl_position.position AS `position`, S.location, S.days, S.start_time, S.end_time, S.section_id, CONCAT(U.first_name, ' ', U.last_name) AS instructor_name, CONCAT(S.start_time, ' - ', S.end_time) AS duration, C.credits,  W.student_id
    FROM Waitlist W
    JOIN Sections S ON W.section_id = S.section_id
    JOIN Courses C ON S.course_id = C.course_id
    LEFT JOIN Instructors I ON S.instructor_id = I.instructor_id
    LEFT JOIN Users U ON I.instructor_id = U.user_id
    LEFT JOIN Users U2 ON W.student_id = U2.user_id
    JOIN (
        SELECT 
            W1.waitlist_id,
            COUNT(*) AS position
        FROM Waitlist W1
        JOIN Waitlist W2
            ON W1.section_id = W2.section_id 
            AND W2.joined_at <= W1.joined_at
        GROUP BY W1.waitlist_id
    ) AS wl_position ON W.waitlist_id = wl_position.waitlist_id
    WHERE S.instructor_id = input_instructor_id
    ORDER BY s.semester, c.course_name;
END $$

/* Get Waitist for Everyone */
CREATE PROCEDURE GetWaitlistForEveryone()
BEGIN
    SELECT 
        C.course_id, C.course_name, U2.first_name, U2.last_name, wl_position.position AS `position`, S.location, S.days, S.start_time, S.end_time, S.section_id, CONCAT(U.first_name, ' ', U.last_name) AS instructor_name, CONCAT(S.start_time, ' - ', S.end_time) AS duration, C.credits, W.student_id
    FROM Waitlist W
    JOIN Sections S ON W.section_id = S.section_id
    JOIN Courses C ON S.course_id = C.course_id
    LEFT JOIN Instructors I ON S.instructor_id = I.instructor_id
    LEFT JOIN Users U ON I.instructor_id = U.user_id
    LEFT JOIN Users U2 ON W.student_id = U2.user_id
    JOIN (
        SELECT 
            W1.waitlist_id,
            COUNT(*) AS position
        FROM Waitlist W1
        JOIN Waitlist W2
            ON W1.section_id = W2.section_id 
            AND W2.joined_at <= W1.joined_at
        GROUP BY W1.waitlist_id
    ) AS wl_position ON W.waitlist_id = wl_position.waitlist_id
    ORDER BY s.semester, c.course_name;
END $$

/* Gets all the courses that an instructor teaches */
CREATE PROCEDURE GetCoursesByInstructorID(IN input_instructor_id INT)
BEGIN
    SELECT C.course_id, C.course_name, C.course_description, S.location, S.days, S.start_time, S.end_time, S.section_id, CONCAT(U.first_name, ' ', U.last_name) AS instructor_name, CONCAT(S.start_time, ' - ', S.end_time) AS duration, C.credits, CONCAT(COUNT(E.student_id), '/', S.capacity) AS capacity
    FROM courses C
    INNER JOIN sections S ON C.course_id = S.course_id
    INNER JOIN users U ON U.user_id = S.instructor_id
    LEFT JOIN enrollments E ON S.section_id = E.section_id
    WHERE S.instructor_id = input_instructor_id
    GROUP BY C.course_id, C.course_name, C.course_description, S.location, S.capacity, S.days, S.start_time, S.end_time, S.section_id, U.first_name, U.last_name, C.credits
    ORDER BY C.course_name;
END $$

DELIMITER ;

-- =============================================
-- TRIGGERS
-- =============================================
DELIMITER $$

-- Drop existing triggers
DROP TRIGGER IF EXISTS RemoveWaitlistAfterEnroll$$
DROP TRIGGER IF EXISTS after_user_insert$$

-- RemoveWaitlistAfterEnroll: Removes student from waitlist when enrolled
CREATE TRIGGER RemoveWaitlistAfterEnroll
AFTER INSERT ON Enrollments
FOR EACH ROW
BEGIN
    DELETE FROM Waitlist 
    WHERE student_id = NEW.student_id 
      AND section_id = NEW.section_id;
END$$

-- after_user_insert: Automatically adds user to correct role table
CREATE TRIGGER after_user_insert
AFTER INSERT ON Users
FOR EACH ROW
BEGIN
    IF NEW.role = 'student' THEN
        INSERT INTO Students (student_id) VALUES (NEW.user_id);
    ELSEIF NEW.role = 'instructor' THEN
        INSERT INTO Instructors (instructor_id) VALUES (NEW.user_id);
    ELSEIF NEW.role = 'administrator' THEN
        INSERT INTO Administrators (admin_id) VALUES (NEW.user_id);
    END IF;
END$$

DELIMITER ;
