<?php
/**
 * DEBUG: AccountMailService OAuth SMTP Test
 * ──────────────────────────────────────────
 * URL: http://localhost/TPHL-ERP/public/debug-account-mail.php
 * 
 * Tests the tech@dpremiumhomes.com OAuth credentials used by AccountMailService.
 * Shows full SMTP debug output to diagnose why emails are failing.
 * 
 * DELETE THIS FILE after debugging is complete!
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── Current credentials from AccountMailService.php ──
$smtpHost     = 'smtp.gmail.com';
$smtpPort     = 587;
$senderEmail  = 'tech@dpremiumhomes.com';
$senderName   = 'TPHL ERP Debug Test';
$clientId     = '684667519692-hcdp7rhi9r64d87g93eq1cagpgqurk35.apps.googleusercontent.com';
$clientSecret = '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';
$refreshToken = '1//04BDEsDiSt3c8CgYIARAAGAQSNwF-L9IrOY_DoujlFcz7uRl9fOeoZQ3BdX64WqmATqzfm_1YDQxWqDlJG_61TjakP5l1YZiU4KQ';

$testToEmail = $_GET['email'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Debug — AccountMailService SMTP</title>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background:#1a1a2e; color:#e0e0e0; padding:30px; margin:0; }
  .container { max-width:850px; margin:0 auto; }
  .card { background:#16213e; border-radius:10px; padding:24px; margin-bottom:20px;
          box-shadow:0 4px 15px rgba(0,0,0,.3); border:1px solid #0f3460; }
  h1 { color:#e94560; margin-top:0; font-size:22px; }
  h2 { color:#e94560; margin-top:0; font-size:18px; }
  .ok   { color:#00d25b; font-weight:700; }
  .err  { color:#e94560; font-weight:700; }
  .warn { color:#ffab00; font-weight:700; }
  .info { color:#00b4d8; }
  pre  { background:#0a0a23; border:1px solid #333; padding:14px;
         border-radius:6px; font-size:12px; overflow-x:auto; color:#a0ffa0;
         max-height:500px; overflow-y:auto; white-space:pre-wrap; word-break:break-all; }
  table { width:100%; border-collapse:collapse; margin:10px 0; }
  th,td { padding:10px 14px; border:1px solid #0f3460; font-size:13px; text-align:left; }
  th { background:#0f3460; color:#e0e0e0; }
  td { color:#ccc; }
  code { background:#0a0a23; padding:2px 6px; border-radius:4px; font-size:12px; color:#ffab00; }
  .btn { background:#e94560; color:#fff; padding:10px 24px; border:none;
         border-radius:6px; font-size:14px; cursor:pointer; text-decoration:none;
         display:inline-block; margin-top:10px; transition:background .2s; }
  .btn:hover { background:#c81e45; }
  input[type=email] { background:#0a0a23; border:1px solid #0f3460; color:#e0e0e0; padding:10px 14px;
                      border-radius:6px; font-size:14px; width:300px; }
  .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
  .badge-ok { background:#003d1a; color:#00d25b; }
  .badge-err { background:#3d0011; color:#e94560; }
  .divider { border:none; border-top:1px solid #0f3460; margin:20px 0; }
</style>
</head>
<body>
<div class="container">

<div class="card">
  <h1>🔧 AccountMailService — SMTP Debug Tool</h1>
  <p style="color:#888;font-size:13px;margin-bottom:0">
    Testing OAuth2 SMTP credentials for <code><?= htmlspecialchars($senderEmail) ?></code>
    &nbsp;|&nbsp; Client ID: <code><?= substr($clientId, 0, 20) ?>...</code>
  </p>
</div>

<!-- ── Step 1: Credential Summary ── -->
<div class="card">
  <h2>📋 Step 1 — Credential Check</h2>
  <table>
    <tr><th>Parameter</th><th>Value</th><th>Status</th></tr>
    <tr>
      <td>SMTP Host</td>
      <td><code><?= $smtpHost ?></code></td>
      <td><span class="ok">✅</span></td>
    </tr>
    <tr>
      <td>SMTP Port</td>
      <td><code><?= $smtpPort ?></code></td>
      <td><span class="ok">✅</span></td>
    </tr>
    <tr>
      <td>Sender Email</td>
      <td><code><?= $senderEmail ?></code></td>
      <td><span class="info">ℹ️ Must match OAuth consent</span></td>
    </tr>
    <tr>
      <td>Client ID</td>
      <td><code><?= htmlspecialchars($clientId) ?></code></td>
      <td><?= !empty($clientId) ? '<span class="ok">✅ Set</span>' : '<span class="err">❌ MISSING</span>' ?></td>
    </tr>
    <tr>
      <td>Client Secret</td>
      <td><code><?= substr($clientSecret, 0, 10) ?>...</code></td>
      <td><?= !empty($clientSecret) ? '<span class="ok">✅ Set</span>' : '<span class="err">❌ MISSING</span>' ?></td>
    </tr>
    <tr>
      <td>Refresh Token</td>
      <td><code><?= substr($refreshToken, 0, 20) ?>...</code></td>
      <td><?= !empty($refreshToken) ? '<span class="ok">✅ Set</span>' : '<span class="err">❌ MISSING</span>' ?></td>
    </tr>
  </table>
</div>

<!-- ── Step 2: DNS / Connectivity Check ── -->
<div class="card">
  <h2>🌐 Step 2 — Network Connectivity</h2>
  <?php
  $dnsResult = @dns_get_record('smtp.gmail.com', DNS_MX);
  $canConnect = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 5);
  ?>
  <table>
    <tr><th>Check</th><th>Result</th></tr>
    <tr>
      <td>DNS resolve <code>smtp.gmail.com</code></td>
      <td><?= $dnsResult ? '<span class="ok">✅ Resolved</span>' : '<span class="err">❌ DNS Failed</span>' ?></td>
    </tr>
    <tr>
      <td>TCP connect <code>smtp.gmail.com:587</code></td>
      <td><?= $canConnect ? '<span class="ok">✅ Connected</span>' : '<span class="err">❌ Failed — ' . htmlspecialchars($errstr) . ' (errno: ' . $errno . ')</span>' ?></td>
    </tr>
    <tr>
      <td>PHP OpenSSL extension</td>
      <td><?= extension_loaded('openssl') ? '<span class="ok">✅ Loaded</span>' : '<span class="err">❌ NOT loaded — STARTTLS will fail</span>' ?></td>
    </tr>
  </table>
  <?php if ($canConnect) fclose($canConnect); ?>
</div>

<!-- ── Step 3: OAuth Token Test ── -->
<div class="card">
  <h2>🔑 Step 3 — OAuth2 Token Exchange Test</h2>
  <p class="info" style="font-size:13px">Attempting to exchange the refresh token for an access token via Google...</p>
  <?php
  $tokenDebug = '';
  $tokenOk = false;
  $accessToken = '';
  try {
      $provider = new Google([
          'clientId'     => $clientId,
          'clientSecret' => $clientSecret,
      ]);
      
      $grant = new \League\OAuth2\Client\Grant\RefreshToken();
      $token = $provider->getAccessToken($grant, ['refresh_token' => $refreshToken]);
      $accessToken = $token->getToken();
      $expires = $token->getExpires();
      $tokenOk = true;
      $tokenDebug = "Access Token: " . substr($accessToken, 0, 30) . "...\n";
      $tokenDebug .= "Expires: " . date('Y-m-d H:i:s', $expires) . "\n";
      $tokenDebug .= "Token length: " . strlen($accessToken) . " chars";
  } catch (\Throwable $e) {
      $tokenDebug = "Exception: " . $e->getMessage() . "\n\n";
      $tokenDebug .= "Trace:\n" . $e->getTraceAsString();
  }
  ?>
  <?php if ($tokenOk): ?>
    <p class="ok" style="font-size:15px">✅ Token exchange SUCCESSFUL — OAuth credentials are valid!</p>
    <pre><?= htmlspecialchars($tokenDebug) ?></pre>
  <?php else: ?>
    <p class="err" style="font-size:15px">❌ Token exchange FAILED — This is the root cause!</p>
    <pre><?= htmlspecialchars($tokenDebug) ?></pre>
    <hr class="divider">
    <h3 style="color:#ffab00;margin-bottom:10px">🛠 Common Fixes:</h3>
    <ol style="color:#ccc;line-height:1.8">
      <li><strong>Refresh token expired</strong> — If the Google Cloud app is in "Testing" mode, tokens expire after 7 days. Move to "Production" or regenerate.</li>
      <li><strong>Refresh token revoked</strong> — The user may have revoked access at <a href="https://myaccount.google.com/permissions" style="color:#00b4d8" target="_blank">myaccount.google.com/permissions</a>.</li>
      <li><strong>Wrong client ID/secret</strong> — Ensure the client ID and secret match the Google Cloud project that generated the refresh token.</li>
      <li><strong>Gmail API not enabled</strong> — Go to <a href="https://console.cloud.google.com/apis/library/gmail.googleapis.com" style="color:#00b4d8" target="_blank">Google Cloud Console → Gmail API</a> and enable it for project <code>684667519692</code>.</li>
      <li><strong>Google Workspace admin restriction</strong> — The workspace admin may have blocked third-party app access for <code>tech@dpremiumhomes.com</code>.</li>
    </ol>
  <?php endif; ?>
</div>

<!-- ── Step 4: Send Test Email ── -->
<div class="card">
  <h2>📧 Step 4 — Send Test Email</h2>
  <?php if (!$testToEmail): ?>
    <form method="get" action="">
      <p>Enter an email address to send a test message:</p>
      <input type="email" name="email" placeholder="your@email.com" required>
      <button type="submit" class="btn">Send Test Email</button>
    </form>
    <p style="font-size:12px;color:#666;margin-top:10px">
      Or use URL: <code>?email=your@email.com</code>
    </p>
  <?php else: ?>
    <p>Sending test email to: <strong style="color:#00b4d8"><?= htmlspecialchars($testToEmail) ?></strong></p>
    <?php
    $debugOutput = '';
    $sendResult  = false;
    $sendError   = '';
    $sendErrorDetail = '';

    try {
        $mail = new PHPMailer(true);
        
        // FULL SMTP DEBUG — this is the key part
        $mail->SMTPDebug = 4; // Maximum verbosity
        $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
            $timestamp = date('H:i:s');
            $debugOutput .= "[{$timestamp}][L{$level}] " . $str . "\n";
        };

        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->Port       = $smtpPort;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout    = 30;

        $mail->AuthType = 'XOAUTH2';

        $provider = new Google([
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
        ]);

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
        $mail->Subject = '🔧 AccountMailService Debug Test — ' . date('H:i:s');
        $mail->Body    = "
        <div style='font-family:Arial;padding:30px;background:#f5f5f5;'>
          <div style='background:#fff;max-width:500px;margin:0 auto;padding:24px;border-radius:8px;border-top:4px solid #e94560;'>
            <h2 style='color:#e94560;margin-top:0;'>🔧 Debug Test — AccountMailService</h2>
            <p>This test email was sent using the <strong>tech@dpremiumhomes.com</strong> OAuth credentials.</p>
            <p>If you see this, the credentials are <strong style='color:#28a745'>working correctly</strong>.</p>
            <hr style='border:none;border-top:1px solid #eee;margin:20px 0'>
            <p style='font-size:12px;color:#888'>Sent at: " . date('d M Y H:i:s') . "</p>
          </div>
        </div>";
        $mail->AltBody = 'AccountMailService debug test — credentials working!';

        $mail->send();
        $sendResult = true;
    } catch (Exception $e) {
        $sendError = $e->getMessage();
        if (isset($mail)) {
            $sendErrorDetail = $mail->ErrorInfo;
        }
    } catch (\Throwable $t) {
        $sendError = $t->getMessage();
        $sendErrorDetail = $t->getTraceAsString();
    }
    ?>

    <?php if ($sendResult): ?>
      <p class="ok" style="font-size:16px">✅ EMAIL SENT SUCCESSFULLY!</p>
      <p>Check <strong><?= htmlspecialchars($testToEmail) ?></strong> inbox (and spam folder).</p>
      <p><span class="badge badge-ok">CREDENTIALS ARE WORKING</span></p>
    <?php else: ?>
      <p class="err" style="font-size:16px">❌ EMAIL SEND FAILED</p>
      <table>
        <tr><th>Error Message</th><td class="err"><?= htmlspecialchars($sendError) ?></td></tr>
        <?php if ($sendErrorDetail && $sendErrorDetail !== $sendError): ?>
        <tr><th>PHPMailer ErrorInfo</th><td class="err"><?= htmlspecialchars($sendErrorDetail) ?></td></tr>
        <?php endif; ?>
      </table>
    <?php endif; ?>

    <?php if ($debugOutput): ?>
      <hr class="divider">
      <h3 style="color:#ffab00">📜 Full SMTP Debug Log:</h3>
      <pre><?= htmlspecialchars($debugOutput) ?></pre>
    <?php endif; ?>

  <?php endif; ?>
</div>

<!-- ── Comparison with working credentials ── -->
<div class="card">
  <h2>⚖️ Credential Comparison (Current vs Working)</h2>
  <table>
    <tr><th>Parameter</th><th>Current (tech@) — NOT WORKING</th><th>Old (info@) — WORKING</th></tr>
    <tr>
      <td>Sender Email</td>
      <td><code>tech@dpremiumhomes.com</code></td>
      <td><code>info@dpremiumhomes.com</code></td>
    </tr>
    <tr>
      <td>Client ID prefix</td>
      <td><code>684667519692-...</code></td>
      <td><code>893215055562-...</code></td>
    </tr>
    <tr>
      <td>Client Secret prefix</td>
      <td><code>GOCSPX-9raHr...</code></td>
      <td><code>GOCSPX-Tpav4...</code></td>
    </tr>
    <tr>
      <td>Google Cloud Project</td>
      <td><code>684667519692</code></td>
      <td><code>893215055562</code></td>
    </tr>
  </table>
  <p style="font-size:12px;color:#888;margin-top:10px">
    These are <strong>different Google Cloud projects</strong> and <strong>different email accounts</strong>.
  </p>
</div>

<div class="card" style="background:#3d1f00;border-color:#ff6600">
  <strong style="color:#ff6600">⚠️ Security Notice:</strong>
  <span style="color:#ccc">Delete <code>public/debug-account-mail.php</code> after debugging!</span>
</div>

</div><!-- .container -->
</body>
</html>
