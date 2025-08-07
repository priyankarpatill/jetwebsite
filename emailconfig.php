<?php
/**
 * Email Configuration
 * 
 * This file contains the configuration settings for sending emails.
 * Update these settings with your actual email credentials.
 */

// SMTP Server Settings
define('SMTP_HOST', 'smtp.freesmtpservers.com');      // SMTP server address
define('SMTP_PORT', 25);                              // SMTP port (25 for no encryption)
define('SMTP_SECURE', '');                            // Encryption type: 'tls', 'ssl', or '' for none
define('SMTP_AUTH', false);                           // Enable SMTP authentication

// Email Account Credentials
define('SMTP_USERNAME', '');                          // Your email address (not needed for this server)
define('SMTP_PASSWORD', '');                          // Your email password (not needed for this server)

// Sender and Recipient Information
define('EMAIL_FROM', 'priyankarpatilpatil@gmail.com');           // Sender email address
define('EMAIL_FROM_NAME', 'AIvent Contact Form');     // Sender name
define('EMAIL_TO', 'rpatilpriyankarpatilpatil@gmail.com');        // Recipient email address
define('EMAIL_TO_NAME', 'AIvent Admin');              // Recipient name

/**
 * IMPORTANT NOTES FOR GMAIL USERS:
 * 
 * 1. You need to enable "Less secure app access" or
 * 2. Create an "App Password" if you have 2-factor authentication enabled:
 *    - Go to your Google Account > Security > App passwords
 *    - Select "Mail" and your device, then generate
 *    - Use the generated 16-character password in SMTP_PASSWORD
 * 
 * For other email providers, use their specific SMTP settings.
 */
?>