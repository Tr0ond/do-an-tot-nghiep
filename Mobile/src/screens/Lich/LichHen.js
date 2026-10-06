import { useEffect, useState } from "react";
import { RefreshControl, View, Pressable } from "react-native";
import {
  TieuDeTab,
  DaiNgay,
  ChonPhan,
  Trong,
  Chip,
  NhomChip,
} from "../../components/FigmaElements";
import { useGiaoDien } from "../../theme";
import {
  ManHinh,
  Chu,
  Nut,
  The,
  NutIcon,
  Nhan,
} from "../../components/GiaoDien";
import {
  ChonNgay,
  HopXacNhan,
  ChonTrangThai,
  PhanTrang,
  TheLich,
  TrangThaiTai,
} from "../../components/HuanLuyen";
import { useXemTruoc } from "../../contexts/XemTruocContext";
import { useTraoDoi } from "../../contexts/TraoDoiContext";
import { useDuLieu } from "../../hooks/useDuLieu";
import { tapLuyenService } from "../../services/tapLuyenService";
import { huanLuyenService as api } from "../../services/huanLuyenService";
import { homNay, doiNgay, nhanNgay, gioVietNam } from "../../utils/lich";

export default function LichHen({ navigation, route }) {
  const { vaiTro, goiDichVu } = useXemTruoc();
  const { dongBoLich } = useTraoDoi();
  const { mau } = useGiaoDien();
  const laKhach = vaiTro === "KHACH_HANG";
  const [muc, datMuc] = useState("plan");
  const [kieu, datKieu] = useState("day");
  const [boLoc, datBoLoc] = useState(false);
  const [ngay, datNgay] = useState(homNay());
  const [trangThai, datTrangThai] = useState("");
  const [page, datPage] = useState(1);
  const cacNgay = Array.from({ length: 7 }, (_, i) =>
    doiNgay(ngay || homNay(), i),
  );
  useEffect(() => {
    if (route.params?.ngay !== undefined) datNgay(route.params.ngay);
    if (route.params?.trangThai !== undefined)
      datTrangThai(route.params.trangThai);
    datPage(1);
  }, [route.params?.ngay, route.params?.trangThai]);
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (signal) =>
      goiDichVu(async (token) => {
        // API hỗ trợ ngày/trạng thái đơn. Chỉ ghép các trang khi giao diện
        // tuần hoặc lịch sử cần nhiều truy vấn, không tải toàn bộ lịch theo ngày.
        if (
          (muc === "plan" && kieu === "day") ||
          (muc === "history" && trangThai)
        ) {
          return api.taiLich(
            token,
            vaiTro,
            { ngay: muc === "plan" ? ngay : "", trang_thai: trangThai, page },
            signal,
          );
        }
        const truyVan =
          muc === "plan"
            ? cacNgay.map((n) => ({ ngay: n, trang_thai: trangThai }))
            : [
                "HOAN_THANH",
                "VANG_MAT",
                "DA_HUY",
                "HET_HAN",
                "QUA_HAN_XAC_NHAN",
              ].map((s) => ({ trang_thai: s }));
        const ketQua = await Promise.all(
          truyVan.map(async (q) => {
            const dau = await api.taiLich(
              token,
              vaiTro,
              { ...q, page: 1 },
              signal,
            );
            const ds = [...dau.data];
            for (let trang = 2; trang <= dau.meta.last_page; trang++) {
              const r = await api.taiLich(
                token,
                vaiTro,
                { ...q, page: trang },
                signal,
              );
              ds.push(...r.data);
            }
            return { ...dau, data: ds };
          }),
        );
        const data = [
          ...new Map(
            ketQua.flatMap((r) => r.data).map((l) => [l.id, l]),
          ).values(),
        ].sort((a, b) =>
          muc === "history"
            ? new Date(b.bat_dau_luc) - new Date(a.bat_dau_luc)
            : new Date(a.bat_dau_luc) - new Date(b.bat_dau_luc),
        );
        return {
          data,
          meta: { ...ketQua[0].meta, last_page: 1, current_page: 1 },
        };
      }),
    `${muc}|${kieu}|${ngay}|${trangThai}|${page}|${vaiTro}|${dongBoLich}`,
  );
  const ngayLich = (l) =>
    new Intl.DateTimeFormat("en-CA", {
      timeZone: "Asia/Ho_Chi_Minh",
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    }).format(new Date(l.bat_dau_luc));
  const ds = (duLieu?.data || []).filter(
    (l) =>
      muc === "history" ||
      trangThai ||
      (!laKhach
        ? !["DA_HUY", "HET_HAN"].includes(l.trang_thai)
        : ["CHO_XAC_NHAN", "DA_XAC_NHAN"].includes(l.trang_thai)),
  );
  const tuTap = useDuLieu(
    (s) =>
      laKhach && muc === "plan"
        ? goiDichVu(async (t) => {
            const q = {
              tu_ngay: ngay || homNay(),
              den_ngay:
                kieu === "week"
                  ? doiNgay(ngay || homNay(), 6)
                  : ngay || homNay(),
            };
            const dau = await tapLuyenService.taiLichTap(
              t,
              vaiTro,
              undefined,
              q,
              s,
            );
            const data = [...dau.data];
            for (let trang = 2; trang <= dau.meta.last_page; trang++) {
              const r = await tapLuyenService.taiLichTap(
                t,
                vaiTro,
                undefined,
                { ...q, page: trang },
                s,
              );
              data.push(...r.data);
            }
            return { ...dau, data };
          })
        : Promise.resolve(null),
    `${ngay}|${kieu}|${vaiTro}|${muc}|${dongBoLich}`,
  );
  const dsTuTap =
    tuTap.duLieu?.data.filter(
      (l) => !["HOAN_THANH", "DA_HUY"].includes(l.trang_thai),
    ) || [];
  function chonNgay(n) {
    datNgay(n);
    datPage(1);
  }
  return (
    <ManHinh
      contentStyle={{ paddingTop: 0, gap: 12, paddingBottom: 96 }}
      noi={
        <Nut
          loai="lime"
          iconSize={20}
          icon={laKhach ? "CalendarPlus" : "CalendarClock"}
          style={{ height: 56, boxShadow: "0 10px 15px -3px rgba(0,0,0,0.2)" }}
          onPress={() => navigation.navigate(laKhach ? "DatLich" : "KhungGio")}
        >
          {laKhach ? "Đặt lịch PT" : "Quản lý khung giờ"}
        </Nut>
      }
      refreshControl={
        <RefreshControl refreshing={dangTai} onRefresh={taiLai} />
      }
    >
      <TieuDeTab
        style={{ paddingBottom: 4 }}
        tacVu={
          <NutIcon
            icon="SlidersHorizontal"
            size={18}
            nhan="Lọc lịch và chọn ngày khác"
            onPress={() => datBoLoc(true)}
          />
        }
      >
        Lịch {laKhach ? "tập" : "dạy"}
      </TieuDeTab>
      <ChonPhan
        value={muc}
        onChange={(v) => {
          datMuc(v);
          datTrangThai("");
          datPage(1);
        }}
        options={[
          ["plan", laKhach ? "Sắp tới" : "Lịch"],
          ["history", "Lịch sử"],
        ]}
      />
      {muc === "history" && (
        <NhomChip>
          {[
            ["", "Tất cả"],
            ["HOAN_THANH", "Hoàn thành"],
            ["VANG_MAT", "Vắng mặt"],
            ["DA_HUY", "Đã hủy / từ chối"],
            ["HET_HAN", "Hết hạn"],
            ["QUA_HAN_XAC_NHAN", "Quá hạn xác nhận"],
          ].map(([s, ten]) => (
            <Chip
              key={s}
              chon={trangThai === s}
              onPress={() => {
                datTrangThai(s);
                datPage(1);
              }}
            >
              {ten}
            </Chip>
          ))}
        </NhomChip>
      )}
      {muc === "plan" && (
        <>
          {laKhach && (
            <ChonPhan
              style={{ marginTop: 4 }}
              value={kieu}
              onChange={datKieu}
              options={[
                ["day", "Theo ngày"],
                ["week", "Theo tuần"],
              ]}
            />
          )}
          {kieu === "day" && <DaiNgay value={ngay} onChange={chonNgay} />}
          {kieu === "day" && (
            <Chu size={13} dam="damVua" color={mau.chuPhu}>
              {ngay === homNay()
                ? "Hôm nay"
                : ngay === doiNgay(homNay(), 1)
                  ? "Ngày mai"
                  : ngay
                    ? nhanNgay(ngay)
                    : "Tất cả ngày"}
            </Chu>
          )}
        </>
      )}
      <TrangThaiTai {...{ dangTai, loi, taiLai }} />
      {muc === "plan" && kieu === "week" ? (
        cacNgay.map((n) => (
          <View
            key={n}
            style={{ flexDirection: "row", gap: 12, marginBottom: 4 }}
          >
            <View style={{ width: 48, alignItems: "center", paddingTop: 4 }}>
              <Chu size={11} dam="damVua" color={mau.chuPhu}>
                {
                  ["CN", "T2", "T3", "T4", "T5", "T6", "T7"][
                    new Date(`${n}T00:00:00Z`).getUTCDay()
                  ]
                }
              </Chu>
              <Chu
                size={18}
                dam="ratDam"
                color={n === homNay() ? mau.chinh : mau.chu}
              >
                {n.slice(8)}
              </Chu>
            </View>
            <View style={{ flex: 1, gap: 8 }}>
              {ds
                .filter((l) => ngayLich(l) === n)
                .map((l) => (
                  <Pressable
                    key={l.id}
                    accessibilityRole="button"
                    style={{
                      flexDirection: "row",
                      justifyContent: "space-between",
                      alignItems: "center",
                      borderLeftWidth: 4,
                      borderColor: mau.chinh,
                      backgroundColor: mau.the,
                      borderRadius: 12,
                      padding: 12,
                      gap: 8,
                    }}
                    onPress={() =>
                      navigation.navigate("ChiTietLich", { id: l.id })
                    }
                  >
                    <Chu size={14} dam="dam">
                      {gioVietNam(l.bat_dau_luc)} – {gioVietNam(l.ket_thuc_luc)}
                    </Chu>
                    <Nhan trangThai={l.trang_thai}>
                      {l.trang_thai === "CHO_XAC_NHAN"
                        ? "Chờ xác nhận"
                        : "Đã xác nhận"}
                    </Nhan>
                  </Pressable>
                ))}
              {dsTuTap
                .filter((l) => l.ngay_tap === n)
                .map((l) => (
                  <Pressable
                    key={l.id}
                    accessibilityRole="button"
                    style={{
                      flexDirection: "row",
                      justifyContent: "space-between",
                      alignItems: "center",
                      borderLeftWidth: 4,
                      borderColor: mau.nangLuong,
                      backgroundColor: mau.the,
                      borderRadius: 12,
                      padding: 12,
                      gap: 8,
                    }}
                    onPress={() =>
                      navigation.navigate("BuoiTuTap", { id: l.id })
                    }
                  >
                    <Chu size={14} dam="dam" style={{ flex: 1 }}>
                      {l.ten_ke_hoach}
                    </Chu>
                    <Chu size={12} color={mau.chuPhu}>
                      Tự tập
                    </Chu>
                  </Pressable>
                ))}
              {!ds.some((l) => ngayLich(l) === n) &&
                !dsTuTap.some((l) => l.ngay_tap === n) && (
                  <Chu
                    size={13}
                    color={mau.chuPhu}
                    style={{ paddingVertical: 8 }}
                  >
                    Không có lịch
                  </Chu>
                )}
            </View>
          </View>
        ))
      ) : (
        <>
          {ds.map((lich) => (
            <TheLich
              key={lich.id}
              lich={lich}
              laKhach={laKhach}
              onPress={() =>
                navigation.navigate("ChiTietLich", { id: lich.id })
              }
            />
          ))}
          {muc === "plan" &&
            dsTuTap.map((l) => (
              <Nut
                key={l.id}
                loai="soft"
                icon="Dumbbell"
                onPress={() => navigation.navigate("BuoiTuTap", { id: l.id })}
              >
                {l.ten_ke_hoach} · Tự tập
              </Nut>
            ))}
          {duLieu && !ds.length && (muc === "history" || !dsTuTap.length) && (
            <Trong
              icon={muc === "history" ? "Clock" : "CalendarDays"}
              tieuDe={
                muc === "history"
                  ? "Chưa có lịch sử"
                  : laKhach
                    ? "Ngày trống"
                    : "Không có lịch"
              }
              moTa={
                muc === "history"
                  ? "Các buổi đã hoàn thành, hủy hoặc hết hạn sẽ hiển thị tại đây."
                  : laKhach
                    ? "Bạn chưa có lịch PT hoặc buổi tự tập trong ngày này."
                    : "Ngày này chưa có học viên đặt lịch."
              }
            >
              <Nut
                sm
                loai={laKhach ? "chinh" : "soft"}
                onPress={() =>
                  navigation.navigate(laKhach ? "DatLich" : "KhungGio")
                }
              >
                {laKhach ? "Đặt lịch PT" : "Mở khung giờ"}
              </Nut>
            </Trong>
          )}
        </>
      )}
      {tuTap.loi && muc === "plan" && <TrangThaiTai {...tuTap} />}
      <HopXacNhan
        visible={boLoc}
        tieuDe="Lọc lịch"
        onDong={() => datBoLoc(false)}
      >
        <ChonNgay value={ngay} onChange={chonNgay} />
        <Nut loai="phu" onPress={() => chonNgay(ngay ? "" : homNay())}>
          {ngay ? "Tất cả ngày" : "Hôm nay"}
        </Nut>
        <ChonTrangThai
          value={trangThai}
          onChange={(s) => {
            datTrangThai(s);
            datPage(1);
            if (s && !["CHO_XAC_NHAN", "DA_XAC_NHAN"].includes(s))
              datMuc("history");
          }}
        />
        {laKhach && (
          <Nut
            loai="soft"
            icon="Dumbbell"
            onPress={() => {
              datBoLoc(false);
              navigation.navigate("LichTuTap");
            }}
          >
            Lịch tự tập & nhật ký
          </Nut>
        )}
      </HopXacNhan>
      <PhanTrang meta={duLieu?.meta} dangTai={dangTai} onChange={datPage} />
    </ManHinh>
  );
}
