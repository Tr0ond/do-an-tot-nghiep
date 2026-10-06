import { Linking } from "react-native";
import { ManHinh, The, Chu, Nut } from "../../components/GiaoDien";
import { useThongBaoDay } from "../../contexts/ThongBaoDayContext";

export default function ThongBaoDay({ navigation }) {
  const { trangThai, dangGui, khaDung, doi } = useThongBaoDay();
  return (
    <ManHinh
      bas
      tieuDe="Thông báo trên điện thoại"
      onBack={() => navigation.goBack()}
    >
      <The>
        <Chu dam="dam">Tin nhắn và lịch hẹn</Chu>
        <Chu>
          Nội dung tin nhắn và thông tin tập luyện không hiển thị trên thông báo
          bên ngoài app. Bấm thông báo để xem sau khi đăng nhập.
        </Chu>
        <Chu accessibilityLiveRegion="polite">{trangThai}</Chu>
        <Nut disabled={!khaDung || dangGui} onPress={() => doi(true)}>
          {dangGui ? "Đang cập nhật…" : "Bật thông báo"}
        </Nut>
        <Nut
          loai="phu"
          disabled={!khaDung || dangGui}
          onPress={() => doi(false)}
        >
          Tắt trên thiết bị này
        </Nut>
        {khaDung && (
          <Nut
            loai="ghost"
            onPress={() => Linking.openSettings().catch(() => {})}
          >
            Mở cài đặt điện thoại
          </Nut>
        )}
      </The>
    </ManHinh>
  );
}
