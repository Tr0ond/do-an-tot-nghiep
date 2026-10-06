import { View, Pressable } from "react-native";
import {
  Chu,
  BieuTuong,
  Nut,
  The,
  Nhan,
  AnhDaiDien,
  NutIcon,
} from "./GiaoDien";
import { Muc, TienDo, Trong } from "./FigmaElements";
import { TheLich } from "./HuanLuyen";
import { useGiaoDien } from "../theme";
import MascotTroLy from "./MascotTroLy";
import {
  gioVietNam,
  nhanNgay,
  ngayThangVietNam,
  trangThaiLich,
} from "../utils/lich";

export function DauTongQuan({ hoSo, laKhach, homNay, soThongBao, onThongBao }) {
  const { mau } = useGiaoDien();
  const gio = new Date().getHours();
  return (
    <View
      style={{
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "space-between",
        paddingTop: 24,
        paddingBottom: laKhach ? 8 : 12,
      }}
    >
      <View>
        <Chu size={13} color={mau.chuPhu}>
          {laKhach
            ? gio < 11
              ? "Chào buổi sáng"
              : gio < 18
                ? "Chào buổi chiều"
                : "Chào buổi tối"
            : nhanNgay(homNay)}
        </Chu>
        <Chu size={26} dam="ratDam" style={{ letterSpacing: -0.65 }}>
          {laKhach ? "" : "Chào "}
          {hoSo.ho_ten.split(" ").slice(-1)[0]} 👋
        </Chu>
      </View>
      <NutIcon
        icon="Bell"
        nhan={`Thông báo, ${soThongBao} chưa đọc`}
        badge={soThongBao > 0}
        onPress={onThongBao}
        style={{ width: 44, height: 44 }}
      />
    </View>
  );
}

export function TongQuanKhach({ d, navigation }) {
  const { mau } = useGiaoDien();
  const tiep = d.lich_sap_toi?.find((l) => l.loai === "PT");
  const tuTap = d.lich_sap_toi?.find((l) => l.loai !== "PT");
  const soBuoi =
    d.tien_do?.theo_ngay?.slice(-7).reduce((tong, n) => tong + n.so_buoi, 0) ??
    d.buoi_thang_nay;
  const canNang = d.chi_so?.moi_nhat?.can_nang_kg;
  return (
    <>
      <View
        style={{
          marginTop: 12,
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
          }}
        >
          <Chu
            size={12}
            dam="damVua"
            color={mau.trenChinh}
            style={{ letterSpacing: 0.6, opacity: 0.8 }}
          >
            BUỔI PT SẮP TỚI
          </Chu>
          {tiep && (
            <View
              style={{
                borderRadius: 999,
                paddingHorizontal: 10,
                paddingVertical: 4,
                backgroundColor: "rgba(255,255,255,0.2)",
              }}
            >
              <Chu size={11} dam="damVua" color={mau.trenChinh}>
                {trangThaiLich[tiep.trang_thai] || tiep.trang_thai}
              </Chu>
            </View>
          )}
        </View>
        {tiep ? (
          <>
            <Chu
              size={30}
              dam="ratDam"
              color={mau.trenChinh}
              style={{ lineHeight: 30, letterSpacing: -0.75, marginTop: 12 }}
            >
              {gioVietNam(tiep.bat_dau_luc)}
            </Chu>
            <Chu
              size={15}
              color={mau.trenChinh}
              style={{ opacity: 0.9, marginTop: 6 }}
            >
              {nhanNgay(tiep.bat_dau_luc?.slice(0, 10))} · {tiep.ten}
            </Chu>
            <View style={{ flexDirection: "row", gap: 8, marginTop: 20 }}>
              <Nut
                sm
                loai="lime"
                style={{ flex: 1 }}
                onPress={() =>
                  navigation.navigate("ChiTietLich", { id: tiep.id })
                }
              >
                Xem chi tiết
              </Nut>
              <Nut
                sm
                loai="ghost"
                style={{ backgroundColor: "rgba(255,255,255,0.15)" }}
                textStyle={{ color: mau.trenChinh }}
                onPress={() => navigation.navigate("TinNhan")}
              >
                Nhắn HLV
              </Nut>
            </View>
          </>
        ) : (
          <>
            <Chu
              size={22}
              dam="ratDam"
              color={mau.trenChinh}
              style={{ lineHeight: 27.5, letterSpacing: -0.55, marginTop: 8 }}
            >
              Chưa có lịch tập nào
            </Chu>
            <Chu
              color={mau.trenChinh}
              style={{ opacity: 0.85, marginTop: 4, marginBottom: 20 }}
            >
              Chọn khung giờ PT còn trống để bắt đầu.
            </Chu>
            <Nut
              sm
              loai="lime"
              icon="CalendarPlus"
              onPress={() => navigation.navigate("DatLich")}
            >
              Đặt lịch PT
            </Nut>
          </>
        )}
      </View>
      <View style={{ marginTop: 16 }}>
        {d.goi ? (
          <Pressable
            accessibilityRole="button"
            onPress={() => navigation.navigate("DonHang")}
          >
            <The style={{ gap: 0 }}>
              <View
                style={{
                  flexDirection: "row",
                  justifyContent: "space-between",
                }}
              >
                <View>
                  <Chu size={12} dam="damVua" color={mau.chuPhu}>
                    Gói đang dùng
                  </Chu>
                  <Chu size={17} dam="dam">
                    {d.goi.ten}
                  </Chu>
                </View>
                <BieuTuong ten="ChevronRight" size={18} color={mau.chuPhu} />
              </View>
              <View
                style={{
                  flexDirection: "row",
                  justifyContent: "space-between",
                  alignItems: "flex-end",
                  marginTop: 16,
                  marginBottom: 8,
                }}
              >
                <Chu>
                  <Chu
                    size={32}
                    dam="ratDam"
                    style={{ lineHeight: 32, letterSpacing: -0.8 }}
                  >
                    {d.goi.so_buoi_con_lai}
                  </Chu>
                  <Chu color={mau.chuPhu}>
                    {" "}
                    / {d.goi.so_buoi_pt} buổi còn lại
                  </Chu>
                </Chu>
                <Chu size={12} color={mau.chuPhu}>
                  Hết hạn {ngayThangVietNam(d.goi.het_han_luc)}
                </Chu>
              </View>
              <TienDo
                lime
                value={d.goi.so_buoi_pt - d.goi.so_buoi_con_lai}
                max={d.goi.so_buoi_pt}
              />
              {d.goi.so_buoi_con_lai <= 3 && (
                <Chu
                  size={12.5}
                  dam="damVua"
                  color={mau.vang}
                  style={{ marginTop: 12 }}
                >
                  Sắp hết buổi, hãy cân nhắc gia hạn.
                </Chu>
              )}
            </The>
          </Pressable>
        ) : (
          <The style={{ flexDirection: "row", alignItems: "center", gap: 16 }}>
            <View
              style={{
                width: 48,
                height: 48,
                borderRadius: 12,
                backgroundColor: mau.chinhNhat,
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              <BieuTuong ten="Package" size={22} />
            </View>
            <View style={{ flex: 1 }}>
              <Chu size={15} dam="dam">
                Chưa có gói tập
              </Chu>
              <Chu size={12.5} color={mau.chuPhu}>
                Mua gói để đặt lịch PT.
              </Chu>
            </View>
            <Nut sm onPress={() => navigation.navigate("GoiTap")}>
              Xem gói
            </Nut>
          </The>
        )}
      </View>
      <Muc>Truy cập nhanh</Muc>
      <View style={{ flexDirection: "row", gap: 8 }}>
        {[
          ["ClipboardList", "Giáo án", "GiaoAn"],
          ["Dumbbell", "Tự tập", "LichTuTap"],
          ["LineChart", "Chỉ số", "ChiSo"],
          ["Sparkles", "Trợ lý AI", "TroLy"],
        ].map(([icon, ten, man]) => (
          <Pressable
            key={man}
            accessibilityRole="button"
            onPress={() => navigation.navigate(man)}
            style={{
              flex: 1,
              borderRadius: 16,
              borderWidth: 1,
              borderColor: mau.vien,
              backgroundColor: mau.the,
              paddingVertical: 14,
              alignItems: "center",
              gap: 8,
            }}
          >
            <View
              style={{
                width: 40,
                height: 40,
                borderRadius: 20,
                backgroundColor: mau.chinhNhat,
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              {man === "TroLy" ? (
                <MascotTroLy />
              ) : (
                <BieuTuong ten={icon} size={20} />
              )}
            </View>
            <Chu size={12} dam="damVua">
              {ten}
            </Chu>
          </Pressable>
        ))}
      </View>
      <Muc phu="7 ngày gần nhất">Tiến độ tập luyện</Muc>
      <The style={{ padding: 0, gap: 0, flexDirection: "row" }}>
        <View style={{ flex: 1, padding: 16 }}>
          <View style={{ flexDirection: "row", alignItems: "center", gap: 6 }}>
            <BieuTuong ten="Flame" size={14} color={mau.chuPhu} />
            <Chu size={12} color={mau.chuPhu}>
              Buổi đã tập
            </Chu>
          </View>
          <Chu
            size={28}
            dam="ratDam"
            style={{ lineHeight: 28, letterSpacing: -0.7, marginTop: 4 }}
          >
            {soBuoi}
          </Chu>
        </View>
        <Pressable
          accessibilityRole="button"
          onPress={() => navigation.navigate("ChiSo")}
          style={{
            flex: 1,
            padding: 16,
            borderLeftWidth: 1,
            borderColor: mau.vien,
          }}
        >
          <Chu size={12} color={mau.chuPhu}>
            Cân nặng
          </Chu>
          {canNang != null ? (
            <>
              <Chu
                size={28}
                dam="ratDam"
                style={{ lineHeight: 28, letterSpacing: -0.7, marginTop: 4 }}
              >
                {canNang}
                <Chu size={13} dam="damVua" color={mau.chuPhu}>
                  {" "}
                  kg
                </Chu>
              </Chu>
              {d.chi_so.thay_doi_can_nang_kg != null && (
                <Chu
                  size={12}
                  dam="damVua"
                  color={mau.chinh}
                  style={{ marginTop: 4 }}
                >
                  {d.chi_so.thay_doi_can_nang_kg} kg từ đầu
                </Chu>
              )}
            </>
          ) : (
            <Chu
              size={13}
              dam="damVua"
              color={mau.chinh}
              style={{ marginTop: 8 }}
            >
              Ghi chỉ số đầu tiên
            </Chu>
          )}
        </Pressable>
      </The>
      <Muc onPress={() => navigation.navigate("LichTuTap")} nhan="Tất cả">
        Tự tập sắp tới
      </Muc>
      {tuTap ? (
        <Pressable
          accessibilityRole="button"
          onPress={() => navigation.navigate("BuoiTuTap", { id: tuTap.id })}
        >
          <The style={{ flexDirection: "row", alignItems: "center", gap: 12 }}>
            <View
              style={{
                width: 44,
                height: 44,
                borderRadius: 12,
                backgroundColor: mau.chinhNhat,
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              <BieuTuong ten="Dumbbell" size={20} />
            </View>
            <View style={{ flex: 1 }}>
              <Chu size={14.5} dam="dam">
                {tuTap.ten}
              </Chu>
              <Chu size={12.5} color={mau.chuPhu}>
                {nhanNgay(tuTap.ngay)}
              </Chu>
            </View>
            <BieuTuong ten="ChevronRight" size={18} color={mau.chuPhu} />
          </The>
        </Pressable>
      ) : (
        <Trong
          icon="Dumbbell"
          tieuDe="Chưa có buổi tự tập"
          moTa="Tạo buổi tự tập từ giáo án hoặc thư viện bài tập."
        />
      )}
      <Pressable
        accessibilityRole="button"
        onPress={() => navigation.navigate("TroLy")}
        style={{
          backgroundColor: mau.chinhNhat,
          borderRadius: 16,
          padding: 16,
          marginTop: 16,
          flexDirection: "row",
          gap: 12,
          alignItems: "center",
        }}
      >
        <MascotTroLy />
        <View style={{ flex: 1 }}>
          <Chu size={13.5}>
            <Chu size={13.5} dam="dam">
              Trợ lý AI
            </Chu>{" "}
            · cùng bạn lên kế hoạch tập luyện
          </Chu>
        </View>
      </Pressable>
      {d.giao_an && (
        <Pressable
          accessibilityRole="button"
          onPress={() => navigation.navigate("GiaoAn")}
          style={{ marginTop: 16 }}
        >
          <The>
            <Chu dam="dam">{d.giao_an.ten}</Chu>
            <Chu size={13} color={mau.chuPhu}>
              {d.giao_an.so_ngay_tap} ngày tập · {d.giao_an.so_bai} bài
            </Chu>
          </The>
        </Pressable>
      )}
    </>
  );
}

export function TongQuanHlv({ d, cho, navigation }) {
  const { mau } = useGiaoDien();
  return (
    <>
      <View style={{ flexDirection: "row", gap: 8 }}>
        {[
          [d.so_buoi_hom_nay, "Buổi hôm nay", false],
          [d.can_xu_ly.cho_dat_lich, "Yêu cầu chờ", true],
          [d.can_xu_ly.cho_ket_qua, "Chờ kết quả PT", false],
        ].map(([so, nhan, lime]) => (
          <View
            key={nhan}
            style={{
              flex: 1,
              borderRadius: 16,
              padding: 14,
              borderWidth: lime ? 0 : 1,
              borderColor: mau.vien,
              backgroundColor: lime ? mau.nangLuong : mau.the,
            }}
          >
            <Chu
              size={28}
              dam="ratDam"
              color={lime ? mau.trenNangLuong : mau.chu}
              style={{ lineHeight: 28, letterSpacing: -0.7 }}
            >
              {so}
            </Chu>
            <Chu
              size={11.5}
              dam="vua"
              color={lime ? mau.trenNangLuong : mau.chuPhu}
              style={{ marginTop: 6 }}
            >
              {nhan}
            </Chu>
          </View>
        ))}
      </View>
      <Muc
        nhan="Xem lịch"
        onPress={() =>
          navigation.navigate("Lich", { ngay: d.hom_nay, trangThai: "" })
        }
      >
        Lịch hôm nay
      </Muc>
      <View style={{ gap: 12 }}>
        {d.lich_hom_nay.length ? (
          d.lich_hom_nay.map((l) => (
            <TheLich
              key={l.id}
              lich={l}
              onPress={() => navigation.navigate("ChiTietLich", { id: l.id })}
            />
          ))
        ) : (
          <Trong
            tieuDe="Hôm nay trống lịch"
            moTa="Chưa có buổi nào được xác nhận trong hôm nay."
          />
        )}
      </View>
      <Muc>Yêu cầu đặt lịch</Muc>
      {cho?.duLieu?.data.length ? (
        <View style={{ gap: 12 }}>
          {cho.duLieu.data.map((l) => (
            <The key={l.id} style={{ gap: 0 }}>
              <Pressable
                accessibilityRole="button"
                onPress={() => navigation.navigate("ChiTietLich", { id: l.id })}
                style={{ flexDirection: "row", alignItems: "center", gap: 12 }}
              >
                <AnhDaiDien ten={l.khach_hang} />
                <View style={{ flex: 1 }}>
                  <Chu size={14.5} dam="dam">
                    {l.khach_hang}
                  </Chu>
                  <Chu size={12.5} color={mau.chuPhu}>
                    {nhanNgay(l.bat_dau_luc.slice(0, 10))} ·{" "}
                    {gioVietNam(l.bat_dau_luc)} – {gioVietNam(l.ket_thuc_luc)}
                  </Chu>
                </View>
                <Nhan loai="vang" icon="Clock">
                  {l.han_xac_nhan_dat_lich
                    ? `${Math.max(0, Math.round((new Date(l.han_xac_nhan_dat_lich) - Date.now()) / 60000))}p`
                    : "Chờ"}
                </Nhan>
              </Pressable>
              <View style={{ flexDirection: "row", gap: 8, marginTop: 12 }}>
                <Nut
                  sm
                  loai="phu"
                  icon="X"
                  style={{ flex: 1 }}
                  onPress={() =>
                    navigation.navigate("ChiTietLich", {
                      id: l.id,
                      moHanhDong: "tu-choi",
                    })
                  }
                >
                  Từ chối
                </Nut>
                <Nut
                  sm
                  icon="Check"
                  style={{ flex: 1 }}
                  onPress={() =>
                    navigation.navigate("ChiTietLich", {
                      id: l.id,
                      moHanhDong: "xac-nhan",
                    })
                  }
                >
                  Chấp nhận
                </Nut>
              </View>
            </The>
          ))}
          {cho.duLieu.meta.last_page > 1 && (
            <Nut
              sm
              loai="ghost"
              onPress={() =>
                navigation.navigate("Lich", {
                  ngay: "",
                  trangThai: "CHO_XAC_NHAN",
                })
              }
            >
              Tất cả yêu cầu đặt lịch
            </Nut>
          )}
        </View>
      ) : d.can_xu_ly.cho_dat_lich > 0 ? (
        <The>
          <Chu size={14.5} dam="dam">
            {d.can_xu_ly.cho_dat_lich} yêu cầu chờ xác nhận
          </Chu>
          {d.can_xu_ly.lich_dat && (
            <Nut
              sm
              onPress={() =>
                navigation.navigate("ChiTietLich", {
                  id: d.can_xu_ly.lich_dat.id,
                })
              }
            >
              Xem yêu cầu đầu tiên
            </Nut>
          )}
          <Nut
            sm
            loai="phu"
            onPress={() =>
              navigation.navigate("Lich", {
                ngay: "",
                trangThai: "CHO_XAC_NHAN",
              })
            }
          >
            Tất cả yêu cầu đặt lịch
          </Nut>
        </The>
      ) : (
        <Trong
          icon="Check"
          tieuDe="Không có yêu cầu chờ"
          moTa="Bạn đã xử lý hết các yêu cầu đặt lịch."
        />
      )}
      <Muc>Buổi PT chờ kết quả</Muc>
      {d.can_xu_ly.lich_ket_qua ? (
        <The>
          <Chu size={14.5} dam="dam">
            {d.can_xu_ly.cho_ket_qua} buổi chờ ghi kết quả
          </Chu>
          <Nut
            sm
            onPress={() =>
              navigation.navigate("ChiTietLich", {
                id: d.can_xu_ly.lich_ket_qua.id,
              })
            }
          >
            Ghi kết quả buổi tập
          </Nut>
        </The>
      ) : (
        <Chu size={13} color={mau.chuPhu} style={{ textAlign: "center" }}>
          Không có buổi PT nào chờ ghi kết quả.
        </Chu>
      )}
      <Muc nhan="Xem tất cả" onPress={() => navigation.navigate("HocVien")}>
        Học viên phụ trách
      </Muc>
      <View style={{ gap: 12 }}>
        {d.hoc_vien.map((h) => (
          <Pressable
            key={h.id}
            accessibilityRole="button"
            onPress={() => navigation.navigate("HoSoHocVien", { id: h.id })}
          >
            <The
              style={{ flexDirection: "row", alignItems: "center", gap: 12 }}
            >
              <AnhDaiDien ten={h.ho_ten} />
              <View style={{ flex: 1 }}>
                <Chu size={15} dam="dam">
                  {h.ho_ten}
                </Chu>
                <Chu size={12.5} color={mau.chuPhu}>
                  {h.muc_tieu || "Xem hồ sơ tập luyện"}
                </Chu>
              </View>
              <BieuTuong ten="ChevronRight" size={18} color={mau.chuPhu} />
            </The>
          </Pressable>
        ))}
      </View>
      <Muc>Tuần này</Muc>
      <The>
        <Chu size={13}>
          {d.tuan.hoan_thanh} buổi PT hoàn thành / {d.tuan.da_len_lich} lịch hẹn
        </Chu>
        <Chu size={13} color={mau.chuPhu}>
          {d.tuan.vang_mat} vắng mặt · {d.tuan.da_huy} đã hủy
        </Chu>
        <Nut
          sm
          loai="phu"
          icon="CalendarClock"
          onPress={() => navigation.navigate("KhungGio")}
        >
          Quản lý khung giờ · {d.khung_gio?.con_trong ?? 0} trống
        </Nut>
      </The>
    </>
  );
}
