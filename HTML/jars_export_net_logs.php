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

REVISION 20260528.01

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

function export_adi($pdo) {
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="jars_net_logs.adi"');

  $output = fopen('php://output', 'w');

  $sql_query = $pdo->prepare("SELECT band, mode, submode, frequency FROM nets WHERE id = :netid;");
  $sql_query->bindParam(':netid', $_SESSION['net_id'], PDO::PARAM_INT);
  $sql_query->execute();

  $result = $sql_query->fetch(PDO::FETCH_ASSOC);
  $band = $result['band'];
  $mode = $result['mode'];
  $submode = $result['submode'];
  $frequency = $result['frequency'];

  // DUMP LOGS TABLE

  $sql_query = $pdo->prepare("SELECT logs.net_control, logs.callsign, logs.date, visitors.preferred_name, visitors.location FROM logs LEFT JOIN visitors ON logs.callsign = visitors.callsign WHERE net_id = :netid ORDER BY date, sequence;");
  $sql_query->bindParam(':netid', $_SESSION['net_id'], PDO::PARAM_INT);
  $sql_query->execute();

  $adi_band = '<band:' . strlen($band) . '>' . $band . ' ';
  $adi_freq = '<freq:' . strlen($frequency) . '>' . $frequency . ' ';
  $adi_mode = '<mode:' . strlen($mode) . '>' . $mode . ' ';
  $adi_submode = ($result['submode'] != '') ? '<submode:' . strlen($result['submode']). '>' . $result['submode'] . ' ' : '';

  // WRITE ADI HEADER

  fwrite($output, "ADIF Export\n");
  fwrite($output, "<adif_ver:5>3.1.7\n");
  fwrite($output, '<created_timestamp:15>' . date("Ymd His") . "\n");
  fwrite($output, "<programid:15>JARS Net Logger\n");
  fwrite($output, '<programversion:' . strlen(APP_VERSION) . '>' . APP_VERSION . "\n");
  fwrite($output, "<eoh>\n");

  while ($row = $sql_query->fetch(PDO::FETCH_ASSOC)) {
    $adi_call = '<call:' . strlen($row['callsign']). '>' . $row['callsign'] . ' ';
    $adi_name = ($row['preferred_name'] != '') ? '<name:' . strlen($row['preferred_name']). '>' . $row['preferred_name'] . ' ' : '';
    $qso_date = $row['date'];
    $qso_date = str_replace("-", "", $qso_date);
    $adi_qso_date = '<qso_date:' . strlen($qso_date). '>' . $qso_date . ' ';

    if ($row['location'] != '') {
      $location = explode(",", $row['location']);
      $adi_qth = '<qth:' . strlen(trim($location[0])). '>' . trim($location[0]) . ' ';

      if (count($location) > 1) $adi_state = (trim($location[1]) != '') ? '<state:' . strlen(trim($location[1])). '>' . trim($location[1]) . ' ' : '';
    } else {
      $adi_qth = '';
      $adi_state = '';
    }

    $adi_station_callsign = '<station_callsign:' . strlen($row['net_control']). '>' . $row['net_control'] . ' ';

    fwrite($output, $adi_call . $adi_qso_date . $adi_band . $adi_freq . $adi_mode . $adi_submode . $adi_name . $adi_qth . $adi_state . $adi_station_callsign . "<eor>\n");
  }

  fclose($output);
}

function export_csv($pdo) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="jars_net_logs.csv"');

  $output = fopen('php://output', 'w');
  $sql_query = $pdo->prepare("SELECT * FROM logs WHERE net_id = :netid ORDER BY date, sequence;");
  $sql_query->bindParam(':netid', $_SESSION['net_id'], PDO::PARAM_INT);
  $sql_query->execute();

  $row = $sql_query->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    fputcsv($output, array_keys($row));
    fputcsv($output, $row);
  }

  while ($row = $sql_query->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
  }

  fclose($output);
}

function export_sql($pdo) {
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="jars_net_logs.sql"');

  $output = fopen('php://output', 'w');

  fwrite($output, "DROP TABLE IF EXISTS `logs`;\n\n");

  $sql_query = $pdo->query("SHOW CREATE TABLE `logs`");
  $result = $sql_query->fetch(PDO::FETCH_NUM);

  if ($result) fwrite($output, $result[1] . ";\n\n");

  $sql_query = $pdo->prepare("SELECT * FROM logs WHERE net_id = :netid ORDER BY date, sequence;");
  $sql_query->bindParam(':netid', $_SESSION['net_id'], PDO::PARAM_INT);
  $sql_query->execute();

  while ($row = $sql_query->fetch(PDO::FETCH_ASSOC)) {
    $values = [];

    foreach ($row as $value) {
      $values[] =  ($value === null) ? "NULL" : $pdo->quote($value);
    }

    $new_row = 'INSERT INTO `logs` VALUES (' . implode(", ", $values) . ");\n";
    fwrite($output, $new_row);
  }

  fclose($output);
}

if (isset($_GET['format'])) {
  switch ($_GET['format']) {
    case "adi":
      export_adi($pdo);
      break;
    case "csv":
      export_csv($pdo);
      break;
    case "sql":
      export_sql($pdo);
      break;
    default:
      exit();
  }
}
