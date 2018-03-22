<?php
/* Copyright 2018 Jonathan Riddell <jr@jriddell.org>
   May be copied under the GNU GPL version 3 (or later) only
*/

require_once 'GrabSepaGauges.php';
require_once 'GrabSepaRivers.php';

/*
Class to deal with the river sections data
call readFromJson() then $obj->riverSectionsData is an array of rivers with their data
[
    {
        "name": "Garry",
        "gauge_location_code": "234189",
        "longitude": "-4.84497",
        "latitude": "57.0759",
        "scrape_value": "0.4",
        "low_value": "0.6",
        "medium_value": "0.8",
        "high_value": "1",
        "very_high_value": "1.4",
        "huge_value": "1.8"
        "grade": "2-3",
        "guidebook_link": "http://www.ukriversguidebook.co.uk/foo",
        "sca_guidebook_no": "123",
        "access_issue": "http://www.canoescotland.org/news/river-clyde"
    }
]
*/
class RiverSections {
    const RIVER_SECTIONS_JSON = 'river-sections.json';
    const DATADIR = 'data';

    public $riverSectionsData;
    public $filename;

    function __construct() {
        $this->riverSectionsData = array();
        $this->filename = ROOT . '/' . self::DATADIR . '/' . self::RIVER_SECTIONS_JSON;
    }
    /* write data to file */
    function writeToJson() {
        $fp = fopen($this->filename, 'w');
        fwrite($fp, json_encode($this->riverSectionsData, JSON_PRETTY_PRINT));
        fclose($fp);
    }

    /* read river data from file */
    function readFromJson() {
        $json = file_get_contents($this->filename);
        $this->riverSectionsData = json_decode($json, true); // truely we do want this to be an array PHP
    }

    /* read from database and convert to our format, 
       to be called once ever in testReadFromDatabaseWriteJsonForReal() 
       to overwrite the .json file 
    */
    public function readFromDatabase() {  
        require('../config/database.php'); // sets $servername, $username, $password, $dbname
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $sql = "SELECT * FROM node_revisions join content_type_river_section ON node_revisions.vid = content_type_river_section.vid";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                $riverSection = array('name'=> $row["title"],
                                 'gauge_location_code' => $row["field_guageid_0_value"],
                                 'longitude' => $row["field_longitude_value"],
                                 'latitude' => $row["field_latitude_value"],
                                 'scrape_value' => $row["field_scrape_value"],
                                 'low_value' => $row["field_low_value"],
                                 'medium_value' => $row["field_medium_value"],
                                 'high_value' => $row["field_high_value"],
                                 'very_high_value' => $row["field_very_high_value"],
                                 'huge_value' => $row["field_huge_value"]
                                 );
                $this->riverSectionsData[] = $riverSection;
            }
        } else {
            echo "0 results";
        }
        $conn->close();
    }

    /* HTML editable form */
    public function editRiverForm() {
        $reply = "";

        foreach($this->riverSectionsData as $jsonid => $riverSection) {
            $reply .= "<form action='river-section.php' method='post'>\n";
            $reply .= "<input type='hidden' name='riverUpdates' value='{$jsonid}' />\n";
            $reply .= $this->editRiverFormLine($riverSection);
            $reply .= "<input type='submit' name='save' value='Save' />\n";
            $reply .= "<input type='submit' name='delete' value='&#10060;' class='right' />\n";
            $reply .= "</form>\n";
        }
        return $reply;
    }

    /* HTML editable form for a river section */
    public function editRiverFormLine($riverSection) {
        if (!array_key_exists('access_issue', $riverSection)) {
            $riverSection['access_issue'] = '';
        }
        $reply = "";
        $reply .= "<legend>" . $riverSection['name'] . "</legend>";
        $reply .= $this->editRiverFormInputItem("River/Section Name", "name", $riverSection['name']);
        $reply .= $this->editRiverFormInputItem("SEPA Gauge Code", "gauge_location_code", $riverSection['gauge_location_code'], "right");
        $reply .= $this->editRiverFormInputItem("Latitude", "latitude", $riverSection['latitude']);
        $reply .= $this->editRiverFormInputItem("Longitude", "longitude", $riverSection['longitude'], "right");
        $reply .= $this->editRiverFormInputItem("Scrape", "scrape_value", $riverSection['scrape_value']);
        $reply .= $this->editRiverFormInputItem("Low", "low_value", $riverSection['low_value'], "right");
        $reply .= $this->editRiverFormInputItem("Medium", "medium_value", $riverSection['medium_value']);
        $reply .= $this->editRiverFormInputItem("High", "high_value", $riverSection['high_value'], "right");
        $reply .= $this->editRiverFormInputItem("Very High", "very_high_value", $riverSection['very_high_value']);
        $reply .= $this->editRiverFormInputItem("Huge", "huge_value", $riverSection['huge_value'], "right");
        $reply .= $this->editRiverFormInputItem("Grade", "grade", $riverSection['grade']);
        $reply .= $this->editRiverFormInputItem("Guidebook Link", "guidebook_link", $riverSection['guidebook_link'], "right");
        $reply .= $this->editRiverFormInputItem("SCA Guidebook No", "sca_guidebook_no", $riverSection['sca_guidebook_no']);
        $reply .= $this->editRiverFormInputItem("Acccess Issue Link", "access_issue", $riverSection['access_issue'], "right");
        return $reply;
    }

    /* one text field in the river form */
    public function editRiverFormInputItem($text, $name, $value, $column="left") {
        $reply = "";
        $reply .= "<label for='{$name}' class='{$column}'>{$text}:</label>\n";
        $reply .= "<input type='text' name='{$name}' value='{$value}' class='{$column}' /> \n";
        return $reply;
    }

    /* read submitted HTML form to update rivers */
    public function updateRiverSection($postData) {
        $jsonid = $postData['riverUpdates'];
        $riverSection = $this->riverSectionsData[$jsonid];
        try {
            $this->validateRiverSectionUpdateData($postData);
        } catch (Exception $e) {
            $name = $riverSection['name'];
            return "<b>&#9888;Not updated $name</b><br />Validation error: " . $e->getMessage();
        }
        $riverSection['name'] = $postData['name'];
        $riverSection['gauge_location_code'] = $postData['gauge_location_code'];
        $riverSection['longitude'] = $postData['longitude'];
        $riverSection['latitude'] = $postData['latitude'];
        $riverSection['scrape_value'] = $postData['scrape_value'];
        $riverSection['low_value'] = $postData['low_value'];
        $riverSection['medium_value'] = $postData['medium_value'];
        $riverSection['high_value'] = $postData['high_value'];
        $riverSection['very_high_value'] = $postData['very_high_value'];
        $riverSection['huge_value'] = $postData['huge_value'];
        $riverSection['grade'] = $postData['grade'];
        $riverSection['guidebook_link'] = $postData['guidebook_link'];
        $riverSection['sca_guidebook_no'] = $postData['sca_guidebook_no'];
        $riverSection['access_issue'] = $postData['access_issue'];
        $this->riverSectionsData[$jsonid] = $riverSection;
        $this->writeToJson();
        return "Updated data for " . $riverSection['name'];
    }

    /* do validation on river section values
       throw exception if a problem
    */
    private function validateRiverSectionUpdateData($postData) {
        $this->validateFloat("Longitude", $postData['longitude']);
        $this->validateFloat("Latitude", $postData['latitude']);
        $this->validateFloat("Scrape Value", $postData['scrape_value']);
        $this->validateFloat("Low Value", $postData['low_value']);
        $this->validateFloat("Medium Value", $postData['medium_value']);
        $this->validateFloat("High Value", $postData['high_value']);
        $this->validateFloat("Very High Value", $postData['very_high_value']);
        $this->validateFloat("Huge Value", $postData['huge_value']);
        if (filter_var($postData['gauge_location_code'], FILTER_VALIDATE_INT) === false) {
            throw new Exception("SEPA gauge code not an int");
        }
        $this->validateNotNegative("Latitude", $postData['latitude']);
        $this->validateNotNegative("Scrape Value", $postData['scrape_value']);
        $this->validateNotNegative("Low Value", $postData['low_value']);
        $this->validateNotNegative("Medium Value", $postData['medium_value']);
        $this->validateNotNegative("High Value", $postData['high_value']);
        $this->validateNotNegative("Very High Value", $postData['very_high_value']);
        $this->validateNotNegative("Huge Value", $postData['huge_value']);
        if ($postData['scrape_value'] > $postData['low_value'] ||
            $postData['low_value'] > $postData['medium_value'] ||
            $postData['medium_value'] > $postData['high_value'] ||
            $postData['high_value'] > $postData['very_high_value'] ||
            $postData['very_high_value'] > $postData['huge_value']) {
            throw new Exception("River level values not in sequential order");
        }
        if (!filter_var($postData['name'], FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/^[a-zA-Z \(\)]+$/")))) {
            throw new Exception("Name not text");
        }
    }

    /* throw exception if it's not a float */
    private function validateFloat($name, $data) {
        if (filter_var($data, FILTER_VALIDATE_FLOAT) === false && filter_var($data, FILTER_VALIDATE_INT) === false) {
            throw new Exception("$name $data is not a number");
        }
    }

    /* throw exception if it's negatuve */
    private function validateNotNegative($name, $data) {
        if ($data < 0) {
            throw new Exception("$name is negative");
        }
    }

    /* HTML editable form for adding a new section */
    public function addRiverForm() {
        $riverSection = array();
        $riverSection['name'] = "";
        $riverSection['gauge_location_code'] = "";
        $riverSection['longitude'] = "";
        $riverSection['latitude'] = "";
        $riverSection['scrape_value'] = "";
        $riverSection['low_value'] = "";
        $riverSection['medium_value'] = "";
        $riverSection['high_value'] = "";
        $riverSection['very_high_value'] = "";
        $riverSection['huge_value'] = "";
        $riverSection['grade'] = "";
        $riverSection['guidebook_link'] = "";
        $riverSection['sca_guidebook_no'] = "";
        $riverSection['access_issue'] = "";

        $reply = "<legend>Add New River Section</legend>";
        $reply .= "<form action='river-section.php' method='post'>\n";
        $reply .= $this->editRiverFormLine($riverSection);
        $reply .= "<input type='submit' name='add' value='Add New River' />\n";
        $reply .= "</form>\n";
        return $reply;
    }

    /* process add new river submit */
    public function addNewRiverSection($postData) {
        try {
            $this->validateRiverSectionUpdateData($postData);
        } catch (Exception $e) {
            $name = $postData['name'];
            return "<b>&#9888;Not added $name</b><br />Validation error: " . $e->getMessage() . "<br />Click Back to retry";
        }
        $riverSection = array();
        $riverSection['name'] = $postData['name'];
        $riverSection['gauge_location_code'] = $postData['gauge_location_code'];
        $riverSection['longitude'] = $postData['longitude'];
        $riverSection['latitude'] = $postData['latitude'];
        $riverSection['scrape_value'] = $postData['scrape_value'];
        $riverSection['low_value'] = $postData['low_value'];
        $riverSection['medium_value'] = $postData['medium_value'];
        $riverSection['high_value'] = $postData['high_value'];
        $riverSection['very_high_value'] = $postData['very_high_value'];
        $riverSection['huge_value'] = $postData['huge_value'];
        $riverSection['grade'] = $postData['grade'];
        $riverSection['guidebook_link'] = $postData['guidebook_link'];
        $riverSection['sca_guidebook_no'] = $postData['sca_guidebook_no'];
        $riverSection['access_issue'] = $postData['access_issue'];
        $this->riverSectionsData[] = $riverSection;
        $this->writeToJson();
        return "Added new river " . $riverSection['name'];
    }

    /* deal with submitted request to delete a river */
    public function deleteRiverSection($postData) {
        $jsonid = $postData['riverUpdates'];
        unset($this->riverSectionsData[$jsonid]); // turns it into a hash.
        $this->riverSectionsData = array_values($this->riverSectionsData); // returns it to array.  Go PHP.
        $this->writeToJson();
        return "Deleted section " . $postData['name'];
    }

    //returns time of last download from SEPA
    public function downloadTime() {
        $timestampFile = fopen(ROOT . '/' . self::DATADIR .'/download_reading_timestamp', "r") or die("Unable to open file!");
        $timestamp = fread($timestampFile, 20);
        return date('D d M Y H:i', $timestamp);
    }

    //returns SEPA reading which is most recent
    public function calculateMostRecentReading() {
        $grabSepaGauges = new GrabSepaGauges;
        $sepaGaugesData = $grabSepaGauges->sepaData();
        $grabSepaRivers = new GrabSepaRivers();
        if (!$grabSepaRivers->readFromJson()) {
            print "<h1>Sorry no river reading data available, try again soon</h1>";
            die();
        }
        $mostRecentTimestamp = 0;
        $mostRecentTime = "";
        $mostRecentRiver = "";
        $mostRecentLevel = 0;
        foreach($this->riverSectionsData as $jsonid => $riverSection) {
            $time = $grabSepaRivers->riversReadingsData[$riverSection['gauge_location_code']]['currentReadingTime'];
            if (empty($time)) {
                continue;
            }
            $time_explode = explode('/', $time); // need to swap date and month cos PHP likes US date format
            $ustime = $time_explode[1] . '/' . $time_explode[0] . '/' . $time_explode[2];
            $timestamp = strtotime($ustime);
            if ($timestamp > $mostRecentTimestamp) {
                $mostRecentTimestamp = $timestamp;
                $mostRecentTime = $time;
                $mostRecentRiver = $riverSection['name'];
                $mostRecentLevel = $grabSepaRivers->riversReadingsData[$riverSection['gauge_location_code']]['currentReading'];
            }
        }
        return "$mostRecentRiver at $mostRecentTime reading $mostRecentLevel";
    }

    // Used by table-view.php to print the table
    public function printTable() {
        $grabSepaGauges = new GrabSepaGauges;
        $sepaGaugesData = $grabSepaGauges->sepaData();
        $grabSepaRivers = new GrabSepaRivers();
        if (!$grabSepaRivers->readFromJson()) {
            print "<h1>Sorry no river reading data available, try again soon</h1>";
            die();
        }
        foreach($this->riverSectionsData as $jsonid => $riverSection) {
            //read river data and pass to jsForRiver
            $this->trForRiver($jsonid, $riverSection, $sepaGaugesData, $grabSepaRivers->riversReadingsData[$riverSection['gauge_location_code']]);
        }
    }

    private function trForRiver($jsonid, $riverSection, $sepaGaugesData, $riverReadingData) {
        $sepaGaugeLocationCode = $riverSection['gauge_location_code'];
        print "<tr class='riverSectionRow'>\n";
        if (!array_key_exists($sepaGaugeLocationCode, $sepaGaugesData)) {
            //print "\n// Error: no SEPA reading for river " . $riverSection['name'] . "\n";
            //return;
            $riverReadingData['currentReading'] = 0;
            $waterLevelValue = "NO_GUAGE_DATA";
        } elseif ($riverReadingData['currentReading'] == '-1') {
            $waterLevelValue = "OLD_DATA";
        } else {
            $waterLevelValue = $this->waterLevelValue($riverReadingData['currentReading'], $riverSection);
        };
        
        if ($riverSection['scrape_value'] == $riverSection['huge_value']) {
            $waterLevelValue = "NEEDS_CALIBRATIONS";
        }

        $linkContent = "<a target='_blank' rel='noopener' href='http://apps.sepa.org.uk/waterlevels/default.aspx?sd=t&lc=".$riverSection['gauge_location_code']."'><img src='/wheres-the-water/pics/graph-icon.png'/></a>";
        $linkContent .= "&nbsp; <a target='_blank' rel='noopener' href='http://riverlevels.mobi/SiteDetails/Index/".$riverSection['gauge_location_code']."'><img src='/wheres-the-water/pics/phone-icon.png'/></a>";
        $linkContent .= "&nbsp; <a target='_blank' rel='noopener' href='https://www.openstreetmap.org/?mlat=".$riverSection['latitude']."&mlon=".$riverSection['longitude']."#map=12/"
                        .$riverSection['latitude']."/".$riverSection['longitude']."'><img src='/wheres-the-water/pics/osm.png' width='22' height='22' /></a>";
        $linkContent .= "&nbsp; <a href='geo:".$riverSection['latitude'].",".$riverSection['longitude']."'><img src='/wheres-the-water/pics/22-apps-marble.png' width='22' height='22' /></a>";
        
        if (!empty($riverSection['guidebook_link'])) {
            $linkContent .= "&nbsp; <a target='_blank' rel='noopener' href='".$riverSection['guidebook_link']."'><img src='/wheres-the-water/pics/ukrgb.ico'/></a>";
        }
        if (!empty($riverSection['sca_guidebook_no'])) {
            $linkContent .= "&nbsp; <img title='SCA WW Guidebook number' src='/wheres-the-water/pics/sca.png' />".$riverSection['sca_guidebook_no'];
        }
        if (!empty($riverSection['access_issue'])) {
            $linkContent .= "&nbsp; <a target='_blank' rel='noopener' href='".$riverSection['access_issue']."'><img title='Access Issue Link' src='/wheres-the-water/pics/warning.png' /></a>";
        }
        
        //User friendly water level values
        $waterLevelValueReadable = array('EMPTY'=>'Empty', 'SCRAPE'=>'Scrape', 'LOW'=>'Low', 'MEDIUM'=>'Medium', 'HIGH'=>'High', 'VERY_HIGH'=>'Very High', 'HUGE'=>'Huge', 'NO_GUAGE_DATA'=>'No Gauge Data', 'OLD_DATA'=>'Old Data', 'NEEDS_CALIBRATIONS'=>'Needs Calibrations');
        
        // Making the table orderable by water level
        $waterLevelValueArray = array('NO_GUAGE_DATA', 'OLD_DATA', 'NEEDS_CALIBRATIONS', 'EMPTY', 'SCRAPE', 'LOW', 'MEDIUM', 'HIGH', 'VERY_HIGH', 'HUGE');
        $waterLevelValueNumber = array_search($waterLevelValue, $waterLevelValueArray);

        // Create an array of info
        $infoArray = array('riverSection' => $riverSection['name'],
            'riverGrade' => $riverSection['grade'],
            'waterLevelValue' => $waterLevelValue,
            'waterLevelValueRead' => $waterLevelValueReadable[$waterLevelValue],
            'waterLevelValueNumber' => $waterLevelValueNumber,
            'latitude' => $riverSection['latitude'],
            'longitude' => $riverSection['longitude'],
            'trend' => $riverReadingData['trend'],
            'currentReadingTime' => $riverReadingData['currentReadingTime'],
            'currentReading' => $riverReadingData['currentReading'],
            'scrapeValue' => $riverSection['scrape_value'],
            'lowValue' => $riverSection['low_value'],
            'mediumValue' => $riverSection['medium_value'],
            'highValue' => $riverSection['high_value'],
            'veryHighValue' => $riverSection['very_high_value'],
            'hugeValue' => $riverSection['huge_value'],
            'gaugeLocationCode' => $riverSection['gauge_location_code'],
            'link' => $linkContent
        );
        
        switch ($infoArray['waterLevelValue']){
            case 'EMPTY':
                $color = '#CCCCCC';
                break;
            case 'SCRAPE':
                $color = '#CCFFCC';
                break;
            case 'LOW':
                $color = '#00FF00';
                break;
            case 'MEDIUM':
                $color = '#FFFF33';
                break;
            case 'HIGH':
                $color = '#FFC004';
                break;
            case 'VERY_HIGH':
                $color = '#FF6060';
                break;
            case 'HUGE':
                $color = '#FF0000';
                break;
            case 'OLD_DATA':
                $color = '#FFFFFF';
                break;
            case 'NO_GUAGE_DATA':
                $color = '#FFFFFF';
                break;
            case 'CONVERSION_UNKNOWN':
                $color = '#FFFFFF';
                break;
            default:
                $color = '#FFFFFF';
        }
        
        $displayedValues = array('riverSection', 'riverGrade', 'waterLevelValueRead', 'trend', 'currentReading', 'link');
        
        // Populate the table
        foreach ($infoArray as $class => $val){
            if (in_array($class, $displayedValues)){
                $visibility = " style='background-color: $color' ";
            }
            else {
                $visibility = " style='display: none' ";
            }
            print "<td class='$class'$visibility>$val</td>\n";
        }
        print "</tr>\n";
    }

    /* javascript for website */
    public function outputJavascript() {
        $grabSepaGauges = new GrabSepaGauges;
        $sepaGaugesData = $grabSepaGauges->sepaData();
        /*
        print "sepaData: " . $sepaGaugesData['234189']['current_level'] . ";\n";
        print "json: " . json_encode($sepaGaugesData, JSON_PRETTY_PRINT) . ";\n";
        */
        //print json_encode($this->riverSectionsData, JSON_PRETTY_PRINT);
        //print json_encode($sepaGaugesData, JSON_PRETTY_PRINT);
        $grabSepaRivers = new GrabSepaRivers();
        if (!$grabSepaRivers->readFromJson()) {
            print "</script>";
            print "<h1>Sorry no river reading data available, try again soon</h1>";
            die();
        }
        foreach($this->riverSectionsData as $jsonid => $riverSection) {
            //read river data and pass to jsForRiver
            $this->jsForRiver($jsonid, $riverSection, $sepaGaugesData, $grabSepaRivers->riversReadingsData[$riverSection['gauge_location_code']]);
        }
    }

    private function jsForRiver($jsonid, $riverSection, $sepaGaugesData, $riverReadingData) {
        $sepaGaugeLocationCode = $riverSection['gauge_location_code'];
        $waterLevelValue = "";
        if (!array_key_exists($sepaGaugeLocationCode, $sepaGaugesData)) {
            print "\n// Warning: no SEPA reading for river " . $riverSection['name'] . "\n";
            $riverReadingData['currentReading'] = 0;
            $waterLevelValue = "NO_GUAGE_DATA";
        } elseif ($riverReadingData['currentReading'] == '-1') {
            $waterLevelValue = "OLD_DATA";
        } else {
            $waterLevelValue = $this->waterLevelValue($riverReadingData['currentReading'], $riverSection);
        }
        if ($riverSection['scrape_value'] == $riverSection['huge_value']) {
            $waterLevelValue = "NEEDS_CALIBRATIONS";
        }

        print "var point$jsonid = new GLatLng(".$riverSection['latitude'].",".$riverSection['longitude'].");\n";
        print "markerOptions = { icon:${waterLevelValue}Icon };\n";
        print "var marker$jsonid = new GMarker(point$jsonid, markerOptions);\n";
        print "GEvent.addListener(marker$jsonid, \"mouseover\", function() {\n";
        print "    showSectionInfo(\"".$riverSection['name']."\", \"$waterLevelValue\", \"".$riverReadingData['currentReadingTime']."\", \"".$riverReadingData['currentReading']."\", \"".$riverReadingData['trend']."\" );\n";
        print "    showConversionInfo(\"$waterLevelValue\", \"".$riverSection['scrape_value']."\",\"".$riverSection['low_value']."\", \"".$riverSection['medium_value']."\", \"".$riverSection['high_value']."\", \"".$riverSection['very_high_value']."\", \"".$riverSection['huge_value']."\");\n";
        print "});\n";
        print "GEvent.addListener(marker$jsonid, \"click\", function() {  showPicWin('http://apps.sepa.org.uk/waterlevels/default.aspx?sd=t&lc=".$riverSection['gauge_location_code']."') });\n";
        print "map.addOverlay(marker$jsonid);\n\n";
    }

    // return the human readable water level (low, medium etc)
    //TODO will puting a space in very high break anything? yep, fix
    private function waterLevelValue($currentLevel, $riverSection) {
        if ($currentLevel < $riverSection['scrape_value']) {
            return "EMPTY";
        } elseif ($currentLevel < $riverSection['low_value']) {
            return "SCRAPE";
        } elseif ($currentLevel < $riverSection['medium_value']) {
            return "LOW";
        } elseif ($currentLevel < $riverSection['high_value']) {
            return "MEDIUM";
        } elseif ($currentLevel < $riverSection['very_high_value']) {
            return "HIGH";
        } elseif ($currentLevel < $riverSection['huge_value']) {
            return "VERY_HIGH";
        } else {
            return "HUGE";
        }
    }
}
/*
$grabSepa = new GrabSepa;
$grabSepa->setVariable('hello');
print "<p>" . $grabSepa->getVariable();
*/

/*
if (time()-filemtime(datadir + SEPA_CSV) > sepa_download_period) {
  // file older than 2 hours
  //grab file
  //check it's valid
  //parse to variable
  //write
} else {
  // read value
}
*/
