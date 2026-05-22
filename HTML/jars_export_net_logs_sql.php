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

// SET HTTP HEADERS

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="jars_net_logs.sql"');

$output = fopen('php://output', 'w');

fwrite($output, "DROP TABLE IF EXISTS `logs`;\n\n");

$sql_query = $pdo->query("SHOW CREATE TABLE `logs`");
$result = $sql_query->fetch(PDO::FETCH_NUM);
    
if ($result) {
  fwrite($output, $result[1] . ";\n\n");
}

$sql_query = $pdo->prepare("SELECT * FROM logs WHERE net_id = :netid ORDER BY date, sequence;");
$sql_query->bindParam(':netid', $_SESSION['net_id'], PDO::PARAM_INT);
$sql_query->execute();

while ($row = $sql_query->fetch(PDO::FETCH_ASSOC)) {
  $values = [];

  foreach ($row as $value) {
    if ($value === null) {
      $values[] = "NULL";
    } else {
      $values[] = $pdo->quote($value);
    }
  }

  $new_row = 'INSERT INTO `logs` VALUES (' . implode(", ", $values) . ");\n";
  fwrite($output, $new_row);
}

fclose($output);
