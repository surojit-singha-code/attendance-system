<div align="center">

```
 █████╗ ████████╗████████╗███████╗███╗   ██╗██████╗ ███████╗ █████╗ ███████╗███████╗
██╔══██╗╚══██╔══╝╚══██╔══╝██╔════╝████╗  ██║██╔══██╗██╔════╝██╔══██╗██╔════╝██╔════╝
███████║   ██║      ██║   █████╗  ██╔██╗ ██║██║  ██║█████╗  ███████║███████╗█████╗  
██╔══██║   ██║      ██║   ██╔══╝  ██║╚██╗██║██║  ██║██╔══╝  ██╔══██║╚════██║██╔══╝  
██║  ██║   ██║      ██║   ███████╗██║ ╚████║██████╔╝███████╗██║  ██║███████║███████╗
╚═╝  ╚═╝   ╚═╝      ╚═╝   ╚══════╝╚═╝  ╚═══╝╚═════╝ ╚══════╝╚═╝  ╚═╝╚══════╝╚══════╝
```

### 🎓 Because attendance shouldn't be a headache — for *anyone*.

<br/>

[![PHP](https://img.shields.io/badge/PHP_8+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap_5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge)](LICENSE)

<br/>

> *"Built in Kolkata. Powered by chai and clean code."*
> — **Surojit**

</div>

---

## 🌟 What is AttendEase?

AttendEase is a **no-nonsense, full-stack attendance management system** built with PHP and MySQL. Two portals. One clean interface. Zero excuses for missing attendance records.

Whether you're a teacher juggling 40 students or a student nervously checking if you've crossed the 75% line — AttendEase has your back.

---

## ✨ Features that actually matter

### 👩‍🏫 For Teachers
| Feature | What it does |
|---|---|
| 📊 **Live Dashboard** | Snapshot of total students + today's attendance at a glance |
| ➕ **Add Students** | Create student accounts with custom credentials instantly |
| 🗑️ **Delete Students** | Full cascade delete — no orphan records left behind |
| ✅ **Mark Attendance** | Individual or bulk "All Present" in one click |
| 🚫 **Duplicate Guard** | Can't mark the same student twice on the same day |
| 📋 **Reports** | Detailed percentage-based reports with **⚠️ low attendance alerts** (<75%) |

### 🎒 For Students
| Feature | What it does |
|---|---|
| 👋 **Personal Dashboard** | Welcome message + today's attendance status |
| 📈 **Progress Bar** | Visual overview of overall attendance percentage |
| 📅 **History View** | Full date-wise records, grouped neatly by month |

### 🔐 Under the Hood
- **bcrypt** password hashing — plain text passwords are a crime
- **PDO Prepared Statements** — SQL injection? Not today.
- **Session-based role guards** — teachers see teacher stuff, students see student stuff
- **Responsive UI** — works on your laptop, your phone, your ancient tablet

---

## 📁 Project Structure

```
attendance_system/
│
├── 📄 setup.sql               ← Run this first. Always.
├── 🔌 db.php                  ← Your database config lives here
├── 🔑 login.php               ← The front door
├── 🚪 logout.php              ← The back door
│
├── 👩‍🏫 teacher/
│   ├── dashboard.php          ← Command center
│   ├── add_student.php        ← Onboard new students
│   ├── delete_student.php     ← Remove & cascade clean
│   ├── mark_attendance.php    ← The daily ritual
│   └── report.php             ← The truth teller
│
└── 🎒 student/
    ├── dashboard.php          ← Personal stats & status
    └── attendance.php         ← Full history view
```

---

## 🚀 Getting Started

### 📋 What you need
- XAMPP / WAMP / LAMP
- PHP 7.4+ (tested on 8+)
- MySQL 5.7+
- A browser from this decade

### ⚡ Setup in 5 steps

**Step 1 — Fire up your stack**
```
Start Apache + MySQL in XAMPP/WAMP Control Panel
```

**Step 2 — Drop the project**
```bash
# XAMPP users
cp -r attendance_system/ /path/to/xampp/htdocs/

# WAMP users
cp -r attendance_system/ /path/to/wamp/www/
```

**Step 3 — Create the database**
```
phpMyAdmin → http://localhost/phpmyadmin → Import setup.sql
```

**Step 4 — Configure your connection**
```php
// db.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // 🔧 change if needed
define('DB_PASS', '');          // 🔧 default is empty in XAMPP
define('DB_NAME', 'attendance_system');
```

**Step 5 — Launch 🚀**
```
http://localhost/attendance_system/login.php
```

---

## 🔑 Default Credentials

| Role | Username | Password |
|---|---|---|
| 👩‍🏫 **Teacher** | `teacher` | `teacher123` |
| 🎒 **Student** | *(created by teacher)* | *(set by teacher)* |

> 💡 **Pro tip:** Change the default teacher password after your first login. Seriously.

---

## 🗄️ Database Schema (the short version)

```sql
users        →  login credentials + role (teacher/student)
students     →  student details, linked to users via foreign key
attendance   →  daily records with UNIQUE(student_id, date) constraint
                               ↑ this is what prevents duplicates
```

---

## 🛠️ Tech Stack

```
Backend   →  PHP 8+ with PDO
Database  →  MySQL (foreign keys + cascading deletes)
Frontend  →  HTML5, CSS3, Bootstrap 5.3, Font Awesome 6
Security  →  bcrypt hashing + prepared statements + session role guards
```

---

## 📸 Screenshots

> *Screenshots coming soon — contributions welcome!*

| Teacher Dashboard | Mark Attendance | Student Dashboard | Report View |
|---|---|---|---|
| 🖼️ soon | 🖼️ soon | 🖼️ soon | 🖼️ soon |

---

## 🤝 Contributing

Found a bug? Have a feature idea? PRs are welcome!

1. Fork the repo
2. Create your branch: `git checkout -b feature/your-idea`
3. Commit your changes: `git commit -m 'Add: your idea here'`
4. Push and open a PR 🎉

---

## 📄 License

Distributed under the **MIT License**. See [`LICENSE`](LICENSE) for details.
Use it, fork it, learn from it — just don't forget to give credit. 🙏

---

<div align="center">

**Made with ❤️ and ☕ for educational institutions**

Built by **[Surojit](https://github.com/)** · Kolkata, West Bengal, India 🇮🇳

*If this helped you, drop a ⭐ — it means more than you think.*

</div>
