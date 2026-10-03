import { beforeEach, expect, it, vi } from 'vitest'
import service from '../src/services/chatbotService'
import http from '../src/utils/http'
import xacThuc from '../src/services/xacThucService'
vi.mock('../src/utils/http', () => ({ default: { get: vi.fn(), post: vi.fn() } }))
vi.mock('../src/services/xacThucService', () => ({ default: { layCsrfCookie: vi.fn() } }))
beforeEach(() => {
  vi.resetAllMocks()
  for (const f of Object.values(http)) f.mockResolvedValue({ data: { status: true } })
})
it('gửi UUID/payload cố định qua CSRF, đủ thời gian chờ Backend', async () => {
  const d = { client_request_id: 'uuid', noi_dung: 'Tập thế nào?', dung_du_lieu_ca_nhan: false }
  await service.gui(1, d)
  expect(http.post).toHaveBeenCalledWith('/khach-hang/chatbot/hoi-thoai/1/tin-nhan', d, {
    timeout: 75000,
  })
  expect(xacThuc.layCsrfCookie).toHaveBeenCalledOnce()
  await service.tao('same-uuid')
  expect(http.post).toHaveBeenLastCalledWith('/khach-hang/chatbot/hoi-thoai', {
    client_request_id: 'same-uuid',
  })
  const signal = new AbortController().signal
  await service.taiChiTiet(2, 3, signal)
  expect(http.get).toHaveBeenLastCalledWith('/khach-hang/chatbot/hoi-thoai/2', {
    params: { page: 3 },
    signal,
  })
})
