/*
Key Queries
*/

--Retrieve all courses and their selections
SELECT c.course_name, c.course_description, s.section_id, s.semester, s.location, s.capacity
FROM Courses c
INNER JOIN Sections s ON c.course_id = s.course_id;

--Get all students from a section
SELECT u.first_name, u.last_name
FROM Enrollments e
INNER JOIN Students s ON e.student_id = s.student_id
INNER JOIN Users u ON s.student_id = u.user_id
WHERE e.section_id = ?;

--Get waitlisted students from a section
SELECT u.first_name, u.last_name, w.joined_at
FROM Waitlist w
INNER JOIN Students s ON w.student_id = s.student_id
INNER JOIN Users u ON s.student_id = u.user_id
WHERE w.section_id = ?
ORDER BY w.joined_at ASC;

--Get a students current courses. Formatted as name, semester, day, start time, end time
SELECT c.course_name, s.semester, s.days, s.start_time, s.end_time
FROM Enrollments e
INNER JOIN Students s ON e.student_id = s.student_id
INNER JOIN Courses c ON s.student_id = c.student_id
WHERE e.student_id = ?

--Select the prerequisites of a course
SELECT c.course_name AS prerequisite_name
FROM Prerequisites p
INNER JOIN Courses c ON p.prerequisite_id = c.course_id
WHERE p.course_id = 1;

--Filter by course name or description
SELECT * FROM Courses
WHERE course_name LIKE '%example%' 
   OR course_description LIKE '%example%';
   
--Filter by semester
SELECT c.course_name, c.course_description, s.section_id, s.days, s.start_time, s.end_time, u.last_name AS instructor
FROM Courses c
INNER JOIN Sections s ON c.course_id = s.course_id
LEFT JOIN Instructors i ON s.instructor_id = i.instructor_id
LEFT JOIN Users u ON i.instructor_id = u.user_id
WHERE s.semester = 'W2025';

--Filter by open seats
SELECT c.course_name, s.section_id, (s.capacity - COUNT(e.enrollment_id)) as seats_remaining
FROM Sections s
JOIN Courses c ON s.course_id = c.course_id
LEFT JOIN Enrollments e ON s.section_id = e.section_id
GROUP BY s.section_id
HAVING seats_remaining > 0;

/*
Stored Procedures
*/

/* Attempts to enroll a student into a course. Adds the student into the course if the course is not full.
   If course is full and auto add waitlist is true, add the student onto the waitlist */
DELIMITER $$
CREATE PROCEDURE EnrollStudent(
    IN p_student_id INT,
    IN p_section_id INT,
    IN auto_add_waitlist BOOLEAN
)
BEGIN
    DECLARE current_enrollment INT;
    DECLARE max_capacity INT;

    /* Find how many students have enrolled */
    SELECT COUNT(*) INTO current_enrollment
    FROM Enrollments
    WHERE section_id = p_section_id;

    /* Get max capacity of the course */
    SELECT capacity INTO max_capacity
    FROM Sections
    WHERE section_id = p_section_id;

    /* Enrollment logic */
    IF current_enrollment < max_capacity THEN
        /* Course is under max capacity. Enroll student into course */
        INSERT INTO Enrollments(student_id, section_id)
        VALUES (p_student_id, p_section_id);
    ELSEIF auto_add_waitlist THEN
        /* Add student into the waitlist */
        INSERT INTO Waitlist(student_id, section_id)
        VALUES (p_student_id, p_section_id);
    END IF;
END $$
DELIMITER ;

/* Drop a student from the course. Add a student from the waitlist into the course if possible. */
DELIMITER $$

CREATE PROCEDURE DropStudent(IN p_student_id INT, IN p_section_id INT)
BEGIN
    /* DECLARE waitlisted_student INT; */
    DECLARE next_student INT;

    /* Remove student from the course */
    DELETE FROM Enrollments
    WHERE student_id = p_student_id AND section_id = p_section_id;

    /* Find next waitlisted student */
    SELECT student_id INTO next_student
    FROM Waitlist
    WHERE section_id = p_section_id
    ORDER BY joined_at ASC
    LIMIT 1;

    IF next_student IS NOT NULL THEN
        /* Enroll the next student and remove from waitlist */
        INSERT INTO Enrollments (student_id, section_id)
        VALUES (next_student, p_section_id);
        
        DELETE FROM Waitlist 
        WHERE section_id = p_section_id 
        AND student_id = next_student;
    END IF;
END $$

DELIMITER ;

/* Select student that is first in the waitlist */
DELIMITER $$

CREATE PROCEDURE GetFirstWaitlistedStudent(OUT first_student_id INT)
BEGIN
    SELECT student_id
    INTO first_student_id
    FROM waitlist
    ORDER BY joined_at ASC
    LIMIT 1;
END $$

DELIMITER ;

/* Gets all course and section info for the course table*/
DELIMITER $$

CREATE PROCEDURE GetCourseTableInformation(IN input_course_name VARCHAR(100))
BEGIN
	IF input_course_name IS NOT NULL THEN 
        SELECT C.course_id, C.course_name, C.course_description, S.location, S.days, S.start_time, S.end_time, S.section_id, CONCAT(U.first_name, ' ', U.last_name) AS instructor_name, CONCAT(S.start_time, ' - ', S.end_time) AS duration, C.credits, CONCAT(COUNT(E.student_id), '/', S.capacity) AS capacity
        FROM courses C
        INNER JOIN sections S ON C.course_id = S.course_id
        INNER JOIN users U ON U.user_id = S.instructor_id
        LEFT JOIN enrollments E ON S.section_id = E.section_id
        WHERE C.course_name = input_course_name
        GROUP BY C.course_id, C.course_name, C.course_description, S.location, S.capacity, S.days, S.start_time, S.end_time, S.section_id, U.first_name, U.last_name, C.credits
        ORDER BY C.course_name;
    ELSE
        SELECT C.course_id, C.course_name, C.course_description, S.location, S.days, S.start_time, S.end_time, S.section_id, CONCAT(U.first_name, ' ', U.last_name) AS instructor_name, CONCAT(S.start_time, ' - ', S.end_time) AS duration, C.credits, CONCAT(COUNT(E.student_id), '/', S.capacity) AS capacity
        FROM courses C
        INNER JOIN sections S ON C.course_id = S.course_id
        INNER JOIN users U ON U.user_id = S.instructor_id
        LEFT JOIN enrollments E ON S.section_id = E.section_id
        GROUP BY C.course_id, C.course_name, C.course_description, S.location, S.capacity, S.days, S.start_time, S.end_time, S.section_id, U.first_name, U.last_name, C.credits
        ORDER BY C.course_name;
   	END IF;
END $$

DELIMITER ;

/* Gets all course for a student_id */
DELIMITER $$

CREATE PROCEDURE GetCoursesByStudentID(IN input_student_id INT)
BEGIN
    SELECT C.course_id, C.course_name, C.course_description, S.location, S.days, CONCAT(S.start_time, ' - ', S.end_time) AS duration, S.section_id, CONCAT(U.first_name, ' ', U.last_name) AS instructor_name, C.credits, CONCAT(COUNT(E.student_id), '/', S.capacity) AS capacity
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
    GROUP BY 
    C.course_id, C.course_name, C.course_description, S.location, S.days, S.start_time, S.end_time, S.section_id, S.capacity, U.first_name, U.last_name, C.credits
    ORDER BY C.course_name;
END $$

DELIMITER ;

/* Gets all course for a instructor_id */
DELIMITER $$

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

/* Get Waitist information by student_id */
DELIMITER $$

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

DELIMITER ;

/* Get Waitist information by instructor_id */
DELIMITER $$

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

DELIMITER ;

/* Get Waitist for Everyone */
DELIMITER $$

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

DELIMITER ;

/* Trigger to make sure no enrolled students are also on the waitlist */
DELIMITER $$

CREATE TRIGGER RemoveWaitlistAfterEnroll
AFTER INSERT ON Enrollments
FOR EACH ROW
BEGIN
    DELETE FROM Waitlist 
    WHERE student_id = NEW.student_id 
      AND section_id = NEW.section_id;
END$$

DELIMITER ;

/* Trigger to insert userID into proper table */
DELIMITER $$

CREATE TRIGGER after_user_insert
AFTER INSERT ON Users
FOR EACH ROW
BEGIN
    -- If the inserted user's role is 'student', insert into Students
    IF NEW.role = 'student' THEN
        INSERT INTO Students (student_id)
        VALUES (NEW.user_id);
    
    -- If the inserted user's role is 'instructor', insert into Instructors
    ELSEIF NEW.role = 'instructor' THEN
        INSERT INTO Instructors (instructor_id)
        VALUES (NEW.user_id);
    
    -- If the inserted user's role is 'administrator', insert into Administrators
    ELSEIF NEW.role = 'administrator' THEN
        INSERT INTO Administrators (admin_id)
        VALUES (NEW.user_id);
    END IF;
END$$

DELIMITER ;
