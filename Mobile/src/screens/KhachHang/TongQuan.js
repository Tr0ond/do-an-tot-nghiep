import { ManHinh } from '../../components/GiaoDien'
import { DauTongQuan, TongQuanKhach } from '../../components/TongQuanFigma'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { homNay, doiNgay } from '../../utils/lich'

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
    goi: {
      ten: 'PT đồng hành',
      so_buoi_con_lai: 8,
      so_buoi_pt: 12,
      het_han_luc: doiNgay(hom, 18),
    },
    pt: { ho_ten: 'Nguyễn Minh Quân' },
    buoi_thang_nay: 2,
    lich_sap_toi: [
      {
        id: 'xem-pt',
        loai: 'PT',
        bat_dau_luc: `${hom}T18:00:00+07:00`,
        ten: 'PT Nguyễn Minh Quân',
        trang_thai: 'DA_XAC_NHAN',
      },
      {
        id: 'xem-tu',
        loai: 'TU_TAP',
        ngay: doiNgay(hom, 1),
        ten: 'Ngực & Tay sau',
      },
    ],
    chi_so: { moi_nhat: { can_nang_kg: 68.5 }, thay_doi_can_nang_kg: 0 },
    giao_an: { ten: 'Tăng sức mạnh', so_ngay_tap: 4, so_bai: 16 },
  }
  return (
    <ManHinh contentStyle={{ paddingTop: 0, gap: 0, paddingBottom: 24 }}>
      <DauTongQuan
        hoSo={hoSo}
        laKhach={true}
        homNay={hom}
        soThongBao={1}
        onThongBao={() => mo.navigate('ThongBao')}
      />
      <TongQuanKhach d={d} navigation={mo} />
    </ManHinh>
  )
}
