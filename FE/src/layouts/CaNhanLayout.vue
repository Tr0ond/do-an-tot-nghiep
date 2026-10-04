<template>
  <div class="member-shell member-with-sidebar" :class="{ 'sidebar-collapsed': dieuHuong.thuGon }">
    <aside id="member-sidebar" class="member-sidebar">
      <MenuCaNhan :vai-tro="vaiTro" :nhan-vai-tro="nhanVaiTro" :thu-gon="dieuHuong.thuGon" />
    </aside>
    <dialog
      ref="menuDiDong"
      class="sidebar-drawer"
      aria-label="Menu điều hướng"
      @close="dongMenuDiDong"
      @click="bamNenMenu"
    >
      <button
        type="button"
        class="shell-icon drawer-close"
        aria-label="Đóng menu"
        autofocus
        @click="dongMenuDiDong"
      >
        <i class="bi bi-x-lg" aria-hidden="true"></i>
      </button>
      <MenuCaNhan :vai-tro="vaiTro" :nhan-vai-tro="nhanVaiTro" @dieu-huong="dongMenuDiDong" />
    </dialog>

    <div class="member-stage">
      <header class="member-header">
        <button
          ref="nutMenu"
          type="button"
          class="shell-icon sidebar-toggle"
          :aria-label="
            laDiDong ? 'Mở menu điều hướng' : dieuHuong.thuGon ? 'Mở rộng menu' : 'Thu gọn menu'
          "
          :aria-expanded="laDiDong ? dangMoMenu : !dieuHuong.thuGon"
          :aria-controls="laDiDong ? undefined : 'member-sidebar'"
          :title="laDiDong ? 'Mở menu' : dieuHuong.thuGon ? 'Mở rộng menu' : 'Thu gọn menu'"
          @click="doiMenu"
        >
          <i
            :class="
              laDiDong
                ? 'bi bi-list'
                : dieuHuong.thuGon
                  ? 'bi bi-layout-sidebar'
                  : 'bi bi-layout-sidebar-inset'
            "
            aria-hidden="true"
          ></i>
        </button>
        <nav class="shell-breadcrumb" aria-label="Vị trí hiện tại">
          <RouterLink :to="trangTongQuan" class="breadcrumb-root">{{ nhanVaiTro }}</RouterLink>
          <span class="breadcrumb-divider" aria-hidden="true">/</span>
          <span class="breadcrumb-current" aria-current="page">{{ tenManHinh }}</span>
        </nav>
        <nav aria-label="Tài khoản" class="account-nav">
          <NutChuyenChuDe kich-thuoc="md" />
          <ThongBaoHeader v-if="xacThuc.taiKhoan" ref="inbox" @mo-bang="dangMoTaiKhoan = false" />
          <div class="header-divider" aria-hidden="true"></div>
          <div
            v-if="xacThuc.taiKhoan"
            ref="khuVucTaiKhoan"
            class="account-dropdown"
            @focusout="matFocusTaiKhoan"
          >
            <button
              ref="nutTaiKhoan"
              type="button"
              class="avatar-toggle"
              :aria-label="`Tài khoản của ${xacThuc.taiKhoan.ho_ten}`"
              :aria-expanded="dangMoTaiKhoan"
              aria-controls="member-account"
              title="Tài khoản"
              @click="doiTaiKhoan"
            >
              <span class="user-avatar-circle">{{ chuCaiDau }}</span>
              <span class="shell-user-copy">
                <strong>{{ xacThuc.taiKhoan.ho_ten }}</strong>
              </span>
              <i class="bi bi-chevron-down ms-1 text-muted small" aria-hidden="true"></i>
            </button>
            <section
              v-if="dangMoTaiKhoan"
              id="member-account"
              class="account-panel"
              aria-label="Tài khoản của tôi"
            >
              <div class="account-summary">
                <strong>{{ xacThuc.taiKhoan.ho_ten }}</strong>
                <span>{{ nhanVaiTro }}</span>
              </div>
              <RouterLink ref="hoSo" :to="duongDanHoSo" @click="dongTaiKhoan()">
                <i class="bi bi-person" aria-hidden="true"></i> Hồ sơ của tôi
              </RouterLink>
              <button type="button" :disabled="dangXuat" @click="thoat">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                {{ dangXuat ? 'Đang thoát…' : 'Đăng xuất' }}
              </button>
            </section>
          </div>
        </nav>
      </header>

      <main id="noi-dung-chinh" class="member-content stitch-workspace" :data-screen="loaiManHinh">
        <div v-if="thongBao" class="alert alert-danger" role="alert">{{ thongBao }}</div>
        <slot />
      </main>
      <footer class="member-footer">
        <span>Hệ thống huấn luyện thể hình cá nhân trực tuyến</span>
        <span class="small">Tr0ond Fitness</span>
      </footer>
      <nav class="mobile-navigation" aria-label="Điều hướng nhanh">
        <RouterLink
          v-for="muc in menuNhanh"
          :key="muc.to"
          :to="muc.to"
          :aria-current="$route.path.startsWith(muc.to) ? 'page' : undefined"
        >
          <i :class="`bi bi-${muc.icon}`" aria-hidden="true"></i><span>{{ muc.ten }}</span>
        </RouterLink>
        <button
          type="button"
          aria-label="Xem tất cả mục điều hướng"
          :aria-expanded="dangMoMenu"
          @click="doiMenu"
        >
          <i class="bi bi-grid" aria-hidden="true"></i><span>Thêm</span>
        </button>
      </nav>
    </div>
  </div>
</template>

<script>
import { useXacThucStore } from '../stores/xacThuc'
import { useDieuHuongStore } from '../stores/dieuHuong'
import { layLoiApi } from '../utils/loiApi'
import NutChuyenChuDe from '../components/NutChuyenChuDe.vue'
import ThongBaoHeader from '../components/ThongBaoHeader.vue'
import MenuCaNhan from '../components/MenuCaNhan.vue'

export default {
  name: 'CaNhanLayout',
  components: { NutChuyenChuDe, ThongBaoHeader, MenuCaNhan },
  data() {
    return {
      dangXuat: false,
      thongBao: '',
      dangMoTaiKhoan: false,
      dangMoMenu: false,
      nutMoMenu: null,
      laDiDong: false,
    }
  },
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
    dieuHuong() {
      return useDieuHuongStore()
    },
    vaiTro() {
      return this.xacThuc.taiKhoan?.vai_tro || ''
    },
    nhanVaiTro() {
      return (
        { KHACH_HANG: 'Khách hàng', HUAN_LUYEN_VIEN: 'Huấn luyện viên', ADMIN: 'Quản trị viên' }[
          this.vaiTro
        ] || 'Thành viên'
      )
    },
    duongDanHoSo() {
      return (
        { KHACH_HANG: '/khach-hang/ho-so', HUAN_LUYEN_VIEN: '/pt/ho-so', ADMIN: '/admin/ho-so' }[
          this.vaiTro
        ] || '/dang-nhap'
      )
    },
    chuCaiDau() {
      return (this.xacThuc.taiKhoan?.ho_ten || 'U').trim().charAt(0).toUpperCase()
    },
    trangTongQuan() {
      return `${this.duongDanHoSo.replace('/ho-so', '')}/tong-quan`
    },
    loaiManHinh() {
      const p = this.$route.path
      if (/\/(them|sua)$/.test(p)) return 'editor'
      if (/\/\d+$/.test(p) && !p.includes('tin-nhan')) return 'detail'
      if (p.includes('tong-quan')) return 'dashboard'
      if (p.includes('tin-nhan') || p.includes('chatbot')) return 'conversation'
      if (p.includes('ho-so')) return 'profile'
      return 'collection'
    },
    tenManHinh() {
      const nhan = {
        'tong-quan': 'Tổng quan',
        'ho-so': 'Hồ sơ cá nhân',
        'chi-so-co-the': 'Chỉ số cơ thể',
        'tin-nhan': 'Tin nhắn',
        chatbot: 'Tr0ond AI',
        'tai-lieu-tu-van': 'Tài liệu & AI',
        'lich-tap': 'Lịch & nhật ký',
        'lich-hen': 'Lịch hẹn',
        'dat-lich': 'Đặt lịch',
        'khung-gio': 'Khung giờ',
        'ke-hoach': 'Giáo án',
        'giao-an-mau': 'Giáo án mẫu',
        'hoc-vien': 'Học viên',
        'goi-cua-toi': 'Gói của tôi',
        'don-hang': 'Đơn hàng',
        'tai-khoan': 'Tài khoản',
        'phan-cong': 'Phân công PT',
        'nhom-co': 'Nhóm cơ',
        'bai-tap': 'Bài tập',
        'goi-tap': 'Gói tập',
        faq: 'Câu hỏi & tài liệu',
      }
      return (
        this.$route.path
          .split('/')
          .reverse()
          .map((p) => nhan[p])
          .find(Boolean) || 'Tr0ond Fitness'
      )
    },
    menuNhanh() {
      const goc = this.duongDanHoSo.replace('/ho-so', '')
      const cacMuc =
        this.vaiTro === 'ADMIN'
          ? [
              ['tong-quan', 'Tổng quan', 'grid-1x2'],
              ['don-hang', 'Đơn hàng', 'receipt'],
              ['phan-cong', 'Phân công', 'person-check'],
              ['tai-khoan', 'Tài khoản', 'people'],
            ]
          : this.vaiTro === 'HUAN_LUYEN_VIEN'
            ? [
                ['tong-quan', 'Tổng quan', 'grid-1x2'],
                ['lich-hen', 'Lịch hẹn', 'calendar3'],
                ['hoc-vien', 'Học viên', 'people'],
                ['tin-nhan', 'Tin nhắn', 'chat-left-text'],
              ]
            : [
                ['tong-quan', 'Tổng quan', 'grid-1x2'],
                ['lich-tap', 'Lịch tập', 'calendar3'],
                ['ke-hoach', 'Giáo án', 'journal-check'],
                ['tin-nhan', 'Tin nhắn', 'chat-left-text'],
              ]
      return cacMuc.map(([duong, ten, icon]) => ({ to: `${goc}/${duong}`, ten, icon }))
    },
  },
  watch: {
    '$route.fullPath'() {
      this.dongMenuDiDong()
      this.dongTaiKhoan()
    },
    'xacThuc.taiKhoan.id'() {
      this.dongMenuDiDong()
      this.dongTaiKhoan()
    },
  },
  mounted() {
    this.dieuHuong.khoiTao()
    this.theoDoiKichThuoc()
    window.addEventListener('resize', this.theoDoiKichThuoc)
    document.addEventListener('pointerdown', this.bamBenNgoai)
    document.addEventListener('keydown', this.bamPhim)
  },
  beforeUnmount() {
    this.$refs.menuDiDong?.close()
    window.removeEventListener('resize', this.theoDoiKichThuoc)
    document.removeEventListener('pointerdown', this.bamBenNgoai)
    document.removeEventListener('keydown', this.bamPhim)
  },
  methods: {
    theoDoiKichThuoc() {
      this.laDiDong = window.matchMedia('(max-width: 1023px)').matches
      if (!this.laDiDong) this.dongMenuDiDong()
    },
    doiMenu(suKien) {
      this.dongTaiKhoan()
      this.$refs.inbox?.dong()
      if (!this.laDiDong) return this.dieuHuong.doiTrangThai()
      this.nutMoMenu = suKien?.currentTarget || this.$refs.nutMenu
      this.$refs.menuDiDong?.showModal()
      this.dangMoMenu = true
    },
    dongMenuDiDong() {
      const daMo = this.dangMoMenu
      this.dangMoMenu = false
      if (this.$refs.menuDiDong?.open) this.$refs.menuDiDong.close()
      if (daMo) (this.nutMoMenu || this.$refs.nutMenu)?.focus()
    },
    bamNenMenu(suKien) {
      if (suKien.target !== this.$refs.menuDiDong) return
      const khung = this.$refs.menuDiDong.getBoundingClientRect()
      if (
        suKien.clientX < khung.left ||
        suKien.clientX > khung.right ||
        suKien.clientY < khung.top ||
        suKien.clientY > khung.bottom
      )
        this.dongMenuDiDong()
    },
    async doiTaiKhoan() {
      if (this.dangMoTaiKhoan) return this.dongTaiKhoan()
      this.$refs.inbox?.dong()
      this.dangMoTaiKhoan = true
      await this.$nextTick()
      this.$refs.hoSo?.$el?.focus()
    },
    dongTaiKhoan(traFocus = false) {
      this.dangMoTaiKhoan = false
      if (traFocus) this.$refs.nutTaiKhoan?.focus()
    },
    matFocusTaiKhoan(suKien) {
      if (!this.$refs.khuVucTaiKhoan?.contains(suKien.relatedTarget)) this.dongTaiKhoan()
    },
    bamBenNgoai(suKien) {
      if (!this.$refs.khuVucTaiKhoan?.contains(suKien.target)) this.dongTaiKhoan()
    },
    bamPhim(suKien) {
      if (suKien.key === 'Escape' && this.dangMoTaiKhoan) {
        suKien.preventDefault()
        this.dongTaiKhoan(true)
      }
    },
    async thoat() {
      if (this.dangXuat) return
      this.dangXuat = true
      this.thongBao = ''
      try {
        await this.xacThuc.dangXuat()
        await this.$router.replace('/dang-nhap')
      } catch (loi) {
        this.thongBao = layLoiApi(loi).thongBao
        this.dongTaiKhoan(true)
      } finally {
        this.dangXuat = false
      }
    },
  },
}
</script>

<style scoped>
.member-with-sidebar {
  --sidebar-width: 232px;
}
.member-with-sidebar.sidebar-collapsed {
  --sidebar-width: 84px;
}
.member-sidebar {
  position: fixed;
  inset: 0 auto 0 0;
  width: var(--sidebar-width);
  overflow-y: auto;
  background: var(--mau-header-bg);
  border-right: 1px solid var(--mau-vien);
  z-index: 110;
}
.member-stage {
  margin-left: var(--sidebar-width);
  min-width: 0;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}
.member-with-sidebar .member-header {
  height: 64px;
  flex: 0 0 64px;
  padding: 0 24px;
  gap: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--mau-the);
  border-bottom: 1px solid var(--mau-vien);
  position: sticky;
  top: 0;
  z-index: 100;
  flex-wrap: nowrap;
}
.shell-breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13.5px;
}
.shell-breadcrumb .breadcrumb-root {
  color: var(--mau-phu);
  text-decoration: none;
  font-weight: 500;
  transition: color 0.15s ease;
}
.shell-breadcrumb .breadcrumb-root:hover {
  color: var(--mau-chu);
}
.shell-breadcrumb .breadcrumb-divider {
  color: var(--mau-vien);
  font-size: 13px;
  user-select: none;
}
.shell-breadcrumb .breadcrumb-current {
  color: var(--mau-chu);
  font-weight: 600;
}
.member-with-sidebar .account-nav {
  margin-left: auto;
  gap: 10px;
  width: auto;
  display: flex;
  align-items: center;
  justify-content: flex-end;
}
.header-divider {
  width: 1px;
  height: 20px;
  background: var(--mau-vien);
  margin: 0 4px;
}
.member-with-sidebar .member-content {
  flex: 1;
  max-width: 1240px;
  margin: 0 auto;
  width: 100%;
  padding: 28px 24px;
  min-width: 0;
  box-sizing: border-box;
}
.member-with-sidebar.chat-layout .member-content {
  max-width: 100%;
  width: 100%;
  padding: 0;
}
.member-with-sidebar .member-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--mau-vien);
  background: var(--mau-nen);
  color: var(--mau-phu);
  font-size: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
}
.shell-icon {
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  padding: 0;
  border: 1px solid var(--mau-vien);
  border-radius: 8px;
  background: transparent;
  color: var(--mau-chu);
  cursor: pointer;
  font-size: 1.1rem;
  transition: all 0.15s ease;
}
.shell-icon:hover {
  background: var(--mau-the-hover);
}
.avatar-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 0;
  background: transparent;
  padding: 4px 6px;
  border-radius: 8px;
  cursor: pointer;
  color: var(--mau-chu);
  transition: all 0.15s ease;
}
.avatar-toggle:hover {
  background: var(--mau-the-hover);
}
.avatar-toggle .user-avatar-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--mau-chinh-nhat);
  color: var(--mau-chinh);
  font-weight: 700;
  font-size: 12px;
  display: grid;
  place-items: center;
}
.shell-user-copy {
  display: flex;
  align-items: center;
}
.shell-user-copy strong {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--mau-chu);
}
.shell-icon:focus-visible,
.avatar-toggle:focus-visible,
.account-panel a:focus-visible,
.account-panel button:focus-visible {
  outline: 2px solid var(--mau-chinh);
  outline-offset: 2px;
}
.account-dropdown {
  position: relative;
}
.account-panel {
  position: absolute;
  right: 0;
  top: calc(100% + 16px);
  width: 260px;
  max-width: calc(100vw - 32px);
  border: 1px solid var(--mau-vien);
  border-radius: 8px;
  background: var(--mau-the, var(--mau-nen));
  box-shadow: var(--bong-nhe);
  padding: 8px;
}
.account-summary {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  border-bottom: 1px solid var(--mau-vien);
  margin-bottom: 8px;
  overflow-wrap: anywhere;
}
.account-summary strong {
  color: var(--mau-chu);
}
.account-summary span {
  font-size: 0.8rem;
  color: var(--mau-phu);
}
.account-panel a,
.account-panel button {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  min-height: 44px;
  padding: 12px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: var(--mau-chu);
  font-size: 0.875rem;
  text-decoration: none;
  text-align: left;
}
.account-panel a:hover,
.account-panel button:hover {
  background: var(--mau-the-hover);
}
.account-panel button:disabled {
  opacity: 0.6;
  cursor: wait;
}
.sidebar-drawer {
  position: fixed;
  inset: 0 auto 0 0;
  margin: 0;
  width: min(300px, calc(100vw - 48px));
  max-width: none;
  height: 100dvh;
  max-height: none;
  border: 0;
  border-right: 1px solid var(--mau-vien);
  padding: 0;
  background: var(--mau-the, var(--mau-nen));
  color: var(--mau-chu);
}
:global(body:has(.sidebar-drawer[open])) {
  overflow: hidden;
}
.sidebar-drawer::backdrop {
  background: rgb(0 0 0 / 55%);
}
.drawer-close {
  position: absolute;
  top: 16px;
  right: 12px;
}
.sidebar-drawer :deep(.sidebar-brand) {
  margin-top: 48px;
}
@media (max-width: 1023px) {
  .member-sidebar {
    display: none;
  }
  .member-stage {
    margin-left: 0;
  }
  .member-with-sidebar .member-header {
    padding-inline: 24px;
    height: 64px;
    flex-basis: 64px;
  }
  .member-with-sidebar .member-content {
    padding: 24px;
  }
  .member-with-sidebar.chat-layout .member-content {
    width: calc(100% - 48px);
    padding-inline: 0;
  }
}
@media (max-width: 600px) {
  .member-with-sidebar .member-header {
    padding-inline: 16px;
    gap: 8px;
  }
  .member-with-sidebar .account-nav {
    gap: 8px;
  }
  .member-with-sidebar .member-content {
    padding: 24px 16px;
  }
  .member-with-sidebar.chat-layout .member-content {
    width: calc(100% - 32px);
    padding-inline: 0;
  }
  .member-with-sidebar .member-footer {
    padding: 24px 16px;
  }
}
</style>
