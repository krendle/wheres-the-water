<?php

require_once('../lib/SepaRiverReadingHistory.php');
require_once('../config.php');

use PHPUnit\Framework\TestCase;

final class SepaRiverReadingHistoryTest extends TestCase
{

    // This test needs a way to override the input data else the values need changed each time
    public function testNewReading() {
        @unlink('data/history-1234.json'); // funny syntax to supress error if it does not exist
        $mySepaRiverReadingHistory = new SepaRiverReadingHistory('1234');
        $mySepaRiverReadingHistory->dataDir = 'data';
        $mySepaRiverReadingHistory->filename = 'data/history-1234.json';
        $mySepaRiverReadingHistory->newReading('2147483647', '1.24');
        $this->assertFileExists('data/history-1234.json');
        $this->assertJsonFileEqualsJsonFile('data/SepaRiverReadingHistoryTest/testNewReading.json', 'data/history-1234.json');
        $mySepaRiverReadingHistory->newReading('2147483123', '2.12');
        $this->assertFileExists('data/history-1234.json');
        $this->assertJsonFileEqualsJsonFile('data/SepaRiverReadingHistoryTest/testNewReading2.json', 'data/history-1234.json');
        @unlink('data/history-1234.json'); // funny syntax to supress error if it does not exist
    }
}
