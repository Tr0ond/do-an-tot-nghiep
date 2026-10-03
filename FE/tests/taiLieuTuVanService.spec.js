import { beforeEach, expect, it, vi } from 'vitest'
import service from '../src/services/taiLieuTuVanService'
import http from '../src/utils/http'
import xacThuc from '../src/services/xacThucService'
vi.mock('../src/utils/http', () => ({ default: { get: vi.fn(), post: vi.fn(), put: vi.fn() } }))
vi.mock('../src/services/xacThucService', () => ({ default: { layCsrfCookie: vi.fn() } }))
beforeEach(() => {
  vi.resetAllMocks()
  for (const f of Object.values(http)) f.mockResolvedValue({ data: {} })
})
it('FAQ công khai không yêu cầu CSRF, thao tác Admin gửi version qua CSRF', async () => {
  const signal = new AbortController().signal
  await service.faq({ page: 1 }, signal)
  expect(http.get).toHaveBeenCalledWith('/faq', { params: { page: 1 }, signal })
  expect(xacThuc.layCsrfCookie).not.toHaveBeenCalled()
  await service.thaoTac(4, 'ngung', 2)
  expect(http.post).toHaveBeenCalledWith('/admin/tai-lieu-tu-van/4/ngung', { phien_ban: 2 })
  expect(xacThuc.layCsrfCookie).toHaveBeenCalledOnce()
})
