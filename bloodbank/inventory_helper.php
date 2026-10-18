<?php
/**
 * Inventory + ledger helpers (shared by the admin pages and find.php).
 *
 * 1. refresh_stock_dates()  - keeps blood_stock dates current automatically.
 * 2. ledger_*()             - keeps blood_ledger up to date automatically.
 */

/**
 * Set to false to switch the automatic date refresh off (for example once
 * you start entering REAL stock, where an expired bag must stay expired).
 */
if (!defined('AUTO_REFRESH_STOCK_DATES')) {
    define('AUTO_REFRESH_STOCK_DATES', true);
}

/** Shelf life in days for each component. */
function stock_shelf_days(string $component): int
{
    $map = ['Whole Blood' => 35, 'RBC' => 42, 'Plasma' => 180, 'Platelets' => 7];
    return $map[$component] ?? 35;
}

/**
 * Once a day: every batch whose expiry date has passed is replaced by a
 * freshly collected batch (new collection_date within the last 0-3 days,
 * expiry_date = collection + shelf life). Units stay the same.
 * Returns how many batches were refreshed (0 if it already ran today).
 */
function refresh_stock_dates($db): int
{
    if (!AUTO_REFRESH_STOCK_DATES) {
        return 0;
    }
    $today = date('Y-m-d');
    $refreshed = 0;
    try {
        $state = $db->getById('settings', 'stock_refresh');
        if (($state['last_run'] ?? '') === $today) {
            return 0;
        }
        foreach ($db->getAll('blood_stock') as $row) {
            $expiry = $row['expiry_date'] ?? '';
            if ($expiry === '' || $expiry >= $today) {
                continue;
            }
            $age = crc32($row['_id']) % 4; // stable pseudo-random 0-3 days old
            $collected = date('Y-m-d', strtotime("-{$age} days"));
            $shelf = stock_shelf_days($row['component'] ?? '');
            $db->update('blood_stock', $row['_id'], [
                'collection_date' => $collected,
                'expiry_date' => date('Y-m-d', strtotime("$collected +{$shelf} days")),
                'last_updated' => date('Y-m-d H:i:s'),
            ]);
            $refreshed++;
        }
        $db->set('settings', 'stock_refresh', ['last_run' => $today, 'refreshed' => $refreshed]);
    } catch (Exception $e) {
        error_log("Stock date refresh failed: " . $e->getMessage());
    }
    return $refreshed;
}

/**
 * Add a ledger entry only if no entry with this source_id exists yet,
 * so changing a status back and forth never creates duplicates.
 */
function ledger_record_once($db, string $sourceId, array $entry): bool
{
    try {
        if ($db->getOneWhere('blood_ledger', 'source_id', $sourceId)) {
            return false;
        }
        $entry['source_id'] = $sourceId;
        $entry += ['entry_date' => date('Y-m-d'), 'created_at' => date('Y-m-d H:i:s')];
        $db->add('blood_ledger', $entry);
        return true;
    } catch (Exception $e) {
        error_log("Ledger entry failed: " . $e->getMessage());
        return false;
    }
}

/** A fulfilled blood request = blood GIVEN out. */
function ledger_from_request($db, array $req): bool
{
    $patient = trim(($req['patient_name'] ?? '') !== '' ? $req['patient_name'] : ($req['requester_name'] ?? 'Patient'));
    return ledger_record_once($db, 'request_' . $req['_id'], [
        'entry_date' => date('Y-m-d'), // the day it was marked Fulfilled
        'transaction_type' => 'GIVEN',
        'from_patient_name' => '',
        'to_patient_name' => $patient,
        'blood_group' => $req['blood_group'] ?? '',
        'component' => 'Whole Blood',
        'units' => max(1, (int)($req['units_needed'] ?? 1)),
        'hospital_name' => $req['hospital_name'] ?? '',
        'remarks' => 'Auto-recorded: blood request marked Fulfilled',
    ]);
}

/** A completed donation appointment = blood TAKEN in (1 unit of whole blood). */
function ledger_from_appointment($db, array $apt): bool
{
    return ledger_record_once($db, 'appointment_' . $apt['_id'], [
        'entry_date' => $apt['appointment_date'] ?? date('Y-m-d'),
        'transaction_type' => 'TAKEN',
        'from_patient_name' => $apt['donor_name'] ?? 'Donor',
        'to_patient_name' => '',
        'blood_group' => $apt['bloodgroup'] ?? '',
        'component' => 'Whole Blood',
        'units' => 1,
        'hospital_name' => $apt['location'] ?? '',
        'remarks' => 'Auto-recorded: donation appointment marked Completed',
    ]);
}
