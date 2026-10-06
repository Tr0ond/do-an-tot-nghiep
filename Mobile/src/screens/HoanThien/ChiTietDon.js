import { useEffect, useRef, useState } from "react";
import { AppState, Linking, RefreshControl, View } from "react-native";
import {
  Chu,
  ManHinh,
  The,
  Nut,
  Nhan,
  BieuTuong,
  NutIcon,
} from "../../components/GiaoDien";
import { TheDon } from "../../components/GoiTap";
import { TrangThaiTai, HopXacNhan } from "../../components/HuanLuyen";
import { useDuLieu } from "../../hooks/useDuLieu";
import { useThaoTac } from "../../hooks/useThaoTac";
import { useXemTruoc } from "../../contexts/XemTruocContext";
import { useGiaoDien } from "../../theme";
import { hoanThienService as api } from "../../services/hoanThienService";
import {
  conChoThanhToan,
  linkPayosHopLe,
  trangThaiDon,
  tien,
} from "../../utils/hoanThien";
import { thoiDiem } from "../../utils/lich";

export default function ChiTietDon({ navigation, route }) {
  const { id } = route.params;
  const { goiDichVu } = useXemTruoc();
  const { mau } = useGiaoDien();
  const [moc, datMoc] = useState(Date.now());
  const [ketQua, datKetQua] = useState("");
  const [chiTietMo, datChiTietMo] = useState(false);
  const [lanQuayVe, datLanQuayVe] = useState(0);
  const choQuayVe = useRef(false);
  const daDongBoLink = useRef(null);
  const { gui, dangGui, loiGui } = useThaoTac();
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (s) => goiDichVu((t) => api.chiTietDon(t, id, s)),
    id,
  );
  useEffect(() => {
    const timer = setInterval(() => datMoc(Date.now()), 1000);
    return () => clearInterval(timer);
  }, []);
  useEffect(() => {
    const nghe = AppState.addEventListener("change", (s) => {
      if (s === "active" && choQuayVe.current) {
        datLanQuayVe((n) => n + 1);
      }
    });
    return () => nghe.remove();
  }, []);
  useEffect(() => {
    // Chờ thao tác mở trình duyệt kết thúc để khóa chống gửi lặp không nuốt lần kiểm tra.
    if (!lanQuayVe || !choQuayVe.current || dangGui) return;
    choQuayVe.current = false;
    dongBo();
  }, [lanQuayVe, dangGui]);
  const d = duLieu?.data;
  useEffect(() => {
    const moc = route.params?.quayVe;
    if (
      !moc ||
      !d ||
      d.id !== id ||
      dangTai ||
      dangGui ||
      daDongBoLink.current === moc
    )
      return;
    daDongBoLink.current = moc;
    // Đọc quyền và trạng thái BE trước; đơn đã kích hoạt không cần hỏi lại cổng.
    if (d.trang_thai === "DANG_SU_DUNG")
      datKetQua("Hệ thống đã xác nhận thanh toán và kích hoạt gói.");
    else dongBo();
  }, [id, route.params?.quayVe, d, dangTai, dangGui]);
  const conHan = conChoThanhToan(d, moc);
  const giay = d
    ? Math.max(
        0,
        Math.ceil((new Date(d.han_thanh_toan).getTime() - moc) / 1000),
      )
    : 0;
  async function moPayos() {
    gui(
      () => goiDichVu((t) => api.taoLink(t, id)),
      async (r) => {
        if (!conChoThanhToan(r.data) || !linkPayosHopLe(r.data.url_thanh_toan))
          throw new Error(
            "Chưa có liên kết payOS hợp lệ. Hãy kiểm tra lại đơn hàng.",
          );
        choQuayVe.current = true;
        try {
          await Linking.openURL(r.data.url_thanh_toan);
        } catch (e) {
          choQuayVe.current = false;
          throw e;
        }
        datKetQua(
          "Sau khi thanh toán hoặc hủy, chọn Mở FitForge ở trang quay về. App sẽ kiểm tra trạng thái từ hệ thống.",
        );
      },
    );
  }
  function dongBo() {
    gui(
      () => goiDichVu((t) => api.dongBoDon(t, id)),
      async (r) => {
        datKetQua(
          `Trạng thái hệ thống: ${trangThaiDon[r.data.trang_thai] || r.data.trang_thai}.`,
        );
        await taiLai();
      },
    );
  }
  return (
    <ManHinh
      bas
      tieuDe="Chi tiết đơn hàng"
      tacVu={
        <View style={{ flexDirection: "row" }}>
          <NutIcon
            icon="RefreshCw"
            size={18}
            nhan="Kiểm tra trạng thái đơn"
            disabled={dangGui || dangTai}
            onPress={dongBo}
            style={{
              width: 40,
              height: 40,
              borderWidth: 0,
              backgroundColor: "transparent",
            }}
          />
          <NutIcon
            icon="SlidersHorizontal"
            size={18}
            nhan="Quyền lợi và giao dịch"
            onPress={() => datChiTietMo(true)}
            style={{
              width: 40,
              height: 40,
              borderWidth: 0,
              backgroundColor: "transparent",
            }}
          />
        </View>
      }
      onBack={() => navigation.goBack()}
      footer={
        d && (
          <View style={{ gap: 8 }}>
            {d.trang_thai === "CHO_THANH_TOAN" && (
              <Nut
                icon="ExternalLink"
                disabled={dangGui || !conHan}
                onPress={moPayos}
              >
                {dangGui ? "Đang xử lý…" : "Thanh toán với payOS"}
              </Nut>
            )}
            {d.trang_thai === "CHO_THANH_TOAN" ? (
              <Nut
                loai="soft"
                sm
                icon="RefreshCw"
                disabled={dangGui || dangTai}
                onPress={dongBo}
              >
                {dangGui ? "Đang kiểm tra…" : "Kiểm tra trạng thái"}
              </Nut>
            ) : (
              <Nut
                onPress={() =>
                  d.trang_thai === "DANG_SU_DUNG"
                    ? navigation.goBack()
                    : navigation.navigate("GoiTap")
                }
              >
                {d.trang_thai === "DANG_SU_DUNG"
                  ? "Hoàn tất"
                  : "Quay lại danh sách gói"}
              </Nut>
            )}
          </View>
        )
      }
      refreshControl={
        <RefreshControl refreshing={dangTai} onRefresh={taiLai} />
      }
    >
      <TrangThaiTai {...{ dangTai, loi, taiLai }} />
      {d && (
        <>
          <View
            style={{
              borderRadius: 24,
              padding: 20,
              backgroundColor:
                d.trang_thai === "CHO_THANH_TOAN"
                  ? mau.vangNhat
                  : mau.chinhNhat,
            }}
          >
            <View
              style={{
                flexDirection: "row",
                justifyContent: "space-between",
                alignItems: "center",
              }}
            >
              <Chu
                size={12}
                dam="dam"
                color={d.trang_thai === "CHO_THANH_TOAN" ? mau.vang : mau.chinh}
                style={{ textTransform: "uppercase", letterSpacing: 1.2 }}
              >
                Trạng thái
              </Chu>
              <Nhan
                trangThai={
                  d.trang_thai === "DANG_SU_DUNG"
                    ? "DA_THANH_TOAN"
                    : d.trang_thai
                }
                loai={d.trang_thai === "CHO_THANH_TOAN" ? "vang" : "chinh"}
              >
                {trangThaiDon[d.trang_thai] || d.trang_thai}
              </Nhan>
            </View>
            {d.trang_thai === "DANG_SU_DUNG" && (
              <View
                style={{
                  marginTop: 12,
                  width: 56,
                  height: 56,
                  borderRadius: 28,
                  backgroundColor: mau.nangLuong,
                  alignItems: "center",
                  justifyContent: "center",
                }}
              >
                <BieuTuong
                  ten="Check"
                  size={30}
                  strokeWidth={3}
                  color={mau.trenNangLuong}
                />
              </View>
            )}
            {d.trang_thai === "CHO_THANH_TOAN" && (
              <Chu
                size={44}
                dam="ratDam"
                color={mau.vang}
                style={{ marginTop: 12, lineHeight: 44, letterSpacing: -1.1 }}
              >
                {String(Math.floor(giay / 60)).padStart(2, "0")}:
                {String(giay % 60).padStart(2, "0")}
              </Chu>
            )}
            <Chu
              size={13.5}
              style={{ marginTop: 12, lineHeight: 21.9375 }}
              color={d.trang_thai === "CHO_THANH_TOAN" ? mau.vang : mau.chinh}
            >
              {d.trang_thai === "CHO_THANH_TOAN"
                ? conHan
                  ? "Hoàn tất thanh toán trong thời gian còn lại, đơn sẽ tự hết hạn."
                  : "Đã tới hạn thanh toán. Kiểm tra trạng thái hệ thống."
                : d.trang_thai === "DANG_SU_DUNG"
                  ? "Thanh toán thành công. Gói của bạn đã được kích hoạt."
                  : trangThaiDon[d.trang_thai] || d.trang_thai}
            </Chu>
          </View>
          <The style={{ gap: 12 }}>
            {[
              ["Mã đơn", d.ma_don_payos],
              ["Gói tập", d.ten_goi],
              ["Số buổi", `${d.so_buoi_pt} buổi PT`],
              ["Hiệu lực", `${d.thoi_han_ngay} ngày`],
              ["Phương thức", "payOS (chuyển khoản QR)"],
            ].map(([ten, giaTri]) => (
              <View
                key={ten}
                style={{
                  flexDirection: "row",
                  justifyContent: "space-between",
                  gap: 16,
                }}
              >
                <Chu size={14} color={mau.chuPhu}>
                  {ten}
                </Chu>
                <Chu
                  size={14}
                  dam="dam"
                  style={{ flex: 1, textAlign: "right" }}
                >
                  {giaTri}
                </Chu>
              </View>
            ))}
            <View
              style={{
                flexDirection: "row",
                alignItems: "center",
                justifyContent: "space-between",
                paddingTop: 12,
                borderTopWidth: 1,
                borderColor: mau.vien,
              }}
            >
              <Chu size={14} dam="damVua">
                Tổng thanh toán
              </Chu>
              <Chu size={20} dam="dam" color={mau.chinh}>
                {tien(d.gia)}
              </Chu>
            </View>
          </The>
          {d.trang_thai === "CHO_THANH_TOAN" && (
            <Chu size={12.5} color={mau.chuPhu} style={{ lineHeight: 20.3125 }}>
              Bạn sẽ được chuyển sang payOS để thanh toán. Sau khi hoàn tất,
              chọn “Mở FitForge” để quay lại ứng dụng. Gói chỉ được cấp sau khi
              hệ thống xác minh giao dịch.
            </Chu>
          )}
          {!!ketQua && <Chu accessibilityLiveRegion="polite">{ketQua}</Chu>}
          {!!loiGui && (
            <Chu color={mau.loi} accessibilityLiveRegion="polite">
              {loiGui.message}
            </Chu>
          )}
          {d.trang_thai === "CAN_DOI_SOAT" && (
            <The>
              <Chu>
                Giao dịch cần Admin đối soát. Giữ mã đơn và thông tin giao dịch
                để liên hệ phòng gym.
              </Chu>
            </The>
          )}
          <HopXacNhan
            visible={chiTietMo}
            tieuDe="Quyền lợi & giao dịch"
            onDong={() => datChiTietMo(false)}
          >
            <View style={{ gap: 16 }}>
              <TheDon don={d} />
              {d.thanh_toan?.map((t) => (
                <The key={t.id}>
                  <Chu dam="dam">
                    {trangThaiDon[t.trang_thai] || t.trang_thai}
                  </Chu>
                  <Chu>
                    {tien(t.so_tien)} · {thoiDiem(t.thanh_toan_luc)}
                  </Chu>
                  <Chu selectable>Mã giao dịch: {t.ma_giao_dich}</Chu>
                  {!!t.ly_do_doi_soat && <Chu>{t.ly_do_doi_soat}</Chu>}
                  {!!t.ly_do_hoan_tien && (
                    <Chu>
                      Hoàn tiền: {tien(t.so_tien_hoan)} · {t.ly_do_hoan_tien}
                    </Chu>
                  )}
                </The>
              ))}
            </View>
          </HopXacNhan>
        </>
      )}
    </ManHinh>
  );
}
