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

const f_btn_change_password = document.getElementById('btn_change_password');
const f_old = document.getElementById('old_password');
const f_new1 = document.getElementById('new_password_1');
const f_new2 = document.getElementById('new_password_2');
const f_cap = document.getElementById('pass_cap');
const f_lwr = document.getElementById('pass_lwr');
const f_num = document.getElementById('pass_num');
const f_len = document.getElementById('pass_len');

// CHECK PASSWORD DURING CHANGE

function check_password() {
  const test = f_old.value.trim();

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
      f_new1.style.borderColor = '#007f00';
      f_new1.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(0, 127, 0, 0.6)'
      f_new2.style.boxShadow = 'none';
    } else if (f_new1.value.trim() == f_new2.value.trim()) {
      f_new1.style.borderColor = '#00ff00';
      f_new1.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(0, 255, 0, 0.6)'
      f_new2.style.borderColor = '#00ff00';
      f_new2.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(0, 255, 0, 0.6)'
    } else {
      f_new1.style.borderColor = '#007f00';
      f_new1.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(0, 127, 0, 0.6)'
      f_new2.style.borderColor = '#ff0000';
      f_new2.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(255, 0, 0, 0.8)'
    }
  } else {
      f_new1.style.borderColor = '#bfbfbf';
      f_new1.style.boxShadow = 'none';
      f_new2.style.borderColor = '#bfbfbf';
      f_new2.style.boxShadow = 'none';
  }

  if (pw_state == 4 && f_new1.value.trim() == f_new2.value.trim() && test != '') {
    f_btn_change_password.disabled = false;
    f_btn_change_password.style.borderColor = '#00ff00';
    f_btn_change_password.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(0, 255, 0, 0.6)'
  } else {
    f_btn_change_password.disabled = true;
    f_btn_change_password.style.borderColor = '#ff0000';
    f_btn_change_password.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(255, 0, 0, 0.8)'
  }
}

f_btn_change_password.disabled = true;
