import { HopXacNhan } from '../../components/HuanLuyen'
import { View } from 'react-native'
import { Chip, NhomChip } from '../../components/FigmaElements'
import { useGiaoDien } from '../../theme'
import { useEffect, useRef, useState } from 'react'
import { Chu, The, Nut, NutIcon, TruongNhap } from '../../components/GiaoDien'
import { LoiGhi } from '../../components/TapLuyen'
import { useBanNhap } from '../../hooks/useBanNhap'
import { useThaoTac } from '../../hooks/useThaoTac'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { tapLuyenService as api } from '../../services/tapLuyenService'
import { homNay, ngayHopLe, doiNgay } from '../../utils/lich'
import { soNhap } from '../../utils/tapLuyen'

export default function GhiChiSo({ navigation, route }) {
  const { goiDichVu } = useXemTruoc()
  const form = useBanNhap(navigation)
  const { mau } = useGiaoDien()
  const ghi = useThaoTac()
  const [nangCao, datNangCao] = useState(false)
  const [daLuu, datDaLuu] = useState(false)
  const [choLuu, datChoLuu] = useState(false)
  const banGui = useRef(null)
  const c = route.params?.banGhi
  useEffect(() => {
    if (!form.ban)
      form.nhan({
        ngay_ghi: c?.ngay_ghi || homNay(),
        can_nang_kg: c?.can_nang_kg == null ? '' : String(c.can_nang_kg),
        chieu_cao_cm:
          c?.chieu_cao_cm == null
            ? String(route.params?.chieuCao || '')
            : String(c.chieu_cao_cm),
        ghi_chu: c?.ghi_chu || '',
      })
  }, [form.ban, c, route.params?.chieuCao])
  useEffect(() => {
    if (daLuu && !ghi.dangGui && !form.coSua) navigation.goBack()
  }, [daLuu, ghi.dangGui, form.coSua, navigation])
  const canTai = ghi.loiGui?.status === 409
  function sua(k, v) {
    if (!ghi.dangGui && !choLuu) form.datBan((d) => ({ ...d, [k]: v }))
  }
  function luu() {
    ghi.gui(
      async () => {
        const d = form.ban
        if (
          !ngayHopLe(d.ngay_ghi) ||
          d.ngay_ghi < '1900-01-01' ||
          d.ngay_ghi > homNay()
        )
          throw new Error(
            'Nhập ngày hợp lệ từ năm 1900 đến hôm nay (YYYY-MM-DD).',
          )
        const payload = {
          ngay_ghi: d.ngay_ghi,
          can_nang_kg: soNhap(d.can_nang_kg, 10, 500),
          chieu_cao_cm: soNhap(d.chieu_cao_cm, 50, 250),
          ghi_chu: d.ghi_chu.trim() || null,
          ...(c ? { updated_at: c.updated_at } : {}),
        }
        const gui = banGui.current || payload
        banGui.current = gui
        try {
          return await goiDichVu((t) => api.luuChiSo(t, c?.id, gui))
        } catch (e) {
          if (!e.status || e.status >= 500) datChoLuu(true)
          else {
            banGui.current = null
            datChoLuu(false)
          }
          throw e
        }
      },
      () => {
        form.nhan(form.ban)
        datDaLuu(true)
      },
    )
  }
  return (
    <View style={{ flex: 1 }}>
      <HopXacNhan
        visible
        tieuDe="Ghi chỉ số"
        dangGui={ghi.dangGui}
        onDong={() => navigation.goBack()}
        onGui={luu}
        nhanGui={ghi.dangGui ? 'Đang lưu…' : 'Lưu chỉ số'}
        chiNutGui
        khoaGui={!form.ban || canTai}
        tacVu={
          <NutIcon
            icon="SlidersHorizontal"
            size={18}
            nhan="Chọn ngày khác & ghi chú"
            onPress={() => datNangCao(!nangCao)}
            style={{
              width: 32,
              height: 27,
              borderWidth: 0,
              backgroundColor: 'transparent',
            }}
          />
        }
      >
        <LoiGhi loi={ghi.loiGui} />
        {choLuu && (
          <Chu>
            Chưa rõ kết quả lần lưu trước. Thử lại cùng số đo để tránh ghi
            trùng.
          </Chu>
        )}
        {canTai && (
          <The>
            <Chu>
              Nội dung bạn nhập được giữ. Hãy quay về lịch sử, tải lại rồi mở
              đúng bản ghi để sửa.
            </Chu>
            <Nut loai="phu" onPress={() => navigation.goBack()}>
              Quay về lịch sử
            </Nut>
          </The>
        )}
        {form.ban && (
          <View style={{ gap: 16 }}>
            <View>
              <Chu size={13} dam="damVua" style={{ marginBottom: 6 }}>
                Ngày
              </Chu>
              <NhomChip>
                {Array.from({ length: 7 }, (_, i) => doiNgay(homNay(), -i)).map(
                  (ngay, i) => (
                    <Chip
                      key={ngay}
                      disabled={ghi.dangGui || choLuu}
                      chon={form.ban.ngay_ghi === ngay}
                      onPress={() => sua('ngay_ghi', ngay)}
                    >
                      {i === 0
                        ? 'Hôm nay'
                        : i === 1
                          ? 'Hôm qua'
                          : ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][
                              new Date(`${ngay}T00:00:00Z`).getUTCDay()
                            ] +
                            ' ' +
                            ngay.slice(8) +
                            '/' +
                            ngay.slice(5, 7)}
                    </Chip>
                  ),
                )}
              </NhomChip>
            </View>
            <View style={{ flexDirection: 'row', gap: 12 }}>
              {[
                ['can_nang_kg', 'Cân nặng (kg)'],
                ['chieu_cao_cm', 'Chiều cao (cm)'],
              ].map(([k, nhan]) => (
                <View key={k} style={{ flex: 1 }}>
                  <TruongNhap
                    nhan={nhan}
                    loi={ghi.loiGui?.errors?.[k]?.[0]}
                    value={form.ban[k]}
                    keyboardType="decimal-pad"
                    editable={!ghi.dangGui && !choLuu}
                    onChangeText={(v) => sua(k, v)}
                  />
                </View>
              ))}
            </View>
            <View
              style={{
                backgroundColor: mau.chinhNhat,
                borderRadius: 16,
                padding: 16,
                flexDirection: 'row',
                alignItems: 'center',
                justifyContent: 'space-between',
              }}
            >
              <Chu size={13}>BMI (hệ thống tự tính)</Chu>
              <Chu size={20} dam="dam" color={mau.chinh}>
                {Number(String(form.ban.chieu_cao_cm).replace(',', '.')) > 0 &&
                Number(String(form.ban.can_nang_kg).replace(',', '.')) > 0
                  ? (
                      Number(String(form.ban.can_nang_kg).replace(',', '.')) /
                      (Number(String(form.ban.chieu_cao_cm).replace(',', '.')) /
                        100) **
                        2
                    ).toFixed(1)
                  : '—'}
              </Chu>
            </View>
            {nangCao && (
              <View style={{ gap: 16 }}>
                <TruongNhap
                  nhan="Ngày ghi (YYYY-MM-DD) *"
                  loi={ghi.loiGui?.errors?.ngay_ghi?.[0]}
                  value={form.ban.ngay_ghi}
                  editable={!ghi.dangGui && !choLuu}
                  onChangeText={(v) => sua('ngay_ghi', v)}
                  maxLength={10}
                  placeholder="2026-10-05"
                />
                <TruongNhap
                  nhan="Ghi chú"
                  loi={ghi.loiGui?.errors?.ghi_chu?.[0]}
                  value={form.ban.ghi_chu}
                  editable={!ghi.dangGui && !choLuu}
                  onChangeText={(v) => sua('ghi_chu', v)}
                  multiline
                  maxLength={1000}
                />
                <Chu size={12}>
                  Mỗi ngày có một bản ghi. Chiều cao được lưu riêng cho ngày
                  này; BMI do hệ thống tính từ số đo đã lưu.
                </Chu>
              </View>
            )}
          </View>
        )}
        {form.hopRoi}
      </HopXacNhan>
    </View>
  )
}
