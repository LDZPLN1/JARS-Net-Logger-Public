<?php

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

?>
    <hr>
    <div class="footer">
<?php
  if (!isset($en_log_entry)) $en_log_entry = false;
  if ($en_log_entry) echo '      <div id="footer_shortcuts" class="footer_shortcuts" onclick="toggle_shortcuts();">View Shortcuts / Legend &#9660;</div>' . "\n";
?>
      <div class="footer_copyright">Copyright &copy; 2026 Douglas Graham [AB9XA]</div>
    </div>
