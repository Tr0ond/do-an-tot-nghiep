import * as SecureStore from 'expo-secure-store'
import { Platform } from 'react-native'

// Giữ key đã phát hành để đổi thương hiệu không làm mất phiên người dùng.
const khoa = 'troond_phien_mobile_v1'
const tuyChon = {
  keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY,
}

export const khoPhien = {
  doc: async () =>
    Platform.OS === 'web' ? null : SecureStore.getItemAsync(khoa, tuyChon),
  luu: async (duLieu) => {
    if (Platform.OS === 'web')
      throw new Error('Đăng nhập thật cần mở app Android/iOS.')
    await SecureStore.setItemAsync(khoa, JSON.stringify(duLieu), tuyChon)
  },
  xoa: async () => {
    if (Platform.OS !== 'web') await SecureStore.deleteItemAsync(khoa, tuyChon)
  },
}
