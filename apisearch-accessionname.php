<?php

//require_once './conf/Conf.php';
$host = "localhost";
$username="view";
$password="V13wdata";
$database="seedstor";
$connection = new mysqli($host, $username, $password, $database);

if (!$connection) {
    echo "error connecting";
}



if (mysqli_connect_errno())
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$query = "SELECT * FROM plant WHERE AccessionName LIKE '" . $_GET["accessionname"] . "'";

if ($result = $connection->query($query)) {

    $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
    print json_encode($rows);


    $result->close();
}

$connection->close();
?>