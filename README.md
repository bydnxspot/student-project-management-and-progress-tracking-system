# Student Project Management & Progress Tracking System (SPMPTS)

PHP / MySQL / HTML5 / CSS3 / JavaScript (Bootstrap 5) university project-management portal with three role-based portals: **Student**, **Supervisor** and **Administrator**.

> **Status:** written against the specification in the project prompt. It has been syntax-balance checked only. It has **not** been run on PHP/MySQL (no PHP runtime was available where it was built). Follow "First-run checklist" below and report any error message you see.

## Installation (XAMPP on Windows)
1. Extract the folder to `C:\xampp\htdocs\student-project-tracker` (the folder must be named exactly that, or change `BASE_URL` in `config/db.php`).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open http://localhost/phpmyadmin → **Import** → choose `database.sql` → **Go**. This creates the `student_project_tracker` database with demo data.
4. Check `config/db.php` (defaults: user `root`, empty password).
5. Open http://localhost/student-project-tracker
6. Log in with a demo account below.

Requires PHP 8.0+ (XAMPP 8.x) and MySQL 8 / MariaDB 10.4+. Pages load Bootstrap from a CDN, so the browser needs internet access.

## DEMONSTRATION ACCOUNTS (demo only — change before real use)
| Role | Email | Password |
|---|---|---|
| Administrator | admin@spmpts.edu.ng | Admin@123 |
| Supervisor | n.okafor@spmpts.edu.ng | Supervisor@123 |
| Supervisor | o.adeyemi@spmpts.edu.ng | Supervisor@123 |
| Student | chinedu.okeke@student.spmpts.edu.ng | Student@123 |
| Student | aisha.bello@student.spmpts.edu.ng | Student@123 |
| Student | tunde.adebayo@student.spmpts.edu.ng | Student@123 |
| Student | blessing.eze@student.spmpts.edu.ng | Student@123 |
| Student | ibrahim.musa@student.spmpts.edu.ng | Student@123 |
| Student | funke.adeleke@student.spmpts.edu.ng | Student@123 |

**Changing passwords:** Admin → Users → Edit → "Reset password". Students can use My Profile → Change password. Delete or deactivate demo accounts before real use.
If demo logins fail, run `C:\xampp\php\php.exe tools\reset_demo_passwords.php` from the project folder.

## Features
- **Student:** register, dashboard, project & supervisor, 7 milestones with status/percent/deadlines, upload documents (validated), submission history, feedback & revision requests, profile & password change.
- **Supervisor:** assigned students, submissions list, secure download, approve / reject / request revision / mark under review with feedback, update milestone status & percent, pending reviews and 30-day deadline list, feedback history.
- **Administrator:** dashboard statistics, create/edit/activate/deactivate users, assign supervisors, create/manage projects (7 milestones auto-generated), progress reports with **CSV export**, activity logs, academic settings.
- **Shared:** notifications, searchable/filterable tables, responsive navy/blue layout.
- **Progress rule:** project progress = average completion % of its 7 milestones. Submit → milestone "Submitted" (≥70%); Approve → 100%; Revision/Reject → status "Revision Required" (capped at 60% / 40%).

## Security
`password_hash` / `password_verify`; PDO prepared statements; session auth with `session_regenerate_id` on login; role checks on every page; CSRF tokens on all POST forms; `htmlspecialchars` output escaping; uploads limited by extension and size, stored under random names in `uploads/` (direct web access and PHP execution blocked by `.htaccess`), served only via `download.php` after an ownership check; CSV export neutralises formula injection.

## Database
`student_project_tracker`: `users`, `students`, `supervisors`, `projects`, `project_milestones`, `submissions`, `supervisor_feedback`, `notifications`, `activity_logs`, `academic_settings` (primary/foreign keys, indexes, timestamps). Demo submissions have no attached file; new uploads do.

## First-run checklist (not yet verified by the author)
1. Log in as each of the three roles. 2. Student: upload a PDF at *Submit / Upload*. 3. Supervisor: open it via *Submissions → Review*, download it, approve it; check the student's progress changed. 4. Admin: add a user, create a project, export the CSV from *Reports*. 5. Visit `/admin/dashboard.php` while logged in as a student — you should get "Access denied".

## Troubleshooting
- **Blank page / HTTP 500:** enable errors in `php.ini` (`display_errors=On`) or check `C:\xampp\apache\logs\error.log`.
- **"Database connection failed":** MySQL not running, wrong credentials in `config/db.php`, or `database.sql` not imported.
- **404 / broken CSS links:** folder name differs from `BASE_URL` in `config/db.php`.
- **Upload fails:** raise `upload_max_filesize` and `post_max_size` in `php.ini`, restart Apache; make sure `uploads/` is writable.
- **Import error on `CHECK` constraint:** you are on an old MySQL/MariaDB; remove the `chk_ms_completion` line in `database.sql`.

## Upload to GitHub (new repository `student-project-management-system`)
```
cd student-project-tracker
git init
git add .
git commit -m "Initial commit: SPMPTS"
git branch -M main
git remote add origin https://github.com/<your-username>/student-project-management-system.git
git push -u origin main
```
Create the empty repository on github.com first (no README/licence), then run the commands above.
