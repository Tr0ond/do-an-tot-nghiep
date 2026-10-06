import { useEffect, useState } from "react";
import { View, Pressable, Linking } from "react-native";
import {
  NavigationContainer,
  DefaultTheme,
  DarkTheme,
  useNavigationContainerRef,
} from "@react-navigation/native";
import { createNativeStackNavigator } from "@react-navigation/native-stack";
import { createBottomTabNavigator } from "@react-navigation/bottom-tabs";
import { SafeAreaView } from "react-native-safe-area-context";
import { useXemTruoc } from "../contexts/XemTruocContext";
import { useTraoDoi } from "../contexts/TraoDoiContext";
import { useThongBaoDay } from "../contexts/ThongBaoDayContext";
import { docLienKet, docPush } from "../utils/lienKet";
import ThongBaoDay from "../screens/CaNhan/ThongBaoDay";
import DanhSachHoiThoai from "../screens/TraoDoi/DanhSachHoiThoai";
import HoiThoai from "../screens/TraoDoi/HoiThoai";
import ThongBao from "../screens/TraoDoi/ThongBao";
import { font, useGiaoDien } from "../theme";
import { Chu, BieuTuong } from "../components/GiaoDien";
import { KHACH_HANG } from "../data/minhHoa";
import DangNhap from "../screens/Auth/DangNhap";
import DangKy from "../screens/Auth/DangKy";
import KhoiPhuc from "../screens/Auth/KhoiPhuc";
import Faq from "../screens/HoanThien/Faq";
import GoiTap from "../screens/HoanThien/GoiTap";
import DonHang from "../screens/HoanThien/DonHang";
import ChiTietDon from "../screens/HoanThien/ChiTietDon";
import TroLy from "../screens/HoanThien/TroLy";
import HoiThoaiAi from "../screens/HoanThien/HoiThoaiAi";
import TongQuanKhachHang from "../screens/KhachHang/TongQuan";
import TongQuanPT from "../screens/PT/TongQuan";
import HoSo from "../screens/CaNhan/HoSo";
import SuaHoSo from "../screens/CaNhan/SuaHoSo";
import GiaoDien from "../screens/CaNhan/GiaoDien";
import PhienDangNhap from "../screens/CaNhan/PhienDangNhap";
import ThanhDieuHuong from "../components/ThanhDieuHuong";
import ChuaTrienKhai from "../screens/ChuaTrienKhai";
import TongQuanTaiKhoan from "../screens/TongQuanTaiKhoan";
import LichHen from "../screens/Lich/LichHen";
import DatLich from "../screens/Lich/DatLich";
import ChiTietLich from "../screens/Lich/ChiTietLich";
import KetQuaBuoiPt from "../screens/Lich/KetQuaBuoiPt";
import KhungGio from "../screens/PT/KhungGio";
import HocVien from "../screens/PT/HocVien";
import HoSoHocVien from "../screens/PT/HoSoHocVien";
import Catalog from "../screens/TapLuyen/Catalog";
import ChiTietBaiTap from "../screens/TapLuyen/ChiTietBaiTap";
import GiaoAn from "../screens/TapLuyen/GiaoAn";
import ChiTietGiaoAn from "../screens/TapLuyen/ChiTietGiaoAn";
import SoanGiaoAn from "../screens/TapLuyen/SoanGiaoAn";
import GiaoAnMau from "../screens/TapLuyen/GiaoAnMau";
import LichTuTap from "../screens/TapLuyen/LichTuTap";
import BuoiTuTap from "../screens/TapLuyen/BuoiTuTap";
import ChiSo from "../screens/TapLuyen/ChiSo";
import GhiChiSo from "../screens/TapLuyen/GhiChiSo";
import { ActivityIndicator } from "react-native";
import { ManHinh, The, Nut } from "../components/GiaoDien";

const Stack = createNativeStackNavigator();
const Tabs = createBottomTabNavigator();

function ThanhTab() {
  const { vaiTro, dangXemTruoc } = useXemTruoc();
  const { soTin } = useTraoDoi();
  const { mau } = useGiaoDien();
  const laKhach = vaiTro === KHACH_HANG;
  return (
    <Tabs.Navigator
      tabBar={(props) => <ThanhDieuHuong {...props} />}
      screenOptions={{
        headerShown: false,
        tabBarHideOnKeyboard: true,
        tabBarActiveTintColor: mau.chinh,
        tabBarInactiveTintColor: mau.chuPhu,
        tabBarStyle: {
          backgroundColor: mau.the,
          borderTopColor: mau.vien,
          paddingTop: 6,
          minHeight: 64,
        },
        tabBarLabelStyle: {
          fontFamily: font.vua,
          fontSize: 11,
          marginBottom: 4,
        },
        sceneStyle: { backgroundColor: mau.nen },
      }}
    >
      <Tabs.Screen
        name="TongQuan"
        component={
          dangXemTruoc
            ? laKhach
              ? TongQuanKhachHang
              : TongQuanPT
            : TongQuanTaiKhoan
        }
        options={{
          title: "Tổng quan",
          tabBarIcon: ({ color }) => <BieuTuong ten="grid" color={color} />,
        }}
      />
      <Tabs.Screen
        name="Lich"
        component={dangXemTruoc ? ChuaTrienKhai : LichHen}
        initialParams={{
          tieuDe: laKhach ? "Lịch của tôi" : "Lịch huấn luyện",
          icon: "calendar",
        }}
        options={{
          title: "Lịch tập",
          tabBarIcon: ({ color }) => <BieuTuong ten="calendar" color={color} />,
        }}
      />
      {laKhach ? (
        <Tabs.Screen
          name="GiaoAn"
          component={dangXemTruoc ? ChuaTrienKhai : GiaoAn}
          initialParams={{ tieuDe: "Giáo án tập luyện", icon: "activity" }}
          options={{
            title: "Giáo án",
            tabBarIcon: ({ color }) => (
              <BieuTuong ten="activity" color={color} />
            ),
          }}
        />
      ) : (
        <Tabs.Screen
          name="HocVien"
          component={dangXemTruoc ? ChuaTrienKhai : HocVien}
          initialParams={{ tieuDe: "Học viên phụ trách", icon: "users" }}
          options={{
            title: "Học viên",
            tabBarIcon: ({ color }) => <BieuTuong ten="users" color={color} />,
          }}
        />
      )}
      <Tabs.Screen
        name="TinNhan"
        component={dangXemTruoc ? ChuaTrienKhai : DanhSachHoiThoai}
        initialParams={{ tieuDe: "Tin nhắn", icon: "message-circle" }}
        options={{
          title: "Tin nhắn",
          tabBarBadge:
            !dangXemTruoc && soTin > 0
              ? soTin > 99
                ? "99+"
                : soTin
              : undefined,
          tabBarBadgeStyle: { minWidth: 32, fontSize: 12 },
          tabBarIcon: ({ color }) => (
            <BieuTuong ten="message-circle" color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="CaNhan"
        component={HoSo}
        options={{
          title: "Cá nhân",
          tabBarIcon: ({ color }) => <BieuTuong ten="user" color={color} />,
        }}
      />
    </Tabs.Navigator>
  );
}

export default function DieuHuong() {
  const [dangSuaHoSo, datDangSuaHoSo] = useState(false);
  const dieuHuong = useNavigationContainerRef();
  const [sanSang, datSanSang] = useState(0);
  const {
    vaiTro,
    dangXemTruoc,
    thoatBanXem,
    dangKhoiPhuc,
    loiKhoiPhuc,
    khoiPhuc,
    dangXuat,
    taiKhoan,
    lienKetCho,
    datLienKetCho,
  } = useXemTruoc();
  const { thongBaoMo, xoaThongBaoMo } = useThongBaoDay();
  useEffect(() => {
    let hien = true;
    const nhan = (url) => {
      const d = docLienKet(url);
      if (d && hien) datLienKetCho(d);
    };
    const nghe = Linking.addEventListener("url", (e) => nhan(e.url));
    Linking.getInitialURL()
      .then(nhan)
      .catch(() => {});
    return () => {
      hien = false;
      nghe.remove();
    };
  }, []);
  useEffect(() => {
    if (!thongBaoMo) return;
    const d = docPush(thongBaoMo, taiKhoan);
    if (d) datLienKetCho(d);
    xoaThongBaoMo();
  }, [thongBaoMo, taiKhoan?.id]);
  useEffect(() => {
    if (!lienKetCho || dangKhoiPhuc || dangXemTruoc || !dieuHuong.isReady())
      return;
    if (!lienKetCho.congKhai && !taiKhoan) return;
    if (lienKetCho.chiKhach && vaiTro !== KHACH_HANG) {
      datLienKetCho(null);
      return;
    }
    dieuHuong.navigate(lienKetCho.name, lienKetCho.params);
    datLienKetCho(null);
  }, [lienKetCho, dangKhoiPhuc, dangXemTruoc, taiKhoan?.id, sanSang]);
  useEffect(() => datDangSuaHoSo(false), [vaiTro, dangXemTruoc, taiKhoan?.id]);
  const { mau, cheDoToi } = useGiaoDien();
  const theme = {
    ...(cheDoToi ? DarkTheme : DefaultTheme),
    colors: {
      ...(cheDoToi ? DarkTheme : DefaultTheme).colors,
      primary: mau.chinh,
      background: mau.nen,
      card: mau.the,
      text: mau.chu,
      border: mau.vien,
      notification: mau.loi,
    },
  };
  if (dangKhoiPhuc)
    return (
      <ManHinh scroll={false}>
        <View
          style={{
            flex: 1,
            justifyContent: "center",
            alignItems: "center",
            gap: 16,
          }}
        >
          <ActivityIndicator size="large" color={mau.chinh} />
          <Chu>Đang kiểm tra phiên đăng nhập…</Chu>
        </View>
      </ManHinh>
    );
  if (loiKhoiPhuc && !dangXemTruoc)
    return (
      <ManHinh>
        <The>
          <Chu size={20} dam="dam">
            Chưa kiểm tra được phiên
          </Chu>
          <Chu>{loiKhoiPhuc}</Chu>
          <Nut onPress={khoiPhuc}>Thử lại</Nut>
          <Nut loai="phu" onPress={dangXuat}>
            Về màn đăng nhập
          </Nut>
        </The>
      </ManHinh>
    );
  return (
    <View style={{ flex: 1 }}>
      {dangXemTruoc && (
        <SafeAreaView
          edges={["top"]}
          style={{ backgroundColor: mau.chinhNhat }}
        >
          <View
            style={{
              flexDirection: "row",
              alignItems: "center",
              justifyContent: "space-between",
              paddingLeft: 16,
              paddingRight: 8,
              minHeight: 48,
            }}
          >
            <Chu size={12} dam="damVua" color={mau.chinh}>
              Xem trước · Dữ liệu minh họa
            </Chu>
            {!dangSuaHoSo && (
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Thoát bản xem trước"
                onPress={thoatBanXem}
                style={({ pressed }) => ({
                  padding: 12,
                  minHeight: 48,
                  justifyContent: "center",
                  opacity: pressed ? 0.6 : 1,
                })}
              >
                <BieuTuong ten="x" size={20} />
              </Pressable>
            )}
          </View>
        </SafeAreaView>
      )}
      <NavigationContainer
        ref={dieuHuong}
        onReady={() => datSanSang((n) => n + 1)}
        key={
          dangXemTruoc
            ? `xem-${vaiTro}`
            : taiKhoan
              ? `tai-khoan-${taiKhoan.id}`
              : "dang-nhap"
        }
        theme={theme}
        onStateChange={(state) =>
          datDangSuaHoSo(state?.routes[state.index]?.name === "SuaHoSo")
        }
      >
        <Stack.Navigator
          screenOptions={{
            headerShown: false,
            contentStyle: { backgroundColor: mau.nen },
            animation: "none",
          }}
        >
          {vaiTro ? (
            <>
              <Stack.Screen name="BanXemTruoc" component={ThanhTab} />
              <Stack.Screen name="SuaHoSo" component={SuaHoSo} />
              <Stack.Screen name="GiaoDien" component={GiaoDien} />
              <Stack.Screen name="PhienDangNhap" component={PhienDangNhap} />
              {!dangXemTruoc && (
                <>
                  <Stack.Screen name="ChiTietLich" component={ChiTietLich} />
                  <Stack.Screen name="KetQuaBuoiPt" component={KetQuaBuoiPt} />
                  <Stack.Screen name="HoiThoai" component={HoiThoai} />
                  <Stack.Screen name="ThongBao" component={ThongBao} />
                  <Stack.Screen name="ThongBaoDay" component={ThongBaoDay} />
                  <Stack.Screen name="Catalog" component={Catalog} />
                  <Stack.Screen
                    name="ChiTietBaiTap"
                    component={ChiTietBaiTap}
                  />
                  <Stack.Screen
                    name="ChiTietGiaoAn"
                    component={ChiTietGiaoAn}
                  />
                  <Stack.Screen name="SoanGiaoAn" component={SoanGiaoAn} />
                  <Stack.Screen name="LichTuTap" component={LichTuTap} />
                  <Stack.Screen name="BuoiTuTap" component={BuoiTuTap} />
                  <Stack.Screen name="ChiSo" component={ChiSo} />
                  {vaiTro === KHACH_HANG ? (
                    <>
                      <Stack.Screen name="DatLich" component={DatLich} />
                      <Stack.Screen
                        name="GhiChiSo"
                        component={GhiChiSo}
                        options={{
                          presentation: "transparentModal",
                          contentStyle: { backgroundColor: "transparent" },
                        }}
                      />
                      <Stack.Screen name="DonHang" component={DonHang} />
                      <Stack.Screen name="ChiTietDon" component={ChiTietDon} />
                      <Stack.Screen name="TroLy" component={TroLy} />
                      <Stack.Screen name="HoiThoaiAi" component={HoiThoaiAi} />
                    </>
                  ) : (
                    <>
                      <Stack.Screen name="KhungGio" component={KhungGio} />
                      <Stack.Screen name="GiaoAnHocVien" component={GiaoAn} />
                      <Stack.Screen name="GiaoAnMau" component={GiaoAnMau} />
                      <Stack.Screen
                        name="HoSoHocVien"
                        component={HoSoHocVien}
                      />
                    </>
                  )}
                </>
              )}
            </>
          ) : (
            <>
              <Stack.Screen name="DangNhap" component={DangNhap} />
              <Stack.Screen name="DangKy" component={DangKy} />
            </>
          )}
          <Stack.Screen name="Faq" component={Faq} />
          <Stack.Screen name="KhoiPhuc" component={KhoiPhuc} />
          <Stack.Screen name="GoiTap" component={GoiTap} />
          <Stack.Screen name="ChuaTrienKhai" component={ChuaTrienKhai} />
        </Stack.Navigator>
      </NavigationContainer>
    </View>
  );
}
