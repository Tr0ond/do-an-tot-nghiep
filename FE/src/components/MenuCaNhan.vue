<template>
  <div class="member-menu" :class="{ 'is-collapsed': thuGon }">
    <!-- Brand & Role Area chuẩn Stitch -->
    <div class="sidebar-brand-box">
      <RouterLink
        to="/"
        class="sidebar-brand"
        aria-label="FitForge — trang chủ"
        @click="$emit('dieu-huong')"
      >
        <LogoThuongHieu />
        <div v-if="!thuGon" class="brand-info">
          <span class="brand-title">FitForge</span>
          <span class="brand-badge">{{ nhanVaiTro }}</span>
        </div>
      </RouterLink>
    </div>

    <!-- Navigation List -->
    <nav class="sidebar-navigation" :aria-label="nhanVaiTro">
      <section v-for="nhom in cacNhom" :key="nhom.ten" class="sidebar-group">
        <h2 class="sidebar-caption" :class="{ 'visually-hidden': thuGon }">{{ nhom.ten }}</h2>
        <RouterLink
          v-for="muc in nhom.cacMuc"
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
      </section>
    </nav>

    <!-- Bottom Sidebar Section (Hỗ trợ kỹ thuật) -->
    <div v-if="!thuGon" class="sidebar-footer-box">
      <RouterLink to="/faq" class="sidebar-support-link" @click="$emit('dieu-huong')">
        <i class="bi bi-question-circle" aria-hidden="true"></i>
        <span>Hỗ trợ kỹ thuật</span>
      </RouterLink>
    </div>
  </div>
</template>

<script>
import { useChatStore } from '../stores/chat'
import LogoThuongHieu from './LogoThuongHieu.vue'

const muc = (ten, duongDan, bieuTuong, them = {}) => ({ ten, duongDan, bieuTuong, ...them })
const menuVaiTro = {
  KHACH_HANG: [
    muc('Tổng quan', '/khach-hang/tong-quan', 'grid-1x2'),
    muc('Giáo án của tôi', '/khach-hang/ke-hoach', 'journal-check'),
    muc('Lịch & nhật ký tập', '/khach-hang/lich-tap', 'calendar2-week'),
    muc('Chỉ số cơ thể', '/khach-hang/chi-so-co-the', 'activity'),
    muc('Lịch hẹn', '/khach-hang/lich-hen', 'calendar3', { lienQuan: '/khach-hang/dat-lich' }),
    muc('Gói của tôi', '/khach-hang/goi-cua-toi', 'wallet2'),
    muc('Đơn hàng', '/khach-hang/don-hang', 'receipt'),
    muc('Tin nhắn', '/khach-hang/tin-nhan', 'chat-left-text', { chat: true }),
    muc('FitForge AI', '/khach-hang/chatbot', 'robot'),
    muc('Thư viện bài tập', '/bai-tap', 'collection-play'),
    muc('Gói tập', '/goi-tap', 'box-seam'),
    muc('Câu hỏi & tài liệu', '/faq', 'question-circle'),
    muc('Hồ sơ của tôi', '/khach-hang/ho-so', 'person'),
  ],
  HUAN_LUYEN_VIEN: [
    muc('Tổng quan', '/pt/tong-quan', 'grid-1x2'),
    muc('Lịch hẹn', '/pt/lich-hen', 'calendar3'),
    muc('Khung giờ', '/pt/khung-gio', 'clock'),
    muc('Học viên PT', '/pt/hoc-vien', 'people', {
      lienQuan: ['/pt/ke-hoach', '/pt/lich-tap'],
    }),
    muc('Giáo án mẫu', '/pt/giao-an-mau', 'journal-text'),
    muc('Thư viện bài tập', '/bai-tap', 'collection-play'),
    muc('Tin nhắn', '/pt/tin-nhan', 'chat-left-text', { chat: true }),
    muc('Hồ sơ PT', '/pt/ho-so', 'person'),
  ],
  ADMIN: [
    muc('Tổng quan', '/admin/tong-quan', 'grid-1x2'),
    muc('Tài khoản', '/admin/tai-khoan', 'people'),
    muc('Phân công PT', '/admin/phan-cong', 'person-check'),
    muc('Đơn hàng', '/admin/don-hang', 'receipt'),
    muc('Lịch hẹn', '/admin/lich-hen', 'calendar3'),
    muc('Gói tập', '/admin/goi-tap', 'box-seam'),
    muc('Bài tập', '/admin/bai-tap', 'collection-play'),
    muc('Nhóm cơ', '/admin/nhom-co', 'diagram-3'),
    muc('Giáo án mẫu', '/admin/giao-an-mau', 'journal-text'),
    muc('Tài liệu & AI', '/admin/tai-lieu-tu-van', 'robot'),
    muc('Hồ sơ của tôi', '/admin/ho-so', 'person'),
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
    cacNhom() {
      const phanNhom =
        this.vaiTro === 'ADMIN'
          ? [
              ['Vận hành', ['tong-quan', 'tai-khoan', 'phan-cong', 'don-hang', 'lich-hen']],
              [
                'Dịch vụ & nội dung',
                ['goi-tap', 'bai-tap', 'nhom-co', 'giao-an-mau', 'tai-lieu-tu-van'],
              ],
              ['Cá nhân', ['ho-so']],
            ]
          : this.vaiTro === 'HUAN_LUYEN_VIEN'
            ? [
                ['Huấn luyện', ['tong-quan', 'lich-hen', 'khung-gio', 'hoc-vien']],
                ['Nội dung & tương tác', ['giao-an-mau', 'bai-tap', 'tin-nhan']],
                ['Cá nhân', ['ho-so']],
              ]
            : [
                ['Tập luyện', ['tong-quan', 'ke-hoach', 'lich-tap', 'chi-so-co-the', 'lich-hen']],
                ['Dịch vụ & cố vấn', ['goi-cua-toi', 'don-hang', 'tin-nhan', 'chatbot']],
                ['Khám phá & cá nhân', ['bai-tap', 'goi-tap', 'faq', 'ho-so']],
              ]
      return phanNhom.map(([ten, duongDan]) => ({
        ten,
        cacMuc: duongDan.flatMap((phan) =>
          this.danhSach.filter((m) => m.duongDan.split('/').at(-1) === phan),
        ),
      }))
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
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 100vh;
  box-sizing: border-box;
}

/* Brand header chuẩn Stitch */
.sidebar-brand-box {
  padding: 16px 14px 14px;
  border-bottom: 1px solid var(--mau-vien);
}
.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: var(--mau-chu);
}
.brand-icon-box {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--mau-chinh);
  color: var(--mau-tren-chinh);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}
.brand-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}
.brand-title {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: var(--mau-chu);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.brand-badge {
  display: inline-flex;
  align-self: flex-start;
  padding: 1px 6px;
  border-radius: 4px;
  background: var(--mau-the-sub);
  color: var(--mau-phu);
  font-size: 10.5px;
  font-weight: 600;
}

/* Navigation items chuẩn Stitch */
.sidebar-navigation {
  flex: 1;
  padding: 12px 10px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  overflow-y: auto;
}
.sidebar-group {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.sidebar-caption {
  display: block;
  padding: 4px 10px;
  margin: 0;
  font-size: 10.5px;
  font-weight: 700;
  color: var(--mau-phu);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.sidebar-navigation a {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 40px;
  padding: 8px 12px;
  color: var(--mau-phu);
  border-radius: 8px;
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 500;
  transition: all 0.15s ease-in-out;
}
.sidebar-navigation i {
  width: 20px;
  flex: 0 0 20px;
  font-size: 1.1rem;
  text-align: center;
}
.sidebar-navigation a:hover {
  color: var(--mau-chu);
  background: var(--mau-the-hover);
}
.sidebar-navigation a.is-active {
  color: var(--mau-chinh);
  background: var(--mau-chinh-nhat);
  font-weight: 600;
}
:root[data-theme='dark'] .sidebar-navigation a.is-active {
  color: var(--mau-tren-chinh);
  background: var(--mau-chinh);
  font-weight: 700;
}
.sidebar-brand:focus-visible,
.sidebar-navigation a:focus-visible,
.sidebar-support-link:focus-visible {
  outline: 2px solid var(--mau-chinh);
  outline-offset: 2px;
}
.sidebar-unread {
  margin-left: auto;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 700;
  background: var(--mau-chinh);
  color: var(--mau-tren-chinh);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Footer link hỗ trợ chuẩn Stitch */
.sidebar-footer-box {
  padding: 10px;
  border-top: 1px solid var(--mau-vien);
}
.sidebar-support-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  min-height: 40px;
  border-radius: 8px;
  color: var(--mau-phu);
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.15s ease-in-out;
}
.sidebar-support-link:hover {
  color: var(--mau-chu);
  background: var(--mau-the-hover);
}
.sidebar-support-link i {
  font-size: 1.1rem;
}

/* Thu gọn sidebar */
.is-collapsed .sidebar-brand-box {
  padding: 16px 8px;
  display: flex;
  justify-content: center;
}
.is-collapsed .sidebar-navigation a {
  justify-content: center;
  padding: 8px 0;
}
.is-collapsed .sidebar-unread {
  position: absolute;
  right: 4px;
  top: 4px;
}
</style>
