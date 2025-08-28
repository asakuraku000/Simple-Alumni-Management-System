-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 10, 2025 at 10:12 AM
-- Server version: 5.6.38
-- PHP Version: 8.2.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nisu_alumni_system`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetBatchStatistics` (IN `batch_year` INT)   BEGIN
    SELECT 
        b.year,
        b.semester,
        b.graduation_date,
        COUNT(a.id) as total_graduates,
        c.name as college,
        p.name as program,
        COUNT(*) as program_graduates
    FROM batches b
    LEFT JOIN alumni a ON b.id = a.batch_id
    LEFT JOIN colleges c ON a.college_id = c.id
    LEFT JOIN programs p ON a.program_id = p.id
    WHERE b.year = batch_year
    GROUP BY b.year, b.semester, b.graduation_date, c.name, p.name
    ORDER BY c.name, p.name;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateAlumniSearchIndex` (IN `alumni_id` INT)   BEGIN
    DECLARE search_title VARCHAR(500);
    DECLARE search_content TEXT;
    DECLARE search_tags VARCHAR(1000);
    
    SELECT 
        CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name),
        CONCAT(COALESCE(bio, ''), ' ', c.name, ' graduate from batch ', b.year),
        CONCAT(LOWER(first_name), ' ', LOWER(COALESCE(middle_name, '')), ' ', LOWER(last_name), ' ', 
               LOWER(c.name), ' ', LOWER(p.name), ' ', b.year)
    INTO search_title, search_content, search_tags
    FROM alumni a
    JOIN colleges c ON a.college_id = c.id
    JOIN programs p ON a.program_id = p.id
    JOIN batches b ON a.batch_id = b.id
    WHERE a.id = alumni_id;
    
    INSERT INTO search_index (entity_type, entity_id, title, content, tags)
    VALUES ('alumni', alumni_id, search_title, search_content, search_tags)
    ON DUPLICATE KEY UPDATE 
        title = search_title,
        content = search_content,
        tags = search_tags,
        last_indexed = CURRENT_TIMESTAMP;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `GetEmploymentRate` (`program_id` INT) RETURNS DECIMAL(5,2) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE total_alumni INT;
    DECLARE employed_alumni INT;
    DECLARE employment_rate DECIMAL(5,2);
    
    SELECT COUNT(*) INTO total_alumni
    FROM alumni WHERE program_id = program_id;
    
    SELECT COUNT(DISTINCT a.id) INTO employed_alumni
    FROM alumni a
    JOIN alumni_employment ae ON a.id = ae.alumni_id AND ae.is_current = TRUE
    WHERE a.program_id = program_id;
    
    IF total_alumni > 0 THEN
        SET employment_rate = (employed_alumni / total_alumni) * 100;
    ELSE
        SET employment_rate = 0;
    END IF;
    
    RETURN employment_rate;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','admin','moderator') COLLATE utf8mb4_unicode_ci DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@nisu.edu.ph', '$2y$10$ONvR9Yp8dorL3GhBzjr62.daWUZwkGKc6VtDIiUBCpeUmu1iwpwQe', 'System Administrator', 'super_admin', 1, '2025-08-10 08:00:35', '2025-08-10 02:51:31', '2025-08-10 08:00:35'),
(2, 'moderator1', 'mod1@nisu.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Moderator', 'moderator', 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(3, 'admin2', 'admin2@nisu.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Administrator', 'admin', 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `alumni`
--

CREATE TABLE `alumni` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suffix` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `civil_status` enum('Single','Married','Divorced','Widowed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `present_address` text COLLATE utf8mb4_unicode_ci,
  `permanent_address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Philippines',
  `postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `college_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `gpa` decimal(4,2) DEFAULT NULL,
  `latin_honor` enum('Summa Cum Laude','Magna Cum Laude','Cum Laude') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `facebook_url` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin_url` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_url` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram_url` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alumni`
--

INSERT INTO `alumni` (`id`, `student_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `phone`, `birth_date`, `gender`, `civil_status`, `present_address`, `permanent_address`, `city`, `province`, `country`, `postal_code`, `college_id`, `program_id`, `batch_id`, `gpa`, `latin_honor`, `profile_picture`, `bio`, `facebook_url`, `linkedin_url`, `twitter_url`, `instagram_url`, `is_verified`, `is_active`, `password_hash`, `email_verified_at`, `last_login`, `created_at`, `updated_at`) VALUES
(1, '2020-001-BS-CS', 'Juan Carlos', 'Rivera', 'Dela Cruz', NULL, 'juan.delacruz@email.com', '+63 917 123 4567', '1998-05-15', 'Male', 'Married', '123 Rizal St., Poblacion', NULL, 'Iloilo City', 'Iloilo', 'Philippines', NULL, 3, 9, 8, 3.75, 'Cum Laude', NULL, 'Software developer passionate about web technologies and mobile applications.', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, '2025-08-10 02:51:31', '2025-08-10 03:23:46'),
(2, '2020-002-BS-IT', 'Maria Isabel', 'Santos', 'Garcia', NULL, 'maria.garcia@email.com', '+63 918 234 5678', '1999-03-22', 'Female', 'Single', '456 Burgos Ave., La Paz', NULL, 'Iloilo City', 'Iloilo', 'Philippines', NULL, 6, 17, 1, 3.85, 'Magna Cum Laude', NULL, 'IT professional specializing in database administration and system analysis.', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(3, '2020-003-BS-CE', 'Robert', 'Miguel', 'Torres', NULL, 'robert.torres@email.com', '+63 919 345 6789', '1997-11-08', 'Male', 'Married', '789 Mabini St., Mandurriao', NULL, 'Iloilo City', 'Iloilo', 'Philippines', NULL, 2, 5, 1, 3.65, 'Cum Laude', NULL, 'Civil engineer working on infrastructure development projects.', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(4, '2021-001-BS-BA', 'Anna Marie', 'Cruz', 'Reyes', NULL, 'anna.reyes@email.com', '+63 920 456 7890', '1998-07-12', 'Female', 'Single', '321 Del Pilar St., Molo', NULL, 'Iloilo City', 'Iloilo', 'Philippines', NULL, 3, 8, 3, 3.90, 'Magna Cum Laude', NULL, 'Business consultant and entrepreneur with expertise in marketing strategy.', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(5, '2021-002-BSED-ENG', 'Michael', 'Jose', 'Fernandez', NULL, 'michael.fernandez@email.com', '+63 921 567 8901', '1999-01-25', 'Male', 'Single', '654 Luna St., City Proper', NULL, 'Iloilo City', 'Iloilo', 'Philippines', NULL, 4, 12, 3, 3.70, 'Cum Laude', NULL, 'High school English teacher passionate about literature and creative writing.', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(6, '2022-001-BS-AGRI', 'Catherine', 'Mae', 'Villanueva', NULL, 'catherine.villanueva@email.com', '+63 922 678 9012', '1998-09-18', 'Female', 'Married', '987 Bonifacio Dr., Jaro', NULL, 'Iloilo City', 'Iloilo', 'Philippines', NULL, 5, 14, 5, 3.80, 'Magna Cum Laude', NULL, 'Agricultural extension worker focusing on sustainable farming practices.', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(7, '2022-002-BS-ACC', 'David', 'Paul', 'Lopez', NULL, 'david.lopez@email.com', '+63 923 789 0123', '1997-12-03', 'Male', 'Single', '147 Quezon St., Arevalo', NULL, 'Iloilo City', 'Iloilo', 'Philippines', NULL, 3, 9, 5, 3.95, 'Summa Cum Laude', NULL, 'Certified Public Accountant working for a multinational corporation.', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(8, '2023-001-BS-BIO', 'Sarah', 'Joy', 'Mendoza', NULL, 'sarah.mendoza@email.com', '+63 924 890 1234', '1999-04-30', 'Female', 'Single', '258 Jalandoni St., Lapuz', NULL, 'Iloilo City', 'Iloilo', 'Philippines', NULL, 1, 1, 7, 3.88, 'Magna Cum Laude', NULL, 'Research scientist working in biotechnology and pharmaceutical research.', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(9, '2023-002-BS-EE', 'James', 'Andrew', 'Ramos', NULL, 'james.ramos@email.com', '+63 925 901 2345', '1998-06-14', 'Male', 'Single', '369 Iznart St., City Proper', NULL, 'Iloilo City', 'Iloilo', 'Philippines', NULL, 2, 6, 7, 3.72, 'Cum Laude', NULL, 'Electrical engineer specializing in power systems and renewable energy.', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(10, '2024-001-BEED', 'Michelle', 'Rose', 'Aquino', NULL, 'michelle.aquino@email.com', '+63 926 012 3456', '1999-08-27', 'Female', 'Single', '741 Aldeguer St., City Proper', NULL, 'Iloilo City', 'Iloilo', 'Philippines', NULL, 4, 11, 9, 3.83, 'Magna Cum Laude', NULL, 'Elementary school teacher with a passion for early childhood education.', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(11, 'PAS-21-242', 'Lina Mae', NULL, 'Balahay', NULL, 'asakuraku000@gmail.com', '09955115167', '2025-08-10', 'Female', 'Married', 'Here', 'meow', 'Naga', 'Cam Sur', 'Philippines', NULL, 3, 9, 11, 1.20, 'Magna Cum Laude', 'uploads/alumni/alumni_68982e25cc23c.png', 'Masarap', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, '2025-08-10 05:17:23', '2025-08-10 05:29:09');

-- --------------------------------------------------------

--
-- Table structure for table `alumni_education`
--

CREATE TABLE `alumni_education` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `institution_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `degree` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_of_study` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT '0',
  `gpa` decimal(4,2) DEFAULT NULL,
  `honors` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alumni_employment`
--

CREATE TABLE `alumni_employment` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `company_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employment_type` enum('Full-time','Part-time','Contract','Freelance','Self-employed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `industry` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary_range` enum('Below 15,000','15,000-25,000','25,001-35,000','35,001-50,000','50,001-75,000','75,001-100,000','Above 100,000') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_description` text COLLATE utf8mb4_unicode_ci,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT '0',
  `company_address` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alumni_employment`
--

INSERT INTO `alumni_employment` (`id`, `alumni_id`, `company_name`, `job_title`, `employment_type`, `industry`, `salary_range`, `job_description`, `start_date`, `end_date`, `is_current`, `company_address`, `created_at`, `updated_at`) VALUES
(1, 1, 'TechStart Solutions Inc.', 'Junior Software Developer', 'Full-time', 'Information Technology', '25,001-35,000', NULL, '2020-06-01', NULL, 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(2, 2, 'DataCore Philippines', 'Database Administrator', 'Full-time', 'Information Technology', '35,001-50,000', NULL, '2020-08-15', NULL, 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(3, 3, 'BuildRight Construction Corp.', 'Project Engineer', 'Full-time', 'Construction', '35,001-50,000', NULL, '2020-04-20', NULL, 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(4, 4, 'MarketPro Consulting', 'Business Analyst', 'Full-time', 'Business Services', '25,001-35,000', NULL, '2021-05-10', NULL, 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(5, 5, 'Iloilo National High School', 'English Teacher', 'Full-time', 'Education', '25,001-35,000', NULL, '2021-06-01', NULL, 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(6, 6, 'Department of Agriculture - Region 6', 'Agricultural Technologist', 'Full-time', 'Government', '25,001-35,000', NULL, '2022-07-01', NULL, 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(7, 7, 'SGV & Co.', 'Junior Auditor', 'Full-time', 'Professional Services', '25,001-35,000', NULL, '2022-05-15', NULL, 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(8, 8, 'BioMed Research Institute', 'Research Associate', 'Full-time', 'Research & Development', '25,001-35,000', NULL, '2023-04-01', NULL, 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(9, 9, 'Panay Electric Company', 'Electrical Engineer', 'Full-time', 'Utilities', '35,001-50,000', NULL, '2023-05-20', NULL, 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(10, 10, 'Iloilo Elementary School', 'Grade 3 Teacher', 'Full-time', 'Education', '25,001-35,000', NULL, '2024-06-01', NULL, 1, NULL, '2025-08-10 02:51:31', '2025-08-10 02:51:31');

-- --------------------------------------------------------

--
-- Stand-in structure for view `alumni_summary`
-- (See below for the actual view)
--
CREATE TABLE `alumni_summary` (
`id` int(11)
,`full_name` varchar(152)
,`student_id` varchar(20)
,`email` varchar(100)
,`college` varchar(100)
,`program` varchar(150)
,`graduation_year` int(11)
,`graduation_semester` enum('1st','2nd','Summer')
,`current_employer` varchar(150)
,`current_position` varchar(100)
,`registration_date` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `announcement_type` enum('General','Event','Job','Achievement','Memorial','Urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `priority` enum('Low','Normal','High','Critical') COLLATE utf8mb4_unicode_ci DEFAULT 'Normal',
  `featured_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachments` text COLLATE utf8mb4_unicode_ci,
  `target_batches` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated batch IDs',
  `target_programs` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated program IDs',
  `target_colleges` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated college IDs',
  `is_public` tinyint(1) DEFAULT '1',
  `status` enum('Draft','Published','Archived') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `view_count` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `admin_id`, `title`, `content`, `excerpt`, `announcement_type`, `priority`, `featured_image`, `attachments`, `target_batches`, `target_programs`, `target_colleges`, `is_public`, `status`, `published_at`, `expires_at`, `view_count`, `created_at`, `updated_at`) VALUES
(1, 1, 'NISU Alumni Homecoming 2024', 'We are excited to announce the NISU Alumni Homecoming 2024! Join us for a weekend of reconnection, celebration, and memories. The event will feature various activities including a grand alumni dinner, campus tours, and college-specific gatherings. Registration is now open on our website. Early bird rates available until November 30, 2024.', 'Join us for NISU Alumni Homecoming 2024 - a weekend of reconnection and celebration!', 'Event', 'High', NULL, NULL, NULL, NULL, NULL, 1, 'Published', '2024-10-01 01:00:00', NULL, 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(2, 1, 'Job Opportunities at TechStart Solutions', 'TechStart Solutions Inc. is looking for talented NISU graduates to join their expanding team. Positions available: Software Developers, System Analysts, and Project Managers. Competitive salary and benefits package. Interested alumni may send their resumes to hr@techstart.com.ph', 'TechStart Solutions hiring NISU graduates - Software Developers, Analysts, and Project Managers needed.', 'Job', 'Normal', NULL, NULL, NULL, NULL, NULL, 1, 'Published', '2024-09-15 06:30:00', NULL, 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(4, 1, 'In Memoriam: Dr. Elena Rodriguez', 'It is with heavy hearts that we announce the passing of Dr. Elena Rodriguez, former Dean of the College of Arts and Sciences and beloved faculty member for over 30 years. Dr. Rodriguez was instrumental in shaping the academic excellence of our institution. Memorial services will be held on August 25, 2024, at 10:00 AM in the University Chapel.', 'Remembering Dr. Elena Rodriguez, former CAS Dean and beloved faculty member.', 'Memorial', 'Critical', NULL, NULL, NULL, NULL, NULL, 1, 'Published', '2024-08-10 08:00:00', NULL, 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(5, 3, 'NISU Alumni Achievement Recognition', 'We are proud to recognize our outstanding alumni who have made significant contributions to their respective fields. This month, we celebrate Maria Garcia (BS-IT 2020) for her innovative mobile app that won the National ICT Awards, and David Lopez (BS-ACC 2022) for being the youngest CPA to pass the board exams with flying colors.', 'Celebrating outstanding alumni achievements in technology and accounting.', 'Achievement', 'Normal', NULL, NULL, NULL, NULL, NULL, 1, 'Published', '2024-07-25 03:30:00', NULL, 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_views`
--

CREATE TABLE `announcement_views` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `alumni_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `semester` enum('1st','2nd','Summer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `graduation_date` date NOT NULL,
  `theme` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `total_graduates` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `year`, `semester`, `graduation_date`, `theme`, `description`, `total_graduates`, `created_at`, `updated_at`) VALUES
(1, 2020, '1st', '2020-03-15', 'Excellence in Innovation', NULL, 450, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(2, 2020, '2nd', '2020-07-15', 'Unity in Diversity', NULL, 380, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(3, 2021, '1st', '2021-03-15', 'Resilience and Hope', NULL, 520, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(4, 2021, '2nd', '2021-07-15', 'Digital Transformation', NULL, 410, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(5, 2022, '1st', '2022-03-15', 'Sustainability and Progress', NULL, 480, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(6, 2022, '2nd', '2022-07-15', 'Innovation and Leadership', NULL, 395, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(7, 2023, '1st', '2023-03-15', 'Building Tomorrow', NULL, 510, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(8, 2023, '2nd', '2023-07-15', 'Excellence Redefined', NULL, 425, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(9, 2024, '1st', '2024-03-15', 'Future Ready Graduates', NULL, 535, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(10, 2024, '2nd', '2024-07-15', 'Empowering Change', NULL, 445, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(11, 2025, '2nd', '2025-08-10', 'I love pussy', 'We love Lina Pussy', 415, '2025-08-10 05:16:18', '2025-08-10 05:16:18');

-- --------------------------------------------------------

--
-- Stand-in structure for view `batch_statistics`
-- (See below for the actual view)
--
CREATE TABLE `batch_statistics` (
`id` int(11)
,`year` int(11)
,`semester` enum('1st','2nd','Summer')
,`graduation_date` date
,`actual_graduates` bigint(21)
,`college` varchar(100)
,`summa_graduates` bigint(21)
,`magna_graduates` bigint(21)
,`cum_laude_graduates` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `id` int(11) NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `dean_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `colleges`
--

INSERT INTO `colleges` (`id`, `code`, `name`, `description`, `dean_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CAS', 'College of Arts and Sciences', 'Liberal arts, sciences, and mathematics programs', 'Dr. Maria Santos', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(2, 'COE', 'College of Engineering', 'Engineering and technology programs', 'Dr. Jose Rodriguez', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(3, 'CBA', 'College of Business Administration', 'Business, management, and entrepreneurship programs', 'Dr. Ana Garcia', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(4, 'COEd', 'College of Education', 'Teacher education and pedagogy programs', 'Dr. Roberto Cruz', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(5, 'CAFS', 'College of Agriculture and Food Sciences', 'Agriculture, food technology, and environmental science', 'Dr. Carmen Dela Cruz', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(6, 'CIT', 'College of Information Technology', 'Computer science and information technology programs', 'Dr. Michael Tan', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(7, 'BSES', 'Environmental Science', 'poging pusa', 'Mingming', 1, '2025-08-10 03:37:28', '2025-08-10 03:37:28');

-- --------------------------------------------------------

--
-- Stand-in structure for view `employment_statistics`
-- (See below for the actual view)
--
CREATE TABLE `employment_statistics` (
`college` varchar(100)
,`program` varchar(150)
,`total_alumni` bigint(21)
,`employed_alumni` bigint(21)
,`employment_rate` decimal(26,2)
,`salary_range` enum('Below 15,000','15,000-25,000','25,001-35,000','35,001-50,000','50,001-75,000','75,001-100,000','Above 100,000')
,`count_in_range` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `event_type` enum('Reunion','Conference','Workshop','Social','Career Fair','Webinar','Other') COLLATE utf8mb4_unicode_ci DEFAULT 'Other',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Asia/Manila',
  `venue` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `is_online` tinyint(1) DEFAULT '0',
  `meeting_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_required` tinyint(1) DEFAULT '0',
  `registration_deadline` date DEFAULT NULL,
  `max_attendees` int(11) DEFAULT NULL,
  `registration_fee` decimal(10,2) DEFAULT '0.00',
  `featured_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gallery` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated image URLs',
  `status` enum('Draft','Published','Cancelled','Completed') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `admin_id`, `title`, `description`, `event_type`, `start_date`, `end_date`, `start_time`, `end_time`, `timezone`, `venue`, `address`, `is_online`, `meeting_link`, `registration_required`, `registration_deadline`, `max_attendees`, `registration_fee`, `featured_image`, `gallery`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'NISU Alumni Homecoming 2024', 'Annual homecoming celebration featuring dinner, awards ceremony, and networking sessions for all NISU alumni.', 'Reunion', '2024-12-14', '2024-12-15', '09:00:00', '21:00:00', 'Asia/Manila', 'NISU Main Campus', 'Northern Iloilo State University, Estancia, Iloilo', 0, NULL, 1, NULL, 500, 1500.00, NULL, NULL, 'Published', '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(2, 2, 'Career Development Workshop', 'Professional development workshop focusing on leadership skills and career advancement strategies.', 'Workshop', '2024-11-20', '2024-11-20', '13:00:00', '17:00:00', 'Asia/Manila', 'NISU Conference Hall', 'Northern Iloilo State University, Estancia, Iloilo', 0, NULL, 1, NULL, 100, 0.00, NULL, NULL, 'Published', '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(3, 1, 'Engineering Alumni Meetup', 'Quarterly meetup for College of Engineering alumni to discuss industry trends and networking opportunities.', 'Social', '2024-10-30', '2024-10-30', '18:00:00', '21:00:00', 'Asia/Manila', 'Iloilo Grand Hotel', 'Gen. Luna St., Iloilo City', 0, NULL, 1, NULL, 80, 800.00, NULL, NULL, 'Published', '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(4, 3, 'Online IT Seminar: Emerging Technologies', 'Virtual seminar discussing AI, blockchain, and other emerging technologies in the IT industry.', 'Webinar', '2024-11-05', '2024-11-05', '14:00:00', '16:00:00', 'Asia/Manila', 'Online', 'Zoom Meeting Platform', 0, NULL, 1, NULL, 200, 0.00, NULL, NULL, 'Published', '2025-08-10 02:51:31', '2025-08-10 02:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Registered','Confirmed','Attended','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Registered',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `payment_status` enum('Pending','Paid','Refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `photos`
--

CREATE TABLE `photos` (
  `id` int(11) NOT NULL,
  `album_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `caption` text COLLATE utf8mb4_unicode_ci,
  `taken_at` timestamp NULL DEFAULT NULL,
  `camera_info` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `view_count` int(11) DEFAULT '0',
  `upload_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `photos`
--

INSERT INTO `photos` (`id`, `album_id`, `admin_id`, `filename`, `original_filename`, `file_path`, `file_size`, `mime_type`, `width`, `height`, `title`, `description`, `caption`, `taken_at`, `camera_info`, `location`, `view_count`, `upload_date`) VALUES
(1, 1, 1, 'grad2024_001.jpg', 'graduation_ceremony_main.jpg', '/uploads/albums/1/grad2024_001.jpg', NULL, NULL, NULL, NULL, 'Graduation Ceremony Opening', 'Opening ceremony of March 2024 graduation', NULL, NULL, NULL, NULL, 0, '2025-08-10 02:51:31'),
(2, 1, 1, 'grad2024_002.jpg', 'valedictorian_speech.jpg', '/uploads/albums/1/grad2024_002.jpg', NULL, NULL, NULL, NULL, 'Valedictorian Speech', 'Class valedictorian delivering the graduation speech', NULL, NULL, NULL, NULL, 0, '2025-08-10 02:51:31'),
(3, 2, 1, 'homecoming2023_001.jpg', 'welcome_banner.jpg', '/uploads/albums/2/homecoming2023_001.jpg', NULL, NULL, NULL, NULL, 'Welcome Banner', 'Alumni homecoming welcome banner at main entrance', NULL, NULL, NULL, NULL, 0, '2025-08-10 02:51:31'),
(4, 3, 2, 'engweek2024_001.jpg', 'robotics_competition.jpg', '/uploads/albums/3/engweek2024_001.jpg', NULL, NULL, NULL, NULL, 'Robotics Competition', 'Students competing in the annual robotics competition', NULL, NULL, NULL, NULL, 0, '2025-08-10 02:51:31'),
(6, 3, 1, 'photo003.jpg', NULL, '/uploads/albums/pusa/photo003.jpg', NULL, NULL, NULL, NULL, 'kulas code', 'meow', 'alright', NULL, NULL, 'Nisu field', 0, '2025-08-10 03:52:02'),
(7, 2, 1, 'photo_20250810_060856_68981b582e87b.jpg', '1000666000.jpg', 'uploads/photos/events/event_/photo_20250810_060856_68981b582e87b.jpg', NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, 'Field 1', 0, '2025-08-10 04:08:56'),
(13, 5, 1, 'photo_20250810_100226_689852120362e.jpg', '1000667162.jpg', 'uploads/photos/batches/2025_2nd/photo_20250810_100226_689852120362e.jpg', NULL, NULL, NULL, NULL, 'Creative shot 3', 'Name: Prof Pusa', NULL, NULL, NULL, 'Davao Campus', 0, '2025-08-10 08:02:26'),
(12, 5, 1, 'photo_20250810_100225_68985211f3ab0.jpg', '1000667161.jpg', 'uploads/photos/batches/2025_2nd/photo_20250810_100225_68985211f3ab0.jpg', NULL, NULL, NULL, NULL, 'Creative shot 2', 'Name: Shadow Ball', NULL, NULL, NULL, 'Davao Campus', 0, '2025-08-10 08:02:26'),
(11, 5, 1, 'photo_20250810_100225_68985211e9d56.jpg', '1000667160.jpg', 'uploads/photos/batches/2025_2nd/photo_20250810_100225_68985211e9d56.jpg', NULL, NULL, NULL, NULL, 'Creative shot 1 ', 'Name: Kulas', NULL, NULL, NULL, 'Davao Campus', 0, '2025-08-10 08:02:25');

-- --------------------------------------------------------

--
-- Table structure for table `photo_albums`
--

CREATE TABLE `photo_albums` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `album_type` enum('Event','Batch','General','Achievement') COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `cover_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT '1',
  `view_count` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `photo_albums`
--

INSERT INTO `photo_albums` (`id`, `admin_id`, `title`, `description`, `album_type`, `cover_photo`, `event_id`, `batch_id`, `is_public`, `view_count`, `created_at`, `updated_at`) VALUES
(1, 1, 'Batch 2024 Graduation Ceremony', 'Official photos from the March 2024 graduation ceremony', 'Batch', NULL, NULL, 9, 1, 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(2, 1, 'Alumni Homecoming 2023', 'Memorable moments from the 2023 alumni homecoming celebration', 'Event', NULL, NULL, NULL, 1, 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(3, 2, 'Engineering Week 2024', 'Photos from the annual Engineering Week celebration', 'Event', NULL, NULL, NULL, 1, 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(4, 3, 'Campus Life Memories', 'Collection of campus life photos from various batches', 'General', NULL, NULL, NULL, 1, 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(5, 1, '2025 Pussycats ', 'meow', 'Batch', NULL, NULL, 11, 1, 0, '2025-08-10 07:59:37', '2025-08-10 07:59:37');

-- --------------------------------------------------------

--
-- Table structure for table `photo_tags`
--

CREATE TABLE `photo_tags` (
  `id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `x_coordinate` decimal(5,2) DEFAULT NULL,
  `y_coordinate` decimal(5,2) DEFAULT NULL,
  `width` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `degree_type` enum('Certificate','Diploma','Associate','Bachelor','Master','Doctorate') COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_years` decimal(3,1) DEFAULT '4.0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `college_id`, `code`, `name`, `degree_type`, `duration_years`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'BS-BIO', 'Bachelor of Science in Biology', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(2, 1, 'BS-CHEM', 'Bachelor of Science in Chemistry', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(3, 1, 'BS-MATH', 'Bachelor of Science in Mathematics', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(4, 1, 'AB-ENG', 'Bachelor of Arts in English', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(5, 2, 'BS-CE', 'Bachelor of Science in Civil Engineering', 'Bachelor', 5.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(6, 2, 'BS-EE', 'Bachelor of Science in Electrical Engineering', 'Bachelor', 5.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(7, 2, 'BS-ME', 'Bachelor of Science in Mechanical Engineering', 'Bachelor', 5.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(8, 3, 'BS-BA', 'Bachelor of Science in Business Administration', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(9, 3, 'BS-ACC', 'Bachelor of Science in Accountancy', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(10, 3, 'BS-ENTREP', 'Bachelor of Science in Entrepreneurship', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(11, 4, 'BEED', 'Bachelor of Elementary Education', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(12, 4, 'BSED-ENG', 'Bachelor of Secondary Education Major in English', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(13, 4, 'BSED-MATH', 'Bachelor of Secondary Education Major in Mathematics', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(14, 5, 'BS-AGRI', 'Bachelor of Science in Agriculture', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(15, 5, 'BS-FOOD', 'Bachelor of Science in Food Technology', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(16, 6, 'BS-CS', 'Bachelor of Science in Computer Science', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(17, 6, 'BS-IT', 'Bachelor of Science in Information Technology', 'Bachelor', 4.0, NULL, 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `search_index`
--

CREATE TABLE `search_index` (
  `id` int(11) NOT NULL,
  `entity_type` enum('alumni','announcement','event','photo','batch') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int(11) NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `tags` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `search_vector` text COLLATE utf8mb4_unicode_ci,
  `last_indexed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `search_index`
--

INSERT INTO `search_index` (`id`, `entity_type`, `entity_id`, `title`, `content`, `tags`, `search_vector`, `last_indexed`) VALUES
(1, 'alumni', 1, 'Juan Carlos Rivera Dela Cruz', 'Software developer passionate about web technologies and mobile applications. Computer Science graduate from batch 2020.', 'juan carlos dela cruz software developer computer science 2020', NULL, '2025-08-10 02:51:32'),
(2, 'alumni', 2, 'Maria Isabel Santos Garcia', 'IT professional specializing in database administration and system analysis. Information Technology graduate from batch 2020.', 'maria isabel garcia IT database administrator information technology 2020', NULL, '2025-08-10 02:51:32'),
(3, 'alumni', 3, 'Robert Miguel Torres', 'Civil engineer working on infrastructure development projects. Civil Engineering graduate from batch 2020.', 'robert torres civil engineer infrastructure construction 2020', NULL, '2025-08-10 02:51:32'),
(4, 'announcement', 1, 'NISU Alumni Homecoming 2024', 'We are excited to announce the NISU Alumni Homecoming 2024! Join us for a weekend of reconnection, celebration, and memories.', 'homecoming 2024 event reunion celebration', NULL, '2025-08-10 02:51:32'),
(5, 'announcement', 2, 'Job Opportunities at TechStart Solutions', 'TechStart Solutions Inc. is looking for talented NISU graduates to join their expanding team.', 'job opportunities techstart software developer analyst project manager', NULL, '2025-08-10 02:51:32'),
(6, 'event', 1, 'NISU Alumni Homecoming 2024', 'Annual homecoming celebration featuring dinner, awards ceremony, and networking sessions for all NISU alumni.', 'homecoming reunion alumni dinner awards networking', NULL, '2025-08-10 02:51:32');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` enum('string','integer','boolean','text') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'NISU Alumni System', 'string', 'Website name displayed in headers and titles', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(2, 'site_description', 'Northern Iloilo State University Alumni Information System', 'string', 'Site description for meta tags', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(3, 'university_address', 'Estancia, Iloilo, Philippines 5017', 'string', 'University official address', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(4, 'university_phone', '+63 (33) 331-9447', 'string', 'University contact number', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(5, 'university_email', 'info@nisu.edu.ph', 'string', 'University official email', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(6, 'alumni_registration_enabled', '1', 'string', 'Allow new alumni to register online', 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(7, 'max_upload_size', '10485760', 'string', 'Maximum file upload size in bytes (10MB)', 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(8, 'email_notifications', '1', 'string', 'Enable email notifications for announcements', 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(9, 'maintenance_mode', '0', 'string', 'Enable maintenance mode to disable public access', 0, '2025-08-10 02:51:31', '2025-08-10 07:24:43'),
(10, 'google_analytics_id', 'GA-XXXXXXXXXX', 'string', 'Google Analytics tracking ID', 0, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(11, 'social_facebook', 'https://facebook.com/NISUOfficial', 'string', 'Official Facebook page URL', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31'),
(12, 'social_twitter', 'https://twitter.com/NISUOfficial', 'string', 'Official Twitter account URL', 1, '2025-08-10 02:51:31', '2025-08-10 02:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `site_statistics`
--

CREATE TABLE `site_statistics` (
  `id` int(11) NOT NULL,
  `metric_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_value` bigint(20) NOT NULL,
  `date_recorded` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_statistics`
--

INSERT INTO `site_statistics` (`id`, `metric_name`, `metric_value`, `date_recorded`, `created_at`) VALUES
(1, 'total_alumni', 1250, '2025-08-10', '2025-08-10 02:51:32'),
(2, 'total_announcements', 45, '2025-08-10', '2025-08-10 02:51:32'),
(3, 'total_events', 12, '2025-08-10', '2025-08-10 02:51:32'),
(4, 'total_photos', 350, '2025-08-10', '2025-08-10 02:51:32'),
(5, 'monthly_visitors', 2500, '2025-07-10', '2025-08-10 02:51:32'),
(6, 'active_alumni', 890, '2025-08-10', '2025-08-10 02:51:32');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `alumni_id` int(11) DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text COLLATE utf8mb4_unicode_ci,
  `new_values` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `alumni_summary`
--
DROP TABLE IF EXISTS `alumni_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `alumni_summary`  AS SELECT `a`.`id` AS `id`, concat(`a`.`first_name`,' ',coalesce(`a`.`middle_name`,''),' ',`a`.`last_name`) AS `full_name`, `a`.`student_id` AS `student_id`, `a`.`email` AS `email`, `c`.`name` AS `college`, `p`.`name` AS `program`, `b`.`year` AS `graduation_year`, `b`.`semester` AS `graduation_semester`, `ae`.`company_name` AS `current_employer`, `ae`.`job_title` AS `current_position`, `a`.`created_at` AS `registration_date` FROM ((((`alumni` `a` join `colleges` `c` on((`a`.`college_id` = `c`.`id`))) join `programs` `p` on((`a`.`program_id` = `p`.`id`))) join `batches` `b` on((`a`.`batch_id` = `b`.`id`))) left join `alumni_employment` `ae` on(((`a`.`id` = `ae`.`alumni_id`) and (`ae`.`is_current` = 1)))) ;

-- --------------------------------------------------------

--
-- Structure for view `batch_statistics`
--
DROP TABLE IF EXISTS `batch_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `batch_statistics`  AS SELECT `b`.`id` AS `id`, `b`.`year` AS `year`, `b`.`semester` AS `semester`, `b`.`graduation_date` AS `graduation_date`, count(`a`.`id`) AS `actual_graduates`, `c`.`name` AS `college`, count((case when (`a`.`latin_honor` = 'Summa Cum Laude') then 1 end)) AS `summa_graduates`, count((case when (`a`.`latin_honor` = 'Magna Cum Laude') then 1 end)) AS `magna_graduates`, count((case when (`a`.`latin_honor` = 'Cum Laude') then 1 end)) AS `cum_laude_graduates` FROM ((`batches` `b` left join `alumni` `a` on((`b`.`id` = `a`.`batch_id`))) left join `colleges` `c` on((`a`.`college_id` = `c`.`id`))) GROUP BY `b`.`id`, `b`.`year`, `b`.`semester`, `b`.`graduation_date`, `c`.`name` ;

-- --------------------------------------------------------

--
-- Structure for view `employment_statistics`
--
DROP TABLE IF EXISTS `employment_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `employment_statistics`  AS SELECT `c`.`name` AS `college`, `p`.`name` AS `program`, count(distinct `a`.`id`) AS `total_alumni`, count(distinct `ae`.`id`) AS `employed_alumni`, round(((count(distinct `ae`.`id`) / count(distinct `a`.`id`)) * 100),2) AS `employment_rate`, `ae`.`salary_range` AS `salary_range`, count(0) AS `count_in_range` FROM (((`alumni` `a` join `colleges` `c` on((`a`.`college_id` = `c`.`id`))) join `programs` `p` on((`a`.`program_id` = `p`.`id`))) left join `alumni_employment` `ae` on(((`a`.`id` = `ae`.`alumni_id`) and (`ae`.`is_current` = 1)))) GROUP BY `c`.`name`, `p`.`name`, `ae`.`salary_range` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `alumni`
--
ALTER TABLE `alumni`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `idx_name` (`first_name`,`last_name`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_batch` (`batch_id`),
  ADD KEY `idx_program` (`program_id`),
  ADD KEY `idx_alumni_graduation` (`batch_id`,`college_id`,`program_id`),
  ADD KEY `idx_alumni_search_names` (`first_name`,`last_name`,`student_id`);
ALTER TABLE `alumni` ADD FULLTEXT KEY `idx_search` (`first_name`,`middle_name`,`last_name`,`student_id`);

--
-- Indexes for table `alumni_education`
--
ALTER TABLE `alumni_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alumni_education` (`alumni_id`);

--
-- Indexes for table `alumni_employment`
--
ALTER TABLE `alumni_employment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alumni_employment` (`alumni_id`),
  ADD KEY `idx_company` (`company_name`),
  ADD KEY `idx_employment_current` (`alumni_id`,`is_current`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`announcement_type`),
  ADD KEY `idx_published` (`published_at`),
  ADD KEY `idx_announcements_published` (`status`,`published_at`);
ALTER TABLE `announcements` ADD FULLTEXT KEY `idx_content_search` (`title`,`content`,`excerpt`);

--
-- Indexes for table `announcement_views`
--
ALTER TABLE `announcement_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcement_views` (`announcement_id`),
  ADD KEY `idx_alumni_views` (`alumni_id`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_batch` (`year`,`semester`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_event_dates` (`start_date`,`end_date`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_event_status` (`status`),
  ADD KEY `idx_events_dates` (`start_date`,`status`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`event_id`,`alumni_id`),
  ADD KEY `idx_event_registrations` (`event_id`),
  ADD KEY `idx_alumni_registrations` (`alumni_id`);

--
-- Indexes for table `photos`
--
ALTER TABLE `photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_album_photos` (`album_id`),
  ADD KEY `idx_photos_album` (`album_id`,`upload_date`);
ALTER TABLE `photos` ADD FULLTEXT KEY `idx_photo_search` (`title`,`description`,`caption`);

--
-- Indexes for table `photo_albums`
--
ALTER TABLE `photo_albums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `idx_album_type` (`album_type`);

--
-- Indexes for table `photo_tags`
--
ALTER TABLE `photo_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_photo_tag` (`photo_id`,`alumni_id`),
  ADD KEY `alumni_id` (`alumni_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `search_index`
--
ALTER TABLE `search_index`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`);
ALTER TABLE `search_index` ADD FULLTEXT KEY `idx_search_content` (`title`,`content`,`tags`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `site_statistics`
--
ALTER TABLE `site_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_metric_date` (`metric_name`,`date_recorded`),
  ADD KEY `idx_metric_name` (`metric_name`),
  ADD KEY `idx_date_recorded` (`date_recorded`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `alumni_id` (`alumni_id`),
  ADD KEY `idx_logs_action` (`action`),
  ADD KEY `idx_logs_table` (`table_name`),
  ADD KEY `idx_logs_date` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `alumni`
--
ALTER TABLE `alumni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `alumni_education`
--
ALTER TABLE `alumni_education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alumni_employment`
--
ALTER TABLE `alumni_employment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `announcement_views`
--
ALTER TABLE `announcement_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `colleges`
--
ALTER TABLE `colleges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `photos`
--
ALTER TABLE `photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `photo_albums`
--
ALTER TABLE `photo_albums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `photo_tags`
--
ALTER TABLE `photo_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `search_index`
--
ALTER TABLE `search_index`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `site_statistics`
--
ALTER TABLE `site_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
