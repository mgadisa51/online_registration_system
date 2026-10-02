CREATE DATABASE IF NOT EXISTS borana_student_registration
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE borana_student_registration;

CREATE TABLE roles (
    role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    status ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

CREATE TABLE departments (
    department_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(150) NOT NULL UNIQUE,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE programs (
    program_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    program_name VARCHAR(150) NOT NULL,
    program_code VARCHAR(50) NOT NULL UNIQUE,
    duration TINYINT UNSIGNED NOT NULL DEFAULT 4,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_program_department FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

CREATE TABLE students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL UNIQUE,
    student_number VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    gender ENUM('Male','Female','Other') NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    department_id INT UNSIGNED NOT NULL,
    program_id INT UNSIGNED NOT NULL,
    year_level TINYINT UNSIGNED NOT NULL,
    status ENUM('active','inactive','graduated','suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_student_department FOREIGN KEY (department_id) REFERENCES departments(department_id),
    CONSTRAINT fk_student_program FOREIGN KEY (program_id) REFERENCES programs(program_id)
) ENGINE=InnoDB;

CREATE TABLE academic_years (
    academic_year_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE semesters (
    semester_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT UNSIGNED NOT NULL,
    semester_name VARCHAR(50) NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    registration_open DATETIME NULL,
    registration_close DATETIME NULL,
    add_drop_open DATETIME NULL,
    add_drop_close DATETIME NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_year_semester (academic_year_id, semester_name),
    CONSTRAINT fk_semester_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(academic_year_id)
) ENGINE=InnoDB;

CREATE TABLE courses (
    course_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(50) NOT NULL UNIQUE,
    course_name VARCHAR(200) NOT NULL,
    credit_hour DECIMAL(4,1) NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_course_department FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

CREATE TABLE curriculum (
    curriculum_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    year_level TINYINT UNSIGNED NOT NULL,
    semester_name VARCHAR(50) NOT NULL,
    course_type ENUM('required','elective') NOT NULL DEFAULT 'required',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_curriculum (program_id, course_id, year_level, semester_name),
    CONSTRAINT fk_curriculum_program FOREIGN KEY (program_id) REFERENCES programs(program_id),
    CONSTRAINT fk_curriculum_course FOREIGN KEY (course_id) REFERENCES courses(course_id)
) ENGINE=InnoDB;

CREATE TABLE prerequisites (
    prerequisite_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    prerequisite_course_id INT UNSIGNED NOT NULL,
    minimum_grade VARCHAR(10) NULL,
    UNIQUE KEY uq_prerequisite (course_id, prerequisite_course_id),
    CONSTRAINT fk_prereq_course FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    CONSTRAINT fk_prereq_required FOREIGN KEY (prerequisite_course_id) REFERENCES courses(course_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Added for implementation: prerequisite checking needs completed-course results.
CREATE TABLE student_course_results (
    result_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    grade VARCHAR(10) NULL,
    grade_point DECIMAL(4,2) NULL,
    result_status ENUM('passed','failed','incomplete','withdrawn') NOT NULL,
    academic_year_id INT UNSIGNED NULL,
    semester_id INT UNSIGNED NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_course_result (student_id, course_id, academic_year_id, semester_id),
    CONSTRAINT fk_result_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_result_course FOREIGN KEY (course_id) REFERENCES courses(course_id),
    CONSTRAINT fk_result_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(academic_year_id) ON DELETE SET NULL,
    CONSTRAINT fk_result_semester FOREIGN KEY (semester_id) REFERENCES semesters(semester_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Configurable official registration rules.
CREATE TABLE registration_rules (
    rule_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    semester_id INT UNSIGNED NOT NULL UNIQUE,
    min_credit DECIMAL(5,1) NULL,
    max_credit DECIMAL(5,1) NULL,
    allow_add_drop TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rule_semester FOREIGN KEY (semester_id) REFERENCES semesters(semester_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE advisors (
    advisor_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    department_id INT UNSIGNED NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_advisor_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_advisor_department FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

CREATE TABLE registrations (
    registration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NOT NULL,
    registration_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft','submitted','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'draft',
    advisor_id INT UNSIGNED NULL,
    advisor_comment TEXT NULL,
    approved_at DATETIME NULL,
    UNIQUE KEY uq_student_registration_period (student_id, academic_year_id, semester_id),
    CONSTRAINT fk_registration_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_registration_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(academic_year_id),
    CONSTRAINT fk_registration_semester FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_registration_advisor FOREIGN KEY (advisor_id) REFERENCES advisors(advisor_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE registration_details (
    registration_detail_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_id BIGINT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    credit_hour DECIMAL(4,1) NOT NULL,
    status ENUM('selected','approved','dropped','rejected') NOT NULL DEFAULT 'selected',
    UNIQUE KEY uq_registration_course (registration_id, course_id),
    CONSTRAINT fk_detail_registration FOREIGN KEY (registration_id) REFERENCES registrations(registration_id) ON DELETE CASCADE,
    CONSTRAINT fk_detail_course FOREIGN KEY (course_id) REFERENCES courses(course_id)
) ENGINE=InnoDB;

CREATE TABLE add_drop_requests (
    request_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_id BIGINT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    request_type ENUM('add','drop') NOT NULL,
    reason TEXT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_adddrop_registration FOREIGN KEY (registration_id) REFERENCES registrations(registration_id) ON DELETE CASCADE,
    CONSTRAINT fk_adddrop_course FOREIGN KEY (course_id) REFERENCES courses(course_id),
    CONSTRAINT fk_adddrop_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE notifications (
    notification_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread','read') NOT NULL DEFAULT 'unread',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) NULL,
    record_id VARCHAR(100) NULL,
    details TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO roles (role_name) VALUES
('student'), ('advisor'), ('department_admin'), ('registrar'), ('system_admin')
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name);

CREATE INDEX idx_curriculum_lookup ON curriculum(program_id, year_level, semester_name, status);
CREATE INDEX idx_prereq_course ON prerequisites(course_id);
CREATE INDEX idx_results_student_course ON student_course_results(student_id, course_id);
CREATE INDEX idx_registration_status ON registrations(status, semester_id);
