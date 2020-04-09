<?php
$conn_string = sprintf(
    'mysql:host=%s;port=%s;charset=utf8;dbname=%s',
    getenv("MYSQL_SERVER"),
    getenv("MYSQL_PORT"),
    getenv("MYSQL_DATABASE")
);
$db_connection = new PDO(
    $conn_string,
    getenv("MYSQL_USERNAME"),
    getenv("MYSQL_PASSWORD"),
    [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
);
?>
