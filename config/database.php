<?php

$host = "sql311.yzz.me";
$dbname = "yzzme_42181776_machaoui";
$username = "yzzme_42181776";
$password = "5OuNoTmzH9A0";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

} catch(PDOException $e) {

    die("Database Connection Failed : " . $e->getMessage());

}
?>