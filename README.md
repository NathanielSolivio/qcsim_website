# QCSim — Virtual Pharmaceutical QC Laboratory

A multi-page PHP web application for learning pharmaceutical quality control.

---

## Project Structure

```
qcsim/
├── index.php                        ← Main Menu (Home)
├── login.php                        ← Login Page
├── signup.php                       ← Sign Up Page
├── verify.php                       ← Email verification handler
├── logout.php                       ← Logout handler
├── composer.json                    ← PHPMailer dependency
├── qcsim_db.sql                     ← Database schema + admin seed
│
├── includes/
│   ├── db.php                       ← MySQL connection
│   ├── auth.php                     ← Auth helpers (login, roles, tokens)
│   ├── mailer.php                   ← PHPMailer confirmation email
│   └── navbar.php                   ← Shared responsive navbar
│
├── pages/
│   ├── virtual_lab.php              ← Virtual Lab (blank placeholder)
│   ├── learning_materials.php       ← Browse & download materials
│   ├── profile.php                  ← View/edit profile + change password
│   ├── manage_materials.php         ← CRUD for instructors & admins
│   └── manage_users.php             ← CRUD for admins only
│
├── Assets/
│   └── MainWebsite/
│       ├── style.css                ← Global stylesheet
│       ├── logo.png                 ← ← YOU SUPPLY THESE
│       ├── bg_lab.png               ← Auth page background
│       ├── bg_home.png              ← Home hero background
│       └── default_avatar.png      ← Fallback profile picture
│
└── uploads/                         ← Created automatically at runtime
    ├── materials/                   ← Uploaded learning material files
    └── profile_pics/                ← User profile pictures
```

---

## Quick Setup

### 1. Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.4+
- Composer (for PHPMailer)
- Apache / Nginx with `mod_rewrite`

### 2. Install PHPMailer
```bash
cd /path/to/qcsim
composer install
```

### 3. Create the Database
```bash
mysql -u root -p < qcsim_db.sql
```
This creates the `qcsim_db` database, both tables, and seeds **one admin account**:
- **Email:** `admin@qcsim.edu`
- **Password:** `Admin@1234`

### 4. Configure the Database
Edit `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'qcsim_db');
```

### 5. Configure PHPMailer
Edit `includes/mailer.php` — fill in your SMTP credentials:
```php
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_USERNAME', 'your-email@gmail.com');   // Gmail address
define('MAIL_PASSWORD', 'your-app-password');       // Gmail App Password (not login password)
define('MAIL_PORT',     587);
define('MAIL_FROM',     'your-email@gmail.com');
define('MAIL_FROM_NAME','QCSim');
define('APP_URL',       'http://localhost/qcsim');  // Change to your domain
```

> **Gmail setup:** Go to Google Account → Security → 2-Step Verification → App Passwords → generate one for "Mail".

### 6. Add Your Assets
Place these image files in `Assets/MainWebsite/`:

| File | Description |
|---|---|
| `logo.png` | QCSim logo (≈ 34–40px tall) |
| `bg_lab.png` | Background for login/signup pages |
| `bg_home.png` | Background for the home hero section |
| `default_avatar.png` | Fallback avatar for users without a profile photo |

The design references the lab illustration shown in your mockup screenshots for `bg_lab.png` and `bg_home.png`.

### 7. Set Folder Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/materials/
chmod 755 uploads/profile_pics/
```
(These folders are created automatically on first use, but pre-creating them avoids any permission issues.)

### 8. Deploy
Place the entire `qcsim/` folder inside your web server root (e.g. `/var/www/html/qcsim` or `htdocs/qcsim`) and navigate to:
```
http://localhost/qcsim/login.php
```

---

## Roles & Access

| Page | Guest | Student | Instructor | Admin |
|---|:---:|:---:|:---:|:---:|
| Login / Sign Up | ✅ | — | — | — |
| Home (index.php) | ❌ | ✅ | ✅ | ✅ |
| Learning Materials | ❌ | ✅ | ✅ | ✅ |
| Virtual Lab | ❌ | ✅ | ✅ | ✅ |
| Profile | ❌ | ✅ | ✅ | ✅ |
| Manage Materials | ❌ | ❌ | ✅ | ✅ |
| Manage Users | ❌ | ❌ | ❌ | ✅ |

### Role rules
- **Admin** is seeded once in the database — there is only one.
- **Admin** can change users between `student` and `instructor` roles only (admin role is protected).
- **Instructors** can only edit/delete materials they uploaded themselves.
- **Admin** can edit/delete any material.

---

## Sign-Up Flow

1. User fills the sign-up form → account is created with `is_verified = 0`
2. PHPMailer sends a confirmation email with a unique token link
3. User clicks the link → `verify.php` sets `is_verified = 1`
4. User can now sign in

> Unverified accounts are blocked at login with a clear message.

---

## Notes

- All passwords are hashed with **bcrypt** (`password_hash` / `password_verify`).
- Verification tokens expire after **24 hours**.
- File uploads for learning materials are capped at **50MB**.
- Profile picture uploads are capped at **2MB** (JPG, PNG, WEBP, GIF).
- The navbar is fully **responsive** with a hamburger menu on mobile.
- The Virtual Lab page is intentionally blank — ready for your simulation content.
