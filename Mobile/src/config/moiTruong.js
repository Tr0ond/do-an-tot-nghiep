import { Platform } from 'react-native'

export function layApiUrl() {
  if (Platform.OS === 'web')
    throw new Error(
      'Đăng nhập thật hiện được hỗ trợ trên app Android/iOS. Trình duyệt dùng phần Duyệt giao diện.',
    )
  const giaTri = process.env.EXPO_PUBLIC_API_URL?.trim().replace(/\/$/, '')
  if (!giaTri || !/^https?:\/\/[^\s]+\/api\/v1$/.test(giaTri))
    throw new Error(
      'Chưa cấu hình địa chỉ hệ thống. Kiểm tra EXPO_PUBLIC_API_URL rồi mở lại app.',
    )
  if (!__DEV__ && !giaTri.startsWith('https://'))
    throw new Error('Bản phát hành cần địa chỉ hệ thống HTTPS.')
  return giaTri
}
