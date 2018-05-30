<?php


require_once './conf/DBconnect.php';

if(isset($_GET['query'])) {
    $query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING);
}

$id = (int)$query;

//$query1 = "SELECT * FROM plant left join storeref on plant.idPlant=storeref.idPlant WHERE lower(plant.AccessionName) LIKE lower('%$query%') OR plant.idPlant=$id";

$query2 = "SELECT idPlant FROM storeref left join plant on storeref.idPlant=plant.idPlant WHERE lower(storeref.StoreCode) LIKE lower('%$query%')";

$query1="SELECT plant.idPlant, plant.SubCollection, plant.AccessionName, StoreCode, Genus, Species, SubTaxa, Pedigree, GROUP_CONCAT(Synonym) as Synonyms, 
  donor.Name as DonorName, breeder.Name as BreederName, SampStatDesc, Country, SowSeason, AccYear, CollSite
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
						WHERE (plant.idPlant=$id) OR (lower(plant.AccessionName) LIKE lower('%$query%'))
						GROUP BY plant.idPlant";



$rows1 = array();
$rows2 = array();

if ($result1 = $dbcnx->query($query1)) {
    while($r1 = mysqli_fetch_assoc($result1)) {
        $r1['phenotype'] = array();
        $this_idPlant = $r1['idPlant'];
        $phototype_query = "SELECT PhenotypeParameter, PhenotypeValue, PhenotypeDescribedBy from phenotype WHERE idPlant=$this_idPlant";
        if ($result_phenotype = $dbcnx->query($phototype_query)) {
            while($r_phenotype = mysqli_fetch_assoc($result_phenotype)) {
                $r1['phenotype'][] = $r_phenotype;
            }
        }
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
print json_encode($rows1);

$dbcnx->close();
?>