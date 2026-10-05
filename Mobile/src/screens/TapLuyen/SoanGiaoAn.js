import { useEffect, useRef, useState } from 'react'
import * as Crypto from 'expo-crypto'
import { View, Pressable } from 'react-native'
import {
  ManHinh,
  Chu,
  The,
  Nut,
  TruongNhap,
  BieuTuong,
} from '../../components/GiaoDien'
import { TrangThaiTai, HopXacNhan } from '../../components/HuanLuyen'
import { LoiGhi } from '../../components/TapLuyen'
import { useBanNhap } from '../../hooks/useBanNhap'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useThaoTac } from '../../hooks/useThaoTac'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { tapLuyenService as api } from '../../services/tapLuyenService'
import { noiDungKeHoach, taoYeuCauGhi } from '../../utils/tapLuyen'

import { useGiaoDien } from '../../theme'
import { Chip } from '../../components/FigmaElements'
import SoBuocFigma from '../../components/SoBuocFigma'
import MinhHoaBaiTap from '../../components/MinhHoaBaiTap'

const moi = () => ({
  ten_ke_hoach: '',
  muc_tieu: '',
  so_ngay_tap: '1',
  giao_an_mau_id: null,
  bai_tap: [],
})
const tuKeHoach = (k) => ({
  ten_ke_hoach: k.ten_ke_hoach || k.ten_giao_an,
  muc_tieu: k.muc_tieu || '',
  so_ngay_tap: String(k.so_ngay_tap),
  giao_an_mau_id: k.giao_an_mau_id ?? null,
  bai_tap: (k.bai_tap || []).map((b) => ({
    ...b,
    muc_ta_kg: b.muc_ta_kg == null ? '' : String(b.muc_ta_kg),
    ghi_chu: b.ghi_chu || '',
  })),
})
export default function SoanGiaoAn({ navigation, route }) {
  const { id, khachId } = route.params || {}
  const { goiDichVu, vaiTro } = useXemTruoc()
  const { mau } = useGiaoDien()
  const [baiMo, datBaiMo] = useState(null)
  const form = useBanNhap(navigation)
  const ghi = useThaoTac()
  const yeuCau = useRef(taoYeuCauGhi(() => Crypto.randomUUID()))
  const banGui = useRef(null)
  const [phienBan, datPhienBan] = useState(null)
  const [choKetQua, datChoKetQua] = useState(false)
  const [daLuu, datDaLuu] = useState(null)
  const [xacNhanTai, datXacNhanTai] = useState(false)
  const lanChon = useRef(null)
  const tai = useDuLieu(
    (s) =>
      id
        ? goiDichVu((t) => api.taiKeHoach(t, vaiTro, id, s))
        : Promise.resolve({ data: null }),
    id || 'moi',
  )
  useEffect(() => {
    if (form.ban) return
    if (!id) form.nhan(moi())
    else if (tai.duLieu?.data) {
      form.nhan(tuKeHoach(tai.duLieu.data))
      datPhienBan(tai.duLieu.data.updated_at)
    }
  }, [id, tai.duLieu, form.ban])
  useEffect(() => {
    if (
      !form.ban ||
      !route.params?.lanChon ||
      lanChon.current === route.params.lanChon ||
      choKetQua
    )
      return
    lanChon.current = route.params.lanChon
    if (route.params.mauChon) {
      const mau = route.params.mauChon
      form.datBan({ ...tuKeHoach(mau), giao_an_mau_id: mau.id })
    } else if (route.params.baiChon) {
      const b = route.params.baiChon
      form.datBan((d) => ({
        ...d,
        bai_tap: [
          ...d.bai_tap,
          {
            bai_tap_id: b.id,
            ten_bai_tap: b.ten_tieng_viet || b.ten_bai_tap,
            ngay_thu: String(route.params.ngayChon || 1),
            so_hiep: '',
            so_lan_lap: '',
            nghi_giay: '',
            muc_ta_kg: '',
            ghi_chu: '',
          },
        ],
      }))
    }
  }, [route.params?.lanChon, form.ban, choKetQua])
  useEffect(() => {
    if (daLuu && !ghi.dangGui && !form.coSua)
      navigation.replace('ChiTietGiaoAn', { id: daLuu, khachId })
  }, [daLuu, ghi.dangGui, form.coSua, navigation, khachId])
  function sua(k, v) {
    if (!ghi.dangGui && !choKetQua) form.datBan((d) => ({ ...d, [k]: v }))
  }
  function suaBai(i, k, v) {
    if (!ghi.dangGui && !choKetQua)
      form.datBan((d) => ({
        ...d,
        bai_tap: d.bai_tap.map((b, j) => (j === i ? { ...b, [k]: v } : b)),
      }))
  }
  const khoa = ghi.dangGui || choKetQua
  const canTai =
    ghi.loiGui?.status === 409 ||
    (id && tai.duLieu?.data && tai.duLieu.data.updated_at !== phienBan)
  const coQuyen = !id || tai.duLieu?.data?.co_the_sua
  async function luu() {
    ghi.gui(
      async () => {
        const d = noiDungKeHoach(form.ban)
        const payload =
          banGui.current ||
          (id ? { ...d, updated_at: phienBan } : yeuCau.current.lay(d))
        banGui.current = payload
        return goiDichVu((t) => api.luuKeHoach(t, vaiTro, khachId, id, payload))
      },
      (r) => {
        yeuCau.current.xong()
        banGui.current = null
        datChoKetQua(false)
        form.nhan(form.ban)
        datDaLuu(r.data.id)
      },
    )
  }
  useEffect(() => {
    if (ghi.loiGui) {
      if (banGui.current && (!ghi.loiGui.status || ghi.loiGui.status >= 500))
        datChoKetQua(true)
      else if (ghi.loiGui.status) {
        yeuCau.current.xong()
        banGui.current = null
        datChoKetQua(false)
      }
    }
  }, [ghi.loiGui])
  return (
    <ManHinh
      bas
      tieuDe={id ? 'Chỉnh sửa giáo án' : 'Tạo giáo án'}
      onBack={() => navigation.goBack()}
      footer={
        form.ban && (
          <Nut
            disabled={
              ghi.dangGui || !!canTai || !coQuyen || (id && tai.dangTai)
            }
            onPress={luu}
          >
            {ghi.dangGui
              ? 'Đang lưu…'
              : choKetQua
                ? 'Thử lại nội dung đã gửi'
                : 'Lưu giáo án'}
          </Nut>
        )
      }
    >
      {!form.ban && <TrangThaiTai {...tai} />}
      <LoiGhi loi={ghi.loiGui} />
      {canTai && (
        <The>
          <Chu>
            Giáo án có thể đã thay đổi. Nội dung bạn nhập vẫn được giữ. Tải lại
            sẽ thay nội dung này bằng bản mới trên hệ thống.
          </Chu>
          <Nut
            loai="phu"
            disabled={ghi.dangGui}
            onPress={() => datXacNhanTai(true)}
          >
            Tải lại giáo án
          </Nut>
        </The>
      )}
      {id && !tai.dangTai && !coQuyen && (
        <Chu>Bạn không còn quyền sửa bản nháp này.</Chu>
      )}
      {choKetQua && (
        <Chu>
          Chưa rõ kết quả lần gửi trước. Hãy thử lại cùng nội dung; hệ thống sẽ
          không tạo giáo án trùng.
        </Chu>
      )}
      {form.ban && (
        <>
          <View style={{ gap: 20 }}>
            <TruongNhap
              nhan="Tên giáo án"
              placeholder="VD: Tăng cơ thân trên"
              loi={ghi.loiGui?.errors?.ten_ke_hoach?.[0]}
              value={form.ban.ten_ke_hoach}
              editable={!khoa}
              onChangeText={(v) => sua('ten_ke_hoach', v)}
              maxLength={255}
            />
            <View>
              <Chu size={13} dam="damVua" style={{ marginBottom: 6 }}>
                Mục tiêu
              </Chu>
              <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
                {['Giảm mỡ', 'Tăng cơ', 'Sức bền', 'Sức khỏe chung'].map(
                  (muc) => (
                    <Chip
                      key={muc}
                      chon={form.ban.muc_tieu === muc}
                      disabled={khoa}
                      onPress={() => sua('muc_tieu', muc)}
                    >
                      {muc}
                    </Chip>
                  ),
                )}
              </View>
              <TruongNhap
                nhan="Mục tiêu khác"
                value={form.ban.muc_tieu}
                editable={!khoa}
                onChangeText={(v) => sua('muc_tieu', v)}
                maxLength={255}
              />
            </View>
            <View
              style={{
                flexDirection: 'row',
                justifyContent: 'space-between',
                alignItems: 'center',
                gap: 12,
              }}
            >
              <Chu size={13} dam="damVua" style={{ flex: 1 }}>
                Số buổi trong giáo án
              </Chu>
              <SoBuocFigma
                nhan="Số buổi"
                value={form.ban.so_ngay_tap}
                min={1}
                max={30}
                disabled={khoa}
                onChange={(v) => sua('so_ngay_tap', v)}
              />
            </View>
          </View>
          {vaiTro === 'HUAN_LUYEN_VIEN' && (
            <Nut
              loai="phu"
              disabled={khoa}
              onPress={() =>
                navigation.navigate('GiaoAnMau', { chonCho: { id, khachId } })
              }
            >
              Sao chép giáo án mẫu đã duyệt
            </Nut>
          )}
          <Chu size={19} dam="dam">
            Bài tập · {form.ban.bai_tap.length}
          </Chu>
          {Array.from(
            { length: Number(form.ban.so_ngay_tap) || 1 },
            (_, n) => n + 1,
          ).map((ngay) => (
            <View
              key={ngay}
              style={{
                borderRadius: 16,
                borderWidth: 1,
                borderColor: mau.vien,
                backgroundColor: mau.the,
                padding: 16,
                gap: 12,
              }}
            >
              <View
                style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}
              >
                <View
                  style={{
                    width: 32,
                    height: 32,
                    borderRadius: 16,
                    backgroundColor: mau.chinhNhat,
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  <Chu size={14} dam="ratDam" color={mau.chinh}>
                    {ngay}
                  </Chu>
                </View>
                <Chu size={15} dam="dam" style={{ flex: 1 }}>
                  Buổi {ngay}
                </Chu>
                <Chu size={12} color={mau.chuPhu}>
                  {
                    form.ban.bai_tap.filter((b) => Number(b.ngay_thu) === ngay)
                      .length
                  }{' '}
                  bài
                </Chu>
              </View>
              {form.ban.bai_tap.map(
                (b, i) =>
                  Number(b.ngay_thu) === ngay && (
                    <View
                      key={i}
                      style={{
                        borderRadius: 12,
                        borderWidth: 1,
                        borderColor: mau.vien,
                        backgroundColor: mau.the,
                        overflow: 'hidden',
                      }}
                    >
                      <Pressable
                        accessibilityRole="button"
                        accessibilityState={{ expanded: baiMo === i }}
                        onPress={() => datBaiMo(baiMo === i ? null : i)}
                        style={{
                          padding: 10,
                          flexDirection: 'row',
                          alignItems: 'center',
                          gap: 12,
                        }}
                      >
                        <MinhHoaBaiTap />
                        <View style={{ flex: 1 }}>
                          <Chu size={14} dam="dam">
                            {b.ten_bai_tap}
                          </Chu>
                          <Chu size={12.5} color={mau.chuPhu}>
                            {b.so_hiep || '—'} × {b.so_lan_lap || '—'} · nghỉ{' '}
                            {b.nghi_giay || '—'}s · Buổi {b.ngay_thu}
                          </Chu>
                        </View>
                        <BieuTuong
                          ten={baiMo === i ? 'ChevronUp' : 'ChevronDown'}
                          size={18}
                          color={mau.chuPhu}
                        />
                      </Pressable>
                      {baiMo === i && (
                        <View
                          style={{
                            borderTopWidth: 1,
                            borderColor: mau.vien,
                            padding: 12,
                            gap: 12,
                          }}
                        >
                          {[
                            [
                              'ngay_thu',
                              'Buổi thứ',
                              1,
                              Number(form.ban.so_ngay_tap) || 30,
                              1,
                            ],
                            ['so_hiep', 'Số set', 1, 100, 1],
                            ['so_lan_lap', 'Số lần / set', 1, 1000, 1],
                            ['muc_ta_kg', 'Mức tạ (kg)', 0, 1000, 2.5],
                            ['nghi_giay', 'Nghỉ (giây)', 0, 3600, 15],
                          ].map(([k, ten, min, max, step]) => (
                            <View key={k}>
                              <View
                                style={{
                                  flexDirection: 'row',
                                  alignItems: 'center',
                                  justifyContent: 'space-between',
                                  gap: 8,
                                }}
                              >
                                <Chu size={13} dam="damVua" style={{ flex: 1 }}>
                                  {ten}
                                </Chu>
                                <SoBuocFigma
                                  nhan={`Bài ${i + 1} · ${ten}`}
                                  value={b[k]}
                                  min={min}
                                  max={max}
                                  step={step}
                                  disabled={khoa}
                                  onChange={(v) => suaBai(i, k, v)}
                                />
                              </View>
                              {ghi.loiGui?.errors?.[
                                `bai_tap.${i}.${k}`
                              ]?.[0] && (
                                <Chu size={12} color={mau.loi}>
                                  {ghi.loiGui.errors[`bai_tap.${i}.${k}`][0]}
                                </Chu>
                              )}
                            </View>
                          ))}
                          <TruongNhap
                            nhan="Ghi chú"
                            value={b.ghi_chu}
                            editable={!khoa}
                            onChangeText={(v) => suaBai(i, 'ghi_chu', v)}
                            maxLength={2000}
                            multiline
                          />
                          <View style={{ flexDirection: 'row', gap: 8 }}>
                            <Nut
                              sm
                              loai="soft"
                              style={{ flex: 1 }}
                              disabled={khoa || i === 0}
                              onPress={() => {
                                const a = [...form.ban.bai_tap]
                                ;[a[i - 1], a[i]] = [a[i], a[i - 1]]
                                sua('bai_tap', a)
                              }}
                            >
                              Đưa lên trước
                            </Nut>
                            <Nut
                              sm
                              loai="loi"
                              icon="Trash2"
                              style={{ flex: 1 }}
                              disabled={khoa}
                              onPress={() =>
                                sua(
                                  'bai_tap',
                                  form.ban.bai_tap.filter((_, j) => j !== i),
                                )
                              }
                            >
                              Xóa bài tập
                            </Nut>
                          </View>
                        </View>
                      )}
                    </View>
                  ),
              )}
              <Nut
                sm
                loai="soft"
                icon="Plus"
                disabled={khoa || form.ban.bai_tap.length >= 500}
                onPress={() =>
                  navigation.navigate('Catalog', {
                    chonCho: { id, khachId, ngayChon: ngay },
                  })
                }
              >
                Thêm bài tập
              </Nut>
            </View>
          ))}
          <Nut
            loai="phu"
            icon="plus"
            disabled={khoa || form.ban.bai_tap.length >= 500}
            onPress={() =>
              navigation.navigate('Catalog', { chonCho: { id, khachId } })
            }
          >
            Thêm bài từ thư viện
          </Nut>
          {form.ban.bai_tap.some(
            (b) =>
              !(
                Number(b.ngay_thu) >= 1 &&
                Number(b.ngay_thu) <= Number(form.ban.so_ngay_tap)
              ),
          ) && (
            <The>
              <Chu color={mau.loi}>
                Có bài tập nằm ngoài số buổi đã chọn. Tăng số buổi để sửa ngày
                của bài trước khi lưu.
              </Chu>
            </The>
          )}
          <Chu size={12}>
            Lưu bản nháp trước. Khi gửi hoặc áp dụng, mỗi ngày tập cần ít nhất
            một bài.
          </Chu>
        </>
      )}
      <HopXacNhan
        visible={xacNhanTai}
        tieuDe="Thay nội dung bằng bản mới?"
        moTa="Các thay đổi chưa lưu sẽ được bỏ."
        onDong={() => datXacNhanTai(false)}
        onGui={async () => {
          const r = await tai.taiLai()
          if (r?.data) {
            form.nhan(tuKeHoach(r.data))
            datPhienBan(r.data.updated_at)
            banGui.current = null
            yeuCau.current.xong()
            datChoKetQua(false)
            ghi.datLoiGui(null)
          }
          datXacNhanTai(false)
        }}
      />
      {form.hopRoi}
    </ManHinh>
  )
}
