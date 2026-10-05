import { View } from "react-native";
import Svg, { Circle, G } from "react-native-svg";
import { BieuTuong } from "./GiaoDien";
import { useGiaoDien } from "../theme";

// Hai vòng tròn và Dumbbell là SVG gốc của ExMedia trong bản xuất Figma Make.
export default function MinhHoaBaiTap({ lon = false }) {
  const { mau } = useGiaoDien();
  return (
    <View
      style={{
        height: lon ? 224 : 56,
        width: lon ? "100%" : 56,
        borderRadius: lon ? 24 : 12,
        backgroundColor: mau.chinhNhat,
        overflow: "hidden",
        alignItems: "center",
        justifyContent: "center",
      }}
    >
      <Svg
        viewBox="0 0 100 100"
        width={lon ? "100%" : 56}
        height={lon ? 224 : 56}
        style={{ position: "absolute" }}
      >
        <G opacity={0.12}>
          <Circle
            cx="50"
            cy="50"
            r="38"
            fill="none"
            stroke={mau.chinh}
            strokeWidth="6"
          />
          <Circle
            cx="50"
            cy="50"
            r="22"
            fill="none"
            stroke={mau.chinh}
            strokeWidth="6"
          />
        </G>
      </Svg>
      <BieuTuong ten="Dumbbell" size={lon ? 64 : 24} strokeWidth={1.8} />
    </View>
  );
}
