import { beforeEach, expect, it, vi } from 'vitest'
import service from '../src/services/nhatKyTapService'
import http from '../src/utils/http'
import xacThuc from '../src/services/xacThucService'
vi.mock('../src/utils/http', () => ({ default: { get: vi.fn(), post: vi.fn(), put: vi.fn() } }))
vi.mock('../src/services/xacThucService', () => ({ default: { layCsrfCookie: vi.fn() } }))
beforeEach(() => {
  vi.resetAllMocks()
  for (const f of Object.values(http)) f.mockResolvedValue({ data: { status: true } })
})
it('đường dẫn theo actor, payload và CSRF tập trung ở service', async () => {
  const signal = new AbortController().signal
  await service.taiDanhSach(null, { tu_ngay: '2026-10-03' }, signal)
  expect(http.get).toHaveBeenLastCalledWith('/khach-hang/lich-tap', {
    params: { tu_ngay: '2026-10-03' },
    signal,
  })
  await service.taiDanhSach(2, {}, signal)
  expect(http.get).toHaveBeenLastCalledWith('/pt/hoc-vien/2/lich-tap', { params: {}, signal })
  await service.taiChiTiet(4, true, signal)
  expect(http.get).toHaveBeenLastCalledWith('/pt/lich-tap/4', { signal })
  await service.tao(2, { ngay_thu: 1 })
  expect(http.post).toHaveBeenLastCalledWith('/pt/hoc-vien/2/lich-tap', { ngay_thu: 1 })
  await service.luu(4, { updated_at: 'v1' })
  expect(http.put).toHaveBeenLastCalledWith('/khach-hang/lich-tap/4', { updated_at: 'v1' })
  await service.thaoTac(4, false, 'hoan-thanh', { updated_at: 'v2' })
  expect(http.post).toHaveBeenLastCalledWith('/khach-hang/lich-tap/4/hoan-thanh', {
    updated_at: 'v2',
  })
  expect(xacThuc.layCsrfCookie).toHaveBeenCalledTimes(3)
})
