<div align="center">

# 🩸 Lifeline Blood Bank

### Donate blood, find blood, save lives.

A blood bank and donor management website built with **PHP** and **Google Firestore**.
Donors register and book appointments, patients request blood in an emergency, and staff manage stock, camps and requests from an admin panel.

![PHP](https://img.shields.io/badge/PHP-8-777bb4?logo=php&logoColor=fff)
![Firestore](https://img.shields.io/badge/Firestore-database-ffca28?logo=firebase&logoColor=000)
![PHPMailer](https://img.shields.io/badge/PHPMailer-email-2e7d32)
![XAMPP](https://img.shields.io/badge/XAMPP-ready-fb7a24?logo=xampp&logoColor=fff)

</div>

---

## ✨ Features

### For donors and the public
| | |
|---|---|
| 🩸 **Donor registration** | Sign up with blood group, age and contact details. |
| 📅 **Appointments** | Book a donation slot at a blood bank. |
| 🔎 **Find blood** | Search blood availability by group and component. |
| 🚨 **Emergency requests** | Submit an urgent blood request; staff are alerted by email. |
| 🏕️ **Donation camps** | Browse upcoming blood donation camps. |
| 👤 **Donor dashboard** | Donor login with a personal profile page. |
| 💬 **Chat assistant** | Help bot on the home page. |
| ℹ️ **About & Contact** | About the blood bank and a contact form. |

### For staff (admin panel)
| | |
|---|---|
| 📊 **Dashboard & analytics** | Overview of donors, requests and stock. |
| 🧑‍🤝‍🧑 **Donors** | View and manage registered donors. |
| 📝 **Requests & appointments** | Review, approve and track blood requests and bookings. |
| 📦 **Inventory** | Stock by blood group and component (whole blood, packed RBC, plasma, platelets) with expiry dates. |
| 📒 **Blood ledger** | Every stock movement recorded for a daily stock report. |
| 🏕️ **Camps** | Create and manage donation camps. |
| 🔐 **Secure login** | Admin login and change-password. |
| ✉️ **Email notifications** | Confirmation and alert emails via PHPMailer (Gmail SMTP). |

## 🧱 Tech stack

- **Backend:** PHP (plain PHP, no framework)
- **Database:** Google **Cloud Firestore**, through a small built-in REST client (`bloodbank/firebase/Firestore.php`) that needs only PHP's `curl` and `openssl`
- **Email:** [PHPMailer](https://github.com/PHPMailer/PHPMailer) (installed with Composer)
- **Local server:** XAMPP (Apache + PHP)

## 📁 Project structure

```
bloodbank/
  index.php                 home page
  donate.php, donor*.php    donor registration, login and dashboard
  request.php, find.php     emergency requests and blood search
  camp.php, camps_helper.php  donation camps
  inventory*.php            stock and daily report
  admin_*.php               admin panel (dashboard, donors, requests, camps, ledger, analytics…)
  mailer.php, mail_config.php  email sending
  db_connect.php            Firestore connection
  firebase/                 Firestore client, auth and seed/backup scripts
  assets/img/               images
  FIRESTORE_SETUP.md        detailed Firestore setup guide
service-account.example.json  template for your Firebase key
```

## 🚀 Getting started

### 1. Requirements
- PHP 8+ with the `curl` and `openssl` extensions (XAMPP includes them)
- [Composer](https://getcomposer.org/) (optional; `vendor/` is already included)
- A Google account with a Firebase project

### 2. Get the code
```bash
git clone https://github.com/snehasanu64/lifeline-bloodbank.git
```
Copy the `bloodbank` folder into your XAMPP `htdocs` folder.

### 3. Connect Firestore
1. In the Firebase Console, create a project and enable **Cloud Firestore**.
2. Go to **Project settings → Service accounts → Generate new private key**.
3. Save the file as `service-account.json` **one folder above the web folder** (see `service-account.example.json` for the shape). Never commit it.
4. Lock the database down in Firestore rules, since only this server talks to it:
   ```
   rules_version = '2';
   service cloud.firestore {
     match /databases/{database}/documents {
       match /{document=**} { allow read, write: if false; }
     }
   }
   ```

Full details are in [`bloodbank/FIRESTORE_SETUP.md`](bloodbank/FIRESTORE_SETUP.md).

### 4. Load sample data (optional)
```bash
php bloodbank/firebase/seed_firestore.php        # donors, requests, appointments, stock
php bloodbank/firebase/seed_blood_stock.php      # blood stock
php bloodbank/firebase/seed_blood_ledger.php     # stock ledger
php bloodbank/firebase/seed_dummy_admin.php      # demo data for the admin panel
```

### 5. Set up email (optional)
Copy `bloodbank/mail_config.local.example.php` to `bloodbank/mail_config.local.php` (git-ignored) and fill in your Gmail address and a Gmail **App password** (Google Account → Security → 2-Step Verification → App passwords). Until it is filled in, emails are skipped and the site still works.

### 6. Run it
Start Apache in XAMPP and open `http://localhost/bloodbank/`.

## ☁️ Deploy online (free, Render)

Vercel does not run PHP, so use [Render](https://render.com) (free web service, Docker):

1. Sign in to Render with GitHub → **New → Blueprint** → pick this repo (it reads `render.yaml`).
2. Fill in the environment variables:
   - `FIREBASE_SERVICE_ACCOUNT_JSON`: paste the whole contents of your `service-account.json`
   - `MAIL_USERNAME`, `MAIL_PASSWORD` (Gmail app password), `MAIL_ADMIN_EMAIL`
3. Deploy. The site is served from the `bloodbank/` folder. On the free plan it sleeps after inactivity, so the first visit can take about a minute.

## 🗄️ Firestore collections

`donors` · `blood_requests` · `donation_appointments` · `blood_stock` · `inventory_transactions` · `blood_ledger` · `contact_messages`

A legacy MySQL schema is kept in `bloodbank/database_setup.sql` for reference; the app itself now uses Firestore.

## 🔒 Security notes

- Keep `service-account.json`, SMTP passwords and the cron key **out of Git** (`.gitignore` already covers the Firebase key and `.env`).
- Use a Gmail **App password**, never your real password, and rotate it if it was ever published.
- The included `.htaccess` blocks web access to `.json`, `.env`, `.log` and `.sql` files.
- Change the default admin password after seeding demo data.

## 🤝 Contributing

Ideas and pull requests are welcome. Please open an issue first for big changes.

---

<div align="center">
Made with ❤️ to help people find blood when they need it most. ⭐ Star the repo if it helps you!
</div>
