# Borana University Online Student Registration System

A plain PHP + MySQL implementation of the Online Student Registration System described in the ORS proposal.

## Technology
- PHP 8+
- MySQL/MariaDB
- Apache/XAMPP
- HTML5/CSS3
- Bootstrap 5 CDN
- JavaScript
- PDO

## Core workflow
Student Login -> Academic Period -> Eligible Courses -> Prerequisite Validation -> Credit Validation -> Submit -> Advisor Review -> Approve/Reject -> Electronic Slip

## Important academic-rule note
The proposal does not specify exact minimum/maximum credit loads, prerequisite grades, or registration dates. These are therefore stored as configurable data and must be entered according to official Borana University rules.

The implementation also includes `student_course_results`, because prerequisite checking requires evidence of completed courses.

## Installation
1. Copy this folder to `C:\xampp\htdocs\borana_student_registration`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin.
4. Create/import `database/schema.sql`.
5. Open:
   `http://localhost/borana_student_registration/tools/create_admin.php`
6. The installer creates a development administrator:
   - Username: `admin`
   - Password: `Admin@123`
7. Delete or protect the `tools` folder after setup.
8. Open:
   `http://localhost/borana_student_registration/`

## Demo data
`database/demo_data.sql` contains clearly marked sample academic data. Replace it with official university data before real use.

## Project phases
Phase 1: Database + authentication
Phase 2: Student/academic data
Phase 3: Course registration + validation
Phase 4: Advisor approval
Phase 5: Slip/history/add-drop
Phase 6: Reports/security/testing/deployment
