# Momora AI — PHP/MySQL REST API Backend

> **Personalized AI-Powered Pregnancy Monitoring and Guidance Application**  
> Complete RESTful API backend supporting Patient, Doctor/Clinician, and Administrator workflows.

---

## 📋 Table of Contents
- [Overview](#overview)
- [Architecture & Tech Stack](#architecture--tech-stack)
- [Directory Structure](#directory-structure)
- [Database Setup (XAMPP / MySQL)](#database-setup-xampp--mysql)
- [Configuration](#configuration)
- [Default Seed Accounts](#default-seed-accounts)
- [API Endpoints Reference](#api-endpoints-reference)
  - [Authentication](#1-authentication)
  - [Patient Dashboard & Profile](#2-patient-dashboard--profile)
  - [Health & Vitals Tracking](#3-health--vitals-tracking)
  - [Clinical Care & Milestones](#4-clinical-care--milestones)
  - [AI Pregnancy Chatbot](#5-ai-pregnancy-guidance)
  - [Doctor / Clinician Portal](#6-doctor--clinician-portal)
  - [Administrator Management](#7-administrator-management)
- [Android Integration Notes](#android-integration-notes)

---

## 🌟 Overview
Momora AI Backend provides secure, role-based REST APIs for maternal healthcare. It handles:
- Secure authentication via BCrypt password hashing and 30-day bearer tokens.
- Live pregnancy tracking: gestational age calculation, trimester milestones, due date countdowns.
- Maternal health logs: blood pressure, weight, symptoms, and vital signs.
- Daily routines: medication adherence and appointment scheduling.
- Clinician portal: doctor caseload, assigned patient vitals monitoring, and direct medical advice.
- System administration: doctor/patient account management and system analytics.

---

## 🛠️ Architecture & Tech Stack
- **Language**: PHP 8.1+ / 8.2+
- **Database**: MySQL 5.7+ / 8.0+ or MariaDB 10.4+ (InnoDB Engine)
- **Database Driver**: PHP Data Objects (PDO) with prepared statements
- **Security**:
  - `password_hash()` (BCrypt) & `password_verify()`
  - Role-Based Access Control (RBAC: `patient`, `doctor`, `admin`)
  - Cross-Origin Resource Sharing (CORS) configured for mobile clients
  - Bearer token authorization stored in `user_tokens` table

---

## 📂 Directory Structure

```text
momora_AI-backend/
├── admin/
│   ├── dashboard.php            # Admin system analytics & metrics
│   ├── doctors.php              # Doctor management (list, add, toggle status)
│   ├── patients.php             # Patient management (list, toggle access)
│   └── settings.php             # MFA & admin security settings
├── ai/
│   └── chat.php                 # AI pregnancy guidance conversation history & Q&A
├── appointments/
│   └── appointments.php         # Antenatal appointment booking & availability
├── auth/
│   ├── login.php                # Role-aware login with token issuance
│   └── signup.php               # Patient registration & auto-initialization
├── baby/
│   └── development.php          # Fetal growth milestones by gestational week
├── config/
│   └── config.php               # Database connection, CORS & auth middleware
├── dashboard/
│   └── patient_dashboard.php    # Live patient dashboard metrics & daily care items
├── database/
│   └── momora_ai.sql            # Complete database schema & seed dataset
├── doctor/
│   ├── advice.php               # Direct clinician advice to patients
│   ├── dashboard.php            # Doctor caseload, visits & review alerts
│   ├── patient_history.php      # Detailed medical background & medications
│   ├── patient_monitoring.php   # Vitals trends (BP & weight) for doctor review
│   ├── patients.php             # List of assigned patients
│   └── schedule.php             # Clinician appointment schedule
├── health/
│   ├── blood_pressure.php       # Blood pressure logging & normalized graph coordinates
│   ├── symptoms.php             # Symptom logging with contextual AI guidance
│   ├── vitals.php               # Vitals overview (BP, heart rate, temp, doctor)
│   └── weight.php               # Maternal weight logging & delta calculation
├── medication/
│   └── reminders.php            # Medication list & daily adherence checklist
├── notifications/
│   └── notifications.php        # Patient alerts, reviews & reminders
├── nutrition/
│   └── plan.php                 # Trimester meal ideas & dietary priorities
├── pregnancy/
│   └── pregnancy_details.php    # Estimated due date, LMP & countdown
├── profile/
│   ├── profile_add.php          # Profile setup
│   ├── profile_edit.php         # Profile update endpoint
│   └── profile_fetch.php        # Patient profile & care team metadata
├── reports/
│   └── reports.php              # Ultrasound & lab test metadata
└── README.md                    # Backend documentation
```

---

## 🗄️ Database Setup (XAMPP / MySQL)

1. **Start XAMPP**: Ensure **Apache** and **MySQL** services are running.
2. **Create Database & Import Schema**:
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin/`).
   - Create a database named `momora_ai` (Collation: `utf8mb4_unicode_ci`).
   - Click **Import** and select `database/momora_ai.sql`.
   - Alternatively, import via MySQL CLI:
     ```bash
     mysql -u root -p momora_ai < database/momora_ai.sql
     ```

---

## ⚙️ Configuration

Database connection settings are centralized in `config/config.php`:

```php
$dbHost = 'localhost';
$dbName = 'momora_ai';
$dbUser = 'root';
$dbPass = '';
```

---

## 👥 Default Seed Accounts

The database comes pre-seeded with three demo accounts for testing:

| Role | Name | Email | Password |
| :--- | :--- | :--- | :--- |
| **Patient** | Maya Patel | `maya.patel@example.com` | `Password123!` |
| **Doctor** | Dr Amelia Chen | `amelia.chen@willow.example` | `Password123!` |
| **Admin** | Alex Morgan | `admin@willow.example` | `Password123!` |

---

## 📡 API Endpoints Reference

All responses return standard JSON format:
```json
{
  "success": true,
  "message": "Optional message",
  "data": { ... }
}
```

### 1. Authentication
- **`POST /auth/login.php`**
  - **Body**: `email`, `password`, `role` (optional: `patient` / `doctor` / `admin`)
  - **Returns**: User object, Bearer token, expiration timestamp.
- **`POST /auth/signup.php`**
  - **Body**: `fullName`, `email`, `password`, `dateOfBirth`
  - **Returns**: Created user details, initial profile, pregnancy details & Bearer token.

### 2. Patient Dashboard & Profile
- **`GET /dashboard/patient_dashboard.php`**
  - **Headers**: `Authorization: Bearer <token>` (or `?user_id=5`)
  - **Returns**: Patient name, gestational age, trimester, due date countdown, latest BP, latest weight, and daily care list.
- **`GET /profile/profile_fetch.php`**
  - **Returns**: Avatar initials, contact info, blood group, assigned doctor name.
- **`POST /profile/profile_edit.php`**
  - **Body**: `fullName`, `email`, `dateOfBirth`, `bloodGroup`, `isCareTeamSharingOn`
- **`GET & POST /pregnancy/pregnancy_details.php`**
  - **Returns/Updates**: Estimated due date, LMP, assigned obstetrician, next clinic visit.

### 3. Health & Vitals Tracking
- **`GET /health/vitals.php`**: Overview of latest BP, pulse, weight, and body temperature.
- **`GET & POST /health/blood_pressure.php`**:
  - `GET`: Returns recent readings with normalized (0.0 to 1.0) coordinates ready for Android Canvas rendering.
  - `POST`: Logs `systolic`, `diastolic`, and `pulse`.
- **`GET & POST /health/weight.php`**:
  - `GET`: Returns normalized chart points and baseline weight delta.
  - `POST`: Logs maternal weight in kg and reading date.
- **`GET & POST /health/symptoms.php`**:
  - `GET`: Returns logged symptoms and dropdown options.
  - `POST`: Logs symptom with automated educational AI guidance.

### 4. Clinical Care & Milestones
- **`GET /baby/development.php?week=24`**: Length (cm), weight (g), and sensory highlights.
- **`GET /nutrition/plan.php?trimester=2`**: Trimester-specific meal inspirations & priorities.
- **`GET & POST /medication/reminders.php`**:
  - `GET`: Daily medication checklist.
  - `POST`: Mark pill taken (`medicationId`).
- **`GET & POST /appointments/appointments.php`**:
  - `GET`: Available calendar dates and time slots.
  - `POST`: Confirm antenatal appointment booking.
- **`GET & POST /reports/reports.php`**: Medical lab test and ultrasound metadata.
- **`GET & POST /notifications/notifications.php`**: Fetch notifications and mark all read.

### 5. AI Pregnancy Guidance
- **`GET & POST /ai/chat.php`**:
  - `GET`: Conversational history.
  - `POST`: Send user query (`message`). Returns tailored pregnancy wellbeing guidance with clinical disclaimers.

### 6. Doctor / Clinician Portal
- **`GET /doctor/dashboard.php`**: Caseload count, today's visit breakdown, next appointment.
- **`GET /doctor/patients.php`**: List of assigned patients with latest vitals and review alerts.
- **`GET /doctor/patient_history.php?patientId=5`**: Clinical background and active prescriptions.
- **`GET /doctor/patient_monitoring.php?patientId=5`**: 7-day systolic/diastolic vitals trends.
- **`GET & POST /doctor/advice.php`**: Consult on patient symptoms and send clinical advice.
- **`GET /doctor/schedule.php`**: Daily clinic schedule.

### 7. Administrator Management
- **`GET /admin/dashboard.php`**: Active/inactive patient and doctor counts, registration trends.
- **`GET & POST /admin/doctors.php`**: Add clinician account (`action=create`) or toggle status (`action=toggle`).
- **`GET & POST /admin/patients.php`**: List accounts and toggle patient access.
- **`GET & POST /admin/settings.php`**: Security settings and admin logout.

---

## 📱 Android Integration Notes

- **Android Emulator**: Base URL is `http://10.0.2.2/momora-AI/`
- **Physical Phone (LAN/Wi-Fi)**: Set Base URL to `http://<YOUR_COMPUTER_IP>/momora-AI/` (e.g. `http://192.168.1.15/momora-AI/`)
- **Cleartext Traffic**: Ensure `android:usesCleartextTraffic="true"` is enabled in `app/src/main/AndroidManifest.xml` for local HTTP development.

