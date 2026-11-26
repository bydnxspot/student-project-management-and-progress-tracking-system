-- Student Project Management & Progress Tracking System (SPMPTS)
-- Import this file in phpMyAdmin (Import tab). It creates the database, tables and DEMO data.
-- DEMO ACCOUNTS ONLY - change all passwords before real use (see README.md).
CREATE DATABASE IF NOT EXISTS student_project_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_project_tracker;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS notifications, activity_logs, supervisor_feedback, submissions, project_milestones, projects, students, supervisors, academic_settings, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','supervisor','student') NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role), INDEX idx_users_status (status)
) ENGINE=InnoDB;

CREATE TABLE supervisors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  staff_no VARCHAR(30) NOT NULL UNIQUE,
  department VARCHAR(120) NOT NULL,
  phone VARCHAR(25) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sup_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  matric_no VARCHAR(30) NOT NULL UNIQUE,
  department VARCHAR(120) NOT NULL,
  level VARCHAR(5) NOT NULL DEFAULT '400',
  phone VARCHAR(25) NULL,
  supervisor_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_stu_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_stu_sup FOREIGN KEY (supervisor_id) REFERENCES supervisors(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL UNIQUE,
  supervisor_id INT UNSIGNED NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  status ENUM('Not Started','In Progress','Completed') NOT NULL DEFAULT 'Not Started',
  progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_proj_stu FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_proj_sup FOREIGN KEY (supervisor_id) REFERENCES supervisors(id) ON DELETE SET NULL,
  INDEX idx_proj_sup (supervisor_id), INDEX idx_proj_status (status)
) ENGINE=InnoDB;

CREATE TABLE project_milestones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  milestone_no TINYINT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  start_date DATE NOT NULL,
  due_date DATE NOT NULL,
  status ENUM('Not Started','In Progress','Submitted','Under Review','Approved','Revision Required') NOT NULL DEFAULT 'Not Started',
  completion TINYINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ms_proj FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  UNIQUE KEY uq_project_ms (project_id, milestone_no),
  INDEX idx_ms_status (status), INDEX idx_ms_due (due_date),
  CONSTRAINT chk_ms_completion CHECK (completion <= 100)
) ENGINE=InnoDB;

CREATE TABLE submissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  milestone_id INT UNSIGNED NOT NULL,
  project_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  notes TEXT NULL,
  file_name VARCHAR(255) NULL,
  file_path VARCHAR(255) NULL,
  status ENUM('Pending','Under Review','Approved','Rejected','Revision Required') NOT NULL DEFAULT 'Pending',
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME NULL,
  CONSTRAINT fk_sub_ms FOREIGN KEY (milestone_id) REFERENCES project_milestones(id) ON DELETE CASCADE,
  CONSTRAINT fk_sub_proj FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_sub_stu FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  INDEX idx_sub_status (status), INDEX idx_sub_date (submitted_at)
) ENGINE=InnoDB;

CREATE TABLE supervisor_feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  submission_id INT UNSIGNED NULL,
  milestone_id INT UNSIGNED NOT NULL,
  supervisor_id INT UNSIGNED NOT NULL,
  comment TEXT NOT NULL,
  decision ENUM('Approved','Rejected','Revision Required','Under Review','Comment') NOT NULL DEFAULT 'Comment',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fb_sub FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
  CONSTRAINT fk_fb_ms FOREIGN KEY (milestone_id) REFERENCES project_milestones(id) ON DELETE CASCADE,
  CONSTRAINT fk_fb_sup FOREIGN KEY (supervisor_id) REFERENCES supervisors(id) ON DELETE CASCADE,
  INDEX idx_fb_date (created_at)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  message VARCHAR(500) NOT NULL,
  link VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user_read (user_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  details VARCHAR(500) NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_log_date (created_at)
) ENGINE=InnoDB;

CREATE TABLE academic_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(80) NOT NULL UNIQUE,
  setting_value VARCHAR(255) NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO academic_settings (setting_key, setting_value) VALUES
('institution_name','University of Demonstration, Nigeria'),('academic_session','2026/2027'),('session_start_date','2026-09-01'),('max_upload_mb','10');

-- Demo users (passwords: Admin@123 / Supervisor@123 / Student@123)
INSERT INTO users (full_name,email,password_hash,role,status,last_login) VALUES
('Prof. Emeka Nwosu','admin@spmpts.edu.ng','$2y$10$RzZ5nhTsKmeOsddTwYXzTu83dXCeeEdGldgLE72YOtJECDWMUAWUW','admin','active','2026-11-25 09:10:00'),
('Dr. Ngozi Okafor','n.okafor@spmpts.edu.ng','$2y$10$CRt1w1kVM2lHTNiM64EF3uy.pudD.5T8k2olzjdOxkI.QVma9G8mm','supervisor','active','2026-11-25 09:10:00'),
('Prof. Olumide Adeyemi','o.adeyemi@spmpts.edu.ng','$2y$10$UFr1OHbboKoXZ3hV2ZShZ.Y3pkEhjkNe6U6pjVR0fj7RUeUlqwesy','supervisor','active','2026-11-25 09:10:00'),
('Chinedu Okeke','chinedu.okeke@student.spmpts.edu.ng','$2y$10$qQ50Zx/TihO0C9O.xHOhROHaVYCxzlDbMgnXmbJM19baq14i9Vnj2','student','active','2026-11-24 14:04:00'),
('Aisha Bello','aisha.bello@student.spmpts.edu.ng','$2y$10$LO5clYoU1gb5Hi4kc5sPMuEqRhhRMgQl.dN60WKdIElbPIcknsYO.','student','active','2026-11-24 14:05:00'),
('Tunde Adebayo','tunde.adebayo@student.spmpts.edu.ng','$2y$10$2kaBhlvjW4ylOqiZhqNPX.qqQiJW7syK3IFREiu5nmmvXWBAf8iBK','student','active','2026-11-24 14:06:00'),
('Blessing Eze','blessing.eze@student.spmpts.edu.ng','$2y$10$20.aviKGLNB2Bcui1V.Ga.atGWYzid4PR/JhaEiZUa6FSCin.McdO','student','active','2026-11-24 14:07:00'),
('Ibrahim Musa','ibrahim.musa@student.spmpts.edu.ng','$2y$10$VgE3lB2JMa1AEpII.QF8BOYMtPVfknOZEmP3NPXHWAI/gbw785KY6','student','active','2026-11-24 14:08:00'),
('Funke Adeleke','funke.adeleke@student.spmpts.edu.ng','$2y$10$rkgM7TXPGNRb16Dy.0ITYOn8ryc7mlAafhRUAdvnBFYo6pLBjzidO','student','active','2026-11-24 14:09:00');
INSERT INTO supervisors (user_id,staff_no,department,phone) VALUES (2,'STF/0101','Computer Science','08055550101'),(3,'STF/0102','Computer Science','08055550102');
INSERT INTO students (user_id,matric_no,department,level,phone,supervisor_id) VALUES
(4,'CSC/2022/001','Computer Science','400','08031234501',1),
(5,'CSC/2022/014','Computer Science','400','08031234502',1),
(6,'CSC/2022/027','Computer Science','400','08031234503',1),
(7,'CSC/2022/033','Computer Science','400','08031234504',2),
(8,'CSC/2022/041','Computer Science','400','08031234505',2),
(9,'CSC/2022/052','Computer Science','400','08031234506',2);
INSERT INTO projects (id,student_id,supervisor_id,title,description,status,progress,created_at) VALUES
(1,1,1,'Online Examination Management System with Automated Grading','A web-based system that lets lecturers create question banks, schedule computer-based tests and grade objective answers automatically.','In Progress',53,'2026-09-02 10:00:00'),
(2,2,1,'Hospital Appointment Scheduling and Patient Queue System','A portal for booking clinic appointments and managing patient queues in a teaching hospital outpatient department.','In Progress',36,'2026-09-02 10:00:00'),
(3,3,1,'Smart Library Management System for University Libraries','A system for cataloguing books, tracking loans and fines, and notifying students about overdue items.','In Progress',67,'2026-09-02 10:00:00'),
(4,4,2,'Farm Produce Marketplace Web Application','A marketplace connecting smallholder farmers with buyers, with product listings, orders and delivery tracking.','In Progress',24,'2026-09-02 10:00:00'),
(5,5,2,'Student Result Processing and Transcript Generation System','Automates score entry, GPA/CGPA computation and transcript generation for departmental results.','In Progress',49,'2026-09-02 10:00:00'),
(6,6,2,'School Fees Payment Tracking and Receipt Verification System','Tracks student fee payments, issues verifiable e-receipts and reports outstanding balances to the bursary.','In Progress',20,'2026-09-02 10:00:00');
INSERT INTO project_milestones (id,project_id,milestone_no,title,description,start_date,due_date,status,completion) VALUES
(1,1,1,'Topic Selection and Approval','Select a viable project topic and obtain supervisor approval.','2026-09-01','2026-10-06','Approved',100),
(2,1,2,'Proposal Preparation','Prepare and submit the project proposal with problem statement, aim and objectives.','2026-10-07','2026-10-27','Approved',100),
(3,1,3,'Literature Review','Review related works and existing systems relevant to the project.','2026-10-28','2026-12-05','Approved',100),
(4,1,4,'System Analysis and Design','Analyse requirements and produce system design (DFDs, ERD, UI designs).','2026-12-06','2027-02-03','Submitted',70),
(5,1,5,'Implementation','Develop and implement the system according to the approved design.','2027-02-04','2027-03-30','Not Started',0),
(6,1,6,'Testing','Test the system, document results and fix defects.','2027-03-31','2027-05-04','Not Started',0),
(7,1,7,'Final Documentation and Submission','Compile and submit the final project report and source code.','2027-05-05','2027-06-08','Not Started',0),
(8,2,1,'Topic Selection and Approval','Select a viable project topic and obtain supervisor approval.','2026-09-01','2026-10-06','Approved',100),
(9,2,2,'Proposal Preparation','Prepare and submit the project proposal with problem statement, aim and objectives.','2026-10-07','2026-10-27','Approved',100),
(10,2,3,'Literature Review','Review related works and existing systems relevant to the project.','2026-10-28','2026-12-05','Revision Required',50),
(11,2,4,'System Analysis and Design','Analyse requirements and produce system design (DFDs, ERD, UI designs).','2026-12-06','2027-02-03','Not Started',0),
(12,2,5,'Implementation','Develop and implement the system according to the approved design.','2027-02-04','2027-03-30','Not Started',0),
(13,2,6,'Testing','Test the system, document results and fix defects.','2027-03-31','2027-05-04','Not Started',0),
(14,2,7,'Final Documentation and Submission','Compile and submit the final project report and source code.','2027-05-05','2027-06-08','Not Started',0),
(15,3,1,'Topic Selection and Approval','Select a viable project topic and obtain supervisor approval.','2026-09-01','2026-10-06','Approved',100),
(16,3,2,'Proposal Preparation','Prepare and submit the project proposal with problem statement, aim and objectives.','2026-10-07','2026-10-27','Approved',100),
(17,3,3,'Literature Review','Review related works and existing systems relevant to the project.','2026-10-28','2026-12-05','Approved',100),
(18,3,4,'System Analysis and Design','Analyse requirements and produce system design (DFDs, ERD, UI designs).','2026-12-06','2027-02-03','Approved',100),
(19,3,5,'Implementation','Develop and implement the system according to the approved design.','2027-02-04','2027-03-30','Under Review',70),
(20,3,6,'Testing','Test the system, document results and fix defects.','2027-03-31','2027-05-04','Not Started',0),
(21,3,7,'Final Documentation and Submission','Compile and submit the final project report and source code.','2027-05-05','2027-06-08','Not Started',0),
(22,4,1,'Topic Selection and Approval','Select a viable project topic and obtain supervisor approval.','2026-09-01','2026-10-06','Approved',100),
(23,4,2,'Proposal Preparation','Prepare and submit the project proposal with problem statement, aim and objectives.','2026-10-07','2026-10-27','Submitted',70),
(24,4,3,'Literature Review','Review related works and existing systems relevant to the project.','2026-10-28','2026-12-05','Not Started',0),
(25,4,4,'System Analysis and Design','Analyse requirements and produce system design (DFDs, ERD, UI designs).','2026-12-06','2027-02-03','Not Started',0),
(26,4,5,'Implementation','Develop and implement the system according to the approved design.','2027-02-04','2027-03-30','Not Started',0),
(27,4,6,'Testing','Test the system, document results and fix defects.','2027-03-31','2027-05-04','Not Started',0),
(28,4,7,'Final Documentation and Submission','Compile and submit the final project report and source code.','2027-05-05','2027-06-08','Not Started',0),
(29,5,1,'Topic Selection and Approval','Select a viable project topic and obtain supervisor approval.','2026-09-01','2026-10-06','Approved',100),
(30,5,2,'Proposal Preparation','Prepare and submit the project proposal with problem statement, aim and objectives.','2026-10-07','2026-10-27','Approved',100),
(31,5,3,'Literature Review','Review related works and existing systems relevant to the project.','2026-10-28','2026-12-05','Approved',100),
(32,5,4,'System Analysis and Design','Analyse requirements and produce system design (DFDs, ERD, UI designs).','2026-12-06','2027-02-03','In Progress',40),
(33,5,5,'Implementation','Develop and implement the system according to the approved design.','2027-02-04','2027-03-30','Not Started',0),
(34,5,6,'Testing','Test the system, document results and fix defects.','2027-03-31','2027-05-04','Not Started',0),
(35,5,7,'Final Documentation and Submission','Compile and submit the final project report and source code.','2027-05-05','2027-06-08','Not Started',0),
(36,6,1,'Topic Selection and Approval','Select a viable project topic and obtain supervisor approval.','2026-09-01','2026-10-06','Approved',100),
(37,6,2,'Proposal Preparation','Prepare and submit the project proposal with problem statement, aim and objectives.','2026-10-07','2026-10-27','Revision Required',40),
(38,6,3,'Literature Review','Review related works and existing systems relevant to the project.','2026-10-28','2026-12-05','Not Started',0),
(39,6,4,'System Analysis and Design','Analyse requirements and produce system design (DFDs, ERD, UI designs).','2026-12-06','2027-02-03','Not Started',0),
(40,6,5,'Implementation','Develop and implement the system according to the approved design.','2027-02-04','2027-03-30','Not Started',0),
(41,6,6,'Testing','Test the system, document results and fix defects.','2027-03-31','2027-05-04','Not Started',0),
(42,6,7,'Final Documentation and Submission','Compile and submit the final project report and source code.','2027-05-05','2027-06-08','Not Started',0);
INSERT INTO submissions (id,milestone_id,project_id,student_id,title,notes,file_name,file_path,status,submitted_at,reviewed_at) VALUES
(1,1,1,1,'Topic Selection and Approval document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-09-28 11:21:00','2026-10-01 15:00:00'),
(2,2,1,1,'Proposal Preparation document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-10-19 11:22:00','2026-10-22 15:00:00'),
(3,3,1,1,'Literature Review document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-11-27 11:23:00','2026-11-30 15:00:00'),
(4,4,1,1,'System Analysis and Design - draft v1','Please find my latest draft attached for your review.',NULL,NULL,'Pending','2026-11-20 10:45:00',NULL),
(5,8,2,2,'Topic Selection and Approval document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-09-27 11:21:00','2026-09-30 15:00:00'),
(6,9,2,2,'Proposal Preparation document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-10-18 11:22:00','2026-10-21 15:00:00'),
(7,10,2,2,'Literature Review - first submission','First draft for review.',NULL,NULL,'Revision Required','2026-11-11 13:00:00','2026-11-18 16:30:00'),
(8,15,3,3,'Topic Selection and Approval document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-09-26 11:21:00','2026-09-29 15:00:00'),
(9,16,3,3,'Proposal Preparation document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-10-17 11:22:00','2026-10-20 15:00:00'),
(10,17,3,3,'Literature Review document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-11-25 11:23:00','2026-11-28 15:00:00'),
(11,18,3,3,'System Analysis and Design document','Submitted for review as scheduled.',NULL,NULL,'Approved','2027-01-24 11:24:00','2027-01-27 15:00:00'),
(12,19,3,3,'Implementation - draft v1','Please find my latest draft attached for your review.',NULL,NULL,'Under Review','2026-11-22 10:45:00',NULL),
(13,22,4,4,'Topic Selection and Approval document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-09-25 11:21:00','2026-09-28 15:00:00'),
(14,23,4,4,'Proposal Preparation - draft v1','Please find my latest draft attached for your review.',NULL,NULL,'Pending','2026-11-23 10:45:00',NULL),
(15,29,5,5,'Topic Selection and Approval document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-09-28 11:21:00','2026-10-01 15:00:00'),
(16,30,5,5,'Proposal Preparation document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-10-19 11:22:00','2026-10-22 15:00:00'),
(17,31,5,5,'Literature Review document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-11-27 11:23:00','2026-11-30 15:00:00'),
(18,36,6,6,'Topic Selection and Approval document','Submitted for review as scheduled.',NULL,NULL,'Approved','2026-09-27 11:21:00','2026-09-30 15:00:00'),
(19,37,6,6,'Proposal Preparation - first submission','First draft for review.',NULL,NULL,'Revision Required','2026-11-15 13:00:00','2026-11-18 16:30:00'),
(20,37,6,6,'Proposal - initial attempt','Initial proposal.',NULL,NULL,'Rejected','2026-11-05 09:30:00','2026-11-07 12:00:00');
INSERT INTO supervisor_feedback (id,submission_id,milestone_id,supervisor_id,comment,decision,created_at) VALUES
(1,1,1,1,'Well structured and properly referenced. Approved.','Approved','2026-10-01 15:00:00'),
(2,2,2,1,'Design diagrams are consistent and complete. Approved - you may begin implementation.','Approved','2026-10-22 15:00:00'),
(3,3,3,1,'Good work. The scope is clear and well justified. Approved - proceed to the next stage.','Approved','2026-11-30 15:00:00'),
(5,5,8,1,'Well structured and properly referenced. Approved.','Approved','2026-09-30 15:00:00'),
(6,6,9,1,'Design diagrams are consistent and complete. Approved - you may begin implementation.','Approved','2026-10-21 15:00:00'),
(7,7,10,1,'The literature review is too thin. Cite at least 15 relevant sources and compare existing systems against your proposed features.','Revision Required','2026-11-18 16:30:00'),
(8,8,15,1,'Well structured and properly referenced. Approved.','Approved','2026-09-29 15:00:00'),
(9,9,16,1,'Design diagrams are consistent and complete. Approved - you may begin implementation.','Approved','2026-10-20 15:00:00'),
(10,10,17,1,'Good work. The scope is clear and well justified. Approved - proceed to the next stage.','Approved','2026-11-28 15:00:00'),
(11,11,18,1,'Well structured and properly referenced. Approved.','Approved','2027-01-27 15:00:00'),
(12,12,19,1,'Received. I am reviewing the implementation modules and will respond within the week.','Under Review','2026-11-24 09:00:00'),
(13,13,22,2,'Well structured and properly referenced. Approved.','Approved','2026-09-28 15:00:00'),
(15,15,29,2,'Well structured and properly referenced. Approved.','Approved','2026-10-01 15:00:00'),
(16,16,30,2,'Design diagrams are consistent and complete. Approved - you may begin implementation.','Approved','2026-10-22 15:00:00'),
(17,17,31,2,'Good work. The scope is clear and well justified. Approved - proceed to the next stage.','Approved','2026-11-30 15:00:00'),
(18,18,36,2,'Well structured and properly referenced. Approved.','Approved','2026-09-30 15:00:00'),
(19,19,37,2,'The problem statement and objectives need to be more specific and measurable. Revise and resubmit.','Revision Required','2026-11-18 16:30:00'),
(20,20,37,2,'This does not meet the proposal template. Follow the departmental format and include a work plan.','Rejected','2026-11-07 12:00:00');
INSERT INTO notifications (user_id,message,link,is_read,created_at) VALUES
(4,'Your supervisor is reviewing your latest submission.','student/progress.php',0,'2026-11-24 09:05:00'),
(5,'Revision requested on your submission. See the feedback page for details.','student/feedback.php',0,'2026-11-18 16:31:00'),
(6,'Your submission is now: Under Review.','student/feedback.php',1,'2026-11-24 09:01:00'),
(9,'Revision requested on your submission. See the feedback page for details.','student/feedback.php',0,'2026-11-18 16:31:00'),
(2,'New submission awaiting review from Chinedu Okeke.','supervisor/submissions.php',0,'2026-11-20 10:46:00'),
(3,'New submission awaiting review from Blessing Eze.','supervisor/submissions.php',0,'2026-11-21 10:46:00'),
(1,'Weekly summary: 2 submissions are pending supervisor review.','admin/reports.php',0,'2026-11-25 08:00:00');
INSERT INTO activity_logs (user_id,action,details,ip_address,created_at) VALUES
(1,'Login','User logged in','127.0.0.1','2026-11-25 09:10:00'),
(1,'Project created','Online Examination Management System with Automated Grading','127.0.0.1','2026-09-02 10:00:00'),
(2,'Review','Approved: "Literature Review document" by Chinedu Okeke','127.0.0.1','2026-11-12 15:00:00'),
(2,'Review','Revision Required: "Literature Review - first submission" by Aisha Bello','127.0.0.1','2026-11-18 16:30:00'),
(3,'Review','Rejected: "Proposal - initial attempt" by Funke Adeleke','127.0.0.1','2026-11-07 12:00:00'),
(4,'Submission','Submitted "System Analysis and Design - draft v1" for milestone 4','127.0.0.1','2026-11-20 10:45:00'),
(7,'Submission','Submitted "Implementation - draft v1" for milestone 5','127.0.0.1','2026-11-22 10:45:00'),
(1,'Settings updated','Session 2026/2027, start 2026-09-01','127.0.0.1','2026-09-01 08:30:00');
