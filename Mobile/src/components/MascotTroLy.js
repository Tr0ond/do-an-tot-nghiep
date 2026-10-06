import { Image } from 'expo-image'
import { useChuyenDong } from '../hooks/useChuyenDong'

const gif = require('../../assets/mascot/fitforge-ai-gundam-slow.gif')
const anhTinh = require('../../assets/mascot/fitforge-ai-gundam-slow-still.png')

export default function MascotTroLy({ size = 40, chuyenDong = true }) {
  const { choPhep } = useChuyenDong()
  const phat = chuyenDong && choPhep
  return (
    <Image
      source={phat ? gif : anhTinh}
      autoplay={phat}
      contentFit="contain"
      style={{ width: size, height: size }}
      accessibilityLabel="FitForge AI — robot tập luyện"
      accessible
    />
  )
}
