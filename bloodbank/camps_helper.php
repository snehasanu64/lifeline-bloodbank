<?php
/**
 * Camp helpers shared by camp.php, donor_home.php and admin_camps.php.
 *
 * Camps are stored in the Firestore collection "camps":
 *   name, address, time, description, map_query,
 *   start_date (Y-m-d), recurrence ('none' | 'weekly' | 'monthly'),
 *   active (bool), created_at
 *
 * The date shown to visitors is NEVER stored. It is worked out every time
 * from start_date + recurrence, so recurring camps roll forward on their own
 * and one-time camps disappear once their date has passed.
 */

/** Next date (Y-m-d) on which a camp happens, or null if it is over. */
function camp_next_date(array $camp, ?DateTime $today = null): ?string
{
    $today = $today ? (clone $today)->setTime(0, 0) : new DateTime('today');
    $start = DateTime::createFromFormat('Y-m-d', $camp['start_date'] ?? '');
    if (!$start) {
        return null;
    }
    $start->setTime(0, 0);
    $recurrence = $camp['recurrence'] ?? 'none';

    if ($start >= $today) {
        return $start->format('Y-m-d'); // hasn't happened yet
    }

    if ($recurrence === 'weekly') {
        $days = (int)$start->diff($today)->days;
        $weeks = (int)ceil($days / 7);
        return $start->modify('+' . ($weeks * 7) . ' days')->format('Y-m-d');
    }

    if ($recurrence === 'monthly') {
        $dayOfMonth = (int)$start->format('j');
        $year = (int)$today->format('Y');
        $month = (int)$today->format('n');
        for ($i = 0; $i < 3; $i++) {
            $d = min($dayOfMonth, (int)cal_days_in_month(CAL_GREGORIAN, $month, $year));
            $candidate = new DateTime(sprintf('%04d-%02d-%02d', $year, $month, $d));
            if ($candidate >= $today) {
                return $candidate->format('Y-m-d');
            }
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
        }
    }

    return null; // one-time camp that has already passed
}

/** Text used as the donation location, e.g. "MG Road Camp - Brigade Road, Bangalore". */
function camp_label(array $camp): string
{
    return trim(($camp['name'] ?? '') . ' - ' . ($camp['address'] ?? ''), ' -');
}

/**
 * Active camps that still have an upcoming date, soonest first.
 * Each returned camp gets a 'next_date' key.
 */
function load_upcoming_camps($db): array
{
    $out = [];
    try {
        foreach ($db->getAll('camps') as $camp) {
            if (array_key_exists('active', $camp) && !$camp['active']) {
                continue;
            }
            $next = camp_next_date($camp);
            if ($next !== null) {
                $camp['next_date'] = $next;
                $out[] = $camp;
            }
        }
    } catch (Exception $e) {
        error_log("Loading camps failed: " . $e->getMessage());
    }
    usort($out, fn($a, $b) => strcmp($a['next_date'], $b['next_date']));
    return $out;
}

/** Donation locations a donor may book: the main office plus every upcoming camp. */
function donation_locations($db): array
{
    $list = ['Lifeline Blood Bank Office, HAL 3rd Stage'];
    foreach (load_upcoming_camps($db) as $camp) {
        $list[] = camp_label($camp);
    }
    return array_values(array_unique($list));
}
