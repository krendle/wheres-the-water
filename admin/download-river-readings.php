<?php
require_once('../lib/RiverSections.php');
require_once('../lib/GrabSepaRivers.php');
require_once('../config.php');

?>

<html>
<head><title>Download SEPA River Readings</title></head>
<body>

<h1>Download SEPA River Readings</h1>
<p><a href="index.html">&#8592; back to admin index</a></p>

<?php
if (array_key_exists('download', $_GET)) {
    print "\n<h1>Downloading SEPA River Readings...</h1>\n";
    $riverSections = new RiverSections();
    $riverSections->readFromJson();

    $sepaRivers = new GrabSepaRivers();
    $sepaRivers->doGrabSepaRiversReadings($riverSections->riverSectionsData);
    print "\n<h1>Done</h1>\n";
} else if (array_key_exists('force', $_GET)) {
    print "\n<h1>Force Downloading SEPA River Readings...</h1>\n";
    $riverSections = new RiverSections();
    $riverSections->readFromJson();

    $sepaRivers = new GrabSepaRivers();
    $sepaRivers->doGrabSepaRiversReadings($riverSections->riverSectionsData, true);
    print "\n<h1>Done</h1>\n";
}

?>

<form action="download-river-readings.php" method="get">
<input type="submit" name="download" value="Download River Readings" />
<input type="submit" name="force" value="Force Download River Readings" />
</form>

</body>
</html>

