<?php

require_once __DIR__ . '/modules/database.php';
require_once __DIR__ . '/modules/page.php';
require_once __DIR__ . '/config.php';

$db = new Database($config["db"]["path"]);
$page = new Page(__DIR__ . '/templates/index.tpl');

// Get page ID from query parameter with default value
$pageId = $_GET['page'] ?? 1;

$data = $db->Read("page", $pageId);

if (!$data) {
    $data = [
        'title' => 'Page Not Found',
        'content' => 'The requested page could not be found.'
    ];
}

echo $page->Render($data);