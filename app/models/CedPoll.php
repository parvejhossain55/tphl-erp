<?php

class CedPoll extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Create a new poll with its options
     */
    public function createPoll($data, array $options)
    {
        $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $image = !empty($data['image']) ? trim($data['image']) : null;
        $pollType = !empty($data['poll_type']) ? $data['poll_type'] : 'single_choice';
        $status = !empty($data['status']) ? $data['status'] : 'active';
        $startDate = !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d');
        $endDate = !empty($data['end_date']) ? $data['end_date'] : null;
        $createdBy = !empty($data['created_by']) ? (int)$data['created_by'] : null;

        if (empty($title)) {
            throw new Exception("Poll title is required.");
        }

        if (empty($options) || count($options) < 2) {
            throw new Exception("At least 2 options are required for a poll.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO ced_polls (
                project_id, title, description, image, poll_type, status, start_date, end_date, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param(
            "isssssssi",
            $projectId,
            $title,
            $description,
            $image,
            $pollType,
            $status,
            $startDate,
            $endDate,
            $createdBy
        );

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Error saving poll: " . $err);
        }

        $pollId = $stmt->insert_id;
        $stmt->close();

        // Save options
        $this->saveOptions($pollId, $options);

        return $pollId;
    }

    /**
     * Save options for a poll
     */
    public function saveOptions($pollId, array $options)
    {
        $pollId = (int)$pollId;
        $stmt = $this->db->prepare("INSERT INTO ced_poll_options (poll_id, option_text, sort_order, created_at) VALUES (?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Prepare options failed: " . $this->db->error);
        }

        foreach ($options as $index => $opt) {
            $optionText = is_array($opt) ? trim($opt['option_text'] ?? '') : trim($opt);
            $sortOrder = is_array($opt) && isset($opt['sort_order']) ? (int)$opt['sort_order'] : (int)$index;

            if ($optionText !== '') {
                $stmt->bind_param("isi", $pollId, $optionText, $sortOrder);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    /**
     * Get all polls with optional filters and client context
     */
    public function getAllPolls($filters = [], $clientId = null, $isAdmin = false)
    {
        $where = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($filters['project_id'])) {
            $where[] = "cp.project_id = ?";
            $params[] = (int)$filters['project_id'];
            $types .= "i";
        }

        if (!empty($filters['status'])) {
            $where[] = "cp.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        $sql = "
            SELECT 
                cp.*,
                p.name AS project_name
            FROM ced_polls cp
            LEFT JOIN projects p ON p.id = cp.project_id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY cp.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $polls = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($polls as &$poll) {
            $this->enrichPollData($poll, $clientId, $isAdmin);
        }

        return $polls;
    }

    /**
     * Get single poll by ID
     */
    public function getPollById($id, $clientId = null, $isAdmin = false)
    {
        $id = (int)$id;
        $stmt = $this->db->prepare("
            SELECT 
                cp.*,
                p.name AS project_name
            FROM ced_polls cp
            LEFT JOIN projects p ON p.id = cp.project_id
            WHERE cp.id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $poll = $res->fetch_assoc();
        $stmt->close();

        if (!$poll) {
            return null;
        }

        $this->enrichPollData($poll, $clientId, $isAdmin);
        return $poll;
    }

    /**
     * Enrich poll with options, voting stats, and user vote status
     */
    protected function enrichPollData(&$poll, $clientId = null, $isAdmin = false)
    {
        $pollId = (int)$poll['id'];
        $clientId = !empty($clientId) ? (int)$clientId : null;

        // 1. Get options
        $optStmt = $this->db->prepare("
            SELECT id, poll_id, option_text, sort_order
            FROM ced_poll_options
            WHERE poll_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $optStmt->bind_param("i", $pollId);
        $optStmt->execute();
        $optRes = $optStmt->get_result();
        $options = $optRes->fetch_all(MYSQLI_ASSOC);
        $optStmt->close();

        // 2. Check client's existing vote
        $hasVoted = false;
        $userVotedOptionId = null;
        if ($clientId) {
            $vStmt = $this->db->prepare("
                SELECT option_id, remarks
                FROM ced_poll_votes
                WHERE poll_id = ? AND client_id = ?
                LIMIT 1
            ");
            $vStmt->bind_param("ii", $pollId, $clientId);
            $vStmt->execute();
            $vRes = $vStmt->get_result();
            if ($vRow = $vRes->fetch_assoc()) {
                $hasVoted = true;
                $userVotedOptionId = (int)$vRow['option_id'];
            }
            $vStmt->close();
        }

        // 3. Compute vote stats
        $statsStmt = $this->db->prepare("
            SELECT option_id, COUNT(*) AS vote_count
            FROM ced_poll_votes
            WHERE poll_id = ?
            GROUP BY option_id
        ");
        $statsStmt->bind_param("i", $pollId);
        $statsStmt->execute();
        $statsRes = $statsStmt->get_result();
        $voteCounts = [];
        $totalVotes = 0;
        while ($sRow = $statsRes->fetch_assoc()) {
            $optId = (int)$sRow['option_id'];
            $cnt = (int)$sRow['vote_count'];
            $voteCounts[$optId] = $cnt;
            $totalVotes += $cnt;
        }
        $statsStmt->close();

        // 4. Attach stats to options based on visibility rule
        // RULE: User CANNOT see vote count / percentage before voting.
        // Once they vote (or if admin is viewing), show count and percentage.
        $showResults = ($hasVoted || $isAdmin);

        foreach ($options as &$opt) {
            $optId = (int)$opt['id'];
            $opt['is_user_choice'] = ($userVotedOptionId === $optId);

            if ($showResults) {
                $optVotes = $voteCounts[$optId] ?? 0;
                $opt['votes_count'] = $optVotes;
                $opt['percentage'] = $totalVotes > 0 ? round(($optVotes / $totalVotes) * 100, 1) : 0;
            }
        }

        $poll['options'] = $options;
        $poll['has_voted'] = $hasVoted;
        $poll['user_voted_option_id'] = $userVotedOptionId;

        if ($showResults) {
            $poll['total_votes'] = $totalVotes;
        }

        // Check if poll is expired
        $poll['is_expired'] = false;
        if (!empty($poll['end_date']) && strtotime($poll['end_date']) < strtotime(date('Y-m-d'))) {
            $poll['is_expired'] = true;
        }

        // Full Image URL helper
        $poll['image_url'] = !empty($poll['image']) ? $this->buildImageUrl($poll['image']) : null;
    }

    /**
     * Cast or update vote
     */
    public function castVote($pollId, $optionId, $clientId, $remarks = null)
    {
        $pollId = (int)$pollId;
        $optionId = (int)$optionId;
        $clientId = (int)$clientId;

        if (!$pollId || !$optionId || !$clientId) {
            throw new Exception("poll_id, option_id, and client_id are required.");
        }

        // Verify poll exists and is active
        $poll = $this->getPollById($pollId);
        if (!$poll) {
            throw new Exception("Poll not found.");
        }
        if ($poll['status'] !== 'active') {
            throw new Exception("This poll is currently not active.");
        }
        if ($poll['is_expired']) {
            throw new Exception("This poll has expired.");
        }

        // Verify option belongs to this poll
        $validOption = false;
        foreach ($poll['options'] as $opt) {
            if ((int)$opt['id'] === $optionId) {
                $validOption = true;
                break;
            }
        }
        if (!$validOption) {
            throw new Exception("Selected option does not belong to this poll.");
        }

        // Insert or update existing vote (allows vote change)
        $stmt = $this->db->prepare("
            INSERT INTO ced_poll_votes (
                poll_id, option_id, client_id, remarks, created_at, updated_at
            ) VALUES (?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                option_id = VALUES(option_id),
                remarks = VALUES(remarks),
                updated_at = NOW()
        ");

        if (!$stmt) {
            throw new Exception("Prepare vote failed: " . $this->db->error);
        }

        $stmt->bind_param("iiis", $pollId, $optionId, $clientId, $remarks);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Failed to record vote: " . $err);
        }
        $stmt->close();

        // Return refreshed poll with voting results visible
        return $this->getPollById($pollId, $clientId);
    }

    /**
     * Update an existing poll
     */
    public function updatePoll($id, $data, array $newOptions = null)
    {
        $id = (int)$id;
        $existing = $this->getPollById($id, null, true);
        if (!$existing) {
            throw new Exception("Poll not found.");
        }

        $fields = [];
        $params = [];
        $types = "";

        if (isset($data['project_id'])) {
            $fields[] = "project_id = ?";
            $params[] = !empty($data['project_id']) ? (int)$data['project_id'] : null;
            $types .= "i";
        }
        if (isset($data['title'])) {
            $fields[] = "title = ?";
            $params[] = trim($data['title']);
            $types .= "s";
        }
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $params[] = trim($data['description']);
            $types .= "s";
        }
        if (isset($data['image'])) {
            $fields[] = "image = ?";
            $params[] = trim($data['image']);
            $types .= "s";
        }
        if (isset($data['poll_type'])) {
            $fields[] = "poll_type = ?";
            $params[] = $data['poll_type'];
            $types .= "s";
        }
        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $params[] = $data['status'];
            $types .= "s";
        }
        if (isset($data['start_date'])) {
            $fields[] = "start_date = ?";
            $params[] = $data['start_date'] ?: null;
            $types .= "s";
        }
        if (isset($data['end_date'])) {
            $fields[] = "end_date = ?";
            $params[] = $data['end_date'] ?: null;
            $types .= "s";
        }

        if (!empty($fields)) {
            $sql = "UPDATE ced_polls SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = ?";
            $params[] = $id;
            $types .= "i";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare update failed: " . $this->db->error);
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        }

        // If new options are provided:
        // If the poll has no votes yet, we can safely replace options.
        // If votes exist, we only add new options or update existing options by id to preserve vote integrity.
        if ($newOptions !== null && !empty($newOptions)) {
            $hasVotes = ($existing['total_votes'] ?? 0) > 0;
            if (!$hasVotes) {
                // Delete existing options and insert fresh
                $this->db->query("DELETE FROM ced_poll_options WHERE poll_id = $id");
                $this->saveOptions($id, $newOptions);
            } else {
                // Add any newly provided options that don't match existing
                $existingTexts = array_map('strtolower', array_column($existing['options'], 'option_text'));
                foreach ($newOptions as $idx => $opt) {
                    $optText = is_array($opt) ? trim($opt['option_text'] ?? '') : trim($opt);
                    if ($optText !== '' && !in_array(strtolower($optText), $existingTexts)) {
                        $sOrder = is_array($opt) && isset($opt['sort_order']) ? (int)$opt['sort_order'] : (int)$idx;
                        $insStmt = $this->db->prepare("INSERT INTO ced_poll_options (poll_id, option_text, sort_order, created_at) VALUES (?, ?, ?, NOW())");
                        $insStmt->bind_param("isi", $id, $optText, $sOrder);
                        $insStmt->execute();
                        $insStmt->close();
                    }
                }
            }
        }

        return $this->getPollById($id, null, true);
    }

    /**
     * Delete poll and associated image file
     */
    public function deletePoll($id)
    {
        $id = (int)$id;
        $poll = $this->getPollById($id, null, true);
        if (!$poll) {
            throw new Exception("Poll not found.");
        }

        // Delete physical image file if exists
        if (!empty($poll['image'])) {
            $imagePath = dirname(dirname(__DIR__)) . '/public/' . ltrim($poll['image'], '/');
            if (file_exists($imagePath) && is_file($imagePath)) {
                @unlink($imagePath);
            }
        }

        // Cascade delete handles ced_poll_options and ced_poll_votes
        $stmt = $this->db->prepare("DELETE FROM ced_polls WHERE id = ?");
        $stmt->bind_param("i", $id);
        $res = $stmt->execute();
        $stmt->close();

        return $res;
    }

    /**
     * Helper to build public image URL
     */
    protected function buildImageUrl($imagePath)
    {
        if (empty($imagePath)) return null;
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }
        $baseUrl = rtrim($GLOBALS['base_url'] ?? '', '/');
        return $baseUrl . '/' . ltrim($imagePath, '/');
    }
}
