<?php

require_once dirname(__DIR__) . '/core/Model.php';

class MealProject extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: db();
        parent::__construct($this->db);
    }

    /**
     * Get all meal projects ordered by newest first
     */
    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM meal_projects ORDER BY from_date DESC, id DESC");
        if (!$stmt) {
            return [];
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }

    /**
     * Get meal project by ID
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM meal_projects WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $project = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $project;
    }

    /**
     * Get latest active meal project
     */
    public function getLatest()
    {
        $stmt = $this->db->prepare("SELECT * FROM meal_projects ORDER BY from_date DESC, id DESC LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $project = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $project;
    }

    /**
     * Create a new meal project
     */
    public function create($data)
    {
        $name = trim($data['name'] ?? '');
        $standardAmount = (float) ($data['standard_meal_amount'] ?? 120.00);
        $fromDate = trim($data['from_date'] ?? '');
        $toDate = trim($data['to_date'] ?? '');
        $payrollProjectId = !empty($data['payroll_project_id']) ? (int)$data['payroll_project_id'] : null;
        $createdBy = !empty($data['created_by']) ? (int)$data['created_by'] : null;

        $stmt = $this->db->prepare("
            INSERT INTO meal_projects (name, standard_meal_amount, from_date, to_date, payroll_project_id, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("sdssii", $name, $standardAmount, $fromDate, $toDate, $payrollProjectId, $createdBy);
        $result = $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();

        return $result ? $insertId : false;
    }

    /**
     * Update project
     */
    public function update($id, $data)
    {
        $name = trim($data['name'] ?? '');
        $standardAmount = (float) ($data['standard_meal_amount'] ?? 120.00);
        $fromDate = trim($data['from_date'] ?? '');
        $toDate = trim($data['to_date'] ?? '');
        $status = $data['status'] ?? 'active';

        $stmt = $this->db->prepare("
            UPDATE meal_projects 
            SET name = ?, standard_meal_amount = ?, from_date = ?, to_date = ?, status = ?
            WHERE id = ?
        ");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("sdsssi", $name, $standardAmount, $fromDate, $toDate, $status, $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Update only standard meal amount for a project
     */
    public function updateStandardRate($id, $rate)
    {
        $id = (int)$id;
        $rate = (float)$rate;
        $stmt = $this->db->prepare("UPDATE meal_projects SET standard_meal_amount = ? WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("di", $rate, $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Delete project and related records
     */
    public function delete($id)
    {
        $id = (int)$id;
        $this->db->query("DELETE FROM meal_records WHERE meal_project_id = $id");
        $this->db->query("DELETE FROM meal_guest_records WHERE meal_project_id = $id");
        $stmt = $this->db->prepare("DELETE FROM meal_projects WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $res = $stmt->execute();
            $stmt->close();
            return $res;
        }
        return false;
    }

    /**
     * Calculate 26th-to-25th Cycle Information for any given date
     * E.g. date '2026-11-04' -> from '2026-10-26' to '2026-11-25', suggested name 'November Meal (Oct 26-Nov 25)-2026'
     */
    public function calculateCycleInfo($date = null)
    {
        if (!$date) {
            date_default_timezone_set('Asia/Dhaka');
            $date = date('Y-m-d');
        }

        $cycleStr = $this->getCycleForDate($date); // e.g. "2026-11"
        list($year, $month) = explode('-', $cycleStr);
        $range = $this->getCycleDateRange((int)$year, (int)$month);

        $fromDate = date('Y-m-d', strtotime($range['start']));
        $toDate = date('Y-m-d', strtotime($range['end']));

        $monthName = date('F', strtotime($toDate)); // e.g. "November"
        $fromFormatted = date('M j', strtotime($fromDate)); // e.g. "Oct 26"
        $toFormatted = date('M j', strtotime($toDate));     // e.g. "Nov 25"
        $cycleYear = date('Y', strtotime($toDate));

        $suggestedName = "{$monthName} Meal ({$fromFormatted}-{$toFormatted})-{$cycleYear}";

        return [
            'cycle' => $cycleStr,
            'year' => (int)$year,
            'month' => (int)$month,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'month_name' => $monthName,
            'suggested_name' => $suggestedName
        ];
    }

    /**
     * Get or automatically create the standard 26th-to-25th Meal Project for a given date
     */
    public function getOrCreateCycleForDate($date = null, $autoSync = true)
    {
        $info = $this->calculateCycleInfo($date);
        return $this->getOrCreateCycleForRange($info['from_date'], $info['to_date'], $info['suggested_name'], null, $autoSync);
    }

    /**
     * Get or automatically create a Meal Project for a specific date range
     */
    public function getOrCreateCycleForRange($fromDate, $toDate, $suggestedName = null, $payrollProjectId = null, $autoSync = true)
    {
        $fromDate = trim($fromDate);
        $toDate = trim($toDate);
        if (empty($fromDate) || empty($toDate)) {
            return null;
        }

        // 1. Check if an exact meal project exists
        $stmt = $this->db->prepare("SELECT * FROM meal_projects WHERE from_date = ? AND to_date = ? ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $fromDate, $toDate);
            $stmt->execute();
            $res = $stmt->get_result();
            $existing = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            if ($existing) {
                // If payroll project is passed and meal project isn't linked yet, link it
                if ($payrollProjectId && empty($existing['payroll_project_id'])) {
                    $mId = (int)$existing['id'];
                    $pId = (int)$payrollProjectId;
                    $this->db->query("UPDATE meal_projects SET payroll_project_id = $pId WHERE id = $mId");
                    $existing['payroll_project_id'] = $pId;
                }
                return $existing;
            }
        }

        // 2. Derive a clean readable name if not passed
        if (empty($suggestedName)) {
            $mName = date('F', strtotime($toDate));
            $fTxt = date('M j', strtotime($fromDate));
            $tTxt = date('M j', strtotime($toDate));
            $yr = date('Y', strtotime($toDate));
            $suggestedName = "{$mName} Meal ({$fTxt}-{$tTxt})-{$yr}";
        }

        // 3. Create the meal project automatically
        $createdBy = $_SESSION['user']['id'] ?? 1;
        $newId = $this->create([
            'name' => $suggestedName,
            'standard_meal_amount' => 120.00,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'payroll_project_id' => $payrollProjectId,
            'created_by' => $createdBy
        ]);

        if (!$newId) {
            return null;
        }

        // 4. Auto-sync attendance records from Tipsoi & local DB
        if ($autoSync) {
            try {
                require_once __DIR__ . '/MealRecord.php';
                $mealRecordModel = new MealRecord($this->db);
                $mealRecordModel->syncAttendanceWithMealRecords($newId);
            } catch (\Throwable $te) {
                error_log("Auto meal project creation sync failed: " . $te->getMessage());
            }
        }

        return $this->getById($newId);
    }
}
