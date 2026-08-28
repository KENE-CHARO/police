<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/autoload.php';
// Use simple SQLite read via PDO to avoid bootstrapping full app
$db = __DIR__ . '/../database/database.sqlite';
if (! file_exists($db)) {
    echo "no db" . PHP_EOL; exit(1);
}
$pdo = new PDO('sqlite:' . $db);
$stmt = $pdo->query('SELECT * FROM historiques');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "id={$r['id']} subject_type={$r['subject_type']} subject_id={$r['subject_id']} user_id={$r['user_id']} action={$r['action']} details={$r['details']}\n";
}

return 0;
