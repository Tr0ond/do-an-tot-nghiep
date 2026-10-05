import { useEffect, useState } from 'react'
import { usePreventRemove } from '@react-navigation/native'
import { HopXacNhan } from '../components/HuanLuyen'

export function useBanNhap(navigation) {
  const [ban, datBan] = useState(null)
  const [goc, datGoc] = useState(null)
  const [roi, datRoi] = useState(null)
  const [choRoi, datChoRoi] = useState(false)
  const coSua = ban !== null && JSON.stringify(ban) !== JSON.stringify(goc)
  usePreventRemove(coSua && !choRoi, ({ data }) => datRoi(data.action))
  useEffect(() => {
    if (choRoi && roi) navigation.dispatch(roi)
  }, [choRoi, roi, navigation])
  function nhan(d) {
    const b = JSON.parse(JSON.stringify(d))
    datBan(b)
    datGoc(b)
  }
  return {
    ban,
    datBan,
    nhan,
    coSua,
    hopRoi: (
      <HopXacNhan
        visible={!!roi}
        tieuDe="Bỏ thay đổi chưa lưu?"
        moTa="Dữ liệu bạn vừa nhập chưa được lưu."
        nhanGui="Bỏ thay đổi"
        onDong={() => datRoi(null)}
        onGui={() => datChoRoi(true)}
      />
    ),
  }
}
