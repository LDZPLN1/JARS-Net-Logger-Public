<?php
session_start();

/*

Copyright (c) 2026 Douglas Graham
All rights reserved.

This file is part of the JARS Net Logger

JARS Net Logger is free software: you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the Free
Software Foundation, either version 3 of the License, or (at your option)
any later version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see <https://www.gnu.org/licenses/>.

*/

// CHECK IF USER SHOULD BE HERE, IF NOT DESTROY SESSION AND FORCE THEM TO THE LOGIN PAGE

if (!isset($_SESSION['user_id']) || !isset($_SESSION['guest']) || !isset($_SESSION['admin'])) {
  header("Location: jars_logout.php");
  exit();
}

if ($_SESSION['admin'] != true) {
  header("Location: jars_logout.php");
  exit();
}

require_once('config.php');

// CREATE DATABASE CONNECTION

try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, base64_decode(DB_PASSWORD));
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  exit;
}

// ADI BASE VARIABLES - CHANGE TO SUIT YOUR REPEATER

$band = '2m';
$country = 'UNITED STATES OF AMERICA';
$mode = 'FM';
$repeater_frequency = '147.270';

// DUMP LOGS TABLE

$sql_query = $pdo->prepare("SELECT logs.net_control, logs.callsign, logs.date, visitors.preferred_name, visitors.location FROM logs LEFT JOIN visitors ON logs.callsign = visitors.callsign WHERE net_id = :netid ORDER BY date, sequence;");
$sql_query->bindParam(':netid', $_SESSION['net_id'], PDO::PARAM_INT);
$sql_query->execute();

// SET HTTP HEADERS

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="jars_net_logs.adi"');

$output = fopen('php://output', 'w');

$adi_band = '<band:' . strlen($band) . '>' . $band;
$adi_country = '<country:' . strlen($country) . '>' . $country;
$adi_freq = '<freq:' . strlen($repeater_frequency) . '>' . $repeater_frequency;
$adi_mode = '<mode:' . strlen($mode) . '>' . $mode;

// WRITE ADI HEADER

fwrite($output, "ADIF Export\n");
fwrite($output, "<adif_ver:5>3.1.7\n");
fwrite($output, '<created_timestamp:15>' . date("Ymd His") . "\n");
fwrite($output, "<programid:15>JARS Net Logger\n");
fwrite($output, '<programversion:' . strlen(APP_VERSION) . '>' . APP_VERSION . "\n");
fwrite($output, "<eoh>\n");

while ($row = $sql_query->fetch(PDO::FETCH_ASSOC)) {
  $adi_call = '<call:' . strlen($row['callsign']). '>' . $row['callsign'];
  $adi_name = '<name:' . strlen($row['preferred_name']). '>' . $row['preferred_name'];
  $qso_date = $row['date'];
  $qso_date = str_replace("-", "", $qso_date);
  $adi_qso_date = '<qso_date:' . strlen($qso_date). '>' . $qso_date;
  $location = explode(",", $row['location']);
  $adi_qth = '<qth:' . strlen(trim($location[0])). '>' . trim($location[0]);
  $adi_state = '<state:' . strlen(trim($location[1])). '>' . trim($location[1]);
  $adi_station_callsign = '<station_callsign:' . strlen($row['net_control']). '>' . $row['net_control'];

  fwrite($output, $adi_call . ' ' . $adi_qso_date . ' ' . $adi_band . ' ' . $adi_freq . ' ' . $adi_mode . ' ' . $adi_name . ' ' . $adi_qth . ' ' . $adi_state . ' ' . $adi_country . ' ' . $adi_station_callsign . " <eor>\n");
}

fclose($output);
