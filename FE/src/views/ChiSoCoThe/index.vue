<template>
  <CaNhanLayout>
    <section class="cs-page">
      <RouterLink v-if="laPt" to="/pt/hoc-vien" class="btn btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Học viên & giáo án
      </RouterLink>
      <header class="cs-heading">
        <div>
          <span class="cs-eyebrow">THEO DÕI TIẾN ĐỘ</span>
          <h1>Chỉ số cơ thể</h1>
          <p>
            {{
              laPt
                ? duLieu?.ho_ten || 'Học viên đang phụ trách'
                : 'Ghi lại thay đổi của bạn qua từng ngày tập luyện.'
            }}
          </p>
        </div>
        <button class="btn btn-outline-secondary" :disabled="dangTai || dangLuu" @click="taiDuLieu">
          <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Cập nhật
        </button>
      </header>
      <p v-if="thongBao" class="alert alert-success" role="status">{{ thongBao }}</p>
      <div v-if="loiTai" class="alert alert-danger" role="alert">{{ loiTai }}</div>
      <p v-if="dangTai && !duLieu" class="cs-panel" role="status">Đang tải chỉ số cơ thể…</p>
      <template v-if="duLieu">
        <div class="cs-metrics">
          <article class="cs-panel">
            <span>Cân nặng gần nhất</span
            ><strong>{{ so(duLieu.moi_nhat?.can_nang_kg) }} <small>kg</small></strong>
            <p>{{ ngay(duLieu.moi_nhat?.ngay_ghi) }}</p>
          </article>
          <article class="cs-panel">
            <span>Chiều cao</span
            ><strong>{{ so(duLieu.moi_nhat?.chieu_cao_cm) }} <small>cm</small></strong>
            <p>Theo lần ghi gần nhất</p>
          </article>
          <article class="cs-panel">
            <span>BMI gần nhất</span><strong>{{ so(duLieu.moi_nhat?.bmi) }}</strong>
            <p>Chỉ số tham khảo</p>
          </article>
          <article class="cs-panel cs-change">
            <span>Thay đổi cân nặng</span
            ><strong
              >{{ thayDoi(duLieu.thay_doi_can_nang_kg) }}
              <small v-if="duLieu.thay_doi_can_nang_kg !== null">kg</small></strong
            >
            <p>{{ soNgay }} ngày · {{ duLieu.so_lan_ghi }} lần ghi</p>
          </article>
        </div>
        <div class="cs-workspace" :class="{ 'cs-readonly': laPt }">
          <form v-if="!laPt" ref="form" class="cs-panel cs-form" @submit.prevent="luu">
            <h2>{{ suaId ? 'Sửa lần ghi' : 'Ghi chỉ số mới' }}</h2>
            <p class="cs-muted">Một lần ghi mỗi ngày. Chiều cao được gợi ý từ lần gần nhất.</p>
            <p v-if="loiLuu" class="alert alert-danger" role="alert">{{ loiLuu }}</p>
            <fieldset :disabled="dangLuu || dangTai">
              <label for="cs-ngay">Ngày ghi nhận</label>
              <input
                id="cs-ngay"
                ref="ngayNhap"
                v-model="form.ngay_ghi"
                type="date"
                min="1900-01-01"
                :max="homNay"
                class="form-control"
                required
                :aria-invalid="!!loiTruong.ngay_ghi"
                aria-describedby="cs-loi-ngay"
              />
              <small id="cs-loi-ngay" class="text-danger">{{ loiTruong.ngay_ghi?.[0] }}</small>
              <div class="cs-form-row">
                <div>
                  <label for="cs-can">Cân nặng (kg)</label
                  ><input
                    id="cs-can"
                    v-model="form.can_nang_kg"
                    type="number"
                    inputmode="decimal"
                    min="10"
                    max="500"
                    step="0.01"
                    required
                    class="form-control"
                    :aria-invalid="!!loiTruong.can_nang_kg"
                    aria-describedby="cs-loi-can"
                  /><small id="cs-loi-can" class="text-danger">{{
                    loiTruong.can_nang_kg?.[0]
                  }}</small>
                </div>
                <div>
                  <label for="cs-cao">Chiều cao (cm)</label
                  ><input
                    id="cs-cao"
                    v-model="form.chieu_cao_cm"
                    type="number"
                    inputmode="decimal"
                    min="50"
                    max="250"
                    step="0.01"
                    required
                    class="form-control"
                    :aria-invalid="!!loiTruong.chieu_cao_cm"
                    aria-describedby="cs-loi-cao"
                  /><small id="cs-loi-cao" class="text-danger">{{
                    loiTruong.chieu_cao_cm?.[0]
                  }}</small>
                </div>
              </div>
              <div class="cs-preview" aria-live="polite">
                <span>BMI dự kiến</span><strong>{{ so(bmiNhap) }}</strong>
              </div>
              <label for="cs-note">Ghi chú <span class="cs-muted">(tùy chọn)</span></label>
              <textarea
                id="cs-note"
                v-model="form.ghi_chu"
                rows="2"
                maxlength="1000"
                class="form-control"
                :aria-invalid="!!loiTruong.ghi_chu"
                aria-describedby="cs-loi-note"
              ></textarea>
              <small id="cs-loi-note" class="text-danger">{{ loiTruong.ghi_chu?.[0] }}</small>
              <div class="cs-form-actions">
                <button class="btn btn-primary" type="submit">
                  {{ dangLuu ? 'Đang lưu…' : suaId ? 'Lưu thay đổi' : 'Ghi nhận' }}
                </button>
                <button
                  v-if="suaId"
                  class="btn btn-outline-secondary"
                  type="button"
                  @click="ghiMoi"
                >
                  Hủy sửa
                </button>
              </div>
            </fieldset>
          </form>
          <article class="cs-panel cs-chart">
            <div class="cs-chart-heading">
              <h2>Thay đổi theo thời gian</h2>
              <label class="cs-period"
                >Khoảng xem<select
                  v-model.number="soNgay"
                  class="form-select"
                  :disabled="dangTai || dangLuu"
                  @change="doiKhoang"
                >
                  <option :value="7">7 ngày</option>
                  <option :value="30">30 ngày</option>
                  <option :value="90">90 ngày</option>
                </select></label
              >
            </div>
            <label class="cs-end-date" for="cs-den"
              >Xem đến ngày
              <input
                id="cs-den"
                v-model="denNgay"
                type="date"
                min="1900-01-01"
                :max="homNay"
                class="form-control"
                :disabled="dangTai || dangLuu"
                @change="doiKhoang"
              />
            </label>
            <div class="cs-tabs" aria-label="Chỉ số biểu đồ">
              <button
                v-for="(ten, cot) in { can_nang_kg: 'Cân nặng (kg)', bmi: 'BMI' }"
                :key="cot"
                type="button"
                :aria-pressed="cotBieuDo === cot"
                @click="cotBieuDo = cot"
              >
                {{ ten }}
              </button>
            </div>
            <p v-if="!bieuDo.diem.length" class="cs-empty">
              Chưa có số đo trong {{ soNgay }} ngày này.
            </p>
            <template v-else>
              <svg
                viewBox="0 0 660 240"
                class="cs-svg"
                role="img"
                :aria-label="`Biểu đồ ${cotBieuDo === 'bmi' ? 'BMI' : 'cân nặng'}, ${bieuDo.diem.length} lần đo từ ${ngay(duLieu.tu_ngay)} đến ${ngay(duLieu.den_ngay)}. Chi tiết trong bảng lịch sử bên dưới.`"
              >
                <g v-for="muc in [0, 0.5, 1]" :key="muc">
                  <line
                    x1="60"
                    x2="610"
                    :y1="200 - 165 * muc"
                    :y2="200 - 165 * muc"
                    class="cs-grid"
                  />
                  <text x="48" :y="204 - 165 * muc" text-anchor="end">
                    {{ so(bieuDo.min + (bieuDo.max - bieuDo.min) * muc) }}
                  </text>
                </g>
                <path v-if="bieuDo.diem.length > 1" :d="bieuDo.duong" class="cs-line" />
                <circle
                  v-for="moc in bieuDo.diem"
                  :key="moc.id"
                  :cx="moc.x"
                  :cy="moc.y"
                  r="4"
                  class="cs-dot"
                >
                  <title>
                    {{ ngay(moc.ngay_ghi) }}: {{ so(moc.giaTri)
                    }}{{ cotBieuDo === 'bmi' ? '' : ' kg' }}
                  </title>
                </circle>
                <text x="60" y="230">{{ ngay(bieuDo.diem[0].ngay_ghi) }}</text>
                <text v-if="bieuDo.diem.length > 1" x="610" y="230" text-anchor="end">
                  {{ ngay(bieuDo.diem.at(-1).ngay_ghi) }}
                </text>
              </svg>
              <p class="cs-muted">
                {{
                  bieuDo.diem.length === 1
                    ? 'Mới có một lần đo, chưa đủ để tính thay đổi.'
                    : 'Các điểm là số đo đã ghi; đường nối thể hiện xu hướng giữa các lần đo.'
                }}
              </p>
            </template>
            <p class="cs-bmi-note">
              <i class="bi bi-info-circle" aria-hidden="true"></i> BMI không phân biệt cơ và mỡ. Kết
              hợp chỉ số với mục tiêu và tiến độ tập luyện.
            </p>
          </article>
        </div>
        <section class="cs-panel cs-history" :aria-busy="dangTai">
          <div class="cs-chart-heading">
            <div>
              <h2>Lịch sử đo</h2>
              <p class="cs-muted">{{ ngay(duLieu.tu_ngay) }} – {{ ngay(duLieu.den_ngay) }}</p>
            </div>
            <span>{{ meta.total }} lần ghi</span>
          </div>
          <p v-if="!duLieu.lich_su.length" class="cs-empty">
            {{
              laPt
                ? 'Học viên chưa ghi chỉ số trong khoảng này.'
                : 'Bạn chưa ghi chỉ số trong khoảng này. Bắt đầu bằng lần đo hôm nay.'
            }}
          </p>
          <div v-else class="cs-table-scroll">
            <table class="table align-middle mb-0">
              <caption class="visually-hidden">
                Lịch sử chiều cao, cân nặng và BMI
              </caption>
              <thead>
                <tr>
                  <th scope="col">Ngày ghi</th>
                  <th scope="col">Cân nặng</th>
                  <th scope="col">Chiều cao</th>
                  <th scope="col">BMI</th>
                  <th scope="col">Ghi chú</th>
                  <th v-if="!laPt" scope="col"><span class="visually-hidden">Thao tác</span></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ban in duLieu.lich_su" :key="ban.id">
                  <td>{{ ngay(ban.ngay_ghi) }}</td>
                  <td>{{ so(ban.can_nang_kg) }} kg</td>
                  <td>{{ so(ban.chieu_cao_cm) }} cm</td>
                  <td>{{ so(ban.bmi) }}</td>
                  <td class="cs-note">{{ ban.ghi_chu || '—' }}</td>
                  <td v-if="!laPt">
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-sm"
                      :disabled="dangTai || dangLuu"
                      :aria-label="`Sửa lần ghi ngày ${ngay(ban.ngay_ghi)}`"
                      @click="moSua(ban)"
                    >
                      Sửa
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <nav v-if="meta.last_page > 1" class="cs-pagination" aria-label="Phân trang lịch sử">
            <button
              class="btn btn-outline-secondary"
              :disabled="dangTai || dangLuu || page <= 1"
              @click="chuyenTrang(-1)"
            >
              Trước</button
            ><span>{{ page }} / {{ meta.last_page }}</span
            ><button
              class="btn btn-outline-secondary"
              :disabled="dangTai || dangLuu || page >= meta.last_page"
              @click="chuyenTrang(1)"
            >
              Sau
            </button>
          </nav>
        </section>
      </template>
    </section>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../layouts/CaNhanLayout.vue'
import chiSoCoTheService from '../../services/chiSoCoTheService'
import { layLoiApi } from '../../utils/loiApi'
import { ngayVietNam } from '../../utils/nhatKyTap'
import { tinhBmi, diemChiSo, noiDungChiSo } from '../../utils/chiSoCoThe'

export default {
  name: 'ChiSoCoThe',
  components: { CaNhanLayout },
  data() {
    return {
      duLieu: null,
      soNgay: 30,
      denNgay: ngayVietNam(),
      page: 1,
      meta: { total: 0, last_page: 1 },
      cotBieuDo: 'can_nang_kg',
      form: {
        ngay_ghi: ngayVietNam(),
        can_nang_kg: '',
        chieu_cao_cm: '',
        ghi_chu: '',
        updated_at: null,
      },
      suaId: null,
      dangTai: false,
      dangLuu: false,
      loiTai: '',
      loiLuu: '',
      loiTruong: {},
      thongBao: '',
      huyTai: null,
    }
  },
  computed: {
    laPt() {
      return this.$route.meta.vaiTro === 'HUAN_LUYEN_VIEN'
    },
    homNay() {
      return ngayVietNam()
    },
    bmiNhap() {
      return tinhBmi(this.form.can_nang_kg, this.form.chieu_cao_cm)
    },
    bieuDo() {
      return diemChiSo(this.duLieu?.cac_moc || [], this.cotBieuDo)
    },
  },
  watch: {
    '$route.params.khachId'() {
      this.duLieu = null
      this.page = 1
      this.taiDuLieu()
    },
  },
  mounted() {
    this.taiDuLieu()
  },
  beforeUnmount() {
    this.huyTai?.abort()
  },
  methods: {
    so(x) {
      return x === null || x === undefined || x === ''
        ? '—'
        : Number(x).toLocaleString('vi-VN', { maximumFractionDigits: 2 })
    },
    ngay(x) {
      return x ? x.split('-').reverse().join('/') : 'Chưa có lần ghi'
    },
    thayDoi(x) {
      return x === null ? '—' : `${x > 0 ? '+' : ''}${this.so(x)}`
    },
    doiKhoang() {
      if (!this.denNgay || this.denNgay < '1900-01-01' || this.denNgay > this.homNay)
        this.denNgay = this.homNay
      this.page = 1
      this.taiDuLieu()
    },
    chuyenTrang(huong) {
      this.page += huong
      this.taiDuLieu()
    },
    ghiMoi() {
      this.suaId = null
      this.form = {
        ngay_ghi: ngayVietNam(),
        can_nang_kg: '',
        chieu_cao_cm: this.duLieu?.moi_nhat?.chieu_cao_cm ?? '',
        ghi_chu: '',
        updated_at: null,
      }
      this.loiLuu = ''
      this.loiTruong = {}
    },
    async moSua(ban) {
      if (this.laPt || this.dangLuu) return
      this.suaId = ban.id
      this.form = {
        ngay_ghi: ban.ngay_ghi,
        can_nang_kg: ban.can_nang_kg ?? '',
        chieu_cao_cm: ban.chieu_cao_cm ?? '',
        ghi_chu: ban.ghi_chu || '',
        updated_at: ban.updated_at,
      }
      this.loiLuu = ''
      this.loiTruong = {}
      this.thongBao = ''
      await this.$nextTick()
      this.$refs.form?.scrollIntoView({ block: 'center' })
      this.$refs.ngayNhap?.focus({ preventScroll: true })
    },
    async taiDuLieu() {
      this.huyTai?.abort()
      const huy = new AbortController()
      this.huyTai = huy
      this.dangTai = true
      this.loiTai = ''
      // Không giữ chỉ số học viên trên màn hình nếu quyền đã bị thu hồi.
      try {
        const k = await chiSoCoTheService.tai(
          this.laPt ? this.$route.params.khachId : null,
          { so_ngay: this.soNgay, page: this.page, den_ngay: this.denNgay },
          huy.signal,
        )
        if (!huy.signal.aborted) {
          this.duLieu = k.data
          this.meta = k.meta
          if (!this.suaId && this.form.chieu_cao_cm === '')
            this.form.chieu_cao_cm = k.data.moi_nhat?.chieu_cao_cm ?? ''
        }
      } catch (e) {
        if (!huy.signal.aborted) {
          this.loiTai = layLoiApi(e).thongBao
          this.duLieu = null
        }
      } finally {
        if (this.huyTai === huy) this.dangTai = false
      }
    },
    async luu() {
      if (this.laPt || this.dangLuu || this.dangTai) return
      this.dangLuu = true
      this.loiLuu = ''
      this.loiTruong = {}
      this.thongBao = ''
      try {
        const k = await chiSoCoTheService.luu(this.suaId, noiDungChiSo(this.form, this.suaId))
        this.thongBao = k.message
        this.ghiMoi()
        this.page = 1
        await this.taiDuLieu()
        this.ghiMoi()
      } catch (e) {
        const k = layLoiApi(e)
        this.loiLuu = k.thongBao
        this.loiTruong = k.loiTruong
      } finally {
        this.dangLuu = false
      }
    },
  },
}
</script>

<style scoped>
.cs-page {
  padding: 0;
  color: var(--mau-chu);
}
.cs-heading,
.cs-chart-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 22px;
}
.cs-heading h1 {
  font-size: 2rem;
  font-weight: 800;
  margin: 8px 0;
}
.cs-heading p,
.cs-muted,
.cs-panel > p {
  color: var(--mau-phu);
}
.cs-heading p {
  margin-bottom: 0;
}
.cs-eyebrow {
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  font-weight: 800;
  color: var(--mau-chinh);
}
.cs-panel {
  background: var(--mau-the);
  border: 1px solid var(--mau-vien);
  border-radius: 18px;
  padding: 24px;
  min-width: 0;
}
.cs-metrics {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 22px;
}
.cs-metrics span {
  color: var(--mau-phu);
}
.cs-metrics strong {
  display: block;
  font-size: 2rem;
  font-weight: 800;
  margin: 12px 0 4px;
}
.cs-metrics strong small {
  font-size: 0.9rem;
  font-weight: 500;
}
.cs-metrics p {
  font-size: 0.8rem;
  margin: 0;
}
.cs-change {
  border-top: 3px solid var(--mau-chinh);
}
.cs-workspace {
  display: grid;
  grid-template-columns: minmax(300px, 0.8fr) minmax(0, 1.5fr);
  gap: 22px;
  margin-bottom: 22px;
  align-items: start;
}
.cs-readonly {
  grid-template-columns: 1fr;
}
.cs-panel h2 {
  font-size: 1.1rem;
  font-weight: 800;
  margin: 0 0 12px;
}
.cs-form .cs-muted {
  font-size: 0.85rem;
}
.cs-form label {
  display: block;
  font-size: 0.9rem;
  font-weight: 600;
  margin: 12px 0 7px;
}
.cs-form small.text-danger {
  display: block;
  font-size: 0.8rem;
}
.cs-form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.cs-form-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}
.cs-preview {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: var(--mau-nen);
  border-radius: 10px;
  margin-top: 16px;
}
.cs-preview strong {
  font-size: 1.4rem;
  color: var(--mau-chinh);
}
.cs-chart-heading h2,
.cs-chart-heading p {
  margin-bottom: 0;
}
.cs-period {
  display: flex;
  gap: 10px;
  align-items: center;
  font-size: 0.85rem;
  white-space: nowrap;
}
.cs-period select {
  width: auto;
}
.cs-end-date {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--mau-phu);
  font-size: 0.85rem;
  margin-bottom: 16px;
}
.cs-end-date input {
  width: auto;
  min-width: 150px;
}
.cs-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}
.cs-tabs button {
  border: 1px solid var(--mau-vien);
  background: var(--mau-the);
  color: var(--mau-chu);
  border-radius: 9px;
  padding: 10px 14px;
  min-height: 44px;
}
.cs-tabs button[aria-pressed='true'] {
  color: var(--mau-chinh);
  border-color: var(--mau-chinh);
  background: var(--mau-nen);
  font-weight: 700;
}
.cs-svg {
  width: 100%;
  height: auto;
  display: block;
  min-height: 160px;
}
.cs-svg text {
  fill: var(--mau-phu);
  font-size: 13px;
}
.cs-grid {
  stroke: var(--mau-vien);
  stroke-dasharray: 4 4;
}
.cs-line {
  fill: none;
  stroke: var(--mau-chinh);
  stroke-width: 3;
}
.cs-dot {
  fill: var(--mau-the);
  stroke: var(--mau-chinh);
  stroke-width: 2;
}
.cs-bmi-note {
  border-top: 1px solid var(--mau-vien);
  padding-top: 16px;
  margin: 16px 0 0;
  font-size: 0.85rem;
  color: var(--mau-phu);
}
.cs-empty {
  padding: 36px 12px;
  text-align: center;
  color: var(--mau-phu);
}
.cs-table-scroll {
  overflow-x: auto;
  position: relative;
}
.cs-history table {
  --bs-table-bg: transparent;
  --bs-table-color: var(--mau-chu);
  --bs-table-border-color: var(--mau-vien);
  min-width: 580px;
  font-size: 0.9rem;
}
.cs-history th,
.cs-history td {
  padding: 14px 12px;
  white-space: nowrap;
}
.cs-history .cs-note {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  min-width: 140px;
  max-width: 400px;
}
.cs-pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin-top: 20px;
}
.cs-heading .btn,
.cs-form-actions .btn,
.cs-pagination .btn {
  min-height: 44px;
  white-space: nowrap;
}
.cs-history .btn {
  min-height: 44px;
}
@media (min-width: 1600px) {
  .cs-workspace {
    grid-template-columns: minmax(300px, 440px) minmax(0, 1fr);
  }
  .cs-readonly {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 1100px) {
  .cs-workspace {
    grid-template-columns: 1fr;
  }
  .cs-metrics {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 600px) {
  .cs-page {
    padding: 0;
  }
  .cs-panel {
    padding: 18px 14px;
  }
  .cs-heading {
    align-items: flex-start;
  }
  .cs-heading h1 {
    font-size: 1.6rem;
  }
  .cs-heading p {
    font-size: 0.9rem;
  }
  .cs-metrics {
    gap: 10px;
  }
  .cs-metrics strong {
    font-size: 1.6rem;
  }
  .cs-metrics span {
    font-size: 0.85rem;
  }
  .cs-chart-heading {
    flex-wrap: wrap;
    gap: 12px;
  }
  .cs-chart-heading h2 {
    font-size: 1rem;
  }
  .cs-period {
    width: 100%;
    justify-content: space-between;
  }
  .cs-svg {
    min-height: 0;
  }
}
</style>
