<?php

class CSVReader
{
  private $params = array( "file" => null,
			   "header" => true,
			   "sep" => ",",
			   "quote" => "\"",
			   "dec" => "." );			   

  private $data = array();

  private $colNames = array();

  private $nbRows = 0;

  public function __construct($params)
  {
    $this->params = array_merge($this->params, $params);
    $this->read_data();
  }

  public function getColNames()
  {
    return $this->colNames;
  }

  public function getColData($colName)
  {
    return $this->data[$colName];
  }

  public function getData()
  {
    return $this->data;
  }

  public function setData($data)
  {
    $this->data = $data;
  }
  
  public function getNbRows()
  {
    return $this->nbRows;
  }

  public function addCol($name)
  {
    $nbCols = count($this->colNames);
    $this->colNames[$name] = $nbCols;
    $this->data[$name] = array();
  }

  private function read_data()
  {    
    // fab : c'est merdique, mieux vaut faire readFromFile readFromStr...
    $rawdata = file($this->params["file"], FILE_SKIP_EMPTY_LINES);
    if(count($rawdata) > 0)
      {
	$this->nbRows = $this->params["header"] ? count($rawdata) - 1 : count($rawdata);
	$cols = array_map("trim", explode($this->params["sep"], $rawdata[0]));
	for($i = 0; $i < count($cols); ++$i)
	  {
	    $colName = $this->params["header"] ? $cols[$i] : "COL" . $i;
	    $this->colNames[$colName] = $i;
	    $this->data[$colName] = array();
	  }
	for($i = 1; $i < count($rawdata); $i++)
	  {
	    $cols = array_map("trim", explode($this->params["sep"], $rawdata[$i]));
	    foreach($this->colNames as $col => $colIdx)
	      {
		$this->data[$col][] = isset($cols[$colIdx]) ? $cols[$colIdx] : null;
	      }
	  }
      }
  }

  public function writeStr($colName)
  {
    $str = "";
    if($this->params["header"])
      {
	$firstSep = true;
	$row = "";
	foreach($this->colNames as $col => $colIdx)
	  {
	    if($firstSep)
	      {
		$firstSep = false;
	      }
	    else
	      {
		$row .=  $this->params["sep"];
	      }
	    $row .= $col;
	  }
	$row .= "\n";
	$str .= $row;
      }
    for($i = 0; $i < count($this->data[$colName]); ++$i)
      {
	$firstSep = true;
	$row = "";
	foreach($this->colNames as $col => $colIdx)
	  {
	    if($firstSep)
	      {
		$firstSep = false;
	      }
	    else
	      {
		$row .= $this->params["sep"];
	      }
	    $row .= isset($this->data[$col][$i]) ? $this->data[$col][$i] : null;
	  }
	$row .= "\n";
	$str .= $row;
      }
    return $str;
  }

}