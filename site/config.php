<?php

$config = [
    "db" => [
        "path" => __DIR__ . "/db/db.sqlite"
    ]
];

// Create db directory if it doesn't exist
if (!file_exists(__DIR__ . '/db')) {
    mkdir(__DIR__ . '/db', 0777, true);
}

// Create database file if it doesn't exist
if (!file_exists($config["db"]["path"])) {
    touch($config["db"]["path"]);
}   