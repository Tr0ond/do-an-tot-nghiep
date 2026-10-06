import { createContext, useContext, useEffect, useRef, useState } from "react";
import { AppState, Platform } from "react-native";
import Constants from "expo-constants";
import { useXemTruoc } from "./XemTruocContext";
import { useTraoDoi } from "./TraoDoiContext";
import { thongBaoDayService as api } from "../services/thongBaoDayService";

const NguCanh = createContext(null);
const projectId =
  Constants.expoConfig?.extra?.eas?.projectId || Constants.easConfig?.projectId;
const banCai =
  Platform.OS !== "web" && Constants.executionEnvironment !== "storeClient";
export function ThongBaoDayProvider({ children }) {
  const { taiKhoan, dangXemTruoc, goiDichVu } = useXemTruoc();
  const { capNhat } = useTraoDoi();
  const capNhatRef = useRef(capNhat);
  capNhatRef.current = capNhat;
  const [trangThai, datTrangThai] = useState(
    "Chưa bật thông báo trên thiết bị này.",
  );
  const [dangGui, datDangGui] = useState(false);
  const [thongBaoMo, datThongBaoMo] = useState(null);
  const theHe = useRef(0);
  const daXuLy = useRef(null);
  const hang = useRef(Promise.resolve());
  function xep(hanhDong, moc) {
    const viec = hang.current
      .catch(() => {})
      .then(() => (moc === theHe.current ? hanhDong() : null));
    hang.current = viec.catch(() => {});
    return viec;
  }
  async function dangKy(hoiQuyen, moc) {
    const n = await import("expo-notifications");
    if (Platform.OS === "android")
      await n.setNotificationChannelAsync("fitforge", {
        name: "Tin nhắn và lịch hẹn",
        importance: n.AndroidImportance.DEFAULT,
        lockscreenVisibility: n.AndroidNotificationVisibility.PRIVATE,
      });
    let quyen = await n.getPermissionsAsync();
    if (!quyen.granted && hoiQuyen) quyen = await n.requestPermissionsAsync();
    if (moc !== theHe.current) return;
    if (!quyen.granted) {
      await goiDichVu((t) => api.tat(t));
      if (moc !== theHe.current) return;
      datTrangThai(
        "Chưa được cấp quyền. Bạn có thể bật trong cài đặt thông báo của điện thoại.",
      );
      return;
    }
    const expoToken = (await n.getExpoPushTokenAsync({ projectId })).data;
    if (moc !== theHe.current) return;
    await goiDichVu((t) => api.luu(t, expoToken));
    if (moc === theHe.current)
      datTrangThai("Đã bật tin nhắn và lịch hẹn trên thiết bị này.");
  }
  useEffect(() => {
    const moc = ++theHe.current;
    datThongBaoMo(null);
    datDangGui(false);
    datTrangThai(
      !banCai
        ? "Thông báo khi đóng app cần bản cài FitForge Android riêng."
        : !projectId
          ? "Chưa cấu hình dịch vụ thông báo cho bản cài này."
          : "Chưa bật thông báo trên thiết bị này.",
    );
    if (!taiKhoan || dangXemTruoc || !banCai || !projectId) return;
    let nhan, mo, token, app;
    const loi = () => {
      if (moc === theHe.current)
        datTrangThai("Chưa kết nối được thông báo. Hãy thử bật lại.");
    };
    const dongBo = () =>
      xep(async () => {
        const d = await goiDichVu((t) => api.tai(t));
        if (moc === theHe.current && d?.da_bat) await dangKy(false, moc);
      }, moc);
    import("expo-notifications")
      .then(async (n) => {
        if (moc !== theHe.current) return;
        n.setNotificationHandler({
          handleNotification: async (thongBao) => ({
            shouldShowBanner:
              moc === theHe.current &&
              Number(thongBao.request.content.data?.tai_khoan_id) ===
                taiKhoan.id,
            shouldShowList:
              moc === theHe.current &&
              Number(thongBao.request.content.data?.tai_khoan_id) ===
                taiKhoan.id,
            shouldPlaySound:
              moc === theHe.current &&
              Number(thongBao.request.content.data?.tai_khoan_id) ===
                taiKhoan.id,
            shouldSetBadge: false,
          }),
        });
        const xuLy = (r) => {
          if (
            !r ||
            moc !== theHe.current ||
            r.actionIdentifier !== n.DEFAULT_ACTION_IDENTIFIER ||
            daXuLy.current === r.notification.request.identifier
          )
            return;
          daXuLy.current = r.notification.request.identifier;
          datThongBaoMo(r.notification.request.content.data);
          n.clearLastNotificationResponseAsync().catch(() => {});
        };
        mo = n.addNotificationResponseReceivedListener(xuLy);
        nhan = n.addNotificationReceivedListener(() => capNhatRef.current());
        token = n.addPushTokenListener(() => dongBo().catch(loi));
        app = AppState.addEventListener("change", (s) => {
          if (s === "active") dongBo().catch(loi);
        });
        xuLy(await n.getLastNotificationResponseAsync());
        await dongBo();
      })
      .catch(loi);
    return () => {
      theHe.current++;
      mo?.remove();
      nhan?.remove();
      token?.remove();
      app?.remove();
    };
  }, [taiKhoan?.id, dangXemTruoc, goiDichVu]);
  async function doi(bat) {
    if (dangGui || !taiKhoan || dangXemTruoc || !banCai || !projectId) return;
    const moc = theHe.current;
    datDangGui(true);
    try {
      await xep(async () => {
        if (bat) await dangKy(true, moc);
        else {
          await goiDichVu((t) => api.tat(t));
          if (moc === theHe.current)
            datTrangThai("Đã tắt thông báo trên thiết bị này.");
        }
      }, moc);
    } catch {
      if (moc === theHe.current)
        datTrangThai("Chưa lưu được cài đặt. Hãy thử lại khi có mạng.");
    } finally {
      if (moc === theHe.current) datDangGui(false);
    }
  }
  return (
    <NguCanh.Provider
      value={{
        trangThai,
        dangGui,
        khaDung: banCai && !!projectId,
        doi,
        thongBaoMo,
        xoaThongBaoMo: () => datThongBaoMo(null),
      }}
    >
      {children}
    </NguCanh.Provider>
  );
}
export const useThongBaoDay = () => useContext(NguCanh);
