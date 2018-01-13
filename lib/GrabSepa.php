<?php

/*
Methods to download SEPA data and convert it to an associative array
*/
class GrabSepa {
    const SEPA_CSV = 'SEPA_River_Levels_Web.csv';
    const DATADIR = 'data';
    const SEPA_DOWNLOAD_PERIOD = 60 * 10;
    const SEPA_URL = 'http://apps.sepa.org.uk/database/riverlevels/SEPA_River_Levels_Web.csv';

    public $sepaFile = self::DATADIR . '/' . self::SEPA_CSV; // filename
    public $sepaCsvData; // csv data as string
    public $sepaData; // the data in associative array form

    /* if file does not exist or is too old download it and write, else just read locally */
    function doGrab() {
        if (!file_exists($this->sepaFile) || time()-filemtime($this->sepaFile) > self::SEPA_DOWNLOAD_PERIOD) {
            $this->sepaCsvData = file_get_contents(self::SEPA_URL);
            $this->verifyCsvData() || die('CSV data did not verify');
            $newSepaFile = fopen($this->sepaFile, "w") or die("Unable to open file!");
            fwrite($newSepaFile, $this->sepaCsvData);
        } else {
            $this->sepaCsvData = file_get_contents($this->sepaFile);
            $this->verifyCsvData() || die('CSV data did not verify');
        }
    }

    /* basic CSV file verification */
    function verifyCsvData() {
        $csvData = explode("\n", $this->sepaCsvData);
        if (sizeof($csvData) < 20) {
            return false;
        }
        if (substr($csvData[0], 0, 40) != "SEPA_HYDROLOGY_OFFICE,STATION_NAME,LOCAT") {
            return false;
        }
        return true;
    }

    /* create the data array */
    function convertCsvToArray() {
        $csvData = explode("\n", $this->sepaCsvData);
        $this->sepaData = array();
        foreach($csvData as $csvLine) {
            if (substr($csvLine, 0, 40) == "SEPA_HYDROLOGY_OFFICE,STATION_NAME,LOCAT") {
                continue;
            }
            $csvSplit = explode(',', $csvLine);
            if (sizeof($csvSplit) < 10) {
                continue;
            }
            $id = $csvSplit[2];
            $this->sepaData[$id] = array();
            $this->sepaData[$id]['current_level'] = $csvSplit[6]; //GAUGE_DATUM
            $this->sepaData[$id]['reading_datetime'] = '213456'; // END_DATE
        }
    }
    
    /* return the data array */
    function sepaData() {
        $this->doGrab();
        $this->convertCsvToArray();
        return $this->sepaData;
    }
}