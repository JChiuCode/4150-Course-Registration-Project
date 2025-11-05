/* populating tables */
INSERT INTO Courses (course_description, course_name, credits) VALUES
('Agile Sfotware Development', 'COMP4220', 3.00),
('Advanced & Practical Database Systems', 'COMP4150', 3.00),
('Project Management Techniques and Tools', 'COMP4990A', 3.00),
('Project Management Techniques and Tools', 'COMP4990B', 3.00);

INSERT INTO Users (first_name, last_name, password, email, role) VALUES
('Jackie', 'Li', 'pass123', 'lijacki@uwindsor.ca', 'student'),
('Aaron', 'Sinn', 'pass123', 'sinna@uwindsor.ca', 'student'),
('Jacky', 'Zhu', 'pass123', 'zhu4e@uwindsor.ca', 'student'),
('Jonathan', 'Chiu', 'pass123', 'chiu41@uwindsor.ca', 'student'),
('Saeed', 'Samet', 'pass321', 'Saeed.Samet@uwindsor.ca', 'instructor'),
('Xiaobu', 'Yuan', 'pass321', 'X.Yuan@uwindsor.ca', 'instructor'),
('Admin', 'User', 'admin123', 'admin@uwindsor.ca', 'administrator');

INSERT INTO Students (student_id) VALUES (1), (2), (3), (4);

INSERT INTO Instructors (instructor_id) VALUES (5), (6);

INSERT INTO Administrators (admin_id) VALUES (7);

INSERT INTO Sections (instructor_id, course_id, location, semester, capacity, start_time, end_time, days) VALUES
(6, 1, 'Room 2', 'F2025', 75, '08:30:00', '9:50:00', 'TT'),
(5, 2, 'Room 1', 'F2025', 60, '11:30:00', '2:50:00', 'W');

INSERT INTO Enrollments (student_id, section_id) VALUES
(1, 2), (2, 2), (3, 2), (4, 2);

