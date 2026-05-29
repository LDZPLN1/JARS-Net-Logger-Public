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

if ($_SESSION['guest'] == true) {
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

// CHANGE USER PASSWORD

function change_password($pdo) {
  global $error;
  global $message;

  if (!isset($_POST['old_password']) || !isset($_POST['new_password_1']) || !isset($_SESSION['user_id'])) {
    $error = 'Password change failed';
    return;
  }

  $old_password = $_POST['old_password'];
  $new_password_1 = $_POST['new_password_1'];

  $sql_query = $pdo->prepare("SELECT username, password FROM auth WHERE username = :username");
  $sql_query->bindParam(':username', $_SESSION['user_id'], PDO::PARAM_STR);
  $sql_query->execute();
  $result = $sql_query->fetch(PDO::FETCH_ASSOC);

  if (empty($result)) {
    $error = 'Password change failed';
    return;
  }

  if (password_verify($old_password, $result['password'])) {
    $hash = password_hash($new_password_1, PASSWORD_DEFAULT);

    $sql_query = $pdo->prepare("UPDATE auth SET password = :password WHERE username = :username");
    $sql_query->bindParam(':password', $hash, PDO::PARAM_STR);
    $sql_query->bindParam(':username', $_SESSION['user_id'], PDO::PARAM_STR);
    $sql_query->execute();

    if ($sql_query->rowCount() > 0) {
      $message = 'Password changed successfully';
      return;
    } else {
      $error = 'Password change failed';
      return;
    }
  } else {
    $error = 'Password change failed';
    return;
  }
}

if (isset($_POST['mode'])) {
  switch ($_POST['mode']) {
    case "change":
      change_password($pdo);
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
  <title><?php echo ORG_NAME; ?> Change Password</title>
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
    <div class="content_top">Changing Password for User <?php echo $_SESSION['user_id']; ?></div>
    <hr>
    <div class="content_main">
<?php
  if ($error != '') echo '<div class="message_error">' . $error . "</div>\n";
  if ($message != '') echo '<div class="message_ok">' . $message . "</div>\n";
?>
      <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
      <table class="form_table">
        <tr>
          <td>Old Password:</td>
          <td><input type="password" id="old_password" name="old_password" autofocus oninput="check_password();"></td>
          <td colspan="4"></td>
        </tr>
        <tr>
          <td>New Password:</td>
          <td><input type="password" id="new_password_1" name="new_password_1" oninput="check_password();"></td>
          <td id="pass_cap" class="color_red">A</td>
          <td id="pass_lwr" class="color_red">a</td>
          <td id="pass_num" class="color_red">#</td>
          <td id="pass_len" class="color_red_w">0</td>
        </tr>
        <tr>
          <td>New Password:</td>
          <td><input type="password" id="new_password_2" name="new_password_2" oninput="check_password();"></td>
          <td colspan="4"></td>
        </tr>
        <tr>
          <td colspan="6" class="align_center">
            <button id="btn_change_password" type="submit" name="mode" value="change">Change Password</button>
          </td>
        </tr>
      </table>
      </form>
    </div>
<?php require_once('jars_footer.php'); ?>
  </div>
  <script type="text/javascript" src="js/jars_change_password.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
