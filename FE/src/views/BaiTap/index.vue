<template>
  <DanhMucLayout>
    <section class="st-library">
      <header class="st-page-heading">
        <div>
          <span class="st-kicker">THƯ VIỆN VẬN ĐỘNG</span>
          <h1>Thư viện bài tập</h1>
          <p>Kỹ thuật thực hiện, nhóm cơ và dụng cụ tập luyện.</p>
        </div>
        <span class="st-status"
          ><i class="bi bi-unlock" aria-hidden="true"></i> Truy cập miễn phí</span
        >
      </header>
      <form class="st-library-toolbar" @submit.prevent="apDungBoLoc">
        <div class="st-search">
          <i class="bi bi-search" aria-hidden="true"></i
          ><label for="tu-khoa-bai-tap" class="visually-hidden">Tên hoặc mã bài tập</label
          ><input
            id="tu-khoa-bai-tap"
            v-model="boLoc.tu_khoa"
            type="search"
            maxlength="100"
            class="form-control"
            placeholder="Tên bài tập, nhóm cơ hoặc mã…"
          />
        </div>
        <label for="nhom-co"
          >Nhóm cơ<select
            id="nhom-co"
            v-model="boLoc.nhom_co_id"
            class="form-select"
            :disabled="dangTaiBoLoc"
          >
            <option value="">Tất cả nhóm cơ</option>
            <option v-for="nhom in danhSachNhomCo" :key="nhom.id" :value="String(nhom.id)">
              {{ nhom.ten_nhom_co }} ({{ nhom.so_bai_tap }})
            </option>
          </select></label
        >
        <label for="dung-cu"
          >Dụng cụ<select
            id="dung-cu"
            v-model="boLoc.dung_cu_nguon"
            class="form-select"
            :disabled="dangTaiBoLoc"
          >
            <option value="">Tất cả dụng cụ</option>
            <option v-for="dc in danhSachDungCu" :key="dc.dung_cu_nguon" :value="dc.dung_cu_nguon">
              {{ dc.dung_cu }}
            </option>
          </select></label
        >
        <button type="submit" class="btn btn-primary" :disabled="dangTai">
          <i class="bi bi-search" aria-hidden="true"></i> Tìm kiếm</button
        ><button
          v-if="coBoLocDangChon"
          type="button"
          class="btn btn-outline-secondary"
          aria-label="Đặt lại bộ lọc"
          title="Đặt lại bộ lọc"
          @click="xoaBoLoc"
        >
          <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
        </button>
      </form>
      <div class="st-muscle-tabs" aria-label="Nhóm cơ">
        <button type="button" :aria-pressed="!boLoc.nhom_co_id" @click="chonNhomCo('')">
          Tất cả
        </button>
        <button
          v-for="nhom in danhSachNhomCo"
          :key="nhom.id"
          type="button"
          :aria-pressed="String(boLoc.nhom_co_id) === String(nhom.id)"
          @click="chonNhomCo(String(nhom.id))"
        >
          {{ nhom.ten_nhom_co }}
        </button>
      </div>
      <p v-if="loiBoLoc" class="alert alert-warning" role="alert">
        {{ loiBoLoc }}
        <button type="button" class="btn btn-outline-secondary" @click="taiBoLoc">
          Tải lại bộ lọc
        </button>
      </p>
      <section
        ref="ketQua"
        class="library-results"
        aria-label="Kết quả bài tập"
        :aria-busy="dangTai"
      >
        <div class="st-list-heading">
          <strong>{{ dinhDangSo(phanTrang.total) }} bài tập phù hợp</strong
          ><span>Trang {{ phanTrang.current_page }} / {{ phanTrang.last_page }}</span>
        </div>
        <p v-if="dangTai" class="st-empty" role="status">Đang tải danh sách bài tập…</p>
        <div v-else-if="thongBao" class="alert alert-danger" role="alert">
          {{ thongBao }}
          <button class="btn btn-outline-secondary" @click="taiDanhSach">Thử lại</button>
        </div>
        <div v-else-if="!danhSach.length" class="st-empty">
          <i class="bi bi-search" aria-hidden="true"></i>
          <h2>Không tìm thấy bài tập phù hợp</h2>
          <button class="btn btn-outline-secondary" @click="xoaBoLoc">Xem tất cả bài tập</button>
        </div>
        <div v-else class="exercise-grid">
          <RouterLink
            v-for="bai in danhSach"
            :key="bai.id"
            class="exercise-modern-card"
            :to="{ name: 'chi-tiet-bai-tap', params: { id: bai.id }, query: $route.query }"
          >
            <!-- Ảnh bài tập có bo góc và tỷ lệ 4:3 chuẩn -->
            <div class="card-image-container">
              <AnhBaiTap
                :src="urlMedia(bai.anh_url)"
                :alt="bai.ten_tieng_viet || bai.ten_bai_tap"
              />
              <span class="badge-card-group">
                <i class="bi bi-fire me-1"></i>{{ bai.nhom_co.ten_nhom_co }}
              </span>
            </div>

            <!-- Thân thẻ thông tin bài tập -->
            <div class="exercise-card-body">
              <h3 class="exercise-title text-truncate-2">
                {{ bai.ten_tieng_viet || bai.ten_bai_tap }}
              </h3>
              <p
                v-if="bai.ten_tieng_viet && bai.ten_tieng_viet !== bai.ten_bai_tap"
                class="exercise-subtitle text-truncate"
              >
                {{ bai.ten_bai_tap }}
              </p>

              <div class="exercise-equipment-tag">
                <i class="bi bi-tools text-muted"></i>
                <span class="text-truncate">{{ bai.dung_cu || 'Không cần dụng cụ' }}</span>
              </div>

              <div class="exercise-card-footer">
                <span class="open-link-text">
                  <span>Chi tiết động tác</span>
                  <i class="bi bi-arrow-right open-arrow"></i>
                </span>
              </div>
            </div>
          </RouterLink>
        </div>

        <nav
          v-if="!dangTai && !thongBao && phanTrang.last_page > 1"
          class="st-pagination"
          aria-label="Phân trang bài tập"
        >
          <button
            class="btn btn-outline-secondary"
            :disabled="phanTrang.current_page <= 1"
            @click="chuyenTrang(phanTrang.current_page - 1)"
          >
            <i class="bi bi-chevron-left" aria-hidden="true"></i> Trước</button
          ><span>{{ phanTrang.current_page }} / {{ phanTrang.last_page }}</span
          ><button
            class="btn btn-outline-secondary"
            :disabled="phanTrang.current_page >= phanTrang.last_page"
            @click="chuyenTrang(phanTrang.current_page + 1)"
          >
            Sau <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </button>
        </nav>
      </section>
    </section>
  </DanhMucLayout>
</template>

<script>
import DanhMucLayout from '../../layouts/DanhMucLayout.vue'
import AnhBaiTap from '../../components/AnhBaiTap.vue'
import baiTapService from '../../services/baiTapService'
import { docBoLoc, taoQueryBoLoc } from '../../utils/baiTap'
import { layLoiApi } from '../../utils/loiApi'

export default {
  name: 'DanhSachBaiTap',
  components: { DanhMucLayout, AnhBaiTap },
  data() {
    return {
      boLoc: docBoLoc(),
      danhSach: [],
      danhSachNhomCo: [],
      danhSachDungCu: [],
      phanTrang: { current_page: 1, last_page: 1, total: 0 },
      dangTai: false,
      dangTaiBoLoc: false,
      thongBao: '',
      loiTruong: {},
      loiBoLoc: '',
      soLanTai: 0,
      huyYeuCau: null,
      daDong: false,
      canCuonKetQua: false,
    }
  },
  computed: {
    coBoLocDangChon() {
      return Boolean(this.boLoc.tu_khoa || this.boLoc.nhom_co_id || this.boLoc.dung_cu_nguon)
    },
    tenNhomCoHienTai() {
      if (!this.boLoc.nhom_co_id) return ''
      const nhom = this.danhSachNhomCo.find((n) => String(n.id) === String(this.boLoc.nhom_co_id))
      return nhom ? nhom.ten_nhom_co : ''
    },
    tenDungCuHienTai() {
      if (!this.boLoc.dung_cu_nguon) return ''
      const dc = this.danhSachDungCu.find((d) => d.dung_cu_nguon === this.boLoc.dung_cu_nguon)
      return dc ? dc.dung_cu : this.boLoc.dung_cu_nguon
    },
  },
  watch: {
    '$route.query': {
      immediate: true,
      handler(query) {
        this.boLoc = docBoLoc(query)
        this.taiDanhSach()
      },
    },
  },
  created() {
    this.taiBoLoc()
  },
  beforeUnmount() {
    this.daDong = true
    this.soLanTai++
    this.huyYeuCau?.abort()
  },
  methods: {
    chonNhomCo(id) {
      this.boLoc.nhom_co_id = id
      this.apDungBoLoc()
    },
    urlMedia: baiTapService.urlMedia,
    dinhDangSo(so) {
      return new Intl.NumberFormat('vi-VN').format(so)
    },
    async taiBoLoc() {
      if (this.dangTaiBoLoc) return
      this.dangTaiBoLoc = true
      this.loiBoLoc = ''
      try {
        const ketQua = await baiTapService.taiBoLoc()
        if (this.daDong) return
        this.danhSachNhomCo = ketQua.data.nhom_co
        this.danhSachDungCu = ketQua.data.dung_cu
      } catch (loi) {
        if (!this.daDong) this.loiBoLoc = layLoiApi(loi).thongBao
      } finally {
        if (!this.daDong) this.dangTaiBoLoc = false
      }
    },
    async taiDanhSach() {
      const lanTai = ++this.soLanTai
      this.huyYeuCau?.abort()
      this.huyYeuCau = new AbortController()
      this.dangTai = true
      this.thongBao = ''
      this.loiTruong = {}
      try {
        const ketQua = await baiTapService.taiDanhSach(
          docBoLoc(this.$route.query),
          this.huyYeuCau.signal,
        )
        if (lanTai !== this.soLanTai) return
        this.danhSach = ketQua.data
        this.phanTrang = ketQua.meta
      } catch (loi) {
        if (lanTai !== this.soLanTai) return
        const ketQua = layLoiApi(loi)
        this.thongBao = ketQua.thongBao
        this.loiTruong = ketQua.loiTruong
      } finally {
        if (lanTai === this.soLanTai) {
          this.dangTai = false
          if (this.canCuonKetQua) await this.cuonDenKetQua()
        }
      }
    },
    async apDungBoLoc() {
      this.canCuonKetQua = true
      await this.$router.push({ name: 'bai-tap', query: taoQueryBoLoc(this.boLoc) })
      await this.cuonDenKetQua()
    },
    async xoaBoLoc() {
      this.boLoc = docBoLoc()
      await this.$router.push({ name: 'bai-tap' })
    },
    async xoaTuKhoa() {
      this.boLoc.tu_khoa = ''
      await this.apDungBoLoc()
    },
    async xoaNhomCo() {
      this.boLoc.nhom_co_id = ''
      await this.apDungBoLoc()
    },
    async xoaDungCu() {
      this.boLoc.dung_cu_nguon = ''
      await this.apDungBoLoc()
    },
    async chuyenTrang(page) {
      this.canCuonKetQua = true
      await this.$router.push({
        name: 'bai-tap',
        query: taoQueryBoLoc(docBoLoc(this.$route.query), page),
      })
      await this.cuonDenKetQua()
    },
    async cuonDenKetQua() {
      if (this.dangTai || !this.canCuonKetQua) return
      await this.$nextTick()
      this.canCuonKetQua = false
      this.$refs.ketQua?.scrollIntoView({ block: 'start', behavior: 'auto' })
    },
  },
}
</script>

<style scoped>
.library-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
  margin-bottom: 28px;
}

.library-intro {
  max-width: 680px;
  margin: 0;
  line-height: 1.6;
}

/* Thanh tìm kiếm lớn */
.exercise-search-box {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 6px 6px 18px;
  background: var(--mau-the, var(--mau-the));
  border: 1.5px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  transition: all 0.25s ease;
}

.exercise-search-box:focus-within {
  border-color: var(--mau-chinh);
  box-shadow: 0 0 0 3.5px color-mix(in srgb, var(--mau-chinh) 25%, transparent);
}

.search-input-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 0;
}

.search-icon {
  color: var(--mau-chinh);
  font-size: 1.15rem;
}

.search-native-input {
  width: 100%;
  border: none;
  background: transparent;
  outline: none;
  font-size: 0.95rem;
  color: var(--mau-chu);
  padding: 10px 0;
}

.btn-clear-search {
  border: none;
  background: transparent;
  color: var(--mau-phu);
  font-size: 1.1rem;
  padding: 4px;
  cursor: pointer;
  transition: color 0.2s;
}

.btn-clear-search:hover {
  color: #475569;
}

.search-submit-btn {
  padding: 0.55rem 1.4rem;
  border-radius: var(--bo-goc-md);
  font-size: 0.9rem;
}

/* Thân trang: Grid 2 cột */
.library-body {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  gap: 32px;
}

.filter-card {
  background: var(--mau-the, var(--mau-the));
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  padding: 22px;
  box-shadow: var(--bong-nhe);
  position: sticky;
  top: 90px;
}

.filters-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--mau-vien);
  padding-bottom: 14px;
  margin-bottom: 18px;
}

.clear-filters-btn {
  border: none;
  background: transparent;
  color: var(--mau-chinh);
  font-size: 0.8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  padding: 4px;
}

.clear-filters-btn:hover {
  text-decoration: underline;
}

.text-amber {
  color: #d97706;
}

/* Chips bộ lọc đang chọn */
.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--mau-the-sub, var(--mau-the-hover));
  border: 1px solid var(--mau-vien, rgba(255, 255, 255, 0.12));
  padding: 3px 10px;
  border-radius: var(--bo-goc-tron);
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--mau-chu, #ffffff);
}

.chip-remove-btn {
  border: none;
  background: var(--mau-vien);
  color: #475569;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  display: grid;
  place-items: center;
  font-size: 0.8rem;
  line-height: 1;
  cursor: pointer;
}

.chip-remove-btn:hover {
  background: var(--mau-phu);
}

/* Khung trạng thái (Loading, Error, Empty) */
.catalog-state-box {
  min-height: 360px;
  background: var(--mau-the, var(--mau-the));
  border: 1px dashed var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 24px;
  text-align: center;
}

.empty-icon-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--mau-the-hover);
  display: grid;
  place-items: center;
}

/* Lưới thẻ bài tập */
.exercise-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 22px;
}

.exercise-modern-card {
  background: var(--mau-the, var(--mau-the));
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  overflow: hidden;
  text-decoration: none;
  color: var(--mau-chu);
  display: flex;
  flex-direction: column;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--bong-nhe);
}

.exercise-modern-card:hover {
  transform: translateY(-5px);
  border-color: color-mix(in srgb, var(--mau-chinh) 40%, transparent);
  box-shadow: var(--bong-nhe);
}

.card-image-container {
  position: relative;
  background: #101010;
  border-bottom: 1px solid var(--mau-vien);
}

.badge-card-group {
  position: absolute;
  top: 12px;
  left: 12px;
  background: rgba(20, 20, 20, 0.92);
  backdrop-filter: blur(8px);
  color: var(--mau-chinh);
  font-size: 0.72rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: var(--bo-goc-tron);
  border: 1px solid color-mix(in srgb, var(--mau-chinh) 30%, transparent);
  box-shadow: var(--bong-nhe);
  z-index: 2;
}

.exercise-card-body {
  padding: 18px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.exercise-title {
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.4;
  margin-bottom: 4px;
  color: var(--mau-chu);
}

.exercise-subtitle {
  font-size: 0.78rem;
  color: var(--mau-phu);
  margin-bottom: 12px;
}

.exercise-equipment-tag {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  color: var(--mau-phu);
  margin-top: auto;
  padding-top: 10px;
  border-top: 1px dashed var(--mau-vien);
}

.exercise-card-footer {
  margin-top: 12px;
}

.open-link-text {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--mau-chinh);
  transition: color 0.2s;
}

.open-arrow {
  transition: transform 0.2s ease;
}

.exercise-modern-card:hover .open-arrow {
  transform: translateX(4px);
}

.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Phân trang */
.exercise-pagination-bar {
  background: var(--mau-the, var(--mau-the));
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  padding: 14px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

@media (max-width: 1080px) {
  .library-body {
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 24px;
  }
  .exercise-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .library-body {
    grid-template-columns: 1fr;
  }
  .filter-card {
    position: static;
  }
}

@media (max-width: 540px) {
  .exercise-grid {
    grid-template-columns: 1fr;
  }
  .library-heading {
    flex-direction: column;
    align-items: flex-start;
  }
  .exercise-search-box {
    padding: 6px;
  }
}
</style>
