<?php

class Controller
{
    public function view($view, $data = [], $useMaster = true)
    {
        $data['base_url'] = $GLOBALS['base_url'];
        extract($data);

        $root = dirname(__DIR__, 2);

        // Load child view — captures HTML output AND any $page_script set inside
        ob_start();
        require $root . "/app/views/" . $view . ".php";
        $content = ob_get_clean();

        // If the view set a $page_script variable, pass it to master layout
        if (isset($page_script)) {
            $data['page_script'] = $page_script;
        }

        if ($useMaster) {
            // Load master layout
            extract($data);
            require $root . "/app/views/layouts/master.php";
        } else {
            // Just render view standalone
            echo $content;
        }
    }
}
