import {
  View,
  ScrollView,
  Pressable,
  KeyboardAvoidingView,
  Platform,
  Image,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Chu, BieuTuong } from "./GiaoDien";
import { useGiaoDien } from "../theme";

export function LogoFitForge({ size = 26, sang = false }) {
  const { mau } = useGiaoDien();
  return (
    <View style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
      <Image
        source={require("../../assets/brand/logo-fitforge.png")}
        resizeMode="contain"
        accessible={false}
        accessibilityIgnoresInvertColors
        style={{
          width: size * 1.35,
          height: size * 1.35,
        }}
      />
      <Chu
        size={size}
        dam="ratDam"
        color={sang ? "#FFFFFF" : mau.chu}
        style={{ letterSpacing: -size * 0.025 }}
      >
        FitForge
      </Chu>
    </View>
  );
}

export default function KhungTaiKhoan({ children, tieuDe, moTa, onBack }) {
  const { mau } = useGiaoDien();
  return (
    <SafeAreaView
      edges={["top", "left", "right", "bottom"]}
      style={{ flex: 1, backgroundColor: mau.chinh }}
    >
      <KeyboardAvoidingView
        style={{ flex: 1, backgroundColor: mau.nen }}
        behavior={Platform.OS === "ios" ? "padding" : "height"}
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
                  flexDirection: "row",
                  gap: 4,
                  alignItems: "center",
                  height: 24,
                  opacity: 0.9,
                  marginBottom: 20,
                  marginLeft: -4,
                  alignSelf: "flex-start",
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
              <LogoFitForge sang />
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
  );
}
