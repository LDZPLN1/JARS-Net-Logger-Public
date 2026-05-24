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

$error = '';

// CREATE DATABASE CONNECTION

try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, base64_decode(DB_PASSWORD));
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  $error = 'Unable to Connect to Database';
}

// ADD NEW NET

function add_net($pdo) {
  global $error;

  $submode = null;

  if ($_POST['add_net_mode'] == 'DIGITALVOICE' || $_POST['add_net_mode'] == 'SSB') {
    $submode = $_POST['add_net_submode'];
  }

  try {
    $sql_query = $pdo->prepare("INSERT INTO nets VALUES (NULL, :netname, :band, :mode, :submode, :frequency, 1);");
    $sql_query->bindParam(':netname', $_POST['add_net_name'], PDO::PARAM_STR);
    $sql_query->bindParam(':band', $_POST['add_net_band'], PDO::PARAM_STR);
    $sql_query->bindParam(':mode', $_POST['add_net_mode'], PDO::PARAM_STR);
    $sql_query->bindParam(':submode', $submode, PDO::PARAM_STR);
    $sql_query->bindParam(':frequency', $_POST['add_net_freq'], PDO::PARAM_STR);
    $sql_query->execute();
  } catch (PDOException $e) {
    if ($e->getCode() == '23000') {
      $error = 'Name Already Exists in Database';
    }
  }
}
// CHANGE NET NAME

function change_net($pdo) {
  global $error;
  try {
    $sql_query = $pdo->prepare("UPDATE nets SET net_name = :netname WHERE id = :netid;");
    $sql_query->bindParam(':netname', $_POST['chg_net_name'], PDO::PARAM_STR);
    $sql_query->bindParam(':netid', $_POST['record_id'], PDO::PARAM_INT);
    $sql_query->execute();
  } catch (PDOException $e) {
    $error = 'Error Trying to Update Database';
  }
}

// DELETE NET

function delete_net($pdo) {
  global $error;
  try {
    $sql_query = $pdo->prepare("DELETE FROM nets WHERE id = :netid;");
    $sql_query->bindParam(':netid', $_POST['record_id'], PDO::PARAM_INT);
    $sql_query->execute();

    // RESET NET_ID IF CURRENT NET IS DELETED
    
    if ($_POST['record_id'] == $_SESSION['net_id']) {
      $_SESSION['net_id'] = '0';
    }
  } catch (PDOException $e) {
    $error = 'Error Trying to Delete Net From Database';
  }
}

// TOGGLE ACTIVE STATUS OF NET

function toggle_active($pdo) {
  $sql_query = $pdo->prepare("SELECT active FROM nets WHERE id = :netid;");
  $sql_query->bindParam(':netid', $_POST['net_id'], PDO::PARAM_INT);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  $active = !$result['active'];

  $sql_query = $pdo->prepare("UPDATE nets SET active = :active WHERE id = :netid;");
  $sql_query->bindParam(':active', $active, PDO::PARAM_INT);
  $sql_query->bindParam(':netid', $_POST['net_id'], PDO::PARAM_INT);
  $sql_query->execute();
}

if (isset($_POST['mode'])) {
  switch ($_POST['mode']) {
    case "add":
      add_net($pdo);
      break;
    case "active":
      toggle_active($pdo);
      break;
    case "change":
      change_net($pdo);
      break;
    case "delete":
      delete_net($pdo);
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
<?php
  echo '  <title>' . ORG_NAME . " Net Manager</title>\n";
?>
  <link rel='stylesheet' type='text/css' href='style.css'>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inclusive+Sans:ital,wght@0,300..700;1,300..700&family=Poetsen+One&display=swap" rel="stylesheet">
</head>

<body onload="init_net_manager();">
  <div id="overlay_add" class="overlay">
    <div class="overlay_content">
      <span class="button_close_overlay" onclick="close_add()">&times;</span>
<?php
    echo '      <form method="POST" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
?>
      <table class="form_table">
        <tr>
          <td colspan="2" class="td_height"></td>
        </tr>
        <tr>
          <td>Net Name:</td>
          <td><input type="text" id="add_net_name" name="add_net_name" class="input_width_300" onchange="update_net_create_button();"></td>
        </tr>
        <tr>
          <td>Frequency (MHz):</td>
          <td><input type="text" id="add_net_freq" class="input_net_freq" name="add_net_freq" maxlength="11" onchange="check_net_freq();"></td>
        </tr>
        <tr>
          <td>Band:</td>
          <td><input type="text" id="add_net_band" class="input_net_band" name="add_net_band" value="" readonly></td>
        </tr>
        <tr>
          <td>Mode:</td>
          <td>
            <select id="add_net_mode" name="add_net_mode" onchange="update_submodes();">
              <option value="AM">AM</option>
              <option value="DIGITALVOICE">Digital Voice</option>
              <option value="FM" selected>FM</option>
              <option value="SSB">SSB</option>
            </select>
          </td>
        </tr>
        <tr id="row_net_submode">
          <td>Submode:</td>
          <td>
            <select id="add_net_submode" name="add_net_submode">
              <option value="LSB">LSB</option>
              <option value="USB">LSB</option>
            </select>
          </td>
        </tr>
        <tr>
          <td colspan="2" class="align_center">
            <button type="submit" id="btn_net_create" name="mode" value="add">Create Net</button>
          </td>
        </tr>
      </table>
      </form>
    </div>
  </div>
  <div id="overlay_chg" class="overlay">
    <div class="overlay_content">
      <span class="button_close_overlay" onclick="close_change()">&times;</span>
<?php
    echo '      <form method="POST" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
?>
      <input type="hidden" id="record_id" name="record_id" value="0">
      <table class="form_table">
        <tr>
          <td colspan="2" class="td_height"></td>
        </tr>
        <tr>
          <td>Net Name:</td>
          <td><input type="text" id="chg_net_name" name="chg_net_name" class="input_width_300" oninput="check_net_name();"></td>
        </tr>
        <tr>
          <td colspan="2" class="align_center">
            <button type="submit" id="btn_net_change" name="mode" value="change">Change Net Name</button>
          </td>
        </tr>
      </table>
      </form>
    </div>
  </div>
  <div id="overlay_del" class="overlay">
    <div class="overlay_content">
      <table class="form_table">
        <tr>
          <td colspan="2" class="message_caution">THIS WILL PERMANENTLY DELETE NET <span id="del_net" class="message_highlight"></span> AND ALL RELATED LOG RECORDS<br>ARE YOU SURE?</td>
        </tr>
        <tr>
          <td colspan="2"></td>
        </tr>
        <tr>
          <td class="align_center"><button type="button" class="button_green" onclick="close_delete();">Cancel</button></td>
          <td class="align_center"><button type="button" class="button_red" onclick="show_delete_2();">Delete Net</button></td>
        </tr>
      </table>
    </div>
  </div>
  <div id="overlay_del_2" class="overlay">
    <div class="overlay_content">
<?php
    echo '      <form method="POST" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
?>
      <input type="hidden" id="record_id_del" name="record_id" value="0">
      <table class="form_table">
        <tr>
          <td colspan="2" class="message_caution">THIS IS A DESTRUCTIVE DELETION AND THE DATA WILL BE GONE FOREVER<br>ARE YOU REALLY SURE?</td>
        </tr>
        <tr>
          <td colspan="2"></td>
        </tr>
        <tr>
          <td class="align_center"><button type="submit" name="mode" class="button_red" value="delete">Delete Net</button></td>
          <td class="align_center"><button type="button" class="button_green" onclick="close_delete_2();">Cancel</button></td>
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

  echo '    <div class="content_top">Welcome ' . $_SESSION['user_id'] . "</div>\n";
  echo "    <hr>\n";
  echo '    <div class="content_main">' . "\n";

  if ($error != '') {
    echo '<div class="message_error">' . $error . "</div>\n";
  }

  $sql_query = $pdo->prepare("SELECT * FROM nets ORDER BY active DESC, net_name;");
  $sql_query->execute();
  $result = $sql_query->fetchAll(PDO::FETCH_ASSOC);

  if (!$result) {
    echo '      <div class="message_ok">Please Create Your First Net</div>' . "\n";
  }

  echo '      <form method="POST" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
  echo '      <div class="selection_list">' . "\n";
  echo '        <div class="align_right">' . "\n";

  if ($result) {
    echo '          <label for="net_list" class="list_box_label">Select Net:</label>' . "\n";
    echo '          <select id="net_list" name="net_id" size="4" onchange="update_net_buttons();">' . "\n";

    foreach ($result as $net) {
      if (str_ends_with($net['frequency'], '000')) {
        $freq = substr($net['frequency'], 0, -3);
      } else {
        $freq = $net['frequency'];
      }

      $submode = '';

      if ($net['submode'] != '') {
        $submode = '/' . $net['submode'];
      }

      echo '            <option value="' . $net['id'] . '" data-id="' . $net['active'] . '::' . $net['net_name'] . '">' . $net['net_name'] . '&nbsp;&nbsp;(' . $freq . ' MHz; ' . $net['mode'] . $submode . ")</option>\n";
    }

    echo "          </select>\n";
  }

  echo "        </div>\n";

  if ($result) {
    echo "        <div></div>\n";
  }

  echo '        <div class="align_';

  if ($result) {
    echo 'left';
  } else {
    echo 'center';
  }

  echo '">' . "\n";
  echo '          <button type="button" name="mode" value="add" class="button_green_m" onclick="show_add_net();">Add Net</button><br>' . "\n";
  echo '          <button type="button" name="mode" value="change" id="btn_net_edit" class="button_grey_m" onclick="show_change_net();">Edit Net Name</button><br>' . "\n";
  echo '          <button type="submit" name="mode" value="active" id="btn_net_active" class="button_grey_m">Toggle Net Status</button><br><br>' . "\n";
  echo '          <button type="button" name="mode" value="delete" id="btn_net_delete" class="button_grey" onclick="show_delete_1();">Delete Net</button>' . "\n";
  echo "        </div>\n";

  if (!$result) {
    echo "        <div></div>\n";
  }

  echo "      </div>\n";
  echo "      </form>\n";
?>
    </div>
<?php
  require_once('jars_footer.php');
?>
  </div>
  <script type="text/javascript" src="js/jars_admin.js"></script>
</body>
</html>
<?php
ob_end_flush();
?>
