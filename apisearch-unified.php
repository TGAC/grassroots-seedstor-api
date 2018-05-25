<?php


require_once './conf/DBconnect.php';

if(isset($_GET['query'])) {
    $query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING);
}

$id = (int)$query;

$query1 = "SELECT * FROM plant left join storeref on plant.idPlant=storeref.idPlant WHERE lower(plant.AccessionName) LIKE lower('%$query%') OR plant.idPlant=$id";

$query2 = "SELECT * FROM storeref left join plant on storeref.idPlant=plant.idPlant WHERE lower(storeref.StoreCode) LIKE lower('%$query%')";

$rows1 = array();
$rows2 = array();

if ($result1 = $dbcnx->query($query1)) {
    while($r1 = mysqli_fetch_assoc($result1)) {
        $rows1[] = $r1;
    }
    $result1->close();
}
if ($result2 = $dbcnx->query($query2)) {
    while($r2 = mysqli_fetch_assoc($result2)) {
        $rows2[] = $r2;
    }
    $result2->close();
}
$rows = $rows1 + $rows2;
print json_encode($rows);

$dbcnx->close();
?>