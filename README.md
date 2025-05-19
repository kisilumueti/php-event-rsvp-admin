# Event RSVP System 🇰🇪

A PHP & MySQL-based RSVP management system for large-scale events in Kenya. This system enables admins to upload guest lists, send bulk email invitations, track RSVP responses, and export attendance data — all through a user-friendly admin panel.

---

## 📐 System Design

### 🔑 Core Features
- Upload CSV guest lists (name, email).
- Bulk invitation via email (PHPMailer integration).
- Personalized RSVP links with guest-specific tracking.
- Admin dashboard with RSVP stats and logs.
- Export data as CSV for reports.
- Resend failed or pending invitations.
- Multiple admin user support.

### 📊 Database Design (MySQL)
- `guests`: Stores guest info.
- `email_logs`: Tracks email delivery status.
- `rsvp_responses`: Logs RSVP actions (attending or not).
- `admins`: Handles admin authentication.

---

## 💻 Technology Choices

| Tech         | Why It Was Chosen |
|--------------|-------------------|
| **PHP**      | Widely used, server-side scripting language ideal for form processing and email integration. |
| **MySQL**    | Reliable and scalable database solution — commonly used in Kenyan SMEs and institutions. |
| **PHPMailer**| Robust mail-sending library, simplifies SMTP setup and HTML email formatting. |
| **HTML/CSS** | Lightweight UI — enhanced with responsiveness for mobile and desktop. |
| **Vanilla JS** (planned) | To improve form feedback, loading states, and interactivity (e.g., AJAX RSVP feedback). |

---

## ⚖️ Key Trade-offs

| Decision | Reason | Trade-off |
|---------|--------|-----------|
| **PHP (vs Laravel)** | Simple hosting, easier to set up for local devs and SMEs. | Miss out on MVC structure and inbuilt security tools of Laravel. |
| **MySQL** | Well-supported and easy to host in Kenya. | Slightly less performant than NoSQL for massive concurrent loads. |
| **Basic UI (vs React or Bootstrap)** | Focused on functional delivery over polish, with plans for future UX upgrades. | UI is minimal; advanced UI/UX features are limited for now. |
| **SMTP over third-party APIs** | More control and no external email costs. | May require proper mail server configuration and testing. |

---

## 🚀 How to Run Locally

### ✅ Requirements
- PHP 7.4+ (with `openssl`, `pdo_mysql`)
- MySQL/MariaDB
- Apache/Nginx
- Composer (for PHPMailer)

### 📦 Setup

bash
git clone https://github.com/yourusername/event-rsvp-kenya.git
cd event-rsvp-kenya

# Install dependencies
composer install

# Setup your database
1. Create a MySQL DB: `event_rsvp`
2. Import `/database/schema.sql`

# Configure DB and SMTP in `config/db.php`

# Start server (for testing)
php -S localhost:8000

🔐 Admin Login
Default credentials can be set during admin creation or directly in the DB.

Passwords are hashed using password_hash() for security.

📤 Email Configuration
Update the SMTP details in send_invites.php:
$mail->Host = 'smtp.yourdomain.co.ke';
$mail->Username = 'your_email@yourdomain.co.ke';
$mail->Password = 'your_password';
event-rsvp-kenya/
├── config/
│   └── db.php
├── rsvp/
│   └── index.php (guest RSVP form)
├── admin/
│   ├── index.php (dashboard)
│   ├── send_invites.php
│   ├── upload_guests.php
│   ├── export_data.php
│   ├── resend_invites.php
│   └── create_admin.php
├── database/
│   └── schema.sql
├── vendor/
│   └── PHPMailer (via Composer)
├── README.md


🙌 Credits
Developed by a 4th year BBIT student ###Johnbosco kisilu muet, with real-world application in Kenyan corporate and campus events.

📜 License
MIT License — free to use, improve, and contribute!
