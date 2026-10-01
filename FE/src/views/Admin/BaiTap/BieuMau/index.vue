<template>
  <CaNhanLayout>
    <!-- Nút quay lại danh sách quản trị -->
    <div class="mb-3">
      <RouterLink :to="{ name: 'admin-bai-tap', query: queryQuayLai }" class="btn-back-link">
        <i class="bi bi-arrow-left"></i>
        <span>Quay lại danh sách bài tập</span>
      </RouterLink>
    </div>

    <!-- Tiêu đề trang biên tập -->
    <header class="editor-heading mb-4 animate__animated animate__fadeIn">
      <div class="eyebrow mb-2">
        <i :class="laThem ? 'bi-plus-circle-fill' : 'bi-pencil-square'"></i>
        <span>{{ laThem ? 'QUẢN TRỊ · THÊM MỚI BÀI TẬP' : 'QUẢN TRỊ · BIÊN TẬP BÀI TẬP' }}</span>
      </div>
      <h1 class="h2 fw-bold mb-1">
        {{ laThem ? 'Thêm bài tập mới' : 'Biên tập nội dung bài tập' }}
      </h1>
      <p class="text-muted small mb-0">
        {{
          laThem
            ? 'Bổ sung bài tập mới, thiết lập nhóm cơ, dụng cụ và soạn thảo hướng dẫn kỹ thuật chi tiết.'
            : 'Cập nhật bản dịch tiếng Việt, điều chỉnh nhóm cơ, dụng cụ hoặc hoàn thiện các bước thực hiện.'
        }}
      </p>
    </header>

    <!-- Trạng thái Đang tải biểu mẫu -->
    <div
      v-if="dangTai"
      class="editor-state p-5 text-center bg-white rounded-4 border shadow-sm"
      role="status"
    >
      <div class="spinner-border text-success mb-3" role="status"></div>
      <h2 class="h5 fw-bold mb-1">Đang tải biểu mẫu bài tập…</h2>
      <p class="text-muted small mb-0">Hệ thống đang chuẩn bị dữ liệu và danh mục nhóm cơ.</p>
    </div>

    <!-- Trạng thái Báo lỗi khi tải -->
    <div
      v-else-if="loiTai"
      class="editor-state p-5 text-center alert alert-danger rounded-4 border-danger-subtle shadow-sm"
      role="alert"
    >
      <i class="bi bi-exclamation-triangle-fill fs-1 text-danger mb-2"></i>
      <h2 class="h5 fw-bold text-danger mb-2">Chưa thể tải dữ liệu bài tập</h2>
      <p class="mb-3">{{ loiTai }}</p>
      <button class="btn btn-outline-danger btn-sm" @click="taiBieuMau">
        <i class="bi bi-arrow-clockwise me-1"></i>Thử tải lại
      </button>
    </div>

    <!-- Biểu mẫu biên tập 2 cột -->
    <form v-else class="editor-grid" @submit.prevent="luuBaiTap" novalidate :aria-busy="dangLuu">
      <!-- Cột chính: Form nhập liệu -->
      <div class="editor-main">
        <!-- Thông báo kết quả tác vụ hoặc Xung đột phiên bản 409 -->
        <div
          v-if="thongBao"
          ref="thongBao"
          class="alert d-flex flex-column gap-2 p-3 mb-4 rounded-3 animate__animated animate__fadeIn"
          :class="
            coLoi ? 'alert-danger border-danger-subtle' : 'alert-success border-success-subtle'
          "
          role="alert"
          tabindex="-1"
        >
          <div class="d-flex align-items-center gap-2">
            <i
              :class="
                coLoi
                  ? 'bi bi-exclamation-triangle-fill text-danger'
                  : 'bi bi-check-circle-fill text-success'
              "
              class="fs-5 flex-shrink-0"
            ></i>
            <strong class="small">{{ thongBao }}</strong>
          </div>
          <ul v-if="coLoi && Object.keys(loiTruong).length" class="mb-0 ps-4 small text-danger">
            <li v-for="(loi, truong) in loiTruong" :key="truong">{{ loi[0] }}</li>
          </ul>
          <div v-if="xungDot" class="mt-2 pt-2 border-top border-danger-subtle">
            <button type="button" class="btn btn-danger btn-sm shadow-sm" @click="taiBieuMau">
              <i class="bi bi-arrow-clockwise me-1"></i>Tải lại bản mới nhất từ máy chủ
            </button>
          </div>
        </div>

        <!-- Khối 1: Thông tin định danh & Phân loại -->
        <section class="editor-panel card-modern mb-4" aria-labelledby="thong-tin-bai-tap">
          <div class="panel-heading">
            <h2 id="thong-tin-bai-tap" class="h5 fw-bold mb-0">
              <i class="bi bi-info-circle-fill text-success"></i>
              <span>Thông tin định danh bài tập</span>
            </h2>
            <span class="badge bg-light text-muted border">Bắt buộc & Tùy chọn</span>
          </div>

          <fieldset :disabled="dangLuu || xungDot" class="border-0 p-0 m-0">
            <!-- Mã bài tập (khi thêm mới) -->
            <TruongNhap
              v-if="laThem"
              v-model="duLieu.ma_nguon"
              id="ma_nguon"
              nhan="Mã bài tập định danh"
              goi-y-nhap="Ví dụ: A001"
              bieu-tuong="bi bi-upc-scan"
              :toi-da="4"
              goi-y="Đúng 4 chữ cái hoặc chữ số, ví dụ A001."
              :loi="loiTruong.ma_nguon"
            />

            <!-- Tên bài tập (gốc) -->
            <TruongNhap
              v-model="duLieu.ten_bai_tap"
              id="ten_bai_tap"
              :nhan="laBaiNhap ? 'Tên bài tập gốc (tiếng Anh)' : 'Tên bài tập'"
              goi-y-nhap="Nhập tên bài tập chuẩn…"
              bieu-tuong="bi bi-fonts"
              :vo-hieu="laBaiNhap || dangLuu"
              :goi-y="
                laBaiNhap ? 'Tên gốc tiếng Anh được giữ nguyên vẹn theo dataset quốc tế.' : ''
              "
              :loi="loiTruong.ten_bai_tap"
            />

            <!-- Tên tiếng Việt -->
            <TruongNhap
              v-model="duLieu.ten_tieng_viet"
              id="ten_tieng_viet"
              nhan="Tên bài tập tiếng Việt (Ưu tiên hiển thị)"
              goi-y-nhap="Ví dụ: Hít đất, Gập bụng trên thảm…"
              bieu-tuong="bi bi-translate"
              :bat-buoc="false"
              goi-y="Tên tiếng Việt này sẽ được hiển thị ưu tiên cho toàn bộ học viên và HLV."
              :loi="loiTruong.ten_tieng_viet"
            />

            <!-- Cặp trường: Nhóm cơ & Dụng cụ -->
            <div class="row g-3">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="nhom_co_id" class="form-label d-flex align-items-center gap-1">
                    <i class="bi bi-person-arms-up text-primary"></i>
                    <span>Nhóm cơ chính</span>
                    <span class="text-danger">*</span>
                  </label>
                  <select
                    id="nhom_co_id"
                    v-model="duLieu.nhom_co_id"
                    required
                    class="form-select"
                    :class="{ 'is-invalid': loiTruong.nhom_co_id }"
                    :aria-invalid="!!loiTruong.nhom_co_id"
                    :aria-describedby="loiTruong.nhom_co_id ? 'nhom-co-loi' : undefined"
                  >
                    <option value="">-- Chọn nhóm cơ tác động --</option>
                    <option
                      v-for="nhom in nhomCo"
                      :key="nhom.id"
                      :value="String(nhom.id)"
                      :disabled="
                        nhom.trang_thai !== 'HOAT_DONG' &&
                        String(nhom.id) !== String(baiTap?.nhom_co_id)
                      "
                    >
                      {{ nhom.ten_nhom_co
                      }}{{ nhom.trang_thai !== 'HOAT_DONG' ? ' · Ngừng dùng' : '' }}
                    </option>
                  </select>
                  <p
                    v-if="loiTruong.nhom_co_id"
                    id="nhom-co-loi"
                    class="invalid-feedback d-block mt-1"
                  >
                    {{ loiTruong.nhom_co_id[0] }}
                  </p>
                </div>
              </div>

              <div class="col-md-6">
                <TruongNhap
                  v-model="duLieu.dung_cu"
                  id="dung_cu"
                  nhan="Dụng cụ tập luyện"
                  goi-y-nhap="Tạ đơn, Thể trọng, Đòn tạ…"
                  bieu-tuong="bi bi-tools"
                  :bat-buoc="false"
                  :loi="loiTruong.dung_cu"
                />
              </div>
            </div>
          </fieldset>
        </section>

        <!-- Khối 2: Hướng dẫn kỹ thuật tiếng Việt -->
        <section class="editor-panel card-modern mb-4" aria-labelledby="huong-dan-bai-tap">
          <div class="panel-heading">
            <h2 id="huong-dan-bai-tap" class="h5 fw-bold mb-0">
              <i class="bi bi-journal-text text-success"></i>
              <span>Hướng dẫn kỹ thuật tiếng Việt</span>
            </h2>
            <span class="badge bg-success-subtle text-success">Soạn thảo giáo án</span>
          </div>

          <fieldset :disabled="dangLuu || xungDot" class="border-0 p-0 m-0">
            <!-- Hướng dẫn tổng quát -->
            <div class="mb-4">
              <label
                for="huong_dan_vi"
                class="form-label d-flex align-items-center justify-content-between"
              >
                <span>Hướng dẫn tổng quát</span>
                <small class="text-muted">Tối đa 10,000 ký tự</small>
              </label>
              <textarea
                id="huong_dan_vi"
                v-model="duLieu.huong_dan_vi"
                class="form-control"
                rows="4"
                maxlength="10000"
                placeholder="Mô tả tư thế chuẩn bị, nhịp thở và lưu ý quan trọng trước khi bắt đầu động tác…"
                :class="{ 'is-invalid': loiTruong.huong_dan_vi }"
                :aria-invalid="!!loiTruong.huong_dan_vi"
                :aria-describedby="loiTruong.huong_dan_vi ? 'huong-dan-loi' : undefined"
              ></textarea>
              <p
                v-if="loiTruong.huong_dan_vi"
                id="huong-dan-loi"
                class="invalid-feedback d-block mt-1"
              >
                {{ loiTruong.huong_dan_vi[0] }}
              </p>
            </div>

            <!-- Các bước thực hiện -->
            <div class="mb-3">
              <label
                for="cac_buoc_vi"
                class="form-label d-flex align-items-center justify-content-between"
              >
                <span>Các bước thực hiện (Mỗi dòng là một bước)</span>
                <span class="badge bg-light text-muted border">Tối đa 30 bước</span>
              </label>
              <textarea
                id="cac_buoc_vi"
                v-model="duLieu.cac_buoc_vi"
                class="form-control font-monospace small"
                rows="8"
                placeholder="Bước 1: Nằm ngửa trên thảm tập, gập đầu gối 90 độ...&#10;Bước 2: Siết chặt cơ bụng và nâng phần thân trên lên...&#10;Bước 3: Từ từ hạ người về vị trí ban đầu..."
                :class="{ 'is-invalid': loiCacBuoc.length }"
                :aria-invalid="!!loiCacBuoc.length"
                aria-describedby="cac-buoc-goi-y cac-buoc-loi"
              ></textarea>
              <div id="cac-buoc-goi-y" class="form-text d-flex align-items-center gap-1 mt-1">
                <i class="bi bi-info-circle text-primary"></i>
                <span
                  >Xuống dòng để tạo bước tiếp theo. Hệ thống sẽ tự động đánh số thứ tự khi hiển thị
                  trong thư viện.</span
                >
              </div>
              <p v-if="loiCacBuoc.length" id="cac-buoc-loi" class="invalid-feedback d-block mt-1">
                {{ loiCacBuoc[0] }}
              </p>
            </div>

            <div v-if="laBaiNhap" class="source-note p-3 bg-light rounded-3 border">
              <div class="small fw-semibold text-dark mb-1">
                <i class="bi bi-translate text-success me-1"></i>Đồng bộ đa ngôn ngữ:
              </div>
              <p class="small text-muted mb-0">
                Hướng dẫn tiếng Anh gốc vẫn được bảo lưu an toàn. Nếu để trống các trường tiếng Việt
                này, hệ thống sẽ sử dụng bản tiếng Anh gốc.
              </p>
            </div>
          </fieldset>
        </section>

        <!-- Thanh nút Lưu / Quay lại -->
        <div class="editor-save d-flex justify-content-end gap-2">
          <RouterLink
            class="btn btn-outline-secondary"
            :to="{ name: 'admin-bai-tap', query: queryQuayLai }"
          >
            Quay lại danh sách
          </RouterLink>
          <button
            class="btn btn-primary shadow-sm"
            :disabled="dangLuu || xungDot || !nhomCo.length"
            type="submit"
          >
            <span v-if="dangLuu" class="spinner-border spinner-border-sm me-1" role="status"></span>
            <i v-else :class="laThem ? 'bi bi-plus-circle' : 'bi bi-check2-circle'"></i>
            <span>{{
              dangLuu
                ? 'Đang lưu bài tập…'
                : laThem
                  ? 'Xác nhận thêm bài tập'
                  : 'Lưu thay đổi bài tập'
            }}</span>
          </button>
        </div>
      </div>

      <!-- Cột phụ bên phải: Preview & Trạng thái -->
      <aside class="editor-aside">
        <section class="editor-panel card-modern mb-4">
          <div class="panel-heading">
            <h2 class="h6 fw-bold mb-0">
              <i class="bi bi-sliders text-success"></i>
              <span>{{ laThem ? 'Thiết lập ban đầu' : 'Thông tin minh họa' }}</span>
            </h2>
          </div>

          <!-- Trạng thái ban đầu khi Thêm mới -->
          <template v-if="laThem">
            <div class="mb-3">
              <label class="form-label" for="trang_thai">Trạng thái phát hành</label>
              <select
                id="trang_thai"
                v-model="duLieu.trang_thai"
                class="form-select"
                :disabled="dangLuu"
              >
                <option value="HOAT_DONG">Đang hoạt động (Hiển thị)</option>
                <option value="NGUNG_SU_DUNG">Tạm ngừng hiển thị</option>
              </select>
              <p v-if="loiTruong.trang_thai" class="text-danger small mt-2">
                {{ loiTruong.trang_thai[0] }}
              </p>
            </div>
            <div class="p-3 bg-light rounded-3 border small text-muted">
              <i class="bi bi-info-circle me-1"></i>Bài tập mới tạo sẽ dùng hình ảnh mặc định của hệ
              thống trước khi được gán ảnh media.
            </div>
          </template>

          <!-- Ảnh preview và thông số khi Sửa bài tập -->
          <template v-else>
            <div class="preview-thumbnail-wrap rounded-3 overflow-hidden border mb-3">
              <AnhBaiTap
                :src="urlMedia(baiTap.anh_url)"
                :alt="baiTap.ten_tieng_viet || baiTap.ten_bai_tap"
              />
            </div>

            <p v-if="baiTap.ghi_cong_media" class="small text-muted mb-3 fst-italic">
              <i class="bi bi-camera me-1"></i>{{ baiTap.ghi_cong_media }}
            </p>

            <dl class="source-facts-list mb-3">
              <div class="fact-row">
                <dt>Mã định danh</dt>
                <dd class="font-monospace">#{{ baiTap.ma_nguon || baiTap.id }}</dd>
              </div>
              <div class="fact-row">
                <dt>Nguồn gốc</dt>
                <dd>
                  <span
                    class="badge"
                    :class="
                      laBaiNhap
                        ? 'bg-secondary-subtle text-dark border'
                        : 'bg-primary-subtle text-primary border'
                    "
                  >
                    {{ laBaiNhap ? 'Dataset quốc tế' : 'Admin biên soạn' }}
                  </span>
                </dd>
              </div>
              <div class="fact-row">
                <dt>Trạng thái</dt>
                <dd>
                  <span
                    class="badge"
                    :class="
                      baiTap.trang_thai === 'HOAT_DONG'
                        ? 'bg-success-subtle text-success'
                        : 'bg-danger-subtle text-danger'
                    "
                  >
                    {{ baiTap.trang_thai === 'HOAT_DONG' ? 'Hoạt động' : 'Ngừng hiển thị' }}
                  </span>
                </dd>
              </div>
            </dl>

            <RouterLink
              v-if="baiTap.trang_thai === 'HOAT_DONG' && baiTap.nhom_co_trang_thai === 'HOAT_DONG'"
              :to="{ name: 'chi-tiet-bai-tap', params: { id: baiTap.id } }"
              class="btn btn-outline-secondary btn-sm w-100"
              target="_blank"
            >
              <i class="bi bi-box-arrow-up-right me-1"></i>Xem hiển thị trong thư viện
            </RouterLink>
          </template>
        </section>

        <p class="small text-muted">
          <i class="bi bi-shield-check me-1"></i>Cơ chế bảo vệ xung đột (409) tự động bảo vệ dữ liệu
          khi có nhiều quản trị viên cùng thao tác.
        </p>
      </aside>
    </form>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../../layouts/CaNhanLayout.vue'
import TruongNhap from '../../../../components/TruongNhap.vue'
import AnhBaiTap from '../../../../components/AnhBaiTap.vue'
import baiTapAdminService from '../../../../services/baiTapAdminService'
import baiTapService from '../../../../services/baiTapService'
import {
  taoBieuMauBaiTap,
  taoPayloadBaiTap,
  docBoLocAdmin,
  taoQueryAdmin,
} from '../../../../utils/baiTapAdmin'
import { layLoiApi } from '../../../../utils/loiApi'

export default {
  name: 'BienTapBaiTap',
  components: { CaNhanLayout, TruongNhap, AnhBaiTap },
  data() {
    return {
      duLieu: taoBieuMauBaiTap(),
      baiTap: null,
      nhomCo: [],
      dangTai: false,
      dangLuu: false,
      loiTai: '',
      thongBao: '',
      coLoi: false,
      xungDot: false,
      loiTruong: {},
      huyTai: null,
      lanTai: 0,
      daDong: false,
    }
  },
  computed: {
    laThem() {
      return !this.$route.params.id
    },
    laBaiNhap() {
      return !!this.baiTap && this.baiTap.nguon_du_lieu !== 'admin'
    },
    queryQuayLai() {
      const boLoc = docBoLocAdmin(this.$route.query)
      return taoQueryAdmin(boLoc, boLoc.page)
    },
    loiCacBuoc() {
      return Object.entries(this.loiTruong)
        .filter(([truong]) => truong === 'cac_buoc_vi' || truong.startsWith('cac_buoc_vi.'))
        .flatMap(([, loi]) => loi)
    },
  },
  watch: {
    '$route.params.id': {
      immediate: true,
      handler() {
        this.taiBieuMau()
      },
    },
  },
  beforeUnmount() {
    this.daDong = true
    this.lanTai++
    this.huyTai?.abort()
  },
  methods: {
    urlMedia: baiTapService.urlMedia,
    async taiBieuMau() {
      const lanTai = ++this.lanTai
      this.huyTai?.abort()
      this.huyTai = new AbortController()
      this.dangTai = true
      this.loiTai = ''
      try {
        const [boLoc, chiTiet] = await Promise.all([
          baiTapAdminService.taiBoLoc(this.huyTai.signal),
          this.laThem
            ? Promise.resolve(null)
            : baiTapAdminService.taiChiTiet(this.$route.params.id, this.huyTai.signal),
        ])
        if (this.daDong || lanTai !== this.lanTai) return
        this.nhomCo = boLoc.data.nhom_co
        this.baiTap = chiTiet?.data || null
        this.duLieu = taoBieuMauBaiTap(this.baiTap || {})
        this.thongBao = ''
        this.loiTruong = {}
        this.xungDot = false
      } catch (loi) {
        if (!this.daDong && lanTai === this.lanTai && loi.code !== 'ERR_CANCELED')
          this.loiTai =
            loi.response?.status === 404 ? 'Bài tập không tồn tại.' : layLoiApi(loi).thongBao
      } finally {
        if (!this.daDong && lanTai === this.lanTai) this.dangTai = false
      }
    },
    async luuBaiTap() {
      if (this.dangLuu || this.xungDot || this.dangTai) return
      this.dangLuu = true
      this.thongBao = ''
      this.loiTruong = {}
      const idDangSua = this.baiTap?.id
      const lanTaiKhiLuu = this.lanTai
      try {
        const payload = taoPayloadBaiTap(this.duLieu, this.baiTap)
        const phanHoi = this.laThem
          ? await baiTapAdminService.taoBaiTap(payload)
          : await baiTapAdminService.suaBaiTap(idDangSua, payload)
        if (this.daDong || this.lanTai !== lanTaiKhiLuu || this.baiTap?.id !== idDangSua) return
        this.coLoi = false
        if (this.laThem)
          await this.$router.push({
            name: 'admin-bai-tap',
            query: { ...this.queryQuayLai, da_them: String(phanHoi.data.id) },
          })
        else {
          this.baiTap = phanHoi.data
          this.duLieu = taoBieuMauBaiTap(this.baiTap)
          this.thongBao = phanHoi.message
        }
      } catch (loi) {
        if (this.daDong || this.lanTai !== lanTaiKhiLuu || this.baiTap?.id !== idDangSua) return
        const phanHoi = layLoiApi(loi)
        this.thongBao = phanHoi.thongBao
        this.loiTruong = phanHoi.loiTruong
        this.coLoi = true
        this.xungDot = loi.response?.status === 409
      } finally {
        if (!this.daDong) {
          this.dangLuu = false
          await this.$nextTick()
          this.$refs.thongBao?.focus()
        }
      }
    },
  },
}
</script>

<style scoped>
.btn-back-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: white;
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-tron);
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--mau-chu);
  text-decoration: none;
  box-shadow: var(--bong-nhe);
  transition: all 0.2s ease;
}

.btn-back-link:hover {
  background: #f8fafc;
  color: var(--mau-chinh);
  border-color: var(--mau-chinh);
  transform: translateX(-3px);
}

.editor-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 32px;
  align-items: start;
}

.editor-panel {
  background: white;
  border: 1px solid var(--mau-vien);
  border-radius: var(--bo-goc-lg);
  padding: 28px;
  box-shadow: var(--bong-nhe);
}

.source-facts-list {
  font-size: 0.85rem;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.fact-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 6px 0;
  border-bottom: 1px solid var(--mau-vien);
}

.fact-row:last-child {
  border-bottom: none;
}

.fact-row dt {
  color: var(--mau-phu);
  font-weight: 500;
  margin: 0;
}

.fact-row dd {
  margin: 0;
  font-weight: 600;
}

.preview-thumbnail-wrap {
  aspect-ratio: 4 / 3;
  background: #f8fafc;
}

@media (max-width: 992px) {
  .editor-grid {
    grid-template-columns: 1fr;
  }
}
</style>
