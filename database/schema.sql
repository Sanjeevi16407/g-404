-- ====================================================
-- Buddy - Your Digital Senior: MySQL Database Schema
-- Target Database: buddy_senior_db
-- ====================================================

CREATE DATABASE IF NOT EXISTS buddy_senior_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE buddy_senior_db;

-- 1. Departments Table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(15) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Sections Table
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    name VARCHAR(10) NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    UNIQUE KEY uq_dept_section (department_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Admins Table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    register_number VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    department_id INT NOT NULL,
    section_id INT NOT NULL,
    avatar_url VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Faculty Table
CREATE TABLE IF NOT EXISTS faculty (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    designation VARCHAR(100) NOT NULL DEFAULT 'Assistant Professor',
    photo_url VARCHAR(255) DEFAULT 'assets/images/default-faculty.png',
    department_id INT NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    cabin_location VARCHAR(100) NOT NULL,
    subject_specialization VARCHAR(100) NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Timetable Table
CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    section_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    period_number INT NOT NULL CHECK (period_number BETWEEN 1 AND 8),
    subject_name VARCHAR(100) NOT NULL,
    faculty_id INT NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_schedule (department_id, section_id, day_of_week, period_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Campus Locations Table
CREATE TABLE IF NOT EXISTS campus_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    photo_url VARCHAR(255) DEFAULT 'assets/images/default-campus.jpg',
    opening_hours VARCHAR(50) DEFAULT 'Always Open',
    closing_hours VARCHAR(50) DEFAULT 'Always Open',
    location_details VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Library Resources Table
CREATE TABLE IF NOT EXISTS library_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_name VARCHAR(100) NOT NULL UNIQUE,
    volume_count INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Library Rules Table
CREATE TABLE IF NOT EXISTS library_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_text VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Hostel Info Table
CREATE TABLE IF NOT EXISTS hostel_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_name VARCHAR(100) NOT NULL UNIQUE,
    warden_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    timings VARCHAR(100) NOT NULL,
    photo_url VARCHAR(255) DEFAULT 'assets/images/default-hostel.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Bus Routes Table
CREATE TABLE IF NOT EXISTS bus_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_number VARCHAR(20) NOT NULL UNIQUE,
    pickup_points TEXT NOT NULL,
    departure_time TIME NOT NULL,
    driver_name VARCHAR(100) NOT NULL,
    driver_phone VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Important Contacts Table
CREATE TABLE IF NOT EXISTS important_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Clubs Table
CREATE TABLE IF NOT EXISTS clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    faculty_coordinator VARCHAR(100) NOT NULL,
    logo_url VARCHAR(255) DEFAULT 'assets/images/default-club.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Club Registrations Table
CREATE TABLE IF NOT EXISTS club_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    club_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    UNIQUE KEY uq_student_club (student_id, club_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Events Table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    image_url VARCHAR(255) DEFAULT 'assets/images/default-event.jpg',
    venue VARCHAR(100) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Event Registrations Table
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY uq_student_event (student_id, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Announcements Table
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'low',
    pdf_path VARCHAR(255) DEFAULT NULL,
    publish_date DATE NOT NULL,
    expiry_date DATE DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Documents Table
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Buddy Knowledge Base Table
CREATE TABLE IF NOT EXISTS buddy_knowledge (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    question_keywords TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    answer TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'low',
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Journey Progress Table
CREATE TABLE IF NOT EXISTS journey_progress (
    student_id INT PRIMARY KEY,
    current_step ENUM('welcome', 'orientation', 'campus', 'faculty', 'timetable', 'clubs', 'events', 'dashboard') DEFAULT 'welcome',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Achievements Table
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    badge_name VARCHAR(100) NOT NULL,
    badge_icon VARCHAR(50) NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY uq_student_badge (student_id, badge_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Settings Table (Student Settings)
CREATE TABLE IF NOT EXISTS settings (
    student_id INT PRIMARY KEY,
    theme ENUM('Light', 'Dark', 'Aurora', 'Liquid Glass', 'Spatial', 'System') DEFAULT 'Spatial',
    animation_speed ENUM('low', 'medium', 'high') DEFAULT 'high',
    notifications_enabled TINYINT(1) DEFAULT 1,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Quick Actions Table
CREATE TABLE IF NOT EXISTS quick_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    action_name VARCHAR(100) NOT NULL,
    action_url VARCHAR(255) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    usage_count INT DEFAULT 1,
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY uq_student_action (student_id, action_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Buddy Settings Table [NEW]
CREATE TABLE IF NOT EXISTS buddy_settings (
    id INT PRIMARY KEY DEFAULT 1,
    buddy_name VARCHAR(50) NOT NULL DEFAULT 'Buddy',
    welcome_message TEXT NOT NULL,
    morning_message TEXT NOT NULL,
    afternoon_message TEXT NOT NULL,
    evening_message TEXT NOT NULL,
    night_message TEXT NOT NULL,
    daily_tips TEXT NOT NULL,
    enable_voice TINYINT(1) NOT NULL DEFAULT 1,
    enable_wheel TINYINT(1) NOT NULL DEFAULT 1,
    enable_predictive TINYINT(1) NOT NULL DEFAULT 1,
    gemini_api_key VARCHAR(255) DEFAULT NULL,
    mapbox_token VARCHAR(255) DEFAULT NULL,
    CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. College Settings Table [NEW]
CREATE TABLE IF NOT EXISTS college_settings (
    id INT PRIMARY KEY DEFAULT 1,
    college_name VARCHAR(150) NOT NULL DEFAULT 'Saranathan College of Engineering',
    college_logo VARCHAR(255) DEFAULT 'assets/images/logo.png',
    college_email VARCHAR(100) NOT NULL DEFAULT 'info@saranathan.ac.in',
    college_phone VARCHAR(50) NOT NULL DEFAULT '0431-2908446',
    address TEXT NOT NULL,
    footer_text VARCHAR(255) NOT NULL DEFAULT '© 2026 Saranathan College of Engineering. All rights reserved.',
    default_theme ENUM('Light', 'Dark', 'Aurora', 'Liquid Glass', 'Spatial', 'System') DEFAULT 'Spatial',
    maintenance_mode TINYINT(1) NOT NULL DEFAULT 0,
    CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. Analytics Logs Table [NEW]
CREATE TABLE IF NOT EXISTS analytics_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL, -- 'buddy_query', 'page_visit', 'theme_change', 'club_join', 'event_register'
    item_name VARCHAR(100) NOT NULL, -- e.g. 'canteen', 'library.php', 'Coding Club'
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ====================================================
-- SEEDING DEFAULT DATA
-- ====================================================

-- Seed Departments
INSERT INTO departments (id, name, code) VALUES
(1, 'Computer Science & Engineering', 'CSE'),
(2, 'Electronics & Communication Engineering', 'ECE'),
(3, 'Artificial Intelligence & Data Science', 'AIDS'),
(4, 'Artificial Intelligence & Machine Learning', 'AIML'),
(5, 'Computer Science & Business Systems', 'CSBS'),
(6, 'Information Technology', 'IT'),
(7, 'Mechanical Engineering', 'MECH'),
(8, 'Civil Engineering', 'CIVIL'),
(9, 'Mathematics', 'MATH'),
(10, 'Physics', 'PHYS'),
(11, 'Chemistry', 'CHEM'),
(12, 'English', 'ENGL');

-- Seed Sections
INSERT INTO sections (id, department_id, name) VALUES
(1, 1, 'A'), (2, 1, 'B'),
(3, 2, 'A'), (4, 2, 'B'),
(5, 3, 'A'), (6, 3, 'B'),
(7, 4, 'A'),
(8, 5, 'A'),
(9, 6, 'A'),
(10, 7, 'A'),
(11, 8, 'A');

-- Seed Default Admin
-- Password text: 'admin@saranathan'
INSERT INTO admins (username, password_hash, email) VALUES
('admin', '$2b$12$OFpVxsCjUW/Z0uCQz/DW6.urxyiwwISryyVj69v4rd95GaJtqFMdO', 'admin@saranathan.ac.in');

-- Seed Default Student
-- Password text: 'password123'
INSERT INTO students (id, register_number, name, email, phone, password_hash, department_id, section_id) VALUES
(1, '2114001', 'Sanjeevi', 'sanjeevi@saranathan.ac.in', '9876543210', '$2b$12$0u6l.0L9BoOd9ekSEMtKu.ZV1qk0ParzcroKI/vC6hQSM4XfeZ2Xe', 1, 1);

-- Seed defaults for Student's Settings and Journey Progress
INSERT INTO journey_progress (student_id, current_step) VALUES (1, 'welcome');
INSERT INTO settings (student_id, theme, animation_speed) VALUES (1, 'Spatial', 'high');

-- Seed Library Resources
INSERT INTO library_resources (resource_name, volume_count) VALUES
('Total No of Books', 56413),
('Titles', 21104),
('Reference Books', 4470),
('Back Volumes', 1941),
('CD/DVDS', 2803),
('Audio Cassettes', 28),
('Project Reports', 2454),
('NPTEL Videos', 182),
('Journals /(Hard Copy)', 106),
('Newspapers', 7),
('e-Journals', 208);

-- Seed Library Rules
INSERT INTO library_rules (rule_text) VALUES
('Scan your ID card in the library gate system while entering and exiting.'),
('Open from 9:00 AM to 5:30 PM on all working days.'),
('1st/2nd year BE/BTech borrow 3 books, 3rd/final year borrow 4 books, PG borrow 5 books, staff borrow 10 books.'),
('Loan duration is 21 days maximum.'),
('Late fine: Rs. 1/day (up to 15 days), Rs. 2/day (15-30 days), Rs. 3/day (30+ days overdue).'),
('Reprography (photocopying) facility is available at Rs. 1 per page.'),
('Dictionaries, reports, proceedings, and reference sources will not be lent out.'),
('Bags and footwear must be left outside in the designated shoe stands/shelves.'),
('Electronic devices (cell phones, cameras, pen drives) are strictly prohibited.'),
('Silence must be observed at all times.');

-- Seed Important Contacts
INSERT INTO important_contacts (name, phone, email) VALUES
('Main Office', '0431-2908446', 'office@saranathan.ac.in'),
('Library Desk', '0431-2908448', 'library@saranathan.ac.in'),
('Transport Coordinator', '9443555541', 'transport@saranathan.ac.in'),
('Boys Hostel Warden', '9443555542', 'boyshostel@saranathan.ac.in'),
('Girls Hostel Warden', '9443555543', 'girlshostel@saranathan.ac.in'),
('Emergency Desk', '9443555544', 'security@saranathan.ac.in');

-- Seed default Campus Locations
INSERT INTO campus_locations (name, description, photo_url, opening_hours, closing_hours, location_details) VALUES
('K. Santhanam Block', 'The main administration and administrative block, housing the Principal\'s office, admissions hall, and main administrative staff offices.', 'assets/images/santhanam-block.jpg', '09:00 AM', '05:30 PM', 'Near Main Entrance'),
('Central Library', 'A comprehensive resource center housing over 56,000 volumes, reference materials, NPTEL videos, and digital e-journals with dedicated reading areas.', 'assets/images/library_stats.png', '09:00 AM', '05:30 PM', 'Administrative Block, First Floor'),
('Academic Courtyard', 'A spacious open-air green courtyard between departments, offering students a calm environment for group studies and peer interactions.', 'assets/images/courtyard.png', '08:30 AM', '06:00 PM', 'Center of Campus Block A & B');

-- Seed initial Clubs
INSERT INTO clubs (name, description, faculty_coordinator, logo_url) VALUES
('Coding Club', 'Nurturing programming talents, logic, and problem-solving through workshops, coding contests, and open-source projects.', 'Dr. S. Venkatasubramanian', 'assets/images/default-club.png'),
('Photography Club', 'A visual storytelling community capturing campus life, events, and conducting workshops on editing and composition.', 'Mr. K. Selvam', 'assets/images/default-club.png'),
('Music Club', 'A vibrant platform for instrumentalists, classical vocalists, and contemporary bands to practice and perform at college events.', 'Mrs. R. Malini', 'assets/images/default-club.png');

-- Seed initial Events
INSERT INTO events (title, description, image_url, venue, event_date, event_time) VALUES
('Fresher\'s Day 2026', 'Official welcoming party for first-year students with cultural programs, talent hunts, and interactions with seniors.', 'assets/images/default-event.jpg', 'JS Auditorium', '2026-08-10', '10:00:00'),
('Code Storm 1.0', 'A 12-hour hackathon exclusive to freshers to build problem-solving web applications using HTML/CSS/JS.', 'assets/images/default-event.jpg', 'CSE Lab 2', '2026-08-25', '09:00:00');

-- Seed initial Timetable Faculty
INSERT INTO faculty (name, designation, department_id, email, cabin_location, subject_specialization) VALUES
('Dr. R. Natarajan', 'Professor & HOD', 1, 'natarajan@saranathan.ac.in', 'Block A, L102', 'Engineering Mathematics'),
('Mrs. S. Priya', 'Assistant Professor', 1, 'priya@saranathan.ac.in', 'Block A, L105', 'Physics for Engineers'),
('Dr. M. Premkumar', 'Associate Professor', 1, 'premkumar@saranathan.ac.in', 'Block B, L201', 'Problem Solving & Python Programming');

-- Seed Timetable Slots template
INSERT INTO timetable (department_id, section_id, day_of_week, period_number, subject_name, faculty_id, room_number) VALUES
(1, 1, 'Monday', 1, 'Engineering Mathematics', 1, 'A-101'),
(1, 1, 'Monday', 2, 'Physics for Engineers', 2, 'A-101'),
(1, 1, 'Monday', 3, 'Problem Solving & Python', 3, 'A-101'),
(1, 1, 'Monday', 4, 'Python Programming Lab', 3, 'CSE Lab 1'),
(1, 1, 'Monday', 5, 'Python Programming Lab', 3, 'CSE Lab 1'),
(1, 1, 'Monday', 6, 'Library / Seminar Hour', 1, 'Central Library'),
(1, 1, 'Monday', 7, 'Professional English', 2, 'A-101'),
(1, 1, 'Monday', 8, 'Mentoring Hour', 3, 'A-101');

-- Seed Buddy Knowledge Base FAQ details (revised structure)
INSERT INTO buddy_knowledge (question, question_keywords, category, answer, priority, status) VALUES
('What are the library rules?', 'library, library rules, library policy, regulation', 'library', 'Our library timings are 9:00 AM to 5:30 PM. You must scan your ID card at the entry gate. 1st year students can take up to 3 books for a duration of 21 days.', 'medium', 'active'),
('When is the library open?', 'library timing, library eppo open, library open time, closing time', 'library', 'Library working hours: 9:00 AM to 5:30 PM on all working days. Make sure to visit within these hours!', 'low', 'active'),
('Where is the library located?', 'library yenga iruku, library eppadi poganum, library location', 'library', 'Central Library Block-A building la First Floor la iruku. Main gate la iruku Block A entrance poitu staircase la andha floor poalaam.', 'high', 'active'),
('Where is the canteen?', 'canteen, sapadu, canteen yenga iruku, food', 'campus', 'College Canteen Block-B block ku pinnala ground floor la iruku. Anga standard vegetarian meals, snacks, and juices affordable rate la kidaikum!', 'medium', 'active'),
('When is the canteen open?', 'canteen timing, canteen eppo open', 'campus', 'Canteen morning 8:00 AM layirundhu evening 6:00 PM varaikum open ah irukum, breaking hours la snacks try pannunga!', 'low', 'active'),
('Where is the CSE department?', 'cse department, cse lab, block b, computer science', 'campus', 'CSE Department Block B la First Floor and Second Floor la occupies simple blocks. Most computer labs first floor la right side la align aagiruku.', 'medium', 'active'),
('Where is Dr. R. Natarajan\'s cabin?', 'natarajan, math faculty, mathematics cabin, natarajan yenga irupaaru', 'faculty', 'Dr. R. Natarajan Math faculty cabin Block-A structure la Room L102 la iruku. Math queries edhunaa anga direct ah polam.', 'high', 'active'),
('How do I view my timetable?', 'view timetable, schedule, en class eppo, classes', 'timetable', 'Dashboard view check panna unga daily timetable slots clear ah display aagum. Timetable page click panna unga full department details retrieve aagum.', 'low', 'active'),
('Where is the orientation info?', 'orientation, freshers welcome, orientations eppo', 'orientation', 'Orientation page la visual missions and rules details check pannunga, first year basic guide links and details Orientation card la preloaded ah register aayiruku.', 'low', 'active');

-- Seed default Buddy settings
INSERT INTO buddy_settings (id, buddy_name, welcome_message, morning_message, afternoon_message, evening_message, night_message, daily_tips, enable_voice, enable_wheel, enable_predictive, gemini_api_key) VALUES
(1, 'Buddy', 
 'Welcome to Saranathan College of Engineering. I\'m Buddy, your Digital Senior. Let\'s begin your journey!',
 '👋 Good morning! Ready to tackle today\'s sessions at Saranathan?',
 '👋 Good afternoon! Hope your lab sessions are going great.',
 '👋 Good evening! Rest well and check your pending tasks for tomorrow.',
 '👋 Good night! Make sure to set an alarm for your 9:15 AM class tomorrow!',
 'Tip of the Day: Keep your syllabus handy and make sure to scan your ID card at the library gate system daily to log hours.',
 1, 1, 1, '');

-- Seed default College settings
INSERT INTO college_settings (id, college_name, college_logo, college_email, college_phone, address, footer_text, default_theme, maintenance_mode) VALUES
(1, 'Saranathan College of Engineering', 'assets/images/logo.png', 'info@saranathan.ac.in', '0431-2908446', 'Venkateswara Nagar, Panjappur, Tiruchirappalli, Tamil Nadu 620012', '© 2026 Saranathan College of Engineering. All rights reserved.', 'Spatial', 0);

-- Seed initial Analytics Logs
INSERT INTO analytics_logs (event_type, item_name) VALUES
('page_visit', 'dashboard.php'),
('buddy_query', 'Where is library?'),
('buddy_query', 'canteen yenga iruku'),
('club_join', 'Coding Club'),
('event_register', 'Code Storm 1.0');

