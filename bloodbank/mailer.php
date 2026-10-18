<?php
/**
 * Email helpers (PHPMailer over Gmail SMTP).
 * Every function fails soft: a mail problem is logged and never breaks a form.
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

function mail_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/mail_config.php';
    }
    return $cfg;
}

function mail_is_configured(): bool
{
    $c = mail_config();
    return $c['username'] !== '' && strpos($c['username'], 'YOUR_') === false
        && $c['password'] !== '' && strpos($c['password'], 'YOUR_') === false;
}

/** Send one HTML email. Returns true on success, false otherwise (never throws). */
function send_mail(string $to, string $subject, string $bodyHtml, ?string $replyTo = null): bool
{
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (!mail_is_configured()) {
        error_log("Mail skipped (SMTP not configured): $subject -> $to");
        return false;
    }

    $c = mail_config();
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $c['host'];
        $mail->Port       = $c['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $c['username'];
        $mail->Password   = $c['password'];
        $mail->SMTPSecure = $c['secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 10;

        $mail->setFrom($c['from_email'], $c['from_name']);
        $mail->addAddress($to);
        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = mail_layout($subject, $bodyHtml);
        $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</tr>'], "\n", $bodyHtml))));

        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log("Mail failed ($subject -> $to): " . $mail->ErrorInfo);
        return false;
    } catch (Throwable $e) {
        error_log("Mail error ($subject -> $to): " . $e->getMessage());
        return false;
    }
}

/** Escape user-supplied text for use inside an email body. */
function mh($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** Branded HTML wrapper. */
function mail_layout(string $title, string $inner): string
{
    return '<!DOCTYPE html><html><body style="margin:0;background:#fdf6f8;font-family:Arial,Helvetica,sans-serif;color:#2d1a22">'
        . '<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 12px">'
        . '<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:14px;overflow:hidden">'
        . '<tr><td style="background:#7a1538;color:#fff;padding:20px 28px;font-size:20px;font-weight:bold">&#129656; Lifeline Blood Bank</td></tr>'
        . '<tr><td style="padding:28px;font-size:15px;line-height:1.6">'
        . '<h2 style="margin:0 0 14px;color:#7a1538;font-size:20px">' . mh($title) . '</h2>' . $inner
        . '</td></tr>'
        . '<tr><td style="background:#f6e7ec;padding:16px 28px;font-size:12px;color:#6f5b63">'
        . 'Emergency line: +91 99001 63000 &middot; emergency@lifeline.com &middot; Mon&ndash;Sat, 9 AM &ndash; 5 PM'
        . '</td></tr></table></td></tr></table></body></html>';
}

/** Two-column details table for emails. */
function mail_rows(array $rows): string
{
    $out = '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;margin:12px 0">';
    foreach ($rows as $label => $value) {
        $out .= '<tr><td style="border-bottom:1px solid #f0dde4;color:#6f5b63;width:38%">' . mh($label)
            . '</td><td style="border-bottom:1px solid #f0dde4;font-weight:bold">' . mh($value) . '</td></tr>';
    }
    return $out . '</table>';
}

/* ---------- Ready-made emails ---------- */

function mail_contact_received(array $m): void
{
    $cfg = mail_config();
    send_mail($cfg['admin_email'], 'New enquiry: ' . $m['subject'] . ' (from ' . $m['name'] . ')',
        mail_rows(['Name' => $m['name'], 'Email' => $m['email'], 'Phone' => $m['phone'], 'Subject' => $m['subject']])
        . '<p style="white-space:pre-wrap">' . mh($m['message']) . '</p>', $m['email']);
    send_mail($m['email'], 'Thank you for contacting Lifeline Blood Bank',
        '<p>Hi ' . mh($m['name']) . ',</p><p>Thank you for contacting Lifeline Blood Bank. We have received your enquiry and our team will get back to you soon.</p>'
        . mail_rows(['Subject' => $m['subject']]));
}

function mail_request_received(array $r): void
{
    $cfg = mail_config();
    $details = [
        'Blood group' => $r['blood_group'], 'Units' => $r['units_needed'], 'Urgency' => $r['urgency_level'],
        'Hospital' => $r['hospital_name'], 'Location' => $r['location_area'],
        'Patient' => $r['patient_name'] ?: '-', 'Requester' => $r['requester_name'], 'Phone' => $r['phone'],
    ];
    send_mail($cfg['admin_email'], '[' . $r['urgency_level'] . '] Blood request: ' . $r['blood_group'] . ' x ' . $r['units_needed'],
        '<p>A new blood request was raised on the website.</p>' . mail_rows($details)
        . ($r['message'] ? '<p style="white-space:pre-wrap">' . mh($r['message']) . '</p>' : ''));
    if (!empty($r['email'])) {
        send_mail($r['email'], 'Your blood request has been received',
            '<p>Hi ' . mh($r['requester_name']) . ',</p><p>We have recorded your request and our team will review it shortly. '
            . 'For emergencies please call <b>+91 99001 63000</b>.</p>' . mail_rows($details));
    }
}

function mail_request_status(array $r, string $status): void
{
    if (empty($r['email'])) {
        return;
    }
    $msg = $status === 'Fulfilled'
        ? 'Good news &mdash; your blood request has been marked as <b>fulfilled</b>. We wish the patient a speedy recovery.'
        : 'Your blood request status is now <b>' . mh($status) . '</b>.';
    send_mail($r['email'], 'Blood request update: ' . $status,
        '<p>Hi ' . mh($r['requester_name'] ?? '') . ',</p><p>' . $msg . '</p>'
        . mail_rows(['Blood group' => $r['blood_group'] ?? '', 'Units' => $r['units_needed'] ?? '', 'Hospital' => $r['hospital_name'] ?? '']));
}

function mail_donor_welcome(string $name, string $email, string $blood): void
{
    send_mail($email, 'Welcome to Lifeline Blood Bank',
        '<p>Hi ' . mh($name) . ',</p><p>Thank you for registering as a donor. Your registration is complete'
        . ($blood ? ' with blood group <b>' . mh($blood) . '</b>' : '') . '.</p>'
        . '<p>You can now log in, book a donation slot and track your donations.</p>'
        . '<p><b>Reminder:</b> bring a valid photo ID and eat a light meal before you donate.</p>');
}

function mail_appointment(array $a, string $kind): void
{
    // $kind: booked | updated | Confirmed | Completed | Cancelled
    if (empty($a['donor_email'])) {
        return;
    }
    $lead = [
        'booked'    => 'Your donation slot has been booked. Our team will confirm it shortly.',
        'updated'   => 'Your donation slot has been updated and is awaiting confirmation.',
        'Confirmed' => 'Great news &mdash; your donation appointment is <b>confirmed</b>.',
        'Completed' => 'Thank you! Your donation has been recorded. You may have helped save up to three lives.',
        'Cancelled' => 'Your donation appointment has been <b>cancelled</b>. You can book a new slot any time.',
    ][$kind] ?? 'Your appointment status is now ' . mh($kind) . '.';
    send_mail($a['donor_email'], 'Donation appointment: ' . ucfirst($kind),
        '<p>Hi ' . mh($a['donor_name'] ?? 'donor') . ',</p><p>' . $lead . '</p>'
        . mail_rows(['Date' => $a['appointment_date'] ?? '', 'Time' => $a['appointment_time'] ?? '', 'Location' => $a['location'] ?? '']));
}

function mail_donation_reminder(array $donor, string $eligibleOn): bool
{
    return send_mail($donor['email'], 'You can donate blood again!',
        '<p>Hi ' . mh($donor['username'] ?? 'donor') . ',</p>'
        . '<p>Your waiting period is over (eligible since <b>' . mh($eligibleOn) . '</b>). A new donation can help save up to three lives.</p>'
        . '<p>Log in to your donor account and book a convenient slot.</p>');
}
