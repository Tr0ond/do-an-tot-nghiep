<template>
  <div ref="khuVuc" class="header-inbox" @focusout="matFocus">
    <button
      ref="chuong"
      type="button"
      class="inbox-icon"
      :aria-label="`Thông báo, ${thongBao.soChuaDoc} chưa đọc`"
      :aria-expanded="dangMo"
      aria-controls="header-thong-bao"
      title="Thông báo"
      @click="doiTrangThai"
    >
      <i class="bi bi-bell" aria-hidden="true"></i>
      <span v-if="thongBao.soChuaDoc" class="inbox-count" aria-hidden="true">{{
        nhanSo(thongBao.soChuaDoc)
      }}</span>
    </button>
    <button
      v-if="duongDanChat"
      ref="tinNhan"
      type="button"
      class="inbox-icon"
      :aria-label="`Tin nhắn, ${chat.soChuaDoc} chưa đọc`"
      :aria-expanded="dangMoChat"
      aria-controls="header-tin-nhan"
      title="Tin nhắn"
      @click="doiTrangThaiChat"
    >
      <i class="bi bi-chat-left-text" aria-hidden="true"></i>
      <span v-if="chat.soChuaDoc" class="inbox-count" aria-hidden="true">{{
        nhanSo(chat.soChuaDoc)
      }}</span>
    </button>
    <span class="visually-hidden" role="status" aria-atomic="true">
      {{ thongBao.soChuaDoc }} thông báo chưa đọc<span v-if="duongDanChat"
        >, {{ chat.soChuaDoc }} tin nhắn chưa đọc</span
      >.
    </span>

    <section
      v-if="dangMoChat && duongDanChat"
      id="header-tin-nhan"
      class="inbox-panel inbox-chat-panel"
      aria-labelledby="header-tin-nhan-title"
      :aria-busy="chat.dangTaiXemNhanh"
    >
      <div class="inbox-top">
        <div>
          <h2 id="header-tin-nhan-title">Tin nhắn</h2>
          <p>
            {{
              chat.soChuaDoc ? `${chat.soChuaDoc} tin nhắn chưa đọc` : 'Hội thoại gần đây của bạn'
            }}
          </p>
        </div>
        <button
          ref="dongChat"
          type="button"
          class="inbox-icon"
          aria-label="Đóng tin nhắn"
          @click="dong(true)"
        >
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>
      <div class="inbox-list">
        <div v-if="chat.loiXemNhanh" class="inbox-state" role="alert">
          <i class="bi bi-wifi-off" aria-hidden="true"></i>
          <p>{{ chat.loiXemNhanh }}</p>
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="chat.dangTaiXemNhanh"
            @click="chat.taiSoChuaDoc()"
          >
            Thử lại
          </button>
        </div>
        <p
          v-else-if="chat.dangTaiXemNhanh && !chat.hoiThoaiMoi.length"
          class="inbox-state"
          role="status"
        >
          Đang tải tin nhắn…
        </p>
        <div v-else-if="!chat.hoiThoaiMoi.length" class="inbox-state">
          <i class="bi bi-chat-square-text" aria-hidden="true"></i>
          <strong>Chưa có hội thoại</strong>
          <p>
            {{
              xacThuc.taiKhoan?.vai_tro === 'KHACH_HANG'
                ? 'Bạn sẽ có hội thoại khi được phân công PT.'
                : 'Chưa có khách hàng được phân công.'
            }}
          </p>
        </div>
        <ul v-else>
          <li v-for="hoi in chat.hoiThoaiMoi" :key="hoi.id">
            <RouterLink
              :to="`${duongDanChat}/${hoi.id}`"
              class="inbox-item inbox-conversation"
              :class="{ 'chua-doc': hoi.so_chua_doc }"
              @click="dong()"
            >
              <span class="inbox-chat-avatar" aria-hidden="true">{{
                hoi.doi_phuong.ho_ten.trim().charAt(0).toUpperCase() || '?'
              }}</span>
              <span class="inbox-item-body">
                <strong>{{ hoi.doi_phuong.ho_ten }}</strong>
                <span class="inbox-preview">{{ hoi.tin_cuoi || 'Bắt đầu cuộc trò chuyện' }}</span>
                <small v-if="hoi.da_ket_thuc" class="inbox-archive">Phân công đã kết thúc</small>
              </span>
              <span class="inbox-chat-meta">
                <time v-if="hoi.tin_cuoi_luc" :datetime="hoi.tin_cuoi_luc">{{
                  dinhDangGio(hoi.tin_cuoi_luc)
                }}</time>
                <span
                  v-if="hoi.so_chua_doc"
                  class="inbox-row-count"
                  :aria-label="`${hoi.so_chua_doc} tin chưa đọc`"
                  >{{ nhanSo(hoi.so_chua_doc) }}</span
                >
              </span>
            </RouterLink>
          </li>
        </ul>
      </div>
      <RouterLink :to="duongDanChat" class="inbox-chat-footer" @click="dong()"
        >Xem tất cả tin nhắn<i class="bi bi-arrow-right" aria-hidden="true"></i
      ></RouterLink>
    </section>

    <section
      v-if="dangMo"
      id="header-thong-bao"
      class="inbox-panel"
      aria-labelledby="header-thong-bao-title"
      :aria-busy="thongBao.dangTai"
    >
      <div class="inbox-top">
        <div>
          <h2 id="header-thong-bao-title">Thông báo</h2>
          <p>
            {{ nhanThongBao }}
          </p>
        </div>
        <button
          ref="dongBang"
          type="button"
          class="inbox-icon"
          aria-label="Đóng thông báo"
          @click="dong(true)"
        >
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>
      <div class="inbox-toolbar">
        <span>Mới nhất</span>
        <button
          type="button"
          :disabled="!thongBao.soChuaDoc || thongBao.dangDoc"
          @click="thongBao.danhDauDoc()"
        >
          {{ thongBao.dangDoc ? 'Đang cập nhật…' : 'Đọc tất cả' }}
        </button>
      </div>
      <div class="inbox-list">
        <div v-if="thongBao.loi" class="inbox-state" role="alert">
          <i class="bi bi-wifi-off" aria-hidden="true"></i>
          <p>{{ thongBao.loi }}</p>
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="thongBao.dangTai || thongBao.dangDoc"
            @click="thongBao.taiDanhSach()"
          >
            Thử lại
          </button>
        </div>
        <p
          v-else-if="thongBao.dangTai && !thongBao.danhSach.length"
          class="inbox-state"
          role="status"
        >
          Đang tải thông báo…
        </p>
        <div v-else-if="!thongBao.danhSach.length" class="inbox-state">
          <i class="bi bi-bell-slash" aria-hidden="true"></i>
          <strong>Bạn chưa có thông báo</strong>
          <p>Các cập nhật dành cho bạn sẽ xuất hiện tại đây.</p>
        </div>
        <ul v-else>
          <li v-for="tin in thongBao.danhSach" :key="tin.id">
            <button
              type="button"
              class="inbox-item"
              :class="{ 'chua-doc': !tin.da_doc_luc }"
              :disabled="thongBao.dangDoc"
              @click="moThongBao(tin)"
            >
              <span class="inbox-item-icon"><i class="bi bi-bell" aria-hidden="true"></i></span>
              <span class="inbox-item-body">
                <strong>{{ tin.tieu_de }}</strong>
                <span>{{ tin.noi_dung }}</span>
                <time :datetime="tin.tao_luc">{{ dinhDangNgay(tin.tao_luc) }}</time>
              </span>
              <span v-if="!tin.da_doc_luc" class="inbox-dot"
                ><span class="visually-hidden">Chưa đọc</span></span
              >
            </button>
          </li>
        </ul>
        <button
          v-if="thongBao.trang < thongBao.trangCuoi"
          type="button"
          class="inbox-more"
          :disabled="thongBao.dangTai || thongBao.dangDoc"
          @click="thongBao.taiDanhSach(true)"
        >
          {{ thongBao.dangTai ? 'Đang tải…' : 'Xem thêm thông báo' }}
        </button>
      </div>
    </section>
  </div>
</template>

<script>
import { useThongBaoStore } from '../stores/thongBao'
import { useChatStore } from '../stores/chat'
import { useXacThucStore } from '../stores/xacThuc'
import { dinhDangGioChat } from '../utils/chat'

export default {
  name: 'ThongBaoHeader',
  emits: ['mo-bang'],
  data() {
    return { dangMo: false, dangMoChat: false }
  },
  computed: {
    thongBao() {
      return useThongBaoStore()
    },
    chat() {
      return useChatStore()
    },
    xacThuc() {
      return useXacThucStore()
    },
    duongDanChat() {
      return (
        { KHACH_HANG: '/khach-hang/tin-nhan', HUAN_LUYEN_VIEN: '/pt/tin-nhan' }[
          this.xacThuc.taiKhoan?.vai_tro
        ] || null
      )
    },
    nhanThongBao() {
      if (this.thongBao.dangTai && !this.thongBao.trang) return 'Đang tải cập nhật…'
      if (!this.thongBao.danhSach.length) return 'Cập nhật dành riêng cho bạn'
      return this.thongBao.soChuaDoc
        ? `${this.thongBao.soChuaDoc} thông báo chưa đọc`
        : 'Bạn đã đọc hết thông báo'
    },
  },
  watch: {
    '$route.fullPath'() {
      this.dong()
    },
    'xacThuc.taiKhoan.id'() {
      this.dong()
    },
  },
  mounted() {
    document.addEventListener('pointerdown', this.bamBenNgoai)
    document.addEventListener('keydown', this.bamPhim)
  },
  beforeUnmount() {
    document.removeEventListener('pointerdown', this.bamBenNgoai)
    document.removeEventListener('keydown', this.bamPhim)
  },
  methods: {
    nhanSo(so) {
      return so > 99 ? '99+' : so
    },
    async doiTrangThai() {
      if (this.dangMo) return this.dong()
      this.dangMoChat = false
      this.dangMo = true
      this.$emit('mo-bang')
      void this.thongBao.taiDanhSach()
      await this.$nextTick()
      if (this.dangMo) this.$refs.dongBang?.focus()
    },
    async doiTrangThaiChat() {
      if (this.dangMoChat) return this.dong()
      this.dangMo = false
      this.dangMoChat = true
      this.$emit('mo-bang')
      void this.chat.taiSoChuaDoc()
      await this.$nextTick()
      if (this.dangMoChat) this.$refs.dongChat?.focus()
    },
    dong(traFocus = false) {
      const nut = this.dangMoChat ? this.$refs.tinNhan : this.$refs.chuong
      this.dangMo = false
      this.dangMoChat = false
      if (traFocus) nut?.focus()
    },
    matFocus(event) {
      if (event.relatedTarget && !this.$refs.khuVuc?.contains(event.relatedTarget)) this.dong()
    },
    bamBenNgoai(event) {
      if ((this.dangMo || this.dangMoChat) && !this.$refs.khuVuc?.contains(event.target))
        this.dong()
    },
    bamPhim(event) {
      if ((this.dangMo || this.dangMoChat) && event.key === 'Escape') {
        event.preventDefault()
        this.dong(true)
      }
    },
    dinhDangGio(luc) {
      return Number.isNaN(Date.parse(luc)) ? '' : dinhDangGioChat(luc)
    },
    async moThongBao(tin) {
      const phien = this.thongBao.maPhien
      if (!tin.da_doc_luc && !(await this.thongBao.danhDauDoc(tin.id))) return
      if (phien !== this.thongBao.maPhien) return
      // Chỉ đi đến đường dẫn nội bộ; quyền đọc tài nguyên vẫn do Backend quyết định.
      if (
        typeof tin.duong_dan === 'string' &&
        /^\/(?!\/)/.test(tin.duong_dan) &&
        !tin.duong_dan.includes('\\') &&
        [...tin.duong_dan].every((kyTu) => kyTu.charCodeAt(0) > 32)
      ) {
        this.dong()
        await this.$router.push(tin.duong_dan)
      }
    },
    dinhDangNgay(ngay) {
      if (!ngay || Number.isNaN(Date.parse(ngay))) return ''
      return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      }).format(new Date(ngay))
    },
  },
}
</script>

<style scoped>
.header-inbox {
  display: flex;
  align-items: center;
  gap: 8px;
}
.inbox-icon {
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  display: inline-grid;
  place-items: center;
  position: relative;
  padding: 0;
  border: 1px solid transparent;
  border-radius: 8px;
  background: transparent;
  color: var(--mau-chu);
  font-size: 1.25rem;
  text-decoration: none;
}
.inbox-icon:hover,
.inbox-icon[aria-expanded='true'] {
  background: var(--mau-the-hover);
  border-color: var(--mau-vien);
}
.inbox-count {
  position: absolute;
  top: 0;
  right: -2px;
  min-width: 20px;
  height: 20px;
  display: grid;
  place-items: center;
  padding: 0 4px;
  border-radius: 8px;
  background: #dc2626;
  color: #fff;
  font-size: 0.68rem;
  line-height: 1;
  font-weight: 750;
  border: 2px solid var(--mau-header-bg);
}
.inbox-panel {
  position: absolute;
  right: 24px;
  top: calc(100% - 2px);
  width: 390px;
  max-width: calc(100vw - 24px);
  color: var(--mau-chu);
  background: var(--mau-the);
  border: 1px solid var(--mau-vien);
  border-radius: 8px;
  box-shadow: var(--bong-nhe);
  overflow: hidden;
}
.inbox-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 18px 18px 8px;
}
.inbox-top h2 {
  margin: 0 0 6px;
  font-size: 1.15rem;
  font-weight: 750;
}
.inbox-top p {
  margin: 0;
  font-size: 0.8rem;
  color: var(--mau-phu);
}
.inbox-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 18px;
  border-bottom: 1px solid var(--mau-vien);
  font-size: 0.8rem;
  color: var(--mau-phu);
}
.inbox-toolbar button,
.inbox-more {
  min-height: 44px;
  padding: 8px;
  border: 0;
  background: transparent;
  color: var(--mau-chu);
  font-size: 0.8rem;
  font-weight: 650;
}
.inbox-chat-panel {
  width: 440px;
}
.inbox-chat-panel .inbox-top {
  padding-bottom: 16px;
  border-bottom: 1px solid var(--mau-vien);
}
.inbox-conversation {
  text-decoration: none;
  align-items: center;
}
.inbox-chat-avatar {
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  flex-shrink: 0;
  border-radius: 50%;
  background: var(--mau-chinh-nhat);
  color: var(--mau-chinh);
  font-size: 1rem;
  font-weight: 750;
}
.inbox-conversation strong,
.inbox-preview {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.inbox-chat-meta {
  display: grid;
  justify-items: end;
  gap: 8px;
  flex-shrink: 0;
}
.inbox-chat-meta time {
  color: var(--mau-phu);
  font-size: 0.7rem;
  white-space: nowrap;
}
.inbox-row-count {
  display: grid;
  place-items: center;
  min-width: 22px;
  height: 22px;
  border-radius: 8px;
  padding: 0 6px;
  color: #fff;
  background: #dc2626;
  font-size: 0.7rem;
  font-weight: 700;
}
.inbox-archive {
  font-size: 0.7rem;
  color: var(--mau-phu);
}
.inbox-chat-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  min-height: 52px;
  padding: 12px 18px;
  border-top: 1px solid var(--mau-vien);
  color: var(--mau-chu);
  font-size: 0.85rem;
  font-weight: 650;
  text-decoration: none;
}
.inbox-chat-footer:hover {
  background: var(--mau-the-hover);
}
.inbox-list {
  max-height: min(460px, calc(100dvh - 200px));
  overflow-y: auto;
  overscroll-behavior: contain;
}
.inbox-list ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.inbox-item {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  width: 100%;
  padding: 16px 18px;
  border: 0;
  border-bottom: 1px solid var(--mau-vien);
  background: transparent;
  text-align: left;
  color: var(--mau-chu);
}
.inbox-item.chua-doc {
  background: var(--mau-chinh-nhat);
}
.inbox-item:hover {
  background: var(--mau-the-hover);
}
.inbox-item-icon {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border-radius: 8px;
  color: var(--mau-chinh);
  background: var(--mau-the-sub);
}
.inbox-item-body {
  display: grid;
  gap: 5px;
  min-width: 0;
  flex: 1;
  overflow-wrap: anywhere;
  font-size: 0.85rem;
}
.inbox-item-body > span {
  color: var(--mau-phu);
  line-height: 1.5;
}
.inbox-item-body time {
  font-size: 0.7rem;
  color: var(--mau-phu);
}
.inbox-dot {
  width: 7px;
  height: 7px;
  background: var(--mau-chinh);
  border-radius: 50%;
  margin-top: 7px;
  flex-shrink: 0;
}
.inbox-state {
  display: grid;
  place-items: center;
  gap: 10px;
  padding: 32px 24px;
  text-align: center;
  font-size: 0.85rem;
}
.inbox-state > i {
  font-size: 1.9rem;
  color: var(--mau-phu);
}
.inbox-state p {
  margin: 0;
  color: var(--mau-phu);
}
.inbox-more {
  width: 100%;
}
.header-inbox button {
  cursor: pointer;
}
.header-inbox button:disabled {
  cursor: default;
  opacity: 0.6;
}
.header-inbox button:focus-visible,
.header-inbox a:focus-visible {
  outline: 2px solid var(--mau-chinh);
  outline-offset: -2px;
}
@media (max-width: 600px) {
  .inbox-panel {
    left: 12px;
    right: 12px;
    width: auto;
  }
}
</style>
