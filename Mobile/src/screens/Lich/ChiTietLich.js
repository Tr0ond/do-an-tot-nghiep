import { useEffect, useRef, useState } from "react";
import { RefreshControl, View } from "react-native";
import {
  ManHinh,
  AnhDaiDien,
  BieuTuong,
  Chu,
  The,
  Nut,
  Nhan,
  TruongNhap,
} from "../../components/GiaoDien";
import { TrangThaiTai, HopXacNhan } from "../../components/HuanLuyen";
import { useDuLieu } from "../../hooks/useDuLieu";
import { useThaoTac } from "../../hooks/useThaoTac";
import { useXemTruoc } from "../../contexts/XemTruocContext";
import { useTraoDoi } from "../../contexts/TraoDoiContext";
import { useGiaoDien } from "../../theme";
import { huanLuyenService as api } from "../../services/huanLuyenService";
import {
  hanhDongLich,
  trangThaiLich,
  thoiDiem,
  gioVietNam,
} from "../../utils/lich";

export default function ChiTietLich({ navigation, route }) {
  const { vaiTro, goiDichVu, datThongBao } = useXemTruoc();
  const { dongBoLich } = useTraoDoi();
  const { id } = route.params;
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (signal) => goiDichVu((token) => api.taiChiTiet(token, vaiTro, id, signal)),
    `${id}-${dongBoLich}`,
  );
  const { dangGui, loiGui, datLoiGui, gui } = useThaoTac();
  const [hanhDong, datHanhDong] = useState(null);
  const [lyDo, datLyDo] = useState("");
  const [loiLyDo, datLoiLyDo] = useState("");
  const { mau } = useGiaoDien();
  const lich = duLieu?.data;
  const daMo = useRef(null);
  useEffect(() => {
    const h = route.params.moHanhDong;
    if (
      h &&
      daMo.current !== `${id}|${h}` &&
      lich?.hanh_dong.includes(h) &&
      hanhDongLich[h]
    ) {
      daMo.current = `${id}|${h}`;
      chon(h);
    }
  }, [id, lich, route.params.moHanhDong]);
  const tacVu = hanhDongLich[hanhDong];
  const xungDot = loiGui && [403, 404, 409].includes(loiGui.status);
  function chon(h) {
    datLoiGui(null);
    datLyDo("");
    datLoiLyDo("");
    datHanhDong(h);
  }
  function xacNhan() {
    if (tacVu.lyDo && !lyDo.trim()) {
      datLoiLyDo("Vui lòng ghi lý do.");
      return;
    }
    gui(
      () =>
        goiDichVu((token) =>
          api.thaoTac(token, vaiTro, id, hanhDong, lyDo.trim()),
        ),
      async (ketQua) => {
        datHanhDong(null);
        datThongBao({
          tieuDe: "Đã cập nhật lịch hẹn",
          noiDung: ketQua.message,
        });
        await taiLai();
      },
    );
  }
  return (
    <ManHinh
      bas
      tieuDe="Chi tiết buổi tập"
      onBack={() => navigation.goBack()}
      footer={
        lich?.hanh_dong?.length > 0 && (
          <View style={{ flexDirection: "row", gap: 12, flexWrap: "wrap" }}>
            {lich.hanh_dong
              .filter((h) => hanhDongLich[h])
              .map((h) => (
                <Nut
                  key={h}
                  style={{ flexGrow: 1 }}
                  disabled={dangGui}
                  loai={
                    ["huy", "tu-choi", "vang-mat"].includes(h) ? "phu" : "chinh"
                  }
                  onPress={() => chon(h)}
                >
                  {hanhDongLich[h].nhan}
                </Nut>
              ))}
          </View>
        )
      }
      refreshControl={
        <RefreshControl
          refreshing={dangTai}
          onRefresh={() => !dangGui && taiLai()}
        />
      }
    >
      <TrangThaiTai {...{ dangTai, loi, taiLai }} />
      {lich && (
        <>
          <View
            style={{
              borderRadius: 24,
              backgroundColor: mau.chinh,
              padding: 20,
            }}
          >
            <View
              style={{
                flexDirection: "row",
                justifyContent: "space-between",
                alignItems: "center",
                gap: 12,
              }}
            >
              <Chu
                size={12}
                dam="damVua"
                color={mau.trenChinh}
                style={{
                  textTransform: "uppercase",
                  letterSpacing: 1.2,
                  opacity: 0.8,
                }}
              >
                {thoiDiem(lich.bat_dau_luc).split(" ")[0]}
              </Chu>
              <Nhan trangThai={lich.trang_thai}>
                {trangThaiLich[lich.trang_thai]}
              </Nhan>
            </View>
            <Chu
              size={34}
              dam="ratDam"
              color={mau.trenChinh}
              style={{ marginTop: 8, lineHeight: 34, letterSpacing: -0.85 }}
            >
              {gioVietNam(lich.bat_dau_luc)}
            </Chu>
            <Chu
              size={14}
              color={mau.trenChinh}
              style={{ opacity: 0.85, marginTop: 4 }}
            >
              Đến {gioVietNam(lich.ket_thuc_luc)} · 60 phút
            </Chu>
          </View>
          <The style={{ flexDirection: "row", alignItems: "center", gap: 12 }}>
            <AnhDaiDien
              ten={
                (vaiTro === "KHACH_HANG" ? lich.pt : lich.khach_hang) || "Hồ sơ"
              }
            />
            <View style={{ flex: 1 }}>
              <Chu size={12} color={mau.chuPhu}>
                {vaiTro === "KHACH_HANG" ? "Huấn luyện viên" : "Học viên"}
              </Chu>
              <Chu dam="dam">
                {vaiTro === "KHACH_HANG" ? lich.pt : lich.khach_hang}
              </Chu>
            </View>
            {vaiTro !== "KHACH_HANG" && lich.khach_hang_id && (
              <Nut
                sm
                loai="soft"
                onPress={() =>
                  navigation.navigate("HoSoHocVien", { id: lich.khach_hang_id })
                }
              >
                Hồ sơ
              </Nut>
            )}
          </The>
          <The style={{ gap: 12 }}>
            <Chu
              size={12}
              dam="dam"
              color={mau.chuPhu}
              style={{ textTransform: "uppercase", letterSpacing: 1.2 }}
            >
              Tiến trình
            </Chu>
            <View style={{ flexDirection: "row" }}>
              {[
                ["Gửi yêu cầu", true],
                [
                  "HLV xác nhận",
                  ["DA_XAC_NHAN", "HOAN_THANH", "VANG_MAT"].includes(
                    lich.trang_thai,
                  ),
                ],
                ["Hoàn thành", lich.trang_thai === "HOAN_THANH"],
              ].map(([ten, xong], i) => (
                <View
                  key={ten}
                  style={{ flex: 1, alignItems: "center", gap: 6 }}
                >
                  <View
                    style={{
                      width: 28,
                      height: 28,
                      borderRadius: 14,
                      backgroundColor: xong ? mau.chinh : mau.vien,
                      alignItems: "center",
                      justifyContent: "center",
                    }}
                  >
                    {xong ? (
                      <BieuTuong
                        ten="Check"
                        size={15}
                        strokeWidth={3}
                        color={mau.trenChinh}
                      />
                    ) : (
                      <Chu size={13} color={mau.chuPhu}>
                        {i + 1}
                      </Chu>
                    )}
                  </View>
                  <Chu size={11.5} dam="vua" color={mau.chuPhu}>
                    {ten}
                  </Chu>
                </View>
              ))}
            </View>
          </The>
          <The>
            <Chu dam="damVua">Thông tin lịch hẹn</Chu>
            <Chu>Học viên: {lich.khach_hang}</Chu>
            <Chu>Huấn luyện viên: {lich.pt}</Chu>
            {lich.trang_thai === "CHO_XAC_NHAN" && (
              <Chu>
                Hạn xác nhận yêu cầu: {thoiDiem(lich.han_xac_nhan_dat_lich)}
              </Chu>
            )}
            {lich.trang_thai === "DA_XAC_NHAN" && (
              <Chu>
                Hạn ghi kết quả: {thoiDiem(lich.han_xac_nhan_hoan_thanh)}
              </Chu>
            )}
            {lich.tieu_hao_luc && (
              <Chu>Đã dùng 1 buổi PT · {thoiDiem(lich.tieu_hao_luc)}</Chu>
            )}
            {lich.ly_do_huy && <Chu>Lý do hủy/hết hạn: {lich.ly_do_huy}</Chu>}
            {lich.ly_do_ghi_nhan && (
              <Chu>Lý do ghi nhận: {lich.ly_do_ghi_nhan}</Chu>
            )}
            {lich.ly_do_dong_xu_ly && (
              <Chu>Lý do đóng xử lý: {lich.ly_do_dong_xu_ly}</Chu>
            )}
            {lich.ghi_nhan_luc && (
              <Chu>Ghi kết quả: {thoiDiem(lich.ghi_nhan_luc)}</Chu>
            )}
          </The>
          <The style={{ gap: 12 }}>
            <Chu dam="dam">Kết quả tập cùng PT</Chu>
            <Chu color={mau.chuPhu}>
              {vaiTro === "KHACH_HANG"
                ? "Xem bài tập, từng hiệp thực tế và nhận xét do PT ghi."
                : "Ghi bài tập, từng hiệp thực tế và nhận xét cho học viên. Chốt kết quả không tự hoàn thành lịch hoặc trừ lượt."}
            </Chu>
            <Nut
              icon="ClipboardList"
              disabled={dangGui}
              onPress={() => navigation.navigate("KetQuaBuoiPt", { id })}
            >
              {vaiTro === "KHACH_HANG"
                ? "Xem kết quả tập cùng PT"
                : "Ghi / xem kết quả tập cùng PT"}
            </Nut>
          </The>
          {!lich.hanh_dong?.length && (
            <Chu>
              Không có thao tác khả dụng cho lịch này ở thời điểm hiện tại.
            </Chu>
          )}
          <Nut loai="phu" disabled={dangGui} icon="refresh-cw" onPress={taiLai}>
            Cập nhật trạng thái
          </Nut>
        </>
      )}
      <HopXacNhan
        visible={!!tacVu}
        tieuDe={tacVu?.nhan}
        moTa={tacVu?.moTa}
        dangGui={dangGui}
        onDong={() => datHanhDong(null)}
        onGui={xungDot ? null : xacNhan}
        loi={loiGui?.message}
        nhanGui={tacVu?.nhan}
        loai={["huy", "tu-choi"].includes(hanhDong) ? "loi" : "chinh"}
      >
        {tacVu?.lyDo && (
          <TruongNhap
            nhan="Lý do *"
            value={lyDo}
            onChangeText={datLyDo}
            editable={!dangGui}
            maxLength={1000}
            multiline
            loi={loiLyDo || loiGui?.errors?.ly_do?.[0]}
          />
        )}
        {xungDot && (
          <Nut
            onPress={() => {
              datHanhDong(null);
              taiLai();
            }}
          >
            Tải lại lịch hẹn
          </Nut>
        )}
      </HopXacNhan>
    </ManHinh>
  );
}
