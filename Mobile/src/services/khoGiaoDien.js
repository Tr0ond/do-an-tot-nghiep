import * as SecureStore from "expo-secure-store";
import { Platform } from "react-native";

const khoa = "fitforge_giao_dien_v1";
// Sở thích của thiết bị độc lập với phiên đăng nhập.
export const khoGiaoDien = {
  doc: () =>
    Platform.OS === "web"
      ? Promise.resolve(globalThis.localStorage?.getItem(khoa))
      : SecureStore.getItemAsync(khoa),
  luu: (giaTri) =>
    Platform.OS === "web"
      ? Promise.resolve(globalThis.localStorage?.setItem(khoa, giaTri))
      : SecureStore.setItemAsync(khoa, giaTri),
};
