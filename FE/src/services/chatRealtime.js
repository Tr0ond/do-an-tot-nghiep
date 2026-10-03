import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import chatService from './chatService'

export function ketNoiChat(taiKhoanId, capNhat, doiTrangThai) {
  const key = import.meta.env.VITE_REVERB_APP_KEY
  if (!key) {
    doiTrangThai('chua-cau-hinh')
    return () => {}
  }
  const echo = new Echo({
    broadcaster: 'reverb',
    Pusher,
    key,
    wsHost: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel) => ({
      authorize: (socketId, callback) => {
        chatService.xacThucKenh(socketId, channel.name).then(
          (data) => callback(null, data),
          (loi) => callback(loi, null),
        )
      },
    }),
  })
  const connection = echo.connector.pusher.connection
  const doi = ({ current }) => doiTrangThai(current)
  connection.bind('state_change', doi)
  echo
    .private(`chat.tai-khoan.${taiKhoanId}`)
    .subscribed(() => {
      doiTrangThai('connected')
      capNhat()
    })
    .listen('.chat.cap-nhat', capNhat)
    .error(() => doiTrangThai('unavailable'))
  return () => {
    connection.unbind('state_change', doi)
    echo.leave(`chat.tai-khoan.${taiKhoanId}`)
    echo.disconnect()
  }
}
