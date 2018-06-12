<?php


require_once './conf/DBconnect.php';

if(isset($_GET['accessionname'])) {
    $accessionname = filter_input(INPUT_GET, 'accessionname', FILTER_SANITIZE_STRING);
}

global $dbcnx;
$query = "SELECT plant.idPlant, plant.SubCollection, plant.AccessionName, StoreCode, Genus, Species, SubTaxa, Pedigree, GROUP_CONCAT(Synonym) as Synonyms, 
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
						WHERE (lower(plant.AccessionName) LIKE lower('%$accessionname%'))
						GROUP BY plant.idPlant";

if ($result = $dbcnx->query($query)) {

    $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
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

        $r1['donorAddress'] = getAddress($idDonor);
        $r1['breederAddress'] = getAddress($idBreeder);
        $rows[] = $r;
    }
    print json_encode($rows);


    $result->close();
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