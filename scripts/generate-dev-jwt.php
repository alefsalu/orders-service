<?php

declare(strict_types=1);

$keyDir = __DIR__ . '/../keys';
$privateKeyPath = $keyDir . '/private.pem';
$publicKeyPath = $keyDir . '/public.pem';

if (!file_exists($privateKeyPath)) {
    if (!is_dir($keyDir)) {
        mkdir($keyDir, 0700, true);
    }
    // Generate RSA 2048 key pair for dev/testing
    $config = [
        'digest_alg' => 'sha256',
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];
    $res = openssl_pkey_new($config);
    if ($res === false) {
        fwrite(STDERR, "Failed to generate private key\n");
        exit(1);
    }
    openssl_pkey_export($res, $privateKey);
    file_put_contents($privateKeyPath, $privateKey);

    $pubKeyDetails = openssl_pkey_get_details($res);
    file_put_contents($publicKeyPath, $pubKeyDetails['key']);
    fwrite(STDERR, "Generated dev RSA keypair in keys/\n");
}

$privateKey = file_get_contents($privateKeyPath);
if ($privateKey === false) {
    fwrite(STDERR, "Could not read private key\n");
    exit(1);
}

$sub = $argv[1] ?? '00000000-0000-0000-0000-000000000001';
$email = $argv[2] ?? 'user@example.com';
$roles = isset($argv[3]) ? explode(',', $argv[3]) : ['ROLE_USER'];

$now = time();
$payload = [
    'iss' => 'wallet-api',
    'aud' => 'portfolio',
    'sub' => $sub,
    'email' => $email,
    'roles' => $roles,
    'iat' => $now,
    'exp' => $now + 900,
    'jti' => bin2hex(random_bytes(16)),
];

$header = [
    'alg' => 'RS256',
    'typ' => 'JWT',
];

function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$headerEncoded = base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
$payloadEncoded = base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
$dataToSign = $headerEncoded . '.' . $payloadEncoded;

$signature = '';
$pkey = openssl_pkey_get_private($privateKey);
if ($pkey === false) {
    fwrite(STDERR, "Invalid private key\n");
    exit(1);
}

openssl_sign($dataToSign, $signature, $pkey, OPENSSL_ALGO_SHA256);
$signatureEncoded = base64UrlEncode($signature);

$jwt = $dataToSign . '.' . $signatureEncoded;
echo $jwt . PHP_EOL;
