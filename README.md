# Momora AI — Backend REST API

> **Personalized AI-Powered Pregnancy Monitoring & Maternal Health Guidance Platform**  
> Comprehensive PHP/MySQL RESTful backend powering Android mobile clients with secure role-based access for Patients, Clinicians/Doctors, and Administrators.

---

## 📋 Table of Contents
- [Overview & Project Description](#overview--project-description)
- [User Roles & Permissions](#user-roles--permissions)
- [Tech Stack & Architecture](#tech-stack--architecture)
- [Setup & Installation](#setup--installation)
  - [1. XAMPP Configuration](#1-xampp-configuration)
  - [2. Database Creation & Import](#2-database-creation--import)
  - [3. Environment Configuration (.env)](#3-environment-configuration-env)
  - [4. Remote Access with ngrok](#4-remote-access-with-ngrok)
- [API Endpoints Reference](#api-endpoints-reference)
  - [Admin (`admin/`)](#admin-admin)
  - [AI Assistant (`ai/`)](#ai-assistant-ai)
  - [Appointments (`appointments/`)](#appointments-appointments)
  - [Authentication (`auth/`)](#authentication-auth)
  - [Fetal Development (`baby/`)](#fetal-development-baby)
  - [Configuration (`config/`)](#configuration-config)
  - [Dashboard (`dashboard/`)](#dashboard-dashboard)
  - [Doctor Portal (`doctor/`)](#doctor-portal-doctor)
  - [Health & Vitals (`health/`)](#health--vitals-health)
  - [Medications (`medication/`)](#medications-medication)
  - [Notifications (`notifications/`)](#notifications-notifications)
  - [Nutrition (`nutrition/`)](#nutrition-nutrition)
  - [Patient Clinical Advice (`patient/`)](#patient-clinical-advice-patient)
  - [Pregnancy Details (`pregnancy/`)](#pregnancy-details-pregnancy)
  - [Profile Management (`profile/`)](#profile-management-profile)
  - [Medical Reports (`reports/`)](#medical-reports-reports)
  - [Device Tokens (`tokens/`)](#device-tokens-tokens)
- [Test Accounts](#test-accounts)
- [Known Limitations](#known-limitations)
- [Future Work](#future-work)

---

## 🌟 Overview & Project Description

**Momora AI** is an intelligent maternal health ecosystem designed to provide continuous, empathetic, and clinically grounded support for expectant mothers while empowering healthcare providers with actionable remote monitoring data.

The backend serves as the central data and intelligence hub:
- **Real-Time Clinical Vitals & Trends**: Captures blood pressure, maternal weight, and symptoms; normalizes coordinate points for mobile canvas chart rendering.
- **Dynamic Pregnancy Tracking**: Dynamically computes gestational age (weeks and days) and trimester milestones from clinical last menstrual period (LMP) and estimated due date (EDD).
- **Two-Way Clinician Communication**: Enables obstetricians to review patient vitals trends, evaluate ultrasound/lab reports, reschedule clinic appointments, and dispatch personalized clinical advice directly to patient dashboards.
- **AI Guidance Integration**: Integrates Google Gemini generative intelligence with maternal wellbeing guardrails to provide contextual pregnancy education and symptom explanations.
- **Administrative Control**: Facilitates clinician provisioning, account activation/deactivation, and security audit monitoring.

---

## 👥 User Roles & Permissions

The platform enforces strict Role-Based Access Control (RBAC) via the central `require_auth()` middleware:

| Role | Key Capabilities |
| :--- | :--- |
| **`patient`** | Logs daily blood pressure, weight, and symptoms; tracks gestational age and fetal milestones; views medication adherence checklists; books antenatal appointments; queries AI Assistant; reads clinical advice from assigned doctor. |
| **`doctor`** | Manages clinic caseload; views real-time vitals and gestational age of assigned patients; inspects lab reports and prescriptions; writes direct clinical advice; manages weekly availability slots; reschedules appointments. |
| **`admin`** | Provisions clinician accounts; toggles active status for doctors and patients; monitors clinic analytics and registration trends; manages security settings. |

---

## 🛠️ Tech Stack & Architecture

- **Backend Runtime**: PHP 8.1+ / 8.2+
- **Web Server**: Apache 2.4+ (XAMPP Environment)
- **Database**: MySQL 5.7+ / 8.0+ or MariaDB 10.4+ (InnoDB Engine)
- **Database Abstraction**: PHP Data Objects (PDO) with strict prepared statements
- **Security & Authentication**:
  - BCrypt password hashing via `password_hash()` and `password_verify()`
  - Stateful 30-day Bearer Token authentication stored in `user_tokens` table
  - Role-Based Access Control (`require_auth(['patient'])`, `require_auth(['doctor'])`, `require_auth(['admin'])`)
  - Configurable Cross-Origin Resource Sharing (CORS) for Android clients
- **External AI Integrations**:
  - Google Gemini API (`gemini-1.5-flash`) via REST
  - Fallback rule-based heuristic guidance engine when API keys are unconfigured
- **Tunneling**: ngrok for public HTTPS forwarding to physical mobile devices and emulators

---

## 🚀 Setup & Installation

### 1. XAMPP Configuration
1. Install [XAMPP](https://www.apachefriends.org/) with PHP 8.1 or higher.
2. Clone or copy the project into the web root:
   ```text
   C:\xampp\htdocs\momora-AI\
   ```
   *(or `C:\xamp\htdocs\momora-AI\` depending on your local installation path)*.
3. Open the **XAMPP Control Panel** and start both **Apache** and **MySQL** services.
4. Verify Apache is operational by navigating to `http://localhost/` in your browser.

### 2. Database Creation & Import
1. Navigate to **phpMyAdmin** at `http://localhost/phpmyadmin/`.
2. Click **New** and create a database named:
   ```text
   momora_ai
   ```
   *(Select `utf8mb4_unicode_ci` or `utf8mb4_general_ci` collation)*.
3. Click on the newly created `momora_ai` database, select the **Import** tab, choose the file:
   ```text
   database/momora_ai.sql
   ```
   and click **Import**.
4. *Alternative via CLI:*
   ```bash
   mysql -u root -p momora_ai < database/momora_ai.sql
   ```
5. Database connection credentials in `config/config.php` default to:
   - Host: `localhost`
   - Database: `momora_ai`
   - User: `root`
   - Password: `""` *(empty)*

### 3. Environment Configuration (.env)
1. Copy the example configuration template to create your `.env` file:
   ```bash
   cp .env.example .env
   ```
2. Open `.env` and specify your AI provider and API credentials:
   ```env
   # AI Provider: 'gemini' (recommended) or 'openai' or 'fallback'
   AI_PROVIDER=gemini

   # Google Gemini API Configuration (Obtain key at https://aistudio.google.com/)
   GEMINI_API_KEY=your_gemini_api_key_here
   GEMINI_MODEL=gemini-1.5-flash

   # Optional OpenAI Alternative
   OPENAI_API_KEY=your_openai_api_key_here
   OPENAI_MODEL=gpt-4o-mini
   ```
3. If no key is provided, the backend automatically operates in fallback mode, supplying rule-based maternal wellbeing guidance.

### 4. Remote Access with ngrok
For running against physical Android devices on mobile networks or external clients:
1. Download and install [ngrok](https://ngrok.com/).
2. Expose your local Apache port:
   ```bash
   ngrok http 80
   ```
3. Copy the generated HTTPS forwarding URL (e.g. `https://xxxx-xx-xx.ngrok-free.app`).
4. Update the `BASE_URL` in the Android client (`RetrofitClient.kt`):
   ```kotlin
   private const val BASE_URL = "https://xxxx-xx-xx.ngrok-free.app/momora-AI/"
   ```
*(For local Android Emulator testing, you can also use `http://10.0.2.2/momora-AI/`)*.

---

## 📡 API Endpoints Reference

All endpoints accept standard JSON or Form-Data and return unified JSON responses:
```json
{
  "success": true,
  "message": "Status description",
  "data": { ... }
}
```

### Admin (`admin/`)
*Protected by `require_auth(['admin'])`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `admin/dashboard.php` | `GET` | Administrator dashboard metrics (patient/doctor active counts, registration trend values). |
| `admin/doctors.php` | `GET`, `POST` | `GET`: List doctor accounts.<br>`POST action=create`: Provision new doctor account.<br>`POST action=toggle`: Toggle doctor active/inactive status. |
| `admin/patients.php` | `GET`, `POST` | `GET`: List patient accounts.<br>`POST action=toggle`: Toggle patient account access status. |
| `admin/settings.php` | `GET`, `POST` | `GET`: View admin security settings.<br>`POST action=logout`: Invalidate admin token.<br>`POST`: Update MFA status and session timeout. |

### AI Assistant (`ai/`)
*Protected by `require_auth(['patient'])`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `ai/chat.php` | `GET`, `POST` | `GET`: Retrieve recent conversation history.<br>`POST`: Send maternal health question; returns clinically guided response. |
| `ai/chat_history.php` | `GET`, `POST` | `GET`: Fetch chat messages.<br>`POST`: Clear or archive conversation thread. |
| `ai/ai_assistant.php` | `POST` | Dedicated AI prompt handler for symptom and nutritional inquiries. |
| `ai/ai_service.php` | Internal | Shared service integrating Gemini Live API / OpenAI / Heuristic fallback. |

### Appointments (`appointments/`)
*Protected by `require_auth()`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `appointments/appointments.php` | `GET`, `POST` | `GET`: Fetch available dates, time slots, and doctor details.<br>`POST`: Confirm antenatal appointment booking and trigger doctor notification. |

### Authentication (`auth/`)
*Public endpoints.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `auth/login.php` | `POST` | Authenticates email and password with optional role filter; issues 30-day Bearer token. |
| `auth/signup.php` | `POST` | Registers new patient account; initializes medical profile, pregnancy details, and authentication token. |
| `auth/forgot_password.php` | `POST` | Generates password reset token and verification code. |
| `auth/reset_password.php` | `POST` | Verifies reset token and updates user password with BCrypt hash. |

### Fetal Development (`baby/`)
*Protected by `require_auth(['patient'])`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `baby/development.php` | `GET` | Returns fetal growth data (length in cm, weight in grams, sensory highlights) for gestational week (`?week=X`). |

### Configuration (`config/`)
*Internal system configuration.*

| File | Purpose |
| :--- | :--- |
| `config/config.php` | Central PDO database connection, CORS headers, token parser, and `require_auth()` authorization guard. |
| `config/env.php` | Custom lightweight `.env` parser and environment variable retrieval functions. |

### Dashboard (`dashboard/`)
*Protected by `require_auth(['patient'])`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `dashboard/patient_dashboard.php` | `GET` | Live patient home metrics: real-time gestational age, trimester, due date countdown, latest vitals, and daily care checklist. |

### Doctor Portal (`doctor/`)
*Protected by `require_auth(['doctor'])`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `doctor/dashboard.php` | `GET` | Clinician dashboard metrics: total caseload, visits today breakdown, next visit, and patient review alerts. |
| `doctor/patients.php` | `GET` | List of assigned patients with real-time calculated gestational age and latest BP reading timestamp. |
| `doctor/patient_history.php` | `GET` | Patient clinical background, active medications, allergy records, and lab report history (`?patientId=X`). |
| `doctor/patient_monitoring.php` | `GET` | Vitals monitoring trends: systolic/diastolic BP series and weight progression (`?patientId=X&days=Y`). |
| `doctor/advice.php` | `GET`, `POST` | `GET`: Fetch advice history for patient.<br>`POST`: Send clinical advice note to patient and trigger patient notification. |
| `doctor/schedule.php` | `GET` | Clinician clinic schedule and appointments for chosen date (`?date=YYYY-MM-DD`). |
| `doctor/appointment_reschedule.php` | `POST` | Reschedules an existing appointment to a new date and time. |
| `doctor/availability.php` | `GET`, `POST` | `GET`: Fetch 7-day availability slots and slot duration.<br>`POST`: Save weekly working hours and slot length. |
| `doctor/profile_fetch.php` | `GET` | Fetch authenticated doctor's profile details. |
| `doctor/profile_update.php` | `POST` | Update doctor's name, clinic, license ID, phone, specialty, and qualifications. |

### Health & Vitals (`health/`)
*Protected by `require_auth(['patient'])`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `health/blood_pressure.php` | `GET`, `POST` | `GET`: Historical BP logs with normalized (0.0 to 1.0) coordinates for canvas rendering.<br>`POST`: Log systolic, diastolic, and pulse. |
| `health/weight.php` | `GET`, `POST` | `GET`: Weight logs with baseline weight change.<br>`POST`: Log maternal weight reading in kg. |
| `health/symptoms.php` | `GET`, `POST` | `GET`: Logged symptom entries and dropdown categories.<br>`POST`: Log symptom severity, start time, and duration; returns automated educational guidance. |
| `health/vitals.php` | `GET` | High-level summary of latest BP, pulse, body temperature, and assigned doctor. |

### Medications (`medication/`)
*Protected by `require_auth(['patient'])`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `medication/reminders.php` | `GET`, `POST` | `GET`: Daily medication checklist and schedule.<br>`POST`: Mark medication taken for today (`medicationId`). |

### Notifications (`notifications/`)
*Protected by `require_auth()`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `notifications/notifications.php` | `GET`, `POST` | `GET`: Fetch notification feed.<br>`POST action=mark_all_read`: Mark all notifications as read. |
| `notifications/notification_fetch.php` | `GET` | Fetch user notification items. |
| `notifications/notification_create.php` | `POST` | Create internal notification alert. |
| `notifications/notification_mark_read.php` | `POST` | Mark individual notification as read (`notificationId`). |
| `notifications/notification_mark_all_read.php` | `POST` | Bulk mark all notifications as read. |
| `notifications/notification_delete.php` | `POST` | Delete notification item. |

### Nutrition (`nutrition/`)
*Protected by `require_auth(['patient'])`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `nutrition/plan.php` | `GET` | Trimester-specific meal recommendations, nutritional priorities, and hydration guidance (`?trimester=X`). |

### Patient Clinical Advice (`patient/`)
*Protected by `require_auth(['patient'])`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `patient/advice_fetch.php` | `GET` | Returns list of clinical advice notes sent by doctors to the authenticated patient, sorted newest first. |

### Pregnancy Details (`pregnancy/`)
*Protected by `require_auth(['patient'])`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `pregnancy/pregnancy_details.php` | `GET`, `POST` | `GET`: Retrieve estimated due date, LMP, assigned obstetrician, and clinic visit info.<br>`POST`: Update estimated due date and recalculate gestational milestones. |

### Profile Management (`profile/`)
*Protected by `require_auth()`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `profile/profile_fetch.php` | `GET` | Patient profile attributes, contact info, blood group, and care team details. |
| `profile/profile_edit.php` | `POST` | Update patient personal details, address, and care team sharing preferences. |
| `profile/profile_update.php` | `POST` | Extended profile update endpoint. |
| `profile/profile_add.php` | `POST` | Initial patient profile setup. |
| `profile/profile_photo_upload.php` | `POST` | Upload and persist profile avatar image to `uploads/` directory. |

### Medical Reports (`reports/`)
*Protected by `require_auth()`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `reports/reports.php` | `GET`, `POST` | `GET`: Fetch ultrasound reports and lab test records categorized by type.<br>`POST`: Register new medical document metadata. |

### Device Tokens (`tokens/`)
*Protected by `require_auth()`.*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `tokens/token_register.php` | `POST` | Register client FCM/device push token with user session. |
| `tokens/token_fetch.php` | `GET` | Fetch active device tokens. |
| `tokens/token_delete.php` | `POST` | Unregister device token upon user sign-out. |

---

## 👥 Test Accounts

The seed database (`database/momora_ai.sql`) includes pre-provisioned demo accounts across all three platform roles:

| Role | Name | Email |
| :--- | :--- | :--- |
| **Patient** | Maya Patel | `maya.patel@example.com` |
| **Doctor** | Dr Amelia Chen | `amelia.chen@willow.example` |
| **Administrator** | Alex Morgan | `admin@willow.example` |

*(Note: In accordance with security practices, passwords are not published in documentation. Refer to local developer records or your team lead for test credentials)*.

---

## ⚠️ Known Limitations

1. **Gemini Key Not Set**:
   - If `GEMINI_API_KEY` is omitted from `.env`, `ai_service.php` automatically defaults to the built-in rule-based fallback guidance engine. Live generative conversations require an active Google AI Studio API key.
2. **Firebase Push Notifications Not Built**:
   - Device tokens can be registered via `tokens/token_register.php`, but automated Firebase Cloud Messaging (FCM) background push notifications are not yet dispatched. In-app alerts are currently stored in MySQL (`notifications` table) and retrieved via polling.
3. **Assigned Patients Only Check Not Done**:
   - Doctor endpoints (`doctor/patient_history.php`, `doctor/patient_monitoring.php`, `doctor/advice.php`) accept any `patientId` parameter without asserting that the patient is explicitly assigned to the querying doctor's caseload (`profiles.assigned_doctor_id == current_doctor_id`).

---

## 🔮 Future Work

- **Firebase Cloud Messaging (FCM)**: Integrate server-side FCM HTTP v1 API to deliver instant background push notifications for upcoming appointments and clinician advice.
- **Strict Caseload Authorization Barrier**: Enforce strict relationship checks in `doctor/*` endpoints to ensure clinicians can only access patient records assigned to them or shared with clinical consent.
- **Multimodal AI Report Analysis**: Extend the Gemini integration to process uploaded ultrasound and lab report images via OCR, extracting key biometric markers automatically.
- **Real-Time Doctor-Patient Messaging**: Implement WebSocket or Server-Sent Events (SSE) for synchronous clinical communication alongside asynchronous advice.
- **Automated Email/SMS Reminders**: Set up cron-driven notification services for daily medication reminders and overdue antenatal checkups.
- **Automated Test Suite**: Build a comprehensive PHPUnit test suite covering authentication, RBAC boundaries, and vitals computation logic.
