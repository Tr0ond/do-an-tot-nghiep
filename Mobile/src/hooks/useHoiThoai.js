import { useCallback, useEffect, useRef, useState } from 'react'
import { AppState } from 'react-native'
import { useFocusEffect, useIsFocused } from '@react-navigation/native'
import { useXemTruoc } from '../contexts/XemTruocContext'
import { useTraoDoi } from '../contexts/TraoDoiContext'
import { traoDoiService as api } from '../services/traoDoiService'
import { gopTin, taiTinMoi } from '../utils/traoDoi'

export function useHoiThoai(id) {
  const { goiDichVu } = useXemTruoc()
  const { dongBo, hoatDong, capNhat } = useTraoDoi()
  const focused = useIsFocused()
  const [hoi, datHoi] = useState(null)
  const [tin, datTin] = useState([])
  const [conCu, datConCu] = useState(false)
  const [dangTai, datDangTai] = useState(true)
  const [loi, datLoi] = useState('')
  const [matQuyen, datMatQuyen] = useState(false)
  const [lanHien, datLanHien] = useState(0)
  const ds = useRef([])
  const cursorDoc = useRef(0)
  const docCho = useRef(0)
  const theHe = useRef(0)
  const hien = useRef(false)
  const khoa = useRef(false)
  const cho = useRef(false)
  const controllers = useRef(new Set())
  const capNhatRef = useRef(capNhat)
  capNhatRef.current = capNhat
  const nhan = useCallback((d, lan) => {
    if (!d || !hien.current || lan !== theHe.current) return
    ds.current = gopTin(ds.current, d.tin_nhan)
    datTin(ds.current)
    datHoi(d.hoi_thoai)
    datMatQuyen(false)
    cursorDoc.current = Math.max(
      cursorDoc.current,
      d.hoi_thoai.cursor_da_doc || 0,
    )
    datLoi('')
  }, [])
  const tai = useCallback(
    async (cu = false) => {
      if (!hien.current) return
      if (khoa.current) {
        if (!cu) cho.current = true
        return
      }
      khoa.current = true
      const lan = theHe.current
      const c = new AbortController()
      controllers.current.add(c)
      datDangTai(true)
      try {
        const doc = (q) => goiDichVu((t) => api.taiTin(t, id, q, c.signal))
        if (cu || !ds.current.length) {
          const d = await doc(cu ? { before_id: ds.current[0].id } : {})
          if (!c.signal.aborted && lan === theHe.current && hien.current && d) {
            nhan(d.data, lan)
            datConCu(d.data.con_tin)
          }
        } else {
          await taiTinMoi(
            async (cursor) => {
              const d = await doc({ after_id: cursor })
              return c.signal.aborted || lan !== theHe.current ? null : d?.data
            },
            ds.current.at(-1).id,
            (d) => nhan(d, lan),
          )
        }
      } catch (e) {
        if (!c.signal.aborted && lan === theHe.current && hien.current) {
          datLoi(e.message)
          if ([403, 404].includes(e.status)) {
            datMatQuyen(true)
            datConCu(false)
            ds.current = []
            datTin([])
            datHoi(null)
          }
        }
      } finally {
        controllers.current.delete(c)
        if (lan === theHe.current) {
          khoa.current = false
          datDangTai(false)
          if (cho.current) {
            cho.current = false
            tai()
          }
        }
      }
    },
    [id, goiDichVu, nhan],
  )
  useFocusEffect(
    useCallback(() => {
      const doi = (active) => {
        theHe.current++
        datLanHien(theHe.current)
        for (const c of controllers.current) c.abort()
        controllers.current.clear()
        khoa.current = false
        cho.current = false
        docCho.current = 0
        hien.current = active
        ds.current = []
        datTin([])
        datHoi(null)
        if (active) tai()
      }
      doi(AppState.currentState === 'active')
      const app = AppState.addEventListener('change', (d) =>
        doi(d === 'active'),
      )
      return () => {
        doi(false)
        app.remove()
      }
    }, [tai]),
  )
  useEffect(() => {
    if (focused && hoatDong) tai()
  }, [dongBo, focused, hoatDong, tai])
  const daDoc = useCallback(
    async (tinId) => {
      if (
        !hien.current ||
        tinId <= cursorDoc.current ||
        tinId <= docCho.current
      )
        return
      docCho.current = tinId
      const lan = theHe.current
      const c = new AbortController()
      controllers.current.add(c)
      try {
        const d = await goiDichVu((t) => api.docTin(t, id, tinId, c.signal))
        if (d && hien.current && lan === theHe.current && !c.signal.aborted) {
          cursorDoc.current = Math.max(cursorDoc.current, d.data.cursor_da_doc)
          capNhatRef.current()
        }
      } catch {
        /* Lần đồng bộ hoặc hiển thị tiếp theo sẽ thử lại cursor đã thấy. */
      } finally {
        controllers.current.delete(c)
        if (lan === theHe.current && docCho.current === tinId)
          docCho.current = 0
      }
    },
    [goiDichVu, id],
  )
  return {
    hoi,
    tin,
    conCu,
    dangTai,
    loi,
    matQuyen,
    lanHien,
    taiLai: tai,
    daDoc,
    nhanTin: (t) => {
      if (!hien.current) return
      ds.current = gopTin(ds.current, [t])
      datTin(ds.current)
    },
  }
}
