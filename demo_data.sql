USE borana_student_registration;

-- DEMO DATA ONLY. Replace with official Borana University data.

INSERT INTO departments (department_name) VALUES
('Computer Science'),
('Information Technology')
ON DUPLICATE KEY UPDATE department_name = VALUES(department_name);

INSERT INTO programs (department_id, program_name, program_code, duration)
SELECT department_id, 'Bachelor of Science in Computer Science', 'BSC-CS', 4
FROM departments WHERE department_name='Computer Science'
ON DUPLICATE KEY UPDATE program_name=VALUES(program_name);

INSERT INTO academic_years (academic_year) VALUES ('2026/2027')
ON DUPLICATE KEY UPDATE academic_year=VALUES(academic_year);

INSERT INTO semesters (
    academic_year_id, semester_name, start_date, end_date,
    registration_open, registration_close, add_drop_open, add_drop_close
)
SELECT academic_year_id, 'Semester I', '2026-10-01', '2027-02-15',
       '2026-10-01 08:00:00', '2026-10-31 23:59:59',
       '2026-10-01 08:00:00', '2026-11-07 23:59:59'
FROM academic_years WHERE academic_year='2026/2027'
ON DUPLICATE KEY UPDATE semester_name=VALUES(semester_name);

INSERT INTO courses (course_code, course_name, credit_hour, department_id)
SELECT 'CS101', 'Introduction to Computer Science', 3, department_id
FROM departments WHERE department_name='Computer Science'
ON DUPLICATE KEY UPDATE course_name=VALUES(course_name);

INSERT INTO courses (course_code, course_name, credit_hour, department_id)
SELECT 'CS201', 'Database Systems', 3, department_id
FROM departments WHERE department_name='Computer Science'
ON DUPLICATE KEY UPDATE course_name=VALUES(course_name);

INSERT INTO courses (course_code, course_name, credit_hour, department_id)
SELECT 'CS301', 'Advanced Database Systems', 3, department_id
FROM departments WHERE department_name='Computer Science'
ON DUPLICATE KEY UPDATE course_name=VALUES(course_name);

INSERT INTO curriculum (program_id, course_id, year_level, semester_name, course_type)
SELECT p.program_id, c.course_id, 1, 'Semester I', 'required'
FROM programs p JOIN courses c ON c.course_code='CS101'
WHERE p.program_code='BSC-CS'
ON DUPLICATE KEY UPDATE status='active';

INSERT INTO curriculum (program_id, course_id, year_level, semester_name, course_type)
SELECT p.program_id, c.course_id, 2, 'Semester I', 'required'
FROM programs p JOIN courses c ON c.course_code='CS201'
WHERE p.program_code='BSC-CS'
ON DUPLICATE KEY UPDATE status='active';

INSERT INTO curriculum (program_id, course_id, year_level, semester_name, course_type)
SELECT p.program_id, c.course_id, 3, 'Semester I', 'required'
FROM programs p JOIN courses c ON c.course_code='CS301'
WHERE p.program_code='BSC-CS'
ON DUPLICATE KEY UPDATE status='active';

INSERT INTO prerequisites (course_id, prerequisite_course_id, minimum_grade)
SELECT c.course_id, p.course_id, 'C'
FROM courses c CROSS JOIN courses p
WHERE c.course_code='CS301' AND p.course_code='CS201'
ON DUPLICATE KEY UPDATE minimum_grade=VALUES(minimum_grade);

INSERT INTO registration_rules (semester_id, min_credit, max_credit)
SELECT semester_id, 12, 21
FROM semesters s JOIN academic_years y ON y.academic_year_id=s.academic_year_id
WHERE y.academic_year='2026/2027' AND s.semester_name='Semester I'
ON DUPLICATE KEY UPDATE min_credit=VALUES(min_credit), max_credit=VALUES(max_credit);
