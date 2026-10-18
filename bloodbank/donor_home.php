<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['donor_email'])) {
    header("Location: donor.php");
    exit();
}

include('db_connect.php');
require_once __DIR__ . '/camps_helper.php';
// Firestore collections are created implicitly on first write — no CREATE TABLE needed.

$bookingMsg = "";
$bookingError = "";
$selectedAppointmentDate = "";
$selectedAppointmentTime = "";
$selectedAppointmentLocation = "";
$showBookingPopup = false;
$isEditingBooking = false;
$editingAppointmentId = 0;

if (isset($_POST['logout'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: donor_home.php");
        exit();
    }

    session_destroy();
    header("Location: donor.php");
    exit();
}

$loggedInEmail = $_SESSION['donor_email'];

$donor = $db->getOneWhere('donors', 'email', $loggedInEmail);

if (!$donor) {
    die("Donor not found.");
}

$profileMsg = "";
$profileError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $profileError = "Invalid request. Please refresh and try again.";
    } else {
        $newPhone   = trim($_POST['phone'] ?? '');
        $newAge     = (int)($_POST['age'] ?? 0);
        $newWeight  = (int)($_POST['weight'] ?? 0);
        $newLast    = trim($_POST['lastdonation'] ?? '');
        $newAddress = trim($_POST['address'] ?? '');

        if (!preg_match('/^\d{10}$/', $newPhone)) {
            $profileError = "Phone must be a 10-digit number.";
        } elseif ($newAge < 18) {
            $profileError = "You must be at least 18 years old to donate blood.";
        } elseif ($newWeight < 50) {
            $profileError = "You must weigh at least 50 kg to donate blood.";
        } elseif ($newAddress === '') {
            $profileError = "Address is required.";
        } elseif ($newLast !== '' && !DateTime::createFromFormat('Y-m-d', $newLast)) {
            $profileError = "Please enter a valid last donation date.";
        } else {
            try {
                $db->update('donors', $donor['_id'], [
                    'phone' => $newPhone,
                    'age' => $newAge,
                    'weight' => $newWeight,
                    'lastdonation' => $newLast,
                    'address' => $newAddress,
                ]);
                $donor = $db->getOneWhere('donors', 'email', $loggedInEmail);
                $profileMsg = "Profile updated successfully.";
            } catch (Exception $e) {
                error_log("Profile update failed: " . $e->getMessage());
                $profileError = "Could not update your profile. Please try again.";
            }
        }
    }
}

$username = $donor['username'];
$email = $donor['email'];
$phone = $donor['phone'];
$bloodgroup = $donor['bloodgroup'];
$age = $donor['age'];
$weight = $donor['weight'];
$lastdonation = $donor['lastdonation'];
$address = $donor['address'];

$latestAppointment = null;

$nextDonationDate = "";
$status = "Not Available";
$statusColor = "#999";
$isEligible = false;

if (!empty($lastdonation)) {
    $lastDate = new DateTime($lastdonation);
    $nextDate = clone $lastDate;
    $nextDate->modify("+90 days");
    $today = new DateTime();

    $nextDonationDate = $nextDate->format("Y-m-d");

    if ($today >= $nextDate) {
        $status = "Eligible to Donate";
        $statusColor = "green";
        $isEligible = true;
    } else {
        $status = "Not Eligible Yet";
        $statusColor = "red";
    }
} else {
    $status = "Eligible to Donate";
    $statusColor = "green";
    $isEligible = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['book_donation'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $bookingError = "Invalid request. Please refresh and try again.";
    } elseif (!$isEligible) {
        $bookingError = "You are not eligible to book a donation slot yet.";
    } else {
        $appointmentDate = trim($_POST['appointment_date'] ?? '');
        $appointmentTime = trim($_POST['appointment_time'] ?? '');
        $appointmentLocation = trim($_POST['appointment_location'] ?? '');
        $bookingMode = trim($_POST['booking_mode'] ?? 'new');
        $appointmentId = trim($_POST['appointment_id'] ?? '');

        $selectedAppointmentDate = $appointmentDate;
        $selectedAppointmentTime = $appointmentTime;
        $selectedAppointmentLocation = $appointmentLocation;
        $isEditingBooking = ($bookingMode === 'edit' && $appointmentId !== '');
        $editingAppointmentId = $appointmentId;

        $allowedTimes = [
            '9:00 AM - 11:00 AM',
            '11:00 AM - 1:00 PM',
            '1:00 PM - 3:00 PM',
            '3:00 PM - 5:00 PM'
        ];

        // Office + every upcoming camp the admin has added (see admin_camps.php).
        require_once __DIR__ . '/camps_helper.php';
        $allowedLocations = donation_locations($db);

        $appointmentDateObject = DateTime::createFromFormat('Y-m-d', $appointmentDate);
        $today = new DateTime('today');

        if (!$appointmentDateObject || $appointmentDateObject < $today) {
            $bookingError = "Please choose a valid future date.";
        } elseif (!in_array($appointmentTime, $allowedTimes, true)) {
            $bookingError = "Please choose a valid donation time slot.";
        } elseif (!in_array($appointmentLocation, $allowedLocations, true)) {
            $bookingError = "Please choose a valid donation location.";
        } else {
            $formattedDate = $appointmentDateObject->format('Y-m-d');

            if ($isEditingBooking) {
                try {
                    $existingAppt = $db->getById('donation_appointments', $appointmentId);
                    if (!$existingAppt || $existingAppt['donor_email'] !== $email) {
                        $bookingError = "Unable to update your booking right now. Please try again later.";
                    } else {
                        $db->update('donation_appointments', $appointmentId, [
                            'appointment_date' => $formattedDate,
                            'appointment_time' => $appointmentTime,
                            'location' => $appointmentLocation,
                            'status' => 'Pending',
                        ]);
                        $bookingMsg = "Your donation slot has been updated successfully.";
                        $showBookingPopup = true;
                        $isEditingBooking = false;
                        $editingAppointmentId = '';
                    }
                } catch (Exception $e) {
                    error_log("Appointment update failed: " . $e->getMessage());
                    $bookingError = "Unable to update your booking right now. Please try again later.";
                }
            } else {
                try {
                    $db->add('donation_appointments', [
                        'donor_email' => $email,
                        'donor_name' => $username,
                        'bloodgroup' => $bloodgroup,
                        'appointment_date' => $formattedDate,
                        'appointment_time' => $appointmentTime,
                        'location' => $appointmentLocation,
                        'status' => 'Pending',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $bookingMsg = "Your donation slot has been booked successfully.";
                    $showBookingPopup = true;
                } catch (Exception $e) {
                    error_log("Appointment booking failed: " . $e->getMessage());
                    $bookingError = "Unable to save your booking right now. Please try again later.";
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_booking'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $bookingError = "Invalid request. Please refresh and try again.";
    } else {
        $appointmentId = trim($_POST['appointment_id'] ?? '');
        if ($appointmentId === '') {
            $bookingError = "Invalid booking selected for editing.";
        } else {
            try {
                $editableAppointment = $db->getById('donation_appointments', $appointmentId);

                if ($editableAppointment && $editableAppointment['donor_email'] === $email && $editableAppointment['status'] !== 'Cancelled') {
                    $isEditingBooking = true;
                    $editingAppointmentId = $editableAppointment['_id'];
                    $selectedAppointmentDate = $editableAppointment['appointment_date'];
                    $selectedAppointmentTime = $editableAppointment['appointment_time'];
                    $selectedAppointmentLocation = $editableAppointment['location'];
                    $bookingMsg = "You can now edit your selected slot and submit again.";
                } else {
                    $bookingError = "This booking cannot be edited.";
                }
            } catch (Exception $e) {
                $bookingError = "Unable to prepare edit request.";
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cancel_booking'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $bookingError = "Invalid request. Please refresh and try again.";
    } else {
        $appointmentId = trim($_POST['appointment_id'] ?? '');
        if ($appointmentId === '') {
            $bookingError = "Invalid booking selected for cancellation.";
        } else {
            try {
                $toCancel = $db->getById('donation_appointments', $appointmentId);

                if ($toCancel && $toCancel['donor_email'] === $email && $toCancel['status'] !== 'Cancelled') {
                    $db->update('donation_appointments', $appointmentId, ['status' => 'Cancelled']);
                    $bookingMsg = "Your booking has been cancelled.";
                    $isEditingBooking = false;
                    $editingAppointmentId = '';
                    $selectedAppointmentDate = "";
                    $selectedAppointmentTime = "";
                    $selectedAppointmentLocation = "";
                } else {
                    $bookingError = "Unable to cancel this booking.";
                }
            } catch (Exception $e) {
                $bookingError = "Unable to prepare cancellation request.";
            }
        }
    }
}

$appointmentRows = $db->getWhere(
    'donation_appointments',
    [['field' => 'donor_email', 'op' => 'EQUAL', 'value' => $loggedInEmail]],
    'created_at',
    'DESCENDING',
    1
);
$latestAppointment = $appointmentRows[0] ?? null;
if ($latestAppointment) {
    $latestAppointment['id'] = $latestAppointment['_id']; // keep old template code (uses ['id']) working
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Donor Profile Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            line-height: 1.6;
            background: #f5f5f5;
        }

        .container {
            width: 85%;
            margin: auto;
        }

        header {
            background: #7a1538;
            padding: 20px 0;
            color: white;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 40px;
            margin-right: 10px;
        }

        .menu {
            list-style: none;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .menu li a {
            text-decoration: none;
            color: white;
            font-weight: bold;
        }

        .page-highlight {
            padding: 22px 0 8px;
        }

        .page-highlight .highlight-box {
            background: linear-gradient(135deg, #7a1538, #a73b66);
            color: #fff;
            border-radius: 14px;
            padding: 18px 22px;
            box-shadow: 0 8px 20px rgba(122,21,56,0.22);
        }

        .page-highlight h1 {
            font-size: 28px;
            margin-bottom: 6px;
        }

        .page-highlight p {
            font-size: 16px;
            opacity: 0.95;
        }

        .logout-btn {
            background: #d32f2f;
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
        }

        .page-box {
            background: white;
            width: 90%;
            margin: 30px auto;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }

        .title {
            font-size: 30px;
            margin-bottom: 15px;
            color: #7a1538;
        }

        .welcome {
            margin-bottom: 20px;
            font-size: 18px;
        }

        .profile-card {
            background: #fff7f7;
            border-left: 5px solid #7a1538;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .profile-card h3 {
            color: #7a1538;
            margin-bottom: 12px;
        }

        .profile-card p {
            margin-bottom: 8px;
            font-size: 16px;
        }

        .alert-box {
            max-width: 900px;
            margin: 18px auto 0;
            padding: 12px 15px;
            border-radius: 10px;
            font-weight: bold;
        }

        .alert-success {
            background: #e7f6ea;
            color: #1f6b2d;
            border: 1px solid #8bc49a;
        }

        .alert-error {
            background: #ffe7e7;
            color: #9e1f1f;
            border: 1px solid #e2a0a0;
        }

        .popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 99999;
        }

        .popup-overlay.show {
            display: flex;
        }

        .popup-box {
            width: min(520px, 100%);
            background: #fff;
            border-radius: 14px;
            border-top: 6px solid #7a1538;
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.22);
            padding: 22px;
        }

        .popup-box h3 {
            color: #7a1538;
            margin-bottom: 8px;
            font-size: 25px;
        }

        .popup-box p {
            margin-bottom: 12px;
            color: #555;
        }

        .popup-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 14px;
        }

        .popup-item {
            border: 1px solid #edd5de;
            border-radius: 10px;
            padding: 10px;
            background: #fff9fb;
        }

        .popup-item span {
            display: block;
            color: #7a1538;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 4px;
            letter-spacing: 0.4px;
        }

        .popup-item strong {
            color: #444;
            font-size: 14px;
        }

        .popup-actions {
            display: flex;
            justify-content: flex-end;
        }

        .popup-close {
            border: none;
            background: #7a1538;
            color: #fff;
            padding: 10px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
        }

        .status {
            font-weight: bold;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-box {
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
        }

        .info-box label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .info-box .value {
            color: #555;
            font-size: 17px;
        }

        .booking-card {
            margin-top: 28px;
            background: linear-gradient(135deg, #fff7f7, #ffffff);
            border: 1px solid #eed5dd;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 8px 20px rgba(122,21,56,0.08);
        }

        .booking-card h2 {
            color: #7a1538;
            margin-bottom: 8px;
            font-size: 26px;
        }

        .booking-card p {
            margin-bottom: 14px;
            color: #555;
        }

        .booking-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 12px;
        }

        .booking-grid .info-box {
            background: #fff;
        }

        .booking-grid .value input,
        .booking-grid .value select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: #fff;
        }

        .booking-note {
            margin-top: 12px;
            font-size: 14px;
            color: #6d1840;
        }

        .slot-summary {
            margin-top: 18px;
            background: #fffdfd;
            border: 1px solid #edd5de;
            border-radius: 12px;
            padding: 18px;
        }

        .slot-summary h3 {
            color: #7a1538;
            margin-bottom: 10px;
        }

        .slot-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .slot-summary-item {
            background: #fff;
            border: 1px solid #eedee4;
            border-radius: 10px;
            padding: 12px;
        }

        .slot-summary-item span {
            display: block;
            color: #7a1538;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
            letter-spacing: 0.4px;
        }

        .slot-summary-item strong {
            color: #444;
            font-size: 15px;
        }

        .slot-actions {
            margin-top: 12px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .slot-action-btn {
            border: none;
            border-radius: 20px;
            padding: 9px 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .slot-action-btn.edit {
            background: #7a1538;
            color: #fff;
        }

        .slot-action-btn.cancel {
            background: #fbeaec;
            color: #8a1b3f;
            border: 1px solid #e7c8d3;
        }

        .full-width {
            grid-column: span 2;
        }

        .bottom-buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .action-link {
            text-decoration: none;
            background: #7a1538;
            color: white;
            padding: 12px 22px;
            border-radius: 25px;
            font-weight: bold;
            display: inline-block;
        }

        .action-link:hover {
            background: #5e102c;
        }

        @media(max-width: 768px) {
            .container {
                width: 92%;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
            }

            .nav {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .menu {
                width: 100%;
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .menu li {
                width: 100%;
            }

            .menu li a {
                display: block;
                padding: 6px 0;
            }

            .page-box {
                width: 100%;
                padding: 24px 18px;
            }

            .bottom-buttons {
                flex-direction: column;
            }

            .booking-grid {
                grid-template-columns: 1fr;
            }

            .slot-summary-grid {
                grid-template-columns: 1fr;
            }

            .popup-details {
                grid-template-columns: 1fr;
            }

            .action-link {
                text-align: center;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="container nav">
        <div class="logo">
            <img decoding="async" src="assets/img/logo.jpg" alt="logo" border="0">
            <h2>Lifeline Blood Bank</h2>
        </div>
        <ul class="menu">
            <li><a href="index.php">HOME</a></li>
            <li><a href="about.php">ABOUT US</a></li>
            <li><a href="contact.php">CONTACT US</a></li>
            <li><a href="camp.php">CAMP</a></li>
            <li>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" name="logout" class="logout-btn">Logout</button>
                </form>
            </li>
        </ul>
    </div>
</header>

<section class="page-highlight">
    <div class="container">
        <div class="highlight-box">
            <h1>Donor Dashboard</h1>
            <p>Track your profile, eligibility status, and next donation date in one place.</p>
        </div>
    </div>
</section>

<?php if (!empty($bookingMsg)) : ?>
    <div class="alert-box alert-success"><?php echo htmlspecialchars($bookingMsg, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if (!empty($bookingError)) : ?>
    <div class="alert-box alert-error"><?php echo htmlspecialchars($bookingError, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="page-box">
    <h1 class="title">Donor Profile Dashboard</h1>
    <p class="welcome">Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></p>

    <?php
    // Reminder for the donor's upcoming (not cancelled / completed) appointment.
    if ($latestAppointment && in_array($latestAppointment['status'] ?? '', ['Pending', 'Confirmed'], true)) {
        $aptDay = DateTime::createFromFormat('Y-m-d', $latestAppointment['appointment_date'] ?? '');
        if ($aptDay) {
            $daysLeft = (int)(new DateTime('today'))->diff($aptDay->setTime(0, 0))->format('%r%a');
            if ($daysLeft >= 0) {
                $when = $daysLeft === 0 ? 'today' : ($daysLeft === 1 ? 'tomorrow' : "in {$daysLeft} days");
                echo '<div class="alert-box alert-success" style="margin:0 0 20px;">Reminder: your donation is <strong>' . $when . '</strong> ('
                    . htmlspecialchars(date('d M Y', $aptDay->getTimestamp()), ENT_QUOTES, 'UTF-8') . ', '
                    . htmlspecialchars($latestAppointment['appointment_time'] ?? '', ENT_QUOTES, 'UTF-8') . ' at '
                    . htmlspecialchars($latestAppointment['location'] ?? '', ENT_QUOTES, 'UTF-8') . ').</div>';
            }
        }
    }
    ?>

    <div class="profile-card">
        <h3>Your Donation Status</h3>
        <p><strong>Last Donation Date:</strong> <?php echo htmlspecialchars($lastdonation); ?></p>
        <p><strong>Next Eligible Date:</strong> <?php echo htmlspecialchars($nextDonationDate); ?></p>
        <p><strong>Status:</strong> <span class="status" style="color: <?php echo $statusColor; ?>;"><?php echo $status; ?></span></p>
    </div>

    <?php if ($isEligible) : ?>
        <div class="booking-card">
            <h2>Book Your Donation Slot</h2>
            <p>You are eligible to donate. Choose your preferred date, time, and location below.</p>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="booking_mode" value="<?php echo $isEditingBooking ? 'edit' : 'new'; ?>">
                <input type="hidden" name="appointment_id" value="<?php echo (int)$editingAppointmentId; ?>">
                <div class="booking-grid">
                    <div class="info-box">
                        <label for="appointment_date">Donation Date</label>
                        <div class="value">
                            <input type="date" id="appointment_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($selectedAppointmentDate, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>

                    <div class="info-box">
                        <label for="appointment_time">Donation Time</label>
                        <div class="value">
                            <select id="appointment_time" name="appointment_time" required>
                                <option value="">Select time</option>
                                <option value="9:00 AM - 11:00 AM" <?php echo ($selectedAppointmentTime === '9:00 AM - 11:00 AM') ? 'selected' : ''; ?>>9:00 AM - 11:00 AM</option>
                                <option value="11:00 AM - 1:00 PM" <?php echo ($selectedAppointmentTime === '11:00 AM - 1:00 PM') ? 'selected' : ''; ?>>11:00 AM - 1:00 PM</option>
                                <option value="1:00 PM - 3:00 PM" <?php echo ($selectedAppointmentTime === '1:00 PM - 3:00 PM') ? 'selected' : ''; ?>>1:00 PM - 3:00 PM</option>
                                <option value="3:00 PM - 5:00 PM" <?php echo ($selectedAppointmentTime === '3:00 PM - 5:00 PM') ? 'selected' : ''; ?>>3:00 PM - 5:00 PM</option>
                            </select>
                        </div>
                    </div>

                    <div class="info-box">
                        <label for="appointment_location">Donation Location</label>
                        <div class="value">
                            <select id="appointment_location" name="appointment_location" required>
                                <option value="">Select location</option>
                                <?php foreach (donation_locations($db) as $locOption) : ?>
                                    <option value="<?php echo htmlspecialchars($locOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selectedAppointmentLocation === $locOption) ? 'selected' : ''; ?>><?php echo htmlspecialchars($locOption, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <p class="booking-note">Your booking will remain pending until our team confirms the slot.</p>
                <div class="bottom-buttons">
                    <button type="submit" name="book_donation" class="action-link" style="border:none; cursor:pointer;"><?php echo $isEditingBooking ? 'Update Slot' : 'Book Donation Slot'; ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($latestAppointment) : ?>
        <div class="slot-summary">
            <h3>My Booked Slot</h3>
            <div class="slot-summary-grid">
                <div class="slot-summary-item">
                    <span>Date</span>
                    <strong><?php echo htmlspecialchars(date('d M Y', strtotime($latestAppointment['appointment_date'])), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="slot-summary-item">
                    <span>Time</span>
                    <strong><?php echo htmlspecialchars($latestAppointment['appointment_time'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="slot-summary-item">
                    <span>Location</span>
                    <strong><?php echo htmlspecialchars($latestAppointment['location'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="slot-summary-item">
                    <span>Status</span>
                    <strong><?php echo htmlspecialchars($latestAppointment['status'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>

            <?php if ($latestAppointment['status'] !== 'Cancelled') : ?>
                <div class="slot-actions">
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($latestAppointment['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" name="edit_booking" class="slot-action-btn edit">Edit Booking</button>
                    </form>

                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($latestAppointment['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" name="cancel_booking" class="slot-action-btn cancel">Cancel Booking</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($profileMsg) : ?>
        <div class="alert-box alert-success"><?php echo htmlspecialchars($profileMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($profileError) : ?>
        <div class="alert-box alert-error"><?php echo htmlspecialchars($profileError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <button type="button" class="action-link" style="border:0;cursor:pointer;margin:20px 0 16px;"
            onclick="var f=document.getElementById('editProfileForm'); f.hidden=!f.hidden;">Edit Profile</button>

    <form method="post" id="editProfileForm" class="info-grid" style="margin-bottom:20px;" <?php echo $profileError ? '' : 'hidden'; ?>>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="info-box">
            <label>Phone</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" pattern="\d{10}" maxlength="10" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')" title="Enter 10 digit mobile number" required style="width:100%;padding:8px;box-sizing:border-box;">
        </div>
        <div class="info-box">
            <label>Age</label>
            <input type="number" name="age" min="18" value="<?php echo htmlspecialchars($age, ENT_QUOTES, 'UTF-8'); ?>" required style="width:100%;padding:8px;box-sizing:border-box;">
        </div>
        <div class="info-box">
            <label>Weight (kg)</label>
            <input type="number" name="weight" min="50" value="<?php echo htmlspecialchars($weight, ENT_QUOTES, 'UTF-8'); ?>" required style="width:100%;padding:8px;box-sizing:border-box;">
        </div>
        <div class="info-box">
            <label>Last Donation</label>
            <input type="date" name="lastdonation" value="<?php echo htmlspecialchars((string)$lastdonation, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;padding:8px;box-sizing:border-box;">
        </div>
        <div class="info-box full-width">
            <label>Address</label>
            <textarea name="address" required style="width:100%;padding:8px;box-sizing:border-box;min-height:70px;"><?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <div>
            <button type="submit" name="update_profile" class="action-link" style="border:0;cursor:pointer;">Save Changes</button>
        </div>
    </form>

    <div class="info-grid">
        <div class="info-box">
            <label>Name</label>
            <div class="value"><?php echo htmlspecialchars($username); ?></div>
        </div>

        <div class="info-box">
            <label>Blood Group</label>
            <div class="value"><?php echo htmlspecialchars($bloodgroup); ?></div>
        </div>

        <div class="info-box">
            <label>Phone</label>
            <div class="value"><?php echo htmlspecialchars($phone); ?></div>
        </div>

        <div class="info-box">
            <label>Email</label>
            <div class="value"><?php echo htmlspecialchars($email); ?></div>
        </div>

        <div class="info-box">
            <label>Age</label>
            <div class="value"><?php echo htmlspecialchars($age); ?></div>
        </div>

        <div class="info-box">
            <label>Weight</label>
            <div class="value"><?php echo htmlspecialchars($weight); ?> kg</div>
        </div>

        <div class="info-box full-width">
            <label>Address</label>
            <div class="value"><?php echo nl2br(htmlspecialchars($address)); ?></div>
        </div>
    </div>

    <div class="bottom-buttons">
        <a href="find.php" class="action-link">Find Blood</a>
        <a href="request.php" class="action-link">Request Blood</a>
    </div>
</div>

<?php if ($showBookingPopup && $latestAppointment) : ?>
    <div class="popup-overlay show" id="bookingPopup">
        <div class="popup-box" role="dialog" aria-modal="true" aria-labelledby="bookingPopupTitle">
            <h3 id="bookingPopupTitle">Booking Confirmed</h3>
            <p>Your slot has been booked with the details below.</p>
            <div class="popup-details">
                <div class="popup-item">
                    <span>Date</span>
                    <strong><?php echo htmlspecialchars(date('d M Y', strtotime($latestAppointment['appointment_date'])), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="popup-item">
                    <span>Time</span>
                    <strong><?php echo htmlspecialchars($latestAppointment['appointment_time'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="popup-item">
                    <span>Location</span>
                    <strong><?php echo htmlspecialchars($latestAppointment['location'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="popup-item">
                    <span>Status</span>
                    <strong><?php echo htmlspecialchars($latestAppointment['status'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>
            <div class="popup-actions">
                <button type="button" class="popup-close" id="closeBookingPopup">OK</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const popup = document.getElementById("bookingPopup");
    const closeBtn = document.getElementById("closeBookingPopup");

    if (!popup) {
        return;
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", function () {
            popup.classList.remove("show");
        });
    }

    popup.addEventListener("click", function (event) {
        if (event.target === popup) {
            popup.classList.remove("show");
        }
    });
});
</script>

</body>
</html>
