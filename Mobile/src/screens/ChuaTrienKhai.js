import { View } from 'react-native'
import { Chu, ManHinh, Nut, BieuTuong, The, Nhan } from '../components/GiaoDien'
import { useGiaoDien } from '../theme'

export default function ChuaTrienKhai({ navigation, route }) {
  const { mau } = useGiaoDien()
  const tieuDe = route.params?.tieuDe || route.name
  return (
    <ManHinh
      tieuDe={tieuDe}
      onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
    >
      <The
        style={{ marginTop: 32, padding: 24, alignItems: 'center', gap: 20 }}
      >
        <View
          style={{
            width: 80,
            height: 80,
            borderRadius: 20,
            backgroundColor: mau.chinhNhat,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <BieuTuong ten={route.params?.icon || 'layout'} size={34} />
        </View>
        <Nhan icon="clock">Sẽ triển khai ở bước tiếp theo</Nhan>
        <Chu size={21} dam="dam" style={{ textAlign: 'center' }}>
          {tieuDe}
        </Chu>
        <Chu color={mau.chuPhu} style={{ textAlign: 'center' }}>
          Màn hình này chưa được dựng trong đợt giao diện đầu tiên. Bạn có thể
          tiếp tục xem Tổng quan và Cá nhân.
        </Chu>
        <Nut
          loai="phu"
          style={{ alignSelf: 'stretch' }}
          onPress={() =>
            navigation.canGoBack()
              ? navigation.goBack()
              : navigation.navigate('TongQuan')
          }
        >
          Quay lại
        </Nut>
      </The>
    </ManHinh>
  )
}
