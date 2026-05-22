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

  REVISION 20260518.01

*/

const dir_path = '/jars';

var first_run = true;

// UPDATE LOG VIEW FOR SELECTED DATE

async function update_log() {
  const f_title_text = document.getElementById('title_text');
  const net_id = f_title_text.dataset.id;

  // CALL API TO GET LOG RECORDS

  try {
    const response = await fetch(`${dir_path}/api/viewer?log_date=${encodeURIComponent(log_date.value)}&net_id=${encodeURIComponent(net_id)}`);

    if (response.ok) {
      const json_data = await response.json();

      const f_check_ins = document.getElementById('check_ins');
      const f_label_check_ins = document.getElementById('label_check_ins');
      const f_check_ins_unique = document.getElementById('check_ins_unique');
      const f_table_logs = document.querySelector('#table_logs tbody');
      const f_content_main = document.getElementById('content_main');
      const f_net_control = document.getElementById('net_control');
      const f_label_net_control = document.getElementById('label_net_control');
      const f_footer_hr = document.getElementById('dynamic_hr');

      const callsigns = [];

      f_table_logs.innerHTML = '';

      if (json_data.status === 'SUCCESS') {

        // MAKE LABELS AND TABLE VISIBLE

        f_label_net_control.style.display = 'inline';
        f_net_control.textContent = json_data.meta.net_control;

        f_label_check_ins.style.display = 'inline';
        f_check_ins_unique.style.display = 'inline';
        f_content_main.style.display = 'block';
        f_footer_hr.style.display = 'block';

        const marker = '&#9673;';

        json_data.visitors.forEach(row_data => {
          if (!callsigns.includes(row_data.callsign)) {
            callsigns.push(row_data.callsign);
          }

          const new_row = document.createElement('tr');

          var chk_a = '';
          var chk_m = '';
          var chk_p = '';
          var chk_e = '';
          var chk_s = '';
          var chk_i = '';
          var chk_c = '';

          if (row_data.announcement) {
            var chk_a = marker;
          }

          if (row_data.mobile) {
            var chk_m = marker;
          }

          if (row_data.portable) {
            var chk_p = marker;
          }

          if (row_data.echolink) {
            var chk_e = marker;
          }

          if (row_data.short_time) {
            var chk_s = marker;
          }

          if (row_data.in_out) {
            var chk_i = marker;
          }

          if (row_data.coupin) {
            var chk_c = marker;
          }

          new_row.innerHTML = `
            <td><div class='input_callsign'>${row_data.callsign}</div></td>
            <td class="td_log_viewer_ac">${chk_a}</td>
            <td class="td_log_viewer_ac">${chk_m}</td>
            <td class="td_log_viewer_ac">${chk_p}</td>
            <td class="td_log_viewer_ac">${chk_s}</td>
            <td class="td_log_viewer_ac">${chk_e}</td>
            <td class="td_log_viewer_ac">${chk_i}</td>
            <td class="td_log_viewer_ac">${chk_c}</td>
            <td class="td_log_viewer_pn"><div class="preferred_name">${row_data.preferred_name}</div></td>
            <td><div class="location">${row_data.location}</div></td>
            <td><div class="notes">${row_data.notes}</div></td>
          `;

          f_table_logs.appendChild(new_row);

        });

        var unique_text = '(' + callsigns.length + ' Visitor';

        if (callsigns.length !== 1) {
          unique_text += 's';
        }

        unique_text += ')';

        document.getElementById('check_ins').textContent = json_data.visitors.length;
        document.getElementById('check_ins_unique').textContent = unique_text;

      } else if (json_data.status === 'NOT_FOUND') {

        // HIDE LABELS AND TABLE

        f_label_net_control.style.display = 'none';
        f_net_control.textContent = '';
        f_label_check_ins.style.display = 'none';
        f_check_ins.textContent = '';
        f_check_ins_unique.style.display = 'none';
        f_check_ins_unique.textContent = '';
        f_content_main.style.display = 'none';
        f_footer_hr.style.display = 'none';

        if (!first_run) {
          alert(`No data found for ${log_date.value}`);
        }
      }

      if (first_run) {
        first_run = false;
      }
    } else {
      console.error('Lookup failed');
    }
  } catch (error) {
    console.error('Lookup failed:', error);
  }
}

f_header_date = document.getElementById('header_date');
document.getElementById('log_date').value = f_header_date.textContent;
update_log();
