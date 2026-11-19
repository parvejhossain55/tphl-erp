<?php

class RecurringTaskTemplate
{
    private $db;

    public const WEEKDAY_ORDER = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Determine whether a template should generate an occurrence on $dateYmd (YYYY-MM-DD).
     */
    public static function shouldRunOnDate(array $tpl, string $dateYmd): bool
    {
        if (empty($tpl['is_active'])) {
            return false;
        }

        $startDate = !empty($tpl['start_date']) ? substr((string)$tpl['start_date'], 0, 10) : '';
        if ($startDate === '' || $dateYmd < $startDate) {
            return false;
        }

        $endDate = !empty($tpl['end_date']) ? substr((string)$tpl['end_date'], 0, 10) : null;
        if ($endDate !== null && $dateYmd > $endDate) {
            return false;
        }

        $freq = strtolower((string)($tpl['frequency'] ?? ''));
        $ts = strtotime($dateYmd);
        if ($ts === false) {
            return false;
        }

        if ($freq === 'daily') {
            return true;
        }

        $repeatDays = [];
        if (!empty($tpl['repeat_days'])) {
            $decoded = is_array($tpl['repeat_days'])
                ? $tpl['repeat_days']
                : json_decode((string)$tpl['repeat_days'], true);
            if (is_array($decoded)) {
                $repeatDays = $decoded;
            }
        }

        if ($freq === 'weekly') {
            if (empty($repeatDays)) {
                return false;
            }
            $todayDay = strtolower(date('D', $ts)); // sun..sat
            $normalized = array_map(fn($d) => strtolower(trim((string)$d)), $repeatDays);
            return in_array($todayDay, $normalized, true);
        }

        if ($freq === 'monthly') {
            if (empty($repeatDays)) {
                return false;
            }
            $dayOfMonth = (int)date('j', $ts);
            $lastDayOfMonth = (int)date('t', $ts);
            foreach ($repeatDays as $d) {
                $num = (int)$d;
                if ($num < 1 || $num > 31) {
                    continue;
                }
                // Clamp e.g. 31 -> 28/29 in Feb, 30 in Apr/Jun/Sep/Nov
                if (min($num, $lastDayOfMonth) === $dayOfMonth) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * Compute the next upcoming run date (YYYY-MM-DD), or null if paused/ended.
     */
    public static function computeNextRunDate(array $tpl, ?string $todayYmd = null): ?string
    {
        if (empty($tpl['is_active'])) {
            return null;
        }

        $today = $todayYmd ?: date('Y-m-d');
        $start = !empty($tpl['start_date']) ? substr((string)$tpl['start_date'], 0, 10) : $today;
        $end = !empty($tpl['end_date']) ? substr((string)$tpl['end_date'], 0, 10) : null;
        $lastGen = !empty($tpl['last_generated_date']) ? substr((string)$tpl['last_generated_date'], 0, 10) : null;

        $cursor = ($start > $today) ? $start : $today;
        if ($lastGen !== null && $cursor <= $lastGen) {
            $cursor = date('Y-m-d', strtotime($lastGen . ' +1 day'));
        }

        if ($end !== null && $cursor > $end) {
            return null;
        }

        for ($i = 0; $i < 366; $i++) {
            $d = ($i === 0) ? $cursor : date('Y-m-d', strtotime($cursor . " +{$i} days"));
            if ($end !== null && $d > $end) {
                return null;
            }
            if (self::shouldRunOnDate($tpl, $d)) {
                return $d;
            }
        }

        return null;
    }

    /**
     * Build a concise human-readable schedule summary for UI badges.
     */
    public static function formatScheduleLabel(array $tpl): string
    {
        $freq = strtolower((string)($tpl['frequency'] ?? 'daily'));
        $repeatDays = [];
        if (!empty($tpl['repeat_days'])) {
            $decoded = is_array($tpl['repeat_days'])
                ? $tpl['repeat_days']
                : json_decode((string)$tpl['repeat_days'], true);
            if (is_array($decoded)) {
                $repeatDays = $decoded;
            }
        }

        if ($freq === 'weekly') {
            $dayLabels = array_map(fn($d) => ucfirst(strtolower((string)$d)), $repeatDays);
            $base = 'Weekly' . (!empty($dayLabels) ? ' (' . implode(', ', $dayLabels) . ')' : '');
        } elseif ($freq === 'monthly') {
            $nums = array_map('intval', $repeatDays);
            sort($nums);
            $base = 'Monthly' . (!empty($nums) ? ' (Day ' . implode(', ', $nums) . ')' : '');
        } else {
            $base = 'Daily';
        }

        $endStr = !empty($tpl['end_date'])
            ? 'Until ' . date('j M Y', strtotime($tpl['end_date']))
            : 'Unlimited';

        return $base . ' · ' . $endStr;
    }

    /**
     * Generate all due tasks for a given date (defaults to today).
     * Safe to call repeatedly (guarded by last_generated_date + UNIQUE constraint).
     */
    public function generateDueForDate(?string $dateYmd = null, ?int $limit = null): array
    {
        $today = $dateYmd ?: date('Y-m-d');
        $summary = ['date' => $today, 'checked' => 0, 'created' => 0, 'task_ids' => []];

        $sql = "
            SELECT *
            FROM recurring_task_templates
            WHERE is_active = 1
              AND start_date <= ?
              AND (end_date IS NULL OR end_date >= ?)
              AND (last_generated_date IS NULL OR last_generated_date < ?)
            ORDER BY id ASC
        ";
        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $st = $this->db->prepare($sql);
        if (!$st) {
            return $summary;
        }
        $st->bind_param("sss", $today, $today, $today);
        $st->execute();
        $res = $st->get_result();
        $templates = [];
        while ($row = $res->fetch_assoc()) {
            $templates[] = $row;
        }
        $st->close();

        foreach ($templates as $tpl) {
            $summary['checked']++;
            $newTaskId = $this->generateForTemplate($tpl, $today);
            if ($newTaskId !== null) {
                $summary['created']++;
                $summary['task_ids'][] = $newTaskId;
            }
        }

        return $summary;
    }

    /**
     * Generate a single occurrence task for $tpl on $dateYmd if scheduled.
     * Returns the inserted task ID, or null if not scheduled / already exists.
     */
    public function generateForTemplate(array $tpl, string $dateYmd): ?int
    {
        $tplId = (int)($tpl['id'] ?? 0);
        if ($tplId <= 0) {
            return null;
        }

        $shouldRun = self::shouldRunOnDate($tpl, $dateYmd);

        // Even if not scheduled today (e.g., weekly Tue checked on Mon), advance last_generated_date
        // so lazy triggers do not re-scan this template on every page load today.
        if (!$shouldRun) {
            $this->touchLastGeneratedDate($tplId, $dateYmd);
            return null;
        }

        // Reset checklist completion flags for the fresh occurrence
        $checklistData = $tpl['checklist_data'] ?? null;
        if (!empty($checklistData)) {
            $decodedCl = json_decode((string)$checklistData, true);
            if (is_array($decodedCl)) {
                foreach ($decodedCl as &$item) {
                    $item['completed'] = false;
                }
                unset($item);
                $checklistData = json_encode($decodedCl, JSON_UNESCAPED_UNICODE);
            }
        }

        $deadlineDt = $dateYmd . ' 00:00:00';
        $workingHour = ($tpl['working_hour'] !== null && $tpl['working_hour'] !== '')
            ? (float)$tpl['working_hour']
            : null;
        $todoProjectId = !empty($tpl['todo_project_id']) ? (int)$tpl['todo_project_id'] : null;
        $projectId = !empty($tpl['project_id']) ? (int)$tpl['project_id'] : null;
        $userId = (int)$tpl['user_id'];
        $assignedBy = (int)$tpl['assigned_by'];

        $stmt = $this->db->prepare("
            INSERT IGNORE INTO tasks (
                title, expected_output, user_id, assigned_by, todo_project_id,
                description, checklist_data, start_date, deadline, priority,
                working_hour, status, progress, employee_accept, mentioned_user_ids,
                cc_user_ids, attachment_image, project_id,
                is_recurring, recurrence_type, recurrence_end_date, recurrence_custom_dates,
                collaborator_user_ids, recurring_template_id, occurrence_date, created_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, 'pending', 0, 0, ?,
                ?, ?, ?,
                1, ?, ?, ?,
                ?, ?, ?, NOW()
            )
        ");

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param(
            "ssiiisssssdsssissssis",
            $tpl['title'],
            $tpl['expected_output'],
            $userId,
            $assignedBy,
            $todoProjectId,
            $tpl['description'],
            $checklistData,
            $dateYmd,
            $deadlineDt,
            $tpl['priority'],
            $workingHour,
            $tpl['mentioned_user_ids'],
            $tpl['cc_user_ids'],
            $tpl['attachment_image'],
            $projectId,
            $tpl['frequency'],
            $tpl['end_date'],
            $tpl['repeat_days'],
            $tpl['collaborator_user_ids'],
            $tplId,
            $dateYmd
        );

        $newTaskId = null;
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $newTaskId = (int)$stmt->insert_id;
        }
        $stmt->close();

        $this->touchLastGeneratedDate($tplId, $dateYmd);

        if ($newTaskId !== null) {
            $this->logActivity($newTaskId, $assignedBy, "Auto-generated from Recurring Schedule #R-{$tplId}.");
            $this->notifyParticipants($newTaskId, $tpl);
        }

        return $newTaskId;
    }

    private function touchLastGeneratedDate(int $tplId, string $dateYmd): void
    {
        $st = $this->db->prepare("
            UPDATE recurring_task_templates
            SET last_generated_date = ?
            WHERE id = ? AND (last_generated_date IS NULL OR last_generated_date < ?)
        ");
        if ($st) {
            $st->bind_param("sis", $dateYmd, $tplId, $dateYmd);
            $st->execute();
            $st->close();
        }
    }

    private function logActivity(int $taskId, int $actorId, string $message): void
    {
        $st = $this->db->prepare("
            INSERT INTO task_activities (task_id, user_id, type, activity, created_at)
            VALUES (?, ?, 'system', ?, NOW())
        ");
        if ($st) {
            $st->bind_param("iis", $taskId, $actorId, $message);
            $st->execute();
            $st->close();
        }
    }

    /**
     * Notify Assignee and Collaborators on new occurrence generation.
     * Reviewer is notified upon Submit for Review; CC follows passively.
     */
    private function notifyParticipants(int $taskId, array $tpl): void
    {
        $assignedBy = (int)($tpl['assigned_by'] ?? 0);
        $assigneeId = (int)($tpl['user_id'] ?? 0);
        $title = trim((string)($tpl['title'] ?? 'Recurring Task'));

        $recipients = [];
        if ($assigneeId > 0) {
            $recipients[$assigneeId] = "New recurring task T-{$taskId}: {$title}";
        }

        if (!empty($tpl['collaborator_user_ids'])) {
            $collabs = json_decode((string)$tpl['collaborator_user_ids'], true);
            if (is_array($collabs)) {
                foreach ($collabs as $cid) {
                    $cid = (int)$cid;
                    if ($cid > 0 && !isset($recipients[$cid])) {
                        $recipients[$cid] = "You are a collaborator on recurring task T-{$taskId}: {$title}";
                    }
                }
            }
        }

        foreach ($recipients as $uid => $msg) {
            $st = $this->db->prepare("
                INSERT INTO notifications (user_id, task_id, assigned_by, message, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            if ($st) {
                $st->bind_param("iiis", $uid, $taskId, $assignedBy, $msg);
                $st->execute();
                $st->close();
            }
        }
    }
}
