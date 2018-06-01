<?php


require_once './conf/DBconnect.php';

if(isset($_GET['query'])) {
    $query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING);
}
//$id = 0;
//if ((int)$query>0){
//    $id = (int)$query;
//}

//$query1 = "SELECT * FROM plant left join storeref on plant.idPlant=storeref.idPlant WHERE lower(plant.AccessionName) LIKE lower('%$query%') OR plant.idPlant=$id";

$query2 = "SELECT idPlant FROM storeref left join plant on storeref.idPlant=plant.idPlant WHERE lower(storeref.StoreCode) LIKE lower('%$query%')";
//
//$query1="SELECT plant.idPlant, plant.SubCollection, plant.AccessionName, StoreCode, Genus, Species, SubTaxa, Pedigree, GROUP_CONCAT(Synonym) as Synonyms,
//  donor.Name as DonorName, breeder.Name as BreederName, SampStatDesc, Country, SowSeason, AccYear, CollSite, taxon.TaxonCode, Genus, SubTaxa, CommonName,
//  TaxonSynonym, Ploidy, Karyotype, Genome, CommonTerms, SpeciesAuthor, SubSpeciesAuthor, TaxonComments, idDonor, idBreeder
//						FROM `plant`
//						JOIN `taxon` ON plant.idTaxon=taxon.idTaxon
//						LEFT JOIN `pedigree` ON plant.idPedigree=pedigree.idPedigree
//						LEFT JOIN `exped` ON plant.idPlant=exped.idPlant
//						LEFT JOIN `synonym` ON plant.idPlant=synonym.idPlant
//						LEFT JOIN `somebody` as donor ON plant.idDonor=donor.idSomebody
//						LEFT JOIN `somebody` as breeder ON plant.idBreeder = breeder.idSomebody
//						LEFT JOIN `codesampstat` ON plant.SampStat =codesampstat.SampStat
//						LEFT JOIN `codecountry` ON plant.OriginCountry =codecountry.CountryCode
//						LEFT JOIN `storeref` ON plant.idPlant=storeref.idPlant
//						WHERE (plant.idPlant=$id) OR (lower(plant.AccessionName) LIKE lower('%$query%'))
//						GROUP BY plant.idPlant";



$rows1 = array();
$rows1[] = getPlantData($query);


$rows2 = array();

//if ($result1 = $dbcnx->query($query1)) {
//    while($r1 = mysqli_fetch_assoc($result1)) {
//        $r1['phenotype'] = array();
////        $r1['donnorAddress'] = array();
////        $r1['breederAddress'] = array();
//        $this_idPlant = $r1['idPlant'];
//        $idBreeder = $r1['idBreeder'];
//        $idDonor = $r1['idDonor'];
//        $phototype_query = "SELECT PhenotypeParameter, PhenotypeValue, PhenotypeDescribedBy from phenotype WHERE idPlant=$this_idPlant";
//        if ($result_phenotype = $dbcnx->query($phototype_query)) {
//            while($r_phenotype = mysqli_fetch_assoc($result_phenotype)) {
//                $r1['phenotype'][] = $r_phenotype;
//            }
//        }
//        $r1['donnorAddress'] = getAddress($idDonor);
//        $r1['breederAddress'] = getAddress($idBreeder);
//        $rows1[] = $r1;
//    }
//    $result1->close();
//}
//if ($result2 = $dbcnx->query($query2)) {
//    while($r2 = mysqli_fetch_assoc($result2)) {
//        $rows2[] = $r2;
//    }
//    $result2->close();
//}
$rows = $rows1 + $rows2;
print json_encode($rows1);


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
        while($r1 = mysqli_fetch_assoc($result1)) {
            $r1['phenotype'] = array();
            $this_idPlant = $r1['idPlant'];
            $idBreeder = $r1['idBreeder'];
            $idDonor = $r1['idDonor'];
            $phototype_query = "SELECT PhenotypeParameter, PhenotypeValue, PhenotypeDescribedBy from phenotype WHERE idPlant=$this_idPlant";
            if ($result_phenotype = $dbcnx->query($phototype_query)) {
                while($r_phenotype = mysqli_fetch_assoc($result_phenotype)) {
                    $r1['phenotype'][] = $r_phenotype;
                }
            }
            $r1['donnorAddress'] = getAddress($idDonor);
            $r1['breederAddress'] = getAddress($idBreeder);
            return $r1;
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