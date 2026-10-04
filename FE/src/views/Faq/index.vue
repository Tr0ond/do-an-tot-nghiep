<template>
  <DanhMucLayout>
    <section class="ai-page">
      <header class="ai-heading">
        <div>
          <span class="ai-eyebrow">TR0OND · HỖ TRỢ</span>
          <h1>Câu hỏi & tài liệu</h1>
          <p>Thông tin đã xuất bản, đọc miễn phí.</p>
        </div>
      </header>
      <div class="st-support-workspace">
        <aside class="st-support-nav">
          <h2>Trung tâm hỗ trợ</h2>
          <nav class="st-quick-links" aria-label="Thông tin liên quan">
            <RouterLink to="/bai-tap"
              ><i class="bi bi-collection" aria-hidden="true"></i>Thư viện bài tập</RouterLink
            ><RouterLink to="/goi-tap"
              ><i class="bi bi-box-seam" aria-hidden="true"></i>Gói tập & dịch vụ</RouterLink
            ><RouterLink to="/dang-nhap"
              ><i class="bi bi-person-circle" aria-hidden="true"></i>Đăng nhập</RouterLink
            >
          </nav>
        </aside>
        <div>
          <form class="ai-admin-filters" @submit.prevent="tai(1)">
            <label class="visually-hidden" for="faq-search">Tìm câu hỏi</label
            ><input
              id="faq-search"
              v-model.trim="tuKhoa"
              class="form-control"
              placeholder="Tìm theo tiêu đề…"
              maxlength="100"
            /><button class="btn btn-primary" :disabled="dangTai">Tìm kiếm</button>
          </form>
          <p v-if="loi" role="alert" class="alert alert-danger">{{ loi }}</p>
          <p v-if="dangTai" role="status">Đang tải tài liệu…</p>
          <p v-else-if="!danhSach.length" class="text-secondary">
            Chưa có tài liệu phù hợp. Bạn có thể xem thư viện bài tập hoặc trao đổi với PT phụ
            trách.
          </p>
          <details v-for="t in danhSach" :key="t.id" class="ai-faq-item">
            <summary>{{ t.tieu_de }}</summary>
            <p>{{ t.noi_dung }}</p>
            <small class="text-secondary">Phiên bản {{ t.phien_ban }}</small>
          </details>
          <div v-if="meta.last_page > 1" class="ai-pages">
            <button type="button" :disabled="page === 1 || dangTai" @click="tai(page - 1)">
              Trước</button
            ><span>{{ page }}/{{ meta.last_page }}</span
            ><button
              type="button"
              :disabled="page === meta.last_page || dangTai"
              @click="tai(page + 1)"
            >
              Sau
            </button>
          </div>
        </div>
      </div>
    </section>
  </DanhMucLayout>
</template>
<script>
import DanhMucLayout from '../../layouts/DanhMucLayout.vue'
import service from '../../services/taiLieuTuVanService'
import { layLoiApi } from '../../utils/loiApi'
import '../../assets/chatbot.css'
export default {
  name: 'FaqCongKhai',
  components: { DanhMucLayout },
  data() {
    return {
      danhSach: [],
      meta: { last_page: 1 },
      page: 1,
      tuKhoa: '',
      loi: '',
      dangTai: false,
      huyTai: null,
      daDong: false,
    }
  },
  mounted() {
    this.tai()
  },
  beforeUnmount() {
    this.daDong = true
    this.huyTai?.abort()
  },
  methods: {
    async tai(page = 1) {
      this.huyTai?.abort()
      const a = new AbortController()
      this.huyTai = a
      this.dangTai = true
      this.loi = ''
      try {
        const r = await service.faq(
          { page, ...(this.tuKhoa ? { tu_khoa: this.tuKhoa } : {}) },
          a.signal,
        )
        if (a.signal.aborted || this.daDong) return
        this.danhSach = r.data
        this.meta = r.meta
        this.page = page
      } catch (e) {
        if (!a.signal.aborted && !this.daDong) this.loi = layLoiApi(e).thongBao
      } finally {
        if (this.huyTai === a) this.dangTai = false
      }
    },
  },
}
</script>
