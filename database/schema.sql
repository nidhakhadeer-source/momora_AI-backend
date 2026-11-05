-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2026 at 05:07 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `momora_ai`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

DROP TABLE IF EXISTS `admin_settings`;
CREATE TABLE `admin_settings` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `is_mfa_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `session_timeout_minutes` int(11) NOT NULL DEFAULT 15,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_settings`
--

INSERT INTO `admin_settings` (`id`, `admin_id`, `is_mfa_enabled`, `session_timeout_minutes`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 15, '2026-10-29 08:33:18', '2026-10-29 08:33:18');

-- --------------------------------------------------------

--
-- Table structure for table `ai_chat_messages`
--

DROP TABLE IF EXISTS `ai_chat_messages`;
CREATE TABLE `ai_chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender` enum('USER','AI') NOT NULL,
  `message_text` text NOT NULL,
  `bullet_points` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bullet_points`)),
  `footer_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_chat_messages`
--

INSERT INTO `ai_chat_messages` (`id`, `user_id`, `sender`, `message_text`, `bullet_points`, `footer_note`, `created_at`) VALUES
(1, 5, 'USER', 'How much water should I drink daily?', NULL, NULL, '2026-10-29 09:18:51'),
(2, 5, 'AI', 'Thank you for asking. Here is some general pregnancy wellbeing guidance regarding \'How much water should I drink daily?\':', '[\"Stay well-hydrated throughout the day with regular sips of water.\",\"Listen to your body and pace your daily physical activities.\",\"Note any persisting questions to discuss at your upcoming antenatal visit.\"]', 'Momora AI educational guidance · Consult Dr Amelia Chen or your clinic for clinical diagnosis.', '2026-10-29 09:18:51'),
(3, 8, 'USER', 'Meal ideas', NULL, NULL, '2026-10-30 07:00:16'),
(4, 8, 'AI', 'Thank you for asking. Here is some general pregnancy wellbeing guidance regarding \'Meal ideas\':', '[\"Stay well-hydrated throughout the day with regular sips of water.\",\"Listen to your body and pace your daily physical activities.\",\"Note any persisting questions to discuss at your upcoming antenatal visit.\"]', 'Momora AI educational guidance · Consult Dr Amelia Chen or your clinic for clinical diagnosis.', '2026-10-30 07:00:16'),
(5, 8, 'USER', 'Meal ideas', NULL, NULL, '2026-10-30 07:00:19'),
(6, 8, 'AI', 'Thank you for asking. Here is some general pregnancy wellbeing guidance regarding \'Meal ideas\':', '[\"Stay well-hydrated throughout the day with regular sips of water.\",\"Listen to your body and pace your daily physical activities.\",\"Note any persisting questions to discuss at your upcoming antenatal visit.\"]', 'Momora AI educational guidance · Consult Dr Amelia Chen or your clinic for clinical diagnosis.', '2026-10-30 07:00:19'),
(7, 5, 'USER', 'I am having heavy bleeding and severe abdominal pain, what should I do?', NULL, NULL, '2026-11-03 08:02:22'),
(8, 5, 'AI', 'URGENT MEDICAL NOTICE: The symptoms you described may indicate a potential pregnancy emergency that requires immediate clinical evaluation. Please contact your obstetrician, visit the nearest maternity emergency triage room, or call your local emergency service (e.g. 112 / 911 / 108) immediately. Do not rely on an AI or delay seeking medical attention.', '[\"Contact your maternity hospital triage helpline or obstetrician right now.\",\"Do not take unprescribed painkillers, aspirin, or medicines before clinical evaluation.\",\"Have a family member or caregiver stay with you and accompany you to the hospital.\",\"Bring your antenatal medical records and note the exact time symptoms started.\"]', 'Momora AI Emergency Alert · Immediate clinical evaluation required.', '2026-11-03 08:02:22'),
(9, 5, 'USER', 'What foods should I eat during my first trimester?', NULL, NULL, '2026-11-03 08:02:53'),
(10, 5, 'AI', 'A balanced diet rich in essential nutrients supports both maternal health and healthy fetal development:', '[\"Focus on folate-rich foods like dark leafy greens, citrus fruits, lentils, and fortified grains.\",\"Include adequate protein from thoroughly cooked eggs, poultry, lean meats, beans, and tofu.\",\"Consume calcium-rich foods like pasteurized milk, yogurt, and fortified plant milks.\",\"Avoid unpasteurized dairy, raw or undercooked meats\\/seafood, and high-mercury fish like swordfish or shark.\"]', 'Momora AI educational guidance · Consult your obstetrician or clinic for clinical diagnosis.', '2026-11-03 08:02:53'),
(11, 5, 'USER', 'How do I write a Python web scraper?', NULL, NULL, '2026-11-03 08:03:04'),
(12, 5, 'AI', 'I am Momora AI, designed exclusively to provide pregnancy monitoring and maternal wellness guidance. I can only assist with pregnancy-related questions. Please feel free to ask about nutrition, symptoms, baby milestones, or wellness during pregnancy!', NULL, 'Momora AI · Exclusively dedicated to pregnancy & maternal health.', '2026-11-03 08:03:04'),
(13, 2, 'USER', 'What are the common pregnancy symptoms?', NULL, NULL, '2026-11-03 08:03:26'),
(14, 2, 'AI', 'Every pregnancy is unique, but common physiological symptoms across stages include:', '[\"Fatigue and tender breasts, which are typical early in pregnancy as progesterone levels rise.\",\"Mild cramping or round ligament stretches as your uterus expands.\",\"Frequent urination and mild digestive changes such as heartburn or constipation.\",\"Always discuss any persistent, unusual, or distressing symptoms with your maternity care team.\"]', 'Momora AI educational guidance · Consult your obstetrician or clinic for clinical diagnosis.', '2026-11-03 08:03:26');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `doctor_name` varchar(150) NOT NULL,
  `doctor_specialty` varchar(150) DEFAULT 'Obstetrician ?? Willow Maternity Clinic',
  `appointment_date` date NOT NULL,
  `appointment_time` varchar(50) NOT NULL,
  `is_in_person` tinyint(1) NOT NULL DEFAULT 1,
  `appointment_type` varchar(100) DEFAULT 'Routine antenatal check',
  `location_notes` text DEFAULT NULL,
  `status` enum('Scheduled','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Confirmed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `doctor_name`, `doctor_specialty`, `appointment_date`, `appointment_time`, `is_in_person`, `appointment_type`, `location_notes`, `status`, `created_at`) VALUES
(1, 5, 3, 'Dr Amelia Chen', 'Obstetrician ?? Willow Maternity Clinic', '2026-10-29', '10:30', 1, 'Routine antenatal check', 'Willow Maternity Clinic ?? Room 3. Bring your pregnancy notes and recent reports.', 'Confirmed', '2026-10-29 08:33:18'),
(2, 5, 3, 'Dr Amelia Chen', 'Obstetrician ?? Willow Maternity Clinic', '2026-11-05', '10:30', 1, 'Antenatal Consultation', 'Willow Maternity Clinic · Room 3', 'Confirmed', '2026-11-04 06:51:48');

-- --------------------------------------------------------

--
-- Table structure for table `baby_development`
--

DROP TABLE IF EXISTS `baby_development`;
CREATE TABLE `baby_development` (
  `id` int(11) NOT NULL,
  `week` int(11) NOT NULL,
  `subtitle` varchar(255) NOT NULL,
  `length_cm_text` varchar(50) NOT NULL,
  `length_sub_text` varchar(100) DEFAULT 'Head to heel ?? approximate',
  `weight_gram_text` varchar(50) NOT NULL,
  `weight_sub_text` varchar(100) DEFAULT 'educational estimate',
  `highlights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`highlights`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `baby_development`
--

INSERT INTO `baby_development` (`id`, `week`, `subtitle`, `length_cm_text`, `length_sub_text`, `weight_gram_text`, `weight_sub_text`, `highlights`, `created_at`) VALUES
(1, 24, 'Week 24 ?? Your baby\'s weekly development.', '30 cm', 'Head to heel ?? approximate', '600 g', 'educational estimate', '[{\"id\":1,\"title\":\"A world of sound\",\"subtitle\":\"Hearing continues to develop this week.\",\"iconType\":\"SOUND\"},{\"id\":2,\"title\":\"Little movements\",\"subtitle\":\"You may notice your baby\'s own rhythm.\",\"iconType\":\"MOVEMENT\"}]', '2026-10-29 08:33:18');

-- --------------------------------------------------------

--
-- Table structure for table `blood_pressure_logs`
--

DROP TABLE IF EXISTS `blood_pressure_logs`;
CREATE TABLE `blood_pressure_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `systolic` int(11) NOT NULL,
  `diastolic` int(11) NOT NULL,
  `pulse` int(11) DEFAULT 78,
  `notes` varchar(255) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blood_pressure_logs`
--

INSERT INTO `blood_pressure_logs` (`id`, `user_id`, `systolic`, `diastolic`, `pulse`, `notes`, `recorded_at`) VALUES
(1, 5, 114, 70, 76, 'Morning check', '2026-10-21 02:30:00'),
(2, 5, 110, 72, 75, 'Morning check', '2026-10-23 02:40:00'),
(3, 5, 115, 68, 77, 'Morning check', '2026-10-25 02:35:00'),
(4, 5, 112, 72, 78, 'Resting vitals', '2026-10-27 02:45:00'),
(5, 5, 116, 74, 76, NULL, '2026-10-29 09:18:51'),
(6, 5, 115, 75, 76, NULL, '2026-11-04 06:49:41');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_advice`
--

DROP TABLE IF EXISTS `doctor_advice`;
CREATE TABLE `doctor_advice` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `advice_text` text NOT NULL,
  `save_copy` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_advice`
--

INSERT INTO `doctor_advice` (`id`, `doctor_id`, `patient_id`, `subject`, `advice_text`, `save_copy`, `created_at`) VALUES
(1, 3, 5, 'Heartburn care plan', 'Stay upright after eating and break meals into 5 smaller portions. We will check at your next clinic appointment.', 1, '2026-11-04 07:02:31');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_profiles`
--

DROP TABLE IF EXISTS `doctor_profiles`;
CREATE TABLE `doctor_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialty` varchar(150) NOT NULL DEFAULT 'Obstetrics',
  `license_id` varchar(100) NOT NULL,
  `clinic` varchar(150) NOT NULL DEFAULT 'Willow Maternity Clinic',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_profiles`
--

INSERT INTO `doctor_profiles` (`id`, `user_id`, `specialty`, `license_id`, `clinic`, `created_at`, `updated_at`) VALUES
(1, 3, 'Obstetrics', 'OBS-19382', 'Willow Maternity Clinic', '2026-10-29 08:33:18', '2026-10-29 08:33:18'),
(2, 11, 'Maternal-Fetal Medicine', 'OBS-55829', 'Willow Maternity Clinic', '2026-11-04 07:03:27', '2026-11-04 07:03:27');

-- --------------------------------------------------------

--
-- Table structure for table `health_vitals`
--

DROP TABLE IF EXISTS `health_vitals`;
CREATE TABLE `health_vitals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `heart_rate` int(11) NOT NULL DEFAULT 78,
  `temperature` decimal(4,1) NOT NULL DEFAULT 36.7,
  `temperature_unit` varchar(10) NOT NULL DEFAULT '??C',
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `health_vitals`
--

INSERT INTO `health_vitals` (`id`, `user_id`, `heart_rate`, `temperature`, `temperature_unit`, `recorded_at`) VALUES
(1, 5, 78, 36.7, '??C', '2026-10-27 02:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `medical_reports`
--

DROP TABLE IF EXISTS `medical_reports`;
CREATE TABLE `medical_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) NOT NULL DEFAULT 'PDF',
  `date_and_size_text` varchar(100) NOT NULL,
  `category` enum('ALL','ULTRASOUND','LAB_RESULTS') NOT NULL DEFAULT 'ALL',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medical_reports`
--

INSERT INTO `medical_reports` (`id`, `user_id`, `title`, `file_path`, `file_type`, `date_and_size_text`, `category`, `notes`, `created_at`) VALUES
(1, 5, 'Anatomy ultrasound', NULL, 'PDF', '02 Oct 2026 ?? PDF ?? 2.4 MB', 'ULTRASOUND', NULL, '2026-10-29 08:33:18'),
(2, 5, 'Complete blood count', NULL, 'PDF', '28 Oct 2026 ?? PDF ?? 640 KB', 'LAB_RESULTS', NULL, '2026-10-29 08:33:18'),
(3, 5, 'Antenatal visit summary', NULL, 'PDF', '04 Oct 2026 ?? PDF ?? 420 KB', 'ALL', NULL, '2026-10-29 08:33:18'),
(4, 5, 'Ultrasound_24Weeks_Report.pdf', NULL, 'PDF', '04 Nov 2026 · PDF · 1.2 MB', 'ULTRASOUND', NULL, '2026-11-04 06:52:02');

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

DROP TABLE IF EXISTS `medications`;
CREATE TABLE `medications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `dosage_and_time` varchar(150) NOT NULL,
  `doctor_prescription_note` varchar(255) DEFAULT NULL,
  `is_taken` tinyint(1) NOT NULL DEFAULT 0,
  `taken_time_note` varchar(100) DEFAULT NULL,
  `due_later_text` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medications`
--

INSERT INTO `medications` (`id`, `user_id`, `name`, `dosage_and_time`, `doctor_prescription_note`, `is_taken`, `taken_time_note`, `due_later_text`, `created_at`, `updated_at`) VALUES
(1, 5, 'Prenatal vitamin', '1 tablet ?? Daily at 08:00 ?? With food', NULL, 1, 'Marked taken today at 07:51', NULL, '2026-10-29 08:33:18', '2026-11-04 06:51:34'),
(2, 5, 'Vitamin D ?? 400 IU', '1 tablet ?? Daily at 20:00', 'As prescribed by Dr. Amelia Chen.', 0, NULL, 'Due later', '2026-10-29 08:33:18', '2026-10-29 08:33:18');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `subtitle` text NOT NULL,
  `detail_text` text DEFAULT NULL,
  `type` enum('APPOINTMENT','REMINDER','REPORT_REVIEW','WEEKLY_DISCOVERY') NOT NULL DEFAULT 'REMINDER',
  `is_new` tinyint(1) NOT NULL DEFAULT 1,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `subtitle`, `detail_text`, `type`, `is_new`, `is_read`, `created_at`) VALUES
(1, 5, 'Your appointment is confirmed', 'Dr Amelia Chen ?? 29 Oct 2026, 10:30', '09:10 ?? Willow Maternity Clinic ?? In person', 'APPOINTMENT', 0, 1, '2026-10-29 08:33:18'),
(2, 5, 'Tonight\'s vitamin D reminder', '400 IU ?? 1 tablet at 20:00', '08:30 ?? Reminder scheduled', 'REMINDER', 0, 1, '2026-10-29 08:33:18'),
(3, 5, 'Your report was reviewed', 'Dr Chen reviewed your blood count.', '08:25 ?? View care-team notes in Medical Reports', 'REPORT_REVIEW', 0, 1, '2026-10-29 08:33:18'),
(4, 5, 'A new week to discover', 'Yesterday ?? Explore today\'s week 24 development.', '', 'WEEKLY_DISCOVERY', 0, 1, '2026-10-29 08:33:18'),
(5, 6, 'Welcome to Momora AI', 'Your personalized pregnancy care companion is ready.', 'Explore your dashboard, track health vitals, and connect with your maternity team.', 'WEEKLY_DISCOVERY', 1, 0, '2026-10-29 08:56:42'),
(6, 7, 'Welcome to Momora AI', 'Your personalized pregnancy care companion is ready.', 'Explore your dashboard, track health vitals, and connect with your maternity team.', 'WEEKLY_DISCOVERY', 1, 0, '2026-10-30 03:42:00'),
(7, 8, 'Welcome to Momora AI', 'Your personalized pregnancy care companion is ready.', 'Explore your dashboard, track health vitals, and connect with your maternity team.', 'WEEKLY_DISCOVERY', 1, 0, '2026-10-30 06:57:46'),
(8, 9, 'Welcome to Momora AI', 'Your personalized pregnancy care companion is ready.', 'Explore your dashboard, track health vitals, and connect with your maternity team.', 'WEEKLY_DISCOVERY', 1, 0, '2026-10-30 08:49:20'),
(10, 10, 'Welcome to Momora AI', 'Your personalized pregnancy care companion is ready.', 'Explore your dashboard, track health vitals, and connect with your maternity team.', 'WEEKLY_DISCOVERY', 1, 0, '2026-11-04 05:22:43'),
(11, 5, 'Your appointment is confirmed', 'Dr Amelia Chen · 05 Nov 2026, 10:30', 'Willow Maternity Clinic · In person', 'APPOINTMENT', 0, 1, '2026-11-04 06:51:48'),
(12, 5, 'Doctor advice received', 'Dr Amelia Chen replied: \"Heartburn care plan\"', 'View guidance from Dr Amelia Chen', 'REPORT_REVIEW', 1, 0, '2026-11-04 07:02:31');

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_plans`
--

DROP TABLE IF EXISTS `nutrition_plans`;
CREATE TABLE `nutrition_plans` (
  `id` int(11) NOT NULL,
  `trimester` int(11) NOT NULL,
  `trimester_title` varchar(255) NOT NULL,
  `featured_meal_category` varchar(100) NOT NULL,
  `featured_meal_title` varchar(150) NOT NULL,
  `featured_meal_details` text NOT NULL,
  `priorities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`priorities`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nutrition_plans`
--

INSERT INTO `nutrition_plans` (`id`, `trimester`, `trimester_title`, `featured_meal_category`, `featured_meal_title`, `featured_meal_details`, `priorities`, `created_at`) VALUES
(1, 2, 'Balanced ideas for your second trimester.', 'LUNCH INSPIRATION', 'Chickpea & quinoa bowl', '25 min ?? Plant-based ?? Protein & fiber', '[{\"id\":1,\"title\":\"Iron-rich foods\",\"subtitle\":\"Lentils, beans and well-cooked lean meats.\",\"iconType\":\"IRON\"},{\"id\":2,\"title\":\"Calcium & protein\",\"subtitle\":\"Pasteurized yogurt or fortified alternatives.\",\"iconType\":\"CALCIUM\"},{\"id\":3,\"title\":\"Keep water nearby\",\"subtitle\":\"Sip regularly; adapt to your care plan.\",\"iconType\":\"HYDRATION\"}]', '2026-10-29 08:33:18');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `reset_token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `email`, `reset_token`, `expires_at`, `is_used`, `created_at`) VALUES
(1, 5, 'maya.patel@example.com', '14dac376280ea91c93569bb3a8355beaf6975e87b6e292b8f688b0b5f6800d8a', '2026-11-03 11:07:03', 1, '2026-11-03 09:07:03'),
(2, 3, 'amelia.chen@willow.example', '763c41a2867ec0dba4a3c290a5082b8eaa92bc4fde2323b9b80f183c8dbe0f15', '2026-11-03 11:07:03', 0, '2026-11-03 09:07:03'),
(3, 8, 'priya.sharma@example.com', 'e7932f5418fcdff57d5cb35aff98284dfa0f0b04a299057bd94b4c21d9a923f6', '2026-11-04 07:25:08', 0, '2026-11-04 05:25:08');

-- --------------------------------------------------------

--
-- Table structure for table `pregnancy_details`
--

DROP TABLE IF EXISTS `pregnancy_details`;
CREATE TABLE `pregnancy_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_menstrual_period` date DEFAULT NULL,
  `estimated_due_date` date NOT NULL,
  `gestational_weeks` int(11) NOT NULL DEFAULT 24,
  `gestational_days` int(11) NOT NULL DEFAULT 3,
  `trimester` int(11) NOT NULL DEFAULT 2,
  `next_visit_date` datetime DEFAULT NULL,
  `next_visit_clinic` varchar(150) DEFAULT 'Willow Maternity Clinic',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pregnancy_details`
--

INSERT INTO `pregnancy_details` (`id`, `user_id`, `last_menstrual_period`, `estimated_due_date`, `gestational_weeks`, `gestational_days`, `trimester`, `next_visit_date`, `next_visit_clinic`, `created_at`, `updated_at`) VALUES
(1, 5, '2026-05-09', '2027-02-13', 24, 3, 2, '2026-10-29 10:30:00', 'Willow Clinic', '2026-10-29 08:33:18', '2026-10-29 08:33:18'),
(2, 6, '2026-05-14', '2027-02-18', 24, 0, 2, NULL, 'Willow Maternity Clinic', '2026-10-29 08:56:42', '2026-10-29 08:56:42'),
(3, 7, '2026-05-15', '2027-02-19', 24, 0, 2, NULL, 'Willow Maternity Clinic', '2026-10-30 03:42:00', '2026-10-30 03:42:00'),
(4, 8, '2026-05-15', '2027-02-19', 24, 0, 2, NULL, 'Willow Maternity Clinic', '2026-10-30 06:57:46', '2026-10-30 06:57:46'),
(5, 9, '2026-05-15', '2027-02-19', 24, 0, 2, NULL, 'Willow Maternity Clinic', '2026-10-30 08:49:20', '2026-10-30 08:49:20'),
(6, 10, '2026-05-20', '2027-02-24', 24, 0, 2, NULL, 'Willow Maternity Clinic', '2026-11-04 05:22:43', '2026-11-04 05:22:43');

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

DROP TABLE IF EXISTS `profiles`;
CREATE TABLE `profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_code` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(30) DEFAULT NULL,
  `blood_group` varchar(15) DEFAULT 'O positive',
  `address` text DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_care_team_sharing_on` tinyint(1) NOT NULL DEFAULT 1,
  `assigned_doctor_id` int(11) DEFAULT NULL,
  `reminder_summary` varchar(255) DEFAULT 'Medication and appointment alerts',
  `clinical_background` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `user_id`, `patient_code`, `phone`, `date_of_birth`, `gender`, `blood_group`, `address`, `profile_photo`, `is_care_team_sharing_on`, `assigned_doctor_id`, `reminder_summary`, `clinical_background`, `updated_at`) VALUES
(1, 1, NULL, '9876543210', '2004-05-15', 'Female', 'O positive', 'Chennai,Tamilnadu', NULL, 1, NULL, 'Medication and appointment alerts', NULL, '2026-10-29 04:08:20'),
(3, 5, 'P-13042', '+1 (555) 234-9999', '1996-08-25', 'Female', 'A positive', '742 Evergreen Terrace, Springfield', 'uploads/profiles/profile_5_1793691116_0d7590b4.jpg', 1, 3, 'Daily prenatal vitamins and weekly checkup', 'Healthy second trimester, routine follow-up', '2026-11-04 06:45:13'),
(4, 6, 'P-10006', NULL, '1998-08-20', 'Female', 'O positive', NULL, NULL, 1, 3, 'Medication and appointment alerts', NULL, '2026-10-29 08:56:42'),
(5, 7, 'P-10007', NULL, '1997-05-14', 'Female', 'O positive', NULL, NULL, 1, 3, 'Medication and appointment alerts', NULL, '2026-10-30 03:42:00'),
(7, 8, 'P-10008', NULL, '1998-07-15', 'Female', 'O positive', NULL, NULL, 1, 3, 'Medication and appointment alerts', NULL, '2026-10-30 06:57:46'),
(8, 9, 'P-10009', NULL, '1997-05-14', 'Female', 'O positive', NULL, NULL, 1, 3, 'Medication and appointment alerts', NULL, '2026-10-30 08:49:20'),
(9, 2, 'P-10002', '9876543210', '2000-01-15', 'Female', 'B positive', 'Chennai, Tamil Nadu', NULL, 1, NULL, 'Medication and appointment alerts', NULL, '2026-11-03 07:05:34'),
(10, 10, 'P-10010', NULL, '2014-04-01', 'Female', 'O positive', NULL, NULL, 1, 3, 'Medication and appointment alerts', NULL, '2026-11-04 05:22:43');

-- --------------------------------------------------------

--
-- Table structure for table `symptom_logs`
--

DROP TABLE IF EXISTS `symptom_logs`;
CREATE TABLE `symptom_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `symptom_name` varchar(150) NOT NULL,
  `severity` enum('MILD','MODERATE','SEVERE') NOT NULL DEFAULT 'MILD',
  `started_text` varchar(100) NOT NULL,
  `duration_text` varchar(100) NOT NULL,
  `ai_guidance` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `symptom_logs`
--

INSERT INTO `symptom_logs` (`id`, `user_id`, `symptom_name`, `severity`, `started_text`, `duration_text`, `ai_guidance`, `created_at`) VALUES
(1, 5, 'Mild fatigue', 'MILD', 'Morning', '1 hour', 'Smaller meals and sitting upright after eating may help. Speak with your maternity team if it persists, worsens, or worries you.', '2026-10-29 09:18:51'),
(2, 9, 'Heartburn after meals', 'MILD', '04 Nov, 02:04', 'About 20 min', 'Smaller meals and sitting upright after eating may help. Speak with your maternity team if it persists, worsens, or worries you.', '2026-11-04 03:06:01'),
(3, 5, 'Heartburn after meals', 'MILD', 'Today, 13:00', 'About 25 min', 'Smaller meals and sitting upright after eating may help. Speak with your maternity team if it persists, worsens, or worries you.', '2026-11-04 06:50:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('patient','doctor','admin') NOT NULL DEFAULT 'patient',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Test User', 'test@example.com', '$2y$10$Yq.0OkYjS5c/2U4A04ZloOjNj.bRI.TinYwqjnm6dKfCuKFjNQR16', 'patient', 1, '2026-10-28 07:26:57', '2026-10-29 08:37:10'),
(2, 'Nidha Test', 'nidha.test@example.com', '$2y$10$Yq.0OkYjS5c/2U4A04ZloOjNj.bRI.TinYwqjnm6dKfCuKFjNQR16', 'patient', 1, '2026-10-29 05:04:54', '2026-10-29 08:37:10'),
(3, 'Dr Amelia Chen', 'amelia.chen@willow.example', '$2y$10$Yq.0OkYjS5c/2U4A04ZloOjNj.bRI.TinYwqjnm6dKfCuKFjNQR16', 'doctor', 1, '2026-10-29 08:33:18', '2026-11-05 03:41:00'),
(4, 'Alex Morgan', 'admin@willow.example', '$2y$10$Yq.0OkYjS5c/2U4A04ZloOjNj.bRI.TinYwqjnm6dKfCuKFjNQR16', 'admin', 1, '2026-10-29 08:33:18', '2026-10-29 08:37:10'),
(5, 'Maya Patel Sharma', 'maya.patel@example.com', '$2y$10$cCExigysKGS1Zcp/a5Vdg.RM6nPrTXf/KlGn4Mt.TypleccoZWP.W', 'patient', 1, '2026-10-29 08:33:18', '2026-11-05 03:41:11'),
(6, 'Sarah Jenkins', 'sarah.j@example.com', '$2y$10$Hxzff3yUEzz/uR.awZViEe48iMfZ/AkV8yXLtKXF0KagLqrt.fq.m', 'patient', 1, '2026-10-29 08:56:42', '2026-10-29 08:56:42'),
(7, 'Nidha', 'nidha@example.com', '$2y$10$IJoAKbLmzM8BFNnMiPwDIupy/RSI3jaXDdmSSMZYteodeOD1j7s6q', 'patient', 1, '2026-10-30 03:42:00', '2026-10-30 03:42:00'),
(8, 'Priya Sharma', 'priya.sharma@example.com', '$2y$10$/etgwQDOmIEnMXg2wvAi8e.Heckto/eqkx0MgRnNnZFCMoIhnQt.K', 'patient', 1, '2026-10-30 06:57:46', '2026-10-30 06:57:46'),
(9, 'Nidha Fathima', 'nidha@gmail.com', '$2y$10$FiU0wOU8m8OcfWD4S/oRMuGScXUT9Yoo3Bwkcyd7mJj3kBE0.Ac3.', 'patient', 1, '2026-10-30 08:49:20', '2026-11-04 03:05:05'),
(10, 'Tabassum', 'tabassum@example.com', '$2y$10$kME.UMCtdy.TFU9XvbF98.xxsfHJg7O8Xdfr5KQ0m85NJSvr9e6uO', 'patient', 1, '2026-11-04 05:22:43', '2026-11-04 05:22:43'),
(11, 'Dr Ethan Walker', 'ethan.walker@willow.example', '$2y$10$jJNvj1ClmfVsH5HQtR3FPOnNsliwNcY5XXKxL9A1uKK7lGSZQ0t2q', 'doctor', 1, '2026-11-04 07:03:27', '2026-11-04 07:03:27');

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

DROP TABLE IF EXISTS `user_tokens`;
CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_tokens`
--

INSERT INTO `user_tokens` (`id`, `user_id`, `token`, `device_info`, `expires_at`, `created_at`) VALUES
(2, 3, '29716f409c9287e924141c9e42b651aeab05dcdf3a0f769c31ac04f583e5a5d2', 'curl/8.9.1', '2026-11-28 09:52:01', '2026-10-29 08:52:01'),
(3, 4, '38f67829b83ed1294758ed39e88d63e3f0dfaf7a7c7dbde59b20684b8fe6291a', 'curl/8.9.1', '2026-11-28 09:55:54', '2026-10-29 08:55:54'),
(4, 6, 'f255e69d32019bed0409b0cdecee6cc7f20cb9d538babec24d766b334d28715f', 'curl/8.9.1', '2026-11-28 09:56:42', '2026-10-29 08:56:42'),
(6, 3, '3b66afe82f1c8ea14d92636f377889839a0482d9dbf616e7d046baf558ffe5a0', 'curl/8.9.1', '2026-11-28 10:18:51', '2026-10-29 09:18:51'),
(7, 4, 'c0acd39e1915333ae2c763a8242f8e18b36f17b85b0493bbd2212a625d75f36b', 'curl/8.9.1', '2026-11-28 10:18:51', '2026-10-29 09:18:51'),
(8, 3, 'a4a78d66fdc01c66322de9f063d352369230548225ecf8d33a1d106f298f461e', 'curl/8.9.1', '2026-11-28 10:19:02', '2026-10-29 09:19:02'),
(9, 4, '0bf8abde7b270fdeb2fefb4efb4ab7e6722ea45c2b7a986ab0abd1f3f0bba02b', 'curl/8.9.1', '2026-11-28 10:19:03', '2026-10-29 09:19:03'),
(10, 7, '8b5ac9af4c7eec2a5f324ad4260ec3c96f34af387b0109306608a37850ee83d2', 'okhttp/3.14.9', '2026-11-29 04:42:00', '2026-10-30 03:42:00'),
(12, 4, '6427a0b27e6643323881aef76716928d7be26bea041b2e6e514c3b70b18442ee', 'PostmanRuntime/2.12.2', '2026-11-29 07:57:10', '2026-10-30 06:57:10'),
(13, 8, 'ac50a1a142743816c23a8b642a8b5daa09f921006c43385f9b0121d70f14f0ca', 'PostmanRuntime/2.12.2', '2026-11-29 07:57:46', '2026-10-30 06:57:46'),
(14, 8, '9afded2b10151aaa4a97babc7da713b2838578527aab3c81aa9bcfbc4aa40dbb', 'okhttp/3.14.9', '2026-11-29 07:58:45', '2026-10-30 06:58:45'),
(15, 9, '92a06761684def2c0caa8a29d24498e2784741cbb82c7a6d8cc31d74ac6c7ce4', 'okhttp/3.14.9', '2026-11-29 09:49:20', '2026-10-30 08:49:20'),
(16, 9, 'c8b061926115dcb668f2dc8b711d98bf53602225ca3f6d4caacfc692d3cf1ea4', 'okhttp/3.14.9', '2026-12-02 09:34:37', '2026-11-02 08:34:37'),
(18, 5, 'a88d3f0befb6e5001cf9e4e97f7b03593d61f4ec7f870102c69ea7987e8d77d9', 'Android App', '2026-12-03 10:07:03', '2026-11-03 09:07:03'),
(19, 4, '3fb19e5da66f4420cfd7d586d71af756163b69d0b84415a780d75dfbd9cd5a5d', 'Android App', '2026-12-03 10:07:03', '2026-11-03 09:07:03'),
(20, 3, '11b26f836a15fdd3563a72114741d71c545d5e45e7caa4ede12e9a68d33ff2bf', 'Android App', '2026-12-03 10:07:03', '2026-11-03 09:07:03'),
(21, 9, 'da17b93036dab8161dcf8a6338cfe1b525c99c8ca7ea7ac3c3c1d6fec557c583', 'okhttp/3.14.9', '2026-12-04 03:58:56', '2026-11-04 02:58:56'),
(22, 5, 'dcb78d7dcc50e90272e6fe63bb35d1bd642d34ed9503700084df1a17a7de9690', 'PostmanRuntime/2.12.2', '2026-12-04 06:11:11', '2026-11-04 05:11:11'),
(23, 3, 'e74c2e7301ebd517f9172c0bea0af432c8b9d6fa3a017f7f4a983ad8c2a12bf0', 'PostmanRuntime/2.12.2', '2026-12-04 06:17:18', '2026-11-04 05:17:18'),
(24, 5, 'fbbd376eeba74027cd3a3b0fede76fd90ae3097e2ecebf99794146f1a564116b', 'PostmanRuntime/2.12.2', '2026-12-04 06:17:32', '2026-11-04 05:17:32'),
(26, 10, 'bd3026321519c9b98d44d7204e5369417d7239dce2ecdd1b02e35ea0edb8415d', 'PostmanRuntime/2.12.2', '2026-12-04 06:22:43', '2026-11-04 05:22:43'),
(27, 9, '94f1c6d2e7ecb3017e64b5dc66d4e56693d2e0f773ad0adb372f39b1a82f94e8', 'okhttp/3.14.9', '2026-12-05 04:32:51', '2026-11-05 03:32:51'),
(28, 4, '86ada0ef53961f7692db68e4d5dc3026d7528b97e3b747824856d4e7d4139851', 'okhttp/3.14.9', '2026-12-05 04:36:58', '2026-11-05 03:36:58'),
(29, 3, 'f42e20e37e532797aef1cfa97b76ad7c9c68787e670a7372d99cbd4c70bf1b6c', 'PostmanRuntime/2.14.0', '2026-12-05 04:46:26', '2026-11-05 03:46:26'),
(30, 3, 'e5a0530c90409075a6dcb6324c3697f5de47c0bfb35edf33ecc5123aa42efb2a', 'okhttp/3.14.9', '2026-12-05 04:48:31', '2026-11-05 03:48:31');

-- --------------------------------------------------------

--
-- Table structure for table `weight_logs`
--

DROP TABLE IF EXISTS `weight_logs`;
CREATE TABLE `weight_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `weight_kg` decimal(5,2) NOT NULL,
  `baseline_weight_kg` decimal(5,2) DEFAULT 59.00,
  `notes` varchar(255) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weight_logs`
--

INSERT INTO `weight_logs` (`id`, `user_id`, `weight_kg`, `baseline_weight_kg`, `notes`, `recorded_at`) VALUES
(1, 5, 61.20, 59.00, NULL, '2026-09-15 02:30:00'),
(2, 5, 62.50, 59.00, NULL, '2026-09-29 02:30:00'),
(3, 5, 63.80, 59.00, NULL, '2026-10-20 02:30:00'),
(4, 5, 64.20, 59.00, NULL, '2026-10-27 02:40:00'),
(5, 5, 64.50, 59.00, NULL, '2026-10-29 18:30:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`);

--
-- Indexes for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_user` (`user_id`,`created_at`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appts_patient` (`patient_id`),
  ADD KEY `idx_appts_doctor` (`doctor_id`);

--
-- Indexes for table `baby_development`
--
ALTER TABLE `baby_development`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `week` (`week`);

--
-- Indexes for table `blood_pressure_logs`
--
ALTER TABLE `blood_pressure_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bp_user_date` (`user_id`,`recorded_at`);

--
-- Indexes for table `doctor_advice`
--
ALTER TABLE `doctor_advice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `health_vitals`
--
ALTER TABLE `health_vitals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reports_user` (`user_id`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`,`is_read`);

--
-- Indexes for table `nutrition_plans`
--
ALTER TABLE `nutrition_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trimester` (`trimester`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_resets_token` (`reset_token`),
  ADD KEY `idx_resets_email` (`email`);

--
-- Indexes for table `pregnancy_details`
--
ALTER TABLE `pregnancy_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `symptom_logs`
--
ALTER TABLE `symptom_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_symptoms_user` (`user_id`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_tokens_token` (`token`),
  ADD KEY `idx_tokens_user` (`user_id`);

--
-- Indexes for table `weight_logs`
--
ALTER TABLE `weight_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_weight_user_date` (`user_id`,`recorded_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `baby_development`
--
ALTER TABLE `baby_development`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blood_pressure_logs`
--
ALTER TABLE `blood_pressure_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `doctor_advice`
--
ALTER TABLE `doctor_advice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `health_vitals`
--
ALTER TABLE `health_vitals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `medical_reports`
--
ALTER TABLE `medical_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `nutrition_plans`
--
ALTER TABLE `nutrition_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pregnancy_details`
--
ALTER TABLE `pregnancy_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `symptom_logs`
--
ALTER TABLE `symptom_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `weight_logs`
--
ALTER TABLE `weight_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD CONSTRAINT `admin_settings_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  ADD CONSTRAINT `ai_chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blood_pressure_logs`
--
ALTER TABLE `blood_pressure_logs`
  ADD CONSTRAINT `blood_pressure_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_advice`
--
ALTER TABLE `doctor_advice`
  ADD CONSTRAINT `doctor_advice_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_advice_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD CONSTRAINT `doctor_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `health_vitals`
--
ALTER TABLE `health_vitals`
  ADD CONSTRAINT `health_vitals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD CONSTRAINT `medical_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medications`
--
ALTER TABLE `medications`
  ADD CONSTRAINT `medications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pregnancy_details`
--
ALTER TABLE `pregnancy_details`
  ADD CONSTRAINT `pregnancy_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `symptom_logs`
--
ALTER TABLE `symptom_logs`
  ADD CONSTRAINT `symptom_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weight_logs`
--
ALTER TABLE `weight_logs`
  ADD CONSTRAINT `weight_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
