<?php
/**
 * SMTP settings (Gmail).
 *
 * This file holds NO secrets, so it is safe to commit. Real credentials go in
 * mail_config.local.php (git-ignored) or in environment variables.
 *
 * SETUP:
 *   1. Turn on 2-Step Verification for the Gmail account.
 *   2. Google Account -> Security -> App passwords -> create one (16 characters).
 *   3. Copy mail_config.local.example.php to mail_config.local.php and fill it in
 *      (or set MAIL_USERNAME / MAIL_PASSWORD / MAIL_ADMIN_EMAIL / MAIL_CRON_KEY).
 *
 * Until credentials are filled in, emails are skipped (and logged) and the site keeps working.
 */

$config = [
    'host'       => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port'       => (int)(getenv('MAIL_PORT') ?: 587),
    'secure'     => getenv('MAIL_SECURE') ?: 'tls', // 'tls' (587) or 'ssl' (465)
    'username'   => getenv('MAIL_USERNAME') ?: '',
    'password'   => getenv('MAIL_PASSWORD') ?: '',
    'from_email' => getenv('MAIL_USERNAME') ?: '',
    'from_name'  => 'Lifeline Blood Bank',
    // Staff inbox that receives contact messages and blood request alerts.
    'admin_email' => getenv('MAIL_ADMIN_EMAIL') ?: '',
    // Secret used to run send_reminders.php from a browser/URL. Set your own. CLI needs no key.
    'cron_key'   => getenv('MAIL_CRON_KEY') ?: '',
    // Days a donor must wait between donations (used for reminders).
    'donation_gap_days' => 90,
];

// Private overrides (never committed).
$local = __DIR__ . '/mail_config.local.php';
if (is_file($local)) {
    $config = array_merge($config, require $local);
}

// Google shows app passwords in groups ("abcd efgh ..."); SMTP needs them without spaces.
$config['password'] = preg_replace('/\s+/', '', (string)$config['password']);
$config['username'] = trim((string)$config['username']);
if ($config['from_email'] === '') {
    $config['from_email'] = $config['username'];
}

return $config;
