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

  REVISION 20260522.01

*/

const dir_path = '/jars';

// ENABLE/DISABLE ADD BUTTON

function ann_check(f_source) {
  const current_row = f_source.closest('tr');
  const f_img_update_ann = current_row.querySelector('.img_update_ann')

  if (f_source.value.trim() == '') {
    f_img_update_ann.src = 'images/save_gs.png';
    f_img_update_ann.disabled = true;
  } else {
    f_img_update_ann.src = 'images/save_green.png';
    f_img_update_ann.disabled = false;
  }
}

// DELETE ANNOUNCEMENT

function ann_delete(f_source) {
  const f_ann_form = document.getElementById('ann_form');
  const current_row = f_source.closest('tr');

  document.getElementById('ann_record_id').value = current_row.querySelector('.textarea_ann').dataset.id;
  document.getElementById('ann_mode').value = 'delete';
  f_ann_form.submit();
}

// ADD/UPDATE ANNOUNCEMENT

function ann_update(f_source) {
  const f_ann_form = document.getElementById('ann_form');
  const f_ann_end_date = document.getElementById('ann_end_date');
  const current_row = f_source.closest('tr');
  const f_end_date = current_row.querySelector('.end_date');
  const f_cbx_no_end = current_row.querySelector('.cbx_no_end');

  document.getElementById('ann_record_id').value = current_row.querySelector('.textarea_ann').dataset.id;
  document.getElementById('ann_message').value = current_row.querySelector('.textarea_ann').value;
  document.getElementById('ann_start_date').value = current_row.querySelector('.start_date').value;
  document.getElementById('ann_mode').value = 'change';

  var end_date = '';

  if (!f_cbx_no_end.checked) {
    var end_date = f_end_date.value;
  }

  f_ann_end_date.value = end_date;
  f_ann_form.submit();
}

// CHECK FIELD LENGTH

function check_length(f_source) {
  const f_btn_update_callsign = document.getElementById('btn_update_callsign');
  const f_valid_callsign = document.getElementById('valid_callsign');
  const f_old_callsign = document.getElementById('old_callsign');
  const f_new_callsign = document.getElementById('new_callsign');
  const f_old_chg_callsign = document.getElementById('old_chg_callsign');
  const f_new_chg_callsign = document.getElementById('new_chg_callsign');
  const f_old_call = document.getElementById('old_call');
  const f_new_call = document.getElementById('new_call');

  if (f_source.value.length >= 4 && f_valid_callsign.value == 1 && f_old_callsign.value.toUpperCase() != f_new_callsign.value.toUpperCase()) {
    f_btn_update_callsign.style.border = '2px solid #00ff00';
    f_btn_update_callsign.disabled = false;
    f_old_call.value = f_old_callsign.value.trim().toUpperCase();
    f_new_call.value = f_new_callsign.value.trim().toUpperCase();
    f_old_chg_callsign.textContent = f_old_callsign.value.trim().toUpperCase();
    f_new_chg_callsign.textContent = f_new_callsign.value.trim().toUpperCase();
  } else {
    f_btn_update_callsign.style.border = '2px solid #ff0000';
    f_btn_update_callsign.disabled = true;
    f_old_call.value = '';
    f_new_call.value = '';
    f_old_chg_callsign.textContent = '';
    f_new_chg_callsign.textContent = '';
  }
}

// CHECK NET NAME DURING CHANGE

function check_net_name(mode) {
  if (mode == 1) {
    var f_net = document.getElementById('add_net_name');
    var f_button = document.getElementById('btn_net_create');
  } else if (mode == 2) {
    var f_net = document.getElementById('chg_net_name');
    var f_button = document.getElementById('btn_net_change');
  } else {
    return;
  }

  if (f_net.value != '') {
    f_button.disabled = false;
    f_button.style.border = '2px solid #00ff00';
  } else {
    f_button.disabled = true;
    f_button.style.border = '2px solid #ff0000';
  }
}

// CHECK PASSWORD DURING CHANGE

function check_password(mode) {
  if (mode == 1) {
    var f_old = document.getElementById('add_username');
    var f_new1 = document.getElementById('add_password_1');
    var f_new2 = document.getElementById('add_password_2');
    var f_button = document.getElementById('btn_add');
    var f_cap = document.getElementById('add_pass_cap');
    var f_lwr = document.getElementById('add_pass_lwr');
    var f_num = document.getElementById('add_pass_num');
    var f_len = document.getElementById('add_pass_len');
    var test = f_old.value.trim();
  } else if (mode == 2) {
    var f_new1 = document.getElementById('chg_password_1');
    var f_new2 = document.getElementById('chg_password_2');
    var f_button = document.getElementById('btn_chg');
    var f_cap = document.getElementById('chg_pass_cap');
    var f_lwr = document.getElementById('chg_pass_lwr');
    var f_num = document.getElementById('chg_pass_num');
    var f_len = document.getElementById('chg_pass_len');
    var test = '*';
  } else {
    return;
  }

  const test_cap = (str) => /[A-Z]/.test(str);
  const test_lwr = (str) => /[a-z]/.test(str);
  const test_num = (str) => /[0-9]/.test(str);

  var pw_state = 0;

  if (test_cap(f_new1.value)) {
    f_cap.style.color = '#00ff00';
    pw_state ++;
  } else {
    f_cap.style.color = '#ff0000';
  }

  if (test_lwr(f_new1.value)) {
    f_lwr.style.color = '#00ff00';
    pw_state ++;
  } else {
    f_lwr.style.color = '#ff0000';
  }

  if (test_num(f_new1.value)) {
    f_num.style.color = '#00ff00';
    pw_state ++;
  } else {
    f_num.style.color = '#ff0000';
  }

  if (f_new1.value.trim().length >= 8) {
    f_len.style.color = '#00ff00';
    pw_state ++;
  } else {
    f_len.style.color = '#ff0000';
  }

  f_len.textContent = f_new1.value.trim().length;

  if (pw_state == 4) {
    if (f_new2.value.trim() == '') {
      f_new1.style.outline = '2px solid #007f00';
      f_new2.style.outline = 'none';
    } else if (f_new1.value.trim() == f_new2.value.trim()) {
      f_new1.style.outline = '2px solid #00ff00';
      f_new2.style.outline = '2px solid #00ff00';
    } else {
      f_new1.style.outline = '2px solid #007f00';
      f_new2.style.outline = '2px solid #ff0000';
    }
  } else {
      f_new1.style.outline = 'none';
      f_new2.style.outline = 'none';
  }

  if (pw_state == 4 && f_new1.value.trim() == f_new2.value.trim() && test != '') {
    f_button.disabled = false;
    f_button.style.border = '2px solid #00ff00';
  } else {
    f_button.disabled = true;
    f_button.style.border = '2px solid #ff0000';
  }
}

// HIDE ADD OVERLAYS

function close_add() {
  const f_overlay_add = document.getElementById("overlay_add");
  f_overlay_add.style.display = 'none';
}

// HIDE CHANGE OVERLAYS

function close_change() {
  const f_overlay_chg = document.getElementById("overlay_chg");
  f_overlay_chg.style.display = 'none';
}

// HIDE DELETE OVERLAY

function close_delete() {
  const f_overlay_del = document.getElementById("overlay_del");
  f_overlay_del.style.display = 'none';
}

// HIDE DELETE OVERLAY

function close_delete_2() {
  const f_overlay_del_2 = document.getElementById("overlay_del_2");
  f_overlay_del_2.style.display = 'none';
}

// CAPTURE ENTER KEY TO MOVE FOCUS TO THE NEXT INPUT ROW

function move_cursor_callsign(event, f_source) {
  if (event.key === 'Enter') {
    const f_new_callsign = document.getElementById('new_callsign');
    f_new_callsign.focus();
  }
}

// MARK NETS ACTIVE/INACTIVE IN NET LIST

function colorize_net_list() {
  const f_net_list = document.getElementById('net_list')

  for (let i = 0; i < f_net_list.options.length; i++) {
    const f_option = f_net_list.options[i].dataset.id;

    if (f_option == 0) {
      f_net_list.options[i].style.color = '#cf0000';
    }
  }
}

// INIT USER MANAGER

function init_user_manager() {
  document.getElementById('btn_user_change').disabled = true;
  document.getElementById('btn_user_admin').disabled = true;
  document.getElementById('btn_user_delete').disabled = true;
}

// INIT UPDATE CALLSIGN

function init_update_callsign() {
  document.getElementById('btn_update_callsign').disabled = true;
}

// INIT NET MANAGER

function init_net_manager() {
  document.getElementById('btn_net_edit').disabled = true;
  document.getElementById('btn_net_active').disabled = true;
  document.getElementById('btn_net_delete').disabled = true;

  if (document.getElementById('net_list')) {
    colorize_net_list();
  }
}

// LOOKUP CALLSIGN AND GET DATA FOR CALLSIGN UPDATE

async function lookup_user_callsign(f_source, lookup_type) {
  const entered_data = f_source.value.trim().toUpperCase();
  const f_preferred_name = document.getElementById('preferred_name');

  if (!entered_data) return;

  f_source.value = entered_data;

  // CALL API TO LOOK UP CALLSIGN

  const f_new_callsign = document.getElementById('new_callsign');

  try {
    const response = await fetch(`${dir_path}/api/lookup?callsign=${encodeURIComponent(entered_data)}`);

    if (response.ok) {
      const json_data = await response.json();
      const f_valid_callsign = document.getElementById('valid_callsign');

      if (json_data.status === 'SUCCESS') {
        f_source.style.outline = '2px solid #00ff00';

        if (lookup_type == 0) {
          f_valid_callsign.value = 1;
          f_preferred_name.textContent = json_data['preferred_name'];
        }
      } else {
        if (lookup_type == 0) {
          f_preferred_name.textContent = '';
          f_source.style.outline = '2px solid #ff0000';
          f_valid_callsign.value = 0;
        } else {
          f_source.style.outline = '2px solid #ffFF00';
        }
      }
    } else {
      console.error('API call failed');
    }
  } catch (error) {
    console.error('API call failed:', error);
  }

  if (lookup_type == 0) {
    check_length(f_new_callsign);
  }
}

// CAPTURE ESC KEY WHEN ADD OVERLAY IS OPEN

function overlay_handler_add(event) {
  if (event.key === 'Escape') {
    document.removeEventListener('keydown', overlay_handler_add);
    close_add();
  }
}

// CAPTURE ESC KEY WHEN CHANGE OVERLAY IS OPEN

function overlay_handler_change(event) {
  if (event.key === 'Escape') {
    document.removeEventListener('keydown', overlay_handler_change);
    close_change();
  }
}

// CAPTURE ESC KEY WHEN DELETE OVERLAY IS OPEN

function overlay_handler_delete(event) {
  if (event.key === 'Escape') {
    document.removeEventListener('keydown', overlay_handler_delete);
    close_delete();
  }
}

// CAPTURE ESC KEY WHEN DELETE OVERLAY IS OPEN

function overlay_handler_delete_2(event) {
  if (event.key === 'Escape') {
    document.removeEventListener('keydown', overlay_handler_delete_2);
    close_delete_2();
  }
}

// SHOW ADD OVERLAY

function show_add() {
  const f_overlay_add = document.getElementById("overlay_add");
  const f_add_username = document.getElementById('add_username');
  const f_button = document.getElementById('btn_add');

  document.addEventListener('keydown', overlay_handler_add);
  f_button.disabled = true;
  f_overlay_add.style.display = 'flex';
  f_add_username.focus();
}

// SHOW ADD NET OVERLAY

function show_add_net() {
  const f_overlay_add = document.getElementById("overlay_add");
  const f_add_net_name = document.getElementById('add_net_name');
  const f_button = document.getElementById('btn_net_create');

  document.addEventListener('keydown', overlay_handler_add);
  f_add_net_name.value = '';
  f_button.style.border = '2px solid #ff0000';
  f_button.disabled = true;
  f_overlay_add.style.display = 'flex';
  f_add_net_name.focus();
}

// SHOW CHANGE PASSWORD OVERLAY

function show_change() {
  const f_overlay_chg = document.getElementById("overlay_chg");
  const f_chg_password_1 = document.getElementById('chg_password_1');
  const f_record_id = document.getElementById('record_id');
  const f_button = document.getElementById('btn_chg');
  const f_user_list = document.getElementById('user_list')
  const f_user_id = f_user_list.value;

  document.addEventListener('keydown', overlay_handler_change);
  f_button.disabled = true;
  f_overlay_chg.style.display = 'flex';
  f_chg_password_1.focus();
  f_record_id.value = f_user_id;
}

// SHOW CHANGE CALLSIGN OVERLAY

function show_change_callsign() {
  const f_overlay_chg = document.getElementById("overlay_chg");

  document.addEventListener('keydown', overlay_handler_change);
  f_overlay_chg.style.display = 'flex';
}

// SHOW CHANGE NET OVERLAY

function show_change_net() {
  const f_overlay_chg = document.getElementById("overlay_chg");
  const f_chg_net_name = document.getElementById('chg_net_name');
  const f_record_id = document.getElementById('record_id');
  const f_button = document.getElementById('btn_net_change');
  const f_net_list = document.getElementById('net_list')
  const f_net_id = f_net_list.value;
  const f_net_name = f_net_list.selectedOptions[0].textContent

  document.addEventListener('keydown', overlay_handler_change);
  f_button.disabled = true;
  f_overlay_chg.style.display = 'flex';
  f_chg_net_name.value = f_net_name;
  f_chg_net_name.focus();
  f_record_id.value = f_net_id;
}

// SHOW USER DELETE CONFIRMATION

function show_delete() {
  const f_overlay_del = document.getElementById("overlay_del");
  const f_record_id_del = document.getElementById('record_id_del');
  const f_del_user = document.getElementById("del_user");
  const f_user_list = document.getElementById('user_list')
  const f_user_id = f_user_list.value;
  const f_user_data = f_user_list.selectedOptions[0].dataset.id;
  const [username, guest] = f_user_data.split(':');

  document.addEventListener('keydown', overlay_handler_delete);
  f_del_user.textContent = username;
  f_overlay_del.style.display = 'flex';
  f_record_id_del.value = f_user_id;
}

// SHOW NET DELETE CONFIRMATION

function show_delete_1() {
  const f_overlay_del = document.getElementById("overlay_del");
  const f_record_id_del = document.getElementById('record_id_del');
  const f_del_net = document.getElementById("del_net");
  const f_net_list = document.getElementById('net_list')
  const f_net_id = f_net_list.value;
  const f_net_name = f_net_list.selectedOptions[0].textContent;

  document.addEventListener('keydown', overlay_handler_delete);
  f_del_net.textContent = f_net_name;
  f_overlay_del.style.display = 'flex';
  f_record_id_del.value = f_net_id;
}

// SHOW SECOND NET DELETE CONFIRMATION

function show_delete_2() {
  const f_overlay_del = document.getElementById("overlay_del");
  const f_overlay_del_2 = document.getElementById("overlay_del_2");

  document.addEventListener('keydown', overlay_handler_delete_2);
  f_overlay_del.style.display = 'none';
  f_overlay_del_2.style.display = 'flex';
}

// TOGGLE NO END DATE

function toggle_no_end(f_source) {
  const current_row = f_source.closest('tr');
  const f_end_date = current_row.querySelector('.end_date')

  if (f_source.checked) {
    f_end_date.disabled = true;
  } else {
    f_end_date.disabled = false;
  }

}

// UPDATE NET BUTTONS

function update_net_buttons() {
  const f_btn_net_edit = document.getElementById('btn_net_edit');
  const f_btn_net_active = document.getElementById('btn_net_active');
  const f_btn_net_delete = document.getElementById('btn_net_delete');

  f_btn_net_edit.disabled = false;
  f_btn_net_active.disabled = false;
  f_btn_net_delete.disabled = false;
  f_btn_net_edit.style.border = '2px solid #ff7f00';
  f_btn_net_active.style.border = '2px solid #ffff00';
  f_btn_net_delete.style.border = '2px solid #ff0000';
}

// UPDATE USER BUTTONS

function update_user_buttons() {
  const f_session_id = document.getElementById('session_id');
  const f_btn_user_change = document.getElementById('btn_user_change');
  const f_btn_user_admin = document.getElementById('btn_user_admin');
  const f_btn_user_delete = document.getElementById('btn_user_delete');
  const f_user_list = document.getElementById('user_list')
  const f_user_id = f_user_list.value;
  const f_user_data = f_user_list.selectedOptions[0].dataset.id;
  const [username, guest] = f_user_data.split(':');

  if (f_session_id.textContent == username) {
    f_btn_user_change.disabled = false;
    f_btn_user_admin.disabled = true;
    f_btn_user_delete.disabled = true;
    f_btn_user_change.style.border = '2px solid #ff7f00';
    f_btn_user_admin.style.border = '2px solid #4f4f4f';
    f_btn_user_delete.style.border = '2px solid #4f4f4f';
  } else if (guest == 1) {
    f_btn_user_change.disabled = false;
    f_btn_user_admin.disabled = true;
    f_btn_user_delete.disabled = false;
    f_btn_user_change.style.border = '2px solid #ff7f00';
    f_btn_user_admin.style.border = '2px solid #4f4f4f';
    f_btn_user_delete.style.border = '2px solid #ff0000 ';
  } else {
    f_btn_user_change.disabled = false;
    f_btn_user_admin.disabled = false;
    f_btn_user_delete.disabled = false;
    f_btn_user_change.style.border = '2px solid #ff7f00';
    f_btn_user_admin.style.border = '2px solid #ffff00';
    f_btn_user_delete.style.border = '2px solid #ff0000';
  }
}
