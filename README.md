# Numele lucrării de laborator
Integrare continuă cu Github Actions

# Scopul lucrării
În cadrul acestei lucrări studenții vor învăța să configureze integrarea continuă cu ajutorul Github Actions.

# Sarcina
Crearea unei aplicații Web, scrierea testelor pentru aceasta și configurarea integrării continue cu ajutorul Github Actions pe baza containerelor.

# Efectuarea
## Directorul containers08
- Sa creat directorul containers08 si in el sa creat directorul site.

## Structura la situl web
- in directorul site se vor crea directoarele si fisierele din urmatoarea imagine:
![alt img](./image/Screenshot%202025-04-20%20182906.png)

### Fisierul database.php
- Adaugam in fisier urmatorul cod:
```
<?php

class Database {
    private $db;

    public function __construct($path) {
        $this->db = new SQLite3($path);
    }

    public function Execute($sql) {
        return $this->db->exec($sql);
    }

    public function Fetch($sql) {
        $result = $this->db->query($sql);
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function Create($table, $data) {
        $columns = implode(', ', array_keys($data));
        $values = "'" . implode("', '", array_values($data)) . "'";
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";
        $this->Execute($sql);
        return $this->db->lastInsertRowID();
    }

    public function Read($table, $id) {
        $sql = "SELECT * FROM {$table} WHERE id = {$id}";
        $result = $this->Fetch($sql);
        return $result[0] ?? null;
    }

    public function Update($table, $id, $data) {
        $updates = [];
        foreach ($data as $key => $value) {
            $updates[] = "{$key} = '{$value}'";
        }
        $updates = implode(', ', $updates);
        $sql = "UPDATE {$table} SET {$updates} WHERE id = {$id}";
        return $this->Execute($sql);
    }

    public function Delete($table, $id) {
        $sql = "DELETE FROM {$table} WHERE id = {$id}";
        return $this->Execute($sql);
    }

    public function Count($table) {
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        $result = $this->Fetch($sql);
        return $result[0]['count'];
    }
}
```

### Fisierul page.php
- Adaugam in fisier urmatorul cod:
```
<?php

class Page {
    private $template;

    public function __construct($template) {
        $this->template = file_get_contents($template);
    }

    public function Render($data) {
        $content = $this->template;
        foreach ($data as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }
        return $content;
    }
}
```

### Fisierul index.tpl
- Adaugam in fisier urmatorul cod:
```
<!DOCTYPE html>
<html>
<head>
    <title>{{title}}</title>
    <link rel="stylesheet" href="/styles/style.css">
</head>
<body>
    <h1>{{title}}</h1>
    <div class="content">{{content}}</div>
</body>
</html>
```

### Fisierul style.css
- Adaugam in fisier urmatorul cod:
```
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

h1 {
    color: #333;
}

.content {
    margin-top: 20px;
    line-height: 1.6;
}
```

### Fisierul index.php
- Adaugam in fisier urmatorul cod:
```
<?php

require_once __DIR__ . '/modules/database.php';
require_once __DIR__ . '/modules/page.php';

require_once __DIR__ . '/config.php';

$db = new Database($config["db"]["path"]);

$page = new Page("/var/www/html/site/templates/index.tpl");

// bad idea, not recommended
$pageId = $_GET['page'] ?? 1;

$data = $db->Read("page", $pageId);

echo $page->Render($data);
```

### Fisierul config.php
- Adaugam in fisier urmatorul cod:
```
<?php
$config = [
    "db" => [
        "path" => "/var/www/db/db.sqlite"
    ]
];
```

## Pregatirea bazei de date
- In directorul site cream directorul sql cu fisierul schema.sql

### Fisierul schema.sql
```
CREATE TABLE page (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT
);

INSERT INTO page (title, content) VALUES ('Page 1', 'Content 1');
INSERT INTO page (title, content) VALUES ('Page 2', 'Content 2');
INSERT INTO page (title, content) VALUES ('Page 3', 'Content 3');
```

## Crearea testelor
- In directorul coontainers08 cream directorul tests cu fisierele testframework.php si tests.php

### Fisierul testframework.php
```
<?php

function message($type, $message) {
    $time = date('Y-m-d H:i:s');
    echo "{$time} [{$type}] {$message}" . PHP_EOL;
}

function info($message) {
    message('INFO', $message);
}

function error($message) {
    message('ERROR', $message);
}

function assertExpression($expression, $pass = 'Pass', $fail = 'Fail'): bool {
    if ($expression) {
        info($pass);
        return true;
    }
    error($fail);
    return false;
}

class TestFramework {
    private $tests = [];
    private $success = 0;

    public function add($name, $test) {
        $this->tests[$name] = $test;
    }

    public function run() {
        foreach ($this->tests as $name => $test) {
            info("Running test {$name}");
            if ($test()) {
                $this->success++;
            }
            info("End test {$name}");
        }
    }

    public function getResult() {
        return "{$this->success} / " . count($this->tests);
    }
}
```

### Fisierul tests.php
```
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
    $page = new Page("/var/www/html/site/templates/index.tpl");
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
```

## Cream in directorul radacina fisierul Dockerfile
- Continutul la fisier
```
FROM php:7.4-fpm AS base

RUN apt-get update && \
    apt-get install -y sqlite3 libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite

VOLUME ["/var/www/db"]

COPY site/sql/schema.sql /var/www/db/schema.sql

RUN echo "prepare database" && \
    cat /var/www/db/schema.sql | sqlite3 /var/www/db/db.sqlite && \
    chmod 777 /var/www/db/db.sqlite && \
    rm -rf /var/www/db/schema.sql && \
    echo "database is ready"

COPY site /var/www/html/site
```

## Configurarea la Git Actions
- In directorul containers08 cream directul .github cu directorul workflows in el. In directorul workflows cream fisierul main.yml
### Fisierul main.yml
```
name: CI

on:
  push:
    branches:
      - main

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4
      - name: Build the Docker image
        run: docker build -t containers08 .
      - name: Create `container`
        run: docker create --name container --volume database:/var/www/db containers08
      - name: Copy tests to the container
        run: docker cp ./tests container:/var/www/html/tests
      - name: Up the container
        run: docker start container
      - name: Run tests
        run: docker exec container php /var/www/html/tests/tests.php
      - name: Stop the container
        run: docker stop container
      - name: Remove the container
        run: docker rm container
```

## Verificare 
- Salvam toate modificarile si efectuam commit si sinc pentru a trimite modificarile pe repositoriul de pe GitHub.
- Intram in GitHub si accesam repositoriul containers08, intram in secciunea Actions aici vom vedem procesul de testare.
![alt img](./image/Screenshot%202025-04-20%20192150.png)
- 