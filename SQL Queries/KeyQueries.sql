/*
Key Queries
*/

--Retrieve all courses and their selections
SELECT c.course_name, c.course_description, s.selection_id, s.semester, s.location, s.capacity
FROM Courses c
INNER JOIN Sections s ON c.course_id = s.section_id;

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
INNER JOIN Students s WHERE e.student_id = s.student_id
INNER JOIN Courses c WHERE s.student_id = c.student_id
WHERE e.student_id = ?

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
    ELSEIF NEW.role = 'admin' THEN
        INSERT INTO Administrators (admin_id)
        VALUES (NEW.user_id);
    END IF;
END$$

DELIMITER ;