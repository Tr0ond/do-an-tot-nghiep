import test from 'node:test'
import assert from 'node:assert/strict'
import { mediaBaiTap, taoUrlMedia } from '../src/utils/media.js'
import { noiDungKeHoach } from '../src/utils/tapLuyen.js'

const api = 'http://192.168.1.15:8001/api/v1'
const anh = '/media/bai-tap/images/0001-2gPfomN.jpg'
const gif = '/media/bai-tap/animations/0001-2gPfomN.gif'

test('Ảnh/GIF lấy đúng origin API, giữ tên asset', () => {
  assert.equal(taoUrlMedia(anh, api), 'http://192.168.1.15:8001' + anh)
  assert.equal(taoUrlMedia(gif, api), 'http://192.168.1.15:8001' + gif)
  assert.equal(
    taoUrlMedia('http://192.168.1.15:8001' + anh, api),
    'http://192.168.1.15:8001' + anh,
  )
})

test('Không lấy media từ origin khác, private upload hoặc URL không hợp lệ', () => {
  for (const url of [
    'https://example.com' + anh,
    '//example.com' + anh,
    '/storage/chat/a.jpg',
    '/media/bai-tap/images/a.svg',
    '/media/bai-tap/images/%2f.jpg',
    'javascript:alert(1)',
    'http://user:pass@192.168.1.15:8001' + anh,
  ])
    assert.equal(taoUrlMedia(url, api), null)
  assert.equal(taoUrlMedia(null, api), null)
  assert.equal(taoUrlMedia(anh, 'bad-url'), null)
})

test('Đọc đúng media snapshot giáo án và nhật ký', () => {
  assert.deepEqual(
    mediaBaiTap({
      noi_dung: {
        anh_url: anh,
        gif_url: gif,
        ghi_cong_media: '© Gym visual',
        du_kien: {},
      },
    }),
    { anh_url: anh, gif_url: gif, ghi_cong_media: '© Gym visual' },
  )
})

test('Media trực tiếp từ catalog/kết quả PT ưu tiên, không gán ảnh cho bài thiếu media', () => {
  assert.equal(mediaBaiTap({ anh_url: anh }).anh_url, anh)
  assert.equal(
    mediaBaiTap({ anh_url: null, noi_dung: { anh_url: anh } }).anh_url,
    null,
  )
  assert.equal(
    mediaBaiTap({ ten_bai_tap: 'Bài do Admin thêm' }).anh_url,
    undefined,
  )
})

test('Media của bài vừa chọn chỉ phục vụ hiển thị, không đổi payload giáo án', () => {
  const bai = {
    bai_tap_id: 1,
    ngay_thu: '1',
    so_hiep: '3',
    so_lan_lap: '10',
    nghi_giay: '60',
    muc_ta_kg: '',
    ghi_chu: '',
    ...mediaBaiTap({ anh_url: anh, gif_url: gif }),
  }
  const ketQua = noiDungKeHoach({
    ten_ke_hoach: 'Giáo án',
    muc_tieu: '',
    so_ngay_tap: '1',
    giao_an_mau_id: null,
    bai_tap: [bai],
  })
  assert.equal(Object.hasOwn(ketQua.bai_tap[0], 'anh_url'), false)
  assert.equal(Object.hasOwn(ketQua.bai_tap[0], 'gif_url'), false)
})
