<?php
namespace ai;

use IntentService;
use EmployeeService;
use ProjectService;
use SalesService;
use AccountService;
use ChatFormatter;

class AiBrain {

    public static function handle($question) {

        $intent = IntentService::detectIntent($question);

        switch ($intent) {

            case 'employee':
                $data = EmployeeService::getTotalEmployees(db());
                break;
        

            case 'project':
                $data = ProjectService::getAllProjects(db());
                break;

            case 'sales':
                $data = SalesService::getOverview(db());
                break;

            case 'cash':
                $data = AccountService::getCashFlow(db());
                break;

            default:
                //  fallback AI (later OpenAI)
                return "আমি প্রশ্নটা বুঝতে চেষ্টা করছি, একটু পরিষ্কার করে বলবেন?";
        }

        return ChatFormatter::format($question, $intent, $data);
    }
}


