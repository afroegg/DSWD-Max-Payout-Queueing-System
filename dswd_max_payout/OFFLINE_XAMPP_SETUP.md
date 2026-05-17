# Offline XAMPP Setup Guide

This guide explains how to run the whole DSWD Max Payout Queueing and Monitoring System offline using XAMPP.

## 1. Install XAMPP

Install XAMPP with Apache, MySQL/MariaDB, and phpMyAdmin.

Start:

- Apache
- MySQL

## 2. Copy the project folder

Copy the full project folder into XAMPP htdocs.

Recommended path:

```text
C:\xampp\htdocs\dswd_max_payout
```

Your files should look like:

```text
C:\xampp\htdocs\dswd_max_payout\index.php
C:\xampp\htdocs\dswd_max_payout\config\db.php
C:\xampp\htdocs\dswd_max_payout\staff\verifier.php
C:\xampp\htdocs\dswd_max_payout\kiosk\index.php
```

## 3. Create the local database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Create a database named:

```text
dswd_max_payout
```

Import your exported SQL file from Railway/online database.

This SQL should include all tables such as:

```text
users
beneficiaries
queue_entries
eligibility_forms
psgc_locations
```

## 4. Database config

For XAMPP, `config/db.php` automatically falls back to:

```text
host: localhost
user: root
password: empty
port: 3306
database: dswd_max_payout
```

So no Render/Railway environment variables are needed offline.

## 5. Seed full PSGC address data while online

Before going offline, open this once while internet is available:

```text
http://localhost/dswd_max_payout/api/seed_psgc_all.php
```

Wait until it says DONE.

This fills:

```text
psgc_locations
```

with all Philippine:

```text
regions
provinces
cities/municipalities
barangays
```

After this, the kiosk address dropdowns can work offline using the local database.

## 6. Open the system offline

Main system:

```text
http://localhost/dswd_max_payout/
```

Staff verifier:

```text
http://localhost/dswd_max_payout/staff/verifier.php
```

Assessment:

```text
http://localhost/dswd_max_payout/staff/assessment_screen.php
```

Confirmation:

```text
http://localhost/dswd_max_payout/staff/confirmation_screen.php
```

Kiosk:

```text
http://localhost/dswd_max_payout/kiosk/index.php
```

Counter Display:

```text
http://localhost/dswd_max_payout/staff/counter_display.php
```

## 7. What works offline

Once the project files and database are local, these work offline:

- Login
- Beneficiary import
- Beneficiary verification
- Auto queue assignment
- Kiosk registration
- PSGC address dropdowns, if seeded
- Assessment / GIS form
- Confirmation / payout
- Counter display screen
- Analytics based on local data

## 8. What needs internet first

Only these need internet before offline use:

- Downloading the project from GitHub
- Exporting the online Railway database
- Running `api/seed_psgc_all.php` if the `psgc_locations` table is empty

After those are done, the system can run offline through XAMPP.

## 9. Recommended offline backup flow

From online database:

```text
Export full database SQL
```

Then in local XAMPP phpMyAdmin:

```text
Import full database SQL
```

For best compatibility, always include:

```text
psgc_locations
beneficiaries
queue_entries
eligibility_forms
users
```

## 10. Notes

If the kiosk address dropdown says offline data is missing, run the PSGC seeder once while connected to the internet:

```text
http://localhost/dswd_max_payout/api/seed_psgc_all.php
```

Then refresh the kiosk.
