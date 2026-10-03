import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useChatStore } from '../src/stores/chat'
import { useXacThucStore } from '../src/stores/xacThuc'
import { ketNoiChat } from '../src/services/chatRealtime'
import chatService from '../src/services/chatService'
import TinNhan from '../src/views/TinNhan/index.vue'
import AnhTinNhan from '../src/components/AnhTinNhan.vue'
import { hopNhatTin, kiemTraAnhChat } from '../src/utils/chat'

vi.mock('../src/services/chatRealtime', () => ({ ketNoiChat: vi.fn() }))
vi.mock('../src/services/xacThucService', () => ({ default: { taiTaiKhoan: vi.fn() } }))
vi.mock('../src/services/chatService', () => ({
  default: { taiHoiThoai: vi.fn(), taiTin: vi.fn(), gui: vi.fn(), daDoc: vi.fn(), taiAnh: vi.fn() },
}))

const hoi = { id: 1, co_the_gui: true, cursor_da_doc: 0 }
function tin(id, body = 'Nội dung') {
  return { id, hoi_thoai_id: 1, nguoi_gui_id: 2, client_message_id: `uuid-${id}`, noi_dung: body }
}
function choKetQua() {
  let resolve
  const promise = new Promise((r) => (resolve = r))
  return { resolve, promise }
}
function taoTrang() {
  const trang = {
    ...TinNhan.data(),
    $route: { params: { id: '1' } },
    $router: { replace: vi.fn() },
    $refs: {},
    $nextTick: () => Promise.resolve(),
  }
  for (const [ten, ham] of Object.entries(TinNhan.methods)) trang[ten] = ham.bind(trang)
  for (const [ten, ham] of Object.entries(TinNhan.computed))
    Object.defineProperty(trang, ten, { get: () => ham.call(trang) })
  trang.hoiThoai = { ...hoi }
  trang.boHuyTin = new AbortController()
  return trang
}

describe('Chat: thứ tự HTTP, realtime, retry và thu hồi', () => {
  let chat
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useFakeTimers()
    vi.stubGlobal('window', new EventTarget())
    vi.stubGlobal('document', Object.assign(new EventTarget(), { hidden: false }))
    setActivePinia(createPinia())
    chat = useChatStore()
    useXacThucStore().taiKhoan = { id: 1, vai_tro: 'KHACH_HANG' }
    chatService.taiHoiThoai.mockResolvedValue({ data: [], meta: { so_chua_doc: 0 } })
    chatService.taiTin.mockResolvedValue({ data: { hoi_thoai: hoi, tin_nhan: [], con_tin: false } })
    chatService.daDoc.mockResolvedValue({ data: { cursor_da_doc: 9 } })
  })
  afterEach(() => {
    chat.dongKetNoi()
    vi.useRealTimers()
    vi.unstubAllGlobals()
  })

  it('HTTP và realtime cùng UUID chỉ hiện một bản, xóa trạng thái lỗi cũ', () => {
    const pending = { ...tin(9), id: undefined, trang_thai: 'loi', loi: 'Mất mạng' }
    for (const ds of [hopNhatTin([pending], [tin(9)]), hopNhatTin([tin(9)], [pending])]) {
      expect(ds).toEqual([tin(9)])
    }
    expect(hopNhatTin([tin(1)], [{ ...tin(1), hoi_thoai_id: 2 }])).toHaveLength(2)
  })

  it('retry giữ UUID và nội dung, không tạo tin thứ hai', async () => {
    const trang = taoTrang()
    trang.noiDung = 'Xin chào PT'
    chatService.gui
      .mockRejectedValueOnce(new Error('Mất mạng'))
      .mockImplementationOnce((id, p) =>
        Promise.resolve({ data: { ...p, id: 9, hoi_thoai_id: id, nguoi_gui_id: 1 } }),
      )
    await trang.guiTin()
    const loi = trang.tinCho[0]
    expect(loi.trang_thai).toBe('loi')
    await trang.guiTin(loi)
    expect(chatService.gui.mock.calls[0].slice(0, 2)).toEqual(
      chatService.gui.mock.calls[1].slice(0, 2),
    )
    expect(trang.tinCho).toHaveLength(0)
    expect(trang.tinNhan).toHaveLength(1)
  })

  it('HTTP gửi trả trước không bỏ qua tin đối phương có ID thấp hơn', async () => {
    const trang = taoTrang()
    trang.cursorDongBo = 5
    trang.noiDung = 'Tin của tôi'
    chatService.gui.mockResolvedValue({ data: { ...tin(9), nguoi_gui_id: 1 } })
    chatService.taiTin.mockResolvedValue({
      data: { hoi_thoai: hoi, tin_nhan: [tin(7), { ...tin(9), nguoi_gui_id: 1 }], con_tin: false },
    })
    await trang.guiTin()
    await Promise.resolve()
    expect(chatService.taiTin.mock.calls[0][1]).toEqual({ after_id: 5 })
    expect(trang.tinNhan.map((t) => t.id)).toEqual([7, 9])
  })

  it('tải bù nhiều trang và hint tới khi request đang chạy', async () => {
    const trang = taoTrang()
    const cho = choKetQua()
    chatService.taiTin
      .mockReturnValueOnce(cho.promise)
      .mockResolvedValueOnce({ data: { hoi_thoai: hoi, tin_nhan: [tin(2)], con_tin: false } })
    const lanDau = trang.taiTinMoi()
    await trang.taiTinMoi()
    cho.resolve({ data: { hoi_thoai: hoi, tin_nhan: [tin(1)], con_tin: true } })
    await lanDau
    expect(trang.tinNhan.map((t) => t.id)).toEqual([1, 2])
    expect(chatService.taiTin.mock.calls.map((c) => c[1].after_id)).toEqual([0, 1])
  })

  it('đổi hội thoại bỏ response cũ, hủy request khi rời trang', async () => {
    const trang = taoTrang()
    const cho = choKetQua()
    chatService.taiTin.mockReturnValueOnce(cho.promise)
    const cu = trang.taiTinDau()
    trang.$route.params.id = '2'
    await trang.taiTinDau()
    cho.resolve({ data: { hoi_thoai: hoi, tin_nhan: [tin(1)], con_tin: false } })
    await cu
    expect(trang.tinNhan).toEqual([])
    TinNhan.beforeUnmount.call(trang)
    expect(trang.boHuyTin.signal.aborted).toBe(true)
  })

  it('PT bị thu hồi xóa nội dung và bản nháp, trở lại danh sách', () => {
    const trang = taoTrang()
    trang.tinNhan = [tin(1)]
    trang.tinCho = [tin(2)]
    trang.noiDung = 'Bản nháp'
    trang.xuLyQuyen({ response: { status: 404 } })
    expect(trang.hoiThoai).toBeNull()
    expect(trang.tinNhan).toEqual([])
    expect(trang.tinCho).toEqual([])
    expect(trang.noiDung).toBe('')
    expect(trang.$router.replace).toHaveBeenCalledWith('/khach-hang/tin-nhan')
  })

  it('đăng xuất bỏ kết quả gửi đang chờ, không khôi phục dữ liệu riêng', async () => {
    const trang = taoTrang()
    const cho = choKetQua()
    trang.noiDung = 'Đang gửi'
    chatService.gui.mockReturnValue(cho.promise)
    const gui = trang.guiTin()
    await Promise.resolve()
    chat.dongKetNoi()
    TinNhan.watch['xacThuc.taiKhoan'].call(trang, null)
    cho.resolve({ data: tin(9) })
    await gui
    expect(trang.tinNhan).toEqual([])
    expect(trang.tinCho).toEqual([])
  })

  it('không đánh dấu đọc khi tab ẩn hoặc đang xem tin cũ', async () => {
    const trang = taoTrang()
    trang.tinNhan = [tin(9)]
    document.hidden = true
    await trang.ghiDaDoc()
    document.hidden = false
    trang.ganCuoi = false
    await trang.ghiDaDoc()
    expect(chatService.daDoc).not.toHaveBeenCalled()
    trang.ganCuoi = true
    await trang.ghiDaDoc()
    expect(chatService.daDoc).toHaveBeenCalledWith(1, 9)
  })

  it('Enter khi đang gõ bằng IME không gửi tin ngoài ý muốn', () => {
    const trang = taoTrang()
    trang.guiTin = vi.fn()
    const preventDefault = vi.fn()
    trang.guiBangEnter({ isComposing: true, preventDefault })
    expect(preventDefault).not.toHaveBeenCalled()
    expect(trang.guiTin).not.toHaveBeenCalled()
    trang.guiBangEnter({ isComposing: false, preventDefault })
    expect(trang.guiTin).toHaveBeenCalledOnce()
  })

  it('reconnect, online và tab hiện lại đều tải bù; logout tháo listener/socket', async () => {
    const huy = vi.fn()
    ketNoiChat.mockReturnValue(huy)
    chat.ketNoi(1)
    const callback = ketNoiChat.mock.calls[0][1]
    callback()
    window.dispatchEvent(new Event('online'))
    document.dispatchEvent(new Event('visibilitychange'))
    expect(chat.phienDongBo).toBe(4)
    chat.dongKetNoi()
    callback()
    window.dispatchEvent(new Event('online'))
    vi.advanceTimersByTime(90000)
    expect(chat.phienDongBo).toBe(0)
    expect(huy).toHaveBeenCalledTimes(1)
  })

  it('badge dùng tổng mọi trang; kết quả của session cũ không ghi đè tài khoản mới', async () => {
    const cu = choKetQua()
    chatService.taiHoiThoai
      .mockReturnValueOnce(cu.promise)
      .mockResolvedValueOnce({ data: [], meta: { so_chua_doc: 23 } })
    chat.ketNoi(1)
    chat.ketNoi(2)
    await Promise.resolve()
    cu.resolve({ data: [], meta: { so_chua_doc: 999 } })
    await Promise.resolve()
    expect(chat.soChuaDoc).toBe(23)
  })

  it('xem nhanh chỉ giữ sáu hội thoại mới nhất, số chưa đọc vẫn tính mọi trang', async () => {
    chat.taiKhoanId = 1
    const data = Array.from({ length: 20 }, (_, i) => ({ id: 20 - i, tin_cuoi: `Tin ${i}` }))
    chatService.taiHoiThoai.mockResolvedValue({ data, meta: { total: 40, so_chua_doc: 120 } })
    await chat.taiSoChuaDoc()
    expect(chat.hoiThoaiMoi.map((h) => h.id)).toEqual([20, 19, 18, 17, 16, 15])
    expect(chat.soChuaDoc).toBe(120)
    expect(chat.tongHoiThoai).toBe(40)
    expect(chatService.daDoc).not.toHaveBeenCalled()
    chat.dongKetNoi()
    expect(chat.hoiThoaiMoi).toEqual([])
  })

  it('lỗi tải preview không giữ nội dung cũ, thử lại khôi phục danh sách', async () => {
    chat.taiKhoanId = 1
    chat.hoiThoaiMoi = [{ id: 1, tin_cuoi: 'Nội dung cũ' }]
    chatService.taiHoiThoai.mockRejectedValueOnce(new Error('Mất mạng'))
    await chat.taiSoChuaDoc()
    expect(chat.hoiThoaiMoi).toEqual([])
    expect(chat.loiXemNhanh).toContain('thử lại')
    expect(chat.dangTaiXemNhanh).toBe(false)
    chatService.taiHoiThoai.mockResolvedValue({
      data: [{ id: 2 }],
      meta: { so_chua_doc: 1, total: 1 },
    })
    await chat.taiSoChuaDoc()
    expect(chat.hoiThoaiMoi).toEqual([{ id: 2 }])
    expect(chat.loiXemNhanh).toBe('')
  })

  it('preview HTTP của tài khoản cũ không trở lại sau logout', async () => {
    chat.taiKhoanId = 1
    const cho = choKetQua()
    chatService.taiHoiThoai.mockReturnValue(cho.promise)
    const tai = chat.taiSoChuaDoc()
    chat.dongKetNoi()
    cho.resolve({ data: [{ id: 1, tin_cuoi: 'Riêng tư' }], meta: { so_chua_doc: 1 } })
    await tai
    expect(chat.hoiThoaiMoi).toEqual([])
    expect(chat.soChuaDoc).toBe(0)
  })

  it('kiểm tra số lượng, dung lượng và loại ảnh trước khi gửi', () => {
    const anh = { name: 'anh.png', type: 'image/png', size: 5 * 1024 * 1024 }
    expect(kiemTraAnhChat([anh], 3)).toBe('')
    expect(kiemTraAnhChat([anh], 4)).toContain('4 ảnh')
    expect(kiemTraAnhChat([{ ...anh, size: anh.size + 1 }])).toContain('5 MB')
    expect(kiemTraAnhChat([{ ...anh, type: 'image/svg+xml' }])).toContain('JPG')
  })

  it('dán và thả ảnh giữ bản nháp chữ; tệp không hợp lệ hoặc mất quyền không được thêm', () => {
    const create = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:nhap')
    const revoke = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => {})
    const trang = taoTrang()
    const file = new File(['png'], 'anh.png', { type: 'image/png' })
    trang.noiDung = 'Chú thích đang soạn'
    const preventDefault = vi.fn()
    trang.danAnh({ clipboardData: { files: [file] }, preventDefault })
    expect(preventDefault).toHaveBeenCalledOnce()
    expect(trang.anhCho[0].file).toBe(file)
    expect(trang.noiDung).toBe('Chú thích đang soạn')
    trang.dangKeoAnh = true
    trang.thaAnh({
      dataTransfer: { files: [new File(['pdf'], 'tep.pdf', { type: 'application/pdf' })] },
    })
    expect(trang.dangKeoAnh).toBe(false)
    expect(trang.anhCho).toHaveLength(1)
    expect(trang.loiAnh).toContain('JPG')
    trang.boAnh(0)
    expect(revoke).toHaveBeenCalledWith('blob:nhap')
    trang.hoiThoai.co_the_gui = false
    trang.thaAnh({ dataTransfer: { files: [file] } })
    expect(trang.anhCho).toEqual([])
    create.mockRestore()
    revoke.mockRestore()
  })

  it('ảnh tải về muộn sau khi rời hội thoại không tạo lại blob riêng tư', async () => {
    const create = vi.spyOn(URL, 'createObjectURL')
    const cho = choKetQua()
    chatService.taiAnh.mockReturnValue(cho.promise)
    const anh = {
      ...AnhTinNhan.data(),
      hoiThoaiId: 1,
      tinId: 9,
      anh: { vi_tri: 0 },
      $emit: vi.fn(),
    }
    const tai = AnhTinNhan.methods.taiAnh.call(anh)
    AnhTinNhan.beforeUnmount.call(anh)
    cho.resolve(new Blob(['png'], { type: 'image/png' }))
    await tai
    expect(chatService.taiAnh.mock.calls[0][3].aborted).toBe(true)
    expect(create).not.toHaveBeenCalled()
    expect(anh.nguonAnh).toBe('')
    create.mockRestore()
  })

  it('gửi ảnh không chú thích và retry giữ nguyên UUID/tệp, dọn blob khi lưu', async () => {
    const revoke = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => {})
    const trang = taoTrang()
    const file = new File(['png'], 'anh.png', { type: 'image/png' })
    trang.anhCho = [{ file, ten: 'anh.png', url: 'blob:anh' }]
    chatService.gui.mockRejectedValueOnce(new Error('Mất mạng')).mockImplementationOnce((id, p) =>
      Promise.resolve({
        data: {
          id: 9,
          hoi_thoai_id: id,
          nguoi_gui_id: 1,
          client_message_id: p.client_message_id,
          noi_dung: '',
          anh: [{ vi_tri: 0, ten: 'anh.png' }],
        },
      }),
    )
    await trang.guiTin()
    expect(trang.anhCho).toEqual([])
    expect(trang.tinCho[0].tep_anh[0].file).toBe(file)
    expect(revoke).not.toHaveBeenCalled()
    await trang.guiTin(trang.tinCho[0])
    expect(chatService.gui.mock.calls[0].slice(0, 2)).toEqual(
      chatService.gui.mock.calls[1].slice(0, 2),
    )
    expect(revoke).toHaveBeenCalledWith('blob:anh')
    expect(trang.tinNhan[0].anh).toHaveLength(1)
    revoke.mockRestore()
  })

  it('thu hồi quyền xóa ảnh nháp, ảnh gửi lỗi, đóng ảnh lớn và hủy upload khi hết phiên', () => {
    const revoke = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => {})
    const trang = taoTrang()
    trang.anhCho = [{ url: 'blob:nhap' }]
    trang.tinCho = [{ tep_anh: [{ url: 'blob:loi' }] }]
    const bo = new AbortController()
    trang.boHuyGui = [bo]
    trang.anhDangXem = { src: 'blob:loi' }
    TinNhan.watch['xacThuc.taiKhoan'].call(trang, null)
    expect(revoke.mock.calls.flat()).toEqual(['blob:nhap', 'blob:loi'])
    expect(trang.anhDangXem).toBeNull()
    expect(bo.signal.aborted).toBe(true)
    revoke.mockRestore()
  })
})
