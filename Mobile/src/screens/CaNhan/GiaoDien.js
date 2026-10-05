import { View, Pressable } from 'react-native'
import { ManHinh, Chu, BieuTuong, The } from '../../components/GiaoDien'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { useGiaoDien } from '../../theme'

export default function GiaoDien({ navigation }) {
  const { cheDoGiaoDien, datCheDoGiaoDien } = useXemTruoc()
  const { mau } = useGiaoDien()
  return (
    <ManHinh
      tieuDe="Giao diện"
      onBack={() => navigation.goBack()}
      contentStyle={{ gap: 12 }}
    >
      {[
        ['sang', 'Sáng', 'Nền sáng, dễ đọc ban ngày', 'Sun'],
        ['toi', 'Tối', 'Dịu mắt khi tập buổi tối', 'Moon'],
        [
          'heThong',
          'Theo hệ thống',
          'Tự đổi theo cài đặt Android',
          'Smartphone',
        ],
      ].map(([ma, nhan, moTa, icon]) => (
        <Pressable
          key={ma}
          accessibilityRole="radio"
          accessibilityState={{ checked: ma === cheDoGiaoDien }}
          onPress={() => datCheDoGiaoDien(ma)}
        >
          <The
            style={{
              flexDirection: 'row',
              gap: 12,
              alignItems: 'center',
              borderColor: ma === cheDoGiaoDien ? mau.chinh : mau.vien,
              boxShadow:
                ma === cheDoGiaoDien ? `0 0 0 1px ${mau.chinh}` : undefined,
            }}
          >
            <View
              style={{
                width: 44,
                height: 44,
                borderRadius: 12,
                backgroundColor: mau.chinhNhat,
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <BieuTuong ten={icon} size={20} />
            </View>
            <View style={{ flex: 1 }}>
              <Chu size={15} dam="dam">
                {nhan}
              </Chu>
              <Chu size={12.5} style={{ lineHeight: 24 }} color={mau.chuPhu}>
                {moTa}
              </Chu>
            </View>
            <View
              style={{
                width: 24,
                height: 24,
                borderRadius: 12,
                borderWidth: 1,
                borderColor: ma === cheDoGiaoDien ? mau.chinh : mau.vien,
                backgroundColor:
                  ma === cheDoGiaoDien ? mau.chinh : 'transparent',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              {ma === cheDoGiaoDien && (
                <BieuTuong
                  ten="Check"
                  size={14}
                  strokeWidth={3}
                  color={mau.trenChinh}
                />
              )}
            </View>
          </The>
        </Pressable>
      ))}
    </ManHinh>
  )
}
