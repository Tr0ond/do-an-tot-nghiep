import { beforeEach, describe, expect, it, vi } from 'vitest'
import chatService from '../src/services/chatService'
import http from '../src/utils/http'
import xacThucService from '../src/services/xacThucService'

vi.mock('../src/utils/http', () => ({ default: { post: vi.fn() } }))
vi.mock('../src/services/xacThucService', () => ({
  default: { layCsrfCookie: vi.fn() },
}))

describe('Payload gửi tin qua HTTP', () => {
  const duLieu = { client_message_id: 'cdfa72c6-6fb8-4a4c-89c8-628c6c84795e', noi_dung: 'Xin chào' }

  beforeEach(() => {
    vi.resetAllMocks()
    xacThucService.layCsrfCookie.mockResolvedValue()
    http.post.mockResolvedValue({ data: { data: { id: 9 } } })
  })

  it('gửi chữ bỏ trường ảnh rỗng, không thay đổi dữ liệu gốc', async () => {
    const payload = { ...duLieu, anh: [] }
    const signal = new AbortController().signal
    await expect(chatService.gui(1, payload, signal)).resolves.toEqual({ data: { id: 9 } })
    expect(xacThucService.layCsrfCookie).toHaveBeenCalledOnce()
    expect(http.post).toHaveBeenCalledWith('/hoi-thoai/1/tin-nhan', duLieu, {
      signal,
      timeout: 60000,
    })
    expect(payload.anh).toEqual([])
  })

  it('retry sau lỗi HTTP giữ UUID và nội dung, không thêm mảng ảnh rỗng', async () => {
    http.post.mockRejectedValueOnce(new Error('Mất kết nối'))
    const payload = { ...duLieu, anh: [] }
    await expect(chatService.gui(1, payload)).rejects.toThrow('Mất kết nối')
    await chatService.gui(1, payload)
    expect(http.post.mock.calls.map((call) => call[1])).toEqual([duLieu, duLieu])
  })

  it('gửi ảnh vẫn dùng multipart với đúng thứ tự tệp và chú thích tùy chọn', async () => {
    const anh = [
      new File(['anh-1'], 'mot.png', { type: 'image/png' }),
      new File(['anh-2'], 'hai.jpg', { type: 'image/jpeg' }),
    ]
    await chatService.gui(1, { client_message_id: duLieu.client_message_id, anh })
    const payload = http.post.mock.calls[0][1]
    expect(payload).toBeInstanceOf(FormData)
    expect(payload.get('client_message_id')).toBe(duLieu.client_message_id)
    expect(payload.get('noi_dung')).toBe('')
    expect(payload.getAll('anh[]').map((tep) => tep.name)).toEqual(['mot.png', 'hai.jpg'])
    expect(await payload.getAll('anh[]')[0].text()).toBe('anh-1')
    await chatService.gui(1, { ...duLieu, anh })
    expect(http.post.mock.calls[1][1].get('noi_dung')).toBe(duLieu.noi_dung)
  })
})
