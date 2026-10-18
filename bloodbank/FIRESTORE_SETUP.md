# Blood Bank — Firestore Setup

Your MySQL database is now replaced with Firestore. All 16 pages talk to
Firestore through `firebase/Firestore.php` (a small REST client — no
Composer/SDK needed, just PHP's built-in `curl` and `openssl`).

## 1. Get a service account key
- Firebase Console → your project → ⚙️ Project Settings → **Service Accounts**
- Click **Generate new private key** → downloads a `.json` file
- Rename it `service-account.json` and put it **one folder above your web root**
  (never inside a publicly-servable folder — it's basically a master password).
  Example layout:
  ```
  /home/you/
    service-account.json      <- here
    public_html/               <- your web root (this project's files)
      db_connect.php
      firebase/
      ...
  ```
  If your hosting only gives you one folder, put it in a folder with an
  `.htaccess` that denies all access, and update the path in `db_connect.php`.

## 2. Set Firestore security rules
Since only this PHP server talks to Firestore (via the service account,
not a client-side SDK), lock the database down completely in the Firebase
Console → Firestore → Rules:
```
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /{document=**} {
      allow read, write: if false;
    }
  }
}
```

## 3. Seed sample data (optional, once)
```
php firebase/seed_firestore.php
```
This creates the same demo rows your old `database_setup.sql` did:
donors, blood_requests, donation_appointments, blood_stock, inventory_transactions.

## 4. Collections created
| Old MySQL table | Firestore collection |
|---|---|
| donors | donors |
| blood_requests | blood_requests |
| donation_appointments | donation_appointments |
| blood_stock | blood_stock |
| inventory_transactions | inventory_transactions |
| blood_ledger | blood_ledger |
| contact_messages | contact_messages |

## What changed under the hood
- **No more `id` auto-increment integers.** Firestore document IDs are
  random strings. Every page that used to reference `$row['id']` as an
  int now uses the string ID (`$row['_id']` from Firestore, aliased to
  `$row['id']` for template compatibility).
- **No GROUP BY / COUNT / SUM.** Firestore doesn't do server-side
  aggregation the way SQL does, so dashboard/analytics pages
  (`admin_dashboard.php`, `admin_analytics.php`, `admin_camps.php`,
  `inventory.php` chart) now pull the relevant collection once and
  aggregate in PHP. Fine at this project's data scale.
- **No LIKE / OR search.** Free-text search boxes (donor search, request
  search, ledger search) now filter in PHP after fetching.
- **Composite filters + sort** (e.g. `find.php`) may ask Firestore to
  create a composite index the first time you run them — Firestore's
  error message includes a direct link to create it with one click.
- **Inventory updates aren't a true atomic transaction** anymore (Firestore
  REST doesn't give a lightweight SQL-style transaction here) — it does
  the stock-check-then-write in sequence instead. Fine for normal traffic;
  flag it if you expect heavy concurrent inventory edits.

## If something errors
Check your PHP error log first — every Firestore call is wrapped in
try/catch and logs the real error via `error_log()`. The most common
first-run issues are: wrong `service-account.json` path, Firestore not
yet created in "Native mode" for the project, or a missing composite
index (Firestore tells you exactly which one to create).
