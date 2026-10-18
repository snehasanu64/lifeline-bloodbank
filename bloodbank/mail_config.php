<?php
/**
 * SMTP settings (Gmail).
 *
 * SETUP:
 *   1. Turn on 2-Step Verification for the Gmail account.
 *   2. Google Account -> Security -> App passwords -> create one (16 characters).
 *   3. Put the Gmail address in SMTP_USER and the app password in SMTP_PASS.
 *
 * Until both are filled in, emails are skipped (and logged) and the site keeps working.
 * Never commit real credentials. This file prints nothing, so it is safe in the web root.
 */

return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'secure'     => 'tls',
    'username'   => 'testdev7353@gmail.com',
    'password'   => 'vxrorwzenwaiuybe',
    'from_email' => 'testdev7353@gmail.com',
    'from_name'  => 'Lifeline Blood Bank',
    // Staff inbox that receives contact messages and blood request alerts.
    'admin_email' => 'testdev7353@gmail.com',
    // Secret used to run send_reminders.php from a browser/URL. Change it. CLI needs no key.
    'cron_key'   => 'change-me-to-a-long-random-string',
    // Days a donor must wait between donations (used for reminders).
    'donation_gap_days' => 90,
];
