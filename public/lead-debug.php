<?php
/**
 * LEAD AJAX DEBUG v2 - DELETE AFTER USE
 * Access: https://thepremiumhomesltd.com/TPHL-ERP/lead-debug.php
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$root = dirname(__DIR__);
$config = require $root . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) die("DB FAIL: " . $db->connect_error);
$db->set_charset('utf8mb4');

$mode = $_GET['mode'] ?? 'info';

// ── MODE: Simulate full AJAX JSON response ──────────────────────────
if ($mode === 'ajax') {
    header('Content-Type: application/json');
    
    // Simulate what getLeadsAjax() does
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $start  = isset($_POST['start'])  ? (int)$_POST['start']  : 0;
    $draw   = isset($_POST['draw'])   ? (int)$_POST['draw']   : 1;

    // Count total
    $r = $db->query("SELECT COUNT(*) AS total FROM leads WHERE deleted_at IS NULL");
    $total = (int)($r->fetch_assoc()['total'] ?? 0);

    // Get paginated leads
    $sql = "SELECT l.id, l.full_name, l.phone_number, l.phone, l.created_at,
                   l.last_lead_action, l.campaign_name, l.call_note,
                   u.full_name AS agent_name,
                   lf.next_follow_up_date
            FROM leads AS l
            LEFT JOIN users AS u ON l.assigned_id = u.id
            LEFT JOIN (
                SELECT lf1.lead_id, lf1.next_follow_up_date
                FROM lead_follow_ups lf1
                WHERE lf1.id = (SELECT MAX(lf2.id) FROM lead_follow_ups lf2 WHERE lf2.lead_id = lf1.lead_id)
            ) lf ON lf.lead_id = l.id
            WHERE l.deleted_at IS NULL
            ORDER BY l.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ii', $length, $start);
    $stmt->execute();
    $leads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $data = [];
    foreach ($leads as $k => $lead) {
        $data[] = [
            ($start + $k + 1) . ' ' . $lead['id'],
            $lead['created_at'],
            $lead['full_name'],
            $lead['phone_number'] ?? $lead['phone'],
            $lead['campaign_name'] ?? '-',
            $lead['last_lead_action'] ?? '',
            $lead['next_follow_up_date'] ?? 'No Schedule',
            $lead['agent_name'] ?? 'No Agent',
            'actions'
        ];
    }

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $total,
        'recordsFiltered' => $total,
        'data'            => $data
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Lead Debug v2</title>
<style>
body{font-family:monospace;padding:20px;background:#0f0f23;color:#cdd6f4}
h2{color:#cba6f7}h3{color:#89b4fa}
.box{background:#1e1e2e;padding:12px 16px;border-radius:8px;margin:8px 0;border-left:4px solid #313244}
.ok{color:#a6e3a1}.slow{color:#f9e2af}.err{color:#f38ba8}
.warn{border-left-color:#f38ba8;background:#2a1626}
a{color:#89dceb}button{background:#cba6f7;border:none;color:#1e1e2e;padding:6px 14px;border-radius:6px;cursor:pointer;font-weight:bold}
pre{background:#181825;padding:10px;border-radius:6px;overflow-x:auto;font-size:12px}
</style>
</head>
<body>
<h2>🔍 Lead AJAX Debug v2</h2>

<div class="box">
<b>Server:</b> PHP <?= PHP_VERSION ?> | max_exec: <?= ini_get('max_execution_time') ?>s | memory: <?= ini_get('memory_limit') ?>
</div>

<?php
// ── TEST 1: AJAX simulation via JS fetch ─────────────────────────────
$selfUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$ajaxUrl = strtok($selfUrl, '?') . '?mode=ajax';
?>

<h3>Step 1 — Live AJAX Test (DataTables simulation)</h3>
<div class="box">
Click to simulate exactly what DataTables sends to <code>leads-ajax-data</code>:<br><br>
<button onclick="testAjax()">▶ Test AJAX Response</button>
<div id="ajaxResult" style="margin-top:10px"></div>
</div>

<script>
function testAjax() {
    const btn = document.querySelector('button');
    btn.textContent = '⏳ Loading...';
    const form = new FormData();
    form.append('draw', '1');
    form.append('start', '0');
    form.append('length', '10');
    form.append('search[value]', '');

    fetch('<?= $ajaxUrl ?>', { method: 'POST', body: form })
        .then(r => r.text())
        .then(txt => {
            btn.textContent = '✅ Done';
            let display = '';
            try {
                const json = JSON.parse(txt);
                display = `<span class="ok">✅ Valid JSON!</span><br>
                recordsTotal: <b>${json.recordsTotal}</b><br>
                recordsFiltered: <b>${json.recordsFiltered}</b><br>
                data rows: <b>${json.data.length}</b><br>
                First row: ${JSON.stringify(json.data[0]).substring(0, 100)}...`;
            } catch(e) {
                display = `<span class="err">❌ Invalid JSON! Raw output:</span><pre>${txt.substring(0, 500)}</pre>`;
            }
            document.getElementById('ajaxResult').innerHTML = display;
        })
        .catch(err => {
            btn.textContent = '❌ Error';
            document.getElementById('ajaxResult').innerHTML = `<span class="err">Fetch Error: ${err}</span>`;
        });
}
</script>

<?php
// ── TEST 2: Check the REAL leads-ajax-data endpoint ──────────────────
$baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/TPHL-ERP';
$realAjaxUrl = $baseUrl . '/leads-ajax-data';
?>

<h3>Step 2 — Real leads-ajax-data endpoint test (requires login session)</h3>
<div class="box">
URL: <code><?= $realAjaxUrl ?></code><br><br>
<button onclick="testReal()">▶ Test Real AJAX Endpoint</button>
<div id="realResult" style="margin-top:10px"></div>
</div>

<script>
function testReal() {
    const btn = document.querySelectorAll('button')[1];
    btn.textContent = '⏳ Loading...';
    const form = new FormData();
    form.append('draw', '1');
    form.append('start', '0');
    form.append('length', '10');
    form.append('search[value]', '');

    fetch('<?= $realAjaxUrl ?>', { method: 'POST', body: form, credentials: 'include' })
        .then(r => {
            const status = r.status;
            const finalUrl = r.url;
            return r.text().then(txt => ({ txt, status, finalUrl }));
        })
        .then(({ txt, status, finalUrl }) => {
            btn.textContent = '✅ Done';
            let display = `HTTP Status: <b>${status}</b> | Final URL: <code>${finalUrl}</code><br>`;
            if (status === 302 || finalUrl.includes('login') || txt.includes('Sign In')) {
                display += `<span class="err">❌ REDIRECTED TO LOGIN! Session not sent with AJAX request.</span>`;
            } else {
                try {
                    const json = JSON.parse(txt);
                    display += `<span class="ok">✅ Valid JSON!</span><br>
                    recordsTotal: <b>${json.recordsTotal}</b><br>
                    data rows: <b>${json.data.length}</b>`;
                } catch(e) {
                    display += `<span class="err">❌ Invalid JSON / PHP Error:</span><pre>${txt.substring(0, 800)}</pre>`;
                }
            }
            document.getElementById('realResult').innerHTML = display;
        })
        .catch(err => {
            btn.textContent = '❌ Error';
            document.getElementById('realResult').innerHTML = `<span class="err">Error: ${err}</span>`;
        });
}
</script>

<?php
// ── TEST 3: Check php.ini output_buffering ──────────────────────────
?>
<h3>Step 3 — PHP Config Check</h3>
<div class="box">
<b>output_buffering:</b> <?= ini_get('output_buffering') ?: 'off' ?><br>
<b>zlib.output_compression:</b> <?= ini_get('zlib.output_compression') ?: 'off' ?><br>
<b>session.cookie_samesite:</b> <?= ini_get('session.cookie_samesite') ?: '(not set)' ?><br>
<b>session.cookie_secure:</b> <?= ini_get('session.cookie_secure') ?: '0' ?><br>
</div>

<?php
// ── TEST 4: check auth guard in index.php ────────────────────────────
?>
<h3>Step 4 — Quick Query Summary</h3>
<?php
$checks = [
    "leads total" => "SELECT COUNT(*) as c FROM leads WHERE deleted_at IS NULL",
    "lead_follow_ups total" => "SELECT COUNT(*) as c FROM lead_follow_ups WHERE deleted_at IS NULL",
    "lead_bookings total" => "SELECT COUNT(*) as c FROM lead_bookings WHERE deleted_at IS NULL",
    "lead_payments total" => "SELECT COUNT(*) as c FROM lead_payments",
];
foreach ($checks as $label => $sql) {
    $t = microtime(true);
    $r = $db->query($sql);
    $c = $r ? $r->fetch_assoc()['c'] : 'ERROR';
    $ms = round((microtime(true)-$t)*1000);
    echo "<div class='box'><b>$label:</b> <span class='ok'>$c</span> | {$ms}ms</div>";
}
?>

<div class="box warn">
<b>⚠️ INSTRUCTIONS:</b><br>
1. Click "Test Real AJAX Endpoint" <b>while logged into the ERP</b><br>
2. If it says "REDIRECTED TO LOGIN" → session issue<br>
3. If it shows "Invalid JSON" → PHP error in AJAX response<br>
4. If it shows valid JSON with recordsTotal=3952 → DataTables JS issue<br><br>
<b>DELETE</b> this file from server after diagnosis!
</div>
</body>
</html>
