<?php
    header('Content-type: text/json; charset=utf-8');
    require '../include/config.inc.php';
	
    $conn_string = sprintf('mysql:host=%s;port=%s;charset=utf8;dbname=%s', 
            MYSQL_SERVER, MYSQL_PORT, MYSQL_DATABASE);
    $db_connection = new PDO($conn_string, MYSQL_USERNAME, MYSQL_PASSWORD,    
    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
    
    $stmt = $db_connection->prepare("SELECT `verb` FROM `verbs` WHERE `verb` LIKE ?");
    if (isset($_REQUEST["q"]))
        $stmt->execute(array($_REQUEST["q"] . '%'));
    else
        $stmt->execute(array('%'));

    echo "[";      
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC))
    {
        echo "\"" . addslashes($row["verb"]) . "\"";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            print ", \"".$row["verb"] . "\"";
            
            ob_flush();
            flush();
        } 
    }
    echo "]";
?>
