<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = App\Models\AIProductQueue::orderBy('id', 'desc')->take(3)->get();
foreach($items as $i) {
    echo "ID: {$i->id} | Name: {$i->product_name} | Status: {$i->status} | Path: '{$i->image_path}' | Error: {$i->error_message}\n";
}
