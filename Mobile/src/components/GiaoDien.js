import { useContext, useEffect, useRef, useState } from 'react'
import {
  Animated,
  View,
  Text,
  Pressable,
  ScrollView,
  StyleSheet,
  TextInput,
  KeyboardAvoidingView,
  Platform,
  Modal,
} from 'react-native'
import IconFigma from './IconFigma'
import { SafeAreaView } from 'react-native-safe-area-context'
import { font, useGiaoDien } from '../theme'
import { useXemTruoc } from '../contexts/XemTruocContext'
import { NavigationContext } from '@react-navigation/native'
import { useHieuUngFigma } from '../hooks/useHieuUngFigma'

export function BieuTuong({ ten, size = 22, color, ...props }) {
  const { mau } = useGiaoDien()
  return (
    <IconFigma
      ten={ten}
      size={size}
      color={color || mau.chinh}
      accessible={false}
      {...props}
    />
  )
}
export function Chu({
  children,
  size = 14,
  dam = 'thuong',
  color,
  style,
  ...props
}) {
  const { mau } = useGiaoDien()
  return (
    <Text
      {...props}
      style={[
        {
          fontFamily: font[dam],
          includeFontPadding: false,
          fontSize: size,
          lineHeight: size * 1.5,
          color: color || mau.chu,
        },
        style,
      ]}
    >
      {children}
    </Text>
  )
}
export function Nut({
  children,
  icon,
  onPress,
  loai = 'chinh',
  style,
  disabled,
  accessibilityLabel,
  sm = false,
  iconSize,
  textStyle,
}) {
  const { mau, cheDoToi } = useGiaoDien()
  const color =
    loai === 'chinh'
      ? mau.trenChinh
      : loai === 'lime'
        ? mau.trenNangLuong
        : loai === 'phu'
          ? mau.chu
          : loai === 'loi'
            ? cheDoToi
              ? '#2A0F0C'
              : '#FFFFFF'
            : mau.chinh
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel}
      accessibilityState={{ disabled: !!disabled }}
      disabled={disabled}
      onPress={onPress}
      style={({ pressed }) => [
        styles.nut,
        {
          backgroundColor:
            loai === 'chinh'
              ? mau.chinh
              : loai === 'loi'
                ? mau.loi
                : loai === 'lime'
                  ? mau.nangLuong
                  : loai === 'soft'
                    ? mau.chinhNhat
                    : loai === 'ghost'
                      ? 'transparent'
                      : mau.the,
          borderColor: loai === 'chinh' ? mau.chinh : mau.vien,
          borderWidth: loai === 'phu' ? 1 : 0,
          minHeight: sm ? 36 : 48,
          paddingHorizontal: sm ? 16 : 24,
          opacity: disabled ? 0.4 : 1,
          transform: [{ scale: pressed ? 0.97 : 1 }],
        },
        style,
      ]}
    >
      {icon && (
        <BieuTuong ten={icon} size={iconSize || (sm ? 16 : 18)} color={color} />
      )}
      {children !== undefined && children !== null && children !== '' && (
        <Chu
          size={sm ? 13 : 15}
          dam="damVua"
          color={color}
          style={[{ textAlign: 'center', flexShrink: 1 }, textStyle]}
        >
          {children}
        </Chu>
      )}
    </Pressable>
  )
}
export function NutIcon({
  icon,
  nhan,
  onPress,
  style,
  badge,
  disabled,
  color,
  size = 22,
}) {
  const { mau } = useGiaoDien()
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={nhan}
      accessibilityState={{ disabled: !!disabled }}
      disabled={disabled}
      onPress={onPress}
      style={({ pressed }) => [
        styles.nutIcon,
        {
          backgroundColor: mau.the,
          borderColor: mau.vien,
          opacity: disabled ? 0.4 : pressed ? 0.65 : 1,
        },
        style,
      ]}
    >
      <BieuTuong ten={icon} size={size} color={color} />
      {badge && <View style={[styles.cham, { backgroundColor: mau.loi }]} />}
    </Pressable>
  )
}
export function The({ children, style }) {
  const { mau } = useGiaoDien()
  return (
    <View
      style={[
        styles.the,
        { backgroundColor: mau.the, borderColor: mau.vien },
        style,
      ]}
    >
      {children}
    </View>
  )
}
export function Nhan({ children, loai = 'chinh', icon, trangThai }) {
  const { mau } = useGiaoDien()
  let color =
    loai === 'xanhDuong'
      ? mau.xanhDuong
      : loai === 'vang'
        ? mau.vang
        : mau.chinh
  let backgroundColor =
    loai === 'xanhDuong'
      ? mau.xanhNhat
      : loai === 'vang'
        ? mau.vangNhat
        : mau.chinhNhat
  if (
    [
      'NHAP',
      'DA_HUY',
      'HET_HAN',
      'QUA_HAN',
      'QUA_HAN_XAC_NHAN',
      'DA_HET_HAN',
    ].includes(trangThai)
  ) {
    color = mau.chuPhu
    backgroundColor = mau.vien
  }
  if (['CHO_DUYET', 'CHO_XAC_NHAN', 'CHO_THANH_TOAN'].includes(trangThai)) {
    color = mau.vang
    backgroundColor = mau.vangNhat
  }
  if (['HOAN_THANH', 'DA_THANH_TOAN'].includes(trangThai)) {
    color = mau.trenChinh
    backgroundColor = mau.chinh
  }
  if (['DANG_AP_DUNG', 'DANG_SU_DUNG'].includes(trangThai)) {
    color = mau.trenNangLuong
    backgroundColor = mau.nangLuong
  }
  if (['TU_CHOI', 'VANG_MAT'].includes(trangThai)) {
    color = mau.loi
    backgroundColor = mau.loiNhat
  }
  return (
    <View style={[styles.nhan, { backgroundColor }]}>
      {icon && <BieuTuong ten={icon} size={14} color={color} />}
      <Chu size={11} dam="damVua" color={color} style={{ lineHeight: 11 }}>
        {children}
      </Chu>
    </View>
  )
}
export function AnhDaiDien({ ten, size = 40, loai = 'chinh' }) {
  const { mau } = useGiaoDien()
  const chuDau = ten
    .trim()
    .split(/\s+/)
    .slice(-2)
    .map((tu) => tu[0])
    .join('')
  return (
    <View
      accessible
      accessibilityLabel={`Ảnh đại diện minh họa của ${ten}`}
      style={{
        width: size,
        height: size,
        borderRadius: size / 2,
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: loai === 'xanhDuong' ? mau.xanhNhat : mau.chinhNhat,
      }}
    >
      <Chu
        size={size * 0.38}
        dam="dam"
        color={loai === 'xanhDuong' ? mau.xanhDuong : mau.chinh}
      >
        {chuDau}
      </Chu>
    </View>
  )
}
export function TieuDeMuc({ children, icon, phu, onPress }) {
  const { mau } = useGiaoDien()
  return (
    <View style={styles.hang}>
      <View style={[styles.hangTrai, { flex: 1 }]}>
        {icon && <BieuTuong ten={icon} size={18} />}
        <Chu
          dam="dam"
          size={17}
          style={{ flexShrink: 1, letterSpacing: -0.425 }}
        >
          {children}
        </Chu>
      </View>
      {phu &&
        (onPress ? (
          <Pressable
            accessibilityRole="button"
            onPress={onPress}
            style={({ pressed }) => ({
              paddingVertical: 14,
              paddingLeft: 8,
              opacity: pressed ? 0.6 : 1,
            })}
          >
            <Chu size={12} dam="damVua" color={mau.chinh}>
              {phu}
            </Chu>
          </Pressable>
        ) : (
          <Chu size={12} color={mau.chuPhu}>
            {phu}
          </Chu>
        ))}
    </View>
  )
}
export function ManHinh({
  children,
  tieuDe,
  onBack,
  tacVu,
  scroll = true,
  bas = false,
  refreshControl,
  contentStyle,
  footer,
  noi,
  truocNoiDung,
}) {
  const { mau } = useGiaoDien()
  const navigation = useContext(NavigationContext)
  const [focused, datFocused] = useState(navigation?.isFocused() ?? true)
  useEffect(() => {
    if (!navigation) return
    const mo = navigation.addListener('focus', () => datFocused(true))
    const dong = navigation.addListener('blur', () => datFocused(false))
    return () => {
      mo()
      dong()
    }
  }, [navigation])
  const hieuUng = useHieuUngFigma(focused)
  const { vaiTro, dangXemTruoc } = useXemTruoc()
  const edges = [
    'left',
    'right',
    ...(!dangXemTruoc ? ['top'] : []),
    ...(!vaiTro || bas ? ['bottom'] : []),
  ]
  return (
    <SafeAreaView style={{ flex: 1, backgroundColor: mau.nen }} edges={edges}>
      <Animated.View style={[{ flex: 1 }, hieuUng]}>
        {tieuDe && (
          <View
            style={[
              styles.tieuDeTrang,
              { borderColor: mau.vien, backgroundColor: mau.the },
            ]}
          >
            {onBack ? (
              <NutIcon
                icon="chevron-left"
                color={mau.chu}
                nhan="Quay lại"
                onPress={onBack}
                style={{
                  width: 40,
                  height: 40,
                  backgroundColor: 'transparent',
                  borderWidth: 0,
                }}
              />
            ) : (
              <View style={{ width: 8 }} />
            )}
            <Chu size={17} dam="dam" style={{ flex: 1, letterSpacing: -0.425 }}>
              {tieuDe}
            </Chu>
            {tacVu}
          </View>
        )}
        <KeyboardAvoidingView
          style={{ flex: 1 }}
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        >
          {truocNoiDung}
          {scroll ? (
            <ScrollView
              refreshControl={refreshControl}
              keyboardShouldPersistTaps="handled"
              contentContainerStyle={[styles.noiDung, contentStyle]}
            >
              {children}
            </ScrollView>
          ) : (
            <View style={[styles.noiDung, contentStyle]}>{children}</View>
          )}
        </KeyboardAvoidingView>
        {footer && (
          <View
            style={{
              backgroundColor: mau.the,
              borderTopWidth: 1,
              borderColor: mau.vien,
              paddingHorizontal: 20,
              paddingVertical: 12,
            }}
          >
            {footer}
          </View>
        )}
        {noi && (
          <View
            pointerEvents="box-none"
            style={{ position: 'absolute', right: 20, bottom: 16 }}
          >
            {noi}
          </View>
        )}
      </Animated.View>
    </SafeAreaView>
  )
}
export function TruongNhap({
  nhan,
  icon,
  loi,
  multiline,
  style,
  inputStyle,
  hint,
  hienAnMatKhau = false,
  ...props
}) {
  const { mau } = useGiaoDien()
  const inputRef = useRef(null)
  const [hienMatKhau, datHienMatKhau] = useState(false)
  return (
    <View style={{ gap: 6 }}>
      <Pressable onPress={() => inputRef.current?.focus()} accessible={false}>
        <Chu size={13} dam="damVua">
          {nhan}
        </Chu>
      </Pressable>
      <View
        style={[
          styles.oNhap,
          {
            borderColor: loi ? mau.loi : mau.vien,
            backgroundColor: mau.truongNhap,
            alignItems: multiline ? 'flex-start' : 'center',
          },
          style,
        ]}
      >
        {icon && (
          <BieuTuong
            ten={icon}
            size={20}
            color={mau.chuPhu}
            style={multiline && { marginTop: 4 }}
          />
        )}
        <TextInput
          ref={inputRef}
          accessibilityLabel={nhan}
          placeholderTextColor={mau.chuPhu}
          multiline={multiline}
          style={{
            flex: 1,
            minWidth: 0,
            fontFamily: font.thuong,
            includeFontPadding: false,
            fontSize: 15,
            color: mau.chu,
            minHeight: multiline ? 96 : 24,
            padding: 0,
            textAlignVertical: multiline ? 'top' : 'center',
            ...inputStyle,
            ...(Platform.OS === 'web' ? { outlineStyle: 'none' } : {}),
          }}
          {...props}
          secureTextEntry={hienAnMatKhau ? !hienMatKhau : props.secureTextEntry}
        />
        {hienAnMatKhau && (
          <Pressable
            accessibilityRole="button"
            accessibilityLabel={hienMatKhau ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'}
            onPress={() => datHienMatKhau(!hienMatKhau)}
            style={{
              width: 24,
              height: 46,
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <BieuTuong
              ten={hienMatKhau ? 'eye-off' : 'eye'}
              size={18}
              color={mau.chuPhu}
            />
          </Pressable>
        )}
      </View>
      {loi && (
        <Chu size={12} color={mau.loi} accessibilityLiveRegion="polite">
          {loi}
        </Chu>
      )}
      {!loi && hint && (
        <Chu size={12} color={mau.chuPhu} style={{ marginTop: -2 }}>
          {hint}
        </Chu>
      )}
    </View>
  )
}
export function HangMenu({
  icon,
  tieuDe,
  moTa,
  onPress,
  cuoi = false,
  nguyHiem = false,
}) {
  const { mau } = useGiaoDien()
  return (
    <Pressable
      accessibilityRole="button"
      onPress={onPress}
      style={({ pressed }) => [
        styles.menu,
        {
          borderBottomWidth: cuoi ? 0 : 1,
          borderColor: mau.vien,
          opacity: pressed ? 0.65 : 1,
        },
      ]}
    >
      <View
        style={[
          styles.iconMenu,
          { backgroundColor: nguyHiem ? mau.loiNhat : mau.chinhNhat },
        ]}
      >
        <BieuTuong
          ten={icon}
          size={20}
          color={nguyHiem ? mau.loi : mau.chinh}
        />
      </View>
      <View style={{ flex: 1 }}>
        <Chu size={15} dam="damVua" color={nguyHiem ? mau.loi : mau.chu}>
          {tieuDe}
        </Chu>
        {moTa && (
          <Chu size={12.5} color={mau.chuPhu}>
            {moTa}
          </Chu>
        )}
      </View>
      <BieuTuong ten="chevron-right" size={18} color={mau.chuPhu} />
    </Pressable>
  )
}
export function HopThongBao() {
  const { thongBao, datThongBao } = useXemTruoc()
  const { mau } = useGiaoDien()
  const hieuUng = useHieuUngFigma(!!thongBao, 'sheet')
  return (
    <Modal
      statusBarTranslucent
      visible={!!thongBao}
      transparent
      animationType="fade"
      onRequestClose={() => datThongBao(null)}
    >
      <View style={[styles.manChe, { backgroundColor: mau.manChe }]}>
        <Pressable
          style={StyleSheet.absoluteFill}
          onPress={() => datThongBao(null)}
          accessibilityLabel="Đóng thông báo"
        />
        <Animated.View
          accessibilityViewIsModal
          style={[styles.hop, { backgroundColor: mau.the }, hieuUng]}
        >
          <View
            style={{
              width: 40,
              height: 4,
              borderRadius: 2,
              backgroundColor: mau.vien,
              alignSelf: 'center',
              marginBottom: 12,
            }}
          />
          <Chu
            size={18}
            dam="dam"
            style={{ letterSpacing: -0.45, marginBottom: 12 }}
          >
            {thongBao?.tieuDe || 'Bản xem trước'}
          </Chu>
          <Chu
            color={mau.chuPhu}
            style={{ lineHeight: 22.75, marginBottom: 20 }}
          >
            {thongBao?.noiDung}
          </Chu>
          <Nut onPress={() => datThongBao(null)}>Đã hiểu</Nut>
        </Animated.View>
      </View>
    </Modal>
  )
}
const styles = StyleSheet.create({
  nut: {
    minHeight: 48,
    borderRadius: 999,
    paddingHorizontal: 24,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  nutIcon: {
    width: 48,
    height: 48,
    borderRadius: 999,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cham: {
    width: 8,
    height: 8,
    borderRadius: 4,
    position: 'absolute',
    top: 10,
    right: 10,
  },
  the: { borderRadius: 16, borderWidth: 1, padding: 16, gap: 16 },
  nhan: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    alignSelf: 'flex-start',
    borderRadius: 9.5,
    overflow: 'hidden',
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  hang: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  hangTrai: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  tieuDeTrang: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 8,
    height: 56,
    gap: 4,
    borderBottomWidth: 1,
  },
  noiDung: {
    padding: 20,
    paddingBottom: 32,
    gap: 16,
    flexGrow: 1,
    width: '100%',
    maxWidth: 560,
    alignSelf: 'center',
  },
  oNhap: {
    flexDirection: 'row',
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 0,
    gap: 10,
    minHeight: 48,
  },
  menu: {
    flexDirection: 'row',
    gap: 12,
    alignItems: 'center',
    minHeight: 64,
    paddingVertical: 14,
    paddingHorizontal: 16,
  },
  iconMenu: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 12,
  },
  manChe: {
    flex: 1,
    justifyContent: 'flex-end',
    alignItems: 'center',
  },
  hop: {
    width: '100%',
    maxWidth: 560,
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 24,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
  },
})
