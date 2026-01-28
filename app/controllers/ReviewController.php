<?php

class ReviewController extends Controller
{
    protected $db;

    protected $reviewModel;


    public function __construct($db)
    {
        $this->db = $db;

        $this->reviewModel  = new Review($this->db);
    }

    /* =========================
       Project List
    ========================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $reviews = $this->reviewModel->getAll();
        $this->view("reviews/review-lists", [
            "current_route" => "review-lists",
            "page_title"    => "Review List",
            "reviews"    => $reviews,
        ]);
    }

    /* =========================
       Create Form
    ========================== */
    public function create()
    {

        $this->view("reviews/create-review", [
            "current_route"   => "create-review",
            "active"          => "create-review",
            "page_title"      => "Add review",
            "show_breadcrumb" => true,
        ]);
    }

    /* =========================
       Store Project
    ========================== */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ======================
        // Collect Form Data
        // ======================
        $data = [
            'reviewer_name'     => $_POST['reviewer_name'] ?? null,
            'reviewer_role'     => $_POST['reviewer_role'] ?? null,
            'rating'            => $_POST['rating'] ?? null,
            'review_text'       => $_POST['review_text'] ?? null,
            'review_date'       => $_POST['review_date'] ?? date('Y-m-d'),
            'status'            => $_POST['status'] ?? 'active',
            'created_by'        => $_SESSION['user']['id'] ?? null,
            'review_video_url'  => $_POST['review_video_url'] ?? null, // string column
        ];

        // ======================
        // File Inputs
        // ======================
        $reviewer_photo = 'reviewer_image';      // file input for avatar image
        $review_video_file = 'review_video_file'; // optional video upload input

        // ======================
        // Store via Model
        // ======================
        if ($this->reviewModel->createReview($data, $reviewer_photo, $review_video_file)) {
            setSuccess("Review created successfully!");
            header("Location: {$GLOBALS['base_url']}/review-lists");
            exit;
        }

        setError("Review creation failed");
        header("Location: {$GLOBALS['base_url']}/create-review");
        exit;
    }



    // public function show($id)
    // {
    //     $project = $this->reviewModel->find($id);

    //     $this->view("sales-developments/projects/show-project", [
    //         'current_route' => 'project-details',
    //         'page_title' => 'project-details',
    //         'project' => $project,
    //     ]);
    // }

    // Edit Employee Page
    public function edit($id)
    {

        $review = $this->reviewModel->find($id);

        $this->view("reviews/edit-review", [
            "current_route"   => "edit-review",
            "page_title"      => "Edit Review",
            "review" => $review,
            "show_breadcrumb" => true,
        ]);
    }

public function update($id)
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    $data = [
        'reviewer_name'     => $_POST['reviewer_name'] ?? null,
        'reviewer_role'     => $_POST['reviewer_role'] ?? null,
        'rating'            => $_POST['rating'] ?? null,
        'review_text'       => $_POST['review_text'] ?? null,
        'review_date'       => $_POST['review_date'] ?? date('Y-m-d'),
        'status'            => $_POST['status'] ?? 'active',
    ];

    $reviewer_photo = 'reviewer_image';       // file input for avatar
    $review_video_file = 'review_video_url'; 

    // ⚠ Correct order: $data first, $id second
    if ($this->reviewModel->update($data, $id, $reviewer_photo, $review_video_file)) {
        setSuccess("Review updated successfully!");
        header("Location: {$GLOBALS['base_url']}/review-lists");
        exit;
    }

    setError("Review update failed");
    header("Location: {$GLOBALS['base_url']}/edit-review/$id");
    exit;
}





       public function delete()
    {
        $id = $_POST['id'] ?? 0;

        if($this->reviewModel->delete($id)){
            setSuccess("Review Deleted Temporaly");
            header("Location:{$GLOBALS['base_url']}/review-lists");
        }else{
             setError( "Failed To  Deleted Temporaly Review");
            header("Location:{$GLOBALS['base_url']}/review-lists");
        };
    }

}
