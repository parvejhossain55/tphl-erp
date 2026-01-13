<?php

namespace SiteManage;

use Controller;
use SiteSetting;

class SiteSettingController extends Controller
{
    protected $siteSettingModel;

    public function __construct($db)
    {
        $this->siteSettingModel = new SiteSetting($db);
    }

    // Load settings page with existing data
    public function index()
    {
        $settings = $this->siteSettingModel->getFirst(); // get first row or null
        $this->view("site-manage/settings/site-settings", [
            "page_title" => "Site Settings",
            "settings" => $settings // এখানে settings হিসেবে পাঠাচ্ছি
        ]);
    }


    // Handle both create and update
    public function storeOrUpdate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $data = $_POST;
            $data['created_by'] = $_SESSION['user']['id'] ?? null;

            $existing = $this->siteSettingModel->getFirst();

            try {
                if ($existing) {
                    // Update
                    $this->siteSettingModel->update(
                        $existing['id'],
                        $data,
                        'white_logo',
                        'dark_logo'
                    );
                    setSuccess("Settings updated successfully!");
                } else {
                    // Create
                    $this->siteSettingModel->create(
                        $data,
                        'white_logo',
                        'dark_logo'
                    );
                    setSuccess("Settings created successfully!");
                }

                header("Location: {$GLOBALS['base_url']}/site-settings");
                exit;
            } catch (\Throwable $th) {
                setError($th->getMessage());
                header("Location: {$GLOBALS['base_url']}/site-settings");
                exit;
            }
        }
    }
}
