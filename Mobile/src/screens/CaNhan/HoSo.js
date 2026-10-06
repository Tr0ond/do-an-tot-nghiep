import { useState } from "react";
import { View } from "react-native";
import {
  ManHinh,
  Chu,
  The,
  Nut,
  AnhDaiDien,
  HangMenu,
} from "../../components/GiaoDien";
import { HopXacNhan } from "../../components/HuanLuyen";
import { useXemTruoc } from "../../contexts/XemTruocContext";
import { useGiaoDien } from "../../theme";
import MascotTroLy from "../../components/MascotTroLy";
import { useDuLieu } from "../../hooks/useDuLieu";
import { huanLuyenService as api } from "../../services/huanLuyenService";

export default function HoSo({ navigation }) {
  const {
    hoSo,
    vaiTro,
    goiDichVu,
    thoatBanXem,
    dangXemTruoc,
    dangXuat,
    dangThaoTac,
    taiHoSo,
    datThongBao,
  } = useXemTruoc();
  const { mau } = useGiaoDien();
  const [xacNhan, datXacNhan] = useState(false);
  const laKhach = vaiTro === "KHACH_HANG";
  const { duLieu } = useDuLieu(
    (signal) =>
      dangXemTruoc
        ? Promise.resolve(null)
        : goiDichVu((token) => api.taiTongQuan(token, vaiTro, signal)),
    `${vaiTro}|${dangXemTruoc}`,
  );
  const goi = duLieu?.data?.hanh_trinh?.goi;
  const mo = (man) =>
    dangXemTruoc &&
    !["SuaHoSo", "GiaoDien", "Faq", "PhienDangNhap"].includes(man)
      ? navigation.navigate("ChuaTrienKhai", {
          tieuDe: "Bản xem trước",
          icon: "info",
        })
      : navigation.navigate(man);
  return (
    <ManHinh contentStyle={{ paddingTop: 0, gap: 0 }}>
      <View style={{ alignItems: "center", paddingTop: 32, paddingBottom: 24 }}>
        <AnhDaiDien ten={hoSo.ho_ten} size={80} />
        <Chu
          size={22}
          dam="ratDam"
          style={{ marginTop: 12, letterSpacing: -0.55 }}
        >
          {hoSo.ho_ten}
        </Chu>
        <Chu size={13} color={mau.chuPhu}>
          {hoSo.email}
        </Chu>
        <View
          style={{
            marginTop: 12,
            borderRadius: 999,
            backgroundColor: mau.chinhNhat,
            paddingHorizontal: 12,
            paddingVertical: 4,
          }}
        >
          <Chu size={12} dam="damVua" color={mau.chinh}>
            {laKhach
              ? `Học viên · Mục tiêu ${hoSo.muc_tieu || "Chưa cập nhật"}`
              : `Huấn luyện viên · ${hoSo.chuyen_mon || "Chưa cập nhật"}`}
          </Chu>
        </View>
      </View>
      {laKhach && (
        <View
          style={{
            flexDirection: "row",
            alignItems: "center",
            justifyContent: "space-between",
            borderRadius: 16,
            backgroundColor: mau.chinh,
            padding: 16,
            marginBottom: 20,
            gap: 8,
          }}
        >
          <View style={{ flex: 1 }}>
            <Chu size={12} color={mau.trenChinh} style={{ opacity: 0.8 }}>
              Gói hiện tại
            </Chu>
            <Chu size={16} dam="dam" color={mau.trenChinh}>
              {goi
                ? `${goi.ten} · còn ${goi.so_buoi_con_lai} buổi`
                : "Chưa có gói tập"}
            </Chu>
          </View>
          <Nut sm loai="lime" onPress={() => mo("GoiTap")}>
            {goi ? "Chi tiết" : "Chọn gói"}
          </Nut>
        </View>
      )}
      <The style={{ padding: 0, gap: 0, overflow: "hidden" }}>
        <HangMenu
          icon="Bell"
          tieuDe="Thông báo trên điện thoại"
          moTa="Tin nhắn và lịch hẹn khi đóng app"
          onPress={() => mo("ThongBaoDay")}
        />
        <HangMenu
          icon="UserCog"
          tieuDe="Chỉnh sửa hồ sơ"
          moTa="Họ tên, mục tiêu tập luyện"
          onPress={() => mo("SuaHoSo")}
        />
        <HangMenu
          icon="Palette"
          tieuDe="Giao diện"
          moTa="Sáng, tối hoặc theo hệ thống"
          onPress={() => mo("GiaoDien")}
        />
        {laKhach && (
          <HangMenu
            icon="Package"
            tieuDe="Gói tập"
            moTa="Quyền lợi và gói đang dùng"
            onPress={() => mo("GoiTap")}
          />
        )}
        {laKhach && (
          <HangMenu
            icon="Receipt"
            tieuDe="Lịch sử đơn hàng"
            moTa="Trạng thái thanh toán"
            onPress={() => mo("DonHang")}
          />
        )}
        <HangMenu
          icon="ShieldCheck"
          tieuDe="Phiên đăng nhập"
          moTa="Thiết bị đang đăng nhập"
          onPress={() => mo("PhienDangNhap")}
          cuoi
        />
      </The>
      <The style={{ padding: 0, gap: 0, overflow: "hidden", marginTop: 20 }}>
        <HangMenu
          icon="LogOut"
          tieuDe={dangXemTruoc ? "Thoát bản xem trước" : "Đăng xuất"}
          moTa="Chỉ thiết bị này"
          onPress={() => datXacNhan(true)}
          nguyHiem
          cuoi
        />
      </The>
      <The style={{ padding: 0, gap: 0, overflow: "hidden", marginTop: 20 }}>
        {laKhach && !dangXemTruoc && (
          <HangMenu
            icon="LineChart"
            tieuDe="Chỉ số cơ thể"
            moTa="Số đo, BMI tham khảo và lịch sử"
            onPress={() => mo("ChiSo")}
          />
        )}
        {laKhach && !dangXemTruoc && (
          <HangMenu
            icon="Sparkles"
            minhHoa={<MascotTroLy size={36} />}
            tieuDe="Trợ lý AI"
            moTa="Tư vấn và tạo giáo án nháp"
            onPress={() => mo("TroLy")}
          />
        )}
        <HangMenu
          icon="CircleHelp"
          tieuDe="Câu hỏi thường gặp"
          onPress={() => mo("Faq")}
        />
        {!dangXemTruoc && (
          <HangMenu
            icon="RefreshCw"
            tieuDe="Tải lại hồ sơ"
            onPress={() =>
              taiHoSo().catch((loi) => {
                if (loi.status !== 401)
                  datThongBao({
                    tieuDe: "Chưa tải được hồ sơ",
                    noiDung: loi.message,
                  });
              })
            }
          />
        )}
      </The>
      <Chu
        size={12}
        color={mau.chuPhu}
        style={{ textAlign: "center", marginTop: 24 }}
      >
        FitForge · Phiên đăng nhập 30 ngày
      </Chu>
      <HopXacNhan
        visible={xacNhan}
        tieuDe="Đăng xuất khỏi thiết bị này?"
        moTa="Bạn sẽ chỉ đăng xuất trên thiết bị này. Các thiết bị khác vẫn giữ phiên đăng nhập (hiệu lực 30 ngày)."
        nhanGui={dangXemTruoc ? "Thoát bản xem trước" : "Đăng xuất"}
        loai="loi"
        dangGui={dangThaoTac}
        onDong={() => datXacNhan(false)}
        onGui={() => {
          datXacNhan(false);
          return dangXemTruoc ? thoatBanXem() : dangXuat();
        }}
      />
    </ManHinh>
  );
}
