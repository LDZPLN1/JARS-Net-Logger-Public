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

const io_logs = io('/logs');

// ENABLE/DISABLE LOGIN BUTTON

function check_login() {
  const f_username = document.getElementsByName('username');
  const f_password = document.getElementsByName('password');
  const f_button_login = document.getElementById('button_login');

  if (f_username[0].value.trim() == '' || f_password[0].value.trim() == '') {
    f_button_login.style.borderColor = '#ff0000';
    f_button_login.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(255, 0, 0, 0.8)';
    f_button_login.disabled = true;
  } else {
    f_button_login.style.borderColor = '#00ff00';
    f_button_login.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(0, 255, 0, 0.6)';
    f_button_login.disabled = false;
  }
}

// UPDATE GUEST LOGIN BUTTON

function update_guest_login() {
  const f_button_live_log = document.getElementById('button_live_log');
  const f_net_list = document.getElementById('net_list');
  const net_active = f_net_list.selectedOptions[0].dataset.id;

  if (net_active == 1) {
    f_button_live_log.style.borderColor = '#00ff00';
    f_button_live_log.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(0, 255, 0, 0.6)';
    f_button_live_log.disabled = false;
  } else {
    f_button_live_log.style.borderColor = '#ff0000';
    f_button_live_log.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(255, 0, 0, 0.8)';
    f_button_live_log.disabled = true;
  }
}

if (document.getElementById('net_list')) {
  io_logs.on('connect', () => {
    io_logs.emit("log-list");
  })

  io_logs.on('log-list', (msg) => {
    const f_net_list = document.getElementById('net_list');

    for (let i = 0; i < f_net_list.options.length; i++) {
      const f_option = f_net_list.options[i];

      if (msg.includes(f_option.value)) {
        f_option.dataset.id = '1';
      } else {
        f_option.dataset.id = '0';
      }
    }

    const f_button_live_log = document.getElementById('button_live_log');
    const net_active = f_net_list.selectedOptions[0].dataset.id;

    if (net_active == 1) {
      f_button_live_log.style.borderColor = '#00ff00';
      f_button_live_log.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(0, 255, 0, 0.6)';
      f_button_live_log.disabled = false;
    } else {
      f_button_live_log.style.borderColor = '#ff0000';
      f_button_live_log.style.boxShadow = 'inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(255, 0, 0, 0.8)';
      f_button_live_log.disabled = true;
    }
  })
}

document.getElementById('button_login').disabled = true;
