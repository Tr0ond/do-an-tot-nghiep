import { beforeEach, describe, expect, it, vi } from 'vitest'
import Trang from '../src/views/ChiSoCoThe/index.vue'
import dichVu from '../src/services/chiSoCoTheService'
import { diemChiSo, noiDungChiSo, tinhBmi } from '../src/utils/chiSoCoThe'

vi.mock('../src/services/chiSoCoTheService', () => ({ default: { tai: vi.fn(), luu: vi.fn() } }))
vi.mock('../src/services/xacThucService', () => ({ default: {} }))

function trang(them = {}) {
  const p = {
    ...Trang.data(),
    $route: { meta: { vaiTro: 'KHACH_HANG' }, params: {} },
    $nextTick: vi.fn().mockResolvedValue(),
    $refs: {},
    ...them,
  }
  for (const [ten, ham] of Object.entries(Trang.methods)) p[ten] = ham.bind(p)
  for (const [ten, ham] of Object.entries(Trang.computed))
    Object.defineProperty(p, ten, { get: () => ham.call(p) })
  return p
}
const ban = {
  id: 1,
  ngay_ghi: '2026-10-04',
  can_nang_kg: 70,
  chieu_cao_cm: 175,
  bmi: 22.86,
  ghi_chu: null,
  updated_at: 'v1',
}
const ketQua = () => ({
  data: { moi_nhat: ban, cac_moc: [ban], lich_su: [ban] },
  meta: { last_page: 1, total: 1 },
})

describe('Chỉ số cơ thể', () => {
  beforeEach(() => vi.resetAllMocks())
  it('BMI xem trước theo kg/cm, thiếu hoặc ngoài giới hạn không giả thành 0', () => {
    expect(tinhBmi(70, 175)).toBe(22.86)
    expect(tinhBmi('', 175)).toBeNull()
    expect(tinhBmi(70, '')).toBeNull()
    expect(tinhBmi(501, 175)).toBeNull()
    expect(tinhBmi(70, 251)).toBeNull()
  })
  it('biểu đồ giữ khoảng cách ngày thực và bỏ BMI null', () => {
    const moc = [1, 2, 11].map((ngay, i) => ({
      id: i,
      ngay_ghi: `2026-10-${String(ngay).padStart(2, '0')}`,
      bmi: 20 + i,
    }))
    const k = diemChiSo(moc, 'bmi')
    expect(k.diem[1].x - k.diem[0].x).toBeCloseTo(55)
    expect(k.diem[2].x - k.diem[1].x).toBeCloseTo(495)
    expect(diemChiSo([{ ...moc[0], bmi: null }], 'bmi').diem).toEqual([])
    expect(diemChiSo([moc[0]], 'bmi').diem[0].x).toBe(320)
  })
  it('payload chỉ gửi ngày/cân/chiều cao/ghi chú, sửa thêm version', () => {
    const form = { ...ban, ghi_chu: '  Ghi chú  ', bmi: 999, vong_eo_cm: 80, khach_hang_id: 99 }
    expect(noiDungChiSo(form, null)).toEqual({
      ngay_ghi: ban.ngay_ghi,
      can_nang_kg: 70,
      chieu_cao_cm: 175,
      ghi_chu: 'Ghi chú',
    })
    expect(noiDungChiSo(form, 1).updated_at).toBe('v1')
  })
  it('tải KH và gợi ý chiều cao, không tự điền cân nặng', async () => {
    dichVu.tai.mockResolvedValue(ketQua())
    const p = trang()
    await p.taiDuLieu()
    expect(dichVu.tai).toHaveBeenCalledWith(
      null,
      { so_ngay: 30, page: 1, den_ngay: p.denNgay },
      expect.any(AbortSignal),
    )
    expect(p.form.chieu_cao_cm).toBe(175)
    expect(p.form.can_nang_kg).toBe('')
    expect(p.so(null)).toBe('—')
    expect(p.thayDoi(null)).toBe('—')
  })
  it('PT tải đúng học viên và không được gửi thao tác ghi', async () => {
    dichVu.tai.mockResolvedValue(ketQua())
    const p = trang({ $route: { meta: { vaiTro: 'HUAN_LUYEN_VIEN' }, params: { khachId: '9' } } })
    await p.taiDuLieu()
    expect(dichVu.tai.mock.calls[0][0]).toBe('9')
    await p.luu()
    await p.moSua(ban)
    expect(dichVu.luu).not.toHaveBeenCalled()
    expect(p.suaId).toBeNull()
  })
  it('sửa trên bản sao, giữ phiên bản và chiều cao của ngày đó', async () => {
    const p = trang({ duLieu: { moi_nhat: { chieu_cao_cm: 180 } } })
    await p.moSua(ban)
    p.form.can_nang_kg = 72
    expect(ban.can_nang_kg).toBe(70)
    expect(p.form.chieu_cao_cm).toBe(175)
    expect(p.form.updated_at).toBe('v1')
    p.ghiMoi()
    expect(p.form.chieu_cao_cm).toBe(180)
    expect(p.form.can_nang_kg).toBe('')
  })
  it('sửa chiều cao gần nhất cập nhật gợi ý cho lần ghi mới', async () => {
    const p = trang({
      form: { ...ban, chieu_cao_cm: 180, ghi_chu: '' },
      suaId: 1,
      duLieu: ketQua().data,
    })
    dichVu.luu.mockResolvedValue({ message: 'Đã lưu' })
    dichVu.tai.mockResolvedValue({
      ...ketQua(),
      data: { ...ketQua().data, moi_nhat: { ...ban, chieu_cao_cm: 180 } },
    })
    await p.luu()
    expect(p.form.chieu_cao_cm).toBe(180)
  })
  it('double-submit bị chặn và refresh không giả kết quả thành công khi lỗi', async () => {
    let tra
    dichVu.luu.mockImplementation(
      () =>
        new Promise((r) => {
          tra = r
        }),
    )
    dichVu.tai.mockResolvedValue(ketQua())
    const p = trang({ form: { ...ban, ghi_chu: '' }, suaId: 1 })
    const lan1 = p.luu()
    await p.luu()
    expect(dichVu.luu).toHaveBeenCalledTimes(1)
    tra({ message: 'Đã lưu' })
    await lan1
    expect(p.suaId).toBeNull()
    expect(p.thongBao).toBe('Đã lưu')
    expect(p.dangLuu).toBe(false)
  })
  it('409 và 422 giữ bản nhập để KH xử lý', async () => {
    const p = trang({ form: { ...ban, ghi_chu: '' }, suaId: 1 })
    dichVu.luu.mockRejectedValue({ response: { status: 409, data: { message: 'Hãy tải lại' } } })
    await p.luu()
    expect(p.loiLuu).toBe('Hãy tải lại')
    expect(p.suaId).toBe(1)
    dichVu.luu.mockRejectedValue({
      response: {
        status: 422,
        data: { message: 'Chưa hợp lệ', errors: { can_nang_kg: ['Nhập cân nặng'] } },
      },
    })
    await p.luu()
    expect(p.loiTruong.can_nang_kg).toEqual(['Nhập cân nặng'])
    expect(p.form.can_nang_kg).toBe(70)
  })
  it('xóa dữ liệu đã tải khi quyền PT bị thu hồi', async () => {
    dichVu.tai.mockRejectedValue({ response: { status: 404, data: { message: 'Không tìm thấy' } } })
    const p = trang({ duLieu: ketQua().data })
    await p.taiDuLieu()
    expect(p.duLieu).toBeNull()
    expect(p.loiTai).toBe('Không tìm thấy')
  })
  it('response cũ sau đổi khoảng không ghi đè request mới', async () => {
    let cu
    dichVu.tai
      .mockImplementationOnce(
        () =>
          new Promise((r) => {
            cu = r
          }),
      )
      .mockResolvedValueOnce({ ...ketQua(), data: { ...ketQua().data, so_lan_ghi: 7 } })
    const p = trang()
    const lan1 = p.taiDuLieu()
    p.soNgay = 7
    await p.taiDuLieu()
    cu({ ...ketQua(), data: { ...ketQua().data, so_lan_ghi: 30 } })
    await lan1
    expect(p.duLieu.so_lan_ghi).toBe(7)
  })
})
