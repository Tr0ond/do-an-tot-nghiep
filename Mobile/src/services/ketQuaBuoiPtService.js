import { goiApi } from '../utils/http'

const duongDan = (id) => `/pt/lich-hen/${encodeURIComponent(id)}/ket-qua`

export const ketQuaBuoiPtService = {
  tai: (token, vaiTro, id, signal) =>
    goiApi(
      `/${vaiTro === 'KHACH_HANG' ? 'khach-hang' : 'pt'}/lich-hen/${encodeURIComponent(id)}/ket-qua`,
      { token, signal, dayDu: true },
    ),
  luu: (token, id, duLieu) =>
    goiApi(duongDan(id), { token, duLieu, method: 'PUT', dayDu: true }),
  chot: (token, id, duLieu) =>
    goiApi(`${duongDan(id)}/chot`, {
      token,
      duLieu,
      method: 'POST',
      dayDu: true,
    }),
}
