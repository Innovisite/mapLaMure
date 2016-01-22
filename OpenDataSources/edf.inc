<?php

$georefParams = array( "src" => array( "file" => "edf.csv",
				       "header" => true,
				       "sep" => ";" ),
		       "dst" => array( "file" => "edf_georef.csv",
				       "log" => "edf_georef.log" ),
		       "geoCols" => array( "adresse", "codepostal", "ville" ),
		       "geoFilters" => array( "explodeLimit" => 4 )
		       );

?>