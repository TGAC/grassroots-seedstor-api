<?php
session_start();
?>
<!DOCTYPE HTML>
<HTML>
<HEAD>
    <!--Meta Data -->
    <TITLE>SeedStor Search Interface</TITLE>
    <meta http-equiv="X-UA-Compatible" content="IE=9"/>
    <meta name="author" content="Dr Richard S.P. Horler">
    <meta name="company" content="Germplasm Resources Unit, John Innes Centre, UK">
    <meta name="Editor" content="Notepad++">
    <meta name="Style" content="Bootstrap CSS">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="SeedStorIcon.ico">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="./css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="./css/bootstrap-theme.css" type="text/css">
    <!-- NOT USED ON PUBLIC -->
    <!-- Load jquery for JavaScript functions -->
    <script type='text/javascript' src='./js/jquery-1.7.1.js'></script>
    <?php
    //Navigation Bar Module
    require_once './conf/NavBar.php';
    //Database connection Module
    require_once './conf/DBconnect.php';
    //Global Settings Module
    require_once './conf/Conf.php';
    $plantid = filter_input(INPUT_GET, 'plantid', FILTER_SANITIZE_STRING);
//    // Self URL referrer for form
    $selfURL = filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_SPECIAL_CHARS);
//    if (isset($_COOKIE["SeedStorCart"])) {
//        $CART = filter_input(INPUT_COOKIE, 'SeedStorCart', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
//        $NewCart = $plantid . "," . $CART;
//        $NewCart = $plantid . "," . $CART;
//    } else {
//        $NewCart = $plantid;
//    }
//    setcookie("SeedStorCart", $NewCart, time() + 3600);  /* expire in 1 hour */
//
//
//    $CART = filter_input(INPUT_COOKIE, 'SeedStorCart', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
//    If first character is , then remove it
//    if (substr($CART, 0, 1) === ',') {
//        $CART = substr($CART, 1);
//    }
//    //Check Lines accessions are unique
//    $LineArray = array();
//    $LineArray = explode(",", $CART);
//    //Create a unique ordered array list
//    sort($LineArray);
//    $UniqueLineArray = array();
//    $UniqueLineArray = array_unique($LineArray);
//    $TidyLines = implode(',', $UniqueLineArray);
//    echo json_encode($TidyLines);
    //Export tidied Shopping Cart
//    setcookie("SeedStorCart", $TidyLines, time() + 3600);  /* expire in 1 hour */
//    $Action = "EditLines";
    $TidyLines = $plantid;
    $Chargeable = "No";

    $Action = "Checkout";
//    Checkout($CART, $TidyLines, $Chargeable);


    if($_SERVER['REQUEST_METHOD'] == "POST")  {
        /// ---------------------------------------------------------------- EMPTY  ----------------------------------------------------------------
        if(isset($_POST['EMPTY'])) {
            setcookie("SeedStorCart", "Delete", 1);  /* expire at start of Epoch time */
            $Action="Empty";
        }
        /// ------------------------------------------------------------- END EMPTY  -------------------------------------------------------------

        /// ---------------------------------------------------------------- REMOVE  ----------------------------------------------------------------
        if(isset($_POST['REMOVE'])) {
            $idPlant = filter_input(INPUT_POST, 'idPlant', FILTER_SANITIZE_NUMBER_INT);
            $UniqueLineArray = array_diff($UniqueLineArray, array($idPlant));
            $TidyLines= implode(',', $UniqueLineArray);
            setcookie("SeedStorCart", $TidyLines, time()+3600);  /* expire in 1 hour */
            $Action="EditLines";
            echo "<script>window.location='$selfURL'</script>";
        }
        /// ------------------------------------------------------------- END REMOVE  -------------------------------------------------------------

        /////===================== DISPATCHEMAIL  ========================================
        if(isset($_POST['DISPATCHEMAIL'])) {
            $Chargeable = filter_input(INPUT_POST, 'Chargeable', FILTER_SANITIZE_STRING,  FILTER_FLAG_STRIP_LOW);
            $Name = filter_input(INPUT_POST, 'Name', FILTER_SANITIZE_STRING,  FILTER_FLAG_STRIP_LOW);
            $Address = filter_input(INPUT_POST, 'Address', FILTER_SANITIZE_STRING);
            $Email = filter_input(INPUT_POST, 'Email', FILTER_SANITIZE_EMAIL);
            $Comments = filter_input(INPUT_POST, 'Comments', FILTER_SANITIZE_STRING);
            $IntendedUse = filter_input(INPUT_POST, 'IntendedUse', FILTER_SANITIZE_STRING,  FILTER_FLAG_STRIP_LOW);
            $IntendedUseDesc = filter_input(INPUT_POST, 'IntendedUseDesc', FILTER_SANITIZE_STRING);

            // data validation
            $errcount=0;
            // check that essentials filled are completed
            if ($Name == '')  {
                $error = 'ERROR: A Name is required';
                $errcount=$errcount+1;
            }
            if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
                $error = 'ERROR: A valid email address is required';
                $errcount=$errcount+1;
            }
            if ($Address == '')  {
                $error = 'ERROR: An Address is required';
                $errcount=$errcount+1;
            }
            if ($IntendedUse == 'Pick')  {
                $error = 'ERROR: Please select a suitable category for the intended use';
                $errcount=$errcount+1;
            }
            if ($IntendedUseDesc == '')  {
                $error = 'ERROR: Please provide a descriptive text for the intended use of these seeds';
                $errcount=$errcount+1;
            }

            // If errors then display error message and reload form with data entered so far
            if($errcount>0) {
                $Action="Checking";
            }
            else {
                $Address =  nl2br ( $Address);   //Capture new lines in address

                $MyQuery2="	SELECT plant.idPlant, AccessionName, storeref.StoreCode, plant.SubCollection, `idCollection`
													FROM plant 
													JOIN storeref ON storeref.idPlant= plant.idPlant 
													WHERE plant.idPlant IN($TidyLines)";

                //Run MySQL Query on $MyQuery output to Result B
                $resultB = $dbcnx->query($MyQuery2) or trigger_error("<div class='alert alert-danger'><strong>Error with MySQL Query: </strong>$MyQuery2</br><strong>Error Message:</strong> ". mysqli_error($dbcnx), E_USER_ERROR). "</div>";
                $rowcount=mysqli_num_rows($resultB);

                $CollSourcesArray = array();
                $AccNamesArray = array();
                $LineCostsTotal = 0;

                while($row = $resultB->fetch_assoc()) {
                    $idPlant = $row['idPlant'];
                    $CollSourcesArray[] = $row['SubCollection'];
                    $AccessionName = $row['AccessionName'];
                    $StoreCode = $row['StoreCode'];
                    $idCollection = $row['idCollection'];

                    $AccNamesArray[] = "$AccessionName ($StoreCode)";

                    // Get CollectioncostRecovery Amount
                    $MyQuery3="	SELECT  idCollection, sum(`CostAmount`) as LineCost
															FROM  collectioncostrecovery 
															WHERE (idCollection = '$idCollection')
															AND `CostMethod` = 'Per Line' and `DefaultCost` = 'Yes'
															GROUP BY idCollection";

                    //Run MySQL Query on $MyQuery output to Result B
                    $resultC = $dbcnx->query($MyQuery3) or trigger_error("<div class='alert alert-danger'><strong>Error with MySQL Query: </strong>$MyQuery3</br><strong>Error Message:</strong> ". mysqli_error($dbcnx), E_USER_ERROR). "</div>";
                    while($row = $resultC->fetch_assoc()) {
                        $LineCost = $row['LineCost'];

                        $LineCostsTotal = $LineCostsTotal + $LineCost;
                    }
                }

                // Get CollectioncostRecovery per Job costs
                $MyQuery4="	SELECT  idCollection, sum(`CostAmount`) as LineCost
													FROM  collectioncostrecovery 
													WHERE (idCollection = '$idCollection')
													AND `CostMethod` = 'Per Job' and `DefaultCost` = 'Yes'
													GROUP BY idCollection";

                //Run MySQL Query on $MyQuery output to Result B
                $resultD = $dbcnx->query($MyQuery4) or trigger_error("<div class='alert alert-danger'><strong>Error with MySQL Query: </strong>$MyQuery4</br><strong>Error Message:</strong> ". mysqli_error($dbcnx), E_USER_ERROR). "</div>";
                while($row = $resultD->fetch_assoc()) {
                    $LineCost = $row['LineCost'];

                    $LineCostsTotal = $LineCostsTotal + $LineCost;
                }

                $AccNames= implode(', ', $AccNamesArray);
                $CollSourcesArray = array_unique($CollSourcesArray);
                $CollSources= implode(', ', $CollSourcesArray);

                //Basic Email Module
                require_once './conf/SMTPmail.php';

                // Message to GRU
                $message = "<b>Name:</b> $Name </br><b>Address: </b></br>$Address </br><b>Email:</b> $Email </br> <b>Intended Use: </b> $IntendedUse </br>    
						<b>Intended Use Description: </b> $IntendedUseDesc </br> <b>Comments:  </b></br> $Comments </br>
						<b>From Collections:  </b>$CollSources </br> <b>Number of Accessions:</b> $rowcount </br>  <b> Accessions:</b> $AccNames </br>  <b> Estimated Cost for Job:</b> &pound; $LineCostsTotal (In addition there may be other charges e.g. Phytosanitary certification)   </br> <b> Requested Lines (idPlant): </b></br> $TidyLines</br>";
                $subject = "SeedStor - New Seed Request";
                $rcpt='JIC.GeneticResources@jic.ac.uk';

                NbiSendEmailPlus($subject, $message, $rcpt);

                //Message to Client
                $message2 = "<b>Name:</b> $Name </br><b>Address: </b>$Address </br><b>Email:</b> $Email </br>  <b>Comments: </b> </br> $Comments </br> <b> Estimated Cost for Job:</b> &pound;$LineCostsTotal (In addition there may be other charges e.g. Phytosanitary certification)   </br><b>Number of Accessions:</b> $rowcount </br>  <b>Accessions: </b>$AccNames </br><p> Please bear with us following Mike Ambrose's recent retirement and other staff changes within the GRU we have not been able to process Seed Requests as quickly as we would hope. The new GRU manager joins us in January 2018 and we hope that the GRU will be back up to speed shortly thereafter.</p>";
                $subject2 = "SeedStor - Client Email copy of new request";
                $rcpt2=$Email;

                NbiSendEmailPlus($subject2, $message2, $rcpt2);
            }
        }

    }
    ?>
</HEAD>

<BODY role="document">

<div class="row">
    <div class="col-md-4">
        <!--SeedStor Logo -->
        <img src="SeedStor.png" alt="SeedStor Logo" class="img-rounded" width='100%'/>
        <p></p>
        <!--GRU Photo -->
        <?php include_once 'randomimage.php' ?>
        <!--GRU Logo -->
        <img src="GRUlogo.png" alt="GRU Logo" class="img-rounded" width='100%'/>
        <p></p>
    </div>
    <div class="col-md-8">
        <div class="jumbotron">
            <h1>Shopping Cart</h1>
            <p></p>
            <?php

            if ($Action == 'EditLines') {
                EditLines($CART, $TidyLines);
            } elseif ($Action == 'Empty') {
                echo "<p>You have just emptied the cart.</p>";
            } elseif ($Action == 'None') {
                echo "<p>The shopping cart is empty please use the search functions to add Lines.</p>";
            } elseif ($Action == 'Checkout') {
                Checkout($CART, $TidyLines, $Chargeable);
            } elseif ($Action == 'Checking') {
                echo "<div class='alert alert-danger'><strong>Submission Warning!</strong> The data did not meet the validation rules and has not been submitted. $error.</div>";
                Checkout($CART, $TidyLines, $Chargeable, $Name, $Address, $Email, $Comments, $IntendedUse, $IntendedUseDesc);
            }
            ?>
        </div> <!-- //End Jumbletron -->
    </div> <!-- //End col-md-8 -->
</div> <!-- //End row -->

<?php

Function EditLines($CART = '', $TidyLines = '')
{
///==================================================================
//Show the selected Lines for clients to edit
///==================================================================
    Global $dbcnx;
    Global $selfURL;
    Global $WPGSRequestLimit;

//If first character is , then remove it
    if (substr($TidyLines, 0, 1) === ',') {
        $TidyLines = substr($TidyLines, 1);
    }
    $MyQuery2 = "	SELECT plant.idPlant, AccessionName, TaxonCode, idCollection, SampStat, SubCollection, AccYear, storeref.StoreCode 
							FROM plant 
							JOIN storeref ON storeref.idPlant= plant.idPlant 
							WHERE plant.idPlant IN($TidyLines)";
//Run MySQL Query on $MyQuery output to Result B
    $resultB = $dbcnx->query($MyQuery2) or trigger_error("<div class='alert alert-danger'><strong>Error with MySQL Query: </strong>$MyQuery2</br><strong>Error Message:</strong> " . mysqli_error($dbcnx), E_USER_ERROR) . "</div>";

//Get the number of rows in the Final List - used for processing the data correctly when editing DB
    $requestlines = mysqli_num_rows($resultB);

    echo "<div class='panel panel-success'>";
    echo "<div class='panel-heading'>";
    echo "<h2> List of Seed Lines to Request </h2> ";
    echo "</div>";

    echo "<div class='panel-body'>";
    //  <!-- ================================================================================================== -->
    //	<!-- ========================================= DATA OUTPUT TABLE====================================== -->
    echo "<table id='querytable' class='table table-bordered table-success table-condensed'>";
    // <!-- Table headers -->
    echo "<tr class='success-headerrow'>";
    echo "<th width='25%'><h4>Collection</h4></th>";
    echo "<th width='10%'><h4>Taxon Code</h4></th>";
    echo "<th width='15%'><h4>GRU Store Code</h4></th>";
    echo "<th width='40%'><h4>Accession Name (Year)</h4></th>";
    echo "<th width='10%'><h4></h4></th>";
    echo "</tr>";
    $Chargeable = 'No';

    $idCollectionArray = array();
    while ($row = $resultB->fetch_assoc()) {
        $idPlant = $row['idPlant'];
        $SubCollection = $row['SubCollection'];
        $AccessionName = $row['AccessionName'];
        $StoreCode = $row['StoreCode'];
        $TaxonCode = $row['TaxonCode'];
        $AccYear = $row['AccYear'];
        $SampStat = $row['SampStat'];
        $idCollection = $row['idCollection'];
        $idCollectionArray[] = $row['idCollection'];

        $HoverText = "Accession Year: $AccYear &#10;SampStat: $SampStat";

        $CostCheckQuery = "SELECT CostMethod 
															FROM collectioncostrecovery 
															WHERE collectioncostrecovery.idCollection = '$idCollection' ";

        //Run MySQL Query on $MyQuery output to Result B
        $resultC = $dbcnx->query($CostCheckQuery) or trigger_error("<div class='alert alert-danger'><strong>Error with MySQL Query: </strong>$CostCheckQuery</br><strong>Error Message:</strong> " . mysqli_error($dbcnx), E_USER_ERROR) . "</div>";
        //Get the number of rows in the Final List - used for processing the data correctly when editing DB
        $CostsRecords = mysqli_num_rows($resultC);

        $tag1 = '';
        if ($CostsRecords != 0) {
            $tag1 = "<font size=3 title='This Collection is cost-recoverable'><button class='btn-xs btn-info'><span class='glyphicon glyphicon-credit-card'></span></button></font>";
            $Chargeable = 'Yes';
        }

        // Create Row data
        echo "<tr><td>$tag1 $SubCollection</td><td>$TaxonCode</td><td >$StoreCode (idPlant=$idPlant)</td><td>$AccessionName <span class='glyphicon glyphicon-info-sign'  title='$HoverText'></span></td>";
        $PaddedidPlant = str_pad($idPlant, 6, "0", STR_PAD_LEFT);
        echo " <td><form action='$selfURL' method='post' class='form-horizontal' enctype='multipart/form-data' role='form'><input name='idPlant' type='hidden' value='$PaddedidPlant'>";
        echo "<button type='submit' name='REMOVE' class='btn-xs btn-danger'><span class='glyphicon glyphicon-remove'></span> Remove </button></form> </td></tr>";
    }
    echo "</table>";

    //Check how many requested WPGS Lines, so a warning can be shown if too many included
    $counter = 0;
    foreach ($idCollectionArray as $idCollection) {
        if ($idCollection == 2) {
            $counter++;
        }
    }
    //If more than 50 then issue warning
    if ($counter > $WPGSRequestLimit) {
        echo "<div class='alert alert-danger'><font size=3> This job includes more than 50 accessions requested from the WPGS collection. </font></div>";
        echo "</br>";
    }

    if ($Chargeable == 'Yes') {
        echo "<div class='row'><div class='col-sm-offset-1 col-sm-11'><font size=3><button class='btn-xs btn-info'><span class='glyphicon glyphicon-credit-card'></span></button> This job includes accessions that are cost  recoverable</font></div></div>";
        echo "</br>";
    }

    echo "<form action='$selfURL' method='post' class='form-horizontal' enctype='multipart/form-data' role='form'>";
    echo "<input name='MyQuery2' type='hidden' value='$MyQuery2'>";
    echo "<input name='Chargeable' type='hidden' value='$Chargeable'>";
    if ($counter <= $WPGSRequestLimit) {
        echo "<button type='submit' name='CHECKOUT' class='btn btn-success'><span class='glyphicon glyphicon-gift'></span> Checkout </button> ";
    }
    echo "<button type='submit' name='EMPTY' class='btn btn-danger' onclick='return delete_confirm();'><span class='glyphicon glyphicon-trash'></span> Empty cart </button> ";
    echo "<button title='Download the data as filtered to your local machine' type='submit' name='SAVECSV' class='btn btn-success' role='button'><span class='glyphicon glyphicon-floppy-save'></span> Save to CSV</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
///==================================================================
//END Show the selected Lines for clients to edit
///==================================================================
}

Function Checkout($CART = '', $TidyLines = '', $Chargeable = 'No', $Name = '', $Address = '', $Email = '', $Comments = '', $IntendedUse = '', $IntendedUseDesc = '')
{
///==================================================================
// Checkout
///==================================================================
    Global $dbcnx;
    Global $selfURL;

//Retrieve Job RequestType data from coderequesttype table to array -------------------------------------------------
    $TypeQuery = "SELECT RequestTypeDesc, RequestType FROM coderequesttype ORDER BY RequestTypeDesc";
    $typeFilter = $dbcnx->query($TypeQuery) or trigger_error("<div class='alert alert-danger'><strong>Error with MySQL Query: </strong>$TypeQuery</br><strong>Error Message:</strong> " . mysqli_error($dbcnx), E_USER_ERROR) . "</div>";
    $TypeArray = array();
    while ($row = $typeFilter->fetch_assoc()) {
        $TypeArray[$row['RequestType']] = $row['RequestTypeDesc'];
    }
//End Retrieve Job RequestType data from coderequesttype table to array -------------------------------------------------

    echo "<div class='panel panel-success'>";
    echo "<div class='panel-heading'>";
    echo "Please complete the fields below so that we can fulfil your request";
    echo "</div>";

    echo "<div class='panel-body'>";

    echo "<form action='$selfURL' method='post' class='form-horizontal' enctype='multipart/form-data' role='form'>";
    echo "<div class='form-group'>";
    // Hidden Lines (for CurrentLinesList)
    echo "<input name='TidyLines' type='hidden' value='$TidyLines'>";
    echo "<input name='Chargeable' type='hidden' value='$Chargeable'>";

    echo "<label for='Lines' class='col-sm-3 control-label'>Requested Lines <span class='glyphicon glyphicon-ok-circle' title='Required'></span></label>";
    echo "<div class='col-md-4'><input disabled name='Lines' type='text'  value='$TidyLines' class='form-control'> </div>";
    echo "</div>"; // End form group

    if ($Chargeable == 'Yes') {
        echo "<div class='form-group'>";
        echo "<label for='CostRecovery' class='col-sm-3 control-label'>Cost Recovery </label>";
        echo "<div class='col-sm-9' id='CostRecovery'><font size ='3' style='color: blue;'> This request involves accessions that are Cost Recoverable.</font></div>";
        echo "</div>";
    }

    echo "<div class='form-group'>";
    echo "<label for='Name' class='col-sm-3 control-label'>Name <span class='glyphicon glyphicon-ok-circle' title='Required'></span></label>";
    echo "<div class='col-sm-5'><input  name='Name' type='text'  value='$Name' class='form-control'></div>";
    echo "</div>";
    echo "<div class='form-group'>";
    echo "<label for='Address' class='col-sm-3 control-label'>Full Postal Address <span class='glyphicon glyphicon-ok-circle' title='Required'></span></label>";
    echo "<div class='col-sm-5'><textarea id='Address' name='Address'  class='form-control' rows='4' title='Please include Institute, Department if applicable, as well your street address, City, Country and postcode / zip code'>$Address</textarea></div>";
    echo "</div>";
    echo "<div class='form-group'>";
    echo "<label for='IntendedUse' class='col-sm-3 control-label'>Intended Use Type <span class='glyphicon glyphicon-ok-circle' title='Required'></span></label>";
    echo "<div class='col-sm-5' >";
    echo "<select name='IntendedUse' id='IntendedUse' class='form-control'>";
    echo "<option value='Pick' style='color: blue;' > Please pick the closest matching category</option>";
    foreach ($TypeArray as $RequestType => $RequestTypeDesc) {
        $tag = '';
        //Unless it has been set prior
        if ($RequestTypeDesc == $IntendedUse) {
            $tag = "selected";
        }
        echo "<option value='$RequestTypeDesc' $tag> $RequestTypeDesc</option>";
    }
    echo "</select>";
    echo "</div>";
    echo "</div>";

    echo "<div class='form-group'>";
    echo "<label for='IntendedUseDesc' class='col-md-3 control-label'>Intended Use Description <span class='glyphicon glyphicon-ok-circle' title='Required'></span></label>";
    echo "<div class='col-sm-5'><textarea id='IntendedUseDesc' name='IntendedUseDesc'  class='form-control' rows='2'>$IntendedUseDesc</textarea></div>";
    echo "</div>";
    echo "<div class='form-group'>";
    echo "<label for='Email' class='col-sm-3 control-label'>Email <span class='glyphicon glyphicon-ok-circle' title='Required'></span></label>";
    echo "<div class='col-sm-5'><input  name='Email' type='text'  value='$Email' class='form-control'></div>";
    echo "</div>";
    echo "<div class='form-group'>";
    echo "<label for='Comments' class='col-sm-3 control-label'>Comments </label>";
    echo "<div class='col-sm-5'><textarea id='Comments' name='Comments'  class='form-control' rows='5'>$Comments</textarea></div>";
    echo "</div>";

    echo "<div class='form-group'>";
    echo "<div class='col-sm-offset-2 col-sm-5'><button type='submit' name='DISPATCHEMAIL' class='btn btn-success'><span class='glyphicon glyphicon-envelope'></span> Send Email Request to GRU</button></div>";
    echo "</div>";
    echo "</form>";

    echo "</div>";
    echo "</div>";
///==================================================================
//END  Checkout
///==================================================================
}

// Save to CSV
/// ---------------------------------------------------------------- SAVECSV  ----------------------------------------------------------------		
if (isset($_POST['SAVECSV'])) {
    $MyQuery2 = filter_input(INPUT_POST, 'MyQuery2', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
    $_SESSION['Query'] = $MyQuery2;
    echo "<script> window.location.href= 'search-exportcsv.php'; </script>";
}
/// ------------------------------------------------------------- END SAVECSV  -------------------------------------------------------------	
?>
</BODY>
<!-- =====================BODY END SECTION FOR PAGE SPECIFIC SCRIPTS========================= -->
<!--- Script for confirm Delete --->
<script type="text/javascript">
    function delete_confirm() {
        var msg = confirm('Are you sure you wish to empty your Shopping Cart?');
        if (msg == false) {
            return false;
        }
    }
</script>
<!--- Script for confirm Delete --->

<!------ Bootstrap core JavaScript ------->
<script src="./js/bootstrap.min.js"></script>
<!------ Bootstrap core JavaScript ------->
<!-- =====================END BODY END SECTION FOR PAGE SPECIFIC SCRIPTS========================= -->
</HTML>