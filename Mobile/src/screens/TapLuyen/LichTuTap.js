import { useRef, useState } from "react";
import { View, Pressable } from "react-native";
import { useGiaoDien } from "../../theme";
import { ChonPhan, DaiNgay, Trong } from "../../components/FigmaElements";
import * as Crypto from "expo-crypto";
import {
  ManHinh,
  Chu,
  Nut,
  The,
  Nhan,
  BieuTuong,
  NutIcon,
} from "../../components/GiaoDien";
import {
  TrangThaiTai,
  PhanTrang,
  ChonNgay,
  HopXacNhan,
} from "../../components/HuanLuyen";
import { LuaChon, LoiGhi } from "../../components/TapLuyen";
import { useDuLieu } from "../../hooks/useDuLieu";
import { useThaoTac } from "../../hooks/useThaoTac";
import { useXemTruoc } from "../../contexts/XemTruocContext";
import { useTraoDoi } from "../../contexts/TraoDoiContext";
import { tapLuyenService as api } from "../../services/tapLuyenService";
import { homNay, doiNgay, nhanNgay } from "../../utils/lich";
import { nhanTrangThai, taoYeuCauGhi } from "../../utils/tapLuyen";

export default function LichTuTap({ navigation, route }) {
  const { vaiTro, goiDichVu } = useXemTruoc();
  const { dongBoLich } = useTraoDoi();
  const khachId = route.params?.khachId;
  const { mau } = useGiaoDien();
  const [boLoc, datBoLoc] = useState(false);
  const [muc, datMuc] = useState("up");
  const [thongKeMo, datThongKeMo] = useState(false);
  const [tu, datTu] = useState(doiNgay(homNay(), -29));
  const [den, datDen] = useState(doiNgay(homNay(), 7));
  const [trangThai, datTrangThai] = useState("");
  const [page, datPage] = useState(1);
  const [mo, datMo] = useState(false);
  const [ngay, datNgay] = useState(homNay());
  const [ngayThu, datNgayThu] = useState(null);
  const [keHoach, datKeHoach] = useState(null);
  const [cho, datCho] = useState(false);
  const yeuCau = useRef(taoYeuCauGhi(() => Crypto.randomUUID()));
  const ghi = useThaoTac();
  const tai = useDuLieu(
    (s) =>
      goiDichVu(async (t) => {
        const q = { tu_ngay: tu, den_ngay: den };
        if (trangThai || muc === "done") {
          return api.taiLichTap(
            t,
            vaiTro,
            khachId,
            { ...q, trang_thai: trangThai || "HOAN_THANH", page },
            s,
          );
        }
        // Backend chưa có bộ lọc nhiều trạng thái; ghép đúng hai trạng thái
        // sắp tới trong khoảng đã chọn để không bỏ sót buổi đang tập ở trang khác.
        const ketQua = await Promise.all(
          ["DA_LEN_LICH", "DANG_TAP"].map(async (tt) => {
            const dau = await api.taiLichTap(
              t,
              vaiTro,
              khachId,
              { ...q, trang_thai: tt, page: 1 },
              s,
            );
            const data = [...dau.data];
            for (let trang = 2; trang <= dau.meta.last_page; trang++) {
              const r = await api.taiLichTap(
                t,
                vaiTro,
                khachId,
                { ...q, trang_thai: tt, page: trang },
                s,
              );
              data.push(...r.data);
            }
            return { ...dau, data };
          }),
        );
        const data = ketQua
          .flatMap((r) => r.data)
          .sort((a, b) => a.ngay_tap.localeCompare(b.ngay_tap));
        return {
          ...ketQua[0],
          data,
          meta: {
            ...ketQua[0].meta,
            current_page: 1,
            last_page: 1,
            total: data.length,
          },
        };
      }),
    `${tu}|${den}|${trangThai}|${page}|${khachId}|${muc}|${dongBoLich}`,
  );
  const tk = tai.duLieu?.meta.thong_ke;
  function moTao() {
    if (cho) {
      datMo(true);
      return;
    }
    const k = tai.duLieu.meta.giao_an_dang_dung;
    datKeHoach(k);
    datNgayThu(k.cac_ngay[0]?.ngay_thu);
    datNgay(homNay());
    ghi.datLoiGui(null);
    datMo(true);
  }
  function gui() {
    ghi.gui(
      async () => {
        const d = {
          ke_hoach_tap_id: keHoach.id,
          ngay_thu: ngayThu,
          ngay_tap: ngay,
        };
        try {
          return await goiDichVu((t) =>
            api.taoLichTap(t, vaiTro, khachId, yeuCau.current.lay(d)),
          );
        } catch (e) {
          if (!e.status || e.status >= 500) datCho(true);
          else {
            yeuCau.current.xong();
            datCho(false);
          }
          throw e;
        }
      },
      async () => {
        datMo(false);
        datCho(false);
        yeuCau.current.xong();
        await tai.taiLai();
      },
    );
  }
  const ds = tai.duLieu?.data || [];
  return (
    <ManHinh
      bas
      tieuDe="Tự tập"
      onBack={() => navigation.goBack()}
      tacVu={
        <NutIcon
          size={18}
          icon="SlidersHorizontal"
          nhan="Lọc lịch tự tập"
          onPress={() => datBoLoc(true)}
        />
      }
      footer={
        tai.duLieu?.meta.giao_an_dang_dung && (
          <Nut icon="Plus" disabled={ghi.dangGui} onPress={moTao}>
            Tạo buổi tự tập
          </Nut>
        )
      }
    >
      <ChonPhan
        value={muc}
        onChange={(v) => {
          datMuc(v);
          datTrangThai("");
          datPage(1);
        }}
        options={[
          ["up", "Sắp tới"],
          ["done", "Đã tập"],
        ]}
      />
      {!tai.duLieu?.meta.giao_an_dang_dung && !tai.dangTai && !tai.loi && (
        <Trong
          icon="Dumbbell"
          tieuDe="Chưa có giáo án đang dùng"
          moTa="Áp dụng một giáo án trước khi tạo buổi tự tập."
        >
          <Nut
            sm
            onPress={() =>
              navigation.navigate(
                vaiTro === "KHACH_HANG" ? "BanXemTruoc" : "GiaoAnHocVien",
                vaiTro === "KHACH_HANG" ? { screen: "GiaoAn" } : { khachId },
              )
            }
          >
            Mở giáo án
          </Nut>
        </Trong>
      )}
      <TrangThaiTai {...tai} />
      {!!tai.duLieu && !ds.length && (
        <Trong
          icon="Dumbbell"
          tieuDe={
            muc === "up" ? "Chưa có buổi tự tập" : "Chưa hoàn thành buổi nào"
          }
          moTa={
            muc === "up"
              ? "Tạo buổi tập riêng hoặc lấy từ giáo án đang dùng."
              : "Kết quả và nhận xét của HLV sẽ hiển thị ở đây."
          }
        />
      )}
      {tk && thongKeMo && (
        <The>
          <Chu size={20} dam="dam">
            Tiến độ đã hoàn thành
          </Chu>
          <Chu>
            {tk.so_buoi} buổi · {tk.so_hiep} hiệp · {tk.so_lan} lần lặp
          </Chu>
          <Chu>
            Khối lượng đã ghi: {tk.tong_khoi_luong_kg} kg · {tk.so_hiep_co_ta}{" "}
            hiệp có mức tạ
          </Chu>
          <Chu size={12}>
            Tổng tính từ buổi hoàn thành trong khoảng ngày đã chọn, không phụ
            thuộc bộ lọc trạng thái. Hiệp chưa ghi tạ không được tính vào tổng
            tạ.
          </Chu>
          {tk.theo_ngay.map((n) => (
            <Chu key={n.ngay_tap}>
              {nhanNgay(n.ngay_tap)} · {n.so_buoi} buổi
            </Chu>
          ))}
          {tk.theo_bai.map((b) => (
            <The key={b.bai_tap_id}>
              <Chu dam="dam">
                {b.ten_bai_tap} · Cao nhất {b.muc_ta_cao_nhat} kg
              </Chu>
              {b.cac_moc.map((m) => (
                <Chu key={m.ngay_tap}>
                  {nhanNgay(m.ngay_tap)} · {m.muc_ta_kg} kg
                </Chu>
              ))}
            </The>
          ))}
        </The>
      )}
      {ds.map((l) => (
        <Pressable
          key={l.id}
          accessibilityRole="button"
          onPress={() => navigation.navigate("BuoiTuTap", { id: l.id })}
        >
          <The style={{ flexDirection: "row", alignItems: "center", gap: 12 }}>
            <View
              style={{
                width: 56,
                height: 56,
                borderRadius: 12,
                backgroundColor: mau.chinhNhat,
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              <Chu size={11} dam="damVua" color={mau.chinh}>
                {
                  ["CN", "T2", "T3", "T4", "T5", "T6", "T7"][
                    new Date(`${l.ngay_tap}T00:00:00Z`).getUTCDay()
                  ]
                }
              </Chu>
              <Chu size={17} dam="ratDam" color={mau.chinh}>
                {l.ngay_tap.slice(8)}
              </Chu>
            </View>
            <View style={{ flex: 1 }}>
              <Chu size={15} dam="dam" numberOfLines={1}>
                {l.ten_ke_hoach}
              </Chu>
              <Chu size={12.5} color={mau.chuPhu}>
                Buổi {l.ngay_thu} · {l.ngay_tap.slice(8)}/
                {l.ngay_tap.slice(5, 7)}
              </Chu>
            </View>
            <Nhan trangThai={l.trang_thai}>{nhanTrangThai(l.trang_thai)}</Nhan>
          </The>
        </Pressable>
      ))}
      {tk && (
        <Nut
          sm
          loai="ghost"
          icon="ChartNoAxesColumn"
          onPress={() => datThongKeMo(!thongKeMo)}
        >
          {thongKeMo ? "Thu gọn tiến độ" : "Xem tiến độ tập luyện"}
        </Nut>
      )}
      <HopXacNhan
        visible={boLoc}
        tieuDe="Lọc lịch tự tập"
        onDong={() => datBoLoc(false)}
      >
        <The>
          <Chu dam="dam">Khoảng thời gian · Giờ Việt Nam</Chu>
          <Chu>Từ ngày</Chu>
          <ChonNgay
            value={tu}
            onChange={(v) => {
              datTu(v);
              datPage(1);
            }}
          />
          <Chu>Đến ngày</Chu>
          <ChonNgay
            value={den}
            onChange={(v) => {
              datDen(v);
              datPage(1);
            }}
          />
        </The>
        <LuaChon
          nhan="Trạng thái"
          cacMuc={[
            ["", "Tất cả"],
            ...["DA_LEN_LICH", "DANG_TAP", "HOAN_THANH", "DA_HUY"].map((v) => [
              v,
              nhanTrangThai(v),
            ]),
          ]}
          giaTri={trangThai}
          onChon={(v) => {
            datTrangThai(v);
            datPage(1);
          }}
        />
      </HopXacNhan>
      <PhanTrang
        meta={tai.duLieu?.meta}
        dangTai={tai.dangTai}
        onChange={datPage}
      />
      <HopXacNhan
        visible={mo}
        tieuDe="Lên lịch tự tập"
        moTa={keHoach?.ten_ke_hoach}
        dangGui={ghi.dangGui}
        loi={ghi.loiGui?.message}
        onDong={() => {
          if (!cho) {
            yeuCau.current.xong();
            datMo(false);
          } else datMo(false);
        }}
        onGui={gui}
      >
        <DaiNgay
          value={ngay}
          disabled={ghi.dangGui || cho}
          onChange={datNgay}
        />
        <ChonNgay
          value={ngay}
          disabled={ghi.dangGui || cho}
          onChange={datNgay}
        />
        <LuaChon
          nhan="Ngày trong giáo án"
          giaTri={ngayThu}
          disabled={ghi.dangGui || cho}
          onChon={datNgayThu}
          cacMuc={(keHoach?.cac_ngay || []).map((n) => [
            n.ngay_thu,
            `Ngày ${n.ngay_thu} · ${n.so_bai} bài`,
          ])}
        />
        {keHoach?.cac_ngay
          .find((n) => n.ngay_thu === ngayThu)
          ?.ten_bai.map((b, i) => (
            <Chu key={i}>{b}</Chu>
          ))}
        {cho && (
          <Chu>
            Chưa rõ kết quả lần gửi trước. Thử lại cùng lịch để tránh tạo trùng.
          </Chu>
        )}
      </HopXacNhan>
      {cho && !mo && (
        <The>
          <LoiGhi loi={ghi.loiGui} />
          <Nut onPress={() => datMo(true)}>Thử lại lịch đang chờ</Nut>
        </The>
      )}
    </ManHinh>
  );
}
