<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = App\Models\Setting::get('ai.deepseek_api_key', config('services.deepseek.api_key'));
$response = Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $key,
    'Content-Type'  => 'application/json'
])->post('https://api.deepseek.com/chat/completions', [
    'model' => 'deepseek-v4-pro',
    'messages' => [['role' => 'user', 'content' => 'Who is the exact manufacturer of Accufine 30Mg Capsules in India? Provide just the company name.']]
]);

echo $response->body();
