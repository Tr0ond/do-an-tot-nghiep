import WebSocket from 'ws'
import process from 'node:process'
import { readFileSync, writeFileSync, appendFileSync, existsSync } from 'node:fs'

// IPC chỉ dùng tài khoản và khóa giả của database kiểm thử biệt lập.
const [port, ipc] = process.argv.slice(2)
const sockets = []
const ids = []
let subscribed = 0
let authorized = false
for (let i = 0; i < 2; i++) {
  const socket = new WebSocket(
    `ws://127.0.0.1:${port}/app/chat-test-key?protocol=7&client=js&version=8.6.0`,
    {
      origin: 'http://localhost:5173',
    },
  )
  sockets.push(socket)
  socket.on('error', () => process.exit(1))
  socket.on('message', (raw) => {
    const event = JSON.parse(String(raw))
    if (event.event === 'pusher:connection_established') {
      ids[i] = JSON.parse(event.data).socket_id
      if (ids.filter(Boolean).length === 2) writeFileSync(`${ipc}.ids`, JSON.stringify(ids))
    }
    if (event.event === 'pusher_internal:subscription_succeeded') {
      subscribed++
      if (subscribed === 2) writeFileSync(`${ipc}.ready`, 'ok')
    }
    if (event.event === 'chat.cap-nhat') {
      appendFileSync(`${ipc}.events`, JSON.stringify({ client: i, ...event }) + '\n')
    }
  })
}
const interval = setInterval(() => {
  if (!authorized && existsSync(`${ipc}.auth`)) {
    authorized = true
    JSON.parse(readFileSync(`${ipc}.auth`, 'utf8')).forEach((data, i) => {
      sockets[i].send(JSON.stringify({ event: 'pusher:subscribe', data }))
    })
  }
  if (existsSync(`${ipc}.stop`)) {
    clearInterval(interval)
    sockets.forEach((socket) => socket.close())
  }
}, 25)
setTimeout(() => process.exit(2), 25000).unref()
