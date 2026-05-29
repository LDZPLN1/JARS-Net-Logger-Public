<?php
ob_start();
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

if (isset($_SESSION['user_id']) && isset($_SESSION['guest']) && isset($_SESSION['admin'])) {
  if (isset($_SESSION['net_id'])) {
    if ($_SESSION['net_id'] == 0) {
      header("Location: jars_net_manager.php");
      exit();
    } else {
      header("Location: jars_net_log_entry.php");
      exit();
    }
  }
}

require_once('config.php');

$error = '';

// CREATE DATABASE CONNECTION

try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, base64_decode(DB_PASSWORD));
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  $error = 'Error connecting to database';
}

// VALIDATE LOGIN CREDENTIALS

function verify_login($pdo) {
  global $error;

  $source = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
  $username = strtoupper(trim($_POST['username']));
  $password = $_POST['password'];

  $sql_query = $pdo->prepare("SELECT username, password, admin FROM auth WHERE username = :username");
  $sql_query->bindParam(':username', $username, PDO::PARAM_STR);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  if (empty($result)) {
    $log = date("Y-m-d H:i:s") . "\t" . $source . "\tLOGIN FAILED: BAD USER ID\t" . $username . "\n";
    error_log($log, 3, "/var/log/jars-net-logger.log");
    $error = 'Log in Failed';
    return;
  }

  if (password_verify($password, $result['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $result['username'];

    $_SESSION['admin'] = ($result['admin'] == 1) ? true : false;
    $_SESSION['guest'] = (str_starts_with($result['username'], strtoupper(GUEST_PREFIX))) ? true : false;

    if (isset($_POST['net_id'])) {
      $_SESSION['net_id'] = $_POST['net_id'];

      $sql_query = $pdo->prepare("SELECT net_name FROM nets WHERE id = :net_id;");
      $sql_query->bindParam(':net_id', $_POST['net_id'], PDO::PARAM_INT);
      $sql_query->execute();
      $result = $sql_query->fetch(PDO::FETCH_ASSOC);

      $_SESSION['net_name'] = $result['net_name'];;

      $sql_query = $pdo->prepare("SELECT preferred_name FROM visitors WHERE callsign = :username");
      $sql_query->bindParam(':username', $username, PDO::PARAM_STR);
      $sql_query->execute();
      $result = $sql_query->fetch(PDO::FETCH_ASSOC);

      if (!empty($result)) {
        $_SESSION['user_name'] = $result['preferred_name'];
      }

      $log = date("Y-m-d H:i:s") . "\t" . $source . "\tLOGIN SUCCESSFUL [" . $_SESSION['net_name'] . "]\t" . $username . "\n";
      error_log($log, 3, "/var/log/jars-net-logger.log");
      header("Location: jars_net_log_entry.php");
      exit();
    } else if($result['admin'] == 1) {
      $_SESSION['net_id'] = 0;
      header("Location: jars_net_manager.php");
      exit();
    } else {
      header("Location: jars_logout.php");
      exit();
    }
  } else {
      $log = date("Y-m-d H:i:s") . "\t" . $source . "\tLOGIN FAILED: BAD PASSWORD\t" . $username . "\n";
      error_log($log, 3, "/var/log/jars-net-logger.log");
      $error = 'Log in Failed';
      return;
  }
}

function open_live_log($pdo) {
  $sql_query = $pdo->prepare("SELECT net_name FROM nets WHERE id = :net_id;");
  $sql_query->bindParam(':net_id', $_POST['net_id'], PDO::PARAM_INT);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  if (!empty($result)) {
    $_SESSION['live_log_net_name'] = $result['net_name'];
    $_SESSION['live_log_net_id'] = $_POST['net_id'];
    header("Location: jars_live_log_viewer.php");
    exit();
  } else {
    return;
  }
}

if (isset($_POST['mode'])) {
  switch ($_POST['mode']) {
    case "login":
      if (isset($_POST['username']) && isset($_POST['password'])) {
        verify_login($pdo);
      }
      break;
    case "live":
      if (isset($_POST['net_id'])) {
        open_live_log($pdo);
      }
      break;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="language" content="English" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
  <title>JARS Net Logger Log In</title>
  <link rel='stylesheet' type='text/css' href='style.css'>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inclusive+Sans:ital,wght@0,300..700;1,300..700&family=Poetsen+One&display=swap" rel="stylesheet">
</head>

<body>
  <div class="content_page">
<?php
  $date = new DateTimeImmutable();
  $base = basename(__FILE__);
  require_once('jars_header.php');
?>
    <div class="content_main">
<?php
  if ($error != '') {
    echo '      <div class="message_error">' . $error . "</div>\n";
  }

  echo '      <form method="POST" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
?>
      <table>
        <tr>
          <td>
            <table class="form_table">
              <tr>
                <td>Login ID:</td>
                <td><input type="text" name="username" autocomplete="username" autofocus oninput="check_login();"></td>
              </tr>
              <tr>
                <td>Password:</td>
                <td><input type="password" name="password" oninput="check_login();"></td>
              </tr>
<?php
  $sql_query = $pdo->prepare("SELECT id, net_name FROM nets WHERE active = 1 ORDER BY net_name");
  $sql_query->execute();
  $result = $sql_query->fetchAll(PDO::FETCH_ASSOC);

  if (!empty($result)) {
    echo "              <tr>\n";
    echo "                <td>Net:</td>\n";
    echo "                <td>\n";
    echo '                  <select id="net_list" name="net_id" onchange="update_guest_login();">' . "\n";

    foreach ($result as $net) {
      echo '                    <option value="' . $net['id'] . '" data-id="0">' . $net['net_name'] . "</option>\n";
    }

    echo "                  </select>\n";
    echo "                </td>\n";
    echo "              </tr>\n";
  }
?>
            </table>
          </td>
          <td width="32px">
          </td>
          <td>
            <div class="align_center">
              <button type="submit" id="button_login" name="mode" value="login" class="button_red_mb">Net Control Login</button><br>
              <button type="submit" id="button_live_log" name="mode" value="live" class="button_red">View Live Log</button>
            </div>
          </td>
        </tr>
      </table>
      </form>
    </div>
<?php
  require_once('jars_footer.php');
?>
  </div>
  <script type="text/javascript" src="/socket.io/socket.io.js"></script>
  <script type="text/javascript" src="js/jars_login.js"></script>
</body>
</html>
<?php
ob_end_flush();
?>
