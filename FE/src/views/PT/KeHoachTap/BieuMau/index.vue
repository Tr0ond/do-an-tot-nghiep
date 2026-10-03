<template>
  <CaNhanLayout>
    <section class="ga kh-plan">
      <RouterLink :to="quayLai" class="btn btn-outline-secondary kh-back-btn mb-3">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách giáo án
      </RouterLink>
      <header class="kh-heading">
        <div>
          <span class="ga-eyebrow">
            <i class="bi bi-journal-text me-1" aria-hidden="true"></i> SOẠN GIÁO ÁN CÁ NHÂN
          </span>
          <h1>{{ $route.params.id ? 'Sửa bản nháp' : 'Giáo án mới' }}</h1>
          <p>
            {{
              laPt
                ? `${hocVien ? `Dành cho ${hocVien.ho_ten}. ` : ''}Lưu bản nháp trước, kiểm tra nội dung rồi gửi cho học viên.`
                : 'Tự chọn bài và thông số phù hợp với bạn. Không cần mua gói hoặc chờ PT duyệt.'
            }}
          </p>
        </div>
      </header>
      <p v-if="dangTai" role="status" class="text-secondary">Đang tải dữ liệu…</p>
      <div v-if="loi" class="kh-notice" role="alert">
        <div class="d-flex align-items-center gap-2 mb-2 text-danger fw-bold">
          <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
          <span>Có lỗi xảy ra</span>
        </div>
        <p class="mb-2">{{ loi }}</p>
        <ul v-if="Object.keys(loiTruong).length" class="mb-3">
          <li v-for="(giaTri, truong) in loiTruong" :key="truong">{{ giaTri.join(' ') }}</li>
        </ul>
        <button
          v-if="!sanSang || xungDot"
          type="button"
          class="btn btn-outline-secondary btn-sm"
          :disabled="dangLuu"
          @click="taiLai"
        >
          <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> Tải lại dữ liệu
        </button>
      </div>
      <div v-if="choThuLai" class="kh-notice">
        <div class="d-flex align-items-center gap-2 mb-2 text-warning fw-bold">
          <i class="bi bi-wifi-off" aria-hidden="true"></i>
          <span>Chưa thể kết nối tới máy chủ</span>
        </div>
        <p class="mb-3">
          Chưa biết yêu cầu lưu đã thành công hay chưa. Nội dung được giữ nguyên để thử lại an toàn.
        </p>
        <button type="button" class="btn btn-primary" :disabled="dangLuu" @click="luu">
          <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> Thử lưu lại
        </button>
      </div>
      <form v-if="sanSang && !dangTai" @submit.prevent="luu">
        <fieldset :disabled="voHieu" class="border-0 p-0 m-0">
          <div class="ga-panel mb-4">
            <h2 class="mb-3">Thông tin giáo án</h2>
            <div class="kh-fields">
              <div>
                <label for="ten-ke-hoach" class="form-label small fw-bold">Tên giáo án *</label>
                <input
                  id="ten-ke-hoach"
                  v-model="form.ten_ke_hoach"
                  required
                  maxlength="255"
                  class="form-control"
                  placeholder="Ví dụ: Lịch tập Hypertrophy 4 ngày"
                  :aria-invalid="!!loiTruong.ten_ke_hoach"
                />
                <p v-if="loiTruong.ten_ke_hoach" class="kh-error">
                  {{ loiTruong.ten_ke_hoach.join(' ') }}
                </p>
              </div>
              <div>
                <label for="so-ngay-ke-hoach" class="form-label small fw-bold">Số ngày tập</label>
                <div class="d-flex align-items-center gap-2">
                  <input
                    id="so-ngay-ke-hoach"
                    :value="form.so_ngay_tap"
                    type="number"
                    min="1"
                    max="30"
                    readonly
                    class="form-control text-center fw-bold"
                    style="max-width: 80px"
                  />
                  <div class="kh-actions">
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      :disabled="form.so_ngay_tap <= 1"
                      @click="botNgay"
                    >
                      <i class="bi bi-dash" aria-hidden="true"></i> Bớt ngày
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      :disabled="form.so_ngay_tap >= 30"
                      @click="form.so_ngay_tap++"
                    >
                      <i class="bi bi-plus" aria-hidden="true"></i> Thêm ngày
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-3">
              <label for="muc-tieu-ke-hoach" class="form-label small fw-bold"
                >Mục tiêu tập luyện</label
              >
              <input
                id="muc-tieu-ke-hoach"
                v-model="form.muc_tieu"
                maxlength="255"
                class="form-control"
                placeholder="Ví dụ: Tăng cơ giảm mỡ, cải thiện sức mạnh thân trên"
              />
            </div>
          </div>
          <div v-if="laPt" class="ga-panel mb-4">
            <h2 class="mb-2">Bắt đầu từ giáo án mẫu</h2>
            <p class="text-secondary small mb-3">
              Tùy chỉnh bài tập, mức tạ và số hiệp theo học viên sau khi lấy mẫu.
            </p>
            <div class="kh-filter-toolbar py-3 px-3 mb-0">
              <div class="flex-grow-1" style="min-width: 240px">
                <label for="chon-mau-ke-hoach" class="form-label small fw-bold"
                  >Giáo án đã duyệt</label
                >
                <select id="chon-mau-ke-hoach" v-model="mauChon" class="kh-filter-select w-100">
                  <option value="">Chọn mẫu để lấy nội dung</option>
                  <option v-for="m in cacMau" :key="m.id" :value="m.id">
                    {{ m.ten_giao_an }} — {{ m.so_ngay_tap }} ngày
                  </option>
                </select>
              </div>
              <button
                type="button"
                class="btn btn-outline-secondary align-self-end"
                :disabled="!mauChon || dangLayMau"
                @click="layMau"
              >
                <i class="bi bi-download me-1" aria-hidden="true"></i>
                {{ dangLayMau ? 'Đang lấy…' : 'Lấy nội dung mẫu' }}
              </button>
            </div>
            <p v-if="loiMau" class="kh-error mt-2" role="alert">
              {{ loiMau }}
              <button type="button" class="btn btn-outline-secondary btn-sm ms-2" @click="taiMau">
                Thử tải mẫu
              </button>
            </p>
            <nav v-if="metaMau.last_page > 1" class="kh-pages" aria-label="Phân trang giáo án mẫu">
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                :disabled="pageMau <= 1 || dangLayMau"
                @click="doiTrangMau(-1)"
              >
                Mẫu trước
              </button>
              <span class="small fw-bold">{{ pageMau }} / {{ metaMau.last_page }}</span>
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                :disabled="pageMau >= metaMau.last_page || dangLayMau"
                @click="doiTrangMau(1)"
              >
                Mẫu sau
              </button>
            </nav>
          </div>
        </fieldset>
        <div class="kh-day-tabs" aria-label="Chọn ngày tập">
          <button
            v-for="ngay in form.so_ngay_tap"
            :key="ngay"
            type="button"
            class="btn btn-outline-secondary kh-day-tab-btn"
            :class="{ 'is-active': ngayChon === ngay }"
            :aria-pressed="ngayChon === ngay"
            @click="ngayChon = ngay"
          >
            <span>Ngày {{ ngay }}</span>
            <span class="kh-day-count-badge">
              {{ cacBai.filter((b) => Number(b.ngay_thu) === ngay).length }} bài
            </span>
          </button>
        </div>
        <div class="kh-editor">
          <div class="ga-panel kh-editor-main">
            <div
              class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-secondary border-opacity-25"
            >
              <h2 class="kh-day-title">
                <span class="kh-day-pill">Ngày {{ ngayChon }}</span>
                <span>Danh sách bài tập</span>
              </h2>
              <span class="small text-secondary fw-bold"> {{ baiTrongNgay.length }} bài </span>
            </div>
            <p v-if="!baiTrongNgay.length" class="text-secondary mt-3 py-4 text-center">
              <i class="bi bi-plus-circle fs-3 text-secondary d-block mb-2" aria-hidden="true"></i>
              Chọn bài từ thư viện để bắt đầu.<br />
              Mỗi ngày cần ít nhất một bài khi {{ laPt ? 'gửi' : 'áp dụng' }}.
            </p>
            <fieldset :disabled="voHieu" class="border-0 p-0 m-0">
              <article v-for="(b, viTri) in baiTrongNgay" :key="b.khoa" class="kh-editor-row">
                <div class="kh-row-title">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary rounded-pill">{{ viTri + 1 }}</span>
                    <strong>{{ b.ten_bai_tap }}</strong>
                  </div>
                  <div class="kh-actions">
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-sm"
                      :disabled="viTri === 0"
                      :aria-label="`Đưa ${b.ten_bai_tap} lên`"
                      title="Đưa lên"
                      @click="doiBai(b.khoa, -1)"
                    >
                      <i class="bi bi-arrow-up" aria-hidden="true"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-sm"
                      :disabled="viTri === baiTrongNgay.length - 1"
                      :aria-label="`Đưa ${b.ten_bai_tap} xuống`"
                      title="Đưa xuống"
                      @click="doiBai(b.khoa, 1)"
                    >
                      <i class="bi bi-arrow-down" aria-hidden="true"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-sm text-danger"
                      :aria-label="`Bỏ ${b.ten_bai_tap}`"
                      title="Bỏ bài"
                      @click="boBai(b.khoa)"
                    >
                      <i class="bi bi-trash" aria-hidden="true"></i>
                    </button>
                  </div>
                </div>
                <div class="kh-inputs">
                  <div v-for="truong in truongSo" :key="truong.key" class="kh-input-field">
                    <label :for="`${truong.key}-${b.khoa}`">{{ truong.ten }}</label>
                    <div class="kh-input-wrapper">
                      <input
                        :id="`${truong.key}-${b.khoa}`"
                        v-model="b[truong.key]"
                        type="number"
                        :required="truong.key !== 'muc_ta_kg'"
                        :min="truong.min"
                        :max="truong.max"
                        :step="truong.step || 1"
                      />
                      <span v-if="truong.donVi" class="kh-input-unit">{{ truong.donVi }}</span>
                    </div>
                  </div>
                </div>
                <div class="mt-3">
                  <label :for="`ghi-chu-${b.khoa}`" class="form-label small fw-bold text-secondary">
                    Lưu ý thực hiện
                  </label>
                  <textarea
                    :id="`ghi-chu-${b.khoa}`"
                    v-model="b.ghi_chu"
                    maxlength="2000"
                    rows="2"
                    class="form-control"
                    placeholder="Lưu ý kỹ thuật hoặc dặn dò học viên..."
                  ></textarea>
                </div>
              </article>
            </fieldset>
          </div>
          <ChonBaiTapGiaoAn
            :ngay="ngayChon"
            :vo-hieu="voHieu || cacBai.length >= 500"
            @chon="themBai"
          />
        </div>
        <div class="kh-actions mt-4 pt-3 border-top border-secondary border-opacity-25">
          <button class="btn btn-primary" :disabled="voHieu">
            <i class="bi bi-save me-1" aria-hidden="true"></i>
            {{ dangLuu ? 'Đang lưu…' : 'Lưu bản nháp' }}
          </button>
          <RouterLink :to="quayLai" class="btn btn-outline-secondary">Quay lại</RouterLink>
        </div>
      </form>
    </section>
  </CaNhanLayout>
</template>
<script>
import CaNhanLayout from '../../../../layouts/CaNhanLayout.vue'
import ChonBaiTapGiaoAn from '../../../../components/ChonBaiTapGiaoAn.vue'
import keHoachTapService from '../../../../services/keHoachTapService'
import giaoAnMauService from '../../../../services/giaoAnMauService'
import { layLoiApi } from '../../../../utils/loiApi'
import { noiDungKeHoach } from '../../../../utils/keHoachTap'
import { doiThuTu, danhSoThuTu } from '../../../../utils/giaoAnMau'
import '../../../../assets/giaoAnMau.css'
import '../../../../assets/keHoachTap.css'
const moi = () => ({ ten_ke_hoach: '', muc_tieu: '', so_ngay_tap: 1, giao_an_mau_id: null })
export default {
  name: 'BieuMauKeHoachTap',
  components: { CaNhanLayout, ChonBaiTapGiaoAn },
  data() {
    return {
      form: moi(),
      cacBai: [],
      ngayChon: 1,
      hocVien: null,
      khachId: null,
      cacMau: [],
      mauChon: '',
      pageMau: 1,
      metaMau: { last_page: 1 },
      loiMau: '',
      dangLayMau: false,
      dangTai: false,
      dangLuu: false,
      sanSang: false,
      loi: '',
      loiTruong: {},
      xungDot: false,
      choThuLai: false,
      yeuCauCho: null,
      uuid: crypto.randomUUID(),
      updatedAt: '',
      banDau: '',
      daLuu: false,
      huyTai: null,
      huyMau: null,
      truongSo: [
        { key: 'so_hiep', ten: 'Số hiệp', donVi: 'hiệp', min: 1, max: 100 },
        { key: 'so_lan_lap', ten: 'Số lần lặp', donVi: 'lần', min: 1, max: 1000 },
        { key: 'nghi_giay', ten: 'Nghỉ (giây)', donVi: 'giây', min: 0, max: 3600 },
        { key: 'muc_ta_kg', ten: 'Mức tạ (kg)', donVi: 'kg', min: 0, max: 1000, step: '.01' },
      ],
    }
  },
  computed: {
    laPt() {
      return this.$route.meta?.vaiTro !== 'KHACH_HANG'
    },
    quayLai() {
      if (!this.laPt) return '/khach-hang/ke-hoach'
      return this.khachId ? `/pt/hoc-vien/${this.khachId}/ke-hoach` : '/pt/hoc-vien'
    },
    voHieu() {
      return this.dangLuu || this.choThuLai || this.xungDot || this.dangLayMau
    },
    baiTrongNgay() {
      return this.cacBai.filter((b) => Number(b.ngay_thu) === this.ngayChon)
    },
    daSua() {
      return (
        this.sanSang &&
        !this.daLuu &&
        this.banDau !== JSON.stringify(noiDungKeHoach(this.form, this.cacBai))
      )
    },
  },
  mounted() {
    this.taiDuLieu()
    if (this.laPt) this.taiMau()
    window.addEventListener('beforeunload', this.truocDong)
  },
  beforeUnmount() {
    this.huyTai?.abort()
    this.huyMau?.abort()
    window.removeEventListener('beforeunload', this.truocDong)
  },
  beforeRouteLeave() {
    if (this.dangLuu) return false
    if (
      (this.daSua || this.choThuLai) &&
      !window.confirm('Rời trang soạn giáo án? Nội dung chưa lưu sẽ không được giữ.')
    )
      return false
  },
  beforeRouteUpdate() {
    if (this.dangLuu || (this.daSua && !window.confirm('Chuyển giáo án và bỏ nội dung chưa lưu?')))
      return false
  },
  watch: {
    '$route.fullPath'() {
      this.taiDuLieu()
    },
  },
  methods: {
    truocDong(e) {
      if (this.daSua || this.choThuLai || this.dangLuu) {
        e.preventDefault()
        e.returnValue = ''
      }
    },
    async taiLai() {
      if (this.daSua && !window.confirm('Tải lại sẽ bỏ nội dung đang sửa. Tiếp tục?')) return
      await this.taiDuLieu()
    },
    async taiDuLieu() {
      this.huyTai?.abort()
      const huy = new AbortController()
      this.huyTai = huy
      this.dangTai = true
      this.sanSang = false
      this.loi = ''
      this.loiTruong = {}
      this.xungDot = false
      this.choThuLai = false
      this.yeuCauCho = null
      this.daLuu = false
      this.form = moi()
      this.cacBai = []
      this.ngayChon = 1
      this.uuid = crypto.randomUUID()
      try {
        if (this.$route.params.id) {
          const k = (
            await keHoachTapService.taiChiTiet(this.$route.params.id, this.laPt, huy.signal)
          ).data
          if (huy.signal.aborted) return
          if (!k.co_the_sua) {
            this.loi = 'Giáo án không còn là bản nháp của bạn. Mở chi tiết để xem nội dung.'
            return
          }
          this.form = {
            ten_ke_hoach: k.ten_ke_hoach,
            muc_tieu: k.muc_tieu || '',
            so_ngay_tap: k.so_ngay_tap,
            giao_an_mau_id: k.giao_an_mau_id,
          }
          this.cacBai = k.bai_tap.map((b) => ({ ...b, khoa: crypto.randomUUID() }))
          this.updatedAt = k.updated_at
          this.khachId = k.khach_hang_id
        } else this.khachId = this.laPt ? this.$route.params.khachId : null
        if (!this.laPt) {
          this.sanSang = true
          this.banDau = JSON.stringify(noiDungKeHoach(this.form, this.cacBai))
          return
        }
        const k = await keHoachTapService.taiDanhSach(this.khachId, { page: 1 }, huy.signal)
        if (!huy.signal.aborted) {
          this.hocVien = k.meta.hoc_vien
          this.sanSang = true
          this.banDau = JSON.stringify(noiDungKeHoach(this.form, this.cacBai))
        }
      } catch (e) {
        if (!huy.signal.aborted) this.loi = layLoiApi(e).thongBao
      } finally {
        if (this.huyTai === huy) this.dangTai = false
      }
    },
    async taiMau() {
      this.huyMau?.abort()
      const huy = new AbortController()
      this.huyMau = huy
      this.loiMau = ''
      this.dangLayMau = true
      try {
        const k = await giaoAnMauService.taiDanhSach(
          { page: this.pageMau, per_page: 20 },
          huy.signal,
        )
        if (!huy.signal.aborted) {
          this.cacMau = k.data
          this.metaMau = k.meta
          this.mauChon = ''
        }
      } catch (e) {
        if (!huy.signal.aborted) this.loiMau = layLoiApi(e).thongBao
      } finally {
        if (this.huyMau === huy) this.dangLayMau = false
      }
    },
    doiTrangMau(huong) {
      this.pageMau += huong
      this.taiMau()
    },
    async layMau() {
      if (this.voHieu || !this.mauChon) return
      if (
        (this.cacBai.length || this.form.ten_ke_hoach) &&
        !window.confirm('Lấy mẫu sẽ thay nội dung giáo án đang soạn. Tiếp tục?')
      )
        return
      this.dangLayMau = true
      this.loiMau = ''
      const huy = new AbortController()
      this.huyMau?.abort()
      this.huyMau = huy
      try {
        const k = (await giaoAnMauService.taiChiTiet(this.mauChon, huy.signal)).data
        if (!huy.signal.aborted) {
          this.form = {
            ten_ke_hoach: k.ten_giao_an,
            muc_tieu: k.muc_tieu || '',
            so_ngay_tap: k.so_ngay_tap,
            giao_an_mau_id: k.id,
          }
          this.cacBai = k.bai_tap.map((b) => ({ ...b, khoa: crypto.randomUUID(), muc_ta_kg: null }))
          this.ngayChon = 1
        }
      } catch (e) {
        if (!huy.signal.aborted) this.loiMau = layLoiApi(e).thongBao
      } finally {
        if (this.huyMau === huy) this.dangLayMau = false
      }
    },
    themBai(b) {
      if (this.voHieu || this.cacBai.length >= 500) return
      this.cacBai = danhSoThuTu([
        ...this.cacBai,
        {
          khoa: crypto.randomUUID(),
          bai_tap_id: b.id,
          ten_bai_tap: b.ten_tieng_viet || b.ten_bai_tap,
          ngay_thu: this.ngayChon,
          so_hiep: 3,
          so_lan_lap: 12,
          nghi_giay: 60,
          muc_ta_kg: null,
          ghi_chu: '',
        },
      ])
    },
    boBai(khoa) {
      this.cacBai = danhSoThuTu(this.cacBai.filter((b) => b.khoa !== khoa))
    },
    doiBai(khoa, huong) {
      this.cacBai = doiThuTu(this.cacBai, khoa, huong)
    },
    botNgay() {
      if (this.form.so_ngay_tap <= 1) return
      if (
        this.cacBai.some((b) => Number(b.ngay_thu) === this.form.so_ngay_tap) &&
        !window.confirm('Bỏ ngày cuối và các bài tập của ngày đó?')
      )
        return
      this.cacBai = this.cacBai.filter((b) => Number(b.ngay_thu) < this.form.so_ngay_tap)
      this.form.so_ngay_tap--
      this.ngayChon = Math.min(this.ngayChon, this.form.so_ngay_tap)
    },
    async luu() {
      if (this.dangLuu || this.xungDot || !this.sanSang) return
      this.dangLuu = true
      this.loi = ''
      this.loiTruong = {}
      const id = this.$route.params.id
      this.yeuCauCho ||= {
        ...noiDungKeHoach(this.form, this.cacBai),
        ...(id ? { updated_at: this.updatedAt } : { client_request_id: this.uuid }),
      }
      try {
        const k = id
          ? await keHoachTapService.sua(id, this.yeuCauCho, this.laPt)
          : await keHoachTapService.tao(this.laPt ? this.khachId : null, this.yeuCauCho)
        this.daLuu = true
        this.choThuLai = false
        this.dangLuu = false
        await this.$router.push(`/${this.laPt ? 'pt' : 'khach-hang'}/ke-hoach/${k.data.id}`)
      } catch (e) {
        const loi = layLoiApi(e)
        this.loi = loi.thongBao
        this.loiTruong = loi.loiTruong
        this.xungDot = e.response?.status === 409
        this.choThuLai = !e.response || e.response.status >= 500
        if (!this.choThuLai) this.yeuCauCho = null
      } finally {
        this.dangLuu = false
      }
    },
  },
}
</script>
