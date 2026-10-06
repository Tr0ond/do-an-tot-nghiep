import { useEffect, useRef, useState } from 'react'
import { TextInput, View } from 'react-native'
import { usePreventRemove } from '@react-navigation/native'
import {
  ManHinh,
  Chu,
  The,
  Nut,
  NutIcon,
  Nhan,
  TruongNhap,
} from '../../components/GiaoDien'
import { HopXacNhan, TrangThaiTai } from '../../components/HuanLuyen'
import { AnhBaiTap, LoiGhi } from '../../components/TapLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useThaoTac } from '../../hooks/useThaoTac'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { font, useGiaoDien } from '../../theme'
import { ketQuaBuoiPtService as api } from '../../services/ketQuaBuoiPtService'
import {
  banNhapKetQuaPt,
  noiDungKetQuaPt,
  duHiepDeChotPt,
  taoLanGuiKetQuaPt,
} from '../../utils/ketQuaBuoiPt'
import { thoiDiem, trangThaiLich } from '../../utils/lich'

export default function KetQuaBuoiPt(props) {
  return <NoiDungKetQuaPt key={props.route.params.id} {...props} />
}

function NoiDungKetQuaPt({ navigation, route }) {
  const { id } = route.params
  const { vaiTro, goiDichVu, datThongBao } = useXemTruoc()
  const { mau } = useGiaoDien()
  const laPt = vaiTro === 'HUAN_LUYEN_VIEN'
  const [duLieu, datDuLieu] = useState(null)
  const [ban, datBan] = useState(null)
  const [goc, datGoc] = useState(null)
  const [phienBan, datPhienBan] = useState(null)
  const [xungDot, datXungDot] = useState(false)
  const [biChan, datBiChan] = useState(false)
  const [hop, datHop] = useState(null)
  const [roi, datRoi] = useState(null)
  const [choRoi, datChoRoi] = useState(false)
  const [cho, datCho] = useState(null)
  const lanGui = useRef(taoLanGuiKetQuaPt())
  const lanGhi = useRef(0)
  const lanChon = useRef(null)
  const ghi = useThaoTac()
  const coSua = !!ban && JSON.stringify(ban) !== JSON.stringify(goc)
  const tai = useDuLieu(async (signal) => {
    const lan = lanGhi.current
    try {
      const r = await goiDichVu((t) => api.tai(t, vaiTro, id, signal))
      return r && { ...r, lanGhi: lan }
    } catch (e) {
      if (!signal.aborted && [401, 403, 404].includes(e.status)) {
        lanGhi.current++
        datBiChan(true)
        datBan(null)
        datGoc(null)
        datDuLieu(null)
        datCho(null)
        datHop(null)
        datRoi(null)
        lanGui.current = taoLanGuiKetQuaPt()
      }
      throw e
    }
  }, id)

  function nhan(d) {
    const moi = banNhapKetQuaPt(d.ket_qua)
    datDuLieu(d)
    datBan(moi)
    datGoc(moi)
    datPhienBan(d.ket_qua?.updated_at ?? null)
    datXungDot(false)
    datBiChan(false)
    ghi.datLoiGui(null)
  }
  useEffect(() => {
    const r = tai.duLieu
    if (!r || r.lanGhi !== lanGhi.current) return
    if (!coSua && !lanGui.current.lay()) nhan(r.data)
    else {
      datDuLieu(r.data)
      if ((r.data.ket_qua?.updated_at ?? null) !== phienBan) datXungDot(true)
    }
  }, [tai.duLieu])
  const coGhi =
    laPt &&
    !!duLieu?.co_the_ghi &&
    !biChan &&
    !xungDot &&
    !tai.dangTai &&
    !tai.loi
  const khoaNhap = !coGhi || ghi.dangGui || !!cho
  const coChot =
    coGhi && duLieu.co_the_chot && !coSua && duHiepDeChotPt(ban) && !cho
  useEffect(() => {
    const { baiChon, lanChon: lan } = route.params
    if (!baiChon || lan === lanChon.current || !ban || tai.dangTai) return
    lanChon.current = lan
    if (khoaNhap) return
    if (ban.bai_tap.some((b) => Number(b.bai_tap_id) === Number(baiChon.id))) {
      ghi.datLoiGui(new Error('Bài tập này đã có trong buổi tập.'))
      return
    }
    if (ban.bai_tap.length >= 30) return
    datBan((d) => ({
      ...d,
      bai_tap: [
        ...d.bai_tap,
        {
          bai_tap_id: baiChon.id,
          ten_bai_tap: baiChon.ten_tieng_viet || baiChon.ten_bai_tap,
          anh_url: baiChon.anh_url,
          gif_url: baiChon.gif_url,
          ghi_cong_media: baiChon.ghi_cong_media,
          hiep_tap: [],
        },
      ],
    }))
  }, [route.params.lanChon, tai.dangTai, ban, khoaNhap])

  usePreventRemove((coSua || !!cho) && !choRoi && !biChan, ({ data }) =>
    datRoi(data.action),
  )
  useEffect(() => {
    if (choRoi && roi) navigation.dispatch(roi)
  }, [choRoi, roi, navigation])

  function suaBai(i, hiep) {
    if (khoaNhap) return
    datBan((d) => ({
      ...d,
      bai_tap: d.bai_tap.map((b, j) =>
        i === j ? { ...b, hiep_tap: hiep } : b,
      ),
    }))
  }
  function gui(loai) {
    if (
      biChan ||
      (cho ? cho.loai !== loai : loai === 'chot' ? !coChot : !coGhi)
    )
      return
    ghi.gui(
      async () => {
        const lan = ++lanGhi.current
        try {
          const r = await lanGui.current.gui(
            loai,
            () =>
              loai === 'luu'
                ? noiDungKetQuaPt(ban, phienBan)
                : { updated_at: phienBan },
            (d) =>
              goiDichVu((t) =>
                loai === 'luu' ? api.luu(t, id, d) : api.chot(t, id, d),
              ),
          )
          return lan === lanGhi.current ? r : null
        } catch (e) {
          if (e.status === 409) datXungDot(true)
          if ([401, 403, 404].includes(e.status)) {
            datBiChan(true)
            datBan(null)
            datGoc(null)
            datDuLieu(null)
            datHop(null)
            datRoi(null)
          }
          throw e
        } finally {
          if (lan === lanGhi.current) datCho(lanGui.current.lay())
        }
      },
      (r) => {
        nhan(r.data)
        datHop(null)
        datRoi(null)
        datThongBao({
          tieuDe:
            loai === 'luu'
              ? 'Đã lưu kết quả nháp'
              : 'Đã chốt kết quả tập cùng PT',
          noiDung: r.message,
        })
      },
    )
  }
  function taiLai() {
    if (ghi.dangGui || cho) return
    if (coSua || xungDot) datHop('tai-lai')
    else tai.taiLai()
  }
  function boVaTai() {
    ghi.gui(
      async () => {
        const r = await tai.taiLai()
        if (!r)
          throw new Error(
            'Chưa tải được kết quả. Nội dung chưa lưu vẫn được giữ.',
          )
        return r
      },
      (r) => {
        nhan(r.data)
        datHop(null)
      },
    )
  }
  const lich = duLieu?.lich
  const ketQua = duLieu?.ket_qua
  return (
    <ManHinh
      bas
      tieuDe="Kết quả tập cùng PT"
      onBack={() => navigation.goBack()}
      footer={
        laPt &&
        !biChan &&
        ban &&
        (coGhi || cho) && (
          <View style={{ gap: 8 }}>
            {cho ? (
              <Nut disabled={ghi.dangGui} onPress={() => gui(cho.loai)}>
                {ghi.dangGui
                  ? 'Đang gửi…'
                  : cho.loai === 'luu'
                    ? 'Thử lại lưu nháp'
                    : 'Thử lại chốt kết quả'}
              </Nut>
            ) : (
              <View style={{ flexDirection: 'row', gap: 12 }}>
                <Nut
                  style={{ flex: 1 }}
                  loai="phu"
                  disabled={ghi.dangGui}
                  onPress={() => gui('luu')}
                >
                  {ghi.dangGui ? 'Đang gửi…' : 'Lưu nháp'}
                </Nut>
                <Nut
                  style={{ flex: 1 }}
                  disabled={!coChot || ghi.dangGui}
                  onPress={() => {
                    ghi.datLoiGui(null)
                    datHop('chot')
                  }}
                >
                  Chốt kết quả
                </Nut>
              </View>
            )}
          </View>
        )
      }
    >
      <TrangThaiTai {...tai} taiLai={taiLai} />
      <LoiGhi loi={ghi.loiGui} />
      {cho && (
        <The>
          <Chu color={mau.loi}>
            Chưa rõ thao tác trước đã được lưu hay chưa. Hãy thử lại cùng nội
            dung đã gửi trước khi sửa hoặc rời màn hình.
          </Chu>
        </The>
      )}
      {xungDot && !biChan && (
        <The>
          <Chu>
            Dữ liệu đã thay đổi trên thiết bị khác hoặc không còn trong thời
            gian được ghi. Nội dung chưa lưu vẫn được giữ.
          </Chu>
        </The>
      )}
      {lich && ban && !biChan && (
        <>
          <The
            style={{
              backgroundColor: mau.chinh,
              borderColor: mau.chinh,
              gap: 8,
            }}
          >
            <Chu size={12} dam="damVua" color={mau.trenChinh}>
              {thoiDiem(lich.bat_dau_luc)} – {thoiDiem(lich.ket_thuc_luc)}
            </Chu>
            <Chu size={22} dam="ratDam" color={mau.trenChinh}>
              {laPt ? lich.khach_hang : lich.pt}
            </Chu>
            <View style={{ flexDirection: 'row', gap: 8, flexWrap: 'wrap' }}>
              <Nhan trangThai={lich.trang_thai}>
                {trangThaiLich[lich.trang_thai]}
              </Nhan>
              <Nhan>
                {ketQua?.chot_luc
                  ? 'Đã chốt'
                  : ketQua
                    ? 'Bản nháp'
                    : 'Chưa ghi kết quả'}
              </Nhan>
            </View>
            <Chu color={mau.trenChinh}>
              {ban.bai_tap.length} bài ·{' '}
              {ban.bai_tap.reduce((s, b) => s + b.hiep_tap.length, 0)} hiệp thực
              tế
            </Chu>
          </The>
          {laPt ? (
            <The>
              <Chu>
                {duLieu.ly_do_khoa ||
                  'Lưu nháp sau mỗi hiệp. Lưu các thay đổi trước khi chốt; mỗi bài cần ít nhất một hiệp.'}
              </Chu>
              <Chu size={12} color={mau.chuPhu}>
                Chốt kết quả không tự hoàn thành lịch hoặc trừ lượt. Xác nhận
                hoàn thành riêng ở chi tiết lịch hẹn.
              </Chu>
            </The>
          ) : (
            <The>
              <Chu>
                Kết quả và nhận xét do PT ghi.{' '}
                {ketQua && !ketQua.chot_luc
                  ? 'Đây là bản nháp, PT có thể tiếp tục cập nhật.'
                  : ''}
              </Chu>
            </The>
          )}
          {!ban.bai_tap.length && (
            <The>
              <Chu dam="dam">Chưa có bài tập được ghi</Chu>
              <Chu color={mau.chuPhu}>
                {laPt && coGhi
                  ? 'Chọn bài từ thư viện để ghi từng hiệp thực tế.'
                  : 'Kết quả sẽ xuất hiện tại đây khi PT ghi buổi tập.'}
              </Chu>
            </The>
          )}
          {ban.bai_tap.map((b, i) => (
            <The key={b.bai_tap_id} style={{ gap: 12 }}>
              <View
                style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}
              >
                <AnhBaiTap bai={b} size={72} />
                <View style={{ flex: 1 }}>
                  <Chu size={16} dam="dam">
                    {i + 1}. {b.ten_bai_tap}
                  </Chu>
                  <Chu size={12} color={mau.chuPhu}>
                    {b.hiep_tap.length} hiệp thực tế
                  </Chu>
                </View>
                {coGhi && (
                  <NutIcon
                    icon="Trash2"
                    nhan={`Bỏ bài ${i + 1}`}
                    disabled={khoaNhap}
                    onPress={() => datHop({ boBai: i })}
                  />
                )}
              </View>
              <View style={{ flexDirection: 'row', gap: 8 }}>
                <Chu size={11} style={{ width: 28 }}>
                  Hiệp
                </Chu>
                {['Lần', 'Kg', 'Nghỉ (s)'].map((k) => (
                  <Chu
                    key={k}
                    size={12}
                    color={mau.chuPhu}
                    style={{ flex: 1, textAlign: 'center' }}
                  >
                    {k}
                  </Chu>
                ))}
                {coGhi && <View style={{ width: 40 }} />}
              </View>
              {b.hiep_tap.map((h, j) => (
                <View
                  key={j}
                  style={{ flexDirection: 'row', gap: 8, alignItems: 'center' }}
                >
                  <Chu
                    size={13}
                    dam="dam"
                    style={{ width: 28, textAlign: 'center' }}
                  >
                    {j + 1}
                  </Chu>
                  {['so_lan_lap', 'khoi_luong_kg', 'nghi_giay'].map((k) =>
                    coGhi ? (
                      <TextInput
                        key={k}
                        accessibilityLabel={`Bài ${i + 1}, hiệp ${j + 1}: ${k === 'so_lan_lap' ? 'Số lần lặp' : k === 'khoi_luong_kg' ? 'Tạ kg' : 'Nghỉ giây'}`}
                        keyboardType={
                          k === 'khoi_luong_kg' ? 'decimal-pad' : 'number-pad'
                        }
                        editable={!khoaNhap}
                        maxLength={12}
                        value={h[k]}
                        placeholder={k === 'khoi_luong_kg' ? '—' : ''}
                        placeholderTextColor={mau.chuPhu}
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
                          height: 44,
                          borderWidth: 1,
                          borderColor: mau.vien,
                          borderRadius: 8,
                          backgroundColor: mau.truongNhap,
                          color: mau.chu,
                          fontFamily: font.damVua,
                          fontSize: 14,
                          textAlign: 'center',
                          paddingHorizontal: 4,
                          includeFontPadding: false,
                        }}
                      />
                    ) : (
                      <Chu key={k} style={{ flex: 1, textAlign: 'center' }}>
                        {h[k] === '' ? '—' : h[k]}
                      </Chu>
                    ),
                  )}
                  {coGhi && (
                    <NutIcon
                      icon="Trash2"
                      size={16}
                      style={{ width: 40, height: 44 }}
                      nhan={`Bỏ hiệp ${j + 1} bài ${i + 1}`}
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
              {!b.hiep_tap.length && (
                <Chu size={13} color={mau.chuPhu}>
                  Chưa ghi hiệp thực tế.
                </Chu>
              )}
              {coGhi && (
                <Nut
                  loai="phu"
                  icon="Plus"
                  disabled={khoaNhap || b.hiep_tap.length >= 20}
                  onPress={() =>
                    suaBai(i, [
                      ...b.hiep_tap,
                      { so_lan_lap: '', khoi_luong_kg: '', nghi_giay: '' },
                    ])
                  }
                >
                  Thêm hiệp thực tế
                </Nut>
              )}
            </The>
          ))}
          {coGhi && (
            <Nut
              loai="phu"
              icon="Plus"
              disabled={khoaNhap || ban.bai_tap.length >= 30}
              onPress={() =>
                navigation.navigate('Catalog', {
                  chonCho: { manHinh: 'KetQuaBuoiPt', id },
                })
              }
            >
              Thêm bài tập
            </Nut>
          )}
          {coGhi ? (
            <>
              <TruongNhap
                nhan="Ghi chú buổi tập"
                multiline
                maxLength={2000}
                editable={!khoaNhap}
                value={ban.ghi_chu}
                onChangeText={(v) => datBan((d) => ({ ...d, ghi_chu: v }))}
              />
              <TruongNhap
                nhan="Nhận xét của PT"
                multiline
                maxLength={2000}
                editable={!khoaNhap}
                value={ban.nhan_xet}
                onChangeText={(v) => datBan((d) => ({ ...d, nhan_xet: v }))}
              />
              <Chu size={12} color={mau.chuPhu}>
                Tạ để trống: chưa ghi. 0 kg: không dùng tạ ngoài. Nghỉ nhập bằng
                giây.
              </Chu>
            </>
          ) : (
            <>
              {!!ban.ghi_chu && (
                <The>
                  <Chu dam="dam">Ghi chú buổi tập</Chu>
                  <Chu>{ban.ghi_chu}</Chu>
                </The>
              )}
              <The>
                <Chu dam="dam">Nhận xét của PT</Chu>
                <Chu>{ban.nhan_xet || 'Chưa có nhận xét của PT.'}</Chu>
              </The>
            </>
          )}
          {ketQua?.chot_luc && (
            <Chu size={12} color={mau.chuPhu}>
              Đã chốt: {thoiDiem(ketQua.chot_luc)} · Kết quả không thể chỉnh
              sửa.
            </Chu>
          )}
          <Nut
            loai="phu"
            icon="RefreshCw"
            disabled={ghi.dangGui || !!cho}
            onPress={taiLai}
          >
            Tải lại kết quả
          </Nut>
        </>
      )}
      <HopXacNhan
        visible={!!hop}
        dangGui={ghi.dangGui}
        onDong={() => datHop(null)}
        loi={ghi.loiGui?.message}
        tieuDe={
          hop === 'chot'
            ? 'Chốt kết quả buổi tập?'
            : hop === 'tai-lai'
              ? 'Tải lại kết quả?'
              : 'Bỏ bài tập?'
        }
        moTa={
          hop === 'chot'
            ? 'Sau khi chốt không thể sửa kết quả. Thao tác này chưa hoàn thành lịch hẹn và không trừ lượt PT.'
            : hop === 'tai-lai'
              ? 'Thay đổi chưa lưu sẽ bị bỏ khi tải lại thành công.'
              : 'Bài tập và các hiệp vừa nhập sẽ bị bỏ khỏi bản nháp.'
        }
        khoaGui={!!cho || (hop === 'chot' && !coChot)}
        onGui={
          hop === 'chot'
            ? () => gui('chot')
            : hop === 'tai-lai'
              ? boVaTai
              : () => {
                  if (!khoaNhap)
                    datBan((d) => ({
                      ...d,
                      bai_tap: d.bai_tap.filter((_, i) => i !== hop.boBai),
                    }))
                  datHop(null)
                }
        }
      />
      <HopXacNhan
        visible={!!roi}
        dangGui={ghi.dangGui}
        tieuDe={cho ? 'Thao tác chưa được xác nhận' : 'Bỏ thay đổi chưa lưu?'}
        moTa={
          cho
            ? 'Thử lại thao tác đã gửi để xác nhận kết quả trước khi rời màn hình.'
            : 'Dữ liệu bạn vừa nhập chưa được lưu.'
        }
        onDong={() => datRoi(null)}
        nhanGui="Bỏ thay đổi"
        onGui={cho ? null : () => datChoRoi(true)}
      />
    </ManHinh>
  )
}
