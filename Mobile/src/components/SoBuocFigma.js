import { Pressable, TextInput, View, Platform } from 'react-native'
import { Chu } from './GiaoDien'
import { font, useGiaoDien } from '../theme'

export default function SoBuocFigma({
  nhan,
  value,
  onChange,
  min = 0,
  max = 1000,
  step = 1,
  disabled = false,
}) {
  const { mau } = useGiaoDien()
  const so = Number(String(value).replace(',', '.'))
  function doi(delta) {
    if (disabled) return
    onChange(
      String(
        Math.min(
          max,
          Math.max(min, +((Number.isFinite(so) ? so : min) + delta).toFixed(1)),
        ),
      ),
    )
  }
  return (
    <View
      style={{
        width: 112,
        height: 40,
        flexDirection: 'row',
        alignItems: 'center',
        borderWidth: 1,
        borderColor: mau.vien,
        backgroundColor: mau.truongNhap,
        borderRadius: 999,
        overflow: 'hidden',
        opacity: disabled ? 0.4 : 1,
      }}
    >
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={`Giảm ${nhan}`}
        disabled={disabled || so <= min}
        onPress={() => doi(-step)}
        style={{
          width: 36,
          height: 38,
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <Chu size={18} dam="damVua" color={mau.chinh}>
          −
        </Chu>
      </Pressable>
      <TextInput
        accessibilityLabel={nhan}
        value={String(value ?? '')}
        onChangeText={onChange}
        editable={!disabled}
        keyboardType="decimal-pad"
        style={{
          flex: 1,
          height: 38,
          padding: 0,
          textAlign: 'center',
          fontFamily: font.damVua,
          fontSize: 14,
          includeFontPadding: false,
          color: mau.chu,
          ...(Platform.OS === 'web' ? { outlineStyle: 'none' } : {}),
        }}
      />
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={`Tăng ${nhan}`}
        disabled={disabled || so >= max}
        onPress={() => doi(step)}
        style={{
          width: 36,
          height: 38,
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <Chu size={18} dam="damVua" color={mau.chinh}>
          +
        </Chu>
      </Pressable>
    </View>
  )
}
