<?php
class ProjectService {

    public static function getAllProjects($db) {
      $countResult = $db->query("
            SELECT COUNT(*) AS total 
            FROM projects 
            WHERE deleted_at IS NULL
        ");
        $countRow = $countResult->fetch_assoc();
        $total = $countRow['total'] ?? 0;

        // Project names
        $listResult = $db->query("
            SELECT name 
            FROM projects 
            WHERE deleted_at IS NULL
        ");

        $projects = [];
        while ($row = $listResult->fetch_assoc()) {
            $projects[] = $row['name'];
        }

        return [
            'total' => $total,
            'projects' => $projects
        ];
    }
}


//  $countResult = $db->query("
//             SELECT COUNT(*) AS total 
//             FROM projects 
//             WHERE deleted_at IS NULL
//         ");
//         $countRow = $countResult->fetch_assoc();
//         $total = $countRow['total'] ?? 0;

//         // Project names
//         $listResult = $db->query("
//             SELECT name 
//             FROM projects 
//             WHERE deleted_at IS NULL
//         ");

//         $projects = [];
//         while ($row = $listResult->fetch_assoc()) {
//             $projects[] = $row['name'];
//         }

//         return [
//             'total' => $total,
//             'projects' => $projects
//         ];

