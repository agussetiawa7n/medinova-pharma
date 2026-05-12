<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = App\Models\Setting::get('ai.openrouter_api_key', config('services.openrouter.api_key'));
$response = Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $key,
    'Content-Type'  => 'application/json'
])->post('https://openrouter.ai/api/v1/chat/completions', [
    'model' => 'openai/gpt-4o',
    'plugins' => [['id' => 'web']],
    'messages' => [['role' => 'user', 'content' => 'Who is the exact manufacturer of Accufine 30Mg Capsules in India? Provide just the company name.']]
]);

echo $response->body();
