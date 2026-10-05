import { useEffect, useRef, useState } from 'react'
import {
  RefreshControl,
  ScrollView,
  View,
  TextInput,
  Pressable,
  Platform,
} from 'react-native'
import { randomUUID } from 'expo-crypto'
import {
  Chu,
  ManHinh,
  The,
  Nut,
  NutIcon,
  TruongNhap,
  Nhan,
  BieuTuong,
} from '../../components/GiaoDien'
import { TrangThaiTai, PhanTrang, HopXacNhan } from '../../components/HuanLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useThaoTac } from '../../hooks/useThaoTac'
import { useBanNhap } from '../../hooks/useBanNhap'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import SoBuocFigma from '../../components/SoBuocFigma'
import CongTacFigma from '../../components/CongTacFigma'
import { font, useGiaoDien } from '../../theme'
import { hoanThienService as api } from '../../services/hoanThienService'
import { soanYeuCauAi, maDaHoanTat, tien } from '../../utils/hoanThien'
import { HanMucAi } from './TroLy'

function NguonAi({ nguon: n, navigation }) {
  if (!n) return null
  const g = n.giao_an_da_tao
  return (
    <>
      {g && (
        <The>
          <Nhan icon="file-text">Giáo án đã lưu</Nhan>
          <Chu dam="dam">{g.ten_ke_hoach}</Chu>
          <Chu>
            {g.so_tuan} tuần · {g.buoi_moi_tuan} buổi/tuần · {g.bai_moi_buoi}{' '}
            bài/buổi
          </Chu>
          <Chu>
            Trạng thái:{' '}
            {{
              NHAP: 'Bản nháp',
              DANG_AP_DUNG: 'Đang áp dụng',
              LUU_TRU: 'Lưu trữ',
              DA_HUY: 'Đã hủy',
            }[g.trang_thai] || g.trang_thai}
            {g.da_an ? ' · Đã ẩn' : ''}
          </Chu>
          <Nut
            loai="phu"
            onPress={() => navigation.navigate('ChiTietGiaoAn', { id: g.id })}
          >
            Xem & chỉnh sửa giáo án
          </Nut>
          <Chu size={12}>
            Bạn tự chọn áp dụng sau khi xem. AI không thay giáo án đang dùng
            hoặc đặt lịch.
          </Chu>
        </The>
      )}
      {n.goi_tap?.map((g) => (
        <Nut
          key={`g-${g.id}`}
          loai="phu"
          onPress={() => navigation.navigate('GoiTap', { id: g.id })}
        >
          {g.ten_goi} · {tien(g.gia)}
        </Nut>
      ))}
      {n.bai_tap?.map((b) => (
        <Nut
          key={`b-${b.id}`}
          loai="phu"
          onPress={() => navigation.navigate('ChiTietBaiTap', { id: b.id })}
        >
          {b.ten_bai_tap} · {b.ten_nhom_co}
        </Nut>
      ))}
      {n.giao_an_mau?.map((m) => (
        <Chu key={`m-${m.id}`}>
          {m.ten_giao_an} · {m.so_ngay_tap} ngày · Mẫu tham khảo
        </Chu>
      ))}
      {n.tai_lieu?.map((d) => (
        <The key={`d-${d.id}`}>
          <Chu dam="dam">
            {d.tieu_de} · v{d.phien_ban}
          </Chu>
          <Chu selectable>{d.noi_dung}</Chu>
        </The>
      ))}
      {n.chinh_sach && (
        <Chu size={12}>
          Tham chiếu: {n.chinh_sach.tieu_de} · v{n.chinh_sach.phien_ban}
        </Chu>
      )}
    </>
  )
}

export default function HoiThoaiAi({ navigation, route }) {
  const { id } = route.params
  const { goiDichVu } = useXemTruoc()
  const { mau } = useGiaoDien()
  const [page, datPage] = useState(1)
  const [cho, datCho] = useState(null)
  const [form, datForm] = useState(!!route.params?.soanGiaoAn)
  const [so, datSo] = useState({ buoi: '3', tuan: '4', bai: '4' })
  const [loiForm, datLoiForm] = useState('')
  const [bo, datBo] = useState(false)
  const [congCu, datCongCu] = useState(false)
  const [riengTu, datRiengTu] = useState(false)
  const { ban, datBan, nhan, hopRoi } = useBanNhap(navigation)
  useEffect(() => nhan({ noiDung: '', caNhan: !!route.params?.caNhan }), [id])
  const { gui, dangGui, loiGui, datLoiGui } = useThaoTac()
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (s) => goiDichVu((t) => api.taiTinAi(t, id, page, s)),
    `${id}-${page}`,
  )
  const vungTin = useRef(null)
  const h = duLieu?.meta.han_muc
  const tin = duLieu?.data || []
  useEffect(() => {
    if (!duLieu || page !== 1) return
    const timer = setTimeout(
      () => vungTin.current?.scrollToEnd({ animated: false }),
      150,
    )
    return () => clearTimeout(timer)
  }, [duLieu, page])
  useEffect(() => {
    if (cho && maDaHoanTat(tin, cho.client_request_id)) {
      datCho(null)
      nhan({ noiDung: '', caNhan: ban?.caNhan || false })
      datLoiGui(null)
    }
  }, [duLieu])
  function guiCau(d) {
    datCho(d)
    gui(
      () => goiDichVu((t) => api.guiAi(t, id, d)),
      async () => {
        datCho(null)
        nhan({ noiDung: '', caNhan: ban?.caNhan || false })
        if (page === 1) await taiLai()
        else datPage(1)
      },
    )
  }
  function guiMoi() {
    const noiDung = ban?.noiDung.trim()
    if (
      !noiDung ||
      noiDung.length > 2000 ||
      cho ||
      dangGui ||
      !h?.co_quyen ||
      !h.san_sang ||
      h.con_lai < 1
    )
      return
    guiCau({
      client_request_id: randomUUID(),
      noi_dung: noiDung,
      dung_du_lieu_ca_nhan: ban.caNhan,
    })
  }
  async function thuLai() {
    // Đọc trạng thái trước: phản hồi trước có thể đã bị mất trên đường về máy.
    gui(
      async () => {
        const r = await goiDichVu((t) => api.taiTinAi(t, id, 1))
        if (!r) return null
        if (maDaHoanTat(r.data, cho.client_request_id)) return r
        return goiDichVu((t) => api.guiAi(t, id, cho))
      },
      async () => {
        datCho(null)
        nhan({ noiDung: '', caNhan: ban?.caNhan || false })
        if (page === 1) await taiLai()
        else datPage(1)
      },
    )
  }
  return (
    <ManHinh
      bas
      scroll={false}
      contentStyle={{ padding: 0, paddingBottom: 0, gap: 0 }}
      tieuDe="Trợ lý AI"
      onBack={() => navigation.goBack()}
      tacVu={
        <View style={{ flexDirection: 'row', gap: 8, alignItems: 'center' }}>
          <HanMucAi hanMuc={h} navigation={navigation} compact />
          <NutIcon
            icon="ShieldCheck"
            size={18}
            nhan="Tùy chọn và dữ liệu AI"
            style={{
              width: 32,
              height: 40,
              borderWidth: 0,
              backgroundColor: 'transparent',
            }}
            onPress={() => datCongCu(true)}
          />
        </View>
      }
    >
      <ScrollView
        ref={vungTin}
        style={{ flex: 1 }}
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={{ gap: 12, padding: 20 }}
        refreshControl={
          <RefreshControl refreshing={dangTai} onRefresh={taiLai} />
        }
      >
        <TrangThaiTai {...{ dangTai, loi, taiLai }} />
        {duLieu && !tin.length && (
          <View style={{ paddingTop: 24, alignItems: 'center' }}>
            <View
              style={{
                width: 64,
                height: 64,
                borderRadius: 32,
                backgroundColor: mau.chinhNhat,
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: 16,
              }}
            >
              <BieuTuong ten="Sparkles" size={28} />
            </View>
            <Chu size={20} dam="ratDam" style={{ letterSpacing: -0.5 }}>
              Bạn muốn tập gì hôm nay?
            </Chu>
            <Chu
              size={13.5}
              color={mau.chuPhu}
              style={{ marginTop: 4, marginBottom: 20, textAlign: 'center' }}
            >
              Mỗi câu hỏi dùng 1 lượt trong ngày.
            </Chu>
            <View style={{ gap: 8, width: '100%' }}>
              {[
                'Bài tập nào tốt cho ngực?',
                'Lịch giảm mỡ cho người mới',
                'Cách tập squat đúng kỹ thuật',
              ].map((cau) => (
                <Pressable
                  key={cau}
                  accessibilityRole="button"
                  disabled={dangGui || !!cho}
                  onPress={() => datBan({ ...ban, noiDung: cau })}
                  style={{
                    padding: 14,
                    borderRadius: 16,
                    borderWidth: 1,
                    borderColor: mau.vien,
                    backgroundColor: mau.the,
                  }}
                >
                  <Chu size={14} dam="vua">
                    {cau}
                  </Chu>
                </Pressable>
              ))}
            </View>
          </View>
        )}
        <PhanTrang
          meta={duLieu && { ...duLieu.meta, current_page: duLieu.meta.page }}
          dangTai={dangTai || dangGui}
          onChange={datPage}
        />
        {tin.map((t) => (
          <The
            key={t.id}
            style={{
              backgroundColor: t.vai_tro === 'USER' ? mau.chinh : mau.the,
              alignSelf: t.vai_tro === 'USER' ? 'flex-end' : 'flex-start',
              maxWidth: '86%',
              borderWidth: t.vai_tro === 'USER' ? 0 : 1,
              borderBottomRightRadius: t.vai_tro === 'USER' ? 4 : 16,
              borderBottomLeftRadius: t.vai_tro === 'USER' ? 16 : 4,
              paddingHorizontal: 16,
              paddingVertical: 12,
              gap: 8,
            }}
          >
            <Chu
              size={14.5}
              style={{ lineHeight: 23.5625 }}
              color={t.vai_tro === 'USER' ? mau.trenChinh : mau.chu}
              selectable
            >
              {t.noi_dung}
            </Chu>
            {t.vai_tro === 'USER' && t.trang_thai === 'DANG_XU_LY' && (
              <Chu size={12}>Đang xử lý. Bấm Cập nhật để kiểm tra kết quả.</Chu>
            )}
            {t.vai_tro === 'USER' && t.trang_thai === 'LOI' && (
              <Chu size={12}>Chưa có câu trả lời hợp lệ · Không mất lượt</Chu>
            )}
            {t.co_the_thu_lai && (
              <Nut
                loai="phu"
                disabled={dangGui || !!cho}
                onPress={() => {
                  const d = {
                    client_request_id: t.client_request_id,
                    noi_dung: t.noi_dung,
                    dung_du_lieu_ca_nhan: t.dung_du_lieu_ca_nhan,
                  }
                  datBan({
                    noiDung: t.noi_dung,
                    caNhan: t.dung_du_lieu_ca_nhan,
                  })
                  guiCau(d)
                }}
              >
                Thử lại câu hỏi này
              </Nut>
            )}
            <NguonAi nguon={t.nguon_da_kiem_tra} navigation={navigation} />
          </The>
        ))}
        {dangGui && (
          <Chu accessibilityLiveRegion="polite">
            Tr0ond AI đang tìm câu trả lời…
          </Chu>
        )}
      </ScrollView>
      <View
        style={{
          gap: 8,
          paddingHorizontal: 20,
          paddingVertical: 12,
          backgroundColor: mau.the,
          borderTopWidth: 1,
          borderColor: mau.vien,
        }}
      >
        <HopXacNhan
          visible={congCu}
          tieuDe="Tùy chọn trợ lý AI"
          onDong={() => datCongCu(false)}
        >
          <View style={{ flexDirection: 'row', gap: 12, alignItems: 'center' }}>
            <Chu size={13} style={{ flex: 1 }}>
              Dùng dữ liệu tập của tôi
            </Chu>
            <CongTacFigma
              nhan="Cho phép dùng dữ liệu tập cá nhân"
              value={ban?.caNhan || false}
              disabled={dangGui || !!cho}
              onValueChange={(v) => datBan({ ...ban, caNhan: v })}
            />
            <NutIcon
              icon="shield"
              nhan="Dữ liệu gửi Gemini và giới hạn AI"
              onPress={() => {
                datCongCu(false)
                datRiengTu(true)
              }}
            />
            <NutIcon
              icon="file-plus"
              nhan="Soạn yêu cầu tạo giáo án"
              disabled={dangGui || !!cho}
              onPress={() => {
                datCongCu(false)
                datForm(true)
              }}
            />
          </View>
          <Chu size={12} color={mau.chuPhu}>
            Câu hỏi gửi Google Gemini · AI có thể sai
          </Chu>
          <Nut loai="soft" icon="RefreshCw" disabled={dangGui} onPress={taiLai}>
            Cập nhật trò chuyện
          </Nut>
        </HopXacNhan>
        {cho ? (
          <The>
            <Chu size={12}>
              Câu hỏi đang chờ kết quả hoặc gửi lại. Nội dung và lựa chọn dữ
              liệu cá nhân được giữ nguyên.
            </Chu>
            <Nut disabled={dangGui} onPress={thuLai}>
              Kiểm tra / thử lại cùng câu hỏi
            </Nut>
            <Nut loai="phu" disabled={dangGui} onPress={() => datBo(true)}>
              Soạn câu hỏi mới
            </Nut>
          </The>
        ) : (
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
            <TextInput
              accessibilityLabel="Câu hỏi cho Tr0ond AI"
              value={ban?.noiDung || ''}
              onChangeText={(v) => datBan({ ...ban, noiDung: v })}
              multiline
              maxLength={2000}
              editable={!dangGui}
              placeholder="Hỏi về bài tập, kỹ thuật…"
              placeholderTextColor={mau.chuPhu}
              style={{
                flex: 1,
                minWidth: 0,
                minHeight: 48,
                maxHeight: 96,
                borderWidth: 1,
                borderColor: mau.vien,
                borderRadius: 999,
                backgroundColor: mau.truongNhap,
                paddingHorizontal: 16,
                paddingVertical: 12,
                fontFamily: font.thuong,
                fontSize: 15,
                color: mau.chu,
                includeFontPadding: false,
                ...(Platform.OS === 'web' ? { outlineStyle: 'none' } : {}),
              }}
            />
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Gửi câu hỏi"
              disabled={
                dangGui ||
                dangTai ||
                !ban?.noiDung.trim() ||
                !h?.co_quyen ||
                !h?.san_sang ||
                h?.con_lai < 1
              }
              onPress={guiMoi}
              style={{
                width: 48,
                height: 48,
                borderRadius: 24,
                backgroundColor: mau.chinh,
                alignItems: 'center',
                justifyContent: 'center',
                opacity:
                  dangGui ||
                  dangTai ||
                  !ban?.noiDung.trim() ||
                  !h?.co_quyen ||
                  !h?.san_sang ||
                  h?.con_lai < 1
                    ? 0.4
                    : 1,
              }}
            >
              <BieuTuong ten="Send" size={18} color={mau.trenChinh} />
            </Pressable>
          </View>
        )}
        {!!loiGui && (
          <Chu color={mau.loi} accessibilityLiveRegion="polite">
            {loiGui.message}
          </Chu>
        )}
      </View>
      <HopXacNhan
        visible={riengTu}
        tieuDe="Dữ liệu gửi Gemini"
        onDong={() => datRiengTu(false)}
      >
        <Chu>
          Câu hỏi và lịch sử chatbot gửi Google Gemini. Khi bật dữ liệu tập, gửi
          thêm mục tiêu, kinh nghiệm, giáo án đang dùng, số buổi hoàn thành và
          chiều cao, cân nặng, BMI cùng các lần đo gần đây.
        </Chu>
        <Chu>
          Không gửi ghi chú số đo hoặc chat riêng với PT. AI hỗ trợ tư vấn và
          tạo giáo án nháp theo yêu cầu; bạn tự xem và chọn áp dụng.
        </Chu>
      </HopXacNhan>
      <HopXacNhan
        visible={form}
        tieuDe="Tạo giáo án cùng AI"
        moTa="Chỉ soạn câu hỏi. Bạn kiểm tra nội dung rồi bấm Gửi; hệ thống lưu giáo án nháp khi có kết quả hợp lệ."
        loi={loiForm}
        onDong={() => datForm(false)}
        onGui={() => {
          try {
            datBan({ ...ban, noiDung: soanYeuCauAi(so.buoi, so.tuan, so.bai) })
            datLoiForm('')
            datForm(false)
          } catch (e) {
            datLoiForm(e.message)
          }
        }}
        nhanGui="Soạn yêu cầu"
      >
        {[
          ['buoi', 'Số buổi mỗi tuần', 7],
          ['tuan', 'Số tuần', 30],
          ['bai', 'Bài mỗi buổi', 8],
        ].map(([k, n, max]) => (
          <View
            key={k}
            style={{
              flexDirection: 'row',
              alignItems: 'center',
              justifyContent: 'space-between',
              gap: 12,
            }}
          >
            <Chu size={14.5} dam="damVua" style={{ flex: 1 }}>
              {n}
            </Chu>
            <SoBuocFigma
              nhan={n}
              value={so[k]}
              min={1}
              max={max}
              onChange={(v) => datSo({ ...so, [k]: v })}
            />
          </View>
        ))}
      </HopXacNhan>
      <HopXacNhan
        visible={bo}
        tieuDe="Soạn câu hỏi mới?"
        moTa="Yêu cầu trước có thể vẫn hoàn tất trên máy chủ. Bạn có thể kiểm tra lại trong lịch sử; không tự gửi thêm câu hỏi."
        onDong={() => datBo(false)}
        onGui={() => {
          datCho(null)
          nhan({ noiDung: '', caNhan: ban?.caNhan || false })
          datLoiGui(null)
          datBo(false)
          taiLai()
        }}
        nhanGui="Soạn câu hỏi mới"
      />
      {hopRoi}
    </ManHinh>
  )
}
