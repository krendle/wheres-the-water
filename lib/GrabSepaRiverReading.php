<?php
/* Copyright 2018 Jonathan Riddell <jr@jriddell.org>
   May be copied under the GNU GPL version 3 (or later) only
*/

/* Downloads and makes available the reading levels for a given river defined by the SEPA gauge_id
   use doGrabSepaRiver($gauge_id) to initialise
   Uses level data .csv file e.g.
   http://apps.sepa.org.uk/database/riverlevels/133094-SG.csv
   This is only for use by GrabSepaRivers when doing the bulk download, too slow and resource intensive to do it more often
*/

require_once 'SepaRiverReadingHistory.php';

class GrabSepaRiverReading {
    const DATADIR = 'data';
    const SEPA_DOWNLOAD_PERIOD = 300; // 60 * 5; // make sure current download is no older than 5 minutes
    const SEPA_URL = 'http://apps.sepa.org.uk/database/riverlevels/';

    public $gauge_id;
    public $currentReading;
    public $trend;
    public $currentReadingTime;
    public $sepaURL;
    public $dataDir;

    function __construct() {
        $this->sepaURL = self::SEPA_URL;
        $this->dataDir = ROOT . '/' . self::DATADIR;
    }

    public function doGrabSepaRiver($gauge_id) {
        $this->gauge_id = $gauge_id;

        $riverFilename = "${gauge_id}-SG.csv";
        $riverFilePath = $this->dataDir . '/' . $riverFilename;
        $riverFileURL = $this->sepaURL . $riverFilename;
        if (!file_exists($riverFilePath) || time()-filemtime($riverFilePath) > self::SEPA_DOWNLOAD_PERIOD) {
            $riverData = @file_get_contents($riverFileURL);
            if($riverData == false) {
                print "<p>No SEPA gauge data for " . $gauge_id . "</p>\n";
                flush();
                return False;
            }
            if (!$this->validateRiverData($riverData)) {
                print "<p>Empty file downloaded for " . $gauge_id . "</p>\n";
                $this->currentReading = -1;
                flush();
                return False;
            }
            $newSepaFile = fopen($riverFilePath, "w") or die("Unable to open file!");
            fwrite($newSepaFile, $riverData);
        } else {
            $riverData = file_get_contents($riverFilePath);
        }

        $riverDataArray = explode("\n", $riverData);
        //print_r($riverDataArray);
        //Get the last value (uses -2 as -1 final entry is just a new line)
        //$mostRecentReading = array_slice($riverDataArray, -2, 1)[0]; // '03/03/2018 12:45:00,0.53'
        $slice = array_slice($riverDataArray, -2, 1);
        $mostRecentReading = $slice[0];
        $mostRecentReading = rtrim($mostRecentReading);
        $mostRecentReadingPair = explode(",", $mostRecentReading); // ['03/03/2018 12:45:00', '0.53']
        $this->currentReading = $mostRecentReadingPair[1];
        $this->currentReadingTime = $mostRecentReadingPair[0];
        //$pastReading = array_slice($riverDataArray, -6, 1)[0]; // '03/03/2018 11:45:00,0.53'
        $slice = array_slice($riverDataArray, -6, 1);
        $pastReading = $slice[0];
        $pastReading = rtrim($pastReading);
        $pastReadingPair = explode(",", $pastReading); // ['03/03/2018 11:45:00', '0.53']
        if ($this->currentReading > $pastReadingPair[1]) {
            $this->trend = 'RISING';
        } elseif ($this->currentReading == $pastReadingPair[1]) {
            $this->trend = 'STABLE';
        } else {
            $this->trend = 'FALLING';
        }
        print "<p>Downloaded River Reading for gauge ".$gauge_id."</p>\n";
        flush();
        // save it to history
        $history = new SepaRiverReadingHistory($gauge_id);
        $time_explode = explode('/', $this->currentReadingTime); // need to swap date and month cos PHP likes US date format
        $ustime = $time_explode[1] . '/' . $time_explode[0] . '/' . $time_explode[2];
        $timestamp = strtotime($ustime);
        $history->newReading($timestamp, $this->currentReading);
    }

    private function validateRiverData($riverData) {
        $riverDataArray = explode("\n", $riverData);
        if (count($riverDataArray) <= 10) {
            return false;
        }
        return true;
    }
}
