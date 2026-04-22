<?php

function getEncryptionKey() {
    return base64_decode($_ENV['ENCRYPTION_KEY']);
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