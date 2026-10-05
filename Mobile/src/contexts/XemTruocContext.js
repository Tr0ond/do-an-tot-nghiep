import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useRef,
  useState,
} from 'react'
import { AppState, useColorScheme } from 'react-native'
import { taoHoSoMau } from '../data/minhHoa'
import { taiKhoanService } from '../services/taiKhoanService'
import { khoPhien } from '../services/khoPhien'
import { taoQuanLyPhien } from '../services/quanLyPhien'
import { chuyenHoSo } from '../utils/hoSo'

const XemTruocContext = createContext(null)

export function XemTruocProvider({ children }) {
  const [vaiTroXem, datVaiTroXem] = useState(null)
  const [cheDoGiaoDien, datCheDoGiaoDien] = useState('sang')
  const heThong = useColorScheme()
  const cheDoToi =
    cheDoGiaoDien === 'toi' ||
    (cheDoGiaoDien === 'heThong' && heThong === 'dark')
  const datCheDoToi = (giaTri) => datCheDoGiaoDien(giaTri ? 'toi' : 'sang')
  const [hoSoMau, datHoSoMau] = useState(null)
  const [thongBao, datThongBao] = useState(null)
  const [phien, datPhien] = useState({
    taiKhoan: null,
    dangKhoiPhuc: true,
    dangThaoTac: false,
    loiPhien: '',
    loiKhoiPhuc: '',
  })
  const quanLy = useRef(null)
  if (!quanLy.current)
    quanLy.current = taoQuanLyPhien({
      kho: khoPhien,
      api: taiKhoanService,
      onChange: (duLieu) => datPhien((cu) => ({ ...cu, ...duLieu })),
    })
  useEffect(() => {
    quanLy.current
      .khoiPhuc()
      .catch((loi) =>
        datThongBao({ tieuDe: 'Không đọc được phiên', noiDung: loi.message }),
      )
  }, [])
  useEffect(() => {
    const listener = AppState.addEventListener('change', (trangThai) => {
      if (trangThai === 'active' && phien.taiKhoan)
        quanLy.current.taiHoSo().catch((loi) => {
          if (loi.status !== 401)
            datThongBao({
              tieuDe: 'Chưa cập nhật được hồ sơ',
              noiDung: loi.message,
            })
        })
    })
    return () => listener.remove()
  }, [phien.taiKhoan?.id])
  const dangXemTruoc = !!vaiTroXem
  const vaiTro = phien.taiKhoan?.vai_tro || vaiTroXem
  const hoSo = dangXemTruoc ? hoSoMau : chuyenHoSo(phien.taiKhoan)
  useEffect(() => {
    if (phien.taiKhoan) {
      datVaiTroXem(null)
      datHoSoMau(null)
    }
  }, [phien.taiKhoan?.id])
  function moBanXem(vaiTro) {
    // Chọn bản mẫu chỉ đổi giao diện, không tạo phiên đăng nhập hoặc cấp quyền.
    datHoSoMau(taoHoSoMau(vaiTro))
    datThongBao(null)
    datVaiTroXem(vaiTro)
  }
  function thoatBanXem() {
    datVaiTroXem(null)
    datHoSoMau(null)
    datThongBao(null)
  }
  const goiDichVu = useCallback(
    (hanhDong) => quanLy.current.goiDichVu(hanhDong),
    [],
  )
  return (
    <XemTruocContext.Provider
      value={{
        vaiTro,
        dangXemTruoc,
        ...phien,
        goiDichVu,
        dangNhap: (duLieu) => quanLy.current.dangNhap(duLieu),
        dangXuat: () => quanLy.current.dangXuat(),
        khoiPhuc: () => quanLy.current.khoiPhuc(),
        taiHoSo: async () => chuyenHoSo(await quanLy.current.taiHoSo()),
        luuHoSo: async (duLieu) =>
          chuyenHoSo(await quanLy.current.luuHoSo(duLieu)),
        cheDoToi,
        datCheDoToi,
        cheDoGiaoDien,
        datCheDoGiaoDien,
        hoSo,
        datHoSo: datHoSoMau,
        moBanXem,
        thoatBanXem,
        thongBao,
        datThongBao,
      }}
    >
      {children}
    </XemTruocContext.Provider>
  )
}

export function useXemTruoc() {
  const nguCanh = useContext(XemTruocContext)
  if (!nguCanh) throw new Error('Thiếu XemTruocProvider')
  return nguCanh
}
