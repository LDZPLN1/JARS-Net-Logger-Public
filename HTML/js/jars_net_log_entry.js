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

  REVISION 20260523.01

*/

const dir_path = '/jars';

const io_logs = io('/logs');

const f_header_date = document.getElementById('header_date');
const f_net_control = document.getElementById('net_control');
const f_table_logs = document.querySelector('#table_logs tbody');
const net_id = document.getElementById('title_text').dataset.id;

const c_hlgt_lid = '#4f0000';
const c_hlgt_checkout = '#004f00';
const c_hlgt_in_out = '#4f4f00';
const c_upd_btn_dis_bdr = '2px solid #ff0000';
const c_upd_btn_en_bdr = '2px solid #00ff00';

var auto_hide = false;
var data_in_flight = false;
var enable_live_log = false;
var f_stored_element = '';
var unsaved_changes = false;

// DYNAMICALLY ADD NEW ROWS TO TABLE

function add_rows(num_rows) {
  for (let i = 0; i < num_rows; i++) {
    const new_row = document.createElement('tr');

    new_row.innerHTML = `
      <td><input type="text" class="input_callsign"
        onchange="lookup_user(this, false)"
        maxlength="9"
        onkeydown="move_cursor(event, this)"
        placeholder="Enter Callsign"></td>
      <td class="checkbox_log_ac"><input type="checkbox" class="announcement" disabled onchange="update_live_logs();"></td>
      <td class="checkbox_log_ac"><input type="checkbox" class="mobile" disabled onchange="update_live_logs();"></td>
      <td class="checkbox_log_ac"><input type="checkbox" class="portable" disabled onchange="update_live_logs();"></td>
      <td class="checkbox_log_ac"><input type="checkbox" class="short_time" disabled onchange="update_live_logs();"></td>
      <td class="checkbox_log_ac"><input type="checkbox" class="echolink" disabled onchange="update_live_logs();"></td>
      <td class="checkbox_log_ac"><input type="checkbox" class="in_out" onchange="update_in_out(this)" disabled></td>
      <td class="checkbox_log_ac"><input type="checkbox" class="coupin" disabled onchange="update_live_logs();"></td>
      <td><input type="text" class="preferred_name" maxlength="32" disabled></td>
      <td><input type="text" class="location" maxlength="32" disabled></td>
      <td><input type="text" class="notes" maxlength="64" disabled></td>
      <td class="checkbox_log_ac"><input type="checkbox" class="checkout" onchange="update_checkout(this)" disabled></td>
      <td class="checkbox_log_ac"><input type="checkbox" class="lid" onchange="update_lid(this)" disabled></td>
      <td class="image_ac"><img src="images/save_gs.png"
        title="Save/Update User Info"
        class="update_user"
        onclick="update_user(this)"></td>
      <td class="image_ac"><img src="images/delete_gs.png"
        title="Delete Row"
        class="img_delete_row"
        onclick="delete_row(this)"></td>
    `;

    f_table_logs.appendChild(new_row);
  }
}

// MARK ALL ROWS WITH CALLSIGNS AS CHECKED OUT

function checkout_all() {
  const f_check_ins = document.getElementById('check_ins');

  if (f_check_ins.textContent  === '0') return;

  if (confirm('Chech out all visitors?')) {
    const rows = document.querySelectorAll('#table_logs tbody tr');

    rows.forEach(current_row => {
      const f_callsign = current_row.querySelector('.input_callsign');

      if (f_callsign && f_callsign.value.trim() !== '') {
        current_row.querySelector('.checkout').checked = true;
        current_row.style.backgroundColor = c_hlgt_checkout;
      }
    });
  }

  update_auto_hide();
  update_live_logs();
}

// HIDE CALLSIGN OVERLAY

function close_callsign() {
  const f_overlay_callsign = document.getElementById("overlay_callsign");
  f_overlay_callsign.style.display = 'none';
}

// DELETE SELECTED ROW

function delete_row(f_source) {
  const current_row = f_source.closest('tr');
  const f_callsign = current_row.querySelector('.input_callsign');

  if (!f_callsign || f_callsign.value.trim() === '') return

  const parent = current_row.parentElement;

  if (confirm('Permanently remove this row?')) {
    current_row.remove();
  }

  update_counter();
  update_buttons();
  update_live_logs();

  if (document.getElementById('check_ins').textContent  === '0') {
    unsaved_changes = false;
  }
}

// SET FOCUS TO CALLSIGN FIELD OF NEXT ROW

function focus_callsign(f_source) {
  const current_row = f_source.closest('tr');

  // ADD NEW ROW IF NEEDED

  if (current_row === current_row.parentElement.lastElementChild && f_source.value.length > 0) {
    add_rows(1);
  }

  var next_row = current_row.nextElementSibling;

  // IF AUTO HIDE IS ENABLED, MOVE FOCUS TO NEXT VISIBLE ROW

  if (auto_hide && next_row.querySelector('.checkout').checked) {
    var test_row = next_row;

    while (next_row.querySelector('.checkout').checked) {
      test_row = next_row.nextElementSibling;
      next_row = test_row;
    }
  }

  const focus_field = next_row.querySelector('.input_callsign');
  focus_field.focus();
}

// SEND LIVE LOG UPDATE

io_logs.on('log-request', () => {
  update_live_logs();
})

// LOOKUP CALLSIGN AND FILL IN FIELDS IF FOUND

async function lookup_user(f_source, refresh) {
  const entered_data = f_source.value.trim().toUpperCase();

  if (!entered_data) return;

  const current_row = f_source.closest('tr');
  const f_announcement = current_row.querySelector('.announcement');
  const f_mobile = current_row.querySelector('.mobile');
  const f_portable = current_row.querySelector('.portable');
  const f_echolink = current_row.querySelector('.echolink');
  const f_short_time = current_row.querySelector('.short_time');
  const f_in_out = current_row.querySelector('.in_out');
  const f_coupin = current_row.querySelector('.coupin');
  const f_preferred_name = current_row.querySelector('.preferred_name');
  const f_location = current_row.querySelector('.location');
  const f_notes = current_row.querySelector('.notes');
  const f_checkout = current_row.querySelector('.checkout');
  const f_lid = current_row.querySelector('.lid');
  const f_update_user = current_row.querySelector('.update_user');
  const f_delete_row = current_row.querySelector('.img_delete_row');

  const modifier_index = entered_data.indexOf('/');

  if (!refresh) {
    f_announcement.disabled = false;
    f_mobile.disabled = false;
    f_portable.disabled = false;
    f_echolink.disabled = false;
    f_short_time.disabled = false;
    f_in_out.disabled = false;
    f_coupin.disabled = false;
    f_preferred_name.disabled = false;
    f_location.disabled = false;
    f_notes.disabled = false;
    f_checkout.disabled = false;
    f_lid.disabled = false;

    f_announcement.checked = false;
    f_mobile.checked = false;
    f_portable.checked = false;
    f_echolink.checked = false;
    f_short_time.checked = false;
    f_in_out.checked = false;
    f_coupin.checked = false;
    f_preferred_name.value = '';
    f_location.value = '';
    f_notes.value = '';
    f_checkout.checked = false;
    f_lid.checked = false;
  }

  unsaved_changes = true;

  // CHECK FOR / SHORTCUTS AND MARK CHECK BOXES

  if (modifier_index !== -1) {
    var callsign = entered_data.slice(0, modifier_index);
    const modifier_1 = entered_data.substr(modifier_index + 1, 1);
    var modifier_2 = '';

    if (entered_data.length - modifier_index - 1 === 2) {
      modifier_2 = entered_data.substr(modifier_index + 2, 1);
    }

    switch (modifier_1) {
      case 'A':
        f_announcement.checked = true;
        break;
      case 'C':
        f_coupin.checked = true;
        break;
      case 'E':
        f_echolink.checked = true;
        break;
      case 'I':
        f_in_out.checked = true;
        break;
      case 'M':
        f_mobile.checked = true;
        break;
      case 'P':
        f_portable.checked = true;
        break;
      case 'R':
        f_coupin.checked = true;
        break;
      case 'S':
        f_short_time.checked = true;
        break;
    }

    switch (modifier_2) {
      case 'S':
        f_short_time.checked = true;
        break;
      case 'I':
        f_in_out.checked = true;
        break;
    }
  } else {
    var callsign = entered_data;
  }

  f_source.value = callsign;
  var ci_count = 0;
  var ci_net_count = 0;

  // CALL API TO LOOK UP CALLSIGN

  try {
    const response = await fetch(`${dir_path}/api/lookup?callsign=${encodeURIComponent(callsign)}&net_control=${encodeURIComponent(f_net_control.value)}&net_id=${encodeURIComponent(net_id)}`);

    if (response.ok) {
      const json_data = await response.json();

      if (json_data.status === 'SUCCESS') {
        if (f_source.value !== json_data.callsign) {
          f_source.value = json_data.callsign;
        }

        f_preferred_name.value = json_data.preferred_name || '';
        f_location.value = json_data.location || '';
        f_notes.value = json_data.notes || '';
        f_lid.checked = json_data.lid || false;
        ci_count = json_data.ci_count;
        ci_net_count = json_data.ci_net_count;
        f_update_user.src = 'images/save_green.png';
        f_update_user.title = 'Add/Update User Info';
      } else if (json_data.status === 'NOT_FOUND') {
        f_update_user.src = 'images/save_orange.png';
        f_update_user.title = 'Callsign Not Found';
      } else if (json_data.status == "SHORT_SEARCH") {
        f_update_user.src = 'images/save_red.png';
        f_update_user.title = 'Callsign too short for search';
      } else if (json_data.status == "MULTIPLE_RECORDS_FOUND") {
        const f_table_callsigns = document.querySelector('#table_callsigns tbody');
        f_table_callsigns.innerHTML = "";

        var counter = 0;

        json_data.visitors.forEach(row_data => {
          counter++;
          const new_row = document.createElement('tr');

          new_row.innerHTML = `
            <td><button type="button" name="c${counter}" value="${row_data['callsign']}" class="button_blue_mb" onclick="select_callsign(this);">${row_data['callsign']}</button></td>
            <td><div class="td_pm">${row_data['preferred_name']}</div></td>
            <td><div class="td_pm">${row_data['location']}</div></td>
          `;

          f_table_callsigns.appendChild(new_row);
        });

        f_stored_element = f_source;
        show_callsign();
      } else {
        f_update_user.src = 'images/save_red.png';
        f_update_user.title = 'Error performing lookup';
      }
    } else {
      console.error('API call failed');
      f_update_user.src = 'images/save_red.png';
      f_update_user.title = 'ERROR CALLING API';
    }
  } catch (error) {
    console.error('API call failed:', error);
    f_update_user.src = 'images/save_red.png';
    f_update_user.title = 'ERROR CALLING API';
  }

  // SET HOVER TEXT FOR CALLSIGN

  var vis_count = 'Total Net Check-ins: ' + ci_count

  if (f_net_control.value) {
    vis_count += `\n${f_net_control.value} Net Check-ins: ${ci_net_count}`
  }

  f_source.title = vis_count;
  f_delete_row.src = 'images/delete_red.png';

  update_counter();
  update_buttons();
  update_live_logs();
  update_row_highlight(current_row);
}

// CAPTURE ENTER KEY TO MOVE FOCUS TO THE NEXT LOG ROW

function move_cursor(event, f_source) {
  if (event.key === 'Enter') {
    event.preventDefault();
    focus_callsign(f_source);
  }
}

// CAPTURE ESC KEY WHEN CALLSIGN OVERLAY IS OPEN

function overlay_handler_callsign(event) {
  if (event.key === 'Escape') {
    document.removeEventListener('keydown', overlay_handler_callsign);
    close_callsign();
  }
}

// PRELOAD IMAGES

function preload_images() {

  const image_list = [
    'images/delete_red.png',
    'images/save_red.png',
    'images/save_green.png',
    'images/save_orange.png'
  ];

  image_list.forEach(image => {
    const img = new Image();
    img.src = image;
  });
}

// SELECT CALLSIGN

function select_callsign(f_source) {
  f_stored_element.value = f_source.value;
  close_callsign();
  lookup_user(f_stored_element, true);
  focus_callsign(f_stored_element);
  f_stored_element = '';
}

// SHOW CALLSIGN OVERLAY

function show_callsign() {
  const f_overlay_callsign = document.getElementById("overlay_callsign");

  document.addEventListener('keydown', overlay_handler_callsign);
  f_overlay_callsign.style.display = 'flex';
}

// SEND LOGS TO SERVER

async function submit_logs(append) {
  if (data_in_flight) return;
  data_in_flight = true;

  const f_log_date = document.getElementById('log_date');
  const selected_date = new Date(f_log_date.value);
  const tz_offset = selected_date.getTimezoneOffset() / 60;

  selected_date.setHours(selected_date.getHours() + tz_offset);

  // BUILD META PORTION OF JSON DATA

  const payload = {
    meta: {
      net_id: net_id,
      log_date: f_log_date.value,
      day_of_week: selected_date.getDay(),
      net_control: f_net_control.value,
      append: append
    },
    visitors: []
  };

  // LOOP THROUGH TABLE ROW AND BUILD VISITORS PORTION OF JSON DATA

  const rows = document.querySelectorAll('#table_logs tbody tr');

  rows.forEach(current_row => {
    const f_callsign = current_row.querySelector('.input_callsign');

    if (f_callsign && f_callsign.value.trim() !== '') {
      payload.visitors.push({
        callsign: f_callsign.value.toUpperCase(),
        announcement: current_row.querySelector('.announcement').checked,
        mobile: current_row.querySelector('.mobile').checked,
        portable: current_row.querySelector('.portable').checked,
        echolink: current_row.querySelector('.echolink').checked,
        short_time: current_row.querySelector('.short_time').checked,
        in_out: current_row.querySelector('.in_out').checked,
        coupin: current_row.querySelector('.coupin').checked
      });
    }
  });

  // CALL API TO UPLOAD DATA

  try {
    const response = await fetch(`${dir_path}/api/uploadcheckins`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (response.ok) {
      const json_data = await response.json();

      if (json_data.status == 'SUCCESS') {
        const count = json_data.count;
        message = `${count} record`

        if (count != 1) {
          message += 's';
        }

        message += ' successfully uploaded'
        alert(message);
        unsaved_changes = false;

        if (enable_live_log) {
          toggle_live_log();
        }
      } else if (json_data.status == 'LOG_EXISTS') {
        if (confirm(`Logs for ${f_log_date.value} already exist!\n\nAppend to existing log?\n\nTHIS WILL APPEND ALL ENTRIES IN THE CURRENT LOG TO THE EXISTING LOG. PLEASE MAKE SURE YOU ONLY HAVE NEW ENTRIES LISTED BEFORE SUBMITTING`)) {
          data_in_flight = false;
          submit_logs(true)
        }
      } else {
        alert('Failed to upload data!');
      }
    } else {
      alert('Invalid server response!');
    }
  } catch (error) {
    alert('Upload Error:', error);
  }
  data_in_flight = false;
}

// TOGGLE AUTO HIDE

function toggle_auto_hide() {
  const f_auto_hide = document.getElementById('auto_hide');
  const f_log_header_checkout = document.querySelector('.th_log_checkout');
  auto_hide = !auto_hide

  if (auto_hide) {
    f_auto_hide.textContent = 'Disable Auto Hide';
    f_log_header_checkout.style.backgroundColor = '#004f00';
  } else {
    f_auto_hide.textContent = 'Enable Auto Hide';
    f_log_header_checkout.style.backgroundColor = '#003fbf';
  }

  update_auto_hide();
}

// TOGGLE LIVE LOG

async function toggle_live_log() {
  if (f_net_control.value.trim() == '') return;

  const f_btn_live_log = document.getElementById('btn_live_log');
  enable_live_log = !enable_live_log

  if (enable_live_log) {
    f_btn_live_log.title = 'Disable Live';
    f_btn_live_log.style.border = c_upd_btn_en_bdr;
    io_logs.emit('log-open', net_id);
    update_live_logs();
  } else {
    f_btn_live_log.title = 'Go Live';
    f_btn_live_log.style.border = c_upd_btn_dis_bdr;
    io_logs.emit('log-close', net_id);
  }
}

// TOGGLE SHORTCUT PANEL

function toggle_shortcuts() {
  const f_shortcuts = document.getElementById('shortcuts');
  const f_footer_shortcuts = document.getElementById('footer_shortcuts');
  const computedStyles = window.getComputedStyle(f_shortcuts);

  if (computedStyles.display == 'none') {
    f_shortcuts.style.display = 'block';
    f_footer_shortcuts.innerHTML = 'Hide Shortcuts / Legend &#9650;'
  } else {
    f_shortcuts.style.display = 'none';
    f_footer_shortcuts.innerHTML = 'View Shortcuts / Legend &#9660;'
  }
}

// SHOW/HIDE CHECKED OUT ROWS

function update_auto_hide() {
  if (document.getElementById('check_ins').textContent  === '0') return;

  const rows = document.querySelectorAll('#table_logs tbody tr');

  rows.forEach(current_row => {
    const f_callsign = current_row.querySelector('.input_callsign');

    if (f_callsign && f_callsign.value.trim() !== '') {
      if (auto_hide && current_row.querySelector('.checkout').checked) {
        current_row.style.display = 'none';
      } else {
        current_row.style.display = 'table-row';
      }
    }
  });
}

// SET UPLOAD BUTTON STATE AND HOVER TEXT

function update_buttons() {
  const f_btn_upload_logs = document.getElementById('btn_upload_logs');
  const f_btn_live_log = document.getElementById('btn_live_log');
  const f_check_ins = document.getElementById('check_ins');

  if (f_net_control.value.trim() !== '') {
    if (f_check_ins.textContent > 0) {

      // ENABLED

      f_btn_upload_logs.style.border = c_upd_btn_en_bdr;
      f_btn_upload_logs.title = 'Submit Log';
      f_btn_upload_logs.disabled = false;
    } else {

      // DISABLED

      f_btn_upload_logs.style.border = c_upd_btn_dis_bdr;
      f_btn_upload_logs.disabled = true;

      if (f_net_control.value.trim() == '') {
        f_btn_upload_logs.title = 'Set Net Control';
      } else {
        f_btn_upload_logs.title = 'No Visitors to Submit';
      }

      f_btn_live_log.disabled = false;
      f_btn_live_log.title = 'Go Live';
    }
  } else {
    f_btn_live_log.disabled = true;
    f_btn_live_log.title = 'Set Net Control';
  }
}

// UPDATE FIELD TO UPPER CASE

function update_case(f_source) {
  const callsign = f_source.value.toUpperCase();
  f_source.value = callsign;
  update_buttons();
}

// UPDATE ROW HIGHLIGHTING WHEN CHECK OUT IS CHANGED

function update_checkout(f_source) {
  const current_row = f_source.closest('tr');

  if (!current_row.querySelector('.input_callsign').value.trim()) {
    f_source.checked = false;
    return;
  }

  update_row_highlight(current_row);
  update_auto_hide();
  update_live_logs();
}

// UPDATE CHECK-IN / VISITOR COUNTER

function update_counter() {
  const rows = document.querySelectorAll('#table_logs tbody tr');
  var counter = 0;
  const callsigns = [];

  rows.forEach(current_row => {
    const f_callsign = current_row.querySelector('.input_callsign');

    if (f_callsign && f_callsign.value.trim() !== '') {
      if (!callsigns.includes(f_callsign.value)) {
        callsigns.push(f_callsign.value);
      }

      counter ++;
    }
  });

  const unique = callsigns.length;
  var unique_text = '(' + unique + ' Visitor';

  if (unique !== 1) {
    unique_text += 's';
  }

  unique_text += ')';

  document.getElementById('check_ins').textContent = counter;
  document.getElementById('check_ins_unique').textContent = unique_text;
}

// HIGHLIGHT ROW IF IN/OUT CHANGED

function update_in_out(f_source) {
  const current_row = f_source.closest('tr');

  if (!current_row.querySelector('.input_callsign').value.trim()) {
    f_source.checked = false;
    return;
  }

  update_row_highlight(current_row);
  update_live_logs();
}

// VERIFY CHANGE IF LID STATUS CHANGES

function update_lid(f_source) {
  const current_row = f_source.closest('tr');

  if (!current_row.querySelector('.input_callsign').value.trim()) {
    f_source.checked = false;
    return;
  }

  const f_source_state = f_source.checked;

  if (confirm('Change Lid Status?')) {
    update_row_highlight(current_row);
  } else {
    f_source.checked = !f_source_state;
  }
}

// UPDATE LIVE LOGS

async function update_live_logs() {
  if (!enable_live_log) return;

  // BUILD META PORTION OF JSON DATA

  const payload = {
    meta: {
      net_control: f_net_control.value,
      net_id: net_id
    },
    visitors: []
  };

  // LOOP THROUGH TABLE ROW AND BUILD VISITORS PORTION OF JSON DATA

  const rows = document.querySelectorAll('#table_logs tbody tr');

  rows.forEach(current_row => {
    const f_callsign = current_row.querySelector('.input_callsign');

    if (f_callsign && f_callsign.value.trim() !== '') {
      payload.visitors.push({
        callsign: f_callsign.value.toUpperCase(),
        announcement: current_row.querySelector('.announcement').checked,
        mobile: current_row.querySelector('.mobile').checked,
        portable: current_row.querySelector('.portable').checked,
        echolink: current_row.querySelector('.echolink').checked,
        short_time: current_row.querySelector('.short_time').checked,
        in_out: current_row.querySelector('.in_out').checked,
        coupin: current_row.querySelector('.coupin').checked,
        preferred_name: current_row.querySelector('.preferred_name').value,
        location: current_row.querySelector('.location').value,
        notes: current_row.querySelector('.notes').value,
        checked_out: current_row.querySelector('.checkout').checked,
        lid: current_row.querySelector('.lid').checked
      });
    }
  });

  io_logs.emit('log-update', {log: payload})
}

// UPDATE ROW BACKGROUND COLOR BASED ON STATUS

function update_row_highlight(row) {
  const f_in_out = row.querySelector('.in_out');
  const f_checkout = row.querySelector('.checkout');
  const f_lid = row.querySelector('.lid');

  if (f_checkout.checked) {
    row.style.backgroundColor = c_hlgt_checkout;
    f_lid.classList.remove('blink');
  } else if (f_lid.checked) {
    row.style.backgroundColor = c_hlgt_lid;
    f_lid.classList.add('blink');
  } else if (f_in_out.checked) {
    row.style.backgroundColor = c_hlgt_in_out;
  } else {
    f_lid.classList.remove('blink');
    row.style.backgroundColor = '';
  }
}

// UPDATE VISITOR INFORMATION

async function update_user(f_source) {
  const current_row = f_source.closest('tr');
  const f_callsign = current_row.querySelector('.input_callsign');

  if (!f_callsign.value.trim()) return;

  const f_preferred_name = current_row.querySelector('.preferred_name');
  const f_location = current_row.querySelector('.location');
  const f_notes = current_row.querySelector('.notes');
  const f_net_control = document.getElementById('net_control');

  // BEAVIS DETECTION

  if (f_preferred_name.value === '') {
    alert('USER INFORMATION UPDATE FAILED\n\nPreferred Name is a Required Field');
    lookup_user(f_callsign, true);
    return;
  }

  const user_data = {
    callsign: f_callsign.value.toUpperCase(),
    preferred_name: f_preferred_name.value,
    location: f_location.value,
    notes: f_notes.value,
    lid: current_row.querySelector('.lid').checked
  };

  // CALL API

  try {
    const response = await fetch(`${dir_path}/api/updateuser`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(user_data)
    });

    if (response.ok) {
      const json_data = await response.json();

      if (json_data.status === "SUCCESS") {
        alert('User information successfully saved');
        f_source.src = 'images/save_green.png';
        f_source.title = 'Add/Update User Info';
      } else {
        alert('Failed to update user information');
        f_source.src = 'images/save_red.png';
        f_source.title = 'Update failed';
      }
    } else {
      alert('Invalid server response!');
      f_source.src = 'images/save_red.png';
      f_source.title = 'Invalid server response';
    }
  } catch (error) {
    alert('Error calling API');
    f_source.src = 'images/save_red.png';
    f_source.title = 'ERROR CALLING API';
  }

  update_live_logs();
}

window.addEventListener('beforeunload', (event) => {
  if (unsaved_changes) {
    event.preventDefault();
    event.returnValue = '';
  }
});

document.getElementById('log_date').value = f_header_date.textContent;
preload_images();
add_rows(1);
update_buttons();

if (f_net_control.value.trim() != '') {
  const focus_field = f_table_logs.rows[0].querySelector('.input_callsign');

  focus_field.focus();
}
