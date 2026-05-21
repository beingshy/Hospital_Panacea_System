HOSPITAL MANAGEMENT + BILLING AND INVOICE SYSTEM

SYSTEM PAIR:
PHP/Web: Hospital Management System
C# Windows Forms: Billing and Invoice System

DATABASE:
SQLite

CONNECTION:
C# connects to PHP through api.php.
PHP and API use the same SQLite database.

RUN PHP:
1. Open php-api folder.
2. Run:
   php -S localhost:8000

If php is not found:
   /c/xampp/php/php.exe -S localhost:8000

OPEN PHP HOSPITAL ADMIN:
http://localhost:8000/index.php

OPEN C# BILLING SYSTEM:
csharp\HospitalInvoiceBillingApp.sln

DEFAULT PHP ADMIN LOGIN:
admin@hospital.test
admin123

DEFAULT C# BILLING LOGIN:
billing@hospital.test
billing123

PHP HOSPITAL FEATURES:
- Admin login/logout
- Dashboard counts
- Patient CRUD
- Doctor CRUD
- Appointment scheduling
- Admission records
- Hospital services and charges
- Invoice monitoring

C# BILLING FEATURES:
- Billing staff login/logout
- Load patients from hospital API
- Load hospital services from hospital API
- Add service invoice items
- Add custom invoice items
- Discount, tax, paid amount, balance calculation
- Save invoice
- View invoice records
- View invoice item details
- Update invoice payment amount

API URL:
http://localhost:8000/api.php


PANACEA THEME UPDATE:
The PHP and C# interfaces were restyled using the uploaded hospital dashboard reference.
Functionality, API connection, SQLite database, and default accounts were preserved.
