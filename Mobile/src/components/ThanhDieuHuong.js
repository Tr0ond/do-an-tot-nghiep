import { useEffect, useState } from 'react'
import { Keyboard, Pressable, View } from 'react-native'
import { useSafeAreaInsets } from 'react-native-safe-area-context'
import { BieuTuong, Chu } from './GiaoDien'
import { useGiaoDien } from '../theme'
import { useXemTruoc } from '../contexts/XemTruocContext'

export default function ThanhDieuHuong({ state, descriptors, navigation }) {
  const { mau } = useGiaoDien()
  const { vaiTro } = useXemTruoc()
  const inset = useSafeAreaInsets()
  const [banPhim, datBanPhim] = useState(false)
  useEffect(() => {
    const mo = Keyboard.addListener('keyboardDidShow', () => datBanPhim(true))
    const dong = Keyboard.addListener('keyboardDidHide', () =>
      datBanPhim(false),
    )
    return () => {
      mo.remove()
      dong.remove()
    }
  }, [])
  if (banPhim) return null
  const nhan = {
    TongQuan: ['Home', 'Tổng quan'],
    Lich: ['CalendarDays', vaiTro === 'KHACH_HANG' ? 'Lịch tập' : 'Lịch dạy'],
    GiaoAn: ['ClipboardList', 'Giáo án'],
    HocVien: ['Users', 'Học viên'],
    TinNhan: ['MessageCircle', 'Tin nhắn'],
    CaNhan: ['User', 'Hồ sơ'],
  }
  return (
    <View
      style={{
        flexDirection: 'row',
        height: 72 + inset.bottom,
        paddingBottom: inset.bottom,
        paddingHorizontal: 4,
        paddingTop: 8,
        backgroundColor: mau.the,
        borderTopWidth: 1,
        borderTopColor: mau.vien,
      }}
    >
      {state.routes.map((route, i) => {
        const chon = state.index === i
        const [icon, tieuDe] = nhan[route.name]
        const badge = descriptors[route.key].options.tabBarBadge
        return (
          <Pressable
            key={route.key}
            accessibilityRole="tab"
            accessibilityLabel={tieuDe}
            accessibilityState={{ selected: chon }}
            onPress={() => {
              const event = navigation.emit({
                type: 'tabPress',
                target: route.key,
                canPreventDefault: true,
              })
              if (!chon && !event.defaultPrevented)
                navigation.navigate(route.name, route.params)
            }}
            onLongPress={() =>
              navigation.emit({ type: 'tabLongPress', target: route.key })
            }
            style={{ flex: 1, alignItems: 'center', gap: 4 }}
          >
            <View
              style={{
                height: 32,
                width: 56,
                borderRadius: 16,
                overflow: 'hidden',
                backgroundColor: chon ? mau.chinhNhat : 'transparent',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <BieuTuong
                ten={icon}
                size={21}
                strokeWidth={chon ? 2.4 : 2}
                color={chon ? mau.chinh : mau.chuPhu}
              />
              {badge != null && (
                <View
                  style={{
                    position: 'absolute',
                    right: 4,
                    top: -3,
                    borderRadius: 999,
                    paddingHorizontal: 4,
                    backgroundColor: mau.nangLuong,
                  }}
                >
                  <Chu size={10} dam="dam" color={mau.trenNangLuong}>
                    {badge}
                  </Chu>
                </View>
              )}
            </View>
            <Chu size={11} dam="damVua" color={chon ? mau.chinh : mau.chuPhu}>
              {tieuDe}
            </Chu>
          </Pressable>
        )
      })}
    </View>
  )
}
