import { useState } from 'react'
import { RefreshControl } from 'react-native'
import { Chu, ManHinh, The, Nut, NutIcon } from '../../components/GiaoDien'
import { TheDon } from '../../components/GoiTap'
import { Chip, NhomChip } from '../../components/FigmaElements'
import { TrangThaiTai, PhanTrang, HopXacNhan } from '../../components/HuanLuyen'
import { useDuLieu } from '../../hooks/useDuLieu'
import { useXemTruoc } from '../../contexts/XemTruocContext'
import { hoanThienService as api } from '../../services/hoanThienService'

export default function DonHang({ navigation }) {
  const [page, datPage] = useState(1)
  const [trangThai, datTrangThai] = useState('')
  const [goiMo, datGoiMo] = useState(false)
  const { goiDichVu } = useXemTruoc()
  const { duLieu, dangTai, loi, taiLai } = useDuLieu(
    (s) =>
      goiDichVu(async (t) => {
        const [don, goi] = await Promise.all([
          api.taiDon(t, page, s, trangThai),
          api.goiCuaToi(t, s),
        ])
        return { ...don, goi: goi.data }
      }),
    `${page}|${trangThai}`,
  )
  return (
    <ManHinh
      bas
      tieuDe="Lịch sử đơn hàng"
      contentStyle={{ paddingTop: 16, gap: 12 }}
      tacVu={
        <NutIcon
          icon="Package"
          size={20}
          nhan="Gói đang dùng & bảng giá"
          onPress={() => datGoiMo(true)}
          style={{
            width: 40,
            height: 40,
            borderWidth: 0,
            backgroundColor: 'transparent',
          }}
        />
      }
      onBack={() => navigation.goBack()}
      refreshControl={
        <RefreshControl refreshing={dangTai} onRefresh={taiLai} />
      }
    >
      <NhomChip>
        {[
          ['', 'Tất cả'],
          ['CHO_THANH_TOAN', 'Chờ thanh toán'],
          ['DANG_SU_DUNG', 'Thành công'],
          ['DA_HUY', 'Đã hủy'],
          ['HET_HAN_THANH_TOAN', 'Hết hạn thanh toán'],
          ['HET_HAN', 'Gói hết hạn'],
          ['CAN_DOI_SOAT', 'Cần đối soát'],
        ].map(([s, nhan]) => (
          <Chip
            key={s}
            chon={trangThai === s}
            onPress={() => {
              datTrangThai(s)
              datPage(1)
            }}
          >
            {nhan}
          </Chip>
        ))}
      </NhomChip>
      <TrangThaiTai {...{ dangTai, loi, taiLai }} />
      {duLieu && (
        <>
          <TrangThaiTai
            rong={!duLieu.data.length}
            tieuDeRong="Chưa có đơn hàng"
          />
          {duLieu.data.map((d) => (
            <TheDon
              key={d.id}
              don={d}
              gon
              onPress={() => navigation.navigate('ChiTietDon', { id: d.id })}
            />
          ))}
          <PhanTrang meta={duLieu.meta} dangTai={dangTai} onChange={datPage} />
          <HopXacNhan
            visible={goiMo}
            tieuDe="Gói đang sử dụng"
            onDong={() => datGoiMo(false)}
          >
            <The>
              <Chu dam="dam">Gói đang sử dụng</Chu>
              <Chu>
                {duLieu.goi.goi?.ten_goi || 'Bạn chưa có gói còn hiệu lực.'}
              </Chu>
              <Chu>
                PT phụ trách: {duLieu.goi.pt?.ho_ten || 'Chưa được phân công'}
              </Chu>
              {duLieu.goi.goi && (
                <Nut
                  loai="phu"
                  onPress={() => (
                    datGoiMo(false),
                    navigation.navigate('ChiTietDon', { id: duLieu.goi.goi.id })
                  )}
                >
                  Xem quyền lợi & thời hạn
                </Nut>
              )}
            </The>
            <Nut
              onPress={() => {
                datGoiMo(false)
                navigation.navigate('GoiTap')
              }}
            >
              Xem bảng giá
            </Nut>
          </HopXacNhan>
        </>
      )}
    </ManHinh>
  )
}
