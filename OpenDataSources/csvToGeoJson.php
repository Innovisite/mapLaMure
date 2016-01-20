<?php

include_once("CSVReader.inc");

function concatColsValue($data, $colsName, $row, $sep = " ")
{
  $res = "";
  $curSep = "";
  foreach($colsName as $v)
    {
      $res .= $curSep . $data[$v][$row];
      if($curSep == "")
	{
	  $curSep = $sep;
	}
    }
  return $res;
}

$csvReader = new CSVReader(array("file" => "edf.csv",
				 "header" => true,
				 "sep" => ";" ));				 

$logHandle = fopen("edf.csv.log", "w");

$colsName = array( "PDL" );
$colsAdr = array( "adresse", "codepostal", "ville" );
$colsToShow = array( "PDL" => "PDL",
		     "tarif" => "Tarif",
		     "adresse" => "Adresse",
		     "ref contrat" => "Référence Contrat",
		     "lieu dit" => "Lieu Dit" );

$geo = array( "type" => "FeatureCollection",
	      "features" => array() );

$csvReader->addCol("LON");
$csvReader->addCol("LAT");

$data = $csvReader->getData();

for($i = 0; $i < $csvReader->getNbRows(); ++$i)
  {
    $found = false;
    $name = concatColsValue($data, $colsName, $i);
    $adresse = concatColsValue($data, $colsAdr, $i);
    $adresse = preg_replace('/\(.*\)/','', $adresse);
    $properties = array( "name" => $name, "Adresse" => $adresse, "searchEngine" => "nominatim" );
    foreach($colsToShow as $k => $v)
      {
	if(isset($data[$k][$i])) 
	  {
	    $properties[$v] = $data[$k][$i];
	  }
      }		    

    $urlQuery = "http://nominatim.openstreetmap.org/search?q=" . 			      
      urlencode($adresse) . 
      '&format=json&countrycodes=fr&city=la+mure&limit=1';

    $json = file_get_contents($urlQuery);

    if($json !== FALSE)
      {
	$resGeo = json_decode($json, true);
	if(is_array($resGeo) and count($resGeo))
	  {
	    $found = true;
	    $geometry = array( "type" => "Point", 
			       "coordinates" => array( $resGeo[0]["lon"], $resGeo[0]["lat"] )
			       );
	  }
      }
    
    if(!$found)
      {
	$properties["searchEngine"] = "data.gouv.fr";
      }

    $explodeLimit = 4;
    while(!$found && $explodeLimit > 0)
      {
	$url = "http://api-adresse.data.gouv.fr/search/?q=" . urlencode($adresse);
	$json = file_get_contents($url);

	fwrite($logHandle, "??? $i ask for $url\n");

	if($json !== FALSE)
	  {
	    $resGeo = json_decode($json, true);
	    if($resGeo !== FALSE)
	      {
		if(isset($resGeo["features"]) and count($resGeo["features"]))
		  {
		    $geometry = $resGeo["features"][0]["geometry"];
		    $properties = array_merge($properties, $resGeo["features"][0]["properties"]);
		    $found = true;
		  }
	      }
	  }
	$explodes = explode(" ", $adresse, 2);
	if($explodes === false || 
	   count($explodes) < 2)
	  {
	    $explodeLimit = 0;
	  }
	else
	  {
	    --$explodeLimit;
	    $adresse = $explodes[1];
	  }
      }

    if($found)
      {	
	$geo["features"][] = array( "type" => "Feature",
				    "properties" => $properties,								   
				    "geometry" => $geometry
				    );		
	$data["LON"][$i] = $geometry["coordinates"][0];
	$data["LAT"][$i] = $geometry["coordinates"][1];
	fwrite($logHandle, "=== Adresse found $i : $adresse \n");
      }
    else
      {
	$data["LON"][$i] = "";
	$data["LAT"][$i] = "";
	fwrite($logHandle, "!!! Adresse not found $i : $adresse \n");
      }
  }

echo json_encode($geo, JSON_PRETTY_PRINT) . "\n";

?>