<?php
// Database & application configuration. Edit these values to match your XAMPP setup.
const DB_HOST = 'localhost';
const DB_NAME = 'student_project_tracker';
const DB_USER = 'root';
const DB_PASS = '';           // XAMPP default: empty password
const BASE_URL = '/student-project-tracker'; // URL path of the project folder under htdocs
const UPLOAD_DIR = __DIR__ . '/../uploads/';
const APP_NAME = 'Student Project Management & Progress Tracking System';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Database connection failed. Check config/db.php and make sure MySQL is running and database.sql has been imported.');
        }
    }
    return $pdo;
}
