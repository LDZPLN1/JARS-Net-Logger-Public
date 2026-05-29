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

API REVISION 20260528.01

API LIST:

/lookup             api_lookup
/viewer             api_viewer
/charts             api_charts
/counts             api_counts
/updateuser         api_updateuser
/uploadcheckins     api_uploadcheckins

*/

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$api = basename(parse_url($uri, PHP_URL_PATH));

if (!isset($_SESSION['user_id']) && $api != 'getlivelog' && $api != 'getliveloglist') {
    echo json_encode(['status' => 'AUTHORIZAION_FAILED']);
    exit();
}

require_once '../config.php';

// CREATE DATABASE CONNECTION

try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, base64_decode(DB_PASSWORD));
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo json_encode(['status' => 'DATABASE_CONNECT_ERROR']);
  exit();
}

header('Content-Type: application/json; charset=utf-8');
$failed = false;

switch ($method) {
  case 'GET':
    switch($api) {
      case 'lookup':
        api_lookup($pdo);
        break;
      case 'viewer':
        api_viewer($pdo);
        break;
      case 'charts':
        api_charts($pdo);
        break;
      case 'counts':
        api_counts($pdo);
        break;
      default:
        $failed = true;
    }
    break;

  case 'POST':
    switch($api) {
      case 'updateuser':
        $json_raw = file_get_contents('php://input');

        if (!json_validate($json_raw)) {
          $failed = true;
        } else {
          $json = json_decode($json_raw, true);
          api_updateuser($pdo, $json);
        }
        break;
      case 'uploadcheckins':
        $json_raw = file_get_contents('php://input');

        if (!json_validate($json_raw)) {
          $failed = true;
        } else {
          $json = json_decode($json_raw, true);
          api_uploadcheckins($pdo, $json);
        }
        break;
      default:
        $failed = true;
    }
    break;

  default:
    $failed = true;
}

if ($failed) {
  echo json_encode(['status' => 'BAD_REQUEST']);
  exit();
}

/*
METHOD: GET
INPUT PARAMETERS:       VISITOR CALLSIGN, NET CONTROL CALLSIGN
RETURN JSON:            STATUS, CALLSIGN, PREFERRED NAME, LOCATION, NOTES, LID, TOTAL CHECK-IN COUNT, NET CONTROL CHECK-IN COUNT
*/

function api_lookup($pdo) {
  $callsign = $_GET['callsign'] ?? '';
  $net_control = $_GET['net_control'] ?? '';
  $net_id = $_GET['net_id'] ?? '';

  if ($callsign === '' || $net_id === '') {
    echo json_encode(['status' => 'BAD_REQUEST']);
    exit();
  }

  if (strlen($callsign) < 3) {
    echo json_encode(['status' => 'SHORT_SEARCH']);
    exit();
  }

  if ($callsign[0] == '-') {
    $lookup = '%' . substr($callsign, 1);
    $sql_query = $pdo->prepare("SELECT * FROM visitors WHERE callsign LIKE :callsign ORDER BY callsign;");
    $sql_query->bindParam(':callsign', $lookup, PDO::PARAM_STR);
  } else {
    $sql_query = $pdo->prepare("SELECT * FROM visitors WHERE callsign = :callsign;");
    $sql_query->bindParam(':callsign', $callsign, PDO::PARAM_STR);
  }

  $sql_query->execute();
  $result = $sql_query->fetchAll(PDO::FETCH_ASSOC);

  if (empty($result)) {
    echo json_encode(['status' => 'NOT_FOUND']);
    exit();
  } elseif (count($result) == 1) {
    $db_callsign = $result[0]['callsign'];

    $sql_query = $pdo->prepare("SELECT SUM(subq.days_visited) as count FROM (SELECT COUNT(DISTINCT callsign) AS days_visited FROM logs WHERE net_id = :netid AND callsign = :callsign GROUP BY date) as subq;");
    $sql_query->bindParam(':netid', $net_id, PDO::PARAM_INT);
    $sql_query->bindParam(':callsign', $db_callsign, PDO::PARAM_STR);
    $sql_query->execute();
    $cresult = $sql_query->fetch(PDO::FETCH_ASSOC);

    $ci_count = ($cresult['count'] === null) ? 0 : $cresult['count'];

    $sql_query = $pdo->prepare("SELECT SUM(subq.days_visited) as count FROM (SELECT COUNT(DISTINCT callsign) AS days_visited FROM logs WHERE net_id = :netid AND callsign = :callsign AND net_control = :netcontrol GROUP BY date) as subq;");
    $sql_query->bindParam(':netid', $net_id, PDO::PARAM_INT);
    $sql_query->bindParam(':callsign', $db_callsign, PDO::PARAM_STR);
    $sql_query->bindParam(':netcontrol', $net_control, PDO::PARAM_STR);
    $sql_query->execute();
    $cresult = $sql_query->fetch(PDO::FETCH_ASSOC);

    $ci_net_count = ($cresult['count'] === null) ? 0 : $cresult['count'];

    echo json_encode(['status' => 'SUCCESS', 'callsign' => $db_callsign, 'preferred_name' => $result[0]['preferred_name'], 'location' => $result[0]['location'], 'notes' => $result[0]['notes'], 'lid' => $result[0]['lid'], 'ci_count' => $ci_count, 'ci_net_count' => $ci_net_count]);
  } else {
      $visitors = [];

      foreach ($result as $visitor) {
        $visitors[] = [
          'callsign' => $visitor['callsign'],
          'preferred_name' => $visitor['preferred_name'],
          'location' => $visitor['location']
        ];
      }
    echo json_encode(['status' => 'MULTIPLE_RECORDS_FOUND', 'visitors' => $visitors]);
  }
}

/*
METHOD: GET
INPUT PARAMETERS:       LOG DATE
RETURN JSON:            META: NET CONTROL CALLSIGN
                        VISITORS: CALLSIGN, ANNOUNCEMENT, MOBILE, PORTABLE, SHORT TIME, ECHOLINK, IN/OUT, COUPIN, PREFERRED NAME, LOCATION, NOTES
                        STATUS
*/

function api_viewer($pdo) {
  $log_date = $_GET['log_date'] ?? '';
  $net_id = $_GET['net_id'] ?? '';

  if ($log_date === '' || $net_id === '') {
    echo json_encode(['status' => 'BAD_REQUEST']);
    exit();
  }

  $sql_query = $pdo->prepare("SELECT logs.*, visitors.callsign as vis_callsign, visitors.preferred_name, visitors.location, visitors.notes FROM logs LEFT JOIN visitors ON logs.callsign = visitors.callsign WHERE net_id = :netid AND date = :logdate ORDER BY logs.sequence;");
  $sql_query->bindParam(':netid', $net_id, PDO::PARAM_INT);
  $sql_query->bindParam(':logdate', $log_date, PDO::PARAM_STR);
  $sql_query->execute();
  $result = $sql_query->fetchAll(PDO::FETCH_ASSOC);

  if (empty($result)) {
    echo json_encode(['status' => 'NOT_FOUND']);
    exit();
  }

  $meta = ['net_control' => $result[0]['net_control']];
  $visitors = [];

  foreach ($result as $log_entry) {
    if ($log_entry['vis_callsign']) {
      $preferred_name = $log_entry['preferred_name'];
      $location = $log_entry['location'];
      $notes = $log_entry['notes'];
    } else {
      $preferred_name = 'N/A';
      $location = 'N/A';
      $notes = 'PLEASE ADD VISITOR INFO';
    }

    $visitors[] = [
      'callsign' => $log_entry['callsign'],
      'announcement' => $log_entry['announcement'],
      'mobile' => $log_entry['mobile'],
      'portable' => $log_entry['portable'],
      'echolink' => $log_entry['echolink'],
      'short_time' => $log_entry['short_time'],
      'in_out' => $log_entry['in_out'],
      'coupin' => $log_entry['coupin'],
      'preferred_name' => $preferred_name,
      'location' => $location,
      'notes' => $notes
    ];
  }

  echo json_encode(['status' => 'SUCCESS', 'meta' => $meta, 'visitors' => $visitors]);
}

/*
METHOD: GET
INPUT PARAMETERS:       NET ID, CHART TYPE, CHART PERIOD
RETURN JSON:            STATUS
                        DATA: KEY, VALUE
*/

function api_charts($pdo) {
  $net_id = $_GET['net_id'] ?? '';
  $type = $_GET['chart_type'] ?? '';
  $period = $_GET['chart_period'] ?? '';

  $type_dict = ['monthly', 'netop', 'days', 'top'];
  $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

  if ($net_id === '' || $type === '' || $period ==='' || !in_array($type, $type_dict)) {
    echo json_encode(['status' => 'BAD_REQUEST']);
    exit();
  }

  $qtype = 0;
  $cperiod = 0;

  if (ctype_digit($period)) {
    $cperiod = (int)$period;

    if ($type != 'monthly') {
      $curr_time = date('H:i');

      if ($curr_time >= '20:30' && $curr_time <= '23:59') $cperiod--;
    }
    $qtype = 1;
  } elseif ($period == 'mtd') {
    $qtype = 2;
  } elseif ($period == 'ytd') {
    $qtype = 3;
  } else {
    echo json_encode(['status' => 'BAD_REQUEST']);
    exit();
  }

  switch ($type) {
    case "days":
      $sfield = 'dow';

      switch ($qtype) {
        case 1:
          $sql_query = $pdo->prepare('SELECT subq.dow, SUM(subq.daily_visitors) AS visitor_count FROM (SELECT date, dow, COUNT(DISTINCT callsign) AS daily_visitors FROM logs WHERE net_id = :netid AND date >= DATE_SUB(CURDATE(), INTERVAL :period DAY) GROUP BY date, dow) AS subq GROUP BY subq.dow ORDER BY subq.dow ASC;');
          $sql_query->bindParam(':period', $cperiod, PDO::PARAM_STR);
          break;
        case 2:
          $sql_query = $pdo->prepare('SELECT subq.dow, SUM(subq.daily_visitors) AS visitor_count FROM (SELECT date, dow, COUNT(DISTINCT callsign) AS daily_visitors FROM logs WHERE net_id = :netid AND date >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND date < DATE_ADD(CURDATE(), INTERVAL 1 DAY) GROUP BY date, dow) AS subq GROUP BY subq.dow ORDER BY subq.dow ASC;');
          break;
        case 3:
          $sql_query = $pdo->prepare('SELECT subq.dow, SUM(subq.daily_visitors) AS visitor_count FROM (SELECT date, dow, COUNT(DISTINCT callsign) AS daily_visitors FROM logs WHERE net_id = :netid AND date >= DATE_FORMAT(CURDATE(), "%Y-01-01") AND date < DATE_FORMAT(CURDATE(), "%Y-01-01") + INTERVAL 1 YEAR GROUP BY date, dow) AS subq GROUP BY subq.dow ORDER BY subq.dow ASC;');
          break;
      }
      break;

    case "monthly":
      switch ($qtype) {
        case 1:
          $sql_query = $pdo->prepare('SELECT DATE_FORMAT(daily_summary, "%Y-%m") AS month, SUM(daily_count) AS visitor_count, COUNT(daily_summary) as nets_called FROM (SELECT DATE(date) AS daily_summary, COUNT(DISTINCT callsign) AS daily_count FROM logs WHERE net_id = :netid AND date >= DATE_SUB(NOW(), INTERVAL :period MONTH) GROUP BY DATE(date)) daily_counts GROUP BY month ORDER BY month;');
          $sql_query->bindParam(':period', $cperiod, PDO::PARAM_STR);
          break;
        case 2:
          echo json_encode(['status' => 'BAD_REQUEST']);
          exit();
          break;
        case 3:
          echo json_encode(['status' => 'BAD_REQUEST']);
          exit();
          break;
      }
      break;

    case "netop":
      $sfield = 'net_control';

      switch ($qtype) {
        case 1:
          $sql_query = $pdo->prepare('SELECT subq.net_control, SUM(subq.daily_visitors) AS visitor_count, COUNT(subq.net_control) as nets_called FROM (SELECT date, net_control, COUNT(DISTINCT callsign) AS daily_visitors FROM logs WHERE net_id = :netid AND date >= DATE_SUB(CURDATE(), INTERVAL :period DAY) GROUP BY date, net_control) AS subq GROUP BY subq.net_control ORDER BY subq.net_control ASC;');
          $sql_query->bindParam(':period', $cperiod, PDO::PARAM_STR);
          break;
        case 2:
          $sql_query = $pdo->prepare('SELECT subq.net_control, SUM(subq.daily_visitors) AS visitor_count, COUNT(subq.net_control) as nets_called FROM (SELECT date, net_control, COUNT(DISTINCT callsign) AS daily_visitors FROM logs WHERE net_id = :netid AND date >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND date < DATE_ADD(CURDATE(), INTERVAL 1 DAY) GROUP BY date, net_control) AS subq GROUP BY subq.net_control ORDER BY subq.net_control ASC;');
          break;
        case 3:
          $sql_query = $pdo->prepare('SELECT subq.net_control, SUM(subq.daily_visitors) AS visitor_count, COUNT(subq.net_control) as nets_called FROM (SELECT date, net_control, COUNT(DISTINCT callsign) AS daily_visitors FROM logs WHERE net_id = :netid AND date >= DATE_FORMAT(CURDATE(), "%Y-01-01") AND date < DATE_FORMAT(CURDATE(), "%Y-01-01") + INTERVAL 1 YEAR GROUP BY date, net_control) AS subq GROUP BY subq.net_control ORDER BY subq.net_control ASC;');
          break;
      }
      break;

    case "top":
      $sfield = 'callsign';

      switch ($qtype) {
        case 1:
          $sql_query = $pdo->prepare('SELECT subq.callsign, SUM(subq.daily_visitors) AS visitor_count FROM (SELECT date, callsign, COUNT(DISTINCT callsign) AS daily_visitors FROM logs WHERE net_id = :netid AND date >= DATE_SUB(CURDATE(), INTERVAL :period DAY) GROUP BY date, callsign) AS subq GROUP BY subq.callsign ORDER BY visitor_count DESC,subq.callsign ASC LIMIT 10;');
          $sql_query->bindParam(':period', $cperiod, PDO::PARAM_STR);
          break;
        case 2:
          $sql_query = $pdo->prepare('SELECT subq.callsign, SUM(subq.daily_visitors) AS visitor_count FROM (SELECT date, callsign, COUNT(DISTINCT callsign) AS daily_visitors FROM logs WHERE net_id = :netid AND date >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND date < DATE_ADD(CURDATE(), INTERVAL 1 DAY) GROUP BY date, callsign) AS subq GROUP BY subq.callsign ORDER BY visitor_count DESC,subq.callsign ASC LIMIT 10;');
          break;
        case 3:
          $sql_query = $pdo->prepare('SELECT subq.callsign, SUM(subq.daily_visitors) AS visitor_count FROM (SELECT date, callsign, COUNT(DISTINCT callsign) AS daily_visitors FROM logs WHERE net_id = :netid AND date >= DATE_FORMAT(CURDATE(), "%Y-01-01") AND date < DATE_FORMAT(CURDATE(), "%Y-01-01") + INTERVAL 1 YEAR GROUP BY date, callsign) AS subq GROUP BY subq.callsign ORDER BY visitor_count DESC,subq.callsign ASC LIMIT 10;');
          break;
      }
      break;
  }

  $sql_query->bindParam(':netid', $net_id, PDO::PARAM_INT);
  $sql_query->execute();
  $result = $sql_query->fetchAll(PDO::FETCH_ASSOC);

  $data = [];

  foreach ($result as $chart_data) {
    switch ($type) {
      case "days":
        $data[] = [
          'key' => $days[$chart_data['dow']],
          'count' => $chart_data['visitor_count']
        ];
        break;
      case "monthly":
        $data[] = [
          'key' => $chart_data['month'],
          'count' => $chart_data['visitor_count'],
          'nets' => $chart_data['nets_called']
        ];
        break;
      case "netop":
        $data[] = [
          'key' => $chart_data[$sfield],
          'count' => $chart_data['visitor_count'],
          'nets' => $chart_data['nets_called']
        ];
        break;
      case "top":
        $data[] = [
          'key' => $chart_data[$sfield],
          'count' => $chart_data['visitor_count']
        ];
        break;
    }
  }

  echo json_encode(['status' => 'SUCCESS', 'data' => $data]);
}

/*
METHOD: GET
INPUT PARAMETERS:       NET ID, COUNT TYPE, COUNT PERIOD
RETURN JSON:            STATUS, COUNT
*/

function api_counts($pdo) {
  $net_id = $_GET['net_id'] ?? '';
  $type = $_GET['count_type'] ?? '';
  $period = $_GET['count_period'] ?? '';

  $type_dict = ['checkins'];

  if ($net_id === '' || $type === '' || $period ==='' || !in_array($type, $type_dict)) {
    echo json_encode(['status' => 'BAD_REQUEST']);
    exit();
  }

  $qtype = 0;
  $cperiod = 0;

  if (ctype_digit($period)) {
    $cperiod = (int)$period;

    if ($type != 'monthly') {
      $curr_time = date('H:i');

      if ($curr_time >= '20:30' && $curr_time <= '23:59') $cperiod--;
    }

    $qtype = 1;
  } elseif ($period == 'mtd') {
    $qtype = 2;
  } elseif ($period == 'ytd') {
    $qtype = 3;
  } else {
    echo json_encode(['status' => 'BAD_REQUEST']);
    exit();
  }

  switch ($qtype) {
    case 1:
      $sql_query = $pdo->prepare('SELECT SUM(subq.daily_count) AS total_checkins FROM (SELECT date, COUNT(DISTINCT callsign) AS daily_count FROM logs WHERE net_id = :netid AND date >= DATE_SUB(CURDATE(), INTERVAL :period DAY) GROUP BY date) AS subq;');
      $sql_query->bindParam(':period', $cperiod, PDO::PARAM_STR);
      break;

    case 2:
      $sql_query = $pdo->prepare('SELECT SUM(subq.daily_count) AS total_checkins FROM (SELECT date, COUNT(DISTINCT callsign) AS daily_count FROM logs WHERE net_id = :netid AND date >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND date < DATE_ADD(CURDATE(), INTERVAL 1 DAY) GROUP BY date) AS subq;');
      break;

    case 3:
      $sql_query = $pdo->prepare('SELECT SUM(subq.daily_count) AS total_checkins FROM (SELECT date, COUNT(DISTINCT callsign) AS daily_count FROM logs WHERE net_id = :netid AND date >= DATE_FORMAT(CURDATE(), "%Y-01-01") AND date < DATE_FORMAT(CURDATE(), "%Y-01-01") + INTERVAL 1 YEAR GROUP BY date) AS subq;');
      break;
  }

  $sql_query->bindParam(':netid', $net_id, PDO::PARAM_INT);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  $count = ($result['total_checkins'] == null) ? 0 : $result['total_checkins'];

  echo json_encode(['status' => 'SUCCESS', 'count' => $count]);
}

/*
METHOD: POST
INPUT JSON:             VISITOR CALLSIGN, PREFERRED NAME, LOCATION, NOTES, LID
RETURN JSON:            STATUS
*/

function api_updateuser($pdo, $json) {
  $callsign = $json['callsign'] ?? '';
  $preferred_name = $json['preferred_name'] ?? '';
  $location = $json['location'] ?? '';
  $notes = $json['notes'] ?? '';
  $lid = $json['lid'] ?? '';

  if ($callsign === '' || $preferred_name === '') {
    echo json_encode(['status' => 'BAD_REQUEST']);
    exit();
  }

  if (!is_bool($lid)) {
    echo json_encode(['status' => 'BAD_REQUEST']);
    exit();
  }

  $sql_query = $pdo->prepare("SELECT * FROM visitors WHERE callsign = :callsign;");
  $sql_query->bindParam(':callsign', $callsign, PDO::PARAM_STR);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  if (empty($result)) {
    $sql_query = $pdo->prepare("INSERT INTO visitors VALUES (:callsign, :preferredname, :location, :notes, :lid);");
  } else {
    $sql_query = $pdo->prepare("UPDATE visitors SET preferred_name = :preferredname, location = :location, notes = :notes, lid = :lid WHERE callsign = :callsign;");
  }

  $sql_query->bindParam(':callsign', $callsign, PDO::PARAM_STR);
  $sql_query->bindParam(':preferredname', $preferred_name, PDO::PARAM_STR);
  $sql_query->bindParam(':location', $location, PDO::PARAM_STR);
  $sql_query->bindParam(':notes', $notes, PDO::PARAM_STR);
  $sql_query->bindParam(':lid', $lid, PDO::PARAM_BOOL);
  $sql_query->execute();

  $source = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
  $log = date("Y-m-d H:i:s") . "\t" . $source . "\tVISITOR " . $callsign . " UPDATED\t" . $_SESSION['user_id'] . "\n";
  error_log($log, 3, "/var/log/jars-net-logger.log");

  echo json_encode(['status' => 'SUCCESS']);
}

/*
METHOD: POST
INPUT JSON:             META: NET CONTROL CALLSIGN, LOG DATE, DAY OF WEEK, APPEND
                        VISITORS: CALLSIGN, ANNOUNCEMENT, MOBILE, PORTABLE, SHORT TIME, ECHOLINK, IN/OUT, COUPIN
RETURN JSON:            STATUS, COUNT
*/

function api_uploadcheckins($pdo, $json) {
  $net_id = $json['meta']['net_id'] ?? '';
  $net_control = $json['meta']['net_control'] ?? '';
  $log_date = $json['meta']['log_date'] ?? '';
  $day_of_week = $json['meta']['day_of_week'] ?? '';
  $append = $json['meta']['append'] ?? '';

  if (!is_bool($append) || $net_id ==='' || $net_control === '' || $log_date === '' || $day_of_week === '') {
    echo json_encode(['status' => 'BAD_REQUEST']);
    exit();
  }

  if ($net_control == 'KJ4QNW' && $append) {
    echo json_encode(['status' => 'REJECTED']);
    exit();
  }

  $failed = false;

  if (ctype_digit($day_of_week)) {
    if ($day_of_week < 0 || $day_of_week > 6) $faile = true;
  } else {
    $faile = true;
  }

  if ($failed) {
    echo json_encode(['status' => 'BAD_REQUEST']);
    exit();
  }

  $sql_query = $pdo->prepare("SELECT net_name FROM nets WHERE id = :netid;");
  $sql_query->bindParam(':netid', $net_id, PDO::PARAM_INT);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  $net_name = $result['net_name'];

  $sql_query = $pdo->prepare("SELECT MAX(sequence) FROM logs WHERE net_id = :netid AND date = :logdate;");
  $sql_query->bindParam(':netid', $net_id, PDO::PARAM_INT);
  $sql_query->bindParam(':logdate', $log_date, PDO::PARAM_STR);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  if ($result['MAX(sequence)'] === null) {
    $counter = 0;
  } else if ($append) {
    $counter = $result['MAX(sequence)'];
  } else {
    echo json_encode(['status' => 'LOG_EXISTS']);
    exit();
  }

  $failed =false;

  foreach ($json['visitors'] as $visitor) {
    if ($visitor['callsign'] === '') $failed = true;
    if (!is_bool($visitor['announcement'])) $failed = true;
    if (!is_bool($visitor['mobile'])) $failed = true;
    if (!is_bool($visitor['portable'])) $failed = true;
    if (!is_bool($visitor['short_time'])) $failed = true;
    if (!is_bool($visitor['echolink'])) $failed = true;
    if (!is_bool($visitor['in_out'])) $failed = true;
    if (!is_bool($visitor['coupin'])) $failed = true;
  }

  if ($failed) {
    echo json_encode(['status' => 'BAD_REQUEST']);
    exit();
  }

  $ncount = 0;

  foreach ($json['visitors'] as $visitor) {
    if ($visitor['callsign'] != $net_control) {
      $counter++;

      $sql_query = $pdo->prepare("INSERT INTO logs VALUES (NULL, :netid, :netcontrol, :counter, :visitor, :announcement, :mobile, :portable, :echolink, :shorttime, :inout, :coupin, :logdate, :dow);");
      $sql_query->bindParam(':netid', $net_id, PDO::PARAM_INT);
      $sql_query->bindParam(':netcontrol', $net_control, PDO::PARAM_STR);
      $sql_query->bindParam(':counter', $counter, PDO::PARAM_INT);
      $sql_query->bindParam(':visitor', $visitor['callsign'], PDO::PARAM_STR);
      $sql_query->bindParam(':announcement', $visitor['announcement'], PDO::PARAM_BOOL);
      $sql_query->bindParam(':mobile', $visitor['mobile'], PDO::PARAM_BOOL);
      $sql_query->bindParam(':portable', $visitor['portable'], PDO::PARAM_BOOL);
      $sql_query->bindParam(':echolink', $visitor['echolink'], PDO::PARAM_BOOL);
      $sql_query->bindParam(':shorttime', $visitor['short_time'], PDO::PARAM_BOOL);
      $sql_query->bindParam(':inout', $visitor['in_out'], PDO::PARAM_BOOL);
      $sql_query->bindParam(':coupin', $visitor['coupin'], PDO::PARAM_BOOL);
      $sql_query->bindParam(':logdate', $log_date, PDO::PARAM_STR);
      $sql_query->bindParam(':dow', $day_of_week, PDO::PARAM_INT);
      $sql_query->execute();
      $ncount++;
    }
  }

  $sql_query = $pdo->prepare("SELECT id FROM logs WHERE net_id = :netid AND callsign = :netcontrol AND date = :logdate;");
  $sql_query->bindParam(':netid', $net_id, PDO::PARAM_INT);
  $sql_query->bindParam(':netcontrol', $net_control, PDO::PARAM_STR);
  $sql_query->bindParam(':logdate', $log_date, PDO::PARAM_STR);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  if (empty($result)) {
    $counter++;
    $sql_query = $pdo->prepare("INSERT INTO logs VALUES (NULL, :netid, :netcontrol, :counter, :netcontrol, 0, 0, 0, 0, 0, 0, 0, :logdate, :dow);");
    $sql_query->bindParam(':netid', $net_id, PDO::PARAM_INT);
    $sql_query->bindParam(':netcontrol', $net_control, PDO::PARAM_STR);
    $sql_query->bindParam(':counter', $counter, PDO::PARAM_INT);
    $sql_query->bindParam(':logdate', $log_date, PDO::PARAM_STR);
    $sql_query->bindParam(':dow', $day_of_week, PDO::PARAM_INT);
    $sql_query->execute();
    $ncount++;
  }

  $source =(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
  $log = date("Y-m-d H:i:s") . "\t" . $source . "\tNET LOG FOR " . $log_date . ' [' . $net_name . ']';

  if ($append) {
    $log .= ' APPENDED';
  } else {
    $log .= ' SUBMITTED';
  }

  $log .= "\t" . $_SESSION['user_id'] . "\n";
  error_log($log, 3, "/var/log/jars-net-logger.log");

  echo json_encode(['status' => 'SUCCESS', 'count' => $ncount]);
}
