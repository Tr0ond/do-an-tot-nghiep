import { useRef, useState } from 'react'
import { RefreshControl, View, Pressable } from 'react-native'
import { randomUUID } from 'expo-crypto'
import {
  Chu,
  ManHinh,
  The,
  Nut,
  Nhan,
  BieuTuong,
} from '../../components/GiaoDien'
import { TienDo } from '../../components/FigmaElements'
import CongTacFigma from '../../components/CongTacFigma'
import { TrangThaiTai, PhanTrang, HopXacNhan } from '../../components/HuanLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useThaoTac } from '../../hooks/useThaoTac'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { useGiaoDien } from '../../theme'
import { hoanThienService as api } from '../../services/hoanThienService'
import { thoiDiem } from '../../utils/lich'

export function HanMucAi({ hanMuc: h, navigation, compact = false }) {
  const { mau } = useGiaoDien()
  if (!h) return null
  if (compact) return <Nhan icon="Sparkles">Còn {h.con_lai} lượt</Nhan>
  return (
    <View style={{ borderRadius: 24, backgroundColor: mau.chinh, padding: 20 }}>
      <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
        <View style={{ flexDirection: 'row', gap: 8, alignItems: 'center' }}>
          <BieuTuong ten="Sparkles" size={16} color={mau.trenChinh} />
          <Chu size={13} dam="damVua" color={mau.trenChinh}>
            Lượt hỏi hôm nay
          </Chu>
        </View>
        <Chu size={12} color={mau.trenChinh} style={{ opacity: 0.85 }}>
          Còn {h.con_lai}/{h.toi_da}
        </Chu>
      </View>
      <Chu
        size={36}
        dam="ratDam"
        color={mau.trenChinh}
        style={{ marginTop: 8, lineHeight: 36 }}
      >
        {h.con_lai}
        <Chu size={15} dam="vua" color={mau.trenChinh}>
          {' '}
          lượt
        </Chu>
      </Chu>
      <View style={{ marginTop: 12 }}>
        <TienDo value={h.con_lai} max={h.toi_da} lime />
      </View>
      <View style={{ marginTop: 12, flexDirection: 'row', gap: 6 }}>
        <BieuTuong ten="Clock" size={13} color={mau.trenChinh} />
        <Chu size={12} color={mau.trenChinh} style={{ opacity: 0.85, flex: 1 }}>
          Làm mới lúc 00:00 giờ Việt Nam. Lượt không bị trừ khi AI gặp lỗi.
        </Chu>
      </View>
      {!h.san_sang && (
        <Chu size={13} color={mau.trenChinh} style={{ marginTop: 12 }}>
          AI đang chờ cấu hình. Bạn vẫn đọc được lịch sử.
        </Chu>
      )}
      {!h.co_quyen && (
        <Nut
          sm
          loai="lime"
          style={{ marginTop: 12 }}
          onPress={() => navigation.navigate('GoiTap')}
        >
          Xem gói tập
        </Nut>
      )}
    </View>
  )
}

export default function TroLy({ navigation }) {
  const [caNhan, datCaNhan] = useState(false)
  const [xinPhep, datXinPhep] = useState(false)
  const [page, datPage] = useState(1)
  const ma = useRef(null)
  const { goiDichVu } = useXemTruoc()
  const { mau } = useGiaoDien()
  const { gui, dangGui, loiGui } = useThaoTac()
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (s) => goiDichVu((t) => api.taiHoiAi(t, page, s)),
    page,
  )
  function tao(soanGiaoAn = false) {
    ma.current ||= randomUUID()
    gui(
      () => goiDichVu((t) => api.taoHoiAi(t, ma.current)),
      (r) => {
        ma.current = null
        navigation.navigate('HoiThoaiAi', { id: r.data.id, soanGiaoAn, caNhan })
      },
    )
  }
  return (
    <ManHinh
      bas
      tieuDe="Trợ lý AI"
      footer={
        <Nut icon="Plus" disabled={dangGui} onPress={() => tao(false)}>
          {dangGui
            ? 'Đang tạo…'
            : loiGui
              ? 'Thử lại cuộc trò chuyện mới'
              : 'Cuộc trò chuyện mới'}
        </Nut>
      }
      onBack={() => navigation.goBack()}
      refreshControl={
        <RefreshControl refreshing={dangTai} onRefresh={taiLai} />
      }
    >
      <HanMucAi hanMuc={duLieu?.meta.han_muc} navigation={navigation} />
      <The style={{ flexDirection: 'row', alignItems: 'flex-start', gap: 12 }}>
        <BieuTuong ten="ShieldCheck" size={20} style={{ marginTop: 2 }} />
        <View style={{ flex: 1 }}>
          <Chu size={14.5} dam="dam">
            Cá nhân hóa câu trả lời
          </Chu>
          <Chu
            size={12.5}
            color={mau.chuPhu}
            style={{ marginTop: 2, lineHeight: 20.3125 }}
          >
            Cho phép AI dùng mục tiêu, chỉ số cơ thể và giáo án hiện tại. Mặc
            định tắt.
          </Chu>
        </View>
        <CongTacFigma
          nhan="Cá nhân hóa"
          value={caNhan}
          onValueChange={(v) => (v ? datXinPhep(true) : datCaNhan(false))}
        />
      </The>
      <Pressable
        accessibilityRole="button"
        onPress={() => tao(true)}
        disabled={dangGui}
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          gap: 12,
          backgroundColor: mau.chinhNhat,
          padding: 16,
          borderRadius: 16,
        }}
      >
        <BieuTuong ten="ClipboardList" size={24} />
        <View style={{ flex: 1 }}>
          <Chu size={14.5} dam="dam">
            Tạo giáo án bằng AI
          </Chu>
          <Chu size={12.5} color={mau.chuPhu}>
            AI chỉ soạn bản nháp, bạn duyệt trước khi lưu.
          </Chu>
        </View>
        <BieuTuong ten="ChevronRight" size={18} />
      </Pressable>
      <Chu
        size={12}
        dam="dam"
        color={mau.chuPhu}
        style={{ letterSpacing: 0.6, paddingTop: 4 }}
      >
        CUỘC TRÒ CHUYỆN
      </Chu>
      {!!loiGui && <Chu color={mau.loi}>{loiGui.message}</Chu>}
      <TrangThaiTai
        {...{ dangTai, loi, taiLai }}
        rong={duLieu && !duLieu.data.length}
        tieuDeRong="Chưa có cuộc trò chuyện"
        moTaRong="Hỏi về bài tập, quyền lợi gói hoặc yêu cầu tạo giáo án nháp."
      />
      {duLieu?.data.map((h) => (
        <Pressable
          key={h.id}
          accessibilityRole="button"
          onPress={() =>
            navigation.navigate('HoiThoaiAi', { id: h.id, caNhan })
          }
        >
          <The style={{ flexDirection: 'row', gap: 12, alignItems: 'center' }}>
            <View
              style={{
                width: 44,
                height: 44,
                borderRadius: 22,
                backgroundColor: mau.chinhNhat,
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <BieuTuong ten="MessageCircle" size={19} />
            </View>
            <View style={{ flex: 1, minWidth: 0 }}>
              <Chu size={14.5} dam="dam" numberOfLines={1}>
                {h.tieu_de}
              </Chu>
              <Chu size={12.5} color={mau.chuPhu}>
                {thoiDiem(h.updated_at)}
              </Chu>
            </View>
          </The>
        </Pressable>
      ))}
      <PhanTrang
        meta={duLieu && { ...duLieu.meta, current_page: duLieu.meta.page }}
        dangTai={dangTai}
        onChange={datPage}
      />
      <Nut loai="phu" onPress={() => navigation.navigate('Faq')}>
        Câu hỏi & tài liệu miễn phí
      </Nut>
      <HopXacNhan
        visible={xinPhep}
        tieuDe="Bật cá nhân hóa?"
        moTa="Câu hỏi và lịch sử gửi Google Gemini. Khi bật, gửi thêm mục tiêu, kinh nghiệm, giáo án đang dùng, chiều cao, cân nặng, BMI và tiến độ tập. Không gửi ghi chú số đo hoặc chat riêng với PT. Bạn có thể tắt bất cứ lúc nào."
        nhanGui="Đồng ý"
        onDong={() => datXinPhep(false)}
        onGui={() => {
          datCaNhan(true)
          datXinPhep(false)
        }}
      />
    </ManHinh>
  )
}
