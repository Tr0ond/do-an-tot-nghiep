import { useEffect, useRef, useState } from "react";
import { View, Pressable, TextInput, Platform } from "react-native";
import { font, useGiaoDien } from "../../theme";
import MinhHoaBaiTap from "../../components/MinhHoaBaiTap";
import * as Crypto from "expo-crypto";
import {
  ManHinh,
  Chu,
  The,
  Nut,
  Nhan,
  TruongNhap,
  NutIcon,
  BieuTuong,
} from "../../components/GiaoDien";
import { TienDo } from "../../components/FigmaElements";
import { TrangThaiTai, HopXacNhan } from "../../components/HuanLuyen";
import { LoiGhi } from "../../components/TapLuyen";
import { HuongDanBai } from "./ChiTietBaiTap";
import { useBanNhap } from "../../hooks/useBanNhap";
import { useDuLieu } from "../../hooks/useDuLieu";
import { useThaoTac } from "../../hooks/useThaoTac";
import { useXemTruoc } from "../../contexts/XemTruocContext";
import { tapLuyenService as api } from "../../services/tapLuyenService";
import {
  noiDungBuoiTap,
  nhanTrangThai,
  taoYeuCauGhi,
} from "../../utils/tapLuyen";
import { nhanNgay, thoiDiem } from "../../utils/lich";

const tuBuoi = (l) => ({
  ghi_chu: l.phien?.ghi_chu || "",
  bai_tap: l.bai_tap.map((b) => ({
    ...b,
    hiep_tap: b.hiep_tap.map((h) => ({
      ...h,
      khoi_luong_kg: h.khoi_luong_kg == null ? "" : String(h.khoi_luong_kg),
    })),
  })),
});
export default function BuoiTuTap({ navigation, route }) {
  const { vaiTro, goiDichVu, datThongBao } = useXemTruoc();
  const tai = useDuLieu(
    (s) => goiDichVu((t) => api.taiBuoiTap(t, vaiTro, route.params.id, s)),
    route.params.id,
  );
  const form = useBanNhap(navigation);
  const ghi = useThaoTac();
  const [phienBan, datPhienBan] = useState(null);
  const [thaoTac, datThaoTac] = useState(null);
  const [huongDan, datHuongDan] = useState(null);
  const [noiDung, datNoiDung] = useState("");
  const [choNhanXet, datChoNhanXet] = useState(false);
  const [choLuu, datChoLuu] = useState(false);
  const banGui = useRef(null);
  const yeuCau = useRef(taoYeuCauGhi(() => Crypto.randomUUID()));
  const { mau } = useGiaoDien();
  const l = tai.duLieu?.data;
  const [bayGio, datBayGio] = useState(Date.now());
  useEffect(() => {
    if (l?.trang_thai !== "DANG_TAP") return;
    datBayGio(Date.now());
    const dongHo = setInterval(() => datBayGio(Date.now()), 1000);
    return () => clearInterval(dongHo);
  }, [l?.trang_thai]);
  useEffect(() => {
    if (l && !form.ban) {
      form.nhan(tuBuoi(l));
      datPhienBan(l.updated_at);
    }
  }, [l, form.ban]);
  const canTai = ghi.loiGui?.status === 409 || (l && l.updated_at !== phienBan);
  const coGhi = !!l?.co_the_ghi && !canTai && !tai.dangTai;
  const khoaNhap = ghi.dangGui || choLuu;
  const hoanThanh = l?.trang_thai === "HOAN_THANH";
  const cacHiepDaLuu = l?.bai_tap.flatMap((b) => b.hiep_tap) || [];
  const tongHiep =
    l?.bai_tap.reduce(
      (tong, b) => tong + (b.noi_dung?.du_kien?.so_hiep || 0),
      0,
    ) || 0;
  const soGiay = l?.phien?.bat_dau_luc
    ? Math.max(
        0,
        Math.floor(
          ((l.phien.hoan_thanh_luc
            ? Date.parse(l.phien.hoan_thanh_luc)
            : bayGio) -
            Date.parse(l.phien.bat_dau_luc)) /
            1000,
        ),
      )
    : null;
  const dongHo =
    soGiay == null
      ? "—"
      : `${String(Math.floor(soGiay / 60)).padStart(2, "0")}:${String(soGiay % 60).padStart(2, "0")}`;
  function nhan(r) {
    banGui.current = null;
    datChoLuu(false);
    form.nhan(tuBuoi(r.data));
    datPhienBan(r.data.updated_at);
    ghi.datLoiGui(null);
  }
  function suaBai(i, hiep) {
    if (coGhi && !khoaNhap)
      form.datBan((d) => ({
        ...d,
        bai_tap: d.bai_tap.map((b, j) =>
          i === j ? { ...b, hiep_tap: hiep } : b,
        ),
      }));
  }
  function luu() {
    ghi.gui(
      async () => {
        const d = banGui.current || noiDungBuoiTap(form.ban, phienBan);
        banGui.current = d;
        try {
          return await goiDichVu((t) => api.luuBuoiTap(t, route.params.id, d));
        } catch (e) {
          if (!e.status || e.status >= 500) datChoLuu(true);
          else {
            banGui.current = null;
            datChoLuu(false);
          }
          throw e;
        }
      },
      async (r) => {
        nhan(r);
        await tai.taiLai();
        datThongBao({
          tieuDe: "Đã lưu kết quả nháp",
          noiDung: "Số hiệp thực tế đã được lưu. Buổi tập chưa hoàn thành.",
        });
      },
    );
  }
  function xuLy() {
    if (thaoTac === "tai-lai") {
      ghi.gui(
        async () => {
          const r = await tai.taiLai();
          if (!r) throw new Error("Chưa tải được buổi tập.");
          return r;
        },
        (r) => {
          nhan(r);
          datThaoTac(null);
        },
      );
      return;
    }
    ghi.gui(
      async () => {
        if (thaoTac === "nhan-xet") {
          const d = yeuCau.current.lay({ noi_dung: noiDung.trim() });
          try {
            return await goiDichVu((t) =>
              api.doiBuoiTap(t, vaiTro, route.params.id, thaoTac, d),
            );
          } catch (e) {
            if (!e.status || e.status >= 500) datChoNhanXet(true);
            else if (e.status === 422) {
              yeuCau.current.xong();
              datChoNhanXet(false);
            }
            throw e;
          }
        }
        return goiDichVu((t) =>
          api.doiBuoiTap(t, vaiTro, route.params.id, thaoTac, {
            updated_at: phienBan,
          }),
        );
      },
      async (r) => {
        nhan(r);
        datThaoTac(null);
        if (thaoTac === "nhan-xet") {
          yeuCau.current.xong();
          datNoiDung("");
          datChoNhanXet(false);
        }
        await tai.taiLai();
      },
    );
  }
  const nhanXet = l && (
    <The style={{ gap: 8 }}>
      <View style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
        <BieuTuong ten="MessageSquareText" size={16} />
        <Chu
          size={12}
          dam="dam"
          color={mau.chuPhu}
          style={{ textTransform: "uppercase", letterSpacing: 0.6 }}
        >
          Nhận xét của HLV
        </Chu>
      </View>
      {l.nhan_xet.length === 0 && (
        <Chu size={13.5} color={mau.chuPhu}>
          Đang chờ HLV nhận xét. Bạn sẽ nhận được thông báo khi có phản hồi.
        </Chu>
      )}
      {l.nhan_xet.map((n) => (
        <The key={n.id}>
          <Chu dam="dam">
            {n.ten_pt} · {thoiDiem(n.created_at)}
          </Chu>
          <Chu>{n.noi_dung}</Chu>
        </The>
      ))}
      {l.co_the_nhan_xet && (
        <Nut
          disabled={ghi.dangGui}
          onPress={() => {
            ghi.datLoiGui(null);
            datThaoTac("nhan-xet");
          }}
        >
          Thêm nhận xét
        </Nut>
      )}
    </The>
  );
  return (
    <ManHinh
      bas
      tacVu={
        coGhi && (
          <View
            style={{
              flexDirection: "row",
              gap: 6,
              alignItems: "center",
              backgroundColor: mau.chinhNhat,
              borderRadius: 999,
              paddingHorizontal: 12,
              paddingVertical: 6,
              marginRight: 4,
            }}
          >
            <BieuTuong ten="Clock" size={14} />
            <Chu size={13} dam="dam" color={mau.chinh}>
              {dongHo}
            </Chu>
          </View>
        )
      }
      truocNoiDung={
        coGhi && (
          <View
            style={{
              paddingHorizontal: 20,
              paddingVertical: 12,
              borderBottomWidth: 1,
              borderColor: mau.vien,
              backgroundColor: mau.nen,
            }}
          >
            <Chu size={12.5} dam="damVua" style={{ marginBottom: 6 }}>
              {cacHiepDaLuu.length}/{tongHiep} set đã lưu nháp
            </Chu>
            <TienDo value={cacHiepDaLuu.length} max={tongHiep} lime />
          </View>
        )
      }
      tieuDe={
        l?.trang_thai === "HOAN_THANH"
          ? "Kết quả buổi tập"
          : coGhi
            ? l?.ten_ke_hoach || "Đang tập"
            : "Chi tiết buổi tập"
      }
      footer={
        l &&
        ((l.co_the_bat_dau && !canTai) || coGhi) && (
          <View style={{ flexDirection: "row", gap: 12 }}>
            {l.co_the_bat_dau && !canTai && (
              <Nut
                style={{ flex: 1 }}
                icon="Play"
                disabled={ghi.dangGui}
                onPress={() => datThaoTac("bat-dau")}
              >
                Bắt đầu tập
              </Nut>
            )}
            {coGhi && (
              <>
                <Nut
                  style={{ flex: 1 }}
                  loai="phu"
                  disabled={ghi.dangGui}
                  onPress={luu}
                >
                  {ghi.dangGui ? "Đang lưu…" : "Lưu nháp"}
                </Nut>
                <Nut
                  style={{ flex: 1 }}
                  disabled={khoaNhap || form.coSua}
                  onPress={() => datThaoTac("hoan-thanh")}
                >
                  Hoàn thành
                </Nut>
              </>
            )}
          </View>
        )
      }
      onBack={() => navigation.goBack()}
    >
      <TrangThaiTai {...tai} />
      <LoiGhi loi={ghi.loiGui} />
      {(canTai || (!l && form.ban && !tai.dangTai)) && (
        <The>
          <Chu>
            Dữ liệu hoặc quyền có thể đã thay đổi. Nội dung chưa lưu vẫn được
            giữ.
          </Chu>
          <Nut
            disabled={ghi.dangGui}
            loai="phu"
            onPress={() => datThaoTac("tai-lai")}
          >
            Tải lại buổi tập
          </Nut>
        </The>
      )}
      {l && (
        <>
          {!coGhi && (
            <View
              style={{
                backgroundColor: mau.chinh,
                padding: 20,
                borderRadius: 24,
              }}
            >
              <View
                style={{
                  flexDirection: "row",
                  alignItems: "center",
                  justifyContent: "space-between",
                  gap: 12,
                }}
              >
                <Chu
                  size={12}
                  dam="damVua"
                  color={mau.trenChinh}
                  style={{
                    textTransform: "uppercase",
                    opacity: 0.8,
                    letterSpacing: 1.2,
                  }}
                >
                  {nhanNgay(l.ngay_tap)}
                </Chu>
                {!hoanThanh && (
                  <Nhan trangThai={l.trang_thai}>
                    {nhanTrangThai(l.trang_thai)}
                  </Nhan>
                )}
              </View>
              <Chu
                size={hoanThanh ? 22 : 24}
                dam="ratDam"
                color={mau.trenChinh}
                style={{ marginTop: hoanThanh ? 4 : 8, letterSpacing: -0.55 }}
              >
                {l.ten_ke_hoach}
              </Chu>
              {hoanThanh ? (
                <View style={{ flexDirection: "row", gap: 8, marginTop: 16 }}>
                  {[
                    [cacHiepDaLuu.length, "set xong"],
                    [
                      Math.round(
                        cacHiepDaLuu.reduce(
                          (tong, h) =>
                            tong +
                            Number(h.khoi_luong_kg || 0) *
                              Number(h.so_lan_lap || 0),
                          0,
                        ),
                      ).toLocaleString("vi-VN"),
                      "kg tổng tạ",
                    ],
                    [soGiay == null ? "—" : Math.round(soGiay / 60), "phút"],
                  ].map(([v, nhan]) => (
                    <View
                      key={nhan}
                      style={{
                        flex: 1,
                        backgroundColor: "#FFFFFF26",
                        borderRadius: 16,
                        padding: 12,
                      }}
                    >
                      <Chu
                        size={22}
                        dam="ratDam"
                        color={mau.trenChinh}
                        style={{ lineHeight: 22 }}
                      >
                        {v}
                      </Chu>
                      <Chu
                        size={11}
                        color={mau.trenChinh}
                        style={{ opacity: 0.85, marginTop: 4 }}
                      >
                        {nhan}
                      </Chu>
                    </View>
                  ))}
                </View>
              ) : (
                <Chu
                  size={13}
                  color={mau.trenChinh}
                  style={{ marginTop: 4, opacity: 0.85 }}
                >
                  {form.ban?.bai_tap.length || 0} bài tập · Buổi {l.ngay_thu}
                </Chu>
              )}
              {!hoanThanh && l.phien?.bat_dau_luc && (
                <Chu size={13} color={mau.trenChinh} style={{ opacity: 0.85 }}>
                  Bắt đầu: {thoiDiem(l.phien.bat_dau_luc)}
                </Chu>
              )}
              {!hoanThanh && l.phien?.hoan_thanh_luc && (
                <Chu size={13} color={mau.trenChinh} style={{ opacity: 0.85 }}>
                  Hoàn thành: {thoiDiem(l.phien.hoan_thanh_luc)}
                </Chu>
              )}
            </View>
          )}
          {hoanThanh && nhanXet}
          {form.ban?.bai_tap.map((b, i) => (
            <The
              key={b.id || b.bai_tap_id + ":" + i}
              style={{ padding: 16, gap: 0 }}
            >
              <Pressable
                accessibilityRole="button"
                onPress={() =>
                  datHuongDan({ ...b.noi_dung, ten_bai_tap: b.ten_bai_tap })
                }
                style={{
                  flexDirection: "row",
                  alignItems: "center",
                  gap: 12,
                  padding: 12,
                }}
              >
                <MinhHoaBaiTap />
                <View style={{ flex: 1 }}>
                  <Chu size={hoanThanh ? 14.5 : 15} dam="dam">
                    {b.ten_bai_tap}
                  </Chu>
                  {!hoanThanh && b.noi_dung?.du_kien && (
                    <Chu size={12.5} color={mau.chuPhu}>
                      Mục tiêu {b.noi_dung.du_kien.so_hiep} ×{" "}
                      {b.noi_dung.du_kien.so_lan_lap} ·{" "}
                      {b.noi_dung.du_kien.muc_ta_kg ?? "—"} kg · Nghỉ{" "}
                      {b.noi_dung.du_kien.nghi_giay}s
                    </Chu>
                  )}
                </View>
              </Pressable>
              {hoanThanh ? (
                <View
                  style={{
                    flexDirection: "row",
                    flexWrap: "wrap",
                    gap: 8,
                    borderTopWidth: 1,
                    borderColor: mau.vien,
                    padding: 12,
                  }}
                >
                  {b.hiep_tap.map((h, j) => (
                    <View
                      key={j}
                      accessibilityLabel={`Hiệp ${j + 1}: ${h.khoi_luong_kg ?? "—"} kg, ${h.so_lan_lap} lần, nghỉ ${h.nghi_giay} giây`}
                      style={{
                        backgroundColor: mau.chinhNhat,
                        borderRadius: 999,
                        paddingHorizontal: 12,
                        paddingVertical: 6,
                      }}
                    >
                      <Chu size={12.5} dam="damVua" color={mau.chinh}>
                        {h.khoi_luong_kg == null || h.khoi_luong_kg === ""
                          ? "—"
                          : h.khoi_luong_kg}
                        kg × {h.so_lan_lap}
                      </Chu>
                    </View>
                  ))}
                </View>
              ) : (
                <View
                  style={{
                    borderTopWidth: 1,
                    borderColor: mau.vien,
                    paddingHorizontal: 12,
                    paddingTop: 8,
                    paddingBottom: 12,
                    gap: 6,
                  }}
                >
                  <View
                    style={{
                      flexDirection: "row",
                      alignItems: "center",
                      gap: 8,
                    }}
                  >
                    <Chu
                      size={11}
                      dam="damVua"
                      color={mau.chuPhu}
                      style={{ width: 28 }}
                    >
                      Set
                    </Chu>
                    {["Kg", "Lần", "Nghỉ (s)"].map((ten) => (
                      <Chu
                        key={ten}
                        size={11}
                        dam="damVua"
                        color={mau.chuPhu}
                        style={{ flex: 1 }}
                      >
                        {ten}
                      </Chu>
                    ))}
                    {coGhi && <View style={{ width: 32 }} />}
                  </View>
                  {b.hiep_tap.map((h, j) => (
                    <View
                      key={j}
                      style={{
                        flexDirection: "row",
                        gap: 8,
                        alignItems: "center",
                        borderRadius: 12,
                        paddingVertical: 6,
                      }}
                    >
                      <Chu
                        size={13}
                        dam="dam"
                        color={mau.chuPhu}
                        style={{ width: 28, textAlign: "center" }}
                      >
                        {j + 1}
                      </Chu>
                      {["khoi_luong_kg", "so_lan_lap", "nghi_giay"].map((k) =>
                        coGhi ? (
                          <TextInput
                            key={k}
                            accessibilityLabel={`Bài ${i + 1}, hiệp ${j + 1} · ${k === "khoi_luong_kg" ? "Mức tạ kg" : k === "so_lan_lap" ? "Số lần lặp" : "Nghỉ giây"}`}
                            keyboardType={
                              k === "khoi_luong_kg"
                                ? "decimal-pad"
                                : "number-pad"
                            }
                            value={String(h[k] ?? "")}
                            editable={!khoaNhap}
                            onChangeText={(v) =>
                              suaBai(
                                i,
                                b.hiep_tap.map((a, n) =>
                                  n === j ? { ...a, [k]: v } : a,
                                ),
                              )
                            }
                            style={{
                              flex: 1,
                              minWidth: 0,
                              height: 40,
                              borderRadius: 8,
                              borderWidth: 1,
                              borderColor: mau.vien,
                              backgroundColor: mau.truongNhap,
                              fontFamily: font.damVua,
                              fontSize: 15,
                              color: mau.chu,
                              includeFontPadding: false,
                              textAlign: "center",
                              padding: 0,
                              ...(Platform.OS === "web"
                                ? { outlineStyle: "none" }
                                : {}),
                            }}
                          />
                        ) : (
                          <Chu
                            key={k}
                            size={12.5}
                            dam="damVua"
                            color={mau.chinh}
                            style={{ flex: 1 }}
                          >
                            {h[k] === "" || h[k] == null ? "—" : h[k]}
                          </Chu>
                        ),
                      )}
                      {coGhi && (
                        <NutIcon
                          icon="Trash2"
                          nhan={`Bỏ hiệp ${j + 1}`}
                          size={16}
                          color={mau.loi}
                          style={{
                            width: 32,
                            height: 40,
                            borderWidth: 0,
                            backgroundColor: "transparent",
                          }}
                          disabled={khoaNhap}
                          onPress={() =>
                            suaBai(
                              i,
                              b.hiep_tap.filter((_, n) => n !== j),
                            )
                          }
                        />
                      )}
                    </View>
                  ))}
                </View>
              )}
              {coGhi && (
                <Nut
                  style={{ margin: 12, marginTop: 0 }}
                  loai="phu"
                  disabled={khoaNhap || b.hiep_tap.length >= 20}
                  onPress={() =>
                    suaBai(i, [
                      ...b.hiep_tap,
                      { so_lan_lap: "", khoi_luong_kg: "", nghi_giay: "" },
                    ])
                  }
                >
                  Thêm hiệp thực tế
                </Nut>
              )}
            </The>
          ))}
          {coGhi && form.ban && (
            <>
              <TruongNhap
                nhan="Ghi chú buổi tập"
                value={form.ban.ghi_chu}
                editable={!khoaNhap}
                onChangeText={(v) => form.datBan((d) => ({ ...d, ghi_chu: v }))}
                multiline
                maxLength={2000}
              />
              {choLuu && (
                <Chu>
                  Chưa rõ kết quả lần lưu trước. Thử lại cùng kết quả đã gửi
                  trước khi thay đổi.
                </Chu>
              )}
              <Chu size={12}>
                Lưu các thay đổi trước khi hoàn thành. Mỗi bài cần ít nhất một
                hiệp thực tế.
              </Chu>
            </>
          )}
          {!coGhi && form.ban?.ghi_chu && (
            <The>
              <Chu dam="dam">Ghi chú buổi tập</Chu>
              <Chu>{form.ban.ghi_chu}</Chu>
            </The>
          )}
          {l.co_the_huy && !canTai && (
            <Nut
              loai="loi"
              disabled={ghi.dangGui}
              onPress={() => datThaoTac("huy")}
            >
              Hủy buổi tập
            </Nut>
          )}
          {!hoanThanh && nhanXet}
        </>
      )}
      <HopXacNhan
        visible={!!thaoTac}
        tieuDe={
          {
            "bat-dau": "Bắt đầu buổi tập?",
            "hoan-thanh": "Hoàn thành buổi tập?",
            huy: "Hủy buổi tự tập?",
            "nhan-xet": "Nhận xét cho học viên",
            "tai-lai": "Tải lại và bỏ thay đổi chưa lưu?",
          }[thaoTac]
        }
        moTa={
          thaoTac === "hoan-thanh"
            ? "Sau khi hoàn thành, kết quả không thể chỉnh sửa."
            : thaoTac === "huy"
              ? "Kết quả nháp đã lưu vẫn được giữ. Các thay đổi chưa lưu sẽ bị bỏ."
              : undefined
        }
        dangGui={ghi.dangGui}
        loi={ghi.loiGui?.message}
        onDong={() => datThaoTac(null)}
        onGui={xuLy}
      >
        {thaoTac === "nhan-xet" && (
          <TruongNhap
            nhan="Nội dung nhận xét *"
            value={noiDung}
            editable={!ghi.dangGui && !choNhanXet}
            onChangeText={datNoiDung}
            multiline
            maxLength={2000}
          />
        )}
        {choNhanXet && thaoTac === "nhan-xet" && (
          <Chu>Thử lại nhận xét đã gửi để tránh ghi trùng.</Chu>
        )}
      </HopXacNhan>
      <HopXacNhan
        visible={!!huongDan}
        tieuDe={huongDan?.ten_bai_tap}
        onDong={() => datHuongDan(null)}
      >
        {huongDan && <HuongDanBai bai={huongDan} />}
      </HopXacNhan>
      {form.hopRoi}
    </ManHinh>
  );
}
