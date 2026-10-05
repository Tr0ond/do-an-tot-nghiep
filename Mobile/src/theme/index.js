import { useXemTruoc } from '../contexts/XemTruocContext'

export const font = {
  thuong: 'BeVietnamPro_400Regular',
  vua: 'BeVietnamPro_500Medium',
  damVua: 'BeVietnamPro_600SemiBold',
  dam: 'BeVietnamPro_700Bold',
  ratDam: 'BeVietnamPro_800ExtraBold',
}
const sang = {
  nen: '#F5F7F8',
  the: '#FFFFFF',
  chu: '#263335',
  chuPhu: '#5F6F72',
  vien: '#E2E8E9',
  nenPhu: '#F5F7F8',
  truongNhap: '#F5F7F8',
  chinh: '#087F75',
  trenChinh: '#FFFFFF',
  chinhNhat: '#E4F3F0',
  nangLuong: '#D6EF52',
  trenNangLuong: '#263335',
  xanhDuong: '#2864D7',
  xanhNhat: '#EAF0FC',
  vang: '#8A5D08',
  vangNhat: '#FBF0D6',
  loi: '#B3372B',
  loiNhat: '#FBE9E6',
  manChe: 'rgba(0, 0, 0, 0.50)',
}
const toi = {
  nen: '#111719',
  the: '#1A2326',
  chu: '#F0F5F5',
  chuPhu: '#98AAAD',
  vien: '#2B383B',
  nenPhu: '#111719',
  truongNhap: '#111719',
  chinh: '#79DCD0',
  trenChinh: '#0B1F1D',
  chinhNhat: '#1D3336',
  nangLuong: '#D6EF52',
  trenNangLuong: '#263335',
  xanhDuong: '#A8C5FF',
  xanhNhat: '#26354E',
  vang: '#E8C25A',
  vangNhat: '#382F18',
  loi: '#F2877A',
  loiNhat: '#3A2220',
  manChe: 'rgba(0, 0, 0, 0.50)',
}
export function useGiaoDien() {
  const { cheDoToi } = useXemTruoc()
  return { mau: cheDoToi ? toi : sang, cheDoToi }
}
