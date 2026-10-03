<template>
  <CaNhanLayout>
    <section class="ai-page">
      <header class="ai-heading">
        <div>
          <span class="ai-eyebrow">NGUỒN TƯ VẤN TR0OND AI</span>
          <h1>Tài liệu & thống kê AI</h1>
          <p>Soạn nội dung, kiểm tra và xuất bản trước khi AI sử dụng.</p>
        </div>
        <button type="button" class="btn btn-primary" :disabled="dangLuu" @click="soanMoi">
          Thêm tài liệu
        </button>
      </header>
      <p
        v-if="thongBao"
        :role="coLoi ? 'alert' : 'status'"
        class="alert"
        :class="coLoi ? 'alert-danger' : 'alert-success'"
      >
        {{ thongBao }}
      </p>
      <div v-if="thongKe" class="ai-admin-stats" aria-label="Thống kê AI hôm nay">
        <div>
          <span>Yêu cầu hôm nay</span><strong>{{ thongKe.yeu_cau }}</strong>
        </div>
        <div>
          <span>Trả lời thành công</span><strong>{{ thongKe.thanh_cong }}</strong>
        </div>
        <div>
          <span>Yêu cầu lỗi</span><strong>{{ thongKe.loi }}</strong>
        </div>
        <div>
          <span>Token vào / ra</span
          ><strong>{{ thongKe.input_tokens }} / {{ thongKe.output_tokens }}</strong>
        </div>
      </div>
      <p class="text-secondary small">
        Thống kê ngày {{ thongKe?.ngay }} theo giờ Việt Nam; không hiển thị nội dung hội thoại KH.
      </p>
      <form v-if="banNhap" ref="form" class="ai-admin-editor" @submit.prevent="luu">
        <h2 class="h5">{{ idSua ? 'Sửa tài liệu' : 'Tài liệu mới' }}</h2>
        <label for="ai-doc-title" class="form-label">Tiêu đề</label
        ><input
          id="ai-doc-title"
          v-model.trim="banNhap.tieu_de"
          class="form-control mb-3"
          required
          maxlength="255"
          :disabled="dangLuu"
        />
        <label for="ai-doc-type" class="form-label">Loại tài liệu</label
        ><select
          id="ai-doc-type"
          v-model="banNhap.loai"
          class="form-select mb-3"
          :disabled="dangLuu"
        >
          <option value="FAQ">Câu hỏi thường gặp</option>
          <option value="CHINH_SACH">Chính sách</option>
          <option value="HUONG_DAN">Hướng dẫn</option>
        </select>
        <label for="ai-doc-body" class="form-label">Nội dung</label
        ><textarea
          id="ai-doc-body"
          v-model.trim="banNhap.noi_dung"
          class="form-control mb-2"
          rows="8"
          required
          maxlength="8000"
          :disabled="dangLuu"
        ></textarea>
        <p class="small text-secondary">
          Văn bản thuần · {{ banNhap.noi_dung.length }}/8000. Lưu thành nháp; cần xuất bản để KH và
          AI đọc được.
        </p>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" :disabled="dangLuu">
            {{ dangLuu ? 'Đang lưu…' : 'Lưu nháp' }}</button
          ><button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="dangLuu"
            @click="banNhap = null"
          >
            Đóng
          </button>
        </div>
      </form>
      <form class="ai-admin-filters" @submit.prevent="taiDanhSach(1)">
        <label class="visually-hidden" for="ai-doc-search">Tìm tài liệu</label
        ><input
          id="ai-doc-search"
          v-model.trim="tuKhoa"
          class="form-control"
          placeholder="Tìm theo tiêu đề…"
          maxlength="100"
        /><label class="visually-hidden" for="ai-doc-state">Trạng thái</label
        ><select id="ai-doc-state" v-model="trangThai" class="form-select">
          <option value="">Tất cả trạng thái</option>
          <option value="NHAP">Bản nháp</option>
          <option value="DA_XUAT_BAN">Đã xuất bản</option>
          <option value="NGUNG_SU_DUNG">Ngừng xuất bản</option></select
        ><button class="btn btn-outline-secondary" :disabled="dangTai || dangLuu">
          Tìm / cập nhật
        </button>
      </form>
      <p v-if="dangTai" role="status">Đang tải tài liệu…</p>
      <p v-else-if="!danhSach.length" class="text-secondary">
        Chưa có tài liệu phù hợp. Thêm tài liệu để bắt đầu.
      </p>
      <article v-for="t in danhSach" :key="t.id" class="ai-admin-document">
        <div>
          <span class="badge bg-secondary-subtle text-body"
            >{{ nhanTrangThai(t.trang_thai) }} · v{{ t.phien_ban }}</span
          >
          <h2 class="h5 mt-2">{{ t.tieu_de }}</h2>
          <details>
            <summary>Xem nội dung</summary>
            <p>{{ t.noi_dung }}</p>
          </details>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="dangLuu"
            @click="sua(t)"
          >
            Sửa</button
          ><button
            type="button"
            class="btn"
            :class="t.trang_thai === 'DA_XUAT_BAN' ? 'btn-outline-danger' : 'btn-primary'"
            :disabled="dangLuu"
            @click="deNghi(t)"
          >
            {{ t.trang_thai === 'DA_XUAT_BAN' ? 'Ngừng xuất bản' : 'Xuất bản' }}
          </button>
        </div>
        <div
          v-if="xacNhan?.taiLieu.id === t.id"
          class="ai-admin-confirm"
          role="group"
          aria-label="Xác nhận xuất bản tài liệu"
        >
          <p>
            {{
              xacNhan.hanhDong === 'xuat-ban'
                ? 'Xuất bản nội dung trên để KH đọc và AI sử dụng?'
                : 'Ngừng đưa tài liệu này vào FAQ và ngữ cảnh AI?'
            }}
          </p>
          <button type="button" class="btn btn-primary me-2" :disabled="dangLuu" @click="thucHien">
            Xác nhận</button
          ><button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="dangLuu"
            @click="xacNhan = null"
          >
            Để sau
          </button>
        </div>
      </article>
      <div v-if="meta.last_page > 1" class="ai-pages">
        <button type="button" :disabled="page === 1 || dangTai" @click="taiDanhSach(page - 1)">
          Trước</button
        ><span>{{ page }}/{{ meta.last_page }}</span
        ><button
          type="button"
          :disabled="page === meta.last_page || dangTai"
          @click="taiDanhSach(page + 1)"
        >
          Sau
        </button>
      </div>
    </section>
  </CaNhanLayout>
</template>
<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import service from '../../../services/taiLieuTuVanService'
import { layLoiApi } from '../../../utils/loiApi'
import '../../../assets/chatbot.css'
export default {
  name: 'TaiLieuTuVanAdmin',
  components: { CaNhanLayout },
  data() {
    return {
      danhSach: [],
      thongKe: null,
      meta: { last_page: 1 },
      page: 1,
      tuKhoa: '',
      trangThai: '',
      banNhap: null,
      idSua: null,
      phienBan: null,
      uuidTao: null,
      hashTao: '',
      xacNhan: null,
      thongBao: '',
      coLoi: false,
      dangTai: false,
      dangLuu: false,
      huyTai: null,
      daDong: false,
    }
  },
  mounted() {
    this.taiDanhSach()
  },
  beforeUnmount() {
    this.daDong = true
    this.huyTai?.abort()
  },
  methods: {
    nhanTrangThai(t) {
      return { NHAP: 'Bản nháp', DA_XUAT_BAN: 'Đã xuất bản', NGUNG_SU_DUNG: 'Ngừng xuất bản' }[t]
    },
    async taiDanhSach(page = 1) {
      this.huyTai?.abort()
      const a = new AbortController()
      this.huyTai = a
      this.dangTai = true
      try {
        const [r, s] = await Promise.all([
          service.taiDanhSach(
            {
              page,
              ...(this.tuKhoa ? { tu_khoa: this.tuKhoa } : {}),
              ...(this.trangThai ? { trang_thai: this.trangThai } : {}),
            },
            a.signal,
          ),
          service.thongKe(a.signal),
        ])
        if (a.signal.aborted || this.daDong) return
        this.danhSach = r.data
        this.meta = r.meta
        this.page = page
        this.thongKe = s.data
      } catch (e) {
        if (!a.signal.aborted && !this.daDong) this.baoLoi(e)
      } finally {
        if (this.huyTai === a) this.dangTai = false
      }
    },
    soanMoi() {
      this.idSua = null
      this.phienBan = null
      this.uuidTao = null
      this.hashTao = ''
      this.banNhap = { tieu_de: '', loai: 'FAQ', noi_dung: '' }
      this.xacNhan = null
    },
    sua(t) {
      this.idSua = t.id
      this.phienBan = t.phien_ban
      this.banNhap = { tieu_de: t.tieu_de, loai: t.loai, noi_dung: t.noi_dung }
      this.xacNhan = null
    },
    baoLoi(e) {
      this.coLoi = true
      this.thongBao = layLoiApi(e).thongBao
    },
    async luu() {
      if (this.dangLuu || !this.banNhap) return
      this.dangLuu = true
      this.thongBao = ''
      try {
        const body = { ...this.banNhap }
        const hash = JSON.stringify(body)
        if (hash !== this.hashTao) {
          this.hashTao = hash
          this.uuidTao = crypto.randomUUID()
        }
        const r = this.idSua
          ? await service.sua(this.idSua, { ...body, phien_ban: this.phienBan })
          : await service.tao({ ...body, client_request_id: this.uuidTao })
        if (this.daDong) return
        this.idSua = r.data.id
        this.phienBan = r.data.phien_ban
        this.banNhap = null
        this.coLoi = false
        this.thongBao = 'Đã lưu bản nháp. Xuất bản khi nội dung đã được kiểm tra.'
        await this.taiDanhSach()
      } catch (e) {
        if (!this.daDong) this.baoLoi(e)
      } finally {
        this.dangLuu = false
      }
    },
    deNghi(t) {
      this.xacNhan = { taiLieu: t, hanhDong: t.trang_thai === 'DA_XUAT_BAN' ? 'ngung' : 'xuat-ban' }
    },
    async thucHien() {
      if (this.dangLuu || !this.xacNhan) return
      this.dangLuu = true
      const { taiLieu: t, hanhDong } = this.xacNhan
      try {
        await service.thaoTac(t.id, hanhDong, t.phien_ban)
        if (this.daDong) return
        this.xacNhan = null
        this.coLoi = false
        this.thongBao = hanhDong === 'xuat-ban' ? 'Đã xuất bản tài liệu.' : 'Đã ngừng xuất bản.'
        await this.taiDanhSach()
      } catch (e) {
        if (!this.daDong) this.baoLoi(e)
      } finally {
        this.dangLuu = false
      }
    },
  },
}
</script>
