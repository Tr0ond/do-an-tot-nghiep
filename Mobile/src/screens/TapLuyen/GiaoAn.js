import { useState } from 'react'
import { RefreshControl, View, Pressable } from 'react-native'
import {
  ManHinh,
  Chu,
  The,
  Nut,
  Nhan,
  NutIcon,
  BieuTuong,
} from '../../components/GiaoDien'
import { TrangThaiTai, PhanTrang, HopXacNhan } from '../../components/HuanLuyen'
import { LuaChon } from '../../components/TapLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { tapLuyenService as api } from '../../services/tapLuyenService'
import { useGiaoDien } from '../../theme'
import {
  TieuDeTab,
  NhomChip,
  Chip,
  Trong,
} from '../../components/FigmaElements'
import { nhanTrangThai } from '../../utils/tapLuyen'

export default function GiaoAn({ navigation, route }) {
  const { goiDichVu, vaiTro } = useXemTruoc()
  const khachId = route.params?.khachId
  const laKhach = vaiTro === 'KHACH_HANG'
  const { mau } = useGiaoDien()
  const [boLoc, datBoLoc] = useState(false)
  const [muc, datMuc] = useState('active')
  const [nguon, datNguon] = useState('')
  const [an, datAn] = useState(0)
  const [page, datPage] = useState(1)
  const tai = useDuLieu(
    (s) =>
      goiDichVu(async (t) => {
        const dau = await api.taiGiaoAn(
          t,
          vaiTro,
          khachId,
          {
            nguon_tao: nguon,
            ...(laKhach ? { da_an: an } : {}),
            page: laKhach && muc !== 'all' ? 1 : page,
          },
          s,
        )
        if (!laKhach || muc === 'all') return dau
        const ds = [...dau.data]
        for (let trang = 2; trang <= dau.meta.last_page; trang++) {
          const r = await api.taiGiaoAn(
            t,
            vaiTro,
            khachId,
            { nguon_tao: nguon, da_an: an, page: trang },
            s,
          )
          ds.push(...r.data)
        }
        return {
          ...dau,
          data: [...new Map(ds.map((k) => [k.id, k])).values()],
          meta: { ...dau.meta, current_page: 1, last_page: 1 },
        }
      }),

    `${muc}|${nguon}|${an}|${page}|${khachId}`,
  )
  const danhSach = (tai.duLieu?.data || []).filter(
    (k) =>
      !laKhach ||
      (muc === 'active'
        ? k.trang_thai === 'DANG_AP_DUNG'
        : muc === 'draft'
          ? ['NHAP', 'LUU_TRU'].includes(k.trang_thai)
          : muc === 'pt'
            ? k.nguon_tao === 'PT' && k.trang_thai === 'CHO_DUYET'
            : true),
  )
  return (
    <ManHinh
      bas={!!khachId}
      tieuDe={khachId ? 'Giáo án học viên' : undefined}
      contentStyle={{ gap: 0, paddingTop: khachId ? 20 : 0 }}
      onBack={khachId ? () => navigation.goBack() : undefined}
      refreshControl={
        <RefreshControl refreshing={tai.dangTai} onRefresh={tai.taiLai} />
      }
    >
      {!khachId && (
        <TieuDeTab
          phu="Mỗi lúc chỉ áp dụng một giáo án."
          tacVu={
            <NutIcon
              icon="SlidersHorizontal"
              nhan="Bộ lọc giáo án"
              size={18}
              onPress={() => datBoLoc(true)}
            />
          }
        >
          Giáo án
        </TieuDeTab>
      )}
      {laKhach && (
        <View style={{ marginBottom: 16 }}>
          <NhomChip>
            {[
              ['active', 'Đang dùng'],
              ['draft', 'Nháp'],
              ['pt', 'Từ PT'],
              ['ai', 'Gợi ý AI'],
            ].map(([id, ten]) => (
              <Chip key={id} chon={muc === id} onPress={() => datMuc(id)}>
                {ten}
              </Chip>
            ))}
          </NhomChip>
        </View>
      )}
      {tai.duLieu?.meta.hoc_vien && (
        <Chu dam="dam">{tai.duLieu.meta.hoc_vien.ho_ten}</Chu>
      )}
      <TrangThaiTai
        {...tai}
        rong={!!tai.duLieu && muc !== 'ai' && danhSach.length === 0}
        tieuDeRong="Chưa có giáo án"
        moTaRong="Bạn có thể tạo bản nháp từ thư viện bài tập."
      />
      {muc === 'ai' && (
        <Trong
          icon="Sparkles"
          tieuDe="Giáo án do AI soạn"
          moTa="Xem bản nháp và nguồn giáo án trong hội thoại với trợ lý AI."
        >
          <Nut sm onPress={() => navigation.navigate('TroLy')}>
            Mở trợ lý AI
          </Nut>
        </Trong>
      )}
      {muc !== 'ai' &&
        danhSach.map((k) => (
          <Pressable
            key={k.id}
            style={{ marginBottom: 12 }}
            accessibilityRole="button"
            onPress={() =>
              navigation.navigate('ChiTietGiaoAn', { id: k.id, khachId })
            }
          >
            <The
              style={{
                gap: 0,
                backgroundColor:
                  k.trang_thai === 'DANG_AP_DUNG' ? mau.chinhNhat : mau.the,
                borderColor:
                  k.trang_thai === 'DANG_AP_DUNG' ? mau.chinh : mau.vien,
              }}
            >
              <View
                style={{
                  flexDirection: 'row',
                  alignItems: 'flex-start',
                  gap: 12,
                }}
              >
                <View style={{ flex: 1 }}>
                  <Chu size={12} dam="damVua" color={mau.chinh}>
                    {k.muc_tieu || 'Giáo án tập luyện'}
                  </Chu>
                  <Chu
                    size={17}
                    dam="dam"
                    style={{
                      lineHeight: 23.375,
                      letterSpacing: -0.425,
                      marginTop: 2,
                    }}
                  >
                    {k.ten_ke_hoach}
                  </Chu>
                </View>
                <Nhan
                  trangThai={k.trang_thai}
                  loai={k.trang_thai === 'CHO_DUYET' ? 'vang' : 'chinh'}
                >
                  {nhanTrangThai(k.trang_thai_hien_thi)}
                  {k.da_an ? ' · Đã ẩn' : ''}
                </Nhan>
              </View>
              <View style={{ flexDirection: 'row', gap: 16, marginTop: 12 }}>
                <Chu size={13} color={mau.chuPhu}>
                  {k.so_ngay_tap} buổi
                </Chu>
                <Chu size={13} color={mau.chuPhu}>
                  {k.so_bai_tap} bài tập
                </Chu>
                {k.nguon_tao === 'PT' && (
                  <Chu size={13} color={mau.chuPhu}>
                    Từ PT
                  </Chu>
                )}
              </View>
            </The>
          </Pressable>
        ))}
      <View style={{ flexDirection: 'row', gap: 12, marginTop: 12 }}>
        <Nut
          icon="Plus"
          style={{ flex: 1 }}
          onPress={() => navigation.navigate('SoanGiaoAn', { khachId })}
        >
          Tạo giáo án
        </Nut>
        <Nut
          loai="phu"
          icon="Library"
          style={{ flex: 1 }}
          onPress={() => navigation.navigate('Catalog')}
        >
          Thư viện
        </Nut>
      </View>
      {laKhach && (
        <Pressable
          accessibilityRole="button"
          onPress={() => navigation.navigate('TroLy')}
          style={{
            marginTop: 12,
            flexDirection: 'row',
            alignItems: 'center',
            gap: 12,
            padding: 16,
            borderRadius: 16,
            backgroundColor: mau.chinhNhat,
          }}
        >
          <BieuTuong ten="Sparkles" size={24} />
          <Chu size={13.5} style={{ flex: 1 }}>
            <Chu size={13.5} dam="dam">
              Nhờ AI soạn nháp
            </Chu>{' '}
            theo mục tiêu của bạn
          </Chu>
        </Pressable>
      )}
      <PhanTrang
        meta={tai.duLieu?.meta}
        dangTai={tai.dangTai}
        onChange={datPage}
      />
      <HopXacNhan
        visible={boLoc}
        tieuDe="Bộ lọc giáo án"
        onDong={() => datBoLoc(false)}
      >
        {boLoc && (
          <>
            <LuaChon
              nhan="Nguồn giáo án"
              cacMuc={[
                ['', 'Tất cả'],
                ['PT', 'HLV giao'],
                ['KHACH_HANG', 'Tự tạo'],
              ]}
              giaTri={nguon}
              onChon={(v) => {
                datNguon(v)
                datPage(1)
              }}
            />
            {laKhach && (
              <LuaChon
                nhan="Hiển thị"
                cacMuc={[
                  [0, 'Đang hiển thị'],
                  [1, 'Đã ẩn'],
                ]}
                giaTri={an}
                onChon={(v) => {
                  datAn(v)
                  datPage(1)
                }}
              />
            )}
            {laKhach && (
              <Nut
                loai="soft"
                onPress={() => {
                  datMuc('all')
                  datBoLoc(false)
                }}
              >
                Xem tất cả giáo án
              </Nut>
            )}
          </>
        )}
      </HopXacNhan>
    </ManHinh>
  )
}
