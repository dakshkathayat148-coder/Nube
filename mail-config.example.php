<?php
/**
 * TEMPLATE for the contact form SMTP config.
 *
 * Copy this file to "mail-config.php" and fill in your real cPanel mail
 * credentials. mail-config.php is gitignored so secrets never get committed.
 *
 * For cPanel mail, values are usually:
 *   HOST = mail.yourdomain  (e.g. mail.nubevest.co.uk)
 *   PORT = 465 (SSL) or 587 (TLS)
 *   USER = the full email address you send from
 *   PASS = that mailbox's password
 */

return [
    'smtp_host'   => 'mail.nubevest.co.uk',
    'smtp_port'   => 465,           // 465 for SSL, 587 for STARTTLS
    'smtp_secure' => 'ssl',         // 'ssl' for 465, 'tls' for 587
    'smtp_user'   => 'YOUR_SMTP_USERNAME',
    'smtp_pass'   => 'YOUR_SMTP_PASSWORD',
    'from_email'  => 'no-reply@nubevest.co.uk',
    'from_name'   => 'Nubevest Website',
    'to_email'    => 'info@nubevest.com.au',
    'to_name'     => 'Nubevest',
];
