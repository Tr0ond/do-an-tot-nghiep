import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useChuDeStore } from '../src/stores/chuDe'

describe('Quản lý Chủ đề Dark / Light Theme', () => {
  let chuDeStore
  let mockStorage = {}
  let rootAttributes = {}
  let rootClassList = new Set()

  beforeEach(() => {
    mockStorage = {}
    rootAttributes = {}
    rootClassList = new Set()

    globalThis.localStorage = {
      getItem: (key) => (key in mockStorage ? mockStorage[key] : null),
      setItem: (key, val) => {
        mockStorage[key] = String(val)
      },
      removeItem: (key) => {
        delete mockStorage[key]
      },
      clear: () => {
        mockStorage = {}
      },
    }

    globalThis.document = {
      documentElement: {
        setAttribute: (key, val) => {
          rootAttributes[key] = val
        },
        getAttribute: (key) => rootAttributes[key] || null,
        removeAttribute: (key) => {
          delete rootAttributes[key]
        },
        classList: {
          add: (cls) => rootClassList.add(cls),
          remove: (cls) => rootClassList.delete(cls),
          contains: (cls) => rootClassList.has(cls),
        },
      },
    }

    setActivePinia(createPinia())
    chuDeStore = useChuDeStore()
  })

  afterEach(() => {
    delete globalThis.localStorage
    delete globalThis.document
  })

  it('khởi tạo mặc định là dark mode', () => {
    chuDeStore.khoiTaoChuDe()
    expect(chuDeStore.chuDeHienTai).toBe('dark')
    expect(chuDeStore.laChuDeToi).toBe(true)
    expect(chuDeStore.laChuDeSang).toBe(false)
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')
    expect(document.documentElement.classList.contains('dark-theme')).toBe(true)
  })

  it('chuyển đổi qua lại giữa dark và light theme', () => {
    chuDeStore.thietLapChuDe('dark')
    expect(chuDeStore.chuDeHienTai).toBe('dark')

    chuDeStore.chuyenDoiChuDe()
    expect(chuDeStore.chuDeHienTai).toBe('light')
    expect(chuDeStore.laChuDeSang).toBe(true)
    expect(document.documentElement.getAttribute('data-theme')).toBe('light')
    expect(document.documentElement.classList.contains('light-theme')).toBe(true)
    expect(localStorage.getItem('gym_theme')).toBe('light')

    chuDeStore.chuyenDoiChuDe()
    expect(chuDeStore.chuDeHienTai).toBe('dark')
    expect(chuDeStore.laChuDeToi).toBe(true)
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')
    expect(localStorage.getItem('gym_theme')).toBe('dark')
  })

  it('khôi phục chủ đề đã lưu trong localStorage', () => {
    localStorage.setItem('gym_theme', 'light')
    chuDeStore.khoiTaoChuDe()
    expect(chuDeStore.chuDeHienTai).toBe('light')
    expect(document.documentElement.getAttribute('data-theme')).toBe('light')
    expect(document.documentElement.classList.contains('light-theme')).toBe(true)
  })
})
