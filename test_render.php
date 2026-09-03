<?php
require 'app/config/config.php';
require 'app/core/Controller.php';
require 'app/core/Model.php';
require 'app/models/Project.php';

$db = new mysqli('127.0.0.1','root','','tphl_db');
$projectModel = new Project($db);
$projects = $projectModel->getAll();

ob_start();
?>
<?php if (!empty($projects)): ?>
    <?php foreach ($projects as $project): ?>
        <?php if (!empty($project['under_construction']) && $project['under_construction'] == 1): ?>
            <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
        <?php endif; ?>
    <?php endforeach; ?>
<?php else: ?>
    <option value="">No projects available (<?= count($projects ?? []) ?> found)</option>
<?php endif; ?>
<?php
$output = ob_get_clean();
echo "Output length: " . strlen(trim($output)) . "\n";
echo "Output:\n" . trim($output);
