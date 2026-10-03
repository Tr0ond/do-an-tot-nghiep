import { beforeEach, expect, it, vi } from 'vitest'
import Page from '../src/components/KhungTroLy.vue'
import Mascot from '../src/components/MascotTroLy.vue'
import service from '../src/services/chatbotService'
vi.mock('../src/services/chatbotService', () => ({
  default: { gui: vi.fn(), tao: vi.fn(), taiChiTiet: vi.fn(), taiDanhSach: vi.fn() },
}))
vi.mock('../src/services/xacThucService', () => ({ default: {} }))
vi.mock('../src/services/baiTapService', () => ({ default: { urlMedia: vi.fn() } }))
const quota = { co_quyen: true, con_lai: 1, toi_da: 1, san_sang: true }
const response = (data = []) => ({ data, meta: { han_muc: quota, last_page: 1 } })
function trang(them = {}) {
  const p = {
    ...Page.data(),
    $nextTick: vi.fn().mockResolvedValue(),
    $refs: { oNhap: { focus: vi.fn() } },
    ...them,
  }
  for (const [k, f] of Object.entries(Page.methods)) p[k] = f.bind(p)
  for (const [k, f] of Object.entries(Page.computed))
    Object.defineProperty(p, k, { get: () => f.call(p) })
  return p
}
beforeEach(() => {
  vi.resetAllMocks()
  service.taiChiTiet.mockResolvedValue(response())
  service.taiDanhSach.mockResolvedValue(response())
})
it('chặn câu hỏi khi không có gói, hết lượt hoặc đang gửi; mặc định không dùng dữ liệu cá nhân', async () => {
  const p = trang({ hanMuc: { ...quota, co_quyen: false }, noiDung: 'Câu hỏi' })
  expect(p.caNhan).toBe(false)
  await p.gui()
  p.hanMuc = { ...quota, con_lai: 0 }
  await p.gui()
  expect(service.gui).not.toHaveBeenCalled()
})
it('mất phản hồi giữ UUID khi gửi lại và không gửi kép', async () => {
  const p = trang({ idChon: 4, hanMuc: quota, noiDung: ' Câu hỏi ' })
  let reject
  service.gui.mockReturnValueOnce(
    new Promise((_, r) => {
      reject = r
    }),
  )
  const a = p.gui()
  await p.gui()
  expect(service.gui).toHaveBeenCalledOnce()
  const payload = service.gui.mock.calls[0][1]
  expect(payload.noi_dung).toBe('Câu hỏi')
  reject(new Error('Mất mạng'))
  await a
  expect(p.yeuCauCho).toEqual(payload)
  service.gui.mockResolvedValue({ meta: { han_muc: quota } })
  await p.guiLai()
  expect(service.gui.mock.calls[1]).toEqual([4, payload])
  expect(p.yeuCauCho).toBeNull()
})
it('đổi hội thoại bỏ yêu cầu chờ cũ; refresh câu hỏi đã thành công bỏ pending', async () => {
  const p = trang({ idChon: 1, yeuCauCho: { client_request_id: 'uuid' } })
  await p.moHoiThoai(2)
  expect(p.yeuCauCho).toBeNull()
  p.yeuCauCho = { client_request_id: 'uuid' }
  service.taiChiTiet.mockResolvedValue(
    response([{ client_request_id: 'uuid', trang_thai: 'THANH_CONG' }]),
  )
  await p.moHoiThoai(2)
  expect(p.yeuCauCho).toBeNull()
})
it('lịch sử tải muộn không ghi vào hội thoại khác hoặc sau unmount', async () => {
  const p = trang({ idChon: 1 })
  let resolve
  service.taiChiTiet.mockReturnValueOnce(
    new Promise((r) => {
      resolve = r
    }),
  )
  const a = p.taiTinCu()
  p.cuocMoi()
  resolve(response([{ id: 99 }]))
  await a
  expect(p.tinNhan).toEqual([])
  expect(p.trangTin).toBe(1)
})
it('thử lại câu hỏi lịch sử giữ cả UUID và lựa chọn dữ liệu cá nhân', async () => {
  const p = trang({ idChon: 3 })
  service.gui.mockResolvedValue({ meta: { han_muc: quota } })
  p.thuLaiTin({ client_request_id: 'uuid', noi_dung: 'Câu hỏi cũ', dung_du_lieu_ca_nhan: true })
  expect(service.gui).toHaveBeenCalledWith(3, {
    client_request_id: 'uuid',
    noi_dung: 'Câu hỏi cũ',
    dung_du_lieu_ca_nhan: true,
  })
  await Promise.resolve()
})
it('mascot tôn trọng giảm chuyển động và tháo listener khi rời trang', () => {
  const p = {
    ...Mascot.data(),
    media: { removeEventListener: vi.fn() },
    capNhatChuyenDong: vi.fn(),
  }
  expect(p.giamChuyenDong).toBe(true)
  Mascot.methods.capNhatChuyenDong.call(p, { matches: false })
  expect(p.giamChuyenDong).toBe(false)
  Mascot.methods.capNhatChuyenDong.call(p, { matches: true })
  expect(p.giamChuyenDong).toBe(true)
  Mascot.beforeUnmount.call(p)
  expect(p.media.removeEventListener).toHaveBeenCalledWith('change', p.capNhatChuyenDong)
})
it('form giáo án soạn đúng thông số rồi chờ KH gửi, không tự gọi Gemini', async () => {
  const p = trang({ buoiTuan: 3, soTuan: 4, baiBuoi: 4, formGiaoAnMo: true })
  p.soanYeuCauGiaoAn()
  expect(p.noiDung).toBe('Tạo cho tôi giáo án 3 buổi/tuần trong 4 tuần, mỗi buổi 4 bài. ')
  expect(p.formGiaoAnMo).toBe(false)
  expect(service.gui).not.toHaveBeenCalled()
  p.soTuan = 20
  p.soanYeuCauGiaoAn()
  expect(p.loi).toContain('120 bài')
})
