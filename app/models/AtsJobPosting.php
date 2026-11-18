<?php

class AtsJobPosting extends Model
{
    protected $table = 'ats_job_postings';

    /**
     * Get all job postings with applicant counts for HR panel
     */
    public function getAll(array $filters = []): array
    {
        $conditions = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($filters['status'])) {
            $conditions[] = "j.status = ?";
            $params[] = trim($filters['status']);
            $types .= "s";
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = "j.department_id = ?";
            $params[] = (int) $filters['department_id'];
            $types .= "i";
        }
        if (!empty($filters['employment_type'])) {
            $conditions[] = "j.employment_type = ?";
            $params[] = trim($filters['employment_type']);
            $types .= "s";
        }
        if (!empty($filters['search'])) {
            $search = "%" . trim($filters['search']) . "%";
            $conditions[] = "(j.title LIKE ? OR j.location LIKE ? OR j.department_name LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "sss";
        }

        $where = implode(" AND ", $conditions);
        $sql = "
            SELECT j.*,
                   COALESCE(c.applicants_count, 0) AS applicants_count
            FROM {$this->table} j
            LEFT JOIN (
                SELECT job_id, COUNT(*) AS applicants_count
                FROM ats_candidates
                WHERE deleted_at IS NULL AND job_id IS NOT NULL
                GROUP BY job_id
            ) c ON c.job_id = j.id
            WHERE {$where}
            ORDER BY j.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    /**
     * Get KPI summary counts for HR Job Postings page
     */
    public function getStats(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'Published' THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) AS draft,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS closed
            FROM {$this->table}
        ";
        $res = $this->db->query($sql);
        $stats = $res ? $res->fetch_assoc() : [];

        $appRes = $this->db->query("SELECT COUNT(*) AS total_applicants FROM ats_candidates WHERE deleted_at IS NULL AND job_id IS NOT NULL");
        $appRow = $appRes ? $appRes->fetch_assoc() : [];

        return [
            'total'            => (int) ($stats['total'] ?? 0),
            'published'        => (int) ($stats['published'] ?? 0),
            'draft'            => (int) ($stats['draft'] ?? 0),
            'closed'           => (int) ($stats['closed'] ?? 0),
            'total_applicants' => (int) ($appRow['total_applicants'] ?? 0),
        ];
    }

    /**
     * Get published jobs for public Career Page API
     */
    public function getPublished(array $filters = []): array
    {
        $conditions = ["status = 'Published'", "(deadline IS NULL OR deadline >= CURDATE())"];
        $params = [];
        $types = "";

        if (!empty($filters['department_id'])) {
            $conditions[] = "department_id = ?";
            $params[] = (int) $filters['department_id'];
            $types .= "i";
        }
        if (!empty($filters['department'])) {
            $conditions[] = "department_name = ?";
            $params[] = trim($filters['department']);
            $types .= "s";
        }
        if (!empty($filters['employment_type'])) {
            $conditions[] = "employment_type = ?";
            $params[] = trim($filters['employment_type']);
            $types .= "s";
        }

        $where = implode(" AND ", $conditions);
        $sql = "
            SELECT id, title, slug, department_id, department_name,
                   employment_type, location, salary_range, deadline,
                   vacancies, description, requirements, created_at
            FROM {$this->table}
            WHERE {$where}
            ORDER BY (deadline IS NULL) ASC, deadline ASC, id DESC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function create(array $data): int|bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (
                title, slug, department_id, department_name, employment_type,
                location, salary_range, deadline, vacancies, description,
                requirements, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return false;
        }

        $title = trim($data['title']);
        $slug = $this->makeSlug($title);
        $deptId = !empty($data['department_id']) ? (int) $data['department_id'] : null;
        $deptName = !empty($data['department_name']) ? trim($data['department_name']) : null;
        $empType = trim($data['employment_type'] ?? 'Full Time');
        $location = trim($data['location'] ?? 'Gulshan, Dhaka');
        $salaryRange = !empty($data['salary_range']) ? trim($data['salary_range']) : null;
        $deadline = !empty($data['deadline']) ? $data['deadline'] : null;
        $vacancies = max(1, (int) ($data['vacancies'] ?? 1));
        $description = !empty($data['description']) ? trim($data['description']) : null;
        $requirements = !empty($data['requirements']) ? trim($data['requirements']) : null;
        $status = in_array($data['status'] ?? '', ['Draft', 'Published', 'Closed'], true) ? $data['status'] : 'Draft';
        $createdBy = !empty($data['created_by']) ? (int) $data['created_by'] : null;

        $stmt->bind_param(
            "ssisssssisssi",
            $title, $slug, $deptId, $deptName, $empType,
            $location, $salaryRange, $deadline, $vacancies, $description,
            $requirements, $status, $createdBy
        );

        if ($stmt->execute()) {
            $id = $this->db->insert_id;
            $stmt->close();
            return $id;
        }
        $stmt->close();
        return false;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET
                title = ?, slug = ?, department_id = ?, department_name = ?,
                employment_type = ?, location = ?, salary_range = ?, deadline = ?,
                vacancies = ?, description = ?, requirements = ?, status = ?
            WHERE id = ?
        ");
        if (!$stmt) {
            return false;
        }

        $title = trim($data['title']);
        $slug = $this->makeSlug($title);
        $deptId = !empty($data['department_id']) ? (int) $data['department_id'] : null;
        $deptName = !empty($data['department_name']) ? trim($data['department_name']) : null;
        $empType = trim($data['employment_type'] ?? 'Full Time');
        $location = trim($data['location'] ?? 'Gulshan, Dhaka');
        $salaryRange = !empty($data['salary_range']) ? trim($data['salary_range']) : null;
        $deadline = !empty($data['deadline']) ? $data['deadline'] : null;
        $vacancies = max(1, (int) ($data['vacancies'] ?? 1));
        $description = !empty($data['description']) ? trim($data['description']) : null;
        $requirements = !empty($data['requirements']) ? trim($data['requirements']) : null;
        $status = in_array($data['status'] ?? '', ['Draft', 'Published', 'Closed'], true) ? $data['status'] : 'Draft';

        $stmt->bind_param(
            "ssisssssisssi",
            $title, $slug, $deptId, $deptName, $empType,
            $location, $salaryRange, $deadline, $vacancies, $description,
            $requirements, $status, $id
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['Draft', 'Published', 'Closed'], true)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ? WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("si", $status, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function hasCandidateApplied(int $jobId, string $email, string $phone): bool
    {
        $stmt = $this->db->prepare("
            SELECT id FROM ats_candidates
            WHERE job_id = ? AND deleted_at IS NULL AND (email = ? OR phone = ?)
            LIMIT 1
        ");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iss", $jobId, $email, $phone);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    private function makeSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}
