import { Pressable, View, ScrollView, TextInput, Platform } from 'react-native'
import { Chu, BieuTuong } from './GiaoDien'
import { useGiaoDien } from '../theme'
import { font } from '../theme'
import { doiNgay, homNay } from '../utils/lich'

export function TieuDeTab({ children, phu, tacVu, style }) {
  const { mau } = useGiaoDien()
  return (
    <View style={[{ paddingTop: 24, paddingBottom: 16 }, style]}>
      <Chu size={26} dam="ratDam" style={{ letterSpacing: -0.65 }}>
        {children}
      </Chu>
      {phu && (
        <Chu size={13} color={mau.chuPhu}>
          {phu}
        </Chu>
      )}
      {tacVu && (
        <View style={{ position: 'absolute', right: 0, top: 24 }}>{tacVu}</View>
      )}
    </View>
  )
}
export function Muc({ children, phu, onPress, nhan }) {
  const { mau } = useGiaoDien()
  return (
    <View
      style={{
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        marginTop: 24,
        marginBottom: 12,
        gap: 8,
      }}
    >
      <View style={{ flex: 1 }}>
        <Chu size={17} dam="dam" style={{ letterSpacing: -0.425 }}>
          {children}
        </Chu>
        {phu && (
          <Chu size={13} color={mau.chuPhu}>
            {phu}
          </Chu>
        )}
      </View>
      {onPress ? (
        <Pressable accessibilityRole="button" onPress={onPress}>
          <Chu size={13} dam="damVua" color={mau.chinh}>
            {nhan}
          </Chu>
        </Pressable>
      ) : null}
    </View>
  )
}
export function TienDo({ value, max, lime }) {
  const { mau } = useGiaoDien()
  return (
    <View
      accessibilityRole="progressbar"
      accessibilityValue={{ min: 0, max, now: value }}
      style={{
        height: 10,
        backgroundColor: mau.vien,
        borderRadius: 999,
        overflow: 'hidden',
      }}
    >
      <View
        style={{
          height: 10,
          borderRadius: 999,
          width: `${Math.min(100, Math.max(0, max ? (value / max) * 100 : 0))}%`,
          backgroundColor: lime ? mau.nangLuong : mau.chinh,
        }}
      />
    </View>
  )
}
export function Trong({ icon = 'CalendarClock', tieuDe, moTa, children }) {
  const { mau } = useGiaoDien()
  return (
    <View
      style={{
        borderRadius: 16,
        borderWidth: 1,
        borderStyle: 'dashed',
        borderColor: mau.vien,
        paddingHorizontal: 24,
        marginHorizontal: 20,
        paddingVertical: 40,
        alignItems: 'center',
      }}
    >
      <View
        style={{
          width: 56,
          height: 56,
          borderRadius: 28,
          backgroundColor: mau.chinhNhat,
          marginBottom: 16,
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <BieuTuong ten={icon} size={26} />
      </View>
      <Chu size={16} dam="dam" style={{ textAlign: 'center' }}>
        {tieuDe}
      </Chu>
      {moTa && (
        <Chu
          size={13}
          color={mau.chuPhu}
          style={{
            lineHeight: 21.125,
            textAlign: 'center',
            marginTop: 4,
            marginBottom: 20,
          }}
        >
          {moTa}
        </Chu>
      )}
      {children}
    </View>
  )
}
export function ChonPhan({ value, onChange, options, style }) {
  const { mau } = useGiaoDien()
  return (
    <View
      style={[
        {
          backgroundColor: `${mau.vien}B3`,
          borderRadius: 999,
          padding: 4,
          flexDirection: 'row',
        },
        style,
      ]}
    >
      {options.map(([ma, nhan]) => (
        <Pressable
          key={ma}
          accessibilityRole="tab"
          accessibilityState={{ selected: ma === value }}
          onPress={() => onChange(ma)}
          style={{
            height: 36,
            flex: 1,
            borderRadius: 999,
            backgroundColor: ma === value ? mau.the : 'transparent',
            justifyContent: 'center',
            alignItems: 'center',
            boxShadow: ma === value ? '0 1px 2px rgba(0,0,0,0.05)' : undefined,
          }}
        >
          <Chu
            size={13}
            dam="damVua"
            color={ma === value ? mau.chu : mau.chuPhu}
          >
            {nhan}
          </Chu>
        </Pressable>
      ))}
    </View>
  )
}
export function Chip({ children, chon, onPress, disabled = false }) {
  const { mau } = useGiaoDien()
  return (
    <Pressable
      disabled={disabled}
      accessibilityRole="button"
      accessibilityState={{ selected: chon, disabled }}
      onPress={onPress}
      style={{
        height: 36,
        paddingHorizontal: 16,
        borderRadius: 999,
        borderWidth: 1,
        borderColor: chon ? mau.chinh : mau.vien,
        backgroundColor: chon ? mau.chinh : mau.the,
        opacity: disabled ? 0.4 : 1,
        alignItems: 'center',
        justifyContent: 'center',
      }}
    >
      <Chu size={13} dam="damVua" color={chon ? mau.trenChinh : mau.chu}>
        {children}
      </Chu>
    </Pressable>
  )
}
export function NhomChip({ children }) {
  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      style={{ marginHorizontal: -20, flexGrow: 0, height: 36, maxHeight: 36 }}
      contentContainerStyle={{ paddingHorizontal: 20, gap: 8 }}
    >
      {children}
    </ScrollView>
  )
}

export function TimKiem({
  value,
  onChangeText,
  onSubmitEditing,
  placeholder = 'Tìm kiếm…',
  maxLength = 100,
}) {
  const { mau } = useGiaoDien()
  return (
    <View
      style={{
        height: 48,
        borderRadius: 999,
        borderWidth: 1,
        borderColor: mau.vien,
        backgroundColor: mau.the,
        paddingLeft: 44,
        paddingRight: 16,
        justifyContent: 'center',
      }}
    >
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Tìm kiếm"
        onPress={onSubmitEditing}
        style={{
          position: 'absolute',
          left: 8,
          height: 46,
          width: 34,
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <BieuTuong ten="Search" size={18} color={mau.chuPhu} />
      </Pressable>
      <TextInput
        accessibilityLabel={placeholder}
        value={value}
        onChangeText={onChangeText}
        onSubmitEditing={onSubmitEditing}
        returnKeyType="search"
        maxLength={maxLength}
        placeholder={placeholder}
        placeholderTextColor={mau.chuPhu}
        style={{
          fontFamily: font.thuong,
          fontSize: 15,
          color: mau.chu,
          padding: 0,
          ...(Platform.OS === 'web' ? { outlineStyle: 'none' } : {}),
        }}
      />
    </View>
  )
}

export function DaiNgay({ value, onChange, disabled = false }) {
  const { mau } = useGiaoDien()
  const dau = value && value < homNay() ? value : homNay()
  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      style={{ marginHorizontal: -20, flexGrow: 0, height: 68, maxHeight: 68 }}
      contentContainerStyle={{ paddingHorizontal: 20, gap: 8 }}
    >
      {Array.from({ length: 14 }, (_, i) => doiNgay(dau, i)).map((ngay) => (
        <Pressable
          key={ngay}
          disabled={disabled}
          accessibilityRole="button"
          accessibilityLabel={ngay}
          accessibilityState={{ selected: value === ngay }}
          onPress={() => onChange(ngay)}
          style={{
            width: 56,
            height: 68,
            borderRadius: 16,
            borderWidth: 1,
            borderColor: ngay === value ? mau.chinh : mau.vien,
            backgroundColor: ngay === value ? mau.chinh : mau.the,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Chu
            size={11}
            dam="damVua"
            color={ngay === value ? mau.trenChinh : mau.chu}
            style={{ opacity: 0.8 }}
          >
            {
              ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][
                new Date(`${ngay}T00:00:00Z`).getUTCDay()
              ]
            }
          </Chu>
          <Chu
            size={18}
            dam="ratDam"
            color={ngay === value ? mau.trenChinh : mau.chu}
          >
            {ngay.slice(8)}
          </Chu>
        </Pressable>
      ))}
    </ScrollView>
  )
}
