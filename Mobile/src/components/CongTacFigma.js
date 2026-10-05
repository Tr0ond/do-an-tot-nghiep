import { Pressable, View } from 'react-native'
import { useGiaoDien } from '../theme'

export default function CongTacFigma({
  value,
  onValueChange,
  nhan,
  disabled = false,
}) {
  const { mau, cheDoToi } = useGiaoDien()
  return (
    <Pressable
      accessibilityRole="switch"
      accessibilityLabel={nhan}
      accessibilityState={{ checked: value, disabled }}
      disabled={disabled}
      onPress={() => onValueChange(!value)}
      style={{
        width: 48,
        height: 28,
        borderRadius: 999,
        backgroundColor: value ? mau.chinh : mau.vien,
        opacity: disabled ? 0.4 : 1,
      }}
    >
      <View
        style={{
          position: 'absolute',
          left: value ? 24 : 4,
          top: 4,
          width: 20,
          height: 20,
          borderRadius: 10,
          backgroundColor: cheDoToi ? '#F0F5F5' : '#FFFFFF',
          boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
        }}
      />
    </Pressable>
  )
}
