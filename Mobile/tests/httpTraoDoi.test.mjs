import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'

// Thay riêng bộ đọc cấu hình native; chạy đúng HTTP client trên máy để kiểm tra wire format.
const source = fs
  .readFileSync(new URL('../src/utils/http.js', import.meta.url), 'utf8')
  .replace(
    "import { layApiUrl } from '../config/moiTruong'",
    "const layApiUrl = () => 'http://localhost/api/v1'",
  )
const { goiApi, huyYeuCau } = await import(
  `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`
)
test('HTTP chat: multipart không ép boundary, raw auth không cần envelope và ảnh riêng đi qua bearer', async () => {
  const cu = globalThis.fetch
  try {
    const formData = new FormData()
    formData.append('noi_dung', 'Ảnh')
    globalThis.fetch = async (url, d) => {
      assert.equal(d.body, formData)
      assert.equal(d.headers['Content-Type'], undefined)
      assert.equal(d.headers.Authorization, 'Bearer token-thử')
      assert.equal(d.credentials, 'omit')
      return Response.json({ status: true, data: { id: 1 } })
    }
    assert.deepEqual(
      await goiApi('/hoi-thoai/1/tin-nhan', {
        method: 'POST',
        formData,
        token: 'token-thử',
      }),
      { id: 1 },
    )
    globalThis.fetch = async () => Response.json({ auth: 'raw chữ ký' })
    assert.deepEqual(await goiApi('/broadcasting/auth', { raw: true }), {
      auth: 'raw chữ ký',
    })
    globalThis.fetch = async (url, d) => {
      assert.equal(d.headers.Authorization, 'Bearer riêng')
      return new Response(new Uint8Array([1, 2, 3]), {
        headers: {
          'Content-Type': 'image/png',
          'Cache-Control': 'private, no-store',
        },
      })
    }
    const anh = await goiApi('/hoi-thoai/1/tin-nhan/1/anh/0', {
      anh: true,
      token: 'riêng',
    })
    assert.equal(anh.mime, 'image/png')
    assert.deepEqual([...anh.bytes], [1, 2, 3])
    globalThis.fetch = async () =>
      Response.json(
        { message: 'Chậm lại' },
        { status: 429, headers: { 'Retry-After': '60' } },
      )
    await assert.rejects(
      goiApi('/hoi-thoai', { raw: true }),
      (e) => e.status === 429 && e.retryAfter === 60,
    )
  } finally {
    globalThis.fetch = cu
    huyYeuCau()
  }
})
test('HTTP ảnh không bỏ qua 401/404 và hủy toàn bộ request khi rời phiên', async () => {
  const cu = globalThis.fetch
  try {
    for (const status of [401, 404]) {
      globalThis.fetch = async () =>
        Response.json({ message: 'Không có quyền' }, { status })
      await assert.rejects(
        goiApi('/ảnh', { anh: true }),
        (e) => e.status === status,
      )
    }
    globalThis.fetch = (url, { signal }) =>
      new Promise((resolve, reject) =>
        signal.addEventListener('abort', () =>
          reject(Object.assign(new Error('stop'), { name: 'AbortError' })),
        ),
      )
    const p = goiApi('/ảnh', { anh: true })
    huyYeuCau()
    await assert.rejects(p, /dừng/)
  } finally {
    globalThis.fetch = cu
  }
})

test('Dịch vụ upload dùng File/Blob và filename, giữ thứ tự ảnh; không gửi object uri không tương thích SDK 57', async () => {
  const files = new Map([
    ['file:///a.png', 'A'],
    ['file:///b.png', 'B'],
  ])
  globalThis.__mb4File = class extends Blob {
    constructor(uri) {
      super([files.get(uri) || ''], { type: 'image/png' })
      this.exists = files.has(uri)
    }
  }
  const calls = []
  globalThis.__mb4Api = async (url, d) => {
    calls.push(d)
    return { status: true, data: { id: 1 } }
  }
  try {
    const s = fs
      .readFileSync(
        new URL('../src/services/traoDoiService.js', import.meta.url),
        'utf8',
      )
      .replace(
        "import { goiApi } from '../utils/http'",
        'const goiApi = globalThis.__mb4Api',
      )
      .replace(
        "import { File } from 'expo-file-system'",
        'const File = globalThis.__mb4File',
      )
    const { traoDoiService: api } = await import(
      `data:text/javascript;base64,${Buffer.from(s).toString('base64')}`
    )
    await api.guiTin('t', 1, {
      client_message_id: 'id',
      noi_dung: 'Ảnh',
      anh: [
        { uri: 'file:///a.png', ten: 'a.png' },
        { uri: 'file:///b.png', ten: 'b.png' },
      ],
    })
    const entries = [...calls[0].formData.entries()]
    assert.deepEqual(
      entries.map(([k, v]) => [k, typeof v === 'string' ? v : v.name]),
      [
        ['client_message_id', 'id'],
        ['noi_dung', 'Ảnh'],
        ['anh[]', 'a.png'],
        ['anh[]', 'b.png'],
      ],
    )
    assert.equal(await entries[2][1].text(), 'A')
    assert.equal(await entries[3][1].text(), 'B')
    await assert.rejects(
      async () =>
        api.guiTin('t', 1, {
          client_message_id: 'id',
          noi_dung: '',
          anh: [{ uri: 'file:///missing.png', ten: 'missing.png' }],
        }),
      (e) => e.status === 422,
    )
  } finally {
    delete globalThis.__mb4Api
    delete globalThis.__mb4File
  }
})
