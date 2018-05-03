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

$query = "SELECT * FROM storeref WHERE StoreCode LIKE '" . $_GET["storecode"] . "'";

if ($result = $connection->query($query)) {

    $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
    print json_encode($rows);


//    foreach($result as $row) {
//        $data[] = array(
//            'idPlant' => $result["idPlant"],
//            'StoreCode' => $result["StoreCode"]
//        );
//    }
//    echo json_encode($data);
    $result->close();
}

$connection->close();
?>