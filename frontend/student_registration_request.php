<?php
/**
 * Student Registration Request Form
 * Allows unregistered students to submit their registration details for admin approval.
 */
require_once __DIR__ . '/../backend/db.php';

$error_msg = "";
$success_msg = "";

// Fetch departments and sections for dropdowns
$departments = $db->query("SELECT * FROM departments ORDER BY code ASC")->fetchAll();
$sections = $db->query("SELECT s.*, d.code as dept_code FROM sections s JOIN departments d ON s.department_id = d.id ORDER BY d.code ASC, s.name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = sanitize_input($_POST['student_name'] ?? '');
    $register_number = sanitize_input($_POST['register_number'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $year_level = (int)($_POST['year_level'] ?? 1);
    $section_id = (int)($_POST['section_id'] ?? 0);
    $college_email = sanitize_input($_POST['college_email'] ?? '');
    $personal_email = sanitize_input($_POST['personal_email'] ?? '');
    $mobile_number = sanitize_input($_POST['mobile_number'] ?? '');
    $message = sanitize_input($_POST['message'] ?? '');

    if (!empty($student_name) && !empty($register_number) && $department_id > 0 && $section_id > 0 && !empty($personal_email) && !empty($mobile_number)) {
        
        // 1. Check if Register Number already exists in Students table
        $check_student = $db->prepare("SELECT id FROM students WHERE register_number = ? LIMIT 1");
        $check_student->execute([$register_number]);
        if ($check_student->fetch()) {
            $error_msg = "An account with this Register Number already exists! Please return to the login page.";
        } else {
            // 2. Check if a Pending request already exists for this Register Number
            $check_req = $db->prepare("SELECT id, status FROM student_registration_requests WHERE register_number = ? AND status = 'Pending' LIMIT 1");
            $check_req->execute([$register_number]);
            if ($check_req->fetch()) {
                $error_msg = "A registration request for this Register Number already exists. Please wait for administrator approval.";
            } else {
                // 3. Save new registration request
                try {
                    $stmt = $db->prepare("
                        INSERT INTO student_registration_requests 
                        (student_name, register_number, department_id, year_level, section_id, college_email, personal_email, mobile_number, message, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
                    ");
                    $stmt->execute([
                        $student_name,
                        $register_number,
                        $department_id,
                        $year_level,
                        $section_id,
                        $college_email,
                        $personal_email,
                        $mobile_number,
                        $message
                    ]);

                    $success_msg = "Registration request submitted successfully! Your request has been sent to the administrator. You will be able to log in after your account has been approved.";
                } catch (PDOException $e) {
                    $error_msg = "Database error while processing your request. Please try again.";
                }
            }
        }
    } else {
        $error_msg = "Please fill in all required fields (Name, Register Number, Department, Year, Section, Personal Email, Mobile Number).";
    }
}

// Fetch college details for header
$college = $db->query("SELECT * FROM college_settings WHERE id = 1 LIMIT 1")->fetch();
$college_name = $college['college_name'] ?? 'Saranathan College of Engineering';
$college_logo = $college['college_logo'] ?? 'assets/images/logo.png';
?>
<!DOCTYPE html>
<html lang="en" data-theme="Spatial">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Student Registration | Buddy Assistant</title>
    <!-- FontAwesome & Core Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/themes/themes.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }
        .request-card {
            width: 100%;
            max-width: 580px;
            padding: 36px;
            border-radius: 24px;
        }
        .logo-img {
            width: 64px;
            height: auto;
            border-radius: 50%;
            box-shadow: 0 0 20px var(--glow-primary-alpha);
            margin-bottom: 12px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-grid .full-width {
            grid-column: span 2;
        }
        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-grid .full-width {
                grid-column: span 1;
            }
            .request-card {
                padding: 24px 18px;
            }
        }
        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }
        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .form-control, select.form-control, textarea.form-control {
            width: 100%;
            padding: 11px 14px;
            font-family: var(--font-body);
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.9rem;
            outline: none;
            transition: all var(--transition-fast);
            box-sizing: border-box;
        }
        select.form-control option {
            background-color: #0d1223;
            color: #ffffff;
        }
        .form-control:focus {
            border-color: var(--glow-primary);
            box-shadow: 0 0 10px var(--glow-primary-alpha);
            background: rgba(255, 255, 255, 0.08);
        }
        .alert-banner {
            padding: 14px 16px;
            border-radius: 14px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            text-align: left;
            line-height: 1.4;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #10b981;
        }
        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }
        .btn-action {
            flex: 1;
            padding: 13px;
            font-weight: 600;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <!-- Moving Aurora Backgrounds -->
    <div class="aurora-bg-container">
        <div class="aurora-blob aurora-blob-1"></div>
        <div class="aurora-blob aurora-blob-2"></div>
    </div>

    <!-- Request Form Glass Card -->
    <div class="glass-panel request-card">
        <div style="text-align: center; margin-bottom: 24px;">
            <img src="../<?php echo sanitize_input($college_logo); ?>" alt="College Logo" class="logo-img">
            <h2 style="font-size: 1.6rem; color: var(--text-primary); margin-bottom: 6px;">New Student Registration</h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">Submit your details for Administrator Approval & Provisioning</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert-banner alert-error">
                ⚠️ <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert-banner alert-success">
                ✅ <?php echo $success_msg; ?>
            </div>
            <div style="margin-top: 20px;">
                <a href="login.php" class="btn-glass btn-primary btn-action" style="display: block; text-align: center;">Return to Login Page</a>
            </div>
        <?php else: ?>

            <form method="POST" action="student_registration_request.php" autocomplete="off">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="student_name">Full Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="student_name" name="student_name" class="form-control" placeholder="e.g. Rahul Sharma" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="register_number">Register Number <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="register_number" name="register_number" class="form-control" placeholder="e.g. 2114001" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="department_id">Department <span style="color:#ef4444;">*</span></label>
                        <select id="department_id" name="department_id" class="form-control" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?php echo (int)$d['id']; ?>"><?php echo sanitize_input($d['code'] . ' - ' . $d['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="year_level">Year of Study <span style="color:#ef4444;">*</span></label>
                        <select id="year_level" name="year_level" class="form-control" required>
                            <option value="1">1st Year (Freshers)</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="section_id">Section <span style="color:#ef4444;">*</span></label>
                        <select id="section_id" name="section_id" class="form-control" required>
                            <option value="">-- Select Section --</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?php echo (int)$sec['id']; ?>"><?php echo sanitize_input($sec['dept_code'] . ' - Section ' . $sec['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="mobile_number">Mobile Number <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="mobile_number" name="mobile_number" class="form-control" placeholder="e.g. 9876543210" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="personal_email">Personal Email <span style="color:#ef4444;">*</span></label>
                        <input type="email" id="personal_email" name="personal_email" class="form-control" placeholder="student@gmail.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="college_email">College Email (Optional)</label>
                        <input type="email" id="college_email" name="college_email" class="form-control" placeholder="student@saranathan.ac.in">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label" for="message">Reason / Note to Administrator (Optional)</label>
                        <textarea id="message" name="message" class="form-control" rows="3" placeholder="e.g. Joined lateral entry in 2nd year. Please verify my register number."></textarea>
                    </div>
                </div>

                <div class="btn-row">
                    <a href="login.php" class="btn-glass btn-action" style="color: var(--text-secondary); text-align: center;">Cancel</a>
                    <button type="submit" class="btn-glass btn-primary btn-action">Submit Request</button>
                </div>
            </form>

        <?php endif; ?>
    </div>

</body>
</html>
