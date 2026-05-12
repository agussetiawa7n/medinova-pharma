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
    'model' => 'openai/gpt-5-image-mini', // What happens here?
    'messages' => [['role' => 'user', 'content' => 'Draw a box.']]
]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
