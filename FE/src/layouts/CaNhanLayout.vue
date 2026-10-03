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
        <nav aria-label="Tài khoản" class="account-nav">
          <NutChuyenChuDe kich-thuoc="lg" />
          <ThongBaoHeader v-if="xacThuc.taiKhoan" ref="inbox" @mo-bang="dangMoTaiKhoan = false" />
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

      <main class="member-content">
        <div v-if="thongBao" class="alert alert-danger" role="alert">{{ thongBao }}</div>
        <slot />
      </main>
      <footer class="member-footer">
        <span>Hệ thống huấn luyện thể hình cá nhân trực tuyến</span>
        <span class="small">Hành trình tập luyện của bạn · Phiên bản 2.0</span>
      </footer>
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
    doiMenu() {
      this.dongTaiKhoan()
      this.$refs.inbox?.dong()
      if (!this.laDiDong) return this.dieuHuong.doiTrangThai()
      this.$refs.menuDiDong?.showModal()
      this.dangMoMenu = true
    },
    dongMenuDiDong() {
      const daMo = this.dangMoMenu
      this.dangMoMenu = false
      if (this.$refs.menuDiDong?.open) this.$refs.menuDiDong.close()
      if (daMo) this.$refs.nutMenu?.focus()
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
  --sidebar-width: 264px;
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
  height: 80px;
  flex: 0 0 80px;
  padding: 16px 32px;
  gap: 16px;
  flex-wrap: nowrap;
}
.member-with-sidebar .account-nav {
  gap: 12px;
  width: auto;
  justify-content: flex-end;
}
.member-with-sidebar .member-content {
  max-width: none;
  padding: 32px;
  min-width: 0;
}
.member-with-sidebar.chat-layout .member-content {
  width: calc(100% - 64px);
  padding-inline: 0;
}
.member-with-sidebar .member-footer {
  padding: 24px 32px;
  flex-wrap: wrap;
}
.shell-icon,
.avatar-toggle {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  padding: 0;
  border: 1px solid var(--mau-vien);
  border-radius: 12px;
  background: transparent;
  color: var(--mau-chu);
  cursor: pointer;
  font-size: 1.2rem;
}
.shell-icon:hover,
.avatar-toggle:hover {
  background: var(--mau-the-hover);
}
.shell-icon:focus-visible,
.avatar-toggle:focus-visible,
.account-panel a:focus-visible,
.account-panel button:focus-visible {
  outline: 2px solid var(--mau-chinh);
  outline-offset: 3px;
}
.avatar-toggle {
  border-radius: 50%;
}
.avatar-toggle .user-avatar-circle {
  width: 38px;
  height: 38px;
  font-size: 1rem;
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
  border-radius: 16px;
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
    height: 72px;
    flex-basis: 72px;
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
