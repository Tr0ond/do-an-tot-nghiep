import { useState } from 'react'
import { ScrollView, View, RefreshControl } from 'react-native'
import { ManHinh, Chu, Nut, The, NutIcon } from '../../components/GiaoDien'
import {
  TrangThaiTai,
  PhanTrang,
  ChonNgay,
  HopXacNhan,
} from '../../components/HuanLuyen'
import Svg, { Line, Circle, Text as SvgText } from 'react-native-svg'
import { font } from '../../theme'
import { ChonPhan, Trong, Muc } from '../../components/FigmaElements'
import { LuaChon } from '../../components/TapLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { useGiaoDien } from '../../theme'
import { tapLuyenService as api } from '../../services/tapLuyenService'
import { homNay, nhanNgay } from '../../utils/lich'

function BieuDo({ cacMoc, truong, tu, den }) {
  const { mau } = useGiaoDien()
  const [rong, datRong] = useState(280)
  const moc = cacMoc.filter((m) => m[truong] != null)
  if (!moc.length)
    return (
      <Chu size={13} color={mau.chuPhu}>
        Chưa có dữ liệu cho biểu đồ trong khoảng này.
      </Chu>
    )
  const min = Math.min(...moc.map((m) => Number(m[truong]))) - 1
  const max = Math.max(...moc.map((m) => Number(m[truong]))) + 1
  const dau = Date.parse(tu),
    doDai = Math.max(86400000, Date.parse(den) - dau)
  return (
    <View onLayout={(e) => datRong(e.nativeEvent.layout.width)}>
      <Svg
        width={rong}
        height={208}
        accessible
        accessibilityRole="image"
        accessibilityLabel={`${moc.length} lần ghi từ ${nhanNgay(tu)} đến ${nhanNgay(den)}. Giá trị từng ngày ở lịch sử bên dưới.`}
      >
        {Array.from({ length: 5 }, (_, i) => (
          <Line
            key={`l${i}`}
            x1={40}
            y1={8 + i * 42}
            x2={rong - 8}
            y2={8 + i * 42}
            stroke={mau.vien}
          />
        ))}
        {Array.from({ length: 5 }, (_, i) => (
          <SvgText
            key={`t${i}`}
            x={34}
            y={12 + i * 42}
            fill={mau.chuPhu}
            fontSize={11}
            fontFamily={font.thuong}
            textAnchor="end"
          >
            {(max - ((max - min) * i) / 4).toFixed(1)}
          </SvgText>
        ))}
        {moc.map((m) => (
          <Circle
            key={m.id}
            cx={40 + ((Date.parse(m.ngay_ghi) - dau) / doDai) * (rong - 48)}
            cy={8 + ((max - Number(m[truong])) / (max - min)) * 168}
            r={4}
            fill={mau.chinh}
          />
        ))}
        <SvgText
          x={40}
          y={200}
          fill={mau.chuPhu}
          fontSize={11}
          fontFamily={font.thuong}
        >
          {nhanNgay(tu)}
        </SvgText>
        <SvgText
          x={rong - 8}
          y={200}
          fill={mau.chuPhu}
          fontSize={11}
          fontFamily={font.thuong}
          textAnchor="end"
        >
          {nhanNgay(den)}
        </SvgText>
      </Svg>
      <Chu size={11} color={mau.chuPhu}>
        Mỗi chấm là một lần ghi; trục ngang theo ngày đo.
      </Chu>
    </View>
  )
}
export default function ChiSo({ navigation, route }) {
  const { vaiTro, goiDichVu } = useXemTruoc()
  const khachId = route.params?.khachId
  const [boLoc, datBoLoc] = useState(false)
  const [soNgay, datSoNgay] = useState(30)
  const [den, datDen] = useState(homNay())
  const [page, datPage] = useState(1)
  const [truong, datTruong] = useState('can_nang_kg')
  const tai = useDuLieu(
    (s) =>
      goiDichVu((t) =>
        api.taiChiSo(
          t,
          vaiTro,
          khachId,
          { so_ngay: soNgay, den_ngay: den, page },
          s,
        ),
      ),
    `${soNgay}|${den}|${page}|${khachId}`,
    true,
  )
  const d = tai.duLieu?.data
  const laKhach = vaiTro === 'KHACH_HANG'
  const { mau } = useGiaoDien()
  const moGhi = () =>
    navigation.navigate('GhiChiSo', { chieuCao: d?.moi_nhat?.chieu_cao_cm })
  return (
    <ManHinh
      bas
      tieuDe="Chỉ số cơ thể"
      tacVu={
        <NutIcon
          icon="SlidersHorizontal"
          size={18}
          nhan="Khoảng theo dõi & thông tin BMI"
          onPress={() => datBoLoc(true)}
        />
      }
      onBack={() => navigation.goBack()}
      footer={
        laKhach &&
        d && (
          <Nut icon="Plus" onPress={moGhi}>
            Ghi chỉ số hôm nay
          </Nut>
        )
      }
      refreshControl={
        <RefreshControl refreshing={tai.dangTai} onRefresh={tai.taiLai} />
      }
    >
      <TrangThaiTai {...tai} />
      {d && !d.moi_nhat && (
        <View style={{ paddingTop: 20 }}>
          <Trong
            icon="Scale"
            tieuDe="Chưa có chỉ số"
            moTa={
              laKhach
                ? 'Ghi cân nặng và chiều cao để theo dõi BMI theo thời gian.'
                : 'Học viên chưa ghi chỉ số nào.'
            }
          >
            {laKhach && (
              <Nut sm onPress={moGhi}>
                Ghi chỉ số đầu tiên
              </Nut>
            )}
          </Trong>
        </View>
      )}
      {d?.moi_nhat && (
        <>
          <View style={{ flexDirection: 'row', gap: 8 }}>
            {[
              ['Cân nặng', d.moi_nhat.can_nang_kg, 'kg'],
              ['Chiều cao', d.moi_nhat.chieu_cao_cm, 'cm'],
              ['BMI', d.moi_nhat.bmi, 'Tham khảo'],
            ].map(([ten, so, donVi], i) => (
              <View
                key={ten}
                style={{
                  flex: 1,
                  padding: 12,
                  borderRadius: 16,
                  borderWidth: i === 2 ? 0 : 1,
                  borderColor: mau.vien,
                  backgroundColor: i === 2 ? mau.nangLuong : mau.the,
                }}
              >
                <Chu size={11} color={i === 2 ? mau.trenNangLuong : mau.chuPhu}>
                  {ten}
                </Chu>
                <Chu
                  size={24}
                  dam="ratDam"
                  color={i === 2 ? mau.trenNangLuong : mau.chu}
                  style={{ marginTop: 4, lineHeight: 24, letterSpacing: -0.6 }}
                >
                  {so ?? '—'}
                </Chu>
                <Chu
                  size={11}
                  dam="damVua"
                  color={i === 2 ? mau.trenNangLuong : mau.chuPhu}
                  style={{ marginTop: 4 }}
                >
                  {donVi}
                </Chu>
              </View>
            ))}
          </View>
          {d.thay_doi_can_nang_kg != null && (
            <Chu size={13} dam="damVua" color={mau.chinh}>
              {d.thay_doi_can_nang_kg > 0 ? '+' : ''}
              {d.thay_doi_can_nang_kg} kg trong khoảng theo dõi
            </Chu>
          )}
          <The>
            <ChonPhan
              value={truong}
              onChange={datTruong}
              options={[
                ['can_nang_kg', 'Cân nặng'],
                ['bmi', 'BMI'],
              ]}
            />
            <BieuDo
              cacMoc={d.cac_moc}
              truong={truong}
              tu={d.tu_ngay}
              den={d.den_ngay}
            />
          </The>
          <Chu
            size={12}
            dam="dam"
            color={mau.chuPhu}
            style={{
              textTransform: 'uppercase',
              letterSpacing: 1.2,
              marginTop: 8,
            }}
          >
            Lịch sử
          </Chu>
          {!d.lich_su.length && (
            <Trong
              icon="Scale"
              tieuDe="Chưa có số đo trong khoảng này"
              moTa="Chọn khoảng thời gian khác để xem các lần ghi trước."
            />
          )}
          <View
            style={{
              borderRadius: 16,
              borderWidth: 1,
              borderColor: mau.vien,
              backgroundColor: mau.the,
              overflow: 'hidden',
            }}
          >
            {d.lich_su.map((c, i) => (
              <View
                key={c.id}
                style={{
                  paddingHorizontal: 16,
                  paddingVertical: 12,
                  flexDirection: 'row',
                  gap: 12,
                  alignItems: 'center',
                  borderTopWidth: i ? 1 : 0,
                  borderColor: mau.vien,
                }}
              >
                <View style={{ flex: 1 }}>
                  <Chu size={14.5} dam="dam">
                    {c.can_nang_kg ?? '—'} kg · {c.chieu_cao_cm ?? '—'} cm
                  </Chu>
                  <Chu size={12.5} color={mau.chuPhu}>
                    {nhanNgay(c.ngay_ghi)} · BMI {c.bmi ?? '—'}
                  </Chu>
                  {c.ghi_chu && (
                    <Chu size={12.5} color={mau.chuPhu}>
                      {c.ghi_chu}
                    </Chu>
                  )}
                </View>
                {laKhach && (
                  <NutIcon
                    icon="Pencil"
                    nhan={`Sửa số đo ${nhanNgay(c.ngay_ghi)}`}
                    size={16}
                    style={{
                      width: 32,
                      height: 40,
                      borderWidth: 0,
                      backgroundColor: 'transparent',
                    }}
                    onPress={() =>
                      navigation.navigate('GhiChiSo', { banGhi: c })
                    }
                  />
                )}
              </View>
            ))}
          </View>
          <PhanTrang
            meta={tai.duLieu?.meta}
            dangTai={tai.dangTai}
            onChange={datPage}
          />
        </>
      )}
      <HopXacNhan
        visible={boLoc}
        tieuDe="Theo dõi chỉ số"
        onDong={() => datBoLoc(false)}
      >
        <LuaChon
          nhan="Khoảng theo dõi"
          cacMuc={[
            [7, '7 ngày'],
            [30, '30 ngày'],
            [90, '90 ngày'],
          ]}
          giaTri={soNgay}
          onChon={(v) => {
            datSoNgay(v)
            datPage(1)
          }}
        />
        <ChonNgay
          value={den}
          onChange={(v) => {
            datDen(v)
            datPage(1)
          }}
        />
        <Chu size={12} color={mau.chuPhu}>
          BMI chỉ để tham khảo, không phân biệt cơ và mỡ, không dùng để chẩn
          đoán. Số đo do khách tự ghi.
        </Chu>
      </HopXacNhan>
    </ManHinh>
  )
}
