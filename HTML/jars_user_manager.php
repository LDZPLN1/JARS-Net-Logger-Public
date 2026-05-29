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
$message = '';

// CREATE DATABASE CONNECTION

try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, base64_decode(DB_PASSWORD));
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  $error = 'Unable to Connect to Database';
}

// ADD NEW USER

function add_user($pdo) {
  global $error;
  global $message;

  $username = trim(strtoupper($_POST['add_username']));
  $hash = password_hash($_POST['add_password_1'], PASSWORD_DEFAULT);

  try {
    $sql_query = $pdo->prepare("INSERT INTO auth VALUES (NULL, :username, :password, 0);");
    $sql_query->bindParam(':username', $username, PDO::PARAM_STR);
    $sql_query->bindParam(':password', $hash, PDO::PARAM_STR);
    $sql_query->execute();
  } catch (PDOException $e) {
    if ($e->getCode() == '23000') $error = 'Name Already Exists in Database';
  }
}

// CHANGE USER PASSWORD

function change_user($pdo) {
  global $message;

  $hash = password_hash($_POST['chg_password_1'], PASSWORD_DEFAULT);

  $sql_query = $pdo->prepare("UPDATE auth SET password = :password WHERE id = :recordid;");
  $sql_query->bindParam(':password', $hash, PDO::PARAM_STR);
  $sql_query->bindParam(':recordid', $_POST['record_id'], PDO::PARAM_INT);
  $sql_query->execute();
  $message = 'Password Changed';
}

// DELETE USER

function delete_user($pdo) {
  $sql_query = $pdo->prepare("DELETE FROM auth WHERE id = :recordid;");
  $sql_query->bindParam(':recordid', $_POST['record_id'], PDO::PARAM_INT);
  $sql_query->execute();
}

// TOGGLE ADMIN STATUS OF USER

function toggle_admin($pdo) {
  $sql_query = $pdo->prepare("SELECT admin FROM auth WHERE id = :userid;");
  $sql_query->bindParam(':userid', $_POST['userid'], PDO::PARAM_INT);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  $admin = !$result['admin'];

  $sql_query = $pdo->prepare("UPDATE auth SET admin = :admin WHERE id = :userid;");
  $sql_query->bindParam(':admin', $admin, PDO::PARAM_INT);
  $sql_query->bindParam(':userid', $_POST['userid'], PDO::PARAM_INT);
  $sql_query->execute();
}

if (isset($_POST['mode'])) {
  switch ($_POST['mode']) {
    case "add":
      add_user($pdo);
      break;
    case "admin":
      toggle_admin($pdo);
      break;
    case "change":
      change_user($pdo);
      break;
    case "delete":
      delete_user($pdo);
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
  <title><?php echo ORG_NAME; ?> User Manager</title>
  <link rel='stylesheet' type='text/css' href='style.css'>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inclusive+Sans:ital,wght@0,300..700;1,300..700&family=Poetsen+One&display=swap" rel="stylesheet">
</head>

<body onload="init_user_manager();">
  <div id="overlay_add" class="overlay">
    <div class="overlay_content">
      <span class="button_close_overlay" onclick="close_add()">&times;</span>
      <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
      <table class="form_table">
        <tr>
          <td colspan="6" class="td_height"></td>
        </tr>
        <tr>
          <td>User ID:</td>
          <td><input type="text" id="add_username" name="add_username" oninput="check_password(1);"></td>
          <td colspan="4"></td>
        </tr>
        <tr>
          <td>New Password:</td>
          <td><input type="password" id="add_password_1" name="add_password_1" oninput="check_password(1);"></td>
          <td id="add_pass_cap" class="color_red">A</td>
          <td id="add_pass_lwr" class="color_red">a</td>
          <td id="add_pass_num" class="color_red">#</td>
          <td id="add_pass_len" class="color_red_w">0</td>
        </tr>
        <tr>
          <td>New Password:</td>
          <td><input type="password" id="add_password_2" name="add_password_2" oninput="check_password(1);"></td>
          <td colspan="4"></td>
        </tr>
        <tr>
          <td colspan="6" class="align_center">
            <button type="submit" id="btn_add" name="mode" value="add">Create User</button>
          </td>
        </tr>
      </table>
      </form>
    </div>
  </div>
  <div id="overlay_chg" class="overlay">
    <div class="overlay_content">
      <span class="button_close_overlay" onclick="close_change()">&times;</span>
      <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
      <input type="hidden" id="record_id" name="record_id" value="0">
      <table class="form_table">
        <tr>
          <td colspan="6" class="td_height"></td>
        </tr>
        <tr>
          <td>New Password:</td>
          <td><input type="password" id="chg_password_1" name="chg_password_1" oninput="check_password(2);"></td>
          <td id="chg_pass_cap" class="color_red">A</td>
          <td id="chg_pass_lwr" class="color_red">a</td>
          <td id="chg_pass_num" class="color_red">#</td>
          <td id="chg_pass_len" class="color_red_w">0</td>
        </tr>
        <tr>
          <td>New Password:</td>
          <td><input type="password" id="chg_password_2" name="chg_password_2" oninput="check_password(2);"></td>
          <td colspan="4"></td>
        </tr>
        <tr>
          <td colspan="6" class="align_center">
            <button type="submit" id="btn_chg" name="mode" value="change">Change Password</button>
          </td>
        </tr>
      </table>
      </form>
    </div>
  </div>
  <div id="overlay_del" class="overlay">
    <div class="overlay_content">
<?php echo '      <form method="POST" action="' . $_SERVER['PHP_SELF'] . '">' . "\n"; ?>
      <input type="hidden" id="record_id_del" name="record_id" value="0">
      <table class="form_table">
        <tr>
          <td colspan="2" class="message_caution">THIS WILL PERMANENTLY DELETE USER <span id="del_user" class="message_highlight"></span><br>ARE YOU SURE?</td>
        </tr>
        <tr>
          <td colspan="2"></td>
        </tr>
        <tr>
          <td class="align_center"><button type="button" class="button_green" onclick="close_delete();">Cancel</button></td>
          <td class="align_center"><button type="submit" name="mode" class="button_red" value="delete">Delete User</button></td>
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
<?php
  if ($error != '') echo '      <div class="message_error">' . $error . "</div>\n";
  if ($message != '') echo '      <div class="message_ok">' . $message . "</div>\n";

  $sql_query = $pdo->prepare("SELECT id, username, admin FROM auth ORDER BY admin DESC, username;");
  $sql_query->execute();
  $result = $sql_query->fetchAll(PDO::FETCH_ASSOC);

  if (!$result) {
    echo '      <div class="message_error">No Records Found</div>' . "\n";
  } else {
    echo '      <form method="POST" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
    echo '      <div class="selection_list">' . "\n";
    echo '        <div class="align_right">' . "\n";
    echo '          <label for="user_list" class="list_box_label">Select User:</label>' . "\n";
    echo '          <select id="user_list" name="userid" size="10" onchange="update_user_buttons();">' . "\n";

    foreach ($result as $user) {
      $flag = ($user['admin'] == 1) ? ' [ADMIN]' : '';
      $guest = (str_starts_with($user['username'], strtoupper(GUEST_PREFIX))) ? '1' : '0';

      echo '            <option value="' . $user['id'] . '" data-id="' . $user['username'] . ':' . $guest . '">' . $user['username'] . $flag . "</option>\n";
    }

    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <div></div>\n";
    echo '        <div class="align_left">' . "\n";
    echo '          <button type="button" name="mode" value="add" class="button_green_m" onclick="show_add();">Add User</button><br>' . "\n";
    echo '          <button type="button" name="mode" value="change" id="btn_user_change" class="button_grey_m" onclick="show_change();">Change User Password</button><br>' . "\n";
    echo '          <button type="submit" name="mode" value="admin" id="btn_user_admin" class="button_grey_m">Toggle Admin Status</button><br><br>' . "\n";
    echo '          <button type="button" name="mode" value="delete" id="btn_user_delete" class="button_grey" onclick="show_delete();">Delete User</button>' . "\n";
    echo "        </div>\n";
    echo "      </div>\n";
    echo "      </form>\n";
  }
?>
    </div>
<?php require_once('jars_footer.php'); ?>
  </div>
  <script type="text/javascript" src="js/jars_admin.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
