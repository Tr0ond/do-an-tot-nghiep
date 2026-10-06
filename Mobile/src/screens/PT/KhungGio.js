import { useState } from "react";
import { RefreshControl, View, Pressable } from "react-native";
import {
  ManHinh,
  Chu,
  The,
  Nut,
  Nhan,
  TruongNhap,
} from "../../components/GiaoDien";
import {
  ChonNgay,
  HopXacNhan,
  PhanTrang,
  TrangThaiTai,
} from "../../components/HuanLuyen";
import { useDuLieu } from "../../hooks/useDuLieu";
import { useThaoTac } from "../../hooks/useThaoTac";
import { useXemTruoc } from "../../contexts/XemTruocContext";
import { useTraoDoi } from "../../contexts/TraoDoiContext";
import { huanLuyenService as api } from "../../services/huanLuyenService";
import { useGiaoDien } from "../../theme";
import { DaiNgay } from "../../components/FigmaElements";
import { homNay, thoiDiem, gioVietNam, gioMoKhung } from "../../utils/lich";

export default function KhungGio({ navigation }) {
  const { vaiTro, goiDichVu } = useXemTruoc();
  const { dongBoLich } = useTraoDoi();
  const { mau } = useGiaoDien();
  const [rongLuoi, datRongLuoi] = useState(350);
  const [moTao, datMoTao] = useState(false);
  const [ngay, datNgay] = useState(homNay);
  const [page, datTrang] = useState(1);
  const [gio, datGio] = useState("08:00");
  const [loiGio, datLoiGio] = useState("");
  const [chon, datChon] = useState(null);
  const { dangGui, loiGui, datLoiGui, gui } = useThaoTac();
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (signal) =>
      goiDichVu((token) => api.taiKhung(token, vaiTro, { ngay, page }, signal)),
    `${ngay}-${page}-${dongBoLich}`,
  );
  function tao() {
    try {
      const batDau = gioMoKhung(ngay, gio.trim());
      datLoiGio("");
      datLoiGui(null);
      datChon({ batDau });
    } catch (e) {
      datLoiGio(e.message);
    }
  }
  const xungDot = loiGui && [403, 404, 409].includes(loiGui.status);
  function xacNhan() {
    gui(
      () =>
        goiDichVu((token) =>
          chon.batDau
            ? api.taoKhung(token, chon.batDau)
            : api.doiKhung(token, chon.id, chon.trangThai),
        ),
      async () => {
        datChon(null);
        await taiLai();
      },
    );
  }
  return (
    <ManHinh
      bas
      tieuDe="Quản lý khung giờ"
      onBack={() => navigation.goBack()}
      refreshControl={
        <RefreshControl
          refreshing={dangTai}
          onRefresh={() => !dangGui && taiLai()}
        />
      }
    >
      <DaiNgay
        value={ngay}
        disabled={dangGui}
        onChange={(n) => {
          datNgay(n);
          datTrang(1);
        }}
      />
      <View
        style={{
          flexDirection: "row",
          alignItems: "center",
          justifyContent: "space-between",
        }}
      >
        <Chu size={14} dam="dam">
          {ngay} ·{" "}
          {duLieu?.data.filter((k) => k.trang_thai === "MO" && !k.dang_giu)
            .length || 0}{" "}
          khung đang mở
        </Chu>
        <Nut
          sm
          loai="soft"
          icon="Plus"
          disabled={dangGui}
          onPress={() => datMoTao(true)}
        >
          Thêm
        </Nut>
      </View>
      <View style={{ flexDirection: "row", flexWrap: "wrap", gap: 16 }}>
        {[
          ["Đang mở", mau.chinhNhat],
          ["Đã đóng", mau.the],
          ["Đã có lịch", mau.chinh],
        ].map(([ten, nen]) => (
          <View
            key={ten}
            style={{ flexDirection: "row", alignItems: "center", gap: 6 }}
          >
            <View
              style={{
                width: 12,
                height: 12,
                borderRadius: 3,
                backgroundColor: nen,
                borderWidth: 1,
                borderColor: mau.vien,
              }}
            />
            <Chu size={12} color={mau.chuPhu}>
              {ten}
            </Chu>
          </View>
        ))}
      </View>
      <HopXacNhan
        visible={moTao}
        tieuDe="Mở khung giờ mới"
        onDong={() => datMoTao(false)}
      >
        <Chu dam="dam" size={19}>
          Mở khung giờ mới
        </Chu>
        <Chu>
          Mỗi khung kéo dài 60 phút. Thời gian hiển thị theo giờ Việt Nam.
        </Chu>
        <TruongNhap
          nhan="Giờ bắt đầu (HH:mm)"
          value={gio}
          onChangeText={datGio}
          placeholder="08:00"
          keyboardType="numbers-and-punctuation"
          autoCapitalize="none"
          maxLength={5}
          editable={!dangGui}
          loi={loiGio}
        />
        <Nut icon="plus" disabled={dangGui} onPress={tao}>
          Mở khung giờ
        </Nut>
      </HopXacNhan>
      <TrangThaiTai
        {...{ dangTai, loi, taiLai }}
        rong={duLieu?.data?.length === 0}
        tieuDeRong="Chưa có khung giờ trong ngày"
        moTaRong="Bạn có thể mở giờ phù hợp với lịch làm việc của mình."
      />
      <View
        onLayout={(e) => datRongLuoi(e.nativeEvent.layout.width)}
        style={{ flexDirection: "row", flexWrap: "wrap", gap: 10 }}
      >
        {duLieu?.data?.map((k) => (
          <Pressable
            key={k.id}
            accessibilityRole="button"
            disabled={dangGui || !k.co_the_doi}
            onPress={() => {
              datLoiGui(null);
              datChon({
                id: k.id,
                bat_dau_luc: k.bat_dau_luc,
                trangThai: k.trang_thai === "MO" ? "DONG" : "MO",
              });
            }}
            style={{
              width: (rongLuoi - 20) / 3,
              height: 72,
              borderRadius: 16,
              borderWidth: 1,
              borderColor: k.trang_thai === "MO" ? mau.chinh : mau.vien,
              backgroundColor: k.dang_giu
                ? mau.chinh
                : k.trang_thai === "MO"
                  ? mau.chinhNhat
                  : mau.the,
              alignItems: "center",
              justifyContent: "center",
              opacity: k.co_the_doi ? 1 : 0.4,
            }}
          >
            <Chu
              size={15}
              dam="dam"
              color={
                k.dang_giu
                  ? mau.trenChinh
                  : k.trang_thai === "MO"
                    ? mau.chinh
                    : mau.chuPhu
              }
            >
              {gioVietNam(k.bat_dau_luc)}
            </Chu>
            <Chu
              size={10.5}
              dam="vua"
              color={
                k.dang_giu
                  ? mau.trenChinh
                  : k.trang_thai === "MO"
                    ? mau.chinh
                    : mau.chuPhu
              }
            >
              {k.dang_giu
                ? "Đã có lịch"
                : k.trang_thai === "MO"
                  ? "Mở"
                  : "Đóng"}
            </Chu>
          </Pressable>
        ))}
      </View>
      <Chu size={12.5} color={mau.chuPhu} style={{ lineHeight: 20.3125 }}>
        Chạm vào khung trống để mở hoặc đóng. Khung đã có học viên đặt không thể
        thay đổi, hãy xử lý yêu cầu trong màn hình lịch.
      </Chu>
      <ChonNgay
        value={ngay}
        disabled={dangGui}
        onChange={(n) => {
          datNgay(n);
          datTrang(1);
        }}
      />
      <PhanTrang
        meta={duLieu?.meta}
        dangTai={dangTai || dangGui}
        onChange={datTrang}
      />
      <HopXacNhan
        visible={!!chon}
        tieuDe={
          chon?.batDau
            ? "Mở khung giờ mới?"
            : chon?.trangThai === "DONG"
              ? "Đóng khung giờ?"
              : "Mở lại khung giờ?"
        }
        moTa={chon ? thoiDiem(chon.batDau || chon.bat_dau_luc) : ""}
        dangGui={dangGui}
        onDong={() => datChon(null)}
        onGui={xungDot ? null : xacNhan}
        loi={loiGui?.message || loiGui?.errors?.bat_dau_luc?.[0]}
      >
        {xungDot && (
          <Nut
            onPress={() => {
              datChon(null);
              taiLai();
            }}
          >
            Cập nhật khung giờ
          </Nut>
        )}
      </HopXacNhan>
    </ManHinh>
  );
}
