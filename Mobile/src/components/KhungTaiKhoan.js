import {
  View,
  ScrollView,
  Pressable,
  KeyboardAvoidingView,
  Platform,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { Chu, BieuTuong } from './GiaoDien'
import { useGiaoDien } from '../theme'

export function LogoTr0ond({ size = 26, sang = false }) {
  const { mau } = useGiaoDien()
  return (
    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
      <View
        style={{
          width: size * 1.35,
          height: size * 1.35,
          borderRadius: 12,
          backgroundColor: mau.chinh,
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <BieuTuong
          ten="Dumbbell"
          size={size * 0.8}
          strokeWidth={2.4}
          color={mau.trenChinh}
        />
        <View
          style={{
            position: 'absolute',
            right: -6,
            top: -6,
            width: 16,
            height: 16,
            borderRadius: 8,
            backgroundColor: mau.nangLuong,
            borderWidth: 2,
            borderColor: mau.nen,
          }}
        />
      </View>
      <Chu
        size={size}
        dam="ratDam"
        color={sang ? '#FFFFFF' : mau.chu}
        style={{ letterSpacing: -size * 0.025 }}
      >
        Tr0ond
      </Chu>
    </View>
  )
}

export default function KhungTaiKhoan({ children, tieuDe, moTa, onBack }) {
  const { mau } = useGiaoDien()
  return (
    <SafeAreaView
      edges={['top', 'left', 'right', 'bottom']}
      style={{ flex: 1, backgroundColor: mau.chinh }}
    >
      <KeyboardAvoidingView
        style={{ flex: 1, backgroundColor: mau.nen }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView
          keyboardShouldPersistTaps="handled"
          contentContainerStyle={{ flexGrow: 1 }}
        >
          <View
            style={{
              backgroundColor: mau.chinh,
              paddingHorizontal: 24,
              paddingTop: 48,
              paddingBottom: 40,
            }}
          >
            {onBack && (
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Quay lại"
                onPress={onBack}
                style={{
                  flexDirection: 'row',
                  gap: 4,
                  alignItems: 'center',
                  height: 24,
                  opacity: 0.9,
                  marginBottom: 20,
                  marginLeft: -4,
                  alignSelf: 'flex-start',
                }}
              >
                <BieuTuong ten="ArrowLeft" size={14} color={mau.trenChinh} />
                <Chu
                  size={14}
                  dam="damVua"
                  color={mau.trenChinh}
                  style={{ lineHeight: 24 }}
                >
                  Quay lại
                </Chu>
              </Pressable>
            )}
            <View style={{ marginBottom: 32 }}>
              <LogoTr0ond sang />
            </View>
            <Chu
              size={28}
              dam="ratDam"
              color={mau.trenChinh}
              style={{ lineHeight: 35, letterSpacing: -0.7 }}
            >
              {tieuDe}
            </Chu>
            <Chu
              size={14}
              color={mau.trenChinh}
              style={{ marginTop: 8, opacity: 0.85 }}
            >
              {moTa}
            </Chu>
          </View>
          <View
            style={{
              marginTop: -20,
              borderTopLeftRadius: 24,
              borderTopRightRadius: 24,
              backgroundColor: mau.nen,
              paddingHorizontal: 24,
              paddingTop: 28,
              paddingBottom: 40,
              flexGrow: 1,
            }}
          >
            {children}
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  )
}
