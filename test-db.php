<?php
try {
    $pdo = new PDO("mysql:host=localhost;port=3306;dbname=smart_library_hub", "root", "");
    echo "Connection successful!\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
