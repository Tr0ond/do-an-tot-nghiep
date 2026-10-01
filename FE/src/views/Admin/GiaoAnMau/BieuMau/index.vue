<template>
  <CaNhanLayout>
    <section class="ga">
      <!-- Nút quay lại danh sách -->
      <RouterLink to="/admin/giao-an-mau" class="btn btn-outline-secondary mb-4">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách giáo án
      </RouterLink>

      <!-- Tiêu đề trang & Trạng thái -->
      <header class="ga-head">
        <div>
          <span class="ga-eyebrow">
            <i class="bi bi-pencil-square" aria-hidden="true"></i> Soạn giáo án mẫu
          </span>
          <h1>{{ id ? 'Chỉnh sửa giáo án mẫu' : 'Tạo giáo án mẫu mới' }}</h1>
          <p>
            Thiết lập lịch tập theo ngày, lựa chọn bài tập từ thư viện và định lượng khối lượng tập.
          </p>
        </div>
        <span class="ga-status" :class="trangThai">
          <i class="bi bi-dot fs-5" aria-hidden="true"></i>
          {{ nhanTrangThai[trangThai] }}
        </span>
      </header>

      <!-- Trạng thái Đang tải -->
      <p v-if="dangTai" role="status" class="ga-panel text-center py-4 text-muted">
        <span class="spinner-border spinner-border-sm text-success me-2" role="status"></span>
        Đang tải thông tin giáo án…
      </p>

      <!-- Thông báo thành công -->
      <div
        v-if="thongBao"
        class="alert alert-success d-flex align-items-center gap-2"
        role="status"
      >
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div>{{ thongBao }}</div>
      </div>

      <!-- Thông báo lỗi chung & Lỗi validate từng trường -->
      <div v-if="loi" ref="baoLoi" tabindex="-1" class="alert alert-danger" role="alert">
        <div class="d-flex align-items-center gap-2 mb-1">
          <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
          <strong>{{ loi }}</strong>
        </div>
        <ul v-if="Object.keys(loiTruong).length" class="mb-0 mt-2 ps-3">
          <li v-for="(cacLoi, truong) in loiTruong" :key="truong">{{ cacLoi.join(' ') }}</li>
        </ul>
        <div class="mt-3 d-flex gap-2">
          <button
            v-if="canTaiLai"
            type="button"
            class="btn btn-outline-secondary btn-sm"
            :disabled="dangLuu"
            @click="taiGiaoAn"
          >
            <i class="bi bi-arrow-clockwise me-1"></i> Tải lại bản mới nhất
          </button>
          <RouterLink v-if="hetPhien" to="/dang-nhap" class="btn btn-outline-secondary btn-sm">
            Đăng nhập lại
          </RouterLink>
        </div>
      </div>

      <!-- Form thiết kế giáo án -->
      <form v-if="!dangTai && !loiTai" @submit.prevent="luuGiaoAn">
        <fieldset :disabled="dangLuu || canTaiLai || hetPhien">
          <div class="ga-editor">
            <!-- Cột trái: Form thông tin chung & Danh sách bài trong ngày -->
            <div class="ga-panel">
              <h2 class="mb-3 fs-5">
                <i class="bi bi-card-heading text-success me-2"></i> Thông tin cơ bản
              </h2>

              <div class="ga-fields">
                <div>
                  <label for="ten-giao-an">Tên giáo án *</label>
                  <input
                    id="ten-giao-an"
                    v-model="bieuMau.ten_giao_an"
                    required
                    maxlength="255"
                    placeholder="Ví dụ: Toàn thân 3 ngày cho người mới bắt đầu"
                    :aria-invalid="!!loiTruong.ten_giao_an"
                    aria-describedby="loi-ten-giao-an"
                  />
                  <small id="loi-ten-giao-an" class="ga-errors">
                    {{ loiTruong.ten_giao_an?.join(' ') }}
                  </small>
                </div>

                <div>
                  <label for="so-ngay-giao-an">Số ngày tập (1..30) *</label>
                  <input
                    id="so-ngay-giao-an"
                    v-model="soNgayNhap"
                    type="number"
                    min="1"
                    max="30"
                    required
                  />
                  <small class="ga-errors">{{ loiTruong.so_ngay_tap?.join(' ') }}</small>
                </div>
              </div>

              <div class="mb-3">
                <label for="muc-tieu-giao-an">Mục tiêu & Đối tượng phù hợp</label>
                <textarea
                  id="muc-tieu-giao-an"
                  v-model="bieuMau.muc_tieu"
                  maxlength="255"
                  placeholder="Mô tả mục tiêu (ví dụ: Tăng cơ giảm mỡ, cải thiện sức bền tim mạch…)"
                  rows="2"
                />
                <small class="ga-errors">{{ loiTruong.muc_tieu?.join(' ') }}</small>
              </div>

              <!-- Cảnh báo nếu giáo án đang ở trạng thái ĐÃ DUYỆT -->
              <p v-if="trangThai === 'DA_DUYET'" class="ga-warning">
                <i class="bi bi-info-circle-fill me-1"></i>
                Lưu nội dung thay đổi sẽ chuyển giáo án về bản nháp. Cần duyệt lại để PT thấy bản
                mới.
              </p>

              <!-- Thanh chuyển đổi ngày tập (Image 1 style) -->
              <div class="border-top pt-3 mt-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <h2 class="fs-5">
                    <i class="bi bi-calendar3-week text-success me-2"></i> Lịch các ngày tập
                  </h2>
                  <span class="text-muted small">Tổng {{ bieuMau.so_ngay_tap }} ngày</span>
                </div>

                <nav class="ga-days" aria-label="Chọn ngày tập">
                  <button
                    v-for="ngay in bieuMau.so_ngay_tap"
                    :key="ngay"
                    type="button"
                    class="btn ga-day"
                    :aria-pressed="ngayChon === ngay"
                    @click="ngayChon = ngay"
                  >
                    Ngày {{ ngay }}
                    <small class="badge bg-light text-dark ms-1">· {{ demBai(ngay) }}</small>
                  </button>
                </nav>
              </div>

              <!-- Chi tiết bài tập trong Ngày đang chọn -->
              <div
                class="d-flex justify-content-between align-items-center gap-2 my-3 pb-2 border-bottom"
              >
                <h3 class="fs-5 fw-bold text-dark m-0">
                  <i class="bi bi-list-check text-success me-1"></i>
                  Bài tập trong Ngày {{ ngayChon }}
                </h3>
                <span class="ga-meta fw-bold">{{ baiTrongNgay.length }} bài tập đã chọn</span>
              </div>

              <div v-if="!baiTrongNgay.length" class="text-center py-4 bg-light rounded-3 my-3">
                <i class="bi bi-plus-square text-secondary fs-2 d-block mb-2"></i>
                <p class="mb-0 text-muted small">
                  Chưa có bài tập cho Ngày {{ ngayChon }}. Hãy chọn bài từ thư viện bài tập bên
                  phải.
                </p>
              </div>

              <!-- Danh sách từng dòng bài tập -->
              <article v-for="(bai, index) in baiTrongNgay" :key="bai.khoa" class="ga-row">
                <div class="ga-row-head">
                  <div>
                    <h3 class="d-flex align-items-center gap-2">
                      <span
                        class="badge bg-success text-white rounded-pill"
                        style="font-size: 0.75rem"
                      >
                        {{ index + 1 }}
                      </span>
                      {{ bai.ten_bai_tap }}
                    </h3>
                    <small class="badge bg-light text-secondary border">{{ bai.nhom_co }}</small>
                  </div>

                  <div class="ga-row-actions">
                    <button
                      type="button"
                      class="btn btn-outline-secondary ga-icon"
                      :disabled="index === 0"
                      :aria-label="'Đưa ' + bai.ten_bai_tap + ' lên'"
                      title="Chuyển lên trên"
                      @click="sapXep(bai.khoa, -1)"
                    >
                      <i class="bi bi-arrow-up" aria-hidden="true"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-secondary ga-icon"
                      :disabled="index === baiTrongNgay.length - 1"
                      :aria-label="'Đưa ' + bai.ten_bai_tap + ' xuống'"
                      title="Chuyển xuống dưới"
                      @click="sapXep(bai.khoa, 1)"
                    >
                      <i class="bi bi-arrow-down" aria-hidden="true"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-secondary ga-icon text-danger"
                      :aria-label="'Bỏ ' + bai.ten_bai_tap"
                      title="Xóa bài tập khỏi ngày này"
                      @click="boBai(bai.khoa)"
                    >
                      <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                  </div>
                </div>

                <!-- Cảnh báo nếu bài ngừng sử dụng -->
                <p v-if="!bai.kha_dung" class="ga-warning">
                  <i class="bi bi-exclamation-octagon-fill me-1"></i>
                  Bài hoặc nhóm cơ đã ngừng sử dụng. Cần thay thế bài này trước khi duyệt giáo án.
                </p>

                <!-- 3 cột: Hiệp, Lần lặp, Nghỉ -->
                <div class="ga-prescription">
                  <div>
                    <label :for="'hiep-' + bai.khoa">Số hiệp *</label>
                    <input
                      :id="'hiep-' + bai.khoa"
                      v-model="bai.so_hiep"
                      type="number"
                      min="1"
                      max="100"
                      required
                    />
                  </div>
                  <div>
                    <label :for="'lap-' + bai.khoa">Lần lặp (reps) *</label>
                    <input
                      :id="'lap-' + bai.khoa"
                      v-model="bai.so_lan_lap"
                      type="number"
                      min="1"
                      max="1000"
                      required
                    />
                  </div>
                  <div>
                    <label :for="'nghi-' + bai.khoa">Nghỉ (giây) *</label>
                    <input
                      :id="'nghi-' + bai.khoa"
                      v-model="bai.nghi_giay"
                      type="number"
                      min="0"
                      max="3600"
                      required
                    />
                  </div>
                </div>

                <label :for="'ghi-chu-' + bai.khoa">Ghi chú huấn luyện viên</label>
                <textarea
                  :id="'ghi-chu-' + bai.khoa"
                  v-model="bai.ghi_chu"
                  maxlength="2000"
                  rows="2"
                  placeholder="Ví dụ: Khởi động kĩ khớp vai, giữ nhịp thở đều…"
                />

                <div v-if="loiCuaBai(bai).length" class="ga-errors">
                  {{ loiCuaBai(bai).join(' ') }}
                </div>
              </article>
            </div>

            <!-- Cột phải: Khung tìm và chọn bài tập vào ngày -->
            <ChonBaiTapGiaoAn
              :ngay="ngayChon"
              :vo-hieu="dangLuu || canTaiLai || hetPhien || cacBai.length >= 500"
              @chon="themBai"
            />
          </div>
        </fieldset>

        <!-- Thanh điều khiển phía dưới -->
        <div class="ga-foot">
          <div class="d-flex align-items-center gap-2">
            <span class="ga-meta fw-bold">
              <i class="bi bi-stack me-1"></i>
              {{ bieuMau.so_ngay_tap }} ngày · {{ cacBai.length }} bài tập
            </span>
            <span
              class="badge"
              :class="coThayDoi ? 'bg-warning text-dark' : 'bg-success text-white'"
            >
              {{ coThayDoi ? '● Có thay đổi chưa lưu' : '✓ Dữ liệu đã lưu' }}
            </span>
          </div>

          <div>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="dangLuu || canTaiLai || hetPhien"
            >
              <i class="bi bi-cloud-arrow-up-fill me-1"></i>
              {{ dangLuu ? 'Đang xử lý…' : id ? 'Lưu nội dung' : 'Lưu bản nháp' }}
            </button>

            <button
              v-if="id && trangThai !== 'DA_DUYET'"
              type="button"
              class="btn btn-outline-secondary text-success"
              :disabled="dangLuu || coThayDoi || canTaiLai || hetPhien"
              @click="datTrangThai('DA_DUYET')"
            >
              <i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Duyệt cho PT
            </button>

            <button
              v-if="id && trangThai !== 'NGUNG_SU_DUNG'"
              type="button"
              class="btn btn-outline-secondary text-danger"
              :disabled="dangLuu || coThayDoi || canTaiLai || hetPhien"
              @click="datTrangThai('NGUNG_SU_DUNG')"
            >
              <i class="bi bi-pause-circle me-1"></i> Ngừng sử dụng
            </button>
          </div>
        </div>

        <p v-if="id && coThayDoi" class="mt-3 mb-0 text-muted small">
          <i class="bi bi-info-circle me-1"></i>
          Vui lòng lưu nội dung trước khi chuyển đổi trạng thái Duyệt hoặc Ngừng sử dụng.
        </p>
      </form>
    </section>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../../layouts/CaNhanLayout.vue'
import ChonBaiTapGiaoAn from '../../../../components/ChonBaiTapGiaoAn.vue'
import giaoAnMauService from '../../../../services/giaoAnMauService'
import { danhSoThuTu, doiThuTu, nhanTrangThai, taoNoiDung } from '../../../../utils/giaoAnMau'
import { layLoiApi } from '../../../../utils/loiApi'
import '../../../../assets/giaoAnMau.css'

export default {
  name: 'BieuMauGiaoAn',
  components: { CaNhanLayout, ChonBaiTapGiaoAn },
  data() {
    return {
      nhanTrangThai,
      id: null,
      bieuMau: { ten_giao_an: '', muc_tieu: '', so_ngay_tap: 1 },
      soNgayNhap: 1,
      cacBai: [],
      ngayChon: 1,
      trangThai: 'NHAP',
      phienBan: '',
      noiDungCu: '',
      maYeuCau: crypto.randomUUID(),
      dangTai: false,
      dangLuu: false,
      loi: '',
      loiTruong: {},
      loiTai: false,
      canTaiLai: false,
      hetPhien: false,
      thongBao: '',
      huyTai: null,
      conSong: true,
    }
  },
  computed: {
    baiTrongNgay() {
      return this.cacBai.filter((bai) => Number(bai.ngay_thu) === this.ngayChon)
    },
    coThayDoi() {
      return JSON.stringify(taoNoiDung(this.bieuMau, this.cacBai)) !== this.noiDungCu
    },
  },
  mounted() {
    this.taiGiaoAn()
  },
  watch: {
    soNgayNhap() {
      this.doiSoNgay()
    },
    '$route.params.id'() {
      this.taiGiaoAn()
    },
  },
  beforeUnmount() {
    this.conSong = false
    this.huyTai?.abort()
  },
  methods: {
    demBai(ngay) {
      return this.cacBai.filter((bai) => Number(bai.ngay_thu) === ngay).length
    },
    doiSoNgay() {
      const soNgay = Number(this.soNgayNhap)
      if (!Number.isInteger(soNgay) || soNgay < 1 || soNgay > 30) return
      if (this.cacBai.some((bai) => Number(bai.ngay_thu) > soNgay)) {
        this.soNgayNhap = this.bieuMau.so_ngay_tap
        this.loi = 'Hãy bỏ các bài ở ngày bị giảm trước khi thay đổi số ngày.'
        return
      }
      this.bieuMau.so_ngay_tap = soNgay
      this.ngayChon = Math.min(this.ngayChon, soNgay)
    },
    themBai(bai) {
      if (this.dangLuu || this.canTaiLai || this.hetPhien || this.cacBai.length >= 500) return
      this.cacBai = danhSoThuTu([
        ...this.cacBai,
        {
          khoa: crypto.randomUUID(),
          bai_tap_id: bai.id,
          ten_bai_tap: bai.ten_tieng_viet || bai.ten_bai_tap,
          nhom_co: bai.nhom_co.ten_nhom_co,
          kha_dung: true,
          ngay_thu: this.ngayChon,
          so_hiep: 3,
          so_lan_lap: 12,
          nghi_giay: 60,
          ghi_chu: '',
        },
      ])
    },
    sapXep(khoa, huong) {
      this.cacBai = doiThuTu(this.cacBai, khoa, huong)
    },
    boBai(khoa) {
      this.cacBai = danhSoThuTu(this.cacBai.filter((bai) => bai.khoa !== khoa))
    },
    loiCuaBai(bai) {
      const index = this.cacBai.findIndex((dong) => dong.khoa === bai.khoa)
      return Object.entries(this.loiTruong)
        .filter(([truong]) => truong.startsWith('bai_tap.' + index + '.'))
        .flatMap(([, loi]) => loi)
    },
    napDuLieu(giaoAn) {
      this.id = giaoAn.id
      this.bieuMau = {
        ten_giao_an: giaoAn.ten_giao_an,
        muc_tieu: giaoAn.muc_tieu || '',
        so_ngay_tap: giaoAn.so_ngay_tap,
      }
      this.soNgayNhap = giaoAn.so_ngay_tap
      this.cacBai = giaoAn.bai_tap.map((bai) => ({
        ...bai,
        khoa: crypto.randomUUID(),
        ghi_chu: bai.ghi_chu || '',
      }))
      this.trangThai = giaoAn.trang_thai
      this.phienBan = giaoAn.updated_at
      this.ngayChon = Math.min(this.ngayChon, giaoAn.so_ngay_tap)
      this.noiDungCu = JSON.stringify(taoNoiDung(this.bieuMau, this.cacBai))
    },
    async taiGiaoAn() {
      this.huyTai?.abort()
      this.loi = ''
      this.loiTruong = {}
      this.canTaiLai = false
      this.hetPhien = false
      this.loiTai = false
      if (!this.$route.params.id) {
        this.id = null
        this.bieuMau = { ten_giao_an: '', muc_tieu: '', so_ngay_tap: 1 }
        this.soNgayNhap = 1
        this.cacBai = []
        this.trangThai = 'NHAP'
        this.ngayChon = 1
        this.phienBan = ''
        this.maYeuCau = crypto.randomUUID()
        this.noiDungCu = JSON.stringify(taoNoiDung(this.bieuMau, this.cacBai))
        this.dangTai = false
        return
      }
      const huy = new AbortController()
      this.huyTai = huy
      this.dangTai = true
      try {
        const ketQua = await giaoAnMauService.taiChiTiet(this.$route.params.id, huy.signal, true)
        if (!huy.signal.aborted) this.napDuLieu(ketQua.data)
      } catch (loi) {
        if (!huy.signal.aborted) {
          this.hetPhien = layLoiApi(loi).hetPhien
          this.loi =
            loi.response?.status === 404 ? 'Không tìm thấy giáo án.' : layLoiApi(loi).thongBao
          this.loiTai = true
          this.canTaiLai = true
        }
      } finally {
        if (this.huyTai === huy) this.dangTai = false
      }
    },
    async baoLoiApi(loi) {
      const ketQua = layLoiApi(loi)
      this.loi = ketQua.thongBao
      this.loiTruong = ketQua.loiTruong
      this.canTaiLai = loi.response?.status === 409
      this.hetPhien = ketQua.hetPhien
      await this.$nextTick()
      this.$refs.baoLoi?.focus()
    },
    async luuGiaoAn() {
      if (this.dangLuu || this.canTaiLai || this.hetPhien) return
      this.dangLuu = true
      this.loi = ''
      this.loiTruong = {}
      this.thongBao = ''
      const duongDan = this.$route.path
      try {
        const duLieu = taoNoiDung(this.bieuMau, this.cacBai)
        const ketQua = this.id
          ? await giaoAnMauService.suaGiaoAn(this.id, { ...duLieu, updated_at: this.phienBan })
          : await giaoAnMauService.taoGiaoAn({ ...duLieu, client_request_id: this.maYeuCau })
        if (!this.conSong || duongDan !== this.$route.path) return
        this.napDuLieu(ketQua.data)
        this.thongBao = ketQua.message
        if (!this.$route.params.id)
          await this.$router.replace('/admin/giao-an-mau/' + this.id + '/sua')
      } catch (loi) {
        if (this.conSong && duongDan === this.$route.path) await this.baoLoiApi(loi)
      } finally {
        this.dangLuu = false
      }
    },
    async datTrangThai(trangThai) {
      if (!this.id || this.dangLuu || this.coThayDoi || this.canTaiLai || this.hetPhien) return
      this.dangLuu = true
      this.loi = ''
      this.loiTruong = {}
      this.thongBao = ''
      const duongDan = this.$route.path
      try {
        const ketQua = await giaoAnMauService.datTrangThai(this.id, {
          trang_thai: trangThai,
          updated_at: this.phienBan,
        })
        if (!this.conSong || duongDan !== this.$route.path) return
        this.napDuLieu(ketQua.data)
        this.thongBao = ketQua.message
      } catch (loi) {
        if (this.conSong && duongDan === this.$route.path) await this.baoLoiApi(loi)
      } finally {
        this.dangLuu = false
      }
    },
  },
}
</script>
