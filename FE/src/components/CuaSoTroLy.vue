<template>
  <div
    v-if="duocHienThi"
    v-show="$route.path !== '/khach-hang/chatbot'"
    class="ai-widget"
    @keydown.esc.stop="dong"
  >
    <Transition name="ai-window">
      <section
        ref="cuaSo"
        v-show="mo"
        id="tr0ond-ai-window"
        class="ai-floating-window"
        :class="{ 'is-expanded': phongTo, 'is-dragging': keo !== null }"
        :style="kieuViTri"
        role="dialog"
        aria-label="Tr0ond AI — trợ lý tập luyện"
        :aria-modal="false"
      >
        <header
          ref="thanhTieuDe"
          class="ai-window-header"
          @pointerdown="batDauKeo"
          @pointermove="keoCuaSo"
          @pointerup="dungKeo"
          @pointercancel="dungKeo"
          @lostpointercapture="dungKeo"
        >
          <MascotTroLy :chuyen-dong="chuyenDong" />
          <button
            type="button"
            class="ai-window-title"
            aria-label="Di chuyển cửa sổ chatbot"
            title="Kéo để di chuyển"
            :aria-expanded="bangDiChuyen"
            @click="batTatDiChuyen"
            @keydown.left.prevent="dichChuyen(-20, 0)"
            @keydown.right.prevent="dichChuyen(20, 0)"
            @keydown.up.prevent="dichChuyen(0, -20)"
            @keydown.down.prevent="dichChuyen(0, 20)"
            @keydown.home.prevent="veGoc"
          >
            <strong>Tr0ond AI <i class="bi bi-grip-vertical" aria-hidden="true"></i></strong
            ><small>{{
              laKhachHang ? 'Tập luyện theo cách của bạn' : 'Cùng bạn bắt đầu tập luyện'
            }}</small>
          </button>
          <button
            type="button"
            :aria-label="chuyenDong ? 'Dừng chuyển động mascot' : 'Bật chuyển động mascot'"
            :aria-pressed="chuyenDong"
            @click="chuyenDong = !chuyenDong"
          >
            <i :class="chuyenDong ? 'bi bi-pause' : 'bi bi-play'" aria-hidden="true"></i>
          </button>
          <button
            type="button"
            :aria-label="phongTo ? 'Thu nhỏ cửa sổ' : 'Phóng to cửa sổ'"
            @click="phongTo = !phongTo"
          >
            <i
              :class="phongTo ? 'bi bi-arrows-angle-contract' : 'bi bi-arrows-angle-expand'"
              aria-hidden="true"
            ></i>
          </button>
          <button type="button" aria-label="Thu gọn chatbot" @click="dong">
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
          </button>
        </header>
        <div v-if="bangDiChuyen" class="ai-position-tools" role="group" aria-label="Vị trí chatbot">
          <button type="button" aria-label="Di chuyển sang trái" @click="dichChuyen(-40, 0)">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
          </button>
          <button type="button" aria-label="Di chuyển lên trên" @click="dichChuyen(0, -40)">
            <i class="bi bi-arrow-up" aria-hidden="true"></i>
          </button>
          <button type="button" aria-label="Di chuyển xuống dưới" @click="dichChuyen(0, 40)">
            <i class="bi bi-arrow-down" aria-hidden="true"></i>
          </button>
          <button type="button" aria-label="Di chuyển sang phải" @click="dichChuyen(40, 0)">
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </button>
          <button type="button" class="ai-position-reset" @click="veGoc">Về góc màn hình</button>
        </div>
        <KhungTroLy
          v-if="laKhachHang && daMo"
          :key="xacThuc.taiKhoan.id"
          ref="khung"
          thu-gon
          :hoat-dong="mo"
          :mascot-chay="chuyenDong"
        />
        <div v-else class="ai-guest-welcome">
          <MascotTroLy :chuyen-dong="chuyenDong" />
          <h2>Chào bạn, tôi là Tr0ond AI</h2>
          <p>Hỏi về tập luyện, khám phá bài tập và nhờ AI soạn giáo án riêng.</p>
          <RouterLink to="/dang-nhap" class="btn btn-primary" @click="dong"
            >Đăng nhập để trò chuyện</RouterLink
          >
          <RouterLink to="/faq" @click="dong"
            >Câu hỏi & tài liệu miễn phí <i class="bi bi-arrow-up-right" aria-hidden="true"></i
          ></RouterLink>
          <small>Chatbot cần gói có quyền AI. Tự tạo giáo án thủ công vẫn miễn phí.</small>
        </div>
      </section>
    </Transition>
    <button
      ref="nutMo"
      type="button"
      class="ai-mascot-launcher"
      :class="{ 'is-dragging': keoMascot !== null }"
      :style="kieuViTriMascot"
      title="Bấm để trò chuyện, kéo để di chuyển. Chuột phải để chọn vị trí."
      :aria-expanded="mo"
      aria-controls="tr0ond-ai-window"
      :aria-label="mo ? 'Thu gọn Tr0ond AI' : 'Mở Tr0ond AI'"
      @click="doiCuaSo"
      @pointerdown="batDauKeoMascot"
      @pointermove="keoConMascot"
      @pointerup="dungKeoMascot"
      @pointercancel="huyKeoMascot"
      @lostpointercapture="dungKeoMascot"
      @contextmenu.prevent="bangDiChuyenMascot = !bangDiChuyenMascot"
      @keydown.left.prevent="dichChuyenMascot(-20, 0)"
      @keydown.right.prevent="dichChuyenMascot(20, 0)"
      @keydown.up.prevent="dichChuyenMascot(0, -20)"
      @keydown.down.prevent="dichChuyenMascot(0, 20)"
      @keydown.home.prevent="veGocMascot"
      @keydown.shift.f10.prevent="bangDiChuyenMascot = !bangDiChuyenMascot"
    >
      <i v-if="mo" class="bi bi-x-lg" aria-hidden="true"></i>
      <MascotTroLy v-else :chuyen-dong="chuyenDong" />
      <span v-if="!mo">Hỏi Tr0ond AI</span>
    </button>
    <div
      v-if="bangDiChuyenMascot"
      class="ai-position-tools ai-mascot-position-tools"
      :style="kieuBangMascot"
      role="group"
      aria-label="Vị trí mascot"
    >
      <button type="button" aria-label="Mascot sang trái" @click="dichChuyenMascot(-40, 0)">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
      </button>
      <button type="button" aria-label="Mascot lên trên" @click="dichChuyenMascot(0, -40)">
        <i class="bi bi-arrow-up" aria-hidden="true"></i>
      </button>
      <button type="button" aria-label="Mascot xuống dưới" @click="dichChuyenMascot(0, 40)">
        <i class="bi bi-arrow-down" aria-hidden="true"></i>
      </button>
      <button type="button" aria-label="Mascot sang phải" @click="dichChuyenMascot(40, 0)">
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
      </button>
      <button type="button" class="ai-position-reset" @click="veGocMascot">Về góc màn hình</button>
      <button type="button" aria-label="Đóng điều khiển mascot" @click="bangDiChuyenMascot = false">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
      </button>
    </div>
  </div>
</template>
<script>
import { useXacThucStore } from '../stores/xacThuc'
import KhungTroLy from './KhungTroLy.vue'
import MascotTroLy from './MascotTroLy.vue'
import '../assets/chatbot.css'
export default {
  name: 'CuaSoTroLy',
  components: { KhungTroLy, MascotTroLy },
  data() {
    return {
      mo: false,
      daMo: false,
      phongTo: false,
      chuyenDong: true,
      viTri: null,
      khungManHinh: null,
      keo: null,
      vuaKeo: false,
      bangDiChuyen: false,
      viTriMascot: null,
      keoMascot: null,
      vuaKeoMascot: false,
      bangDiChuyenMascot: false,
    }
  },
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
    laKhachHang() {
      return this.xacThuc.taiKhoan?.vai_tro === 'KHACH_HANG'
    },
    duocHienThi() {
      return this.xacThuc.daKhoiTao && (!this.xacThuc.taiKhoan || this.laKhachHang)
    },
    kieuViTri() {
      if (!this.viTri) return undefined
      return {
        position: 'fixed',
        left: `${this.viTri.x}px`,
        top: `${this.viTri.y}px`,
        right: 'auto',
        bottom: 'auto',
        width: `${Math.min(this.phongTo ? 760 : 440, this.khungManHinh.rong - 24)}px`,
        maxHeight: `${Math.max(0, this.khungManHinh.cao - 24)}px`,
      }
    },
    kieuViTriMascot() {
      if (!this.viTriMascot) return undefined
      return {
        position: 'fixed',
        left: `${this.viTriMascot.x}px`,
        top: `${this.viTriMascot.y}px`,
        right: 'auto',
        bottom: 'auto',
        margin: 0,
      }
    },
    kieuBangMascot() {
      // Đọc hình học khi mở/di chuyển để bảng điều khiển không tràn mép màn hình.
      if (!this.bangDiChuyenMascot) return undefined
      const hop = this.$refs.nutMo?.getBoundingClientRect()
      if (!hop) return undefined
      const { rong, cao } = this.khungManHinh ?? this.layKhungManHinh()
      const x = this.viTriMascot?.x ?? hop.left
      const y = this.viTriMascot?.y ?? hop.top
      return {
        left: `${Math.max(12, Math.min(x, rong - 264 - 12))}px`,
        top: `${Math.max(12, Math.min(y >= 132 ? y - 120 : y + hop.height + 8, cao - 120))}px`,
      }
    },
  },
  watch: {
    'xacThuc.taiKhoan.id'() {
      this.dungKeo()
      this.huyKeoMascot()
      this.mo = false
      this.daMo = false
      this.phongTo = false
      this.viTri = null
      this.bangDiChuyen = false
      this.viTriMascot = null
      this.bangDiChuyenMascot = false
    },
    '$route.path'() {
      if (this.$route.path === '/khach-hang/chatbot') this.dong()
    },
    phongTo() {
      this.dungKeo()
      this.$nextTick(() => this.giuTrongManHinh())
    },
  },
  mounted() {
    window.addEventListener('resize', this.capNhatKichThuoc)
    window.visualViewport?.addEventListener('resize', this.capNhatKichThuoc)
  },
  beforeUnmount() {
    this.dungKeo()
    this.huyKeoMascot()
    window.removeEventListener('resize', this.capNhatKichThuoc)
    window.visualViewport?.removeEventListener('resize', this.capNhatKichThuoc)
  },
  methods: {
    layKhungManHinh() {
      const rong = Math.min(
        document.documentElement.clientWidth,
        window.visualViewport?.width ?? window.innerWidth,
      )
      const cao = Math.min(window.innerHeight, window.visualViewport?.height ?? window.innerHeight)
      return { rong, cao }
    },
    datViTri(x, y) {
      const hop = this.$refs.cuaSo?.getBoundingClientRect()
      if (!hop) return
      const { rong, cao } = this.layKhungManHinh()
      this.khungManHinh = { rong, cao }
      const rongCuaSo = Math.min(this.phongTo ? 760 : 440, rong - 24)
      const caoCuaSo = Math.min(hop.height, cao - 24)
      this.viTri = {
        x: Math.max(12, Math.min(x, rong - rongCuaSo - 12)),
        y: Math.max(12, Math.min(y, cao - caoCuaSo - 12)),
      }
    },
    batDauKeo(e) {
      const nut = e.target.closest('button')
      if (
        !e.isPrimary ||
        e.button !== 0 ||
        this.keo ||
        (nut && !nut.classList.contains('ai-window-title'))
      )
        return
      const hop = this.$refs.cuaSo.getBoundingClientRect()
      this.vuaKeo = false
      const phanTu = nut || e.currentTarget
      this.keo = {
        id: e.pointerId,
        x: e.clientX,
        y: e.clientY,
        trai: hop.left,
        tren: hop.top,
        phanTu,
      }
      phanTu.setPointerCapture(e.pointerId)
    },
    keoCuaSo(e) {
      if (!this.keo || e.pointerId !== this.keo.id) return
      const dx = e.clientX - this.keo.x
      const dy = e.clientY - this.keo.y
      if (!this.vuaKeo && Math.hypot(dx, dy) < 4) return
      this.vuaKeo = true
      this.bangDiChuyen = false
      this.datViTri(this.keo.trai + dx, this.keo.tren + dy)
    },
    dungKeo(e) {
      if (!this.keo || (e && e.pointerId !== this.keo.id)) return
      const { id, phanTu } = this.keo
      this.keo = null
      if (phanTu?.hasPointerCapture(id)) phanTu.releasePointerCapture(id)
    },
    batTatDiChuyen() {
      if (this.vuaKeo) {
        this.vuaKeo = false
        return
      }
      this.bangDiChuyen = !this.bangDiChuyen
    },
    dichChuyen(dx, dy) {
      const hop = this.$refs.cuaSo.getBoundingClientRect()
      this.datViTri(hop.left + dx, hop.top + dy)
    },
    giuTrongManHinh() {
      this.khungManHinh = this.layKhungManHinh()
      if (this.mo && this.viTri) this.datViTri(this.viTri.x, this.viTri.y)
      if (this.viTriMascot) this.datViTriMascot(this.viTriMascot.x, this.viTriMascot.y)
    },
    capNhatKichThuoc() {
      this.giuTrongManHinh()
      // Đo lại sau cập nhật Vue để tính cả thanh cuộn/bố cục responsive mới.
      this.$nextTick(() => this.giuTrongManHinh())
    },
    veGoc() {
      this.dungKeo()
      this.viTri = null
      this.bangDiChuyen = false
    },
    doiCuaSo() {
      // Pointerup sau thao tác kéo sinh click; chỉ click thông thường mới mở chat.
      if (this.vuaKeoMascot) {
        this.vuaKeoMascot = false
        return
      }
      this.bangDiChuyenMascot = false
      if (this.mo) {
        this.dong()
        return
      }
      this.daMo = true
      this.mo = true
      this.$nextTick(() => {
        if (this.viTriMascot && !this.viTri) {
          const nut = this.$refs.nutMo.getBoundingClientRect()
          const hop = this.$refs.cuaSo.getBoundingClientRect()
          this.datViTri(nut.right - hop.width, nut.top - hop.height - 12)
        }
        this.giuTrongManHinh()
        this.$refs.khung?.$refs.oNhap?.focus()
      })
    },
    dong() {
      this.dungKeo()
      this.huyKeoMascot()
      this.mo = false
      this.bangDiChuyen = false
      this.bangDiChuyenMascot = false
      this.$nextTick(() => {
        this.giuTrongManHinh()
        this.$refs.nutMo?.focus()
      })
    },
    datViTriMascot(x, y) {
      const hop = this.$refs.nutMo?.getBoundingClientRect()
      if (!hop) return
      const { rong, cao } = this.layKhungManHinh()
      this.viTriMascot = {
        x: Math.max(12, Math.min(x, rong - hop.width - 12)),
        y: Math.max(12, Math.min(y, cao - hop.height - 12)),
      }
    },
    batDauKeoMascot(e) {
      if (!e.isPrimary || e.button !== 0 || this.keoMascot) return
      const hop = this.$refs.nutMo.getBoundingClientRect()
      this.vuaKeoMascot = false
      this.keoMascot = {
        id: e.pointerId,
        x: e.clientX,
        y: e.clientY,
        trai: hop.left,
        tren: hop.top,
        phanTu: e.currentTarget,
      }
      e.currentTarget.setPointerCapture(e.pointerId)
    },
    keoConMascot(e) {
      if (!this.keoMascot || e.pointerId !== this.keoMascot.id) return
      const dx = e.clientX - this.keoMascot.x
      const dy = e.clientY - this.keoMascot.y
      if (!this.vuaKeoMascot && Math.hypot(dx, dy) < 4) return
      this.vuaKeoMascot = true
      this.bangDiChuyenMascot = false
      this.datViTriMascot(this.keoMascot.trai + dx, this.keoMascot.tren + dy)
    },
    dungKeoMascot(e) {
      if (!this.keoMascot || (e && e.pointerId !== this.keoMascot.id)) return
      const { id, phanTu } = this.keoMascot
      this.keoMascot = null
      if (phanTu.hasPointerCapture(id)) phanTu.releasePointerCapture(id)
    },
    huyKeoMascot(e) {
      if (e && e.pointerId !== this.keoMascot?.id) return
      this.dungKeoMascot(e)
      this.vuaKeoMascot = false
    },
    dichChuyenMascot(dx, dy) {
      const hop = this.$refs.nutMo.getBoundingClientRect()
      this.datViTriMascot(hop.left + dx, hop.top + dy)
    },
    veGocMascot() {
      this.huyKeoMascot()
      this.viTriMascot = null
      this.bangDiChuyenMascot = false
    },
  },
}
</script>
