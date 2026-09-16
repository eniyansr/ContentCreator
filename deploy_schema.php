<?php
require_once __DIR__ . '/config/config.php';

echo "Deploying schema to Cloudflare D1...\n";

if (empty(CF_ACCOUNT_ID) || empty(CF_DB_ID) || empty(CF_API_TOKEN)) {
    die("Missing Cloudflare credentials in .env\n");
}

$schemaSql = file_get_contents(__DIR__ . '/database/schema.sqlite.sql');
if (!$schemaSql) {
    die("Could not read schema.sqlite.sql\n");
}

// Split the schema by semicolons to execute statement by statement
$statements = explode(';', $schemaSql);
$payloadArray = [];

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    if (str_starts_with($statement, '--')) continue;
    if ($statement === 'COMMIT') continue;
    if (str_starts_with($statement, 'SET')) continue;
    
    $payloadArray[] = [
        "sql" => $statement,
        "params" => []
    ];
}

$url = "https://api.cloudflare.com/client/v4/accounts/" . CF_ACCOUNT_ID . "/d1/database/" . CF_DB_ID . "/query";

// Send each statement individually
foreach ($payloadArray as $idx => $payloadObj) {
    echo "Sending statement " . ($idx + 1) . " of " . count($payloadArray) . "...\n";
    
    $payload = json_encode($payloadObj);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . CF_API_TOKEN,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        die("cURL Error: " . curl_error($ch) . "\n");
    }
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($httpCode !== 200 || empty($data['success'])) {
        echo "Error in statement " . ($idx + 1) . ":\n";
        print_r($data);
        die("Deployment failed.\n");
    }
}

echo "Schema deployed successfully!\n";
