<?php

function getEncryptionKey() {
    // import env variables
    try {
        //local env variabes
        require_once __DIR__ . '/../../vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $key = base64_decode($_ENV['ENCRYPTION_KEY']);
        
    } catch (\Throwable $th) {
        //get from hosting server environment
        $key = base64_decode(getenv('ENCRYPTION_KEY'));
    }
    

    if (!$key || strlen($key) !== 32) {
        throw new Exception("Invalid encryption key (must be 32 bytes)");
    }

    return $key;
}

function encryptMessage($plaintext) {
    $key = getEncryptionKey();
    $cipher = "aes-256-gcm";

    $iv = random_bytes(12);
    $tag = '';

    $encrypted = openssl_encrypt(
        $plaintext,
        $cipher,
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    return base64_encode($iv . $tag . $encrypted);
}

function decryptMessage($encryptedData) {
    $key = getEncryptionKey();
    $cipher = "aes-256-gcm";

    $data = base64_decode($encryptedData);

    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $encrypted = substr($data, 28);

    return openssl_decrypt(
        $encrypted,
        $cipher,
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
}