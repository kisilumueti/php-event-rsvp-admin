📄 Event RSVP System – Admin Email Inviter
This system is designed to manage bulk guest invitations for events. Admins can upload guest lists, send email invitations, monitor RSVP responses, and export data. Built with PHP and MySQL for simplicity, it handles over 500,000 guests, ensuring scalability, security, and a smooth user experience for both administrators and invitees.

📐 System Design
1. Architecture Overview
Frontend: Responsive Bootstrap 5 interface for admin users.

Backend: PHP handles form logic, database access, and mailing.

Database: MySQL stores guest details, RSVP statuses, and email logs.

Mailer: PHPMailer with SMTP integration for scalable email delivery.

RSVP Flow: Each invite contains a unique link tied to the guest's ID.

2. Database Structure
Tables:
guests – stores name, email, and RSVP response.

email_logs – tracks sent invitations with guest_id and timestamp.

admins – handles login credentials and roles.

3. Email Logic
Sends up to 50 unsent invites per batch to avoid timeouts.

Skips guests already in email_logs.

Generates a unique RSVP link using the guest ID.

💻 Technology Choices
Component	Technology	Justification
Frontend UI	Bootstrap 5	Ensures mobile-first responsive UI with minimal custom CSS.
Backend	PHP (7.x/8.x)	Widely supported on most Kenyan hosting platforms; simple to deploy.
Database	MySQL	Reliable RDBMS for structured data and joins.
Mailer	PHPMailer + SMTP	Easy to integrate, supports Kenyan SMTP providers and Gmail.
Server	Apache / Nginx	Runs on common LAMP stacks used by local providers like Safaricom Cloud or Truehost.

⚖️ Key Trade-offs
Trade-Off	Decision	Reason
Ease of Deployment vs Modern Frameworks	Chose PHP over Laravel/Node.js	Pure PHP runs on low-cost shared hosting (common in Kenya), no need for CLI-based deployment.
Batch Size	Sends 50 emails at a time	Balances performance and server timeout limits. Can be changed based on server specs.
Security	Simple session-based login	Avoids complex OAuth setup; good for internal admin use.
Speed vs Logging	Logs each sent invite in DB	Prioritizes tracking and avoids duplicate emails, even though it adds DB overhead.

✅ Features Summary
Admin authentication.

Upload guest list (CSV).

Send bulk email invites with RSVP links.

Avoid duplicate invites using logs.

Export RSVP data (e.g., to Excel).

Clean mobile-friendly UI.

🚀 Deployment Notes
Edit the SMTP credentials in send_invites.php:

php
Copy
Edit
$mail->Host = 'smtp.yourdomain.co.ke';
$mail->Username = 'your_email@yourdomain.co.ke';
$mail->Password = 'your_password';
Upload on a PHP-supporting web host (e.g., Truehost Kenya, Safaricom Web Hosting).

Make sure PHPMailer is installed via Composer or included manually.

📊 Scalability Plans
If traffic increases or guests exceed 1 million:

Switch to Laravel Queue or Cron Jobs for email batching.

Move MySQL to a cloud-managed DB (e.g., DigitalOcean, GCP).

Add caching with Redis or Memcached.

Load balance using Nginx + PHP-FPM behind a CDN like Cloudflare.

👨🏽‍💻 Maintainer
Developed for the SpinMobile Coding/System Design Challenge
Deadline: 3rd June 2025 – Prepared by a BBIT 3rd Year Student with real-world Kenya-based deployment in mind.

