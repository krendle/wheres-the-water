<?php
/* Copyright 2018 Jonathan Riddell <jr@jriddell.org>
   May be copied under the GNU GPL version 3 (or later) only
*/

/*
 Functions to read and write a river reading history
 Uses file to data/history-1234.json of format associative array of timestamp: reading
 {
    "2147483647": "1.24",
    "2147483123": "2.12"
 }
*/

class SepaRiverReadingHistory {
    const DATADIR = 'data';
    public $gauge_id;
    public $filename;
    
    function __construct($gauge_id) {
        $this->dataDir = ROOT . '/' . self::DATADIR;
        $this->gauge_id = $gauge_id;
        $this->filename = $this->dataDir . '/history-' . $this->gauge_id . '.json';
    }

    public function newReading($timeStamp, $waterLevel) {
        if (file_exists($this->filename)) {
            $json = file_get_contents($this->filename);
            $riversReadingsHistory = json_decode($json, true);
        } else {
            $riversReadingsHistory = array();
        }
        $riversReadingsHistory[$timeStamp] = $waterLevel;
        $fp = fopen($this->filename, 'w');
        fwrite($fp, json_encode($riversReadingsHistory, JSON_PRETTY_PRINT));
        fclose($fp);
    }

}
