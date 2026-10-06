import { useState } from 'react'
import { Pressable, View } from 'react-native'
import { TimKiem, NhomChip, Chip } from '../../components/FigmaElements'
import { useGiaoDien } from '../../theme'
import {
  ManHinh,
  Chu,
  Nut,
  The,
  TruongNhap,
  BieuTuong,
  NutIcon,
} from '../../components/GiaoDien'
import { TrangThaiTai, PhanTrang, HopXacNhan } from '../../components/HuanLuyen'
import { AnhBaiTap } from '../../components/TapLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { tapLuyenService as api } from '../../services/tapLuyenService'

export default function Catalog({ navigation, route }) {
  const { goiDichVu } = useXemTruoc()
  const { mau } = useGiaoDien()
  const [tu, datTu] = useState('')
  const [loc, datLoc] = useState({
    tu_khoa: '',
    nhom_co_id: '',
    dung_cu_nguon: '',
    page: 1,
  })
  const [hop, datHop] = useState(false)
  const boLoc = useDuLieu((s) => goiDichVu((t) => api.taiBoLoc(t, s)))
  const tai = useDuLieu(
    (s) => goiDichVu((t) => api.taiBaiTap(t, { ...loc, per_page: 12 }, s)),
    JSON.stringify(loc),
  )
  const chon = route.params?.chonCho
  function suaLoc(k, v) {
    datLoc((d) => ({ ...d, [k]: v, page: 1 }))
  }
  return (
    <ManHinh
      bas
      contentStyle={{ gap: 12, paddingTop: 16 }}
      tieuDe={chon ? 'Chọn bài tập' : 'Thư viện bài tập'}
      onBack={() => navigation.goBack()}
    >
      <View style={{ flexDirection: 'row', gap: 8 }}>
        <View style={{ flex: 1 }}>
          <TimKiem
            value={tu}
            onChangeText={datTu}
            placeholder="Tìm bài tập…"
            onSubmitEditing={() => suaLoc('tu_khoa', tu.trim())}
          />
        </View>
        <NutIcon
          icon="SlidersHorizontal"
          nhan="Bộ lọc"
          onPress={() => datHop(true)}
        />
      </View>
      <NhomChip>
        <Chip chon={!loc.nhom_co_id} onPress={() => suaLoc('nhom_co_id', '')}>
          Tất cả
        </Chip>
        {boLoc.duLieu?.data.nhom_co.map((n) => (
          <Chip
            key={n.id}
            chon={loc.nhom_co_id === n.id}
            onPress={() => suaLoc('nhom_co_id', n.id)}
          >
            {n.ten_nhom_co}
          </Chip>
        ))}
      </NhomChip>
      <Chu size={12.5} color={mau.chuPhu}>
        {tai.duLieu?.meta.total ?? 0} bài tập
      </Chu>
      <TrangThaiTai
        {...tai}
        rong={tai.duLieu?.data.length === 0}
        tieuDeRong="Không có bài phù hợp"
      />
      <The style={{ padding: 0, gap: 0, overflow: 'hidden' }}>
        {tai.duLieu?.data.map((b, i) => (
          <View key={b.id}>
            <Pressable
              accessibilityRole="button"
              onPress={() =>
                chon
                  ? navigation.popTo(chon.manHinh || 'SoanGiaoAn', {
                      ...chon,
                      baiChon: b,
                      lanChon: Date.now(),
                    })
                  : navigation.navigate('ChiTietBaiTap', { id: b.id })
              }
              style={{
                flexDirection: 'row',
                gap: 12,
                padding: 12,
                alignItems: 'center',
                borderTopWidth: i ? 1 : 0,
                borderColor: mau.vien,
              }}
            >
              <AnhBaiTap bai={b} size={64} />
              <View style={{ flex: 1, minWidth: 0 }}>
                <Chu size={14.5} dam="dam" numberOfLines={1}>
                  {b.ten_tieng_viet || b.ten_bai_tap}
                </Chu>
                <Chu size={12.5} color={mau.chuPhu}>
                  {b.nhom_co.ten_nhom_co} · {b.dung_cu || 'Không có dụng cụ'}
                </Chu>
              </View>
              <BieuTuong
                ten={chon ? 'Plus' : 'ChevronRight'}
                size={18}
                color={mau.chuPhu}
              />
            </Pressable>
            {chon && (
              <Nut
                sm
                loai="ghost"
                onPress={() =>
                  navigation.navigate('ChiTietBaiTap', { id: b.id })
                }
              >
                Hướng dẫn kỹ thuật
              </Nut>
            )}
          </View>
        ))}
      </The>
      <PhanTrang
        meta={tai.duLieu?.meta}
        dangTai={tai.dangTai}
        onChange={(page) => datLoc((d) => ({ ...d, page }))}
      />
      <HopXacNhan
        visible={hop}
        tieuDe="Bộ lọc bài tập"
        onDong={() => datHop(false)}
      >
        <TrangThaiTai {...boLoc} />
        <Nut
          loai="phu"
          onPress={() => {
            datLoc({ tu_khoa: '', nhom_co_id: '', dung_cu_nguon: '', page: 1 })
            datTu('')
            datHop(false)
          }}
        >
          Bỏ tất cả bộ lọc
        </Nut>
        <Chu dam="dam">Nhóm cơ</Chu>
        {boLoc.duLieu?.data.nhom_co.map((n) => (
          <Nut
            sm
            key={n.id}
            loai={loc.nhom_co_id === n.id ? 'chinh' : 'phu'}
            onPress={() => {
              suaLoc('nhom_co_id', n.id)
              datHop(false)
            }}
          >
            {n.ten_nhom_co}
          </Nut>
        ))}
        <Chu dam="dam">Dụng cụ</Chu>
        {boLoc.duLieu?.data.dung_cu.map((n) => (
          <Nut
            sm
            key={n.dung_cu_nguon}
            loai={loc.dung_cu_nguon === n.dung_cu_nguon ? 'chinh' : 'phu'}
            onPress={() => {
              suaLoc('dung_cu_nguon', n.dung_cu_nguon)
              datHop(false)
            }}
          >
            {n.dung_cu}
          </Nut>
        ))}
      </HopXacNhan>
    </ManHinh>
  )
}
