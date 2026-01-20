<?php

class Review extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /* =========================
       Get All Projects
    ========================== */
    public function getAll()
    {
        // ধরলাম review date অনুযায়ী latest first
        $sql = $this->db->prepare(
            "SELECT * FROM property_reviews 
         WHERE status = 'active' 
           AND deleted_at IS NULL 
         ORDER BY review_date DESC"
        );

        if (!$sql) {
            echo "SQL Prepare Error: " . $this->db->error;
            return [];
        }

        $sql->execute();
        $result = $sql->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function createReview($data, $reviewer_image, $review_video_file = null)
    {
        // -----------------------------
        // 1 Upload Image
        // -----------------------------
        $reviewer_photo = '';
        if (!empty($_FILES[$reviewer_image]['name'])) {
            $reviewer_photo = uploadImage($reviewer_image, 'reviewers', 'fixed', 300, 300);
        }

        // -----------------------------
        // 2 Handle Video
        // -----------------------------
        $videoUrl = '';

        if (!empty($_FILES[$review_video_file]['name'])) {
            $ext = pathinfo($_FILES[$review_video_file]['name'], PATHINFO_EXTENSION);
            $allowed = ['mp4', 'webm', 'ogg'];

            if (in_array(strtolower($ext), $allowed)) {
                $uploadDir = 'uploads/reviews/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $videoName = 'review_' . time() . '.' . $ext;
                move_uploaded_file($_FILES[$review_video_file]['tmp_name'], $uploadDir . $videoName);
                $videoUrl = $uploadDir . $videoName;
            }
        } elseif (!empty($data['review_video_url'])) {
            $videoUrl = $data['review_video_url'];
        }

        // -----------------------------
        // 3 Prepare SQL
        // -----------------------------
        $sql = $this->db->prepare(
            "INSERT INTO property_reviews 
        (reviewer_name, reviewer_role, reviewer_image, review_video_url, rating, review_text, review_date, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$sql) {
            echo "SQL Prepare Error: " . $this->db->error;
            return false;
        }

        // -----------------------------
        // 4 Bind Parameters
        // -----------------------------
        $sql->bind_param(
            "ssssisssi",
            $data['reviewer_name'],
            $data['reviewer_role'],
            $reviewer_photo,
            $videoUrl,
            $data['rating'],
            $data['review_text'],
            $data['review_date'],
            $data['status'],
            $data['created_by']
        );

        if ($sql->execute()) {
            return true; // or return $this->db->insert_id;
        } else {
            echo "SQL Execute Error: " . $sql->error;
            return false;
        }
    }



    public function find($id)
    {
        $sql = $this->db->prepare("SELECT * FROM property_reviews WHERE id = ? AND deleted_at IS NULL");
        if (!$sql) {
            echo "SQL Prepare Error: " . $this->db->error;
            return false;
        }

        $sql->bind_param('i', $id);

        if ($sql->execute()) {
            $result = $sql->get_result();
            return $result->fetch_assoc(); // single row as associative array
        } else {
            echo "SQL Execute Error: " . $sql->error;
            return false;
        }
    }

public function update($data, $id, $reviewer_image, $review_video_file = null)
{
    // -----------------------------
    // 1 Upload Image
    // -----------------------------
    $reviewer_photo = '';
    if (!empty($_FILES[$reviewer_image]['name'])) {
        $reviewer_photo = uploadImage($reviewer_image, 'reviewers', 'fixed', 300, 300);
    } else {
        // যদি নতুন image না আসে, আগের image রাখ
        $existing = $this->find($id);
        $reviewer_photo = $existing['reviewer_image'] ?? '';
    }

    // -----------------------------
    // 2 Handle Video
    // -----------------------------
    $videoUrl = '';
    
    // Case 1: User uploaded a video file
    if (!empty($_FILES[$review_video_file]['name'])) {
        $ext = pathinfo($_FILES[$review_video_file]['name'], PATHINFO_EXTENSION);
        $allowed = ['mp4', 'webm', 'ogg'];

        if (in_array(strtolower($ext), $allowed)) {
            $uploadDir = 'uploads/reviews/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $videoName = 'review_'.time().'.'.$ext;
            move_uploaded_file($_FILES[$review_video_file]['tmp_name'], $uploadDir . $videoName);
            $videoUrl = $uploadDir . $videoName;
        }
    } 
    // Case 2: URL from form
    elseif (!empty($data['review_video_url'])) {
        $videoUrl = $data['review_video_url'];
    } else {
        // যদি নতুন URL বা file না আসে, আগের value রাখ
        $existing = $existing ?? $this->find($id);
        $videoUrl = $existing['review_video_url'] ?? '';
    }

    // -----------------------------
    // 3 Prepare SQL
    // -----------------------------
    $sql = $this->db->prepare(
        "UPDATE property_reviews SET
            reviewer_name = ?,
            reviewer_role = ?,
            reviewer_image = ?,
            review_video_url = ?,
            rating = ?,
            review_text = ?,
            review_date = ?,
            status = ?
         WHERE id = ?"
    );

    if (!$sql) {
        echo "SQL Prepare Error: " . $this->db->error;
        return false;
    }

    // -----------------------------
    // 4️⃣ Bind Parameters
    // -----------------------------
    $sql->bind_param(
        "ssssisssi",
        $data['reviewer_name'],
        $data['reviewer_role'],
        $reviewer_photo,
        $videoUrl,
        $data['rating'],
        $data['review_text'],
        $data['review_date'],
        $data['status'],
        $id
    );

    // -----------------------------
    // 5 Execute
    // -----------------------------
    if ($sql->execute()) {
        return true;
    } else {
        echo "SQL Execute Error: " . $sql->error;
        return false;
    }
}

public function delete($id)
{
    $sql = $this->db->prepare("DELETE FROM property_reviews WHERE id = ?");
    if (!$sql) {
        echo "SQL Prepare Error: " . $this->db->error;
        return false;
    }

    $sql->bind_param('i', $id);

    if ($sql->execute()) {
        return true;
    } else {
        echo "SQL Execute Error: " . $sql->error;
        return false;
    }
}

}
