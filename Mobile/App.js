import { ActivityIndicator, Platform, StatusBar, View } from "react-native";
import { SafeAreaProvider } from "react-native-safe-area-context";
import { useFonts } from "expo-font";
import { XemTruocProvider } from "./src/contexts/XemTruocContext";
import { TraoDoiProvider } from "./src/contexts/TraoDoiContext";
import { ThongBaoDayProvider } from "./src/contexts/ThongBaoDayContext";
import DieuHuong from "./src/navigation/DieuHuong";
import { useGiaoDien } from "./src/theme";
import { HopThongBao } from "./src/components/GiaoDien";

function UngDung() {
  const { mau, cheDoToi } = useGiaoDien();
  return (
    <View style={{ flex: 1, backgroundColor: mau.nen }}>
      <StatusBar
        barStyle={cheDoToi ? "light-content" : "dark-content"}
        backgroundColor={
          Platform.OS === "android" && Platform.Version < 35
            ? mau.nen
            : undefined
        }
      />
      <DieuHuong />
      <HopThongBao />
    </View>
  );
}

export default function App() {
  const [daTaiFont, loiFont] = useFonts({
    BeVietnamPro_400Regular: require("@expo-google-fonts/be-vietnam-pro/400Regular/BeVietnamPro_400Regular.ttf"),
    BeVietnamPro_500Medium: require("@expo-google-fonts/be-vietnam-pro/500Medium/BeVietnamPro_500Medium.ttf"),
    BeVietnamPro_600SemiBold: require("@expo-google-fonts/be-vietnam-pro/600SemiBold/BeVietnamPro_600SemiBold.ttf"),
    BeVietnamPro_700Bold: require("@expo-google-fonts/be-vietnam-pro/700Bold/BeVietnamPro_700Bold.ttf"),
    BeVietnamPro_800ExtraBold: require("@expo-google-fonts/be-vietnam-pro/800ExtraBold/BeVietnamPro_800ExtraBold.ttf"),
  });
  if (!daTaiFont && !loiFont)
    return (
      <View
        style={{
          flex: 1,
          justifyContent: "center",
          alignItems: "center",
          backgroundColor: "#F5F7F8",
        }}
      >
        <ActivityIndicator
          size="large"
          color="#087F75"
          accessibilityLabel="Đang mở ứng dụng"
        />
      </View>
    );
  return (
    <SafeAreaProvider>
      <XemTruocProvider>
        <TraoDoiProvider>
          <ThongBaoDayProvider>
            <UngDung />
          </ThongBaoDayProvider>
        </TraoDoiProvider>
      </XemTruocProvider>
    </SafeAreaProvider>
  );
}
