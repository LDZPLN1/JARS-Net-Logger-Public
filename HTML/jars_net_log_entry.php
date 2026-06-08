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

if (!isset($_SESSION['user_id']) || !isset($_SESSION['guest']) || !isset($_SESSION['admin']) || !isset($_SESSION['net_id'])) {
  header("Location: jars_logout.php");
  exit();
}

require_once('config.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="language" content="English" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
  <title><?php echo ORG_NAME; ?> Net Log Entry</title>
  <link rel='stylesheet' type='text/css' href='style.css'>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inclusive+Sans:ital,wght@0,300..700;1,300..700&family=Poetsen+One&display=swap" rel="stylesheet">
</head>

<body>
<?php
if ($_SESSION['net_id'] == 0) {
  echo "  <script>\n";
  echo "    alert('No net selected (Maybe you added a new net and have not logged out/in to select it). Please select a net at log in and try again');\n";
  echo "    window.location.href = 'jars_logout.php';\n";
  echo "  </script>\n";
}
?>
  <input type="hidden" id="account" name="account" value="<?php echo $_SESSION['guest'];?>">
  <div id="overlay_callsign" class="overlay">
    <div class="overlay_content">
      <span class="button_close_overlay" onclick="close_callsign()">&times;</span>
      <table class="form_table">
        <tr>
          <td class="td_height"></td>
        </tr>
      </table>
      <div class="message_green_mb">Multiple matches found<br>Please select from the following:</div>
      <table id="table_callsigns">
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
  <div class="content_page">
<?php
  $date = new DateTimeImmutable();
  $base = basename(__FILE__);
  $en_log_entry = true;
  require_once('jars_header.php');

  echo '    <div id="title_text" class="message_green" data-id="' . $_SESSION['net_id'] . '">' . $_SESSION['net_name'] . "</div>\n";
?>
    <hr>
    <div class="content_top_flex">
      <div>
<?php
  echo '        <span>Net Control:</span><input type="text" maxlength="6" id="net_control" class="input_width_100" placeholder="Enter Callsign" onchange="update_case(this);" ';
  echo ($_SESSION['guest'] == false && $_SESSION['user_id'] != 'ADMIN') ? 'value="' . $_SESSION['user_id'] . '">' . "\n" : "autofocus>\n";
?>
      </div>
      <div>
        <span>Log Date:</span><input type="date" id="log_date" value="2026-01-01" min="2026-01-01" max="2040-12-31">
      </div>
      <div>
        <span>Check-Ins:</span><span id="check_ins">0</span>&nbsp;<span id="check_ins_unique">(0 Visitors)</span>
      </div>
      <div>
        <button id="btn_live_log" class="button_red_mr" title="Set Net Control" disabled onclick="toggle_live_log();">Go Live</button>
        <button id="btn_upload_logs" class="button_red" title="Set Net Control" disabled onclick="submit_logs(false);">Submit Log</button>
      </div>
    </div>
    <hr>
    <div class="content_main">
      <table id="table_logs">
        <thead>
          <tr>
            <th class="th_log_pl">CALLSIGN</th>
            <th class="th_log">ANN</th>
            <th class="th_log">MOB</th>
            <th class="th_log">POR</th>
            <th class="th_log">S/T</th>
            <th class="th_log">E/L</th>
            <th class="th_log">I/O</th>
            <th class="th_log">CPN</th>
            <th class="th_log_pl">PREFERRED NAME</th>
            <th class="th_log_al">LOCATION</th>
            <th class="th_log_al">NOTES</th>
            <th class="th_log_checkout" title="Checkout All Rows" onclick="checkout_all();">C/O</th>
            <th class="th_log">LID</th>
            <th class="th_log">A/U</th>
            <th class="th_log_p">DEL</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
<?php require_once('jars_footer.php'); ?>
    <div id="shortcuts" class="shortcuts">
      <table id="table_shortcuts">
        <tr>
          <th>CALLSIGN SEARCH</th>
          <th colspan="3">CHECKBOX SHORTCUTS</th>
          <th>LEGEND</th>
        </tr>
        <tr>
          <td>
            -XX, -XXX, -XXXX - Suffix Search
          </td>
          <td>
            /A - Announcement<br>
            /M - Mobile<br>
            /P - Portable<br>
            /S - Short Time<br>
            /E - EchoLink<br>
            /I - In/Out<br>
            /C - Coupin<br>
            /R - Re-Check
          </td>
          <td>
            /MS - Mobile & Short Time<br>
            /PS - Portable & Short Time<br>
            /ES - EchoLink & Short Time
          </td>
          <td>
            /MI - Mobile & In/Out<br>
            /PI - Portable & In/Out<br>
            /EI - EchoLink & In/Out
          </td>
          <td>
            <div>
              <div class="color_bg_in_out">In/Out</div>
            </div>
            <div>
              <div class="color_bg_lid">LID</div>
            </div>
            <div>
              <div class="color_bg_checkout">Checked Out</div>
            </div>
          </td>
        </tr>
      </table>
    </div>
  </div>
  <div id="jars_chat" class="jars_chat"><img src="images/chat_red.png" id="chat_icon" class="chat_icon" onclick="toggle_chat();"></div>
  <div id="chat_container" class="chat_container">
    <div class="chat_header">JARS Chat <span id="chat_count"></span></div>
    <div id="chat_message_list" class="chat_message_list"></div>
    <div class="input_chat_text">
      <input type="text" id="input_chat" placeholder="Type a message...">
      <button class="button_green" onclick="send_message()">Send</button>
    </div>
  </div>
  <script type="text/javascript" src="/socket.io/socket.io.js"></script>
  <script type="text/javascript" src="js/jars_net_log_entry.js"></script>
  <script type="text/javascript" src="js/jars_chat.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
