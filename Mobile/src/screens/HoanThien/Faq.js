import { useState } from 'react'
import { RefreshControl } from 'react-native'
import { ManHinh, Chu, The, Nhan } from '../../components/GiaoDien'
import { TrangThaiTai, PhanTrang } from '../../components/HuanLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { hoanThienService as api } from '../../services/hoanThienService'

export default function Faq({ navigation }) {
  const [page, datPage] = useState(1)
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (s) => api.taiFaq(page, s),
    page,
  )
  return (
    <ManHinh
      tieuDe="Câu hỏi & tài liệu"
      bas
      onBack={() => navigation.goBack()}
      refreshControl={
        <RefreshControl refreshing={dangTai} onRefresh={taiLai} />
      }
    >
      <TrangThaiTai
        {...{ dangTai, loi, taiLai }}
        rong={duLieu && !duLieu.data.length}
        tieuDeRong="Chưa có tài liệu được xuất bản"
      />
      {duLieu?.data.map((d) => (
        <The key={d.id}>
          <Nhan>
            {{
              FAQ: 'Câu hỏi thường gặp',
              CHINH_SACH: 'Chính sách',
              HUONG_DAN: 'Hướng dẫn',
            }[d.loai] || 'Tài liệu'}
          </Nhan>
          <Chu size={19} dam="dam">
            {d.tieu_de}
          </Chu>
          <Chu selectable>{d.noi_dung}</Chu>
        </The>
      ))}
      <PhanTrang
        meta={duLieu && { ...duLieu.meta, current_page: duLieu.meta.page }}
        dangTai={dangTai}
        onChange={datPage}
      />
    </ManHinh>
  )
}
