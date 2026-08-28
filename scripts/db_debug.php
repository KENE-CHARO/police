<?php
require __DIR__ . '/../vendor/autoload.php';
use Illuminate\Database\Capsule\Manager as Capsule;

$env = parse_ini_file(__DIR__ . '/../.env') ?: [];
$db = __DIR__ . '/../database/database.sqlite';

if (! file_exists($db)) {
    echo "No sqlite DB found at $db\n";
    exit(1);
}

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => $db,
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "Plaintes:\n";
$plaintes = Capsule::table('plaintes')->get();
foreach ($plaintes as $p) {
    echo "- id={$p->id} title={$p->titre}\n";
}

echo "Attachments:\n";
$atts = Capsule::table('attachments')->get();
foreach ($atts as $a) {
    echo "- id={$a->id} filename={$a->filename} path={$a->path} attachable_type={$a->attachable_type} attachable_id={$a->attachable_id}\n";
}

return 0;
