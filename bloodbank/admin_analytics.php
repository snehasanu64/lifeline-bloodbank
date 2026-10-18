<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

include('db_connect.php');

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if (!function_exists('dx')) {
    function dx($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// ------------------------------------------------------------------ data
// Firestore has no COUNT / GROUP BY, so each collection is read once and
// everything below is worked out in PHP. Only real data is used.
$allAppointments = $allDonors = $allRequests = $allStock = $allLedger = [];
try {
    $allAppointments = $db->getAll('donation_appointments');
    $allDonors = $db->getAll('donors');
    $allRequests = $db->getAll('blood_requests');
    $allStock = $db->getAll('blood_stock');
    $allLedger = $db->getAll('blood_ledger');
} catch (Exception $e) {
    error_log("Analytics data fetch failed: " . $e->getMessage());
}

$range = (int)($_GET['range'] ?? 30);
if (!in_array($range, [14, 30, 90], true)) {
    $range = 30;
}
$today = date('Y-m-d');
$from = date('Y-m-d', strtotime('-' . ($range - 1) . ' days'));           // this period: $from .. $today
$prevFrom = date('Y-m-d', strtotime('-' . (2 * $range - 1) . ' days'));    // previous period of the same length
$prevTo = date('Y-m-d', strtotime('-' . $range . ' days'));

// ---- monthly statistics (last 6 months, always) -------------------------
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime(date('Y-m-01') . " -$i months"));
    $months[$key] = ['label' => date('M Y', strtotime($key . '-01')), 'total' => 0, 'completed' => 0, 'scheduled' => 0, 'cancelled' => 0, 'donors' => 0];
}
foreach ($allAppointments as $a) {
    $m = substr($a['appointment_date'] ?? '', 0, 7);
    if (!isset($months[$m])) { continue; }
    $st = $a['status'] ?? '';
    $months[$m]['total']++;
    if ($st === 'Completed') { $months[$m]['completed']++; }
    elseif ($st === 'Cancelled') { $months[$m]['cancelled']++; }
    else { $months[$m]['scheduled']++; }
}
foreach ($allDonors as $d) {
    $m = substr($d['created_at'] ?? '', 0, 7);
    if (isset($months[$m])) { $months[$m]['donors']++; }
}

// ---- CSV export of the monthly table -----------------------------------
if (($_GET['export'] ?? '') === 'monthly') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="monthly_statistics_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Month', 'Total appointments', 'Completed', 'Scheduled', 'Cancelled', 'Completion rate %', 'New donors']);
    foreach ($months as $m) {
        fputcsv($out, [$m['label'], $m['total'], $m['completed'], $m['scheduled'], $m['cancelled'],
            $m['total'] ? round($m['completed'] / $m['total'] * 100) : 0, $m['donors']]);
    }
    fclose($out);
    exit();
}

// ---- trend: appointments per day in the chosen range ---------------------
$daily = [];
for ($ts = strtotime($from); $ts <= strtotime($today); $ts += 86400) {
    $daily[date('Y-m-d', $ts)] = 0;
}
$apptThis = $apptPrev = $completedThis = 0;
foreach ($allAppointments as $a) {
    $date = $a['appointment_date'] ?? '';
    $st = $a['status'] ?? '';
    if ($date >= $from && $date <= $today) {
        $apptThis++;
        if ($st === 'Completed') { $completedThis++; }
        if ($st !== 'Cancelled') { $daily[$date]++; }
    } elseif ($date >= $prevFrom && $date <= $prevTo) {
        $apptPrev++;
    }
}
$donorsThis = $donorsPrev = 0;
foreach ($allDonors as $d) {
    $c = substr($d['created_at'] ?? '', 0, 10);
    if ($c >= $from && $c <= $today) { $donorsThis++; }
    elseif ($c >= $prevFrom && $c <= $prevTo) { $donorsPrev++; }
}

function delta_text($cur, $prev, $days) {
    if ($prev == 0) {
        return $cur > 0 ? ['up', 'New activity (none in the previous ' . $days . ' days)'] : ['flat', 'No change vs previous ' . $days . ' days'];
    }
    $pct = round(($cur - $prev) / $prev * 100);
    if ($pct == 0) { return ['flat', 'Same as previous ' . $days . ' days']; }
    return [$pct > 0 ? 'up' : 'down', ($pct > 0 ? '▲ ' : '▼ ') . abs($pct) . '% vs previous ' . $days . ' days'];
}
[$apptDeltaClass, $apptDeltaText] = delta_text($apptThis, $apptPrev, $range);
[$donorDeltaClass, $donorDeltaText] = delta_text($donorsThis, $donorsPrev, $range);

$completionRate = $apptThis > 0 ? round($completedThis / $apptThis * 100) : 0;

// ---- statuses ------------------------------------------------------------
$aptStatus = ['Pending' => 0, 'Confirmed' => 0, 'Completed' => 0, 'Cancelled' => 0];
foreach ($allAppointments as $a) {
    $st = $a['status'] ?? '';
    if (isset($aptStatus[$st])) { $aptStatus[$st]++; }
}
$reqStatus = ['Pending' => 0, 'Fulfilled' => 0, 'Cancelled' => 0];
$pendingDemand = array_fill_keys(['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'], 0);
foreach ($allRequests as $r) {
    $st = $r['request_status'] ?? '';
    if (isset($reqStatus[$st])) { $reqStatus[$st]++; }
    $g = $r['blood_group'] ?? '';
    if ($st === 'Pending' && isset($pendingDemand[$g])) { $pendingDemand[$g] += (int)($r['units_needed'] ?? 0); }
}
$reqTotal = array_sum($reqStatus);
$fulfilRate = $reqTotal ? round($reqStatus['Fulfilled'] / $reqTotal * 100) : 0;
$aptStatusColors = ['Pending' => '#d98a00', 'Confirmed' => '#3b6fb6', 'Completed' => '#1b7f4b', 'Cancelled' => '#9aa1ab'];
$reqStatusColors = ['Pending' => '#d98a00', 'Fulfilled' => '#1b7f4b', 'Cancelled' => '#9aa1ab'];

// ---- supply vs demand per blood group ------------------------------------
$supply = array_fill_keys(array_keys($pendingDemand), 0);
foreach ($allStock as $s) {
    $exp = $s['expiry_date'] ?? '';
    if ($exp !== '' && $exp < $today) { continue; }
    $g = $s['blood_group'] ?? '';
    if (isset($supply[$g])) { $supply[$g] += (int)($s['units_available'] ?? 0); }
}
$balance = [];
foreach ($supply as $g => $u) {
    $balance[] = ['group' => $g, 'supply' => $u, 'demand' => $pendingDemand[$g], 'gap' => $u - $pendingDemand[$g]];
}
usort($balance, fn($a, $b) => $a['gap'] <=> $b['gap']);
$supplyPeak = max(1, max(array_column($balance, 'supply')), max(array_column($balance, 'demand')));
$shortGroups = count(array_filter($balance, fn($b) => $b['gap'] < 0));

// ---- locations, time slots, weekdays ------------------------------------
$byLocation = $bySlot = $byWeekday = [];
foreach ($allAppointments as $a) {
    if (($a['status'] ?? '') === 'Cancelled') { continue; }
    $loc = $a['location'] ?? 'Unknown';
    $byLocation[$loc] = ($byLocation[$loc] ?? 0) + 1;
    $slot = $a['appointment_time'] ?? 'Unknown';
    $bySlot[$slot] = ($bySlot[$slot] ?? 0) + 1;
    $wd = date('l', strtotime($a['appointment_date'] ?? $today));
    $byWeekday[$wd] = ($byWeekday[$wd] ?? 0) + 1;
}
arsort($byLocation); arsort($bySlot); arsort($byWeekday);
$byLocation = array_slice($byLocation, 0, 6, true);
$locPeak = max(1, $byLocation ? max($byLocation) : 1);
ksort($bySlot);
$slotPeak = max(1, $bySlot ? max($bySlot) : 1);

// ---- ledger flow in range -------------------------------------------------
$ledgerIn = $ledgerOut = 0;
foreach ($allLedger as $l) {
    $d = $l['entry_date'] ?? '';
    if ($d < $from || $d > $today) { continue; }
    if (($l['transaction_type'] ?? '') === 'TAKEN') { $ledgerIn += (int)($l['units'] ?? 0); }
    else { $ledgerOut += (int)($l['units'] ?? 0); }
}

// ---- key insights (plain sentences worked out from the data) --------------
$insights = [];
if ($byWeekday) { $insights[] = ['📅', 'Busiest day for donations: <b>' . dx(array_key_first($byWeekday)) . '</b> (' . current($byWeekday) . ' appointments).']; }
if ($byLocation) { $insights[] = ['📍', 'Most popular location: <b>' . dx(array_key_first($byLocation)) . '</b>.']; }
if ($bySlot) { $s2 = $bySlot; arsort($s2); $insights[] = ['🕘', 'Most booked time slot: <b>' . dx(array_key_first($s2)) . '</b>.']; }
$worst = $balance[0] ?? null;
if ($worst && $worst['gap'] < 0) {
    $insights[] = ['⚠️', '<b>' . dx($worst['group']) . '</b> is short by <b>' . abs($worst['gap']) . '</b> units against pending requests.'];
} elseif ($worst) {
    $insights[] = ['✅', 'Stock covers every pending request. Tightest group: <b>' . dx($worst['group']) . '</b> (' . $worst['gap'] . ' units spare).'];
}
$insights[] = ['🩸', 'Request fulfilment rate: <b>' . $fulfilRate . '%</b> (' . $reqStatus['Fulfilled'] . ' of ' . $reqTotal . ' requests).'];


// =====================================================================
// SMART FORECAST (statistics on your own data - nothing leaves the server)
// =====================================================================
$compat = [   // recipient group => donor groups that can safely give to it
    'O-' => ['O-'], 'O+' => ['O-', 'O+'], 'A-' => ['O-', 'A-'], 'A+' => ['O-', 'O+', 'A-', 'A+'],
    'B-' => ['O-', 'B-'], 'B+' => ['O-', 'O+', 'B-', 'B+'], 'AB-' => ['O-', 'A-', 'B-', 'AB-'],
    'AB+' => ['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'],
];
$lookback = 90;
$lbFrom = date('Y-m-d', strtotime("-$lookback days"));
$recentFrom = date('Y-m-d', strtotime('-30 days'));
$olderFrom = date('Y-m-d', strtotime('-60 days'));
$groupsList = array_keys($compat);
$demandTotal = array_fill_keys($groupsList, 0);
$demandRecent = array_fill_keys($groupsList, 0);
$demandOlder = array_fill_keys($groupsList, 0);
$earliest = $today;
foreach ($allRequests as $rq) {
    if (($rq['request_status'] ?? '') === 'Cancelled') { continue; }
    $g = $rq['blood_group'] ?? '';
    $d = substr($rq['created_at'] ?? '', 0, 10);
    if (!isset($demandTotal[$g]) || $d === '' || $d < $lbFrom) { continue; }
    $u = (int)($rq['units_needed'] ?? 0);
    $demandTotal[$g] += $u;
    if ($d >= $recentFrom) { $demandRecent[$g] += $u; } elseif ($d >= $olderFrom) { $demandOlder[$g] += $u; }
    if ($d < $earliest) { $earliest = $d; }
}
// observed window: at least 14 days so one request does not look like a huge daily rate
$observedDays = max(14, min($lookback, (int)((strtotime($today) - strtotime($earliest)) / 86400) + 1));

$eligibleByGroup = array_fill_keys($groupsList, []);
foreach ($allDonors as $dn) {
    $ld = $dn['lastdonation'] ?? '';
    if ($ld !== '' && date('Y-m-d', strtotime($ld . ' +90 days')) > $today) { continue; }
    $eligibleByGroup[$dn['bloodgroup'] ?? ''][] = $dn;
}

$forecast = [];
foreach ($groupsList as $g) {
    $dRate = $demandTotal[$g] / $observedDays;
    $need14 = (int)ceil($dRate * 14);
    $sup = $supply[$g] ?? 0;
    $cover = $dRate > 0 ? $sup / $dRate : null;                        // days of stock left at the current pace
    $pend = $pendingDemand[$g] ?? 0;
    if ($sup < $pend || ($cover !== null && $cover < 7)) { $risk = 'critical'; }
    elseif (($cover !== null && $cover < 21) || $sup < $need14) { $risk = 'watch'; }
    else { $risk = 'safe'; }
    if ($demandRecent[$g] > $demandOlder[$g]) { $trend = 'up'; }
    elseif ($demandRecent[$g] < $demandOlder[$g]) { $trend = 'down'; }
    else { $trend = 'flat'; }
    // eligible, compatible donors who could fix a shortage
    $helpers = [];
    foreach ($compat[$g] as $dg) { foreach ($eligibleByGroup[$dg] ?? [] as $dn) { $helpers[] = $dn; } }
    $forecast[] = ['group' => $g, 'supply' => $sup, 'daily' => $dRate, 'need14' => $need14, 'cover' => $cover,
        'risk' => $risk, 'trend' => $trend, 'pending' => $pend, 'helpers' => $helpers];
}
$riskOrder = ['critical' => 0, 'watch' => 1, 'safe' => 2];
usort($forecast, fn($a, $b) => [$riskOrder[$a['risk']], $a['cover'] ?? 9999] <=> [$riskOrder[$b['risk']], $b['cover'] ?? 9999]);
$atRisk = array_values(array_filter($forecast, fn($f) => $f['risk'] !== 'safe'));
$totalDaily = array_sum(array_column($forecast, 'daily'));
$totalSupply = array_sum(array_column($forecast, 'supply'));
$overallCover = $totalDaily > 0 ? round($totalSupply / $totalDaily) : null;

// plain-English summary
$aiLines = [];
$aiLines[] = $atRisk
    ? count($atRisk) . ' blood group' . (count($atRisk) == 1 ? ' needs' : 's need') . ' attention: ' . implode(', ', array_map(fn($f) => $f['group'] . ' (' . $f['risk'] . ')', array_slice($atRisk, 0, 4))) . '.'
    : 'All eight blood groups look safe for the next two weeks at the current request pace.';
$coverWords = fn($d) => $d === null || $d > 365 ? 'over a year' : (int)round($d) . ' days';
$aiLines[] = 'At the recent pace (about ' . round($totalDaily, 1) . ' units requested per day), total usable stock of ' . $totalSupply . ' units lasts ' . ($overallCover !== null && $overallCover <= 365 ? 'about ' : '') . $coverWords($overallCover) . '.';
$rising = array_filter($forecast, fn($f) => $f['trend'] === 'up');
if ($rising) { $aiLines[] = 'Demand is rising for ' . implode(', ', array_map(fn($f) => $f['group'], array_slice($rising, 0, 4))) . ' compared with the previous 30 days.'; }
$finished = $aptStatus['Cancelled'] + $aptStatus['Completed'];
$noShow = $finished > 0 ? round($aptStatus['Cancelled'] / $finished * 100) : 0;
if ($noShow >= 25) { $aiLines[] = 'Cancellations are high (' . $noShow . '% of finished bookings). Consider sending reminders the day before.'; }
$expiringUnits = 0;
foreach ($allStock as $s) { $e = $s['expiry_date'] ?? ''; if ($e !== '' && $e >= $today && $e <= date('Y-m-d', strtotime('+3 days'))) { $expiringUnits += (int)($s['units_available'] ?? 0); } }
if ($expiringUnits > 0) { $aiLines[] = $expiringUnits . ' units expire within 3 days. Issue those first.'; }

// "Ask" box: answers common questions from the same numbers (no outside service)
$askQ = trim($_GET['ask'] ?? '');
$askA = '';
if ($askQ !== '') {
    $q = strtolower($askQ);
    $first = $forecast[0] ?? null;
    if (preg_match('/short|low|critical|risk|lowest|run out|runout/', $q)) {
        $askA = $atRisk ? 'Most at risk: ' . $first['group'] . ' with ' . ($first['cover'] !== null ? 'about ' . round($first['cover']) . ' days of cover' : 'no recent demand') . ' (' . $first['supply'] . ' units, ' . $first['pending'] . ' needed by pending requests).' : 'No blood group is at risk right now.';
    } elseif (preg_match('/demand|request|need|most requested|popular/', $q)) {
        $top = $forecast; usort($top, fn($a, $b) => $b['daily'] <=> $a['daily']);
        $askA = 'Highest demand: ' . $top[0]['group'] . ' (about ' . round($top[0]['daily'], 2) . ' units/day, ' . $top[0]['need14'] . ' expected over 14 days).';
    } elseif (preg_match('/donor|eligible|contact|call|outreach/', $q)) {
        $askA = $first ? count($first['helpers']) . ' eligible donors could help ' . $first['group'] . ' (compatible groups included). See the outreach list below.' : 'No data.';
    } elseif (preg_match('/appoint|booking|busy|day/', $q)) {
        $askA = $byWeekday ? 'Busiest day: ' . array_key_first($byWeekday) . '. Appointments in the last ' . $range . ' days: ' . $apptThis . ' (' . $completionRate . '% completed).' : 'No appointment data yet.';
    } elseif (preg_match('/expir|waste|spoil/', $q)) {
        $askA = $expiringUnits . ' units expire within 3 days.';
    } elseif (preg_match('/stock|supply|units|inventory/', $q)) {
        $askA = 'Usable stock: ' . $totalSupply . ' units across all groups; ' . ($overallCover !== null ? 'about ' . $overallCover . ' days of cover.' : 'no recent demand to measure cover.');
    } else {
        $askA = 'I can answer questions about shortages, demand, donors to contact, appointments, expiry and stock. Try: "Which blood group is lowest?"';
    }
}

// ---- SVG line chart geometry ------------------------------------------------
$labels = array_keys($daily);
$vals = array_values($daily);
$n = count($vals);
$maxVal = max(1, max($vals));
$step = $maxVal <= 5 ? 1 : ($maxVal <= 10 ? 2 : ($maxVal <= 25 ? 5 : 10));
$yMax = (int)(ceil($maxVal / $step) * $step);
$W = 760; $H = 250; $L = 38; $R = 14; $T = 14; $B = 34;
$px = fn($i) => $n > 1 ? $L + $i * (($W - $L - $R) / ($n - 1)) : $L;
$py = fn($v) => $T + ($H - $T - $B) * (1 - $v / $yMax);
$linePts = [];
foreach ($vals as $i => $v) { $linePts[] = round($px($i), 1) . ',' . round($py($v), 1); }
$areaPts = round($px(0), 1) . ',' . round($py(0), 1) . ' ' . implode(' ', $linePts) . ' ' . round($px($n - 1), 1) . ',' . round($py(0), 1);
$labelEvery = max(1, (int)ceil($n / 7));
$totalInRange = array_sum($vals);

// stacked monthly bars geometry
$monthPeak = max(1, max(array_column($months, 'total')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - Lifeline Blood Bank</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f9f2f5;
            line-height: 1.5;
            color: #1f2a33;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, #7a1538 0%, #5e0f2a 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
        }

        .sidebar-header img {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            background: #fff;
            padding: 5px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu a {
            display: block;
            color: #ffdfe8;
            padding: 12px 20px;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.08);
            border-left-color: #ffd5e3;
            color: white;
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.14);
            border-left-color: #ffd5e3;
            color: white;
        }

        .sidebar-logout {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 20px;
        }

        .quick-action {
            display: block;
            width: 100%;
            text-align: center;
            border: none;
            background: #b11d4a;
            color: white;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            margin-bottom: 10px;
        }

        .logout-btn {
            width: 100%;
            border: none;
            background: #b11d4a;
            color: white;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .main-content {
            margin-left: 240px;
            padding: 24px;
            width: calc(100% - 240px);
        }
        @media(max-width: 768px) {
            .dashboard-container { flex-direction: column; }
            .sidebar { position: static; width: 100%; height: auto; }
            .main-content { margin-left: 0; width: 100%; padding: 14px; }
        }



        /* ===== Redesigned dashboard (dx-) ===== */
        .dx-hero {
            background: linear-gradient(120deg, #7a1538 0%, #a3194a 55%, #c0132d 100%);
            color: #fff;
            border-radius: 20px;
            padding: 26px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: 0 12px 30px rgba(122, 21, 56, 0.25);
            margin-bottom: 20px;
        }
        .dx-hero h1 { font-size: 30px; line-height: 1.15; margin-bottom: 4px; color: #fff; }
        .dx-hero p { opacity: .9; font-size: 15px; }
        .dx-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .dx-search { display: flex; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.35); border-radius: 30px; overflow: hidden; }
        .dx-search input { background: transparent; border: 0; color: #fff; padding: 10px 16px; width: 220px; outline: none; font-size: 14px; }
        .dx-search input::placeholder { color: rgba(255,255,255,.8); }
        .dx-search button { border: 0; background: #fff; color: #7a1538; font-weight: 700; padding: 0 16px; cursor: pointer; }
        .dx-btn { display: inline-block; text-decoration: none; background: #fff; color: #7a1538; font-weight: 700; font-size: 14px; padding: 10px 18px; border-radius: 30px; transition: transform .15s, box-shadow .15s; }
        .dx-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0,0,0,.2); }
        .dx-btn.ghost { background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,.7); }

        .dx-alerts { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        .dx-alert { flex: 1 1 240px; text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 13px 16px; border-radius: 14px; background: #fff; border: 1px solid #eadde2; border-left-width: 6px; color: #2b2f38; transition: transform .15s, box-shadow .15s; }
        .dx-alert:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,.08); }
        .dx-alert strong { display: block; font-size: 15px; }
        .dx-alert span { font-size: 13px; color: #6b7280; }
        .dx-alert .ic { font-size: 22px; }
        .dx-alert.crit { border-left-color: #c0132d; }
        .dx-alert.warn { border-left-color: #d98a00; }
        .dx-alert.good { border-left-color: #1b7f4b; }

        .dx-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .dx-kpi { text-decoration: none; color: inherit; background: #fff; border-radius: 18px; padding: 18px 20px; border: 1px solid #f0e4e8; box-shadow: 0 4px 14px rgba(0,0,0,.04); transition: transform .15s, box-shadow .15s, border-color .15s; display: block; }
        .dx-kpi:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(122,21,56,.14); border-color: #d9aebb; }
        .dx-kpi .top { display: flex; justify-content: space-between; align-items: center; }
        .dx-kpi .label { font-size: 13px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; font-weight: 600; }
        .dx-kpi .icon { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; font-size: 19px; background: #fbeaf0; }
        .dx-kpi .value { font-size: 36px; font-weight: 800; color: #1d2733; line-height: 1.1; margin-top: 8px; }
        .dx-kpi .sub { font-size: 13px; color: #6b7280; margin-top: 4px; }
        .dx-kpi .go { font-size: 12px; color: #7a1538; font-weight: 700; margin-top: 8px; opacity: 0; transition: opacity .15s; }
        .dx-kpi:hover .go { opacity: 1; }

        .dx-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .dx-panel { background: #fff; border-radius: 18px; padding: 20px 22px; border: 1px solid #f0e4e8; box-shadow: 0 4px 14px rgba(0,0,0,.04); min-width: 0; }
        .dx-panel.wide { grid-column: 1 / -1; }
        .dx-head { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; margin-bottom: 14px; }
        .dx-head h3 { font-size: 18px; color: #1d2733; }
        .dx-head a { font-size: 13px; font-weight: 700; color: #7a1538; text-decoration: none; white-space: nowrap; }
        .dx-head a:hover { text-decoration: underline; }

        .dx-bars { display: grid; gap: 8px; }
        .dx-bar { display: grid; grid-template-columns: 44px 1fr 62px; align-items: center; gap: 10px; text-decoration: none; color: inherit; padding: 4px 6px; border-radius: 10px; transition: background .15s; }
        .dx-bar:hover { background: #fbf3f6; }
        .dx-bar .name { font-weight: 700; font-size: 14px; color: #1d2733; }
        .dx-bar .track { height: 12px; background: #f3e9ed; border-radius: 6px; overflow: hidden; }
        .dx-bar .fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #b8325e, #7a1538); }
        .dx-bar.low .fill { background: #c0132d; }
        .dx-bar .num { text-align: right; font-size: 14px; font-weight: 700; color: #1d2733; }
        .dx-bar .tag { font-size: 11px; color: #c0132d; font-weight: 700; display: block; text-align: right; }
        .dx-legend { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 12px; font-size: 13px; color: #4b5563; }
        .dx-legend i { display: inline-block; width: 12px; height: 12px; border-radius: 3px; margin-right: 6px; vertical-align: -1px; }

        .dx-seg { display: flex; height: 22px; border-radius: 11px; overflow: hidden; gap: 2px; background: #fff; }
        .dx-seg a { display: block; min-width: 6px; transition: filter .15s; }
        .dx-seg a:hover { filter: brightness(1.12); }
        .dx-stat-rows { display: grid; gap: 8px; margin-top: 14px; }
        .dx-stat-rows a { display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: #1d2733; padding: 9px 12px; border-radius: 10px; background: #faf6f8; font-size: 14px; transition: background .15s; }
        .dx-stat-rows a:hover { background: #f4e6ec; }
        .dx-stat-rows b { font-size: 16px; }

        .dx-list { display: grid; gap: 4px; }
        .dx-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 11px 12px; border-radius: 12px; text-decoration: none; color: inherit; transition: background .15s; }
        .dx-row:hover { background: #fbf3f6; }
        .dx-row strong { display: block; font-size: 15px; color: #1d2733; }
        .dx-row span.meta { font-size: 13px; color: #6b7280; }
        .dx-chip { font-size: 12px; font-weight: 700; padding: 4px 11px; border-radius: 20px; white-space: nowrap; }
        .dx-chip.pending { background: #fff3d6; color: #8a5a00; }
        .dx-chip.confirmed { background: #e0ecff; color: #1d4fa3; }
        .dx-chip.fulfilled, .dx-chip.completed { background: #dcf3e6; color: #17653c; }
        .dx-chip.cancelled { background: #eceef1; color: #4b5563; }
        .dx-empty { color: #6b7280; padding: 14px 6px; font-size: 14px; }

        .dx-camp { display: flex; gap: 14px; align-items: center; text-decoration: none; color: inherit; padding: 11px 12px; border-radius: 12px; transition: background .15s; }
        .dx-camp:hover { background: #fbf3f6; }
        .dx-date { flex: 0 0 56px; text-align: center; background: #7a1538; color: #fff; border-radius: 12px; padding: 6px 0; line-height: 1.1; }
        .dx-date b { display: block; font-size: 22px; }
        .dx-date small { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }

        .dx-two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .dx-mini { text-decoration: none; color: inherit; background: #faf6f8; border-radius: 14px; padding: 14px 16px; display: block; transition: background .15s, transform .15s; }
        .dx-mini:hover { background: #f4e6ec; transform: translateY(-2px); }
        .dx-mini b { font-size: 28px; display: block; color: #1d2733; }
        .dx-mini small { color: #6b7280; font-size: 13px; }
        .dx-table summary { cursor: pointer; font-size: 13px; color: #7a1538; font-weight: 700; margin-top: 12px; }
        .dx-table table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 13px; }
        .dx-table td, .dx-table th { padding: 5px 8px; border-bottom: 1px solid #f0e4e8; text-align: left; }

        @media (max-width: 900px) {
            .dx-hero h1 { font-size: 24px; }
            .dx-search input { width: 150px; }
            .dx-grid { grid-template-columns: 1fr; }
        }

        /* ===== Analytics-only ===== */
        .an-tabs { display: inline-flex; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.35); border-radius: 30px; padding: 3px; gap: 2px; }
        .an-tabs a { color: #fff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 8px 16px; border-radius: 26px; }
        .an-tabs a.on { background: #fff; color: #7a1538; }
        .an-tabs a:hover:not(.on) { background: rgba(255,255,255,.18); }
        .an-delta { font-size: 12.5px; font-weight: 600; margin-top: 6px; }
        .an-delta.up { color: #17653c; } .an-delta.down { color: #b3261e; } .an-delta.flat { color: #6b7280; }
        .an-insights { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .an-insight { background: #fff; border: 1px solid #f0e4e8; border-left: 5px solid #7a1538; border-radius: 14px; padding: 13px 16px; display: flex; gap: 12px; align-items: flex-start; font-size: 14.5px; color: #2b2f38; }
        .an-insight .ic { font-size: 22px; }
        svg.an-chart { width: 100%; height: auto; display: block; }
        .an-chart text { font-family: inherit; fill: #6b7280; font-size: 11.5px; }
        .an-chart .grid { stroke: #efe6ea; stroke-width: 1; }
        .an-chart .hit { fill: transparent; cursor: pointer; }
        .an-chart .dot { fill: #7a1538; stroke: #fff; stroke-width: 2; opacity: 0; transition: opacity .1s; }
        .an-chart g.pt:hover .dot { opacity: 1; }
        .an-chart g.pt:hover .vline { opacity: 1; }
        .an-chart .vline { stroke: #d9aebb; stroke-width: 1; opacity: 0; }
        .an-months { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; align-items: end; height: 190px; padding-top: 6px; }
        .an-col { display: flex; flex-direction: column; justify-content: flex-end; height: 100%; text-align: center; }
        .an-stack { display: flex; flex-direction: column-reverse; gap: 2px; margin: 0 auto; width: min(54px, 100%); }
        .an-stack i { display: block; border-radius: 5px; min-height: 4px; }
        .an-col small { color: #6b7280; font-size: 12px; margin-top: 6px; }
        .an-col b { font-size: 13px; color: #1d2733; display: block; margin-bottom: 4px; }
        .an-balance { display: grid; gap: 6px; }
        .an-brow { display: grid; grid-template-columns: 44px 1fr 120px; gap: 10px; align-items: center; text-decoration: none; color: inherit; padding: 6px 8px; border-radius: 10px; }
        .an-brow:hover { background: #fbf3f6; }
        .an-brow .name { font-weight: 700; }
        .an-btrack { position: relative; height: 12px; background: #f3e9ed; border-radius: 6px; }
        .an-btrack .s { position: absolute; left: 0; top: 0; bottom: 0; background: #7a1538; border-radius: 6px; }
        .an-btrack .dm { position: absolute; top: -4px; bottom: -4px; width: 3px; background: #d98a00; border-radius: 2px; }
        .an-brow .st { font-size: 12.5px; font-weight: 700; text-align: right; }
        .an-brow .st.short { color: #b3261e; } .an-brow .st.ok { color: #17653c; }
        .an-mtable { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 12px; }
        .an-mtable th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; padding: 8px; border-bottom: 2px solid #f0e4e8; }
        .an-mtable td { padding: 9px 8px; border-bottom: 1px solid #f6eef1; }
        .an-mtable tr:hover td { background: #fbf3f6; }
        .an-rate { display: inline-block; min-width: 46px; text-align: center; padding: 3px 9px; border-radius: 14px; font-weight: 700; font-size: 12.5px; background: #dcf3e6; color: #17653c; }
        .an-rate.mid { background: #fff3d6; color: #8a5a00; } .an-rate.low { background: #fde7e4; color: #b3261e; }
        .an-badge { font-size: 11px; font-weight: 700; background: #f4e6ec; color: #7a1538; padding: 3px 10px; border-radius: 12px; vertical-align: middle; margin-left: 6px; }
        .an-ai-summary { background: linear-gradient(135deg, #fbf3f6, #fff); border: 1px solid #f0e4e8; border-radius: 14px; padding: 14px 18px; font-size: 14.5px; color: #2b2f38; }
        .an-ai-summary p { margin: 4px 0; }
        .an-ask { display: flex; gap: 8px; margin-top: 14px; }
        .an-ask input[type=text] { flex: 1; padding: 11px 14px; border: 1px solid #d9c9d0; border-radius: 24px; font-size: 14px; }
        .an-ask button { background: #7a1538; color: #fff; border: 0; padding: 0 22px; border-radius: 24px; font-weight: 700; cursor: pointer; }
        .an-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .an-chips a { font-size: 12.5px; text-decoration: none; color: #7a1538; background: #f4e6ec; padding: 5px 12px; border-radius: 16px; font-weight: 600; }
        .an-chips a:hover { background: #ecd3de; }
        .an-answer { margin-top: 12px; padding: 12px 16px; background: #eef6ff; border-left: 4px solid #3b6fb6; border-radius: 10px; font-size: 14.5px; }
        .an-risk { display: inline-block; font-size: 12.5px; font-weight: 700; padding: 3px 11px; border-radius: 14px; white-space: nowrap; }
        .an-risk.critical { background: #fde7e4; color: #b3261e; } .an-risk.watch { background: #fff3d6; color: #8a5a00; } .an-risk.safe { background: #dcf3e6; color: #17653c; }
        .an-fc a { color: #7a1538; font-weight: 700; text-decoration: none; } .an-fc a:hover { text-decoration: underline; }
        .an-note { font-size: 12.5px; color: #6b7280; margin-top: 12px; line-height: 1.5; }
        .an-outreach { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 14px; }
        .an-ocard { background: #faf6f8; border: 1px solid #f0e4e8; border-radius: 14px; padding: 14px 16px; scroll-margin-top: 20px; }
        .an-ocard:target { box-shadow: 0 0 0 3px #d9aebb; }
        .an-ohead { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; font-size: 18px; }
        .an-donor { display: flex; justify-content: space-between; gap: 8px; text-decoration: none; color: #1d2733; font-size: 13.5px; padding: 7px 9px; margin-top: 6px; background: #fff; border-radius: 9px; border: 1px solid #f0e4e8; }
        .an-donor:hover { border-color: #d9aebb; }
        .an-donor em { font-style: normal; font-size: 11.5px; background: #f4e6ec; color: #7a1538; border-radius: 8px; padding: 1px 7px; margin-left: 4px; font-weight: 700; }
        .an-more { display: inline-block; margin-top: 10px; font-size: 13px; font-weight: 700; color: #7a1538; text-decoration: none; }
        @media (max-width: 768px) { .an-months { gap: 6px; } .an-brow { grid-template-columns: 38px 1fr 90px; } }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header">
            <img decoding="async" src="assets/img/logo.jpg" alt="Lifeline Blood Bank Logo" border="0">
            <h2>Lifeline Blood Bank</h2>
            <p>Blood Bank Admin</p>
        </div>

        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
            <li><a href="admin_donors.php">👥 Donors</a></li>
            <li><a href="admin_requests.php">🩸 Blood Requests</a></li>
            <li><a href="admin_appointments.php">📅 Appointments</a></li>
            <li><a href="admin_camps.php">🏥 Camps</a></li>
            <li><a href="admin_inventory.php">📦 Inventory</a></li>
            <li><a href="admin_blood_ledger.php">🧾 Blood Ledger</a></li>
            <li><a href="admin_analytics.php" class="active">📈 Analytics</a></li>
            <li><a href="admin_change_password.php">🔑 Change Password</a></li>
        </ul>

        <div class="sidebar-logout">
            <a href="admin_blood_ledger.php" class="quick-action">+ New Donation</a>
            <form method="post" style="display:inline;">
                <button type="submit" name="logout" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

    <div class="main-content">

        <div class="dx-hero">
            <div>
                <h1>📈 Analytics &amp; Insights</h1>
                <p>Real activity from your donors, appointments, requests and stock &middot; Updated <?php echo date('d M, h:i A'); ?></p>
            </div>
            <div class="dx-hero-actions">
                <div class="an-tabs" role="tablist" aria-label="Date range">
                    <?php foreach ([14, 30, 90] as $r) : ?>
                        <a href="?range=<?php echo $r; ?>" class="<?php echo $range === $r ? 'on' : ''; ?>">Last <?php echo $r; ?> days</a>
                    <?php endforeach; ?>
                </div>
                <a class="dx-btn" href="?export=monthly">⬇ Export monthly CSV</a>
            </div>
        </div>

        <div class="an-insights">
            <?php foreach ($insights as [$ic, $txt]) : ?>
                <div class="an-insight"><span class="ic"><?php echo $ic; ?></span><span><?php echo $txt; ?></span></div>
            <?php endforeach; ?>
        </div>

        <div class="dx-kpis">
            <a class="dx-kpi" href="admin_appointments.php">
                <div class="top"><span class="label">Appointments</span><span class="icon">📅</span></div>
                <div class="value"><?php echo (int)$apptThis; ?></div>
                <div class="an-delta <?php echo $apptDeltaClass; ?>"><?php echo dx($apptDeltaText); ?></div>
                <div class="go">Open appointments &rarr;</div>
            </a>
            <a class="dx-kpi" href="admin_appointments.php?status=Completed">
                <div class="top"><span class="label">Completion rate</span><span class="icon">✅</span></div>
                <div class="value"><?php echo (int)$completionRate; ?>%</div>
                <div class="sub"><?php echo (int)$completedThis; ?> of <?php echo (int)$apptThis; ?> completed</div>
                <div class="go">See completed &rarr;</div>
            </a>
            <a class="dx-kpi" href="admin_donors.php">
                <div class="top"><span class="label">New donors</span><span class="icon">👥</span></div>
                <div class="value"><?php echo (int)$donorsThis; ?></div>
                <div class="an-delta <?php echo $donorDeltaClass; ?>"><?php echo dx($donorDeltaText); ?></div>
                <div class="go">Open donors &rarr;</div>
            </a>
            <a class="dx-kpi" href="admin_requests.php">
                <div class="top"><span class="label">Fulfilment rate</span><span class="icon">🩸</span></div>
                <div class="value"><?php echo (int)$fulfilRate; ?>%</div>
                <div class="sub"><?php echo (int)$reqStatus['Pending']; ?> request(s) still pending</div>
                <div class="go">Open requests &rarr;</div>
            </a>
            <a class="dx-kpi" href="admin_inventory.php?order=low-first">
                <div class="top"><span class="label">Groups short</span><span class="icon">⚠️</span></div>
                <div class="value"><?php echo (int)$shortGroups; ?></div>
                <div class="sub">of 8 groups below pending demand</div>
                <div class="go">Check stock &rarr;</div>
            </a>
            <a class="dx-kpi" href="admin_blood_ledger.php">
                <div class="top"><span class="label">Ledger flow</span><span class="icon">🧾</span></div>
                <div class="value"><?php echo (int)$ledgerIn; ?><small style="font-size:16px;color:#6b7280;"> in</small> / <?php echo (int)$ledgerOut; ?><small style="font-size:16px;color:#6b7280;"> out</small></div>
                <div class="sub">units, last <?php echo $range; ?> days</div>
                <div class="go">Open ledger &rarr;</div>
            </a>
        </div>


        <!-- ===== SMART FORECAST ===== -->
        <div class="dx-panel wide an-ai" style="margin-bottom:20px;">
            <div class="dx-head"><h3>🤖 Smart Forecast <span class="an-badge">worked out from your data</span></h3></div>
            <div class="an-ai-summary">
                <?php foreach ($aiLines as $line) : ?><p>&bull; <?php echo dx($line); ?></p><?php endforeach; ?>
            </div>
            <form class="an-ask" method="get" action="admin_analytics.php#ask">
                <input type="hidden" name="range" value="<?php echo $range; ?>">
                <input type="text" name="ask" value="<?php echo dx($askQ); ?>" placeholder="Ask a question, e.g. Which blood group is lowest?" maxlength="120" aria-label="Ask about your data">
                <button type="submit">Ask</button>
            </form>
            <div class="an-chips">
                <?php foreach (['Which blood group is lowest?', 'Which group is most requested?', 'Who should we contact?', 'What is expiring soon?'] as $sq) : ?>
                    <a href="?range=<?php echo $range; ?>&amp;ask=<?php echo urlencode($sq); ?>#ask"><?php echo dx($sq); ?></a>
                <?php endforeach; ?>
            </div>
            <?php if ($askA !== '') : ?><div class="an-answer" id="ask"><b>Q:</b> <?php echo dx($askQ); ?><br><b>A:</b> <?php echo dx($askA); ?></div><?php endif; ?>

            <div style="overflow-x:auto;margin-top:16px;">
            <table class="an-mtable an-fc">
                <thead><tr><th>Group</th><th>Usable stock</th><th>Demand / day</th><th>Expected in 14 days</th><th>Days of cover</th><th>Trend</th><th>Risk</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($forecast as $f) : ?>
                    <tr>
                        <td><b><?php echo dx($f['group']); ?></b></td>
                        <td><?php echo (int)$f['supply']; ?></td>
                        <td><?php echo $f['daily'] > 0 ? round($f['daily'], 2) : '0'; ?></td>
                        <td><?php echo (int)$f['need14']; ?></td>
                        <td><?php echo $f['cover'] === null ? 'no demand' : ($f['cover'] > 365 ? 'over a year' : (int)round($f['cover']) . ' days'); ?></td>
                        <td><?php echo $f['trend'] === 'up' ? '<span class="an-delta down">▲ rising</span>' : ($f['trend'] === 'down' ? '<span class="an-delta up">▼ falling</span>' : '<span class="an-delta flat">▬ steady</span>'); ?></td>
                        <td><span class="an-risk <?php echo $f['risk']; ?>"><?php echo $f['risk'] === 'critical' ? '⚠ Critical' : ($f['risk'] === 'watch' ? '◔ Watch' : '✓ Safe'); ?></span></td>
                        <td><?php echo $f['risk'] === 'safe' ? '<a href="admin_inventory.php?group=' . urlencode($f['group']) . '">View stock</a>' : '<a href="#outreach-' . dx(str_replace(['+', '-'], ['p', 'm'], $f['group'])) . '">Find donors</a>'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <p class="an-note">How it works: demand per day = units requested over the last <?php echo $lookback; ?> days (cancelled requests ignored) &divide; <?php echo (int)$observedDays; ?> observed days. Days of cover = usable stock &divide; daily demand. <b>Critical</b>: stock below pending requests, or under 7 days of cover. <b>Watch</b>: under 21 days, or below the 14-day forecast. With few requests the forecast is rough, so treat it as guidance.</p>
        </div>

        <?php if ($atRisk) : ?>
        <div class="dx-panel wide" style="margin-bottom:20px;">
            <div class="dx-head"><h3>📣 Smart donor outreach</h3><a href="admin_donors.php?status=<?php echo urlencode('Eligible to Donate'); ?>">All eligible donors &rarr;</a></div>
            <p class="an-note" style="margin:0 0 12px;">For each group at risk: donors who are eligible today (last donation over 90 days ago) and whose blood group is compatible.</p>
            <div class="an-outreach">
                <?php foreach (array_slice($atRisk, 0, 4) as $f) : ?>
                <div class="an-ocard" id="outreach-<?php echo dx(str_replace(['+', '-'], ['p', 'm'], $f['group'])); ?>">
                    <div class="an-ohead"><b><?php echo dx($f['group']); ?></b><span class="an-risk <?php echo $f['risk']; ?>"><?php echo $f['risk'] === 'critical' ? '⚠ Critical' : '◔ Watch'; ?></span></div>
                    <small><?php echo count($f['helpers']); ?> compatible eligible donor<?php echo count($f['helpers']) == 1 ? '' : 's'; ?></small>
                    <?php foreach (array_slice($f['helpers'], 0, 3) as $h) : ?>
                        <a class="an-donor" href="admin_donors.php?search=<?php echo urlencode($h['email'] ?? ''); ?>"><span><?php echo dx($h['username'] ?? ''); ?> <em><?php echo dx($h['bloodgroup'] ?? ''); ?></em></span><span><?php echo dx($h['phone'] ?? ''); ?></span></a>
                    <?php endforeach; ?>
                    <?php if (!$f['helpers']) : ?><div class="dx-empty" style="padding:6px 0;">No eligible donors yet. Consider running a camp.</div><?php endif; ?>
                    <a class="an-more" href="admin_donors.php?bloodgroup=<?php echo urlencode($f['group']); ?>&amp;status=<?php echo urlencode('Eligible to Donate'); ?>">See all <?php echo dx($f['group']); ?> donors &rarr;</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="dx-grid">
            <!-- TREND -->
            <div class="dx-panel wide">
                <div class="dx-head"><h3>Appointments per day &middot; last <?php echo $range; ?> days</h3><a href="admin_appointments.php">All appointments &rarr;</a></div>
                <?php if ($totalInRange === 0) : ?>
                    <div class="dx-empty">No appointments in this period. Try a longer range.</div>
                <?php else : ?>
                <svg class="an-chart" viewBox="0 0 <?php echo $W; ?> <?php echo $H; ?>" role="img" aria-label="Line chart of appointments per day">
                    <?php for ($g = 0; $g <= $yMax; $g += $step) : $gy = round($py($g), 1); ?>
                        <line class="grid" x1="<?php echo $L; ?>" x2="<?php echo $W - $R; ?>" y1="<?php echo $gy; ?>" y2="<?php echo $gy; ?>"></line>
                        <text x="<?php echo $L - 8; ?>" y="<?php echo $gy + 4; ?>" text-anchor="end"><?php echo $g; ?></text>
                    <?php endfor; ?>
                    <polygon points="<?php echo $areaPts; ?>" fill="#7a1538" fill-opacity="0.09"></polygon>
                    <polyline points="<?php echo implode(' ', $linePts); ?>" fill="none" stroke="#7a1538" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></polyline>
                    <?php foreach ($vals as $i => $v) : $cx = round($px($i), 1); $cy = round($py($v), 1); ?>
                        <g class="pt">
                            <title><?php echo dx(date('D, d M', strtotime($labels[$i])) . ': ' . $v . ' appointment' . ($v == 1 ? '' : 's')); ?></title>
                            <line class="vline" x1="<?php echo $cx; ?>" x2="<?php echo $cx; ?>" y1="<?php echo $T; ?>" y2="<?php echo $H - $B; ?>"></line>
                            <circle class="dot" cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="5"></circle>
                            <rect class="hit" x="<?php echo $cx - ($W - $L - $R) / max(1, $n - 1) / 2; ?>" y="<?php echo $T; ?>" width="<?php echo ($W - $L - $R) / max(1, $n - 1); ?>" height="<?php echo $H - $T - $B; ?>"></rect>
                        </g>
                        <?php if ($i % $labelEvery === 0 || $i === $n - 1) : ?>
                            <text x="<?php echo $cx; ?>" y="<?php echo $H - 12; ?>" text-anchor="middle"><?php echo dx(date('d M', strtotime($labels[$i]))); ?></text>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </svg>
                <?php endif; ?>
                <details class="dx-table"><summary>View as table</summary>
                    <table><tr><th>Date</th><th>Appointments</th></tr>
                    <?php foreach (array_reverse($daily, true) as $dd => $cc) : if ($cc === 0) { continue; } ?>
                        <tr><td><?php echo dx(date('D, d M Y', strtotime($dd))); ?></td><td><?php echo (int)$cc; ?></td></tr>
                    <?php endforeach; ?></table>
                </details>
            </div>

            <!-- SUPPLY VS DEMAND -->
            <div class="dx-panel">
                <div class="dx-head"><h3>Supply vs demand by blood group</h3><a href="admin_inventory.php">Inventory &rarr;</a></div>
                <div class="an-balance">
                    <?php foreach ($balance as $b) : $short = $b['gap'] < 0; ?>
                        <a class="an-brow" href="admin_inventory.php?group=<?php echo urlencode($b['group']); ?>" title="<?php echo dx($b['group'] . ': ' . $b['supply'] . ' in stock, ' . $b['demand'] . ' needed by pending requests'); ?>">
                            <span class="name"><?php echo dx($b['group']); ?></span>
                            <span class="an-btrack">
                                <span class="s" style="width:<?php echo (int)round($b['supply'] / $supplyPeak * 100); ?>%;"></span>
                                <?php if ($b['demand'] > 0) : ?><span class="dm" style="left:calc(<?php echo (int)round($b['demand'] / $supplyPeak * 100); ?>% - 1px);"></span><?php endif; ?>
                            </span>
                            <span class="st <?php echo $short ? 'short' : 'ok'; ?>"><?php echo $short ? '⚠ Short ' . abs($b['gap']) : '✓ ' . $b['supply'] . ' units'; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="dx-legend"><span><i style="background:#7a1538"></i>Usable stock</span><span><i style="background:#d98a00"></i>Needed by pending requests</span></div>
            </div>

            <!-- MONTHLY -->
            <div class="dx-panel">
                <div class="dx-head"><h3>Monthly appointments</h3><a href="?export=monthly">Download CSV &rarr;</a></div>
                <div class="an-months">
                    <?php foreach ($months as $m) : ?>
                        <div class="an-col" title="<?php echo dx($m['label'] . ': ' . $m['completed'] . ' completed, ' . $m['scheduled'] . ' scheduled, ' . $m['cancelled'] . ' cancelled'); ?>">
                            <b><?php echo (int)$m['total']; ?></b>
                            <div class="an-stack" style="height:<?php echo $m['total'] ? max(8, (int)round($m['total'] / $monthPeak * 150)) : 0; ?>px;">
                                <?php foreach ([['completed', '#1b7f4b'], ['scheduled', '#3b6fb6'], ['cancelled', '#9aa1ab']] as [$k, $col]) :
                                    if ($m[$k] < 1) { continue; } ?>
                                    <i style="background:<?php echo $col; ?>;flex:<?php echo (int)$m[$k]; ?>;"></i>
                                <?php endforeach; ?>
                            </div>
                            <small><?php echo dx(date('M', strtotime($m['label']))); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="dx-legend"><span><i style="background:#1b7f4b"></i>Completed</span><span><i style="background:#3b6fb6"></i>Scheduled</span><span><i style="background:#9aa1ab"></i>Cancelled</span></div>
            </div>

            <!-- STATUS BREAKDOWNS -->
            <div class="dx-panel">
                <div class="dx-head"><h3>Appointment status</h3><a href="admin_appointments.php">Manage &rarr;</a></div>
                <?php $aptTotal = max(1, array_sum($aptStatus)); ?>
                <div class="dx-seg" role="img" aria-label="Appointments by status">
                    <?php foreach ($aptStatus as $st => $c) : if ($c < 1) { continue; } ?>
                        <a href="admin_appointments.php?status=<?php echo urlencode($st); ?>" title="<?php echo dx($st . ': ' . $c); ?>" style="width:<?php echo round($c / $aptTotal * 100, 2); ?>%;background:<?php echo $aptStatusColors[$st]; ?>;"></a>
                    <?php endforeach; ?>
                </div>
                <div class="dx-stat-rows">
                    <?php foreach ($aptStatus as $st => $c) : ?>
                        <a href="admin_appointments.php?status=<?php echo urlencode($st); ?>"><span><i style="display:inline-block;width:11px;height:11px;border-radius:3px;margin-right:8px;background:<?php echo $aptStatusColors[$st]; ?>"></i><?php echo dx($st); ?></span><b><?php echo (int)$c; ?></b></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dx-panel">
                <div class="dx-head"><h3>Request status</h3><a href="admin_requests.php">Manage &rarr;</a></div>
                <div class="dx-seg" role="img" aria-label="Requests by status">
                    <?php foreach ($reqStatus as $st => $c) : if ($c < 1) { continue; } ?>
                        <a href="admin_requests.php?status=<?php echo urlencode($st); ?>" title="<?php echo dx($st . ': ' . $c); ?>" style="width:<?php echo round($c / max(1, $reqTotal) * 100, 2); ?>%;background:<?php echo $reqStatusColors[$st]; ?>;"></a>
                    <?php endforeach; ?>
                </div>
                <div class="dx-stat-rows">
                    <?php foreach ($reqStatus as $st => $c) : ?>
                        <a href="admin_requests.php?status=<?php echo urlencode($st); ?>"><span><i style="display:inline-block;width:11px;height:11px;border-radius:3px;margin-right:8px;background:<?php echo $reqStatusColors[$st]; ?>"></i><?php echo dx($st); ?></span><b><?php echo (int)$c; ?></b></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- LOCATIONS -->
            <div class="dx-panel">
                <div class="dx-head"><h3>Top donation locations</h3><a href="admin_camps.php">Camps &rarr;</a></div>
                <div class="dx-bars">
                    <?php foreach ($byLocation as $loc => $c) : ?>
                        <a class="dx-bar" style="grid-template-columns:1fr 1.3fr 36px;" href="admin_appointments.php?location=<?php echo urlencode($loc); ?>" title="<?php echo dx($loc . ': ' . $c); ?>">
                            <span class="name" style="font-size:13px;font-weight:600;"><?php echo dx($loc); ?></span>
                            <span class="track"><span class="fill" style="display:block;width:<?php echo (int)round($c / $locPeak * 100); ?>%;"></span></span>
                            <span class="num"><?php echo (int)$c; ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$byLocation) : ?><div class="dx-empty">No location data yet.</div><?php endif; ?>
                </div>
            </div>

            <!-- TIME SLOTS -->
            <div class="dx-panel">
                <div class="dx-head"><h3>Popular time slots</h3></div>
                <div class="dx-bars">
                    <?php foreach ($bySlot as $slot => $c) : ?>
                        <div class="dx-bar" style="grid-template-columns:1fr 1.3fr 36px;" title="<?php echo dx($slot . ': ' . $c); ?>">
                            <span class="name" style="font-size:13px;font-weight:600;"><?php echo dx($slot); ?></span>
                            <span class="track"><span class="fill" style="display:block;width:<?php echo (int)round($c / $slotPeak * 100); ?>%;"></span></span>
                            <span class="num"><?php echo (int)$c; ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$bySlot) : ?><div class="dx-empty">No time slot data yet.</div><?php endif; ?>
                </div>
            </div>

            <!-- MONTHLY TABLE -->
            <div class="dx-panel wide">
                <div class="dx-head"><h3>Monthly statistics &middot; last 6 months</h3><a href="?export=monthly">⬇ Download CSV</a></div>
                <div style="overflow-x:auto;">
                <table class="an-mtable">
                    <thead><tr><th>Month</th><th>Total</th><th>Completed</th><th>Scheduled</th><th>Cancelled</th><th>Completion rate</th><th>New donors</th></tr></thead>
                    <tbody>
                        <?php foreach (array_reverse($months) as $m) :
                            $rate = $m['total'] ? (int)round($m['completed'] / $m['total'] * 100) : 0;
                            $rc = $rate >= 70 ? '' : ($rate >= 40 ? 'mid' : 'low'); ?>
                            <tr>
                                <td><b><?php echo dx($m['label']); ?></b></td>
                                <td><?php echo (int)$m['total']; ?></td>
                                <td><?php echo (int)$m['completed']; ?></td>
                                <td><?php echo (int)$m['scheduled']; ?></td>
                                <td><?php echo (int)$m['cancelled']; ?></td>
                                <td><?php echo $m['total'] ? '<span class="an-rate ' . $rc . '">' . $rate . '%</span>' : '-'; ?></td>
                                <td><?php echo (int)$m['donors']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
