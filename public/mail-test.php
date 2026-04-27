<?php
/**
 * TPHL ERP — Workflow Mail Diagnostic Tool
 * ─────────────────────────────────────────
 * URL: http://localhost:8080/TPHL-ERP/public/mail-test.php
 * PURPOSE: Test if ProjectWorkflowMailService can send emails correctly.
 * DELETE THIS FILE after testing is confirmed.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

// Load full bootstrap
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers/helper.php';
require_once __DIR__ . '/../app/core/ProjectWorkflowMailService.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set GLOBALS base_url with full host (needed for email link building)
$GLOBALS['base_url'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                       . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8080') . '/TPHL-ERP';

// ── DB connection ──────────────────────────────────────────────────
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'tphl_db'; // ← আপনার DB নাম

$db = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($db->connect_error) {
    die("<b style='color:red'>DB Error:</b> " . $db->connect_error);
}

// ── Test target: your email address ────────────────────────────────
$testToEmail   = $_GET['email'] ?? null;  // pass ?email=your@email.com in URL
$testProjectId = (int)($_GET['project_id'] ?? 1);
$sendProjectId = (int)($_GET['send_project'] ?? 0); // ← NEW: send assignment emails

// ══════════════════════════════════════════════════════════
// MODE: ?send_project=ID → Show team + send emails immediately
// ══════════════════════════════════════════════════════════
if ($sendProjectId > 0) {
    header('Content-Type: text/html; charset=utf-8');

    $project = $db->query("SELECT * FROM projects WHERE id = {$sendProjectId} AND deleted_at IS NULL LIMIT 1")->fetch_assoc();

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Email Send Check</title>
    <style>
      body{font-family:Arial,sans-serif;padding:24px;background:#f5f5f5}
      .card{background:#fff;border-radius:10px;padding:20px;margin-bottom:16px;box-shadow:0 2px 10px rgba(0,0,0,.08);max-width:800px}
      h2{color:#198754;margin-top:0}
      .ok{color:#198754;font-weight:700} .warn{color:#f59e0b;font-weight:700} .err{color:#dc3545;font-weight:700}
      table{width:100%;border-collapse:collapse} th,td{padding:9px 12px;border:1px solid #ddd;font-size:13px;text-align:left} th{background:#f0f0f0}
      .badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700}
      .badge-ok{background:#d1e7dd;color:#0f5132} .badge-warn{background:#fff3cd;color:#856404}
    </style></head><body>";

    if (!$project) {
        echo "<div class='card'><p class='err'>❌ Project #{$sendProjectId} not found.</p></div>";
        echo "<a href='mail-test.php'>← Back</a></body></html>";
        exit;
    }

    echo "<div class='card'>
      <h2>📧 Assignment Email Check — Project #{$sendProjectId}</h2>
      <p>Project: <strong>" . htmlspecialchars($project['name']) . "</strong></p>
    </div>";

    // --- Team table ---
    $roleMap = [
        'pm_id'              => 'Project Manager',
        'site_engineer_id'   => 'Site Engineer',
        'landman_id'         => 'Land Manager',
        'struct_engineer_id' => 'Structural Engineer',
        'draftsman_id'       => 'Draftsman',
        'architect_id'       => 'Architect',
        'estimator_id'       => 'Estimator',
        'qc_id'              => 'Quality Controller',
        'operator_id'        => 'Site Operator',
    ];

    $toSend = [];
    echo "<div class='card'>
      <h3 style='margin-top:0'>Team Members & Email Status</h3>
      <table>
        <tr><th>Role</th><th>User ID</th><th>Name</th><th>Email</th><th>Will Receive?</th></tr>";

    foreach ($roleMap as $col => $label) {
        $uid  = (int)($project[$col] ?? 0);
        $uRow = null;
        if ($uid > 0) {
            $uRow = $db->query("SELECT id, full_name, email FROM users WHERE id = {$uid} LIMIT 1")->fetch_assoc();
        }
        $name      = $uRow['full_name'] ?? '—';
        $email     = $uRow['email']     ?? '';
        $hasEmail  = !empty($email);
        $badge     = $uid > 0
            ? ($hasEmail ? "<span class='badge badge-ok'>✅ Will get email</span>" : "<span class='badge badge-warn'>⚠️ No email in DB</span>")
            : "<span style='color:#ccc;font-size:12px'>Not assigned</span>";

        if ($uid > 0 && $hasEmail) {
            $toSend[] = ['role' => $label, 'name' => $name, 'email' => $email];
        }

        echo "<tr>
          <td>{$label}</td>
          <td>" . ($uid ?: '<span style="color:#ccc">NULL</span>') . "</td>
          <td>" . htmlspecialchars($name) . "</td>
          <td>" . htmlspecialchars($email ?: '—') . "</td>
          <td>{$badge}</td>
        </tr>";
    }
    echo "</table></div>";

    // --- Send emails ---
    if (empty($toSend)) {
        echo "<div class='card'><p class='warn'>⚠️ No team members have email addresses assigned. Emails cannot be sent.</p></div>";
    } else {
        echo "<div class='card'><h3 style='margin-top:0'>📤 Sending Emails Now...</h3>";
        echo "<table><tr><th>Role</th><th>Name</th><th>Email</th><th>Result</th></tr>";

        $wfMail = new ProjectWorkflowMailService($db);

        foreach ($toSend as $member) {
            echo "<tr>
              <td>" . htmlspecialchars($member['role']) . "</td>
              <td>" . htmlspecialchars($member['name']) . "</td>
              <td>" . htmlspecialchars($member['email']) . "</td>
              <td>⏳ queued...</td>
            </tr>";
        }

        echo "</table>";

        // Actually fire it
        try {
            $GLOBALS['base_url'] = '/TPHL-ERP';
            $wfMail->sendProjectAssignmentEmails($sendProjectId, $project['name']);
            echo "<p class='ok' style='font-size:15px;margin-top:12px'>✅ sendProjectAssignmentEmails() fired successfully!<br>
              Check the inboxes of: <strong>" . implode(', ', array_column($toSend, 'email')) . "</strong></p>";
        } catch (\Throwable $e) {
            echo "<p class='err'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }

        echo "</div>";
    }

    echo "<div style='margin-top:10px'>
      <a href='mail-test.php?project_id={$sendProjectId}' style='color:#198754'>← Back to Diagnostic</a>
    </div></body></html>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mail Diagnostic — TPHL ERP</title>
<style>
  body { font-family: Arial, sans-serif; background:#f5f5f5; padding:30px; }
  .card { background:#fff; border-radius:10px; padding:24px; margin-bottom:20px;
          box-shadow:0 2px 10px rgba(0,0,0,.08); max-width:800px; }
  h2 { color:#198754; margin-top:0; }
  .ok   { color:#198754; font-weight:700; }
  .err  { color:#dc3545; font-weight:700; }
  .warn { color:#f59e0b; font-weight:700; }
  pre  { background:#f8f9fa; border:1px solid #ddd; padding:12px;
         border-radius:6px; font-size:12px; overflow-x:auto; }
  table { width:100%; border-collapse:collapse; }
  th,td { padding:8px 12px; border:1px solid #ddd; font-size:13px; text-align:left; }
  th { background:#f0f0f0; }
  .btn { background:#198754; color:#fff; padding:10px 24px; border:none;
         border-radius:6px; font-size:14px; cursor:pointer; text-decoration:none;
         display:inline-block; margin-top:10px; }
</style>
</head>
<body>

<div class="card">
  <h2>🔍 TPHL ERP — Workflow Mail Diagnostic</h2>
  <p style="color:#888;font-size:13px">This tool checks if workflow emails are working correctly. Delete after testing.</p>
</div>

<!-- ── STEP 1: Check DB Project ── -->
<div class="card">
  <h2>Step 1 — Project Data Check (project_id = <?= $testProjectId ?>)</h2>
  <?php
  $pRow = $db->query("SELECT * FROM projects WHERE id = {$testProjectId} AND deleted_at IS NULL LIMIT 1")->fetch_assoc();
  if (!$pRow):
  ?>
    <p class="err">❌ Project #<?= $testProjectId ?> not found in DB. Try ?project_id=X with a valid ID.</p>
    <?php
    $ids = $db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
    echo "<p>Available projects:</p><pre>" . print_r(array_column($ids, null, 'id'), true) . "</pre>";
  else: ?>
    <p class="ok">✅ Project found: <strong><?= htmlspecialchars($pRow['name']) ?></strong></p>
    <table>
      <tr><th>Column</th><th>Value</th><th>User Name (from DB)</th><th>Email</th></tr>
      <?php
      $roles = [
        'pm_id'             => 'Project Manager',
        'site_engineer_id'  => 'Site Engineer',
        'landman_id'        => 'Land Manager',
        'struct_engineer_id'=> 'Structural Engineer',
        'draftsman_id'      => 'Draftsman',
        'architect_id'      => 'Architect',
        'estimator_id'      => 'Estimator',
        'qc_id'             => 'QC Controller',
        'operator_id'       => 'Site Operator',
      ];
      $teamEmails = [];
      foreach ($roles as $col => $label):
        $uid = (int)($pRow[$col] ?? 0);
        $uRow = null;
        if ($uid > 0) {
            $uRow = $db->query("SELECT id, full_name, email FROM users WHERE id = {$uid} LIMIT 1")->fetch_assoc();
        }
        $nameStr  = $uRow ? htmlspecialchars($uRow['full_name'] ?? '—') : '—';
        $emailStr = $uRow ? htmlspecialchars($uRow['email'] ?? '—') : '—';
        $statusCls = $uid > 0 ? ($uRow && !empty($uRow['email']) ? 'ok' : 'warn') : '';
        if ($uRow && !empty($uRow['email'])) $teamEmails[] = $uRow['email'];
        ?>
        <tr>
          <td><?= $label ?> <small style="color:#888">(<?= $col ?>)</small></td>
          <td><?= $uid ?: '<span class="warn">NULL</span>' ?></td>
          <td><?= $nameStr ?></td>
          <td class="<?= $statusCls ?>"><?= $emailStr ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php if (empty($teamEmails)): ?>
      <p class="warn">⚠️ No team members with emails assigned to this project.</p>
    <?php else: ?>
      <p class="ok">✅ <?= count($teamEmails) ?> member(s) have emails: <?= implode(', ', $teamEmails) ?></p>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ── STEP 2: Send Test Email ── -->
<div class="card">
  <h2>Step 2 — Send Test Email</h2>
  <?php if (!$testToEmail): ?>
    <p class="warn">⚠️ No email address provided. Add <code>?email=your@email.com</code> to the URL to test sending.</p>
    <p>Example: <code>http://localhost:8080/TPHL-ERP/public/mail-test.php?project_id=<?= $testProjectId ?>&email=your@gmail.com</code></p>
  <?php else: ?>
    <p>Sending test workflow email to: <strong><?= htmlspecialchars($testToEmail) ?></strong></p>
    <?php
    // Direct PHPMailer XOAUTH2 test
    $smtpHost     = 'smtp.gmail.com';
    $smtpPort     = 587;
    $senderEmail  = 'info@dpremiumhomes.com';
    $senderName   = 'TPHL ERP Test';
    $clientId     = '893215055562-pbt3j7lg7ufqju370ltss46kh7pati3n.apps.googleusercontent.com';
    $clientSecret = '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';
    $refreshToken = '1//04tQCZJUfNcm7CgYIARAAGAQSNwF-L9IrFjuXh9BJ80IcIEKsg8h5m5TFRRQS6BQTt-wwIvtJKnUHd4sAtITIw8QJ0pgbijIOF0w';

    $debugOutput = '';
    $sendResult  = false;
    $sendError   = '';

    try {
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 3; // Full debug
        $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
            $debugOutput .= htmlspecialchars($str) . "\n";
        };

        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->Port       = $smtpPort;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $provider = new Google([
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
        ]);

        $mail->AuthType = 'XOAUTH2';
        $mail->setOAuth(new OAuth([
            'provider'     => $provider,
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
            'refreshToken' => $refreshToken,
            'userName'     => $senderEmail,
        ]));

        $mail->setFrom($senderEmail, $senderName);
        $mail->addAddress($testToEmail);
        $mail->isHTML(true);
        $mail->Subject = '✅ TPHL ERP — Workflow Mail Test';
        $mail->Body    = "
        <div style='font-family:Arial;padding:30px;background:#f5f5f5;'>
          <div style='background:#fff;max-width:500px;margin:0 auto;padding:24px;border-radius:8px;border-top:4px solid #198754;'>
            <h2 style='color:#198754;margin-top:0;'>✅ Mail Test Successful!</h2>
            <p>This is a test email from <strong>TPHL ERP Workflow Mail Service</strong>.</p>
            <p>If you received this, the SMTP configuration is working correctly.</p>
            <hr style='border:none;border-top:1px solid #eee;margin:20px 0'>
            <p style='font-size:12px;color:#888'>Project: #" . $testProjectId . " — Sent at: " . date('d M Y H:i:s') . "</p>
          </div>
        </div>";
        $mail->AltBody = 'TPHL ERP Workflow Mail Test — Working!';

        $mail->send();
        $sendResult = true;
    } catch (Exception $e) {
        $sendError = $e->getMessage();
    } catch (\Throwable $t) {
        $sendError = $t->getMessage();
    }
    ?>

    <?php if ($sendResult): ?>
      <p class="ok" style="font-size:16px">✅ EMAIL SENT SUCCESSFULLY to <?= htmlspecialchars($testToEmail) ?></p>
      <p>Check your inbox (and spam/junk folder).</p>
    <?php else: ?>
      <p class="err" style="font-size:15px">❌ EMAIL FAILED</p>
      <p><strong>Error:</strong> <span class="err"><?= htmlspecialchars($sendError) ?></span></p>
    <?php endif; ?>

    <?php if ($debugOutput): ?>
      <h4>SMTP Debug Output:</h4>
      <pre><?= $debugOutput ?></pre>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ── STEP 3: Check PHP error log ── -->
<div class="card">
  <h2>Step 3 — Recent PHP Error Log</h2>
  <?php
  $logFile = ini_get('error_log');
  if ($logFile && file_exists($logFile)):
      $lines = array_slice(file($logFile), -30); // last 30 lines
      $relevant = array_filter($lines, fn($l) => stripos($l, 'workflow') !== false || stripos($l, 'mail') !== false || stripos($l, 'mailer') !== false || stripos($l, 'smtp') !== false);
      if ($relevant):
  ?>
    <p class="warn">Last relevant log entries:</p>
    <pre><?= htmlspecialchars(implode('', $relevant)) ?></pre>
  <?php else: ?>
    <p class="ok">✅ No workflow/mail errors in recent PHP error log.</p>
  <?php endif; else: ?>
    <p class="warn">⚠️ PHP error_log path: <code><?= htmlspecialchars($logFile ?: 'Not configured') ?></code> — Not accessible.</p>
    <p style="font-size:12px">Check: <code>C:\xampp\php\logs\php_error_log</code></p>
  <?php endif; ?>
</div>

<!-- ── STEP 4: XAMPP error log ── -->
<div class="card">
  <h2>Step 4 — XAMPP Apache Error Log (last 20 relevant lines)</h2>
  <?php
  $apacheLog = 'C:\\xampp\\apache\\logs\\error.log';
  if (file_exists($apacheLog)):
      $lines = array_slice(file($apacheLog), -50);
      $relevant = array_filter($lines, fn($l) => stripos($l, 'workflow') !== false || stripos($l, 'mail') !== false || stripos($l, 'Mailer') !== false || stripos($l, 'ProjectController') !== false);
      if ($relevant):
  ?>
    <pre><?= htmlspecialchars(implode('', $relevant)) ?></pre>
  <?php else: ?>
    <p class="ok">✅ No relevant errors in Apache log.</p>
  <?php endif; else: ?>
    <p class="warn">Apache log not found at default path.</p>
  <?php endif; ?>
</div>

<div class="card" style="background:#fff3cd;border:1px solid #ffc107">
  <strong>⚠️ Security Notice:</strong> Delete <code>public/mail-test.php</code> after finishing your testing!
</div>

</body>
</html>
