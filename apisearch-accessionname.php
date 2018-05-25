<?php


require_once './conf/DBconnect.php';

if (!$dbcnx) {
    echo "error connecting";
}



if (mysqli_connect_errno())
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}


if(isset($_GET['accessionname'])) {
    $accessionname = filter_input(INPUT_GET, 'accessionname', FILTER_SANITIZE_STRING);
}

global $dbcnx;
$query = "SELECT * FROM plant WHERE lower(AccessionName) LIKE lower('%$accessionname%')";

if ($result = $dbcnx->query($query)) {

    $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
    print json_encode($rows);


    $result->close();
}

$dbcnx->close();
?>