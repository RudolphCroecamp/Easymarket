<?php
session_set_cookie_params([
    'lifetime' => 60 * 180,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => false,       
    'httponly' => true,
    'samesite' => 'Lax'      
]);

session_start();