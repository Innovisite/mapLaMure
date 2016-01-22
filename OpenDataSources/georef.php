<?php

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

include_once("CSVReader.inc");

if(count($argv) < 2) {
  die("Error: Usage " . $argv[0] . 
      " <conffile>\n" . 
      "\t <conffile> Configuration file see documentation\n");
}

try
{
  $confFile = $argv[1];
  if(!file_exists($confFile))
    {
      throw new Exception("Cannot find configuration file $confFile.");
    }

  include_once($confFile);

  if(!isset($georefParams))
    {
      throw new Exception("configuration file syntax error, georefParams does not exist.");
    }  

  $csvReader = new CSVReader($georefParams["src"]);
  $logHandle = fopen($georefParams["dst"]["log"], "w");
  $colsAdr = $georefParams["geoCols"];

  $csvReader->addCol("georef_lon");
  $csvReader->addCol("georef_lat");
  $csvReader->addCol("georef_engine");
  $csvReader->addCol("georef_label");
  $csvReader->addCol("georef_found");

  $data = $csvReader->getData();

  for($i = 0; $i < $csvReader->getNbRows(); ++$i)
    {
      $georefRes = array( "georef_lon" => 0,
			  "georef_lat" => 0,
			  "georef_engine" => "",
			  "georef_label" => "",
			  "georef_found" => 0 );

      $found = false;
      $adresse = concatColsValue($data, $colsAdr, $i);
      $adresse = preg_replace('/\(.*\)/','', $adresse);

      $urlQuery = "http://nominatim.openstreetmap.org/search?q=" . 			      
      urlencode($adresse) . 
	'&format=json&countrycodes=fr&limit=1';

      $json = file_get_contents($urlQuery);

      if($json !== FALSE)
	{
	  $resGeo = json_decode($json, true);
	  if(is_array($resGeo) and count($resGeo))
	    {
	      $found = true;
	      $georefRes["georef_lon"] = $resGeo[0]["lon"];
	      $georefRes["georef_lat"] = $resGeo[0]["lat"];
	      $georefRes["georef_engine"] = "nominatim";
	      $georefRes["georef_label"] = $resGeo[0]["display_name"];
	      $georefRes["georef_found"] = 1;
	    }
	}

      if(!$found)
	{
	  $explodeLimit = $georefParams["geoFilters"]["explodeLimit"];
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
			  $georefRes["georef_lon"] = $geometry["coordinates"][0];
			  $georefRes["georef_lat"] = $geometry["coordinates"][1];
			  $georefRes["georef_engine"] = "data.gouv.fr";
			  $georefRes["georef_label"] = $resGeo["features"][0]["properties"]["label"];
			  $georefRes["georef_found"] = 1;
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
	}

      if($found)
	{	
	  fwrite($logHandle, "=== Adresse found $i : $adresse \n");
	}
      else
	{
	  fwrite($logHandle, "!!! Adresse not found $i : $adresse \n");
	}

      foreach($georefRes as $k => $v)
	{
	  $data[$k][$i] = $v;
	}
    }

  $csvReader->setData($data);  
  $str = $csvReader->writeStr("georef_lon");  
  file_put_contents($georefParams["dst"]["file"], $str);
}
catch(Exception $e)
{
  die("Error : " . $e->getMessage() . "\n");
}

?>
