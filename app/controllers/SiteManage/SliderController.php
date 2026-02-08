<?php

namespace SiteManage;

use Controller;
use Slider;

class SliderController extends Controller
{
    protected $db;
    protected $sliderModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->sliderModel = new Slider($db);
    }
    public function index()
    {
        $sliders =  $this->sliderModel->getAll();
        $this->view("site-manage/sliders/sliders", [
            "current_route"   => "slider-manage",
            "page_title"      => "slider List",
            "sliders"      => $sliders,
        ]);
    }

    // Handle form submission
    public function store()
    {
        $data = [
            'title'      => $_POST['title'] ?? '',
            'subtitle'      => $_POST['subtitle'] ?? '',

            'link'      => $_POST['link'] ?? '',
            'status' => isset($_POST['status']) ? 1 : 0,
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];
        $photoInputName = 'image';

        try {
            if ($this->sliderModel->create($data, $photoInputName)) {
                setSuccess('slider Created Successfully');
                header("Location: {$GLOBALS['base_url']}/slider-manage");
                exit;
            }
        } catch (\Throwable $th) {
            setError($th->getMessage());
            header("Location: {$GLOBALS['base_url']}/slider-manage");
            exit;
        }
    }
public function update()
{
    $id = $_POST['id'];
    $data = [
        'id'     => $_POST['id'] ?? 0,
        'title'   => $_POST['title'] ?? '',   // must match input name
        'link'   => $_POST['link'] ?? '',   // if slider has link field
        'status' => isset($_POST['status']) ? intval($_POST['status']) : 0,
    ];

    $photoInputName = 'image';

    try {
        if ($this->sliderModel->update($data, $id, $photoInputName)) {
            setSuccess('Slider updated successfully');
            header("Location: {$GLOBALS['base_url']}/slider-manage");
            exit;
        }
    } catch (\Throwable $th) {
        setError($th->getMessage());
        header("Location: {$GLOBALS['base_url']}/slider-manage");
        exit;
    }
}


   public function delete()
    {
        $id = $_POST['id'] ?? 0;

        if ($id && $this->sliderModel->delete($id)) {
            setSuccess("slider deleted successfully.");
            header("Location: {$GLOBALS['base_url']}/slider-manage");
            exit;
        } else {
          setError("Slider Deleted Failed");
        }
    }
}
