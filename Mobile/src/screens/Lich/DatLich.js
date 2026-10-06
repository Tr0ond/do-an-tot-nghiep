import { useEffect, useRef, useState } from "react";
import { RefreshControl, View, Pressable } from "react-native";
import { randomUUID } from "expo-crypto";
import { DaiNgay, Trong } from "../../components/FigmaElements";
import { useGiaoDien } from "../../theme";
import { ManHinh, Chu, The, Nut, Nhan } from "../../components/GiaoDien";
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
import {
  homNay,
  thoiDiem,
  gioVietNam,
  taoYeuCauDatLich,
} from "../../utils/lich";

export default function DatLich({ navigation }) {
  const { mau } = useGiaoDien();
  const [rong, datRong] = useState(0);
  const { vaiTro, goiDichVu } = useXemTruoc();
  const { dongBoLich } = useTraoDoi();
  const [ngay, datNgay] = useState(homNay);
  const [page, datTrang] = useState(1);
  const [chon, datChon] = useState(null);
  const [daDat, datDaDat] = useState(null);
  const { dangGui, loiGui, datLoiGui, gui } = useThaoTac();
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (signal) =>
      goiDichVu((token) => api.taiKhung(token, vaiTro, { ngay, page }, signal)),
    `${ngay}-${page}-${dongBoLich}`,
  );
  const goiRef = useRef(goiDichVu);
  goiRef.current = goiDichVu;
  const yeuCau = useRef(null);
  if (!yeuCau.current)
    yeuCau.current = taoYeuCauDatLich({
      taoMa: randomUUID,
      goi: (payload) => goiRef.current((token) => api.datLich(token, payload)),
    });
  useEffect(() => {
    if (daDat && !dangGui) navigation.replace("ChiTietLich", { id: daDat });
  }, [daDat, dangGui, navigation]);
  const xungDot = loiGui && [403, 404, 409].includes(loiGui.status);
  function chonKhung(khung) {
    yeuCau.current.chon(khung.id);
    datLoiGui(null);
    datChon(khung);
  }
  return (
    <ManHinh
      bas
      tieuDe="Đặt lịch PT"
      contentStyle={{ gap: 16 }}
      onBack={() => navigation.goBack()}
      refreshControl={
        <RefreshControl
          refreshing={dangTai}
          onRefresh={() => !dangGui && taiLai()}
        />
      }
    >
      {duLieu?.meta && (
        <View
          style={{
            backgroundColor: mau.chinhNhat,
            padding: 14,
            borderRadius: 16,
          }}
        >
          <Chu size={13}>
            Còn{" "}
            <Chu size={13} dam="dam">
              {duLieu.meta.so_buoi_con_lai ?? 0}
            </Chu>{" "}
            lượt đặt · đặt trước ít nhất{" "}
            <Chu size={13} dam="dam">
              4 giờ
            </Chu>
          </Chu>
          {duLieu.meta.pt && (
            <Chu size={13} style={{ marginTop: 4 }}>
              PT {duLieu.meta.pt.ho_ten} · {duLieu.meta.pt.chuyen_mon}
            </Chu>
          )}
        </View>
      )}
      <DaiNgay
        value={ngay}
        disabled={dangGui}
        onChange={(n) => {
          datNgay(n);
          datTrang(1);
        }}
      />
      <Chu size={14} dam="dam">
        {ngay} <Chu color={mau.chuPhu}>· mỗi buổi 60 phút</Chu>
      </Chu>
      <TrangThaiTai
        {...{ dangTai, loi, taiLai }}
        rong={duLieu?.data?.length === 0}
        tieuDeRong="Chưa có khung giờ phù hợp"
        moTaRong={
          duLieu?.meta?.ly_do ||
          "Thử chọn ngày khác hoặc liên hệ PT để mở thêm giờ."
        }
      />
      <View
        onLayout={(e) => datRong(e.nativeEvent.layout.width)}
        style={{ flexDirection: "row", flexWrap: "wrap", gap: 10 }}
      >
        {duLieu?.data?.map((k) => (
          <Pressable
            key={k.id}
            accessibilityRole="button"
            disabled={dangGui || k.dang_giu || k.trang_thai !== "MO"}
            onPress={() => chonKhung(k)}
            style={{
              width: Math.max(0, (rong - 20) / 3),
              height: 64,
              borderRadius: 12,
              borderWidth: 1,
              borderColor: mau.vien,
              backgroundColor: mau.the,
              justifyContent: "center",
              alignItems: "center",
              opacity: k.dang_giu || k.trang_thai !== "MO" ? 0.4 : 1,
            }}
          >
            <Chu size={15} dam="dam">
              {gioVietNam(k.bat_dau_luc)}
            </Chu>
            <Chu size={11} color={mau.chuPhu}>
              {k.dang_giu ? "Đã có người đặt" : "60 phút"}
            </Chu>
          </Pressable>
        ))}
      </View>
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
        tieuDe="Gửi yêu cầu đặt lịch?"
        moTa={
          chon
            ? `${thoiDiem(chon.bat_dau_luc)} – ${gioVietNam(chon.ket_thuc_luc)}. PT cần xác nhận trước hạn ghi trong lịch hẹn.`
            : ""
        }
        dangGui={dangGui}
        onDong={() => datChon(null)}
        onGui={
          xungDot
            ? null
            : () =>
                gui(
                  () => yeuCau.current.gui(),
                  (k) => datDaDat(k.data.id),
                )
        }
        nhanGui="Gửi yêu cầu"
        loi={loiGui?.message}
      >
        {loiGui && !xungDot && (
          <Chu>
            Thử lại sẽ dùng cùng yêu cầu để tránh đặt trùng. Bạn cũng có thể
            kiểm tra lịch của mình trước khi đặt tiếp.
          </Chu>
        )}
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
