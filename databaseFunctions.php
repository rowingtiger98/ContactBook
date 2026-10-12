<?php
/*-------------------------------------------------------------*/
/*                                                             */
/*  DATABASE FUNCTIONS                                         */
/*                                                             */
/*  Single source for DB connection helpers. Credentials come  */
/*  from config.php (git-ignored), never from this file.       */
/*                                                             */
/*-------------------------------------------------------------*/
require_once __DIR__ . '/config.php';

/* *********************************************************** */
/* Function:    Create_SQL_PHP_DataObject                      */
/* Description: Creates a PDO connection. Use prepared          */
/*              statements with it to prevent SQL injection.   */
/* Inputs:      $database - the database to connect to         */
/*              (defaults to DB_NAME from config.php).         */
/* Returns:     $PDO - The PHP Data Object.                    */
/* *********************************************************** */
function Create_SQL_PHP_DataObject(string $database = DB_NAME): PDO {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . $database . ';charset=utf8mb4';

    try {
        $PDO = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Log the details server-side; don't leak them to the browser.
        error_log('ContactBook DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('A database error occurred. Please try again later.');
    }

    return $PDO;
}
