import { ManHinh } from '../../components/GiaoDien'
import { DauTongQuan, TongQuanHlv } from '../../components/TongQuanFigma'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { homNay, doiNgay } from '../../utils/lich'
import { hocVienMau } from '../../data/minhHoa'
export default function TongQuan({ navigation }) {
  const { hoSo } = useXemTruoc()
  const hom = homNay()
  const mo = {
    navigate: (ten, params) =>
      ['GiaoAn', 'TinNhan', 'HocVien'].includes(ten)
        ? navigation.navigate(ten)
        : navigation.navigate('ChuaTrienKhai', {
            tieuDe: 'Bản xem trước',
            icon: 'info',
          }),
  }
  const d = {
    so_buoi_hom_nay: 3,
    can_xu_ly: {
      cho_dat_lich: 1,
      cho_ket_qua: 1,
      lich_dat: { id: 'xem-cho' },
      lich_ket_qua: { id: 'xem-kq' },
    },
    lich_hom_nay: [
      {
        id: 'xem-lich',
        bat_dau_luc: `${hom}T18:00:00+07:00`,
        ket_thuc_luc: `${hom}T19:00:00+07:00`,
        khach_hang: 'Nguyễn Văn Minh',
        trang_thai: 'DA_XAC_NHAN',
      },
    ],
    hom_nay: hom,
    hoc_vien: hocVienMau,
    tuan: { hoan_thanh: 8, da_len_lich: 12, vang_mat: 0, da_huy: 0 },
    khung_gio: { con_trong: 3 },
  }
  return (
    <ManHinh contentStyle={{ paddingTop: 0, gap: 0, paddingBottom: 24 }}>
      <DauTongQuan
        hoSo={hoSo}
        laKhach={false}
        homNay={hom}
        soThongBao={1}
        onThongBao={() => mo.navigate('ThongBao')}
      />
      <TongQuanHlv d={d} navigation={mo} />
    </ManHinh>
  )
}
