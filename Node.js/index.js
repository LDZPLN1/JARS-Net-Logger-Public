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

const express = require('express');
const { createServer } = require('http');
const { Server } = require('socket.io');

const app = express();
const http_server = createServer(app);
const io = new Server(http_server, {});
const chat = io.of('/chat');
const logs = io.of('/logs');

const id_map = new Map();

chat.on('connection', (socket) => {
  socket.emit("message", {'sender': 'Admin', 'message': 'Welcome to JARS Chat'});

  socket.on("disconnecting", (reason) => {
    for (const room of socket.rooms) {
      if (room !== socket.id) {
        const count = chat.adapter.rooms.get(room).size - 1;
        chat.to(room).emit('stat-members', count);
        socket.to(room).emit('leave', id_map.get(socket.id));
        id_map.delete(socket.id);
      }
    }
  })

  socket.on('disconnect', () => {
  })

  socket.on('join', (msg) => {
    socket.join(msg.room);
    id_map.set(socket.id, msg.sender);
    const count = chat.adapter.rooms.get(msg.room).size;
    chat.to(msg.room).emit('stat-members', count);
    socket.to(msg.room).emit('join', msg.sender);
  })

  socket.on('message', (msg) => {
    socket.to(msg.net_id).emit('message', msg);
  })

  socket.on('update-sender', (msg) => {
    const current_name = id_map.get(socket.id);

    if (current_name != msg.sender) {
      id_map.set(socket.id, msg.sender);
      socket.to(msg.net_id).emit('name-change', {'old': current_name, "new": msg.sender});
    }
  })
})

logs.on('connection', (socket) => {
  socket.on('log-close', (msg) => {
    socket.leave(msg);

    const all_rooms = logs.adapter.rooms;
    const socket_ids = logs.adapter.sids;
    const room_list = [...all_rooms.keys()].filter(room => !socket_ids.has(room));

    logs.emit('log-list', room_list);

    chat.to(msg).emit('message', {net_id: msg, sender: 'Admin', message: 'Chat closing in 30 seconds'});
    setTimeout(send_close, 30000, msg);
    id_map.clear();
  })

  socket.on('log-join', (msg) => {
    socket.join(msg);
  })

  socket.on('log-list', () => {
    const all_rooms = logs.adapter.rooms;
    const socket_ids = logs.adapter.sids;
    const room_list = [...all_rooms.keys()].filter(room => !socket_ids.has(room));

    socket.emit('log-list', room_list);
  })

  socket.on('log-open', (msg) => {
    socket.join(msg);

    const all_rooms = logs.adapter.rooms;
    const socket_ids = logs.adapter.sids;
    const room_list = [...all_rooms.keys()].filter(room => !socket_ids.has(room));

    logs.emit('log-list', room_list);
  })

  socket.on('log-request', (msg) => {
    logs.to(msg).emit('log-request');
  })

  socket.on('log-update', (msg) => {
    logs.to(msg.log.meta.net_id).emit('log-data', msg);
  })

  function send_close(room) {
    logs.to(room).emit('log-shutdown');
  }
})

http_server.listen(3000, () => {
  console.log('Server is running on http://localhost:3000');
})
