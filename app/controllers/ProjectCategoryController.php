<?php

class ProjectCategoryController extends Controller
{
    protected $db;
    protected $categoryModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->categoryModel = new ProjectCategory($this->db);
    }

    /* ══ LIST ═══════════════════════════════════════════════ */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $categories = $this->categoryModel->getAll();

        $this->view('sales-developments/project-categories/project-category-lists', [
            'page_title'    => 'Project Categories',
            'current_route' => 'project-category-lists',
            'categories'    => $categories,
        ]);
    }

    /* ══ CREATE FORM ════════════════════════════════════════ */
    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        require_once __DIR__ . '/../models/Location.php';
        $locations = (new Location($this->db))->getAll();

        $this->view('sales-developments/project-categories/create-project-category', [
            'page_title'    => 'Add Project Category',
            'current_route' => 'create-project-category',
            'locations'     => $locations
        ]);
    }

    /* ══ STORE ══════════════════════════════════════════════ */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $name       = trim($_POST['name'] ?? '');
        $mainCat    = trim($_POST['main_category'] ?? '');
        $locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
        $incentive  = (float)($_POST['incentive_percentage'] ?? 0);
        $createdBy  = $_SESSION['user']['id'] ?? null;

        if (empty($name)) {
            setError('Category name is required.');
            header("Location: {$GLOBALS['base_url']}/create-project-category");
            exit;
        }

        $ok = $this->categoryModel->create([
            'name'                 => $name,
            'main_category'        => $mainCat ?: null,
            'location_id'          => $locationId,
            'incentive_percentage' => $incentive,
            'created_by'           => $createdBy,
        ]);

        if ($ok) {
            setSuccess('Project category created successfully!');
        } else {
            setError('Failed to create category. Please try again.');
        }

        header("Location: {$GLOBALS['base_url']}/project-category-lists");
        exit;
    }

    /* ══ EDIT FORM ══════════════════════════════════════════ */
    public function edit($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $category = $this->categoryModel->getById((int)$id);
        if (!$category) {
            setError('Category not found.');
            header("Location: {$GLOBALS['base_url']}/project-category-lists");
            exit;
        }

        require_once __DIR__ . '/../models/Location.php';
        $locations = (new Location($this->db))->getAll();

        $this->view('sales-developments/project-categories/edit-project-category', [
            'page_title'    => 'Edit Project Category',
            'current_route' => 'project-category-lists',
            'category'      => $category,
            'locations'     => $locations
        ]);
    }

    /* ══ UPDATE ════════════════════════════════════════════ */
    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $name      = trim($_POST['name'] ?? '');
        $mainCat   = trim($_POST['main_category'] ?? '');
        $locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
        $incentive = (float)($_POST['incentive_percentage'] ?? 0);

        if (empty($name)) {
            setError('Category name is required.');
            header("Location: {$GLOBALS['base_url']}/edit-project-category/{$id}");
            exit;
        }

        $ok = $this->categoryModel->update((int)$id, [
            'name'                 => $name,
            'main_category'        => $mainCat ?: null,
            'location_id'          => $locationId,
            'incentive_percentage' => $incentive,
        ]);

        // Also update all projects using this category to reflect new incentive %
        if ($ok) {
            $syncStmt = $this->db->prepare(
                "UPDATE projects SET incentive_percentage = ? WHERE category_id = ?"
            );
            if ($syncStmt) {
                $syncStmt->bind_param('di', $incentive, $id);
                $syncStmt->execute();
                $syncStmt->close();
            }

            setSuccess('Project category updated successfully!');
        } else {
            setError('Failed to update category.');
        }

        header("Location: {$GLOBALS['base_url']}/project-category-lists");
        exit;
    }

    /* ══ DELETE ════════════════════════════════════════════ */
    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setError('Invalid category.');
            header("Location: {$GLOBALS['base_url']}/project-category-lists");
            exit;
        }

        if ($this->categoryModel->delete($id)) {
            setSuccess('Category deleted successfully.');
        } else {
            setError('Failed to delete category.');
        }

        header("Location: {$GLOBALS['base_url']}/project-category-lists");
        exit;
    }
}
