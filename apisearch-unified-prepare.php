<?php


require_once './conf/DBconnect.php';

if(isset($_GET['query'])) {
    $query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING);
}



//
//$rows1 = array();
//$rows1 = getPlantData($query);
//if ($rows1[0]==""){
//
//    $rows1 = array();
//}


$rows2 = array();

$row_ids = array();

$query2 = $dbcnx->stmt_init();

$query2 = $dbcnx->prepare("SELECT storeref.idPlant FROM storeref left join plant on storeref.idPlant=plant.idPlant WHERE lower(storeref.StoreCode) LIKE lower(?)");
//if (!(){
//    echo "Prepare failed1: (" . $dbcnx->errno . ") " . $dbcnx->error;
//}
//echo json_encode($query2);
//echo $_GET['query'];
if (!($query2->bind_param("s",$_GET['query']))){
    echo "Prepare failed2: (" . $query2->errno . ") " . $query2->error;
}
$query2->execute();
echo json_encode($query2);
if ($result2 = $query2->get_result()) {
    while($r2 = $result2->fetch_assoc()) {
        $row_ids[] = $r2;
    }
    $result2->close();
    foreach($row_ids as $key => $value){
        array_push($rows2, getPlantData($value));
    }


}
//$rows = $rows1 + $rows2;
print json_encode($row_ids);


function getPlantData($search_text){
    $search_id = 0;
    if ((int)$search_text>0){
        $search_id = (int)$search_text;
    }

    global $dbcnx;
    $plant_data_query="SELECT plant.idPlant, plant.SubCollection, plant.AccessionName, StoreCode, Genus, Species, SubTaxa, Pedigree, GROUP_CONCAT(Synonym) as Synonyms, 
  donor.Name as DonorName, breeder.Name as BreederName, SampStatDesc, Country, SowSeason, AccYear, CollSite, taxon.TaxonCode, Genus, SubTaxa, CommonName,
  TaxonSynonym, Ploidy, Karyotype, Genome, CommonTerms, SpeciesAuthor, SubSpeciesAuthor, TaxonComments, idDonor, idBreeder
						FROM `plant`  
						JOIN `taxon` ON plant.idTaxon=taxon.idTaxon
						LEFT JOIN `pedigree` ON plant.idPedigree=pedigree.idPedigree
						LEFT JOIN `exped` ON plant.idPlant=exped.idPlant
						LEFT JOIN `synonym` ON plant.idPlant=synonym.idPlant
						LEFT JOIN `somebody` as donor ON plant.idDonor=donor.idSomebody
						LEFT JOIN `somebody` as breeder ON plant.idBreeder = breeder.idSomebody
						LEFT JOIN `codesampstat` ON plant.SampStat =codesampstat.SampStat
						LEFT JOIN `codecountry` ON plant.OriginCountry =codecountry.CountryCode
						LEFT JOIN `storeref` ON plant.idPlant=storeref.idPlant
						WHERE (plant.idPlant=$search_id) OR (lower(plant.AccessionName) LIKE lower('%$search_text%'))
						GROUP BY plant.idPlant";
    if ($result1 = $dbcnx->query($plant_data_query)) {
        $tt = Array();
        while($r1 = mysqli_fetch_assoc($result1)) {
                $r1['phenotype'] = array();
                $this_idPlant = $r1['idPlant'];
                $idBreeder = $r1['idBreeder'];
                $idDonor = $r1['idDonor'];
                $phototype_query = "SELECT PhenotypeParameter, PhenotypeValue, PhenotypeDescribedBy from phenotype WHERE idPlant=$this_idPlant";
                if ($result_phenotype = $dbcnx->query($phototype_query)) {
                    while ($r_phenotype = mysqli_fetch_assoc($result_phenotype)) {
                        $r1['phenotype'][] = $r_phenotype;
                    }
                }
                $r1['donnorAddress'] = getAddress($idDonor);
                $r1['breederAddress'] = getAddress($idBreeder);
                $tt[] = $r1;
        }
        if (!empty($tt)){
            return $tt;
        } else {
            return '' ;
        }
        $result1->close();
    }
    $dbcnx->close();
}



function getAddress($somebodyId){
    global $dbcnx;
    $address_query="SELECT address.Department, address.InstituteName, address.InstituteAcronym, address.InstituteCode,
                                      address.AddressLine1, address.AddressLine2, address.AddressLine3, address.City, address.NationalRegion, address.PostZipCode, address.Country, address.CountryCode
                                FROM `somebody` 
                                LEFT JOIN `address` ON somebody.idAddress=address.idAddress
                                WHERE (somebody.idSomebody=$somebodyId) LIMIT 1";
    if ($result_address = $dbcnx->query($address_query)) {
        while($r_address = mysqli_fetch_assoc($result_address)) {
            return $r_address;
        }
    }
    $dbcnx->close();

}

$dbcnx->close();
?>