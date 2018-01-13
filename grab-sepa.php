<?php

/*
PHP which checks if .json is older than 1 min
if so downloads SEPA CSV and writes
reads CSV and converts to json
writes json
*/
define("SEPA_CSV", "SEPA_River_Levels_Web.csv");
define("datadir", "data");
define("sepa_download_period", 60 * 10); // how often to download SEPA file in seconds

if (time()-filemtime(datadir + SEPA_CSV) > sepa_download_period) {
  // file older than 2 hours
  //grab file
  //check it's valid
  //parse to variable
  //write
} else {
  // read value
}
