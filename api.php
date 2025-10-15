<?php
require_once 'config.php';

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get database connection
$db = Database::getInstance()->getConnection();

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'signup':
        signup($db);
        break;
    case 'login':
        login($db);
        break;
    case 'sendResetToken':
        sendResetToken($db);
        break;
    case 'resetPassword':
        resetPassword($db);
        break;
    case 'getAccountsByEmail':
        getAccountsByEmail($db);
        break;
    case 'getStudentsByAccount':
        getStudentsByAccount($db);
        break;
    case 'saveStudentsByAccount':
        saveStudentsByAccount($db);
        break;
    case 'canSaveAttendance':
        canSaveAttendance($db);
        break;
    case 'saveAttendanceByAccount':
        saveAttendanceByAccount($db);
        break;
    case 'getAttendanceByAccount':
        getAttendanceByAccount($db);
        break;
    case 'getTotalClasses':
        getTotalClasses($db);
        break;
    case 'getStudentStatus':
        getStudentStatus($db);
        break;
    case 'isFirstLogin':
        isFirstLogin($db);
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}

// ============= SIGNUP =============
function signup($db) {
    $email = sanitizeInput($_POST['email'] ?? '');
    $dept = sanitizeInput($_POST['department'] ?? '');
    $sem = sanitizeInput($_POST['semester'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password) || empty($subject)) {
        jsonResponse(['success' => false, 'message' => 'All fields required']);
    }
    
    try {
        $db->beginTransaction();
        
        // Generate account ID
        $accountId = generateAccountId();
        
        // Insert account
        $stmt = $db->prepare("INSERT INTO accounts (account_id, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$accountId, strtolower($email), hashPassword($password)]);
        
        // Insert subject
        $stmt = $db->prepare("INSERT INTO subjects (account_id, department, semester, subject_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$accountId, $dept, $sem, $subject]);
        
        $db->commit();
        
        jsonResponse(['success' => true, 'message' => 'Account created successfully']);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Signup failed: ' . $e->getMessage()]);
    }
}

// ============= LOGIN =============
function login($db) {
    $email = strtolower(sanitizeInput($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Email and password required']);
    }
    
    try {
        $stmt = $db->prepare("
            SELECT a.account_id, a.password_hash, s.department, s.semester, s.subject_name
            FROM accounts a
            LEFT JOIN subjects s ON a.account_id = s.account_id
            WHERE a.email = ?
        ");
        $stmt->execute([$email]);
        $accounts = $stmt->fetchAll();
        
        if (empty($accounts)) {
            jsonResponse(['success' => false, 'message' => 'Invalid credentials']);
        }
        
        // Verify password (same for all accounts under this email)
        if (!verifyPassword($password, $accounts[0]['password_hash'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid credentials']);
        }
        
        // Format accounts
        $accountsList = array_map(function($acc) {
            return [
                'accountId' => $acc['account_id'],
                'dept' => $acc['department'],
                'semester' => $acc['semester'],
                'subject' => $acc['subject_name']
            ];
        }, $accounts);
        
        jsonResponse(['success' => true, 'accounts' => $accountsList]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Login failed']);
    }
}

// ============= GET ACCOUNTS BY EMAIL =============
function getAccountsByEmail($db) {
    $email = strtolower(sanitizeInput($_POST['email'] ?? ''));
    
    try {
        $stmt = $db->prepare("
            SELECT a.account_id, s.department, s.semester, s.subject_name
            FROM accounts a
            LEFT JOIN subjects s ON a.account_id = s.account_id
            WHERE a.email = ?
        ");
        $stmt->execute([$email]);
        $accounts = $stmt->fetchAll();
        
        $result = array_map(function($acc) {
            return [
                'accountId' => $acc['account_id'],
                'dept' => $acc['department'],
                'semester' => $acc['semester'],
                'subject' => $acc['subject_name']
            ];
        }, $accounts);
        
        jsonResponse($result);
    } catch (Exception $e) {
        jsonResponse([]);
    }
}

// ============= SEND RESET TOKEN =============
function sendResetToken($db) {
    $accountId = sanitizeInput($_POST['accountId'] ?? '');
    
    try {
        // Generate token
        $token = generateToken();
        $expiresAt = date('Y-m-d H:i:s', time() + TOKEN_EXPIRY);
        
        // Delete old tokens
        $stmt = $db->prepare("DELETE FROM reset_tokens WHERE account_id = ?");
        $stmt->execute([$accountId]);
        
        // Insert new token
        $stmt = $db->prepare("INSERT INTO reset_tokens (account_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$accountId, $token, $expiresAt]);
        
        // In production, send email here
        // For now, return token for testing
        jsonResponse(['success' => true, 'token' => $token, 'message' => 'Token sent']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to send token']);
    }
}

// ============= RESET PASSWORD =============
function resetPassword($db) {
    $accountId = sanitizeInput($_POST['accountId'] ?? '');
    $token = sanitizeInput($_POST['token'] ?? '');
    $newPassword = $_POST['newPassword'] ?? '';
    
    try {
        // Verify token
        $stmt = $db->prepare("
            SELECT id FROM reset_tokens 
            WHERE account_id = ? AND token = ? AND expires_at > NOW() AND used = FALSE
        ");
        $stmt->execute([$accountId, $token]);
        $tokenData = $stmt->fetch();
        
        if (!$tokenData) {
            jsonResponse(['success' => false, 'message' => 'Invalid or expired token']);
        }
        
        $db->beginTransaction();
        
        // Update password
        $stmt = $db->prepare("UPDATE accounts SET password_hash = ? WHERE account_id = ?");
        $stmt->execute([hashPassword($newPassword), $accountId]);
        
        // Mark token as used
        $stmt = $db->prepare("UPDATE reset_tokens SET used = TRUE WHERE id = ?");
        $stmt->execute([$tokenData['id']]);
        
        $db->commit();
        
        jsonResponse(['success' => true, 'message' => 'Password reset successful']);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Reset failed']);
    }
}

// ============= STUDENTS =============
function getStudentsByAccount($db) {
    $accountId = sanitizeInput($_POST['accountId'] ?? '');
    
    try {
        $stmt = $db->prepare("SELECT enrollment_number, student_name FROM students WHERE account_id = ? ORDER BY enrollment_number");
        $stmt->execute([$accountId]);
        $students = $stmt->fetchAll();
        
        $result = array_map(function($s) {
            return ['enroll' => $s['enrollment_number'], 'name' => $s['student_name']];
        }, $students);
        
        jsonResponse($result);
    } catch (Exception $e) {
        jsonResponse([]);
    }
}

function saveStudentsByAccount($db) {
    $accountId = sanitizeInput($_POST['accountId'] ?? '');
    $students = json_decode($_POST['students'] ?? '[]', true);
    
    try {
        $db->beginTransaction();
        
        // Delete existing students
        $stmt = $db->prepare("DELETE FROM students WHERE account_id = ?");
        $stmt->execute([$accountId]);
        
        // Insert new students
        $stmt = $db->prepare("INSERT INTO students (account_id, enrollment_number, student_name) VALUES (?, ?, ?)");
        foreach ($students as $student) {
            $stmt->execute([$accountId, $student['enroll'], $student['name'] ?? '']);
        }
        
        $db->commit();
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Failed to save students']);
    }
}

// ============= ATTENDANCE =============
function canSaveAttendance($db) {
    $accountId = sanitizeInput($_POST['accountId'] ?? '');
    $date = sanitizeInput($_POST['date'] ?? '');
    
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM attendance WHERE account_id = ? AND attendance_date = ?");
        $stmt->execute([$accountId, $date]);
        $result = $stmt->fetch();
        
        jsonResponse($result['count'] == 0);
    } catch (Exception $e) {
        jsonResponse(false);
    }
}

function saveAttendanceByAccount($db) {
    $accountId = sanitizeInput($_POST['accountId'] ?? '');
    $payload = json_decode($_POST['payload'] ?? '{}', true);
    
    $date = $payload['date'] ?? '';
    $students = $payload['students'] ?? [];
    
    try {
        // Get subject info
        $stmt = $db->prepare("SELECT department, semester, subject_name FROM subjects WHERE account_id = ?");
        $stmt->execute([$accountId]);
        $subject = $stmt->fetch();
        
        if (!$subject) {
            jsonResponse(['success' => false, 'message' => 'Subject not found']);
        }
        
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO attendance (account_id, enrollment_number, attendance_date, status, department, semester, subject_name)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($students as $student) {
            $stmt->execute([
                $accountId,
                $student['enroll'],
                $date,
                $student['status'],
                $subject['department'],
                $subject['semester'],
                $subject['subject_name']
            ]);
        }
        
        $db->commit();
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Failed to save attendance']);
    }
}

function getAttendanceByAccount($db) {
    $accountId = sanitizeInput($_POST['accountId'] ?? '');
    $filter = json_decode($_POST['filter'] ?? '{}', true);
    
    $search = $filter['search'] ?? '';
    $status = $filter['status'] ?? 'All';
    
    try {
        $sql = "
            SELECT attendance_date as Date, enrollment_number as Enroll, status as Status,
                   department as Department, semester as Semester, subject_name as Subject
            FROM attendance
            WHERE account_id = ?
        ";
        $params = [$accountId];
        
        if (!empty($search)) {
            $sql .= " AND enrollment_number LIKE ?";
            $params[] = "%$search%";
        }
        
        if ($status !== 'All') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY attendance_date DESC, enrollment_number LIMIT 100";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();
        
        jsonResponse($records);
    } catch (Exception $e) {
        jsonResponse([]);
    }
}

function getTotalClasses($db) {
    $accountId = sanitizeInput($_POST['accountId'] ?? '');
    
    try {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT attendance_date) as count FROM attendance WHERE account_id = ?");
        $stmt->execute([$accountId]);
        $result = $stmt->fetch();
        
        jsonResponse($result['count'] ?? 0);
    } catch (Exception $e) {
        jsonResponse(0);
    }
}

// ============= STUDENT STATUS =============
function getStudentStatus($db) {
    $dept = sanitizeInput($_POST['department'] ?? '');
    $sem = sanitizeInput($_POST['semester'] ?? '');
    $enroll = sanitizeInput($_POST['enrollmentNumber'] ?? '');
    
    try {
        $stmt = $db->prepare("
            SELECT subject_name,
                   COUNT(*) as total,
                   SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
                   SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
            FROM attendance
            WHERE department = ? AND semester = ? AND enrollment_number = ?
            GROUP BY subject_name
        ");
        $stmt->execute([$dept, $sem, $enroll]);
        $results = $stmt->fetchAll();
        
        if (empty($results)) {
            jsonResponse(['success' => false, 'message' => 'No records found']);
        }
        
        $data = [];
        foreach ($results as $row) {
            $data[$row['subject_name']] = [
                'total' => (int)$row['total'],
                'present' => (int)$row['present'],
                'absent' => (int)$row['absent']
            ];
        }
        
        jsonResponse(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Error fetching data']);
    }
}

function isFirstLogin($db) {
    $accountId = sanitizeInput($_POST['accountId'] ?? '');
    
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM students WHERE account_id = ?");
        $stmt->execute([$accountId]);
        $result = $stmt->fetch();
        
        jsonResponse($result['count'] == 0);
    } catch (Exception $e) {
        jsonResponse(false);
    }
}
?>