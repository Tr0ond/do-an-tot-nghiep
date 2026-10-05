import { RefreshControl, View } from 'react-native'
import { ManHinh } from '../components/GiaoDien'
import { TrangThaiTai } from '../components/HuanLuyen'
import {
  DauTongQuan,
  TongQuanKhach,
  TongQuanHlv,
} from '../components/TongQuanFigma'
import { useXemTruoc } from '../contexts/XemTruocContext'
import { useTraoDoi } from '../contexts/TraoDoiContext'
import { useDuLieu } from '../hooks/useDuLieu'
import { huanLuyenService as api } from '../services/huanLuyenService'

export default function TongQuanTaiKhoan({ navigation }) {
  const { hoSo, vaiTro, goiDichVu } = useXemTruoc()
  const { soThongBao } = useTraoDoi()
  const laKhach = vaiTro === 'KHACH_HANG'
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (signal) => goiDichVu((token) => api.taiTongQuan(token, vaiTro, signal)),
    vaiTro,
  )
  const d = laKhach ? duLieu?.data?.hanh_trinh : duLieu?.data?.huan_luyen
  const cho = useDuLieu(
    (s) =>
      laKhach
        ? Promise.resolve(null)
        : goiDichVu((t) =>
            api.taiLich(t, vaiTro, { trang_thai: 'CHO_XAC_NHAN', page: 1 }, s),
          ),
    vaiTro,
  )
  return (
    <ManHinh
      contentStyle={{ paddingTop: 0, gap: 0, paddingBottom: 24 }}
      refreshControl={
        <RefreshControl refreshing={dangTai} onRefresh={taiLai} />
      }
    >
      <DauTongQuan
        hoSo={hoSo}
        laKhach={laKhach}
        homNay={d?.hom_nay || new Date().toISOString().slice(0, 10)}
        soThongBao={soThongBao}
        onThongBao={() => navigation.navigate('ThongBao')}
      />
      <TrangThaiTai {...{ dangTai, loi, taiLai }} />
      {d &&
        (laKhach ? (
          <TongQuanKhach d={d} navigation={navigation} />
        ) : (
          <TongQuanHlv d={d} cho={cho} navigation={navigation} />
        ))}
    </ManHinh>
  )
}
