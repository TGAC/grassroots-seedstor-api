<?php

//Database connection Module
require_once './conf/DBconnect.php';

if (!$dbcnx) {
    echo "error connecting";
}



if (mysqli_connect_errno())
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
global $dbcnx;

if(isset($_GET['storecode'])) {
    $storecode = filter_input(INPUT_GET, 'storecode');
}

$query = "SELECT * FROM storeref left join plant on storeref.idPlant=plant.idPlant WHERE storeref.StoreCode LIKE '$storecode'";


if ($result = $dbcnx->query($query)) {

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

$dbcnx->close();
?>