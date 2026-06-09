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

  REVISION 20260609.01

*/

const io_chat = io("/chat");

const f_jars_chat = document.getElementById('jars_chat');
const f_chat_icon = document.getElementById('chat_icon');
const f_input_chat = document.getElementById('input_chat');
const f_chat_container = document.getElementById('chat_container');

var chat_visible = false;
var last_sender = '';

// CHECK IF RUNING FROM LOG ENTRY OR LIVE LOG VIEWER

if (document.getElementById('log_date')) {
  var net_control = true;
} else {
  var net_control = false;
  const f_chat_callsign = document.getElementById('input_chat_callsign');
  const random_hex = Math.floor(Math.random() * 0x10000).toString(16).padStart(4, '0');
  f_chat_callsign.value = 'Guest_' + random_hex;
}

// CAPTURE ENTER KEY TO SEND MESSAGE

f_input_chat.addEventListener('keydown', (event) => {
  if (event.key === 'Enter') {
    send_message();
  }
})

// CONNECT TO SERVER

io_chat.on('connect', () => {
  const net_id = document.getElementById('title_text').dataset.id;

  // SET CHAT SENDER NAME

  if (!net_control) {
    var sender_id = document.getElementById('input_chat_callsign').value;
  } else {
    var sender_id = document.getElementById('net_control').value;

    if (sender_id == '') {
      sender_id = 'Net Control';
    }
  }

  // JOIN CHAT ROOM

  io_chat.emit("join", {room: net_id, 'sender': sender_id});
  f_chat_icon.src = 'images/chat_green.png';
})

// UPDATE COUNT OF ROOM MEMBERS

io_chat.on('stat-members', (counter) => {
  document.getElementById('chat_count').textContent = `[${counter}]`;
})

// UPDATE ICON ON DISCONNECT

io_chat.on('disconnect', () => {
  f_chat_icon.src = 'images/chat_red.png';
})

// SHOW USER HAS LEFT THE ROOM

io_chat.on('leave', (msg) => {
  const f_chat_message_list = document.getElementById('chat_message_list');
  const new_div = document.createElement('div');

  new_div.className = 'chat_message sender';
  new_div.textContent = msg + ' left the chat';
  f_chat_message_list.appendChild(new_div);
  f_chat_message_list.scrollTop = f_chat_message_list.scrollHeight;
  last_sender = '';
})

// SHOW USER HAS JOINED THE ROOM

io_chat.on('join', (msg) => {
  const f_chat_message_list = document.getElementById('chat_message_list');
  const new_div = document.createElement('div');

  new_div.className = 'chat_message sender';
  new_div.textContent = msg + ' joined the chat';
  f_chat_message_list.appendChild(new_div);
  f_chat_message_list.scrollTop = f_chat_message_list.scrollHeight;
  last_sender = '';
})

// SHOW SENDER NAME UPDATE

io_chat.on('name-change', (msg) => {
  const f_chat_message_list = document.getElementById('chat_message_list');
  const new_div = document.createElement('div');

  new_div.className = 'chat_message sender';
  new_div.textContent = msg.old + ' updated to ' + msg.new;
  f_chat_message_list.appendChild(new_div);
  f_chat_message_list.scrollTop = f_chat_message_list.scrollHeight;
  last_sender = '';
})

// SHOW RECEIVED MESSAGE

io_chat.on('message', (msg) => {
  const f_chat_message_list = document.getElementById('chat_message_list');

  if (last_sender != msg.sender) {
    var new_div = document.createElement('div');
    new_div.className = 'chat_message sender';
    new_div.textContent = msg.sender + ':';
    f_chat_message_list.appendChild(new_div);
    last_sender = msg.sender;
  }

  var new_div = document.createElement('div');

  if (msg.sender == 'Admin') {
    new_div.className = 'chat_message admin';
  } else {
    new_div.className = 'chat_message received';
  }

  const link_message = add_links(msg.message);

  new_div.innerHTML = link_message;
  f_chat_message_list.appendChild(new_div);
  f_chat_message_list.scrollTop = f_chat_message_list.scrollHeight;

  const computedStyles = window.getComputedStyle(f_chat_container);

  if (computedStyles.display == 'none' && msg.sender != 'Admin') {
    f_chat_icon.classList.add('fade');
  }
})

// CONVERT LINKS TO CLICKABLE HTML

function add_links(message) {
  const url_regex = /(((https?:\/\/)|(www\.))[^\s]+)/g;

  return message.replace(url_regex, (url) => {
    let hyperlink = url;
    if (!hyperlink.match('^https?:\/\/')) {
      hyperlink = 'http://' + hyperlink;
    }
    return `<a href="${hyperlink}" target="_blank" rel="noopener noreferrer">${url}</a>`;
  });
}

// SEND CHAT MESSAGE

function send_message() {
  const message = document.getElementById('input_chat').value;

  if (!net_control) {
    var sender_id = document.getElementById('input_chat_callsign').value;
  } else {
    var sender_id = document.getElementById('net_control').value;

    if (sender_id == '') {
      sender_id = 'Net Control';
    }
  }

  if (message) {
    const net_id = document.getElementById('title_text').dataset.id;
    const f_chat_message_list = document.getElementById('chat_message_list');

    io_chat.emit("message", {'net_id': net_id, 'sender': sender_id, 'message': message});

    const new_div = document.createElement('div');
    const link_message = add_links( message);

    new_div.className = 'chat_message sent';
    new_div.innerHTML = link_message;
    f_chat_message_list.appendChild(new_div);
    f_input_chat.value = '';
    f_chat_message_list.scrollTop = f_chat_message_list.scrollHeight;
    last_sender = '';
  }
}

// UPDATE SENDER

function sender_update() {
  const net_id = document.getElementById('title_text').dataset.id;
  const sender_id = document.getElementById('input_chat_callsign').value;

  io_chat.emit("update-sender", {'net_id': net_id, 'sender': sender_id});
}

// SHOW/HIDE CHAT WINDOW

function toggle_chat() {
  chat_visible = !chat_visible;

  if (chat_visible) {
    f_chat_container.style.display = "flex";
    f_chat_icon.classList.remove('fade');
    f_input_chat.focus();
  } else {
    f_chat_container.style.display = "none";
  }
}
