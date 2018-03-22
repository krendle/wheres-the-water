# SCA Where's the Water website

* Copyright 2010 Scottish Canoe Association
* Copyright 2017 Jonathan Riddell
* May be copied under the GNU GPL version 3 or later only

wheres-the-water.php is body which can be viewed or used at http://canoescotland.org/where-go/wheres-water

sidebar.php is drupal is body of side block http://canoescotland.org/admin/structure/block/manage/block/18/
old-html/wheres-water-drupal-content.html is drupal body of old site

Old Version
================
The old version was written many years ago.  It had a Java backend
which ran on cron to download SEPA data from private database
connection and put it into SCA website Drupal database.  Mixing the
WtW data into multiple tables in the Drupal database is unnecessary
and complicates things.  The Java source code has been lost so the
site needs to be rewritten.  These days SEPA provides public CSV files
which list the gauges and ones for each river with current readings,
so we just use these.

tests
=====
./phpunit GrabSepaGaugesTest.php
./phpunit GrabSepaRiversTest.php
./phpunit GrabSepaRiverTest.php
./phpunit ScratchTest.php
./phpunit RiverSectionsTest.php   (note this needs a database connection)

database connection:
only used for data import from old drupal/java system which stored the river data in Drupal's mysql database
 config/database.php:
 <?php
 $servername = "localhost";
 $username = "scauser";
 $password = "xxx";
 $dbname = "scadb";

code
====
lib/Scratch.php  class to play around and test with
lib/GrabSepaGauges  class to download SEPA gauge data and return it as array, sepaData() is the main method to use
lib/RiverSections.php Class to deal with the river sections data, edit the data and export it as Javascript (most of the action happens here)
lib/GrabSepaRivers  class to provide and save/load json with current river readings, when it needs to update data it uses below class
lib/GrabSepaRiver  class to download current reading data for a river

admin/river-section.php  admin UI to set, add and delete river sections

TODO
====
- Download river data as an external process (takes to long to do it in sync with web page load)
- Deploy

PHP nuttyness
=============
We have to use PHP as it integrates with Drupal.

SCA server uses PHP 5, this may cause problems.

json_decode($data, true)  <-- use true here else it'll import hashes as objects not as associative arrays

Config
======
config.php should have the root file location set:
 <?php
 define("ROOT",     "/var/www/canoescotland.org/wheres-the-water");
 
 Setup
 ======
 Edit config.php to have the correct server path eg. "/var/www/wheres-the-water"
 
 Make sure your webserver has permission to write to the data folder
 Debian: sudo chown -R www-data.www-data data/
 
 Run the admin/download-river-readings.php script once
 'Unable to open file' will be displayed if the above steps have not been performed correctly
 
 
