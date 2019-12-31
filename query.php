<?php

// List of possible sensors and their files
$sensors = array(
	"23137" => array("file" => "data/23137.txt", "descr" => "Keimola", "dir1" => "Hämeenlinna", "dir2" => "Helsinki"),
	"23107" => array("file" => "data/23107.txt", "descr" => "Kaivoksela", "dir1" => "Hämeenlinna", "dir2" => "Helsinki"),
	"23145" => array("file" => "data/23145.txt", "descr" => "Kannelmäki", "dir1" => "Itäkeskus", "dir2" => "Tapiola"),
	"23147" => array("file" => "data/23147.txt", "descr" => "Pakila", "dir1" => "Itäkeskus", "dir2" => "Tapiola"),
	"23152" => array("file" => "data/23152.txt", "descr" => "Pirkkola", "dir1" => "Tampere", "dir2" => "Helsinki"),
	"23004" => array("file" => "data/23004.txt", "descr" => "Kivistö", "dir1" => "Hämeenlinna", "dir2" => "Helsinki"),
	"23159" => array("file" => "data/23159.txt", "descr" => "Petikko", "dir1" => "Vantaa", "dir2" => "Espoo"),
	"23123" => array("file" => "data/23123.txt", "descr" => "Pähkinärinne", "dir1" => "Vihti", "dir2" => "Helsinki"),
	"23165" => array("file" => "data/23165.txt", "descr" => "Hakuninmaa", "dir1" => "Hämeenlinna", "dir2" => "Helsinki"),
	"23005" => array("file" => "data/23005.txt", "descr" => "Klaukkala", "dir1" => "Hämeenlinna", "dir2" => "Helsinki"),
	"23138" => array("file" => "data/23138.txt", "descr" => "Odilampi", "dir1" => "Vihti", "dir2" => "Helsinki"),
	"23146" => array("file" => "data/23146.txt", "descr" => "Länsi-Pakila", "dir1" => "NA", "dir2" => "NA"),
	"23153" => array("file" => "data/23153.txt", "descr" => "Oulunkylä", "dir1" => "Tuusula", "dir2" => "Helsinki"),
	"23164" => array("file" => "data/23164.txt", "descr" => "Kivihaka", "dir1" => "Hämeenlinna", "dir2" => "Helsinki"),
	"23151" => array("file" => "data/23151.txt", "descr" => "Pitäjänmäki", "dir1" => "Vantaa", "dir2" => "Helsinki"),
	"23197" => array("file" => "data/23197.txt", "descr" => "Hakamäen tunneli", "dir1" => "NA", "dir2" => "NA")
	);


$sensorid = "23153";

if(isset($_GET["id"]) && !empty($_GET["id"]))
	$sensorid = $_GET["id"];

// Direction
$dir = 1;
if(isset($_GET["dir"]) && !empty($_GET["dir"]))
	$dir = $_GET["dir"];

// Default to current date
$reqday = date("d");
$reqmonth = date("m");
$reqyear = date("Y");

if(isset($_GET["d"]) && !empty($_GET["d"]))
	$reqday = $_GET["d"];
if(isset($_GET["m"]) && !empty($_GET["m"]))
	$reqmonth = $_GET["m"];
if(isset($_GET["y"]) && !empty($_GET["y"]))
	$reqyear = $_GET["y"];

// Low pass filter for data (between 0.0 and 1.0, 1.0 = no filter)
$filter = 1.0;
if(isset($_GET["filter"]) && !empty($_GET["filter"]))
	$filter = $_GET["filter"];

$amount = 5;
if(isset($_GET["n"]) && !empty($_GET["n"]))
	$amount = $_GET["n"];

$period = 2;
if(isset($_GET["period"]) && !empty($_GET["period"]))
	$period = $_GET["period"];


// Create date from the requested date
$date = date_create($reqyear."-".$reqmonth."-".$reqday);

// Array holding the dates for which the curves are plotted
$dates = array();

// Also create the array holding the curve data
$linedata = array("title" => "N/A", "datasets" => array());


// Current week + 4 previous weeks
for($unit=0; $unit < $amount; $unit++)
{
	// Push the date to array
	//array_push($dates, $date);
	array_push($dates, $date->format('Y-m-d'));

	// Create a placeholder for this data
	$linedata["datasets"][$unit] = array("label" => "NA", "data" => array(), "yaxis" => "y-axis-1", "fill" => false);

	// Go to previous week
	if($period == 2)
		date_modify($date, "-1 week");
	else
		date_modify($date, "-1 day");
}
// One more for current traffic amount
$linedata["datasets"][$amount] = array("label" => "Amount", "data" => array(), "yaxis" => "y-axis-2", "fill" => true);
// And one for last week(?)
$linedata["datasets"][$amount + 1] = array("label" => "Amount previous", "data" => array(), "yaxis" => "y-axis-2", "fill" => true);



// Parse the files, pushing data of correct dates to arrays
$sensordata = file($sensors[$sensorid]["file"]);

// Array of arrays containing X and Y pairs. First dimension is for each week, second is the single curve
foreach($sensordata as $line_num => $dataline)
{
	$data = explode(";", $dataline);

	if($line_num == 0) {
		// Headers on first line
		$dirtext = $sensors[$sensorid]["dir1"];
		if($dir == 2) $dirtext = $sensors[$sensorid]["dir2"];
		$linedata["title"] = $sensors[$sensorid]["descr"]." - suuntaan ".$dirtext;

		continue;
	}

	// Rest of the lines contain data

	$timestamp = $data[0];	// Use timestamp for all calculations
	//$datayear = $data[1];
	//$datamonth = $data[2];
	//$dataday = $data[3];
	//$datadate = date_create($datayear."-".$datamonth."-".$dataday);
	$datadate = date_create("@$timestamp");
	$datadate->setTimezone(new DateTimeZone("Europe/Helsinki"));


	// Check if this date is in the requested list?
	$index = array_search($datadate->format('Y-m-d'), $dates);
	if($index === FALSE) continue;


	// Label is the date
	$linedata["datasets"][$index]["label"] = $datadate->format('D d.m.Y');

	// Data
	// Use timestamp as the source of the X axis hour
	$hour = (float)$datadate->format('G');	// Hour in 24 h format, without leading zeros
	$minute = (float)$datadate->format('i');  // Minute
	$x = $hour + $minute / 60.0;
	if($dir == 2) {
	    $datavalue = $data[9];
	    $amountvalue = $data[7];
	} else {
	    $datavalue = $data[8];
	    $amountvalue = $data[6];
	}

	if (count($linedata["datasets"][$index]["data"]) == 0) {
	  $y = $datavalue;
	  $y2 = $amountvalue;
	} else {
	  $y = $filter * $datavalue + (1.0 - $filter) * $y; // TODO: Look at timestep also?
	  $y2 = $filter * $amountvalue + (1.0 - $filter) * $y2; // TODO: Look at timestep also?
	}

        $point = array("x" => $x, "y" => $y);
	array_push($linedata["datasets"][$index]["data"], $point);

	// Push traffic amount to only first two indices
	if($index == 0 || $index == 1) {
		$point2 = array("x" => $x, "y" => $y2);
		array_push($linedata["datasets"][$amount + $index]["data"], $point2);
	}
}

// It is also good to set the access security - just replace * with the domain you want to be able to reach it.
header('Access-Control-Allow-Origin: *');
header('Content-type: application/json');
//header("Content-Type: application/json;charset=utf-8");
$dataJSON = json_encode($linedata);
if (json_last_error() != JSON_ERROR_NONE) {
  // TODO: Handle JSON error
    //printf("JSON Error: %s", json_last_error_msg());
}
echo $dataJSON;

?>
