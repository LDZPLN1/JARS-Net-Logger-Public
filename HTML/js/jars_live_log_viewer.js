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

  REVISION 20260519.01

*/

const io_logs = io('/logs');

const c_hlgt_lid = '#4f0000';
const c_hlgt_checkout = '#004f00';
const c_hlgt_in_out = '#4f4f00';
const f_banner_message = document.getElementById('banner_message');
const net_id = document.getElementById('title_text').dataset.id;

var active_index = 0;

io_logs.on('connect', () => {
  io_logs.emit('log-join', net_id);
})

// UPDATE LIVE LOG VIEW

io_logs.on('log-data', (msg) => {
  const f_net_control = document.getElementById('net_control');
  const f_check_ins = document.getElementById('check_ins');
  const f_check_ins_unique = document.getElementById('check_ins_unique');
  const f_table_logs = document.querySelector('#table_logs tbody');
  const f_footer_hr = document.getElementById('dynamic_hr');
  const f_content_main = document.getElementById('content_main');
  const callsigns = [];
  const json_log_data = msg.log;

  f_net_control.textContent = json_log_data.meta.net_control;

  if (json_log_data.visitors.length > 0) {
    f_content_main.style.display = 'block';
    f_footer_hr.style.display = 'block';
  } else {
    f_content_main.style.display = 'none';
    f_footer_hr.style.display = 'none';
  }

  const marker = '&#9673;';
  f_table_logs.innerHTML = '';

  json_log_data.visitors.forEach(row_data => {
    if (!callsigns.includes(row_data.callsign)) {
      callsigns.push(row_data.callsign);
    }

    const new_row = document.createElement('tr');

    if (row_data.in_out == 1) {
      new_row.style.backgroundColor = c_hlgt_in_out;
    }

    if (row_data.lid == 1) {
      new_row.style.backgroundColor = c_hlgt_lid;
    }

    if (row_data.checked_out == 1) {
      new_row.style.backgroundColor = c_hlgt_checkout;
    }

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

  f_check_ins.textContent = json_log_data.visitors.length;
  f_check_ins_unique.textContent = unique_text;
})

io_logs.on('log-shutdown', () => {
  window.location.href = "index.php";
})

function rotate_banner() {
  active_index = (active_index + 1) % banners.length;
  f_banner_message.classList.remove('active');

  setTimeout(() => {
    f_banner_message.textContent = banners[active_index];
    f_banner_message.classList.add('active');
  }, 1000);
}

f_banner_message.textContent = banners[0];
f_banner_message.classList.add('active');

setInterval(rotate_banner, 15000);
io_logs.emit('log-request', net_id);
