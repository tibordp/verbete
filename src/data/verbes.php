<?php
header('Content-type: text/json; charset=utf-8');

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

$stmt = $db_connection->prepare(
    "SELECT `verb` FROM `verbs` WHERE `verb` LIKE ?"
);
if (isset($_REQUEST["q"])) {
    $stmt->execute([$_REQUEST["q"] . '%']);
} else {
    $stmt->execute(['%']);
}

echo "[";
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "\"" . addslashes($row["verb"]) . "\"";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print ", \"" . $row["verb"] . "\"";

        ob_flush();
        flush();
    }
}
echo "]";
?>
