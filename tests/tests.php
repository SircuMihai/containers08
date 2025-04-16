<?php

require_once __DIR__ . '/testframework.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modules/database.php';
require_once __DIR__ . '/../modules/page.php';

$testFramework = new TestFramework();

// Test Database class
function testDbConnection() {
    global $config;
    try {
        $db = new Database($config["db"]["path"]);
        return assertExpression($db instanceof Database, "Database connection established", "Failed to connect to database");
    } catch (Exception $e) {
        return assertExpression(false, "", "Database connection failed: " . $e->getMessage());
    }
}

function testDbExecute() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $result = $db->Execute("CREATE TABLE IF NOT EXISTS test_execute (id INTEGER PRIMARY KEY, name TEXT)");
    return assertExpression($result !== false, "Execute method works", "Execute method failed");
}

function testDbFetch() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $db->Execute("INSERT INTO test_execute (name) VALUES ('test')");
    $result = $db->Fetch("SELECT * FROM test_execute WHERE name = 'test'");
    return assertExpression(!empty($result) && $result[0]['name'] === 'test', "Fetch method works", "Fetch method failed");
}

function testDbCreate() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $id = $db->Create("test_execute", ["name" => "create_test"]);
    return assertExpression($id > 0, "Create method works", "Create method failed");
}

function testDbRead() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $id = $db->Create("test_execute", ["name" => "read_test"]);
    $result = $db->Read("test_execute", $id);
    return assertExpression($result['name'] === 'read_test', "Read method works", "Read method failed");
}

function testDbUpdate() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $id = $db->Create("test_execute", ["name" => "before_update"]);
    $result = $db->Update("test_execute", $id, ["name" => "after_update"]);
    $updated = $db->Read("test_execute", $id);
    return assertExpression($result && $updated['name'] === 'after_update', "Update method works", "Update method failed");
}

function testDbDelete() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $id = $db->Create("test_execute", ["name" => "to_delete"]);
    $result = $db->Delete("test_execute", $id);
    $deleted = $db->Read("test_execute", $id);
    return assertExpression($result && empty($deleted), "Delete method works", "Delete method failed");
}

function testDbCount() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $initialCount = $db->Count("test_execute");
    $db->Create("test_execute", ["name" => "count_test"]);
    $newCount = $db->Count("test_execute");
    return assertExpression($newCount == $initialCount + 1, "Count method works", "Count method failed");
}

// Test Page class
function testPageConstructor() {
    $templatePath = __DIR__ . '/../templates/index.tpl';
    $page = new Page($templatePath);
    return assertExpression($page instanceof Page, "Page constructor works", "Page constructor failed");
}

function testPageRender() {
    $templatePath = __DIR__ . '/../templates/index.tpl';
    $page = new Page($templatePath);
    $data = [
        'title' => 'Test Title',
        'content' => 'Test Content'
    ];
    $output = $page->Render($data);
    
    $titleCheck = strpos($output, 'Test Title') !== false;
    $contentCheck = strpos($output, 'Test Content') !== false;
    $templateCheck = strpos($output, '<html') !== false;
    
    return assertExpression($titleCheck && $contentCheck && $templateCheck, 
                          "Render method works", 
                          "Render method failed");
}

// Add Database tests
$testFramework->add('Database connection', 'testDbConnection');
$testFramework->add('Database Execute', 'testDbExecute');
$testFramework->add('Database Fetch', 'testDbFetch');
$testFramework->add('Database Create', 'testDbCreate');
$testFramework->add('Database Read', 'testDbRead');
$testFramework->add('Database Update', 'testDbUpdate');
$testFramework->add('Database Delete', 'testDbDelete');
$testFramework->add('Database Count', 'testDbCount');

// Add Page tests
$testFramework->add('Page constructor', 'testPageConstructor');
$testFramework->add('Page Render', 'testPageRender');

// Run tests
$testFramework->run();

// Clean up test table
$db = new Database($config["db"]["path"]);
$db->Execute("DROP TABLE IF EXISTS test_execute");

// Output results
echo "Test results: " . $testFramework->getResult() . " tests passed\n";
exit($testFramework->getSuccessCount() === $testFramework->getTotalTests() ? 0 : 1);