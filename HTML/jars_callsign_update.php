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

REVISION 20260608.01

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

$message = '';

// CREATE DATABASE CONNECTION

try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, base64_decode(DB_PASSWORD));
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  $error = 'Unable to Connect to Database';
}

// UPDATE CALLSIGN

function update_callsign($pdo) {
  global $message;

  if (!isset($_POST['old_call']) || !isset($_POST['new_call'])) return;

  $old_call = trim($_POST['old_call']);
  $new_call = trim($_POST['new_call']);
  $update = 0;

  // GET CURRENT CALL INFORMATION

  $sql_query = $pdo->prepare("SELECT callsign, preferred_name, location, notes, lid FROM visitors WHERE callsign = :callsign;");
  $sql_query->bindParam(':callsign', $old_call, PDO::PARAM_STR);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  if (empty($result)) return;

  $old_db_preferred_name = $result['preferred_name'];
  $old_db_location = $result['location'];
  $old_db_notes = $result['notes'];
  $old_db_lid = $result['lid'];

  // CHECK IF NEW CALL EXISTS

  $sql_query = $pdo->prepare("SELECT callsign FROM visitors WHERE callsign = :callsign;");
  $sql_query->bindParam(':callsign', $new_call, PDO::PARAM_STR);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  if (!empty($result)) $update = 1;

  // CHECK CALL FOR () MARKINGS

  $old_pn = (str_contains($old_db_preferred_name, '(')) ? trim(strstr($old_db_preferred_name, '(', true)) : $old_db_preferred_name;
  $old_preferred_name = $old_pn . ' (Now ' . $new_call . ')';
  $new_preferred_name = $old_pn . ' (AKA ' . $old_call . ')';

  // INSERT/UPDATE NEW CALL INFO

  if ($update == 0) {
    $sql_query = $pdo->prepare("INSERT INTO visitors VALUES (:new_call, :preferred_name, :location, :notes, :lid);");
  } else {
    $sql_query = $pdo->prepare("UPDATE visitors SET preferred_name = :preferred_name, location = :location, notes = :notes, lid = :lid WHERE callsign= :new_call;");
  }

  $sql_query->bindParam(':new_call', $new_call, PDO::PARAM_STR);
  $sql_query->bindParam(':preferred_name', $new_preferred_name, PDO::PARAM_STR);
  $sql_query->bindParam(':location', $old_db_location, PDO::PARAM_STR);
  $sql_query->bindParam(':notes', $old_db_notes, PDO::PARAM_STR);
  $sql_query->bindParam(':lid', $old_db_lid, PDO::PARAM_INT);
  $sql_query->execute();

  // CHANGE OLD CALLSIGN PREFERRED NAME

  $sql_query = $pdo->prepare("UPDATE visitors SET preferred_name = :preferred_name, lid = 0 WHERE callsign = :old_call;");
  $sql_query->bindParam(':old_call', $old_call, PDO::PARAM_STR);
  $sql_query->bindParam(':preferred_name', $old_preferred_name, PDO::PARAM_STR);
  $sql_query->execute();

  // UPDATE LOGS WITH NEW ID

  $sql_query = $pdo->prepare("UPDATE logs SET callsign = :new_call WHERE callsign = :old_call;");
  $sql_query->bindParam(':old_call', $old_call, PDO::PARAM_STR);
  $sql_query->bindParam(':new_call', $new_call, PDO::PARAM_STR);
  $sql_query->execute();

  // GET UPDATED RECORD COUNT AND ADD TO MESSAGE

  $count = $sql_query->rowCount();
  $message =  'Callsign ' . $_POST['old_call'] . ' updated to ' . $_POST['new_call'] . ', ' . $count . " log records updated\n";
}

if (isset($_POST['mode'])) {
  switch ($_POST['mode']) {
    case "change":
      update_callsign($pdo);
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
  <title><?php echo ORG_NAME; ?> Callsign Update</title>
  <link rel='stylesheet' type='text/css' href='style.css'>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inclusive+Sans:ital,wght@0,300..700;1,300..700&family=Poetsen+One&display=swap" rel="stylesheet">
</head>

<body onload="init_update_callsign();">
  <div id="overlay_chg" class="overlay">
    <div class="overlay_content">
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
      <input type="hidden" id="old_call" name="old_call" value="">
      <input type="hidden" id="new_call" name="new_call" value="">
      <table class="form_table">
        <tr>
          <td colspan="2" class="message_caution">The following callsign will be updated:</td>
        </tr>
        <tr>
          <td colspan="2" class="align_center"><span id="old_chg_callsign"></span> (<span id="preferred_name"></span>) &#8680; <span id="new_chg_callsign"></span></td>
        </tr>
        <tr>
          <td colspan="2"></td>
        </tr>
        <tr>
          <td class="align_center"><button type="button" class="button_green" onclick="close_change();">Cancel</button></td>
          <td class="align_center"><button type="submit" name="mode" class="button_red" value="change">Update Callsign</button></td>
        </tr>
      </table>
      </form>
    </div>
  </div>
  <div class="content_page">
<?php
  $date = new DateTimeImmutable();
  $base = basename(__FILE__);
  require_once('jars_header.php');
?>
  <div class="content_top">Welcome <span id="session_id"><?php echo $_SESSION['user_id']; ?></span></div>
  <hr>
  <div class="content_main">
<?php if ($message != '') echo '<div class="message_ok">' . $message . "</div>\n"; ?>
      <div class="message_yellow">Making a callsign update will:</div><br>
        <table>
          <tr class="color_yellow">
            <td colspan="2" class="list_pb">
              <li class="li_mb">Create a new callsign entry if one does not already exist</li>
              <li class="li_mb">Copy the preferred name, location, notes and lid status <br>from the original callsign to the new one</li>
              <li class="li_mb">Update existing log records by replacing the old callsign <br>with the new one</li>
            </td>
          </tr>
          <tr>
            <td class="td_label"><label for="old_callsign">Old Callsign:</label></td>
            <td class="td_callsign"><input id="old_callsign" name="old_callsign" class="input_width_100" type="text" maxlength="6" onchange="lookup_user_callsign(this, 0);" onkeydown="move_cursor_callsign(event)" placeholder="Enter Callsign" autofocus></td>
          </tr>
          <tr>
            <td class="td_label"><label for="new_callsign">New Callsign:</label></td>
            <td class="td_callsign"><input id="new_callsign" name="new_callsign" class="input_width_100" type="text" maxlength="6" oninput="check_length(this);" onchange="lookup_user_callsign(this, 1);" placeholder="Enter Callsign"></td>
          </tr>
        </table><br>
        <input type="hidden" id="valid_callsign" name="valid_callsign" value="0">
        <div class="align_center"><button type="button" id="btn_update_callsign" class="button_red_w" onclick="show_change_callsign();">Update Callsign</button></div>
      </div>
<?php require_once('jars_footer.php'); ?>
  </div>
  <script type="text/javascript" src="js/jars_admin.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
