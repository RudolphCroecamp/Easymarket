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



// session_set_cookie_params([
//     'lifetime' => 60 * 180,
//     'path' => '/',
//     'domain' => 'easymarket-727523185751.europe-west1.run.app',
//     'secure' => true,
//     'httponly' => true,
//     'samesite' => 'None'
// ]);



// session_set_cookie_params([
//     'lifetime' => 60 * 180,
//     'path' => '/',
//     'domain' => 'localhost',
//     'secure' => false,       
//     'httponly' => true,
//     'samesite' => 'Lax'      
// ]);


