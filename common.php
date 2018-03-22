<?php

function heading() {
?>
<!DOCTYPE html>
<head>
    <link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="description" content="Water levels on popular kayaking rivers around Scotland" />
    <meta name="og:description" content="Water levels on popular kayaking rivers around Scotland" />
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript" src="http://canoescotland.org/sites/all/themes/basestation_open/js/riverLevels.js"></script>
<script type="text/javascript" src="http://canoescotland.org/sites/all/themes/basestation_open/js/utilLib.js?"></script>
<title>SCA Where's the Water</title>
<style>
body {font-family: 'Ubuntu', sans-serif;}
</style>
</head>

<body style="background: white" onload="sortTable(0)">
<p><a href="http://www.andyjacksonfund.org.uk"><img src="/wheres-the-water/andy-jackson-fund.png" width="350" /></a>
<a href="http://canoescotland.org"><img src="/wheres-the-water/scottish-canoe-association-social.jpg" width="350" /></a></p>

<h1>SCA Where&#039;s The Water?</h1>

<h2>Scottish River Levels</h2>
<?php
}

function footer() {
?>
<p>SCA Where's the Water uses <a href="
<a href="http://apps.sepa.org.uk/waterlevels/">water level data from SEPA</a>.</p>

<p>Code written and maintained by <a href="http://www.edinburghlinux.co.uk">Jonathan Riddell</a>. <a href="https://github.com/jriddell/wheres-the-water">Code on GitHub</a>.  Please file <a href="https://github.com/jriddell/wheres-the-water/issues">bug reports and feature requests on GitHub</a>.</p>

<p><a href="http://goo.gl/forms/nnEOgVkw8ebhygW52">Help Us Calibrate: River Level Report Form</a>.</p>
<p><a href="http://goo.gl/forms/YQ3xZTi30vrtFYpo1">River Gauge Request Form</a></a>.</p>
<p><a href="/wtw">Simple List View</a></p>
<p><a href="/wtw/map">Simple Map View</a></p>
<p><a href="http://canoescotland.org/where-go/wheres-water">Full Map View</a></p>
</body>
</html>
<?php
}
