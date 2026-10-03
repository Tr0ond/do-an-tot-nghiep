<template>
  <div class="member-menu" :class="{ 'is-collapsed': thuGon }">
    <RouterLink
      to="/"
      class="sidebar-brand"
      aria-label="Huấn luyện cá nhân — trang chủ"
      @click="$emit('dieu-huong')"
    >
      <LogoThuongHieu />
      <span class="sidebar-brand-copy">HUẤN LUYỆN<small>CÁ NHÂN</small></span>
    </RouterLink>
    <span class="sidebar-caption" :class="{ 'visually-hidden': thuGon }">{{ nhanVaiTro }}</span>
    <nav class="sidebar-navigation" :aria-label="nhanVaiTro">
      <RouterLink
        v-for="muc in danhSach"
        :key="muc.duongDan"
        :to="muc.duongDan"
        :class="{ 'is-active': dangChon(muc) }"
        :aria-current="dangChon(muc) ? 'page' : undefined"
        :aria-label="muc.ten"
        :title="thuGon ? muc.ten : undefined"
        @click="$emit('dieu-huong')"
      >
        <i :class="`bi bi-${muc.bieuTuong}`" aria-hidden="true"></i>
        <span class="sidebar-label" :class="{ 'visually-hidden': thuGon }">{{ muc.ten }}</span>
        <span
          v-if="muc.chat && chat.soChuaDoc"
          class="sidebar-unread"
          :aria-label="`${chat.soChuaDoc} tin chưa đọc`"
          >{{ chat.soChuaDoc > 99 ? '99+' : chat.soChuaDoc }}</span
        >
      </RouterLink>
    </nav>
  </div>
</template>

<script>
import { useChatStore } from '../stores/chat'
import LogoThuongHieu from './LogoThuongHieu.vue'

const muc = (ten, duongDan, bieuTuong, them = {}) => ({ ten, duongDan, bieuTuong, ...them })
const menuVaiTro = {
  KHACH_HANG: [
    muc('Tổng quan', '/khach-hang/tong-quan', 'grid-1x2'),
    muc('Tin nhắn', '/khach-hang/tin-nhan', 'chat-left-text', { chat: true }),
    muc('Tr0ond AI', '/khach-hang/chatbot', 'robot'),
    muc('Hồ sơ của tôi', '/khach-hang/ho-so', 'person'),
    muc('Gói của tôi', '/khach-hang/goi-cua-toi', 'wallet2'),
    muc('Giáo án của tôi', '/khach-hang/ke-hoach', 'journal-check'),
    muc('Lịch & nhật ký tập', '/khach-hang/lich-tap', 'calendar2-week'),
    muc('Lịch hẹn', '/khach-hang/lich-hen', 'calendar3', { lienQuan: '/khach-hang/dat-lich' }),
    muc('Đơn hàng', '/khach-hang/don-hang', 'receipt'),
    muc('Gói tập', '/goi-tap', 'box-seam'),
    muc('Thư viện bài tập', '/bai-tap', 'collection-play'),
    muc('Câu hỏi & tài liệu', '/faq', 'question-circle'),
  ],
  HUAN_LUYEN_VIEN: [
    muc('Tổng quan', '/pt/tong-quan', 'grid-1x2'),
    muc('Tin nhắn', '/pt/tin-nhan', 'chat-left-text', { chat: true }),
    muc('Hồ sơ PT', '/pt/ho-so', 'person'),
    muc('Học viên & giáo án', '/pt/hoc-vien', 'people', {
      lienQuan: ['/pt/ke-hoach', '/pt/lich-tap'],
    }),
    muc('Giáo án mẫu', '/pt/giao-an-mau', 'journal-text'),
    muc('Khung giờ', '/pt/khung-gio', 'clock'),
    muc('Lịch hẹn', '/pt/lich-hen', 'calendar3'),
    muc('Thư viện bài tập', '/bai-tap', 'collection-play'),
  ],
  ADMIN: [
    muc('Tổng quan', '/admin/tong-quan', 'grid-1x2'),
    muc('Tài khoản', '/admin/tai-khoan', 'people'),
    muc('Hồ sơ của tôi', '/admin/ho-so', 'person'),
    muc('Bài tập', '/admin/bai-tap', 'collection-play'),
    muc('Nhóm cơ', '/admin/nhom-co', 'diagram-3'),
    muc('Gói tập', '/admin/goi-tap', 'box-seam'),
    muc('Giáo án mẫu', '/admin/giao-an-mau', 'journal-text'),
    muc('Đơn hàng', '/admin/don-hang', 'receipt'),
    muc('Phân công PT', '/admin/phan-cong', 'person-check'),
    muc('Lịch hẹn', '/admin/lich-hen', 'calendar3'),
    muc('Tài liệu & AI', '/admin/tai-lieu-tu-van', 'robot'),
  ],
}

export default {
  name: 'MenuCaNhan',
  components: { LogoThuongHieu },
  props: {
    vaiTro: { type: String, default: '' },
    nhanVaiTro: { type: String, default: 'Thành viên' },
    thuGon: { type: Boolean, default: false },
  },
  emits: ['dieu-huong'],
  computed: {
    chat() {
      return useChatStore()
    },
    danhSach() {
      return menuVaiTro[this.vaiTro] || []
    },
  },
  methods: {
    dangChon(muc) {
      return [muc.duongDan, ...[muc.lienQuan].flat()]
        .filter(Boolean)
        .some((goc) => this.$route.path === goc || this.$route.path.startsWith(`${goc}/`))
    },
  },
}
</script>

<style scoped>
.member-menu {
  padding: 24px 16px;
}
.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  color: var(--mau-chu);
  min-height: 48px;
  margin: 0 8px 32px;
}
.sidebar-brand .logo-thuong-hieu {
  --kich-thuoc-logo: 48px;
}
.sidebar-brand-copy {
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.04em;
}
.sidebar-brand-copy small {
  display: block;
  margin-top: 4px;
  font-size: 0.72rem;
  color: var(--mau-phu);
  letter-spacing: 0.14em;
}
.sidebar-caption {
  display: block;
  padding: 0 16px;
  margin-bottom: 12px;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--mau-phu);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.sidebar-navigation {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.sidebar-navigation a {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 48px;
  padding: 12px 14px;
  color: var(--mau-phu);
  border-radius: 12px;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 650;
}
.sidebar-navigation i {
  width: 22px;
  flex: 0 0 22px;
  font-size: 1.2rem;
  text-align: center;
}
.sidebar-navigation a:hover {
  color: var(--mau-chu);
  background: var(--mau-the-hover);
}
.sidebar-navigation a.is-active {
  color: var(--mau-chinh);
  background: color-mix(in srgb, var(--mau-chinh) 12%, transparent);
}
.sidebar-navigation a.is-active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 14px;
  bottom: 14px;
  width: 3px;
  border-radius: 4px;
  background: var(--mau-chinh);
}
.sidebar-brand:focus-visible,
.sidebar-navigation a:focus-visible {
  outline: 2px solid var(--mau-chinh);
  outline-offset: 2px;
}
.sidebar-unread {
  margin-left: auto;
  min-width: 22px;
  padding: 2px 5px;
  border-radius: 20px;
  font-size: 0.65rem;
  background: var(--mau-chinh);
  color: white;
  text-align: center;
}
.is-collapsed {
  padding-inline: 12px;
}
.is-collapsed .sidebar-brand {
  margin-inline: 6px;
}
.is-collapsed .sidebar-brand-copy {
  display: none;
}
.is-collapsed .sidebar-navigation a {
  justify-content: center;
  padding-inline: 0;
}
.is-collapsed .sidebar-unread {
  position: absolute;
  right: 0;
  top: 0;
}
</style>
