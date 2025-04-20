<?php

require_once __DIR__ . '/testframework.php';

require_once __DIR__ . '/../site/config.php';
require_once __DIR__ . '/../site/modules/database.php';
require_once __DIR__ . '/../site/modules/page.php';

$testFramework = new TestFramework();

// test 1: check database connection
function testDbConnection() {
    global $config;
    try {
        $db = new Database($config["db"]["path"]);
        return assertExpression($db instanceof Database, 'Database connected successfully', 'Failed to connect to database');
    } catch (Exception $e) {
        return assertExpression(false, '', 'Failed to connect to database: ' . $e->getMessage());
    }
}

// test 2: test count method
function testDbCount() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $count = $db->Count("page");
    return assertExpression($count == 3, 'Count method works correctly', 'Count method failed');
}

// test 3: test create method
function testDbCreate() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $id = $db->Create("page", ["title" => "Test Page", "content" => "Test Content"]);
    return assertExpression($id > 0, 'Create method works correctly', 'Create method failed');
}

// test 4: test read method
function testDbRead() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $data = $db->Read("page", 1);
    return assertExpression(!empty($data) && isset($data['title']), 'Read method works correctly', 'Read method failed');
}

// test 5: test update method
function testDbUpdate() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $result = $db->Update("page", 1, ["title" => "Updated Title"]);
    $data = $db->Read("page", 1);
    return assertExpression($result && $data['title'] == "Updated Title", 'Update method works correctly', 'Update method failed');
}

// test 6: test delete method
function testDbDelete() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $initialCount = $db->Count("page");
    $result = $db->Delete("page", 1);
    $newCount = $db->Count("page");
    return assertExpression($result && $newCount == $initialCount - 1, 'Delete method works correctly', 'Delete method failed');
}

// test 7: test page rendering
function testPageRender() {
    global $config;
    $page = new Page("var/www/html/site/templates/index.tpl");
    $rendered = $page->Render(["title" => "Test", "content" => "Test Content"]);
    return assertExpression(
        strpos($rendered, "Test") !== false && strpos($rendered, "Test Content") !== false,
        'Page render works correctly',
        'Page render failed'
    );
}

// add tests
$testFramework->add('Database connection', 'testDbConnection');
$testFramework->add('table count', 'testDbCount');
$testFramework->add('data create', 'testDbCreate');
$testFramework->add('data read', 'testDbRead');
$testFramework->add('data update', 'testDbUpdate');
$testFramework->add('data delete', 'testDbDelete');
$testFramework->add('page render', 'testPageRender');

// run tests
$testFramework->run();

echo "Test results: " . $testFramework->getResult() . PHP_EOL;