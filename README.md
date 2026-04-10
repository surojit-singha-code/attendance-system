# 🎓 AttendEase — Attendance Management System

A full-featured PHP + MySQL attendance management web app with separate **Teacher** and **Student** portals.

---

## 📁 File Structure

```
attendance_system/
├── setup.sql              ← Run this first to create the database
├── db.php                 ← Database connection (edit credentials here)
├── login.php              ← Main login page (entry point)
├── logout.php             ← Clears session, redirects to login
│
├── teacher/
│   ├── dashboard.php      ← Teacher home: stats + quick actions
│   ├── add_student.php    ← Add/delete students
│   ├── delete_student.php ← Handles deletion (redirect-based)
│   ├── mark_attendance.php← Mark Present/Absent for any date
│   └── report.php         ← Full report + percentage per student
│
└── student/
    ├── dashboard.php      ← Student home: summary + recent records
    └── attendance.php     ← Full date-wise attendance history
```

---

## 🚀 How to Run

### Requirements
- XAMPP / WAMP / LAMP (PHP 7.4+, MySQL 5.7+)
- A browser

### Step 1 — Start your server
Open **XAMPP Control Panel** and start **Apache** and **MySQL**.

### Step 2 — Copy the project
Place the `attendance_system/` folder inside:
- XAMPP: `C:/xampp/htdocs/`
- WAMP:  `C:/wamp64/www/`

### Step 3 — Set up the database
1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Choose `attendance_system/setup.sql`
4. Click **Go**

### Step 4 — Configure DB credentials
Open `db.php` and update:
```php
define('DB_USER', 'root');  // your MySQL username
define('DB_PASS', '');      // your MySQL password (blank for XAMPP default)
```

### Step 5 — Open the app
Visit: `http://localhost/attendance_system/login.php`

---

## 🔐 Login Credentials

| Role    | Username | Password   |
|---------|----------|------------|
| Teacher | teacher  | teacher123 |
| Student | (set by teacher when adding student) |

---

## ✨ Features Summary

### Teacher Portal
- Dashboard with live stats (total students, today's attendance)
- Add students with custom login credentials
- Delete students (cascades to attendance records)
- Mark attendance with Present/Absent toggles (with "All Present" bulk action)
- Prevents duplicate attendance for the same date
- Full report with per-student attendance percentage
- Date-wise breakdown (last 20 marked dates)
- Low attendance warning (<75%)

### Student Portal
- Personal dashboard with welcome card + today's status
- Stats: total days, present, absent, percentage
- Full attendance history grouped by month
- Progress bar showing attendance percentage

---

## 🗄️ Database Schema

```sql
users       (id, username, password, role, created_at)
students    (id, name, roll, user_id→users.id, created_at)
attendance  (id, student_id→students.id, date, status, marked_at)
            UNIQUE KEY on (student_id, date) — prevents duplicates
```

---

## 🛠 Tech Stack
- **Backend:** PHP 8+ with PDO (prepared statements, no SQL injection)
- **Database:** MySQL with foreign keys + cascading deletes
- **Frontend:** HTML5, CSS3, Bootstrap 5.3, Font Awesome 6
- **Security:** bcrypt password hashing, session-based auth, role guards

