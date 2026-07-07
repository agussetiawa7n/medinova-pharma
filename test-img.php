<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = App\Models\Setting::get('ai.fal_api_key', config('services.fal.api_key'));
$response = Illuminate\Support\Facades\Http::connectTimeout(15)->timeout(120)->withHeaders([
    'Authorization' => 'Key ' . $key,
    'Content-Type'  => 'application/json'
])->post('https://fal.run/fal-ai/gpt-image-1-mini', [
    'prompt' => 'Draw a small white pharmaceutical box labeled Accufine.',
    'image_size' => '1024x1024',
    'quality' => 'high'
]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
