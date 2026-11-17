<?php
// =============================================================================
// Momora AI - Doctor Assigned Patients List Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['doctor']);
$doctorId = (int)$user['id'];

$input = getInput();
$page = (int)($_GET['page'] ?? ($input['page'] ?? 1));
if ($page < 1) {
    $page = 1;
}

$limit = (int)($_GET['limit'] ?? ($input['limit'] ?? 10));
if ($limit < 1) {
    $limit = 10;
}
if ($limit > 100) {
    $limit = 100;
}

$offset = ($page - 1) * $limit;

// Total count of patients
$countStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM users u
     JOIN profiles p ON p.user_id = u.id
     WHERE u.role = "patient" AND u.is_active = 1'
);
$countStmt->execute();
$total = (int)$countStmt->fetchColumn();
$totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

// Fetch paginated patients
$stmt = $pdo->prepare(
    'SELECT
        u.id,
        u.name,
        p.patient_code,
        pd.gestational_weeks,
        pd.gestational_days,
        pd.estimated_due_date,
        pd.last_menstrual_period
     FROM users u
     JOIN profiles p ON p.user_id = u.id
     LEFT JOIN pregnancy_details pd ON pd.user_id = u.id
     WHERE u.role = "patient" AND u.is_active = 1
     ORDER BY u.id ASC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$patients = $stmt->fetchAll();

$items = [];
$now = time();

foreach ($patients as $idx => $p) {
    $pId = (string)$p['id'];
    $code = $p['patient_code'] ?: ('P-' . (10000 + (int)$p['id']));

    // Compute real gestational age dynamically
    $gestText = null;
    if (!empty($p['last_menstrual_period'])) {
        $lmpTime = strtotime($p['last_menstrual_period']);
        if ($lmpTime !== false && $lmpTime <= $now) {
            $daysDiff = (int)floor(($now - $lmpTime) / 86400);
            $w = (int)floor($daysDiff / 7);
            $d = $daysDiff % 7;
            $gestText = "{$w}w {$d}d";
        }
    }
    if ($gestText === null && !empty($p['estimated_due_date'])) {
        $eddTime = strtotime($p['estimated_due_date']);
        if ($eddTime !== false) {
            $daysUntilDue = (int)floor(($eddTime - $now) / 86400);
            $daysElapsed = max(0, 280 - $daysUntilDue);
            $w = (int)floor($daysElapsed / 7);
            $d = $daysElapsed % 7;
            $gestText = "{$w}w {$d}d";
        }
    }
    if ($gestText === null) {
        $weeks = (int)($p['gestational_weeks'] ?? 0);
        $days = (int)($p['gestational_days'] ?? 0);
        $gestText = ($weeks > 0 || $days > 0) ? "{$weeks}w {$days}d" : "Not recorded";
    }

    $dueFormatted = !empty($p['estimated_due_date'])
        ? ('Due ' . date('d M Y', strtotime($p['estimated_due_date'])))
        : 'Due date not set';

    // Check latest vitals with real timestamp
    $bpStmt = $pdo->prepare(
        'SELECT systolic, diastolic, pulse, recorded_at
         FROM blood_pressure_logs
         WHERE user_id = ?
         ORDER BY recorded_at DESC
         LIMIT 1'
    );
    $bpStmt->execute([$p['id']]);
    $bp = $bpStmt->fetch();

    if ($bp) {
        $recordedTime = strtotime($bp['recorded_at']);
        $diff = time() - $recordedTime;
        $bpDate = date('Y-m-d', $recordedTime);
        $todayDate = date('Y-m-d');
        $yesterdayDate = date('Y-m-d', strtotime('-1 day'));

        if ($diff < 60) {
            $timeAgo = 'Updated just now';
        } elseif ($diff < 3600) {
            $mins = max(1, (int)floor($diff / 60));
            $timeAgo = "Updated {$mins}m ago";
        } elseif ($bpDate === $todayDate) {
            $timeAgo = 'Updated today at ' . date('H:i', $recordedTime);
        } elseif ($bpDate === $yesterdayDate) {
            $timeAgo = 'Updated yesterday at ' . date('H:i', $recordedTime);
        } elseif ($diff < 7 * 86400) {
            $timeAgo = 'Updated ' . date('D, H:i', $recordedTime);
        } else {
            $timeAgo = 'Updated ' . date('d M Y', $recordedTime);
        }
        $bpNote = "Latest BP {$bp['systolic']}/{$bp['diastolic']} mmHg · {$timeAgo}";
    } else {
        $bpNote = 'No BP recorded yet';
    }

    // Check if patient has a visit scheduled for today with this doctor
    $visitStmt = $pdo->prepare(
        'SELECT appointment_time
         FROM appointments
         WHERE patient_id = ? AND doctor_id = ? AND appointment_date = CURDATE() AND status != "Cancelled"
         ORDER BY appointment_time ASC
         LIMIT 1'
    );
    $visitStmt->execute([$p['id'], $doctorId]);
    $visit = $visitStmt->fetch();
    $visitNote = $visit ? ('Visit today at ' . date('H:i', strtotime($visit['appointment_time']))) : null;

    // Check if patient has any recent medical reports (within last 14 days)
    $reportStmt = $pdo->prepare(
        'SELECT title, category
         FROM medical_reports
         WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
         ORDER BY created_at DESC
         LIMIT 1'
    );
    $reportStmt->execute([$p['id']]);
    $recentReport = $reportStmt->fetch();

    $needsReview = (bool)$recentReport;
    $reviewType = $recentReport ? ("New " . ($recentReport['category'] ?: "medical") . " report") : null;
    $isHighlighted = ($idx === 0);

    $items[] = [
        'id' => $pId,
        'patientIdText' => $code,
        'name' => $p['name'],
        'gestationalAge' => $gestText,
        'dueDateText' => $dueFormatted,
        'latestVitalsNote' => $bpNote,
        'visitNote' => $visitNote,
        'needsReview' => $needsReview,
        'reviewType' => $reviewType,
        'isHighlighted' => $isHighlighted
    ];
}

respond(200, [
    'success' => true,
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'totalPages' => $totalPages,
    'data' => $items
]);
