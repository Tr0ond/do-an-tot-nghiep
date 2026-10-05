import { useEffect, useRef, useState } from 'react'
import {
  FlatList,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  TextInput,
  View,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useIsFocused } from '@react-navigation/native'
import { randomUUID } from 'expo-crypto'
import * as ImagePicker from 'expo-image-picker'
import { File, Paths } from 'expo-file-system'
import { Image } from 'expo-image'
import {
  Chu,
  Nut,
  NutIcon,
  BieuTuong,
  AnhDaiDien,
} from '../../components/GiaoDien'
import AnhChat from '../../components/AnhChat'
import { useHoiThoai } from '../../hooks/useHoiThoai'
import { useBanNhap } from '../../hooks/useBanNhap'
import { useThaoTac } from '../../hooks/useThaoTac'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { useTraoDoi } from '../../contexts/TraoDoiContext'
import { traoDoiService as api } from '../../services/traoDoiService'
import { taoTinCho, kiemTraTin } from '../../utils/traoDoi'
import { font, useGiaoDien } from '../../theme'
import { thoiDiem, gioVietNam } from '../../utils/lich'

function donAnh(ds) {
  for (const uri of ds) {
    // Chỉ xóa bản sao do picker tạo trong cache app; không đụng ảnh thư viện.
    if (!uri.startsWith(`${Paths.cache.uri}ImagePicker/`)) continue
    try {
      new File(uri).delete()
    } catch {
      /* File có thể đã được hệ điều hành dọn. */
    }
  }
}

export default function HoiThoai({ navigation, route }) {
  const id = route.params.id
  const { goiDichVu, taiKhoan } = useXemTruoc()
  const { ketNoi, hoatDong, capNhat } = useTraoDoi()
  const { mau } = useGiaoDien()
  const focused = useIsFocused()
  const tai = useHoiThoai(id)
  const ghi = useThaoTac()
  const ban = useBanNhap(navigation)
  const [choGui, datChoGui] = useState(false)
  const [dangChon, datDangChon] = useState(false)
  const [thay, datThay] = useState([])
  const [coTinMoi, datCoTinMoi] = useState(false)
  const [hanThu, datHanThu] = useState(0)
  const [giay, datGiay] = useState(0)
  const queue = useRef(taoTinCho(randomUUID))
  const tep = useRef(new Set())
  const song = useRef(true)
  const lanQuyen = useRef(0)
  const list = useRef(null)
  const dangDay = useRef(true)
  const canVeDay = useRef(true)
  const soTin = useRef(0)
  const phienHien = useRef(false)
  phienHien.current = focused && hoatDong
  const doc = useRef(tai.daDoc)
  doc.current = tai.daDoc
  const viewability = useRef({
    itemVisiblePercentThreshold: 50,
    minimumViewTime: 400,
  }).current
  const onThay = useRef(({ viewableItems }) => {
    const ids = viewableItems.filter((v) => v.isViewable).map((v) => v.item.id)
    datThay(ids)
    if (ids.length && phienHien.current) doc.current(Math.max(...ids))
  }).current
  useEffect(() => {
    canVeDay.current = true
    dangDay.current = true
    datThay([])
    datCoTinMoi(false)
  }, [tai.lanHien])
  useEffect(() => {
    song.current = true
    ban.nhan({ noiDung: '', anh: [] })
    return () => {
      song.current = false
      donAnh(tep.current)
      tep.current.clear()
    }
  }, [])
  useEffect(() => {
    if (tai.matQuyen || (tai.hoi && !tai.hoi.co_the_gui)) {
      lanQuyen.current++
      queue.current.xong()
      datChoGui(false)
      donAnh(tep.current)
      tep.current.clear()
      ban.nhan({ noiDung: '', anh: [] })
    }
  }, [tai.matQuyen, tai.hoi?.co_the_gui])
  useEffect(() => {
    if (focused && hoatDong && thay.length) tai.daDoc(Math.max(...thay))
  }, [tai.hoi, focused, hoatDong, thay])
  useEffect(() => {
    if (hanThu <= Date.now()) {
      datGiay(0)
      return
    }
    const tick = () =>
      datGiay(Math.max(0, Math.ceil((hanThu - Date.now()) / 1000)))
    tick()
    const timer = setInterval(tick, 1000)
    return () => clearInterval(timer)
  }, [hanThu])
  useEffect(() => {
    const max = tai.tin.at(-1)?.id || 0
    if (max > soTin.current && soTin.current && !dangDay.current)
      datCoTinMoi(true)
    soTin.current = max
  }, [tai.tin])
  const b = ban.ban || { noiDung: '', anh: [] }
  const khoa =
    ghi.dangGui || choGui || dangChon || !tai.hoi?.co_the_gui || !hoatDong
  async function chonAnh() {
    if (khoa || b.anh.length >= 4) return
    const lan = lanQuyen.current
    datDangChon(true)
    try {
      const k = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        allowsMultipleSelection: true,
        selectionLimit: 4 - b.anh.length,
        quality: 1,
      })
      const moi = (k.assets || []).map((a) => ({
        uri: a.uri,
        ten: a.fileName || a.uri.split('/').at(-1),
        mime: a.mimeType,
        size: a.fileSize || new File(a.uri).size,
        width: a.width,
        height: a.height,
      }))
      for (const a of moi) tep.current.add(a.uri)
      if (!song.current || lan !== lanQuyen.current) {
        donAnh(moi.map((a) => a.uri))
        return
      }
      if (!k.canceled) {
        kiemTraTin(b.noiDung, [...b.anh, ...moi])
        ban.datBan({ ...b, anh: [...b.anh, ...moi] })
      }
    } catch (e) {
      if (song.current) ghi.datLoiGui(e)
    } finally {
      if (song.current) datDangChon(false)
    }
  }
  function gui() {
    if (ghi.dangGui || giay || !tai.hoi?.co_the_gui || !hoatDong) return
    ghi.gui(
      async () => {
        const lan = lanQuyen.current
        const d = queue.current.lay(b.noiDung, b.anh)
        datChoGui(true)
        try {
          const k = await goiDichVu((t) => api.guiTin(t, id, d))
          return lan === lanQuyen.current ? k : null
        } catch (e) {
          if (
            e.status &&
            ![408, 409, 429].includes(e.status) &&
            e.status < 500
          ) {
            queue.current.xong()
            datChoGui(false)
          }
          if (e.status === 429)
            datHanThu(Date.now() + Math.max(e.retryAfter || 0, 1) * 1000)
          if ([403, 404, 409].includes(e.status)) tai.taiLai()
          throw e
        }
      },
      (d) => {
        queue.current.xong()
        datChoGui(false)
        donAnh(tep.current)
        tep.current.clear()
        ban.nhan({ noiDung: '', anh: [] })
        tai.nhanTin(d.data)
        capNhat()
        list.current?.scrollToOffset({ offset: 0, animated: true })
        datCoTinMoi(false)
      },
    )
  }
  return (
    <SafeAreaView
      edges={['top', 'bottom', 'left', 'right']}
      style={{ flex: 1, backgroundColor: mau.nen }}
    >
      <View
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          gap: 8,
          paddingHorizontal: 8,
          height: 56,
          backgroundColor: mau.the,
          borderBottomWidth: 1,
          borderColor: mau.vien,
        }}
      >
        <NutIcon
          icon="ChevronLeft"
          size={22}
          color={mau.chu}
          nhan="Quay lại"
          onPress={() => navigation.goBack()}
          style={{ width: 40, height: 40, borderWidth: 0 }}
        />
        <View style={{ flex: 1 }}>
          <Chu size={17} dam="dam" style={{ letterSpacing: -0.425 }}>
            {tai.hoi?.doi_phuong.ho_ten || 'Hội thoại'}
          </Chu>
        </View>
        <NutIcon
          icon="refresh-cw"
          nhan={`Cập nhật hội thoại · ${ketNoi === 'da-noi' ? 'Đang kết nối trực tiếp' : 'Đồng bộ khi có kết nối'}`}
          onPress={() => tai.taiLai()}
          style={{ borderWidth: 0 }}
        />
      </View>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={{ flex: 1 }}
      >
        {!!tai.loi && (
          <View style={{ padding: 12, gap: 8 }}>
            <Chu color={mau.loi}>{tai.loi}</Chu>
            <Nut loai="phu" onPress={() => tai.taiLai()}>
              Thử tải lại
            </Nut>
          </View>
        )}
        {tai.dangTai && !tai.tin.length && (
          <Chu style={{ padding: 16 }}>Đang tải hội thoại…</Chu>
        )}
        <FlatList
          key={`${id}-${tai.lanHien}`}
          ref={list}
          style={{ flex: 1 }}
          inverted
          data={[...tai.tin].reverse()}
          keyExtractor={(t) => String(t.id)}
          maintainVisibleContentPosition={{
            minIndexForVisible: 0,
            autoscrollToTopThreshold: 60,
          }}
          onContentSizeChange={() => {
            if (!tai.tin.length || (!canVeDay.current && !dangDay.current))
              return
            requestAnimationFrame(() => {
              list.current?.scrollToOffset({ offset: 0, animated: false })
              canVeDay.current = false
            })
          }}
          keyboardShouldPersistTaps="handled"
          contentContainerStyle={{ padding: 20, gap: 8, flexGrow: 1 }}
          viewabilityConfig={viewability}
          onViewableItemsChanged={onThay}
          extraData={[thay, focused, hoatDong, tai.hoi?.cursor_doi_phuong]}
          onScroll={(e) => {
            if (canVeDay.current) return
            dangDay.current = e.nativeEvent.contentOffset.y < 60
            if (dangDay.current) datCoTinMoi(false)
          }}
          scrollEventThrottle={100}
          ListEmptyComponent={
            !tai.dangTai && tai.hoi ? (
              <Chu style={{ textAlign: 'center' }} color={mau.chuPhu}>
                Bắt đầu cuộc trò chuyện với {tai.hoi.doi_phuong.ho_ten}.
              </Chu>
            ) : null
          }
          ListFooterComponent={
            tai.conCu ? (
              <Nut
                loai="phu"
                disabled={tai.dangTai}
                onPress={() => tai.taiLai(true)}
              >
                {tai.dangTai ? 'Đang tải…' : 'Xem tin nhắn cũ hơn'}
              </Nut>
            ) : null
          }
          renderItem={({ item: t }) => {
            const cuaToi = t.nguoi_gui_id === taiKhoan.id
            const hien = thay.includes(t.id) && focused && hoatDong
            return (
              <View
                style={{
                  alignSelf: cuaToi ? 'flex-end' : 'flex-start',
                  maxWidth: '80%',
                  gap: 4,
                }}
              >
                <View
                  style={{
                    borderRadius: 16,
                    borderBottomRightRadius: cuaToi ? 4 : 16,
                    borderBottomLeftRadius: cuaToi ? 16 : 4,
                    backgroundColor: cuaToi ? mau.chinh : mau.the,
                    paddingHorizontal: 16,
                    paddingVertical: 10,
                    borderWidth: cuaToi ? 0 : 1,
                    borderColor: mau.vien,
                    gap: 8,
                  }}
                >
                  {!!t.noi_dung && (
                    <Chu
                      size={14.5}
                      style={{ lineHeight: 23.5625 }}
                      color={cuaToi ? mau.trenChinh : mau.chu}
                      selectable
                    >
                      {t.noi_dung}
                    </Chu>
                  )}
                  {t.anh?.map((a) => (
                    <AnhChat
                      key={a.vi_tri}
                      hoiId={id}
                      tinId={t.id}
                      anh={a}
                      hien={hien}
                      onMatQuyen={() => tai.taiLai()}
                    />
                  ))}
                  <Chu
                    size={10.5}
                    color={cuaToi ? mau.trenChinh : mau.chuPhu}
                    accessibilityLabel={gioVietNam(t.created_at)}
                    style={{ opacity: cuaToi ? 0.7 : 1, marginTop: -4 }}
                  >
                    {gioVietNam(t.created_at)}
                    {cuaToi
                      ? t.id <= (tai.hoi?.cursor_doi_phuong || 0)
                        ? ' · Đã đọc'
                        : ' · Đã gửi'
                      : ''}
                  </Chu>
                </View>
              </View>
            )
          }}
        />
        {coTinMoi && (
          <Nut
            loai="phu"
            onPress={() => {
              list.current?.scrollToOffset({ offset: 0, animated: true })
              datCoTinMoi(false)
            }}
          >
            Về tin mới nhất
          </Nut>
        )}
        {!!ghi.loiGui && (
          <Chu
            color={mau.loi}
            accessibilityLiveRegion="polite"
            style={{ paddingHorizontal: 12, paddingVertical: 6 }}
          >
            {ghi.loiGui.message}
            {choGui ? ' Nội dung đang giữ để gửi lại cùng mã tin.' : ''}
          </Chu>
        )}
        {tai.hoi?.co_the_gui ? (
          <View
            style={{
              backgroundColor: mau.the,
              borderTopWidth: 1,
              borderColor: mau.vien,
              paddingHorizontal: 20,
              paddingVertical: 12,
              gap: 8,
            }}
          >
            {b.anh.length > 0 && (
              <ScrollView horizontal contentContainerStyle={{ gap: 8 }}>
                {b.anh.map((a, i) => (
                  <View key={a.uri} style={{ gap: 4 }}>
                    <Image
                      source={{ uri: a.uri }}
                      cachePolicy="none"
                      style={{ width: 64, height: 64, borderRadius: 8 }}
                    />
                    <Pressable
                      accessibilityRole="button"
                      accessibilityLabel={`Bỏ ảnh ${i + 1}`}
                      disabled={khoa}
                      onPress={() => {
                        donAnh([a.uri])
                        tep.current.delete(a.uri)
                        ban.datBan({
                          ...b,
                          anh: b.anh.filter((_, j) => j !== i),
                        })
                      }}
                      style={{ minHeight: 44, justifyContent: 'center' }}
                    >
                      <Chu size={12} color={mau.loi}>
                        Bỏ ảnh {i + 1}
                      </Chu>
                    </Pressable>
                  </View>
                ))}
              </ScrollView>
            )}
            <View
              style={{ flexDirection: 'row', alignItems: 'flex-end', gap: 8 }}
            >
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Chọn ảnh gửi"
                disabled={khoa || b.anh.length >= 4}
                onPress={chonAnh}
                style={{
                  width: 44,
                  height: 48,
                  alignItems: 'center',
                  justifyContent: 'center',
                  opacity: khoa ? 0.4 : 1,
                }}
              >
                <BieuTuong ten="image" />
              </Pressable>
              <TextInput
                accessibilityLabel="Nội dung tin nhắn"
                placeholder="Nhập tin nhắn…"
                placeholderTextColor={mau.chuPhu}
                multiline
                value={b.noiDung}
                editable={!khoa}
                maxLength={4000}
                onChangeText={(v) => ban.datBan({ ...b, noiDung: v })}
                style={{
                  flex: 1,
                  fontFamily: font.thuong,
                  fontSize: 15,
                  includeFontPadding: false,
                  color: mau.chu,
                  minHeight: 48,
                  maxHeight: 116,
                  paddingHorizontal: 16,
                  paddingVertical: 12,
                  borderRadius: 999,
                  borderWidth: 1,
                  borderColor: mau.vien,
                  backgroundColor: mau.truongNhap,
                }}
              />
              <Nut
                icon="send"
                accessibilityLabel={
                  choGui ? 'Thử gửi lại cùng tin' : 'Gửi tin nhắn'
                }
                disabled={
                  ghi.dangGui ||
                  giay > 0 ||
                  !hoatDong ||
                  (!b.noiDung.trim() && !b.anh.length)
                }
                onPress={gui}
                style={{
                  paddingHorizontal: 12,
                  minWidth: 48,
                  ...(choGui || giay || ghi.dangGui ? {} : { width: 48 }),
                }}
              >
                {ghi.dangGui
                  ? '…'
                  : giay
                    ? `${giay}s`
                    : choGui
                      ? 'Thử lại'
                      : ''}
              </Nut>
            </View>
            <Chu size={10} color={mau.chuPhu}>
              Tối đa 4 ảnh JPG/PNG/WebP, 5 MB mỗi ảnh.
            </Chu>
          </View>
        ) : (
          tai.hoi && (
            <Chu
              style={{ padding: 16, backgroundColor: mau.the }}
              color={mau.chuPhu}
            >
              Hội thoại chỉ đọc. Bạn không thể gửi thêm tin trong phân công này.
            </Chu>
          )
        )}
      </KeyboardAvoidingView>
      {ban.hopRoi}
    </SafeAreaView>
  )
}
