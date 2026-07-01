<?php
// session_set_cookie_params([
//     'lifetime' => 60 * 180,
//     'path' => '/',
//     'domain' => 'localhost',
//     'secure' => false,       
//     'httponly' => true,
//     'samesite' => 'Lax'      
// ]);

session_set_cookie_params([
    'lifetime' => 60 * 180,
    'path' => '/',
    'domain' => 'easymarket2-727523185751.africa-south1.run.app',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);


// Start/loading the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


