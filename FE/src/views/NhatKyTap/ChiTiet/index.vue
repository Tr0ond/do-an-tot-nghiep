<template>
  <CaNhanLayout>
    <section class="nk">
      <RouterLink :to="quayLai" class="nk-back">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Lịch & nhật ký tập
      </RouterLink>

      <p v-if="dangTai" role="status" class="text-secondary py-3">
        <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i> Đang tải buổi tập…
      </p>

      <!-- Alert thông báo lỗi -->
      <div v-if="loi" class="nk-alert" role="alert">
        <div class="d-flex align-items-center gap-2 fw-bold">
          <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
          <span>Có lỗi xảy ra</span>
        </div>
        <p class="mb-1">{{ loi }}</p>
        <ul v-if="Object.keys(loiTruong).length" class="mb-2">
          <li v-for="(giaTri, ten) in loiTruong" :key="ten">{{ giaTri.join(' ') }}</li>
        </ul>
        <button
          class="btn btn-outline-secondary btn-sm align-self-start mt-1"
          :disabled="dangLuu"
          @click="taiLai"
        >
          <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> Tải lại kết quả
        </button>
      </div>

      <!-- Alert thông báo thành công -->
      <div v-if="thanhCong" class="nk-success" role="status">
        <div class="d-flex align-items-center gap-2 fw-bold">
          <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
          <span>{{ thanhCong }}</span>
        </div>
      </div>

      <template v-if="lich && !dangTai">
        <!-- HEADER / HERO CARD -->
        <div class="nk-panel mb-4">
          <header class="nk-heading mb-0">
            <div>
              <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="nk-badge" :class="lich.trang_thai">
                  <i
                    v-if="lich.trang_thai === 'HOAN_THANH'"
                    class="bi bi-check-circle-fill"
                    aria-hidden="true"
                  ></i>
                  <i
                    v-else-if="lich.trang_thai === 'DANG_TAP'"
                    class="bi bi-play-circle-fill"
                    aria-hidden="true"
                  ></i>
                  <i
                    v-else-if="lich.trang_thai === 'DA_LEN_LICH'"
                    class="bi bi-calendar3"
                    aria-hidden="true"
                  ></i>
                  <i v-else class="bi bi-x-circle" aria-hidden="true"></i>
                  {{ nhanTrangThai[lich.trang_thai] }}
                </span>
                <span class="small text-secondary">
                  <i
                    :class="lich.nguon_tao === 'PT' ? 'bi bi-person-badge' : 'bi bi-person'"
                    class="me-1"
                    aria-hidden="true"
                  ></i
                  >{{ lich.nguon_tao === 'PT' ? 'Giáo án PT giao' : 'Giáo án tự tạo' }}
                </span>
              </div>
              <h1>{{ lich.ten_ke_hoach }}</h1>
              <p class="mb-0 text-secondary">
                <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ ngay(lich.ngay_tap) }} ·
                Ngày {{ lich.ngay_thu }} trong giáo án · {{ lich.bai_tap?.length || 0 }} bài tập
              </p>
            </div>

            <div class="nk-actions">
              <button
                v-if="lich.co_the_bat_dau"
                class="btn btn-primary"
                :disabled="dangLuu"
                @click="thaoTac('bat-dau')"
              >
                <i class="bi bi-play-circle-fill me-1" aria-hidden="true"></i>
                Bắt đầu ghi kết quả
              </button>
              <button
                v-if="lich.co_the_huy"
                class="btn btn-outline-secondary"
                :disabled="dangLuu"
                @click="deNghiThaoTac('huy')"
              >
                <i class="bi bi-x-circle me-1" aria-hidden="true"></i> Hủy buổi tự tập
              </button>
            </div>
          </header>

          <p class="nk-footnote mt-3 mb-0">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <span>
              {{
                lich.trang_thai === 'DA_LEN_LICH'
                  ? 'Đến ngày tập, KH bấm bắt đầu để nhập kết quả thực tế.'
                  : lich.trang_thai === 'HOAN_THANH'
                    ? `Hoàn thành lúc ${thoiGian(lich.phien?.hoan_thanh_luc)}. Kết quả đã được giữ nguyên.`
                    : lich.trang_thai === 'DA_HUY'
                      ? 'Buổi đã hủy. Kết quả nháp được giữ trong lịch sử và không tính vào thống kê.'
                      : 'Nhập số liệu thực tế sau mỗi hiệp, rồi lưu nháp. Bạn có thể quay lại ghi tiếp.'
              }}
              Tự tập hoàn toàn không trừ lượt PT.
            </span>
          </p>
        </div>

        <!-- HỘP XÁC NHẬN THAO TÁC (ref="xacNhan", tabindex="-1") -->
        <section
          v-if="hanhDongCho"
          ref="xacNhan"
          tabindex="-1"
          class="nk-panel nk-confirm"
          role="region"
          aria-label="Xác nhận thao tác buổi tập"
          @keydown.esc="hanhDongCho = ''"
        >
          <h2>{{ hanhDongCho === 'hoan-thanh' ? 'Hoàn thành buổi tập?' : 'Hủy buổi tự tập?' }}</h2>
          <p>
            {{
              hanhDongCho === 'hoan-thanh'
                ? 'Kết quả sẽ được giữ nguyên và không thể sửa. Mỗi bài cần có ít nhất một hiệp thực tế.'
                : 'Kết quả đã lưu vẫn giữ trong lịch sử; nội dung chưa lưu sẽ bỏ đi.'
            }}
          </p>
          <div class="nk-actions">
            <button
              class="btn btn-primary"
              :disabled="dangLuu"
              @click="hanhDongCho === 'hoan-thanh' ? hoanThanh() : huyBuoi()"
            >
              Đồng ý, tiếp tục
            </button>
            <button class="btn btn-outline-secondary" :disabled="dangLuu" @click="hanhDongCho = ''">
              Để sau
            </button>
          </div>
        </section>

        <!-- GRID CHI TIẾT & NHẬN XÉT -->
        <div class="nk-grid nk-detail-grid">
          <form class="nk-main" @submit.prevent="luuNhap">
            <!-- TỪNG BÀI TẬP TRONG BUỔI -->
            <article
              v-for="(b, viTri) in lich.bai_tap"
              :key="b.id || viTri"
              class="nk-panel nk-exercise"
            >
              <header class="nk-exercise-heading">
                <div class="nk-exercise-image">
                  <img
                    v-if="media(b) && !anhLoi[b.bai_tap_id]"
                    :src="media(b)"
                    :alt="b.ten_bai_tap"
                    loading="lazy"
                    @error="anhLoi[b.bai_tap_id] = true"
                  />
                  <i v-else class="bi bi-person-arms-up" aria-hidden="true"></i>
                </div>
                <div>
                  <h2>{{ viTri + 1 }}. {{ b.ten_bai_tap }}</h2>
                  <div class="nk-target-chips">
                    <span class="nk-chip">
                      <i class="bi bi-repeat" aria-hidden="true"></i>
                      Dự kiến: {{ b.noi_dung?.du_kien?.so_hiep }} hiệp ×
                      {{ b.noi_dung?.du_kien?.so_lan_lap }} lần
                    </span>
                    <span v-if="b.noi_dung?.du_kien?.muc_ta_kg != null" class="nk-chip">
                      <i class="bi bi-disc" aria-hidden="true"></i>
                      {{ b.noi_dung.du_kien.muc_ta_kg }} kg
                    </span>
                    <span class="nk-chip">
                      <i class="bi bi-stopwatch" aria-hidden="true"></i>
                      Nghỉ {{ b.noi_dung?.du_kien?.nghi_giay }}s
                    </span>
                  </div>
                </div>
              </header>

              <details
                v-if="b.noi_dung?.huong_dan || b.noi_dung?.cac_buoc?.length"
                class="nk-guidance"
              >
                <summary>
                  <i class="bi bi-book me-1" aria-hidden="true"></i> Hướng dẫn bài tập
                </summary>
                <p v-if="b.noi_dung.huong_dan">{{ b.noi_dung.huong_dan }}</p>
                <ol v-if="b.noi_dung.cac_buoc?.length">
                  <li v-for="(buoc, i) in b.noi_dung.cac_buoc || []" :key="i">{{ buoc }}</li>
                </ol>
              </details>

              <!-- KHI ĐANG TẬP (co_the_ghi === true) -->
              <template v-if="lich.co_the_ghi">
                <div class="nk-set-heading" aria-hidden="true">
                  <span>Hiệp</span>
                  <span>Số lần</span>
                  <span>Tạ (kg)</span>
                  <span>Nghỉ (giây)</span>
                  <span></span>
                </div>
                <div v-for="(h, i) in banNhap[viTri]?.hiep_tap || []" :key="i" class="nk-set">
                  <strong>{{ i + 1 }}</strong>
                  <input
                    v-model="h.so_lan_lap"
                    :aria-label="`${b.ten_bai_tap}, hiệp ${i + 1}, số lần`"
                    type="number"
                    min="1"
                    max="1000"
                    step="1"
                    inputmode="numeric"
                    required
                    class="form-control"
                    placeholder="Lần"
                    :disabled="dangLuu"
                  />
                  <input
                    v-model="h.khoi_luong_kg"
                    :aria-label="`${b.ten_bai_tap}, hiệp ${i + 1}, tạ kg; có thể để trống`"
                    type="number"
                    min="0"
                    max="1000"
                    step="0.01"
                    inputmode="decimal"
                    placeholder="Chưa ghi"
                    class="form-control"
                    :disabled="dangLuu"
                  />
                  <input
                    v-model="h.nghi_giay"
                    :aria-label="`${b.ten_bai_tap}, hiệp ${i + 1}, nghỉ giây`"
                    type="number"
                    min="0"
                    max="3600"
                    step="1"
                    required
                    class="form-control"
                    placeholder="Giây"
                    :disabled="dangLuu"
                  />
                  <button
                    type="button"
                    class="nk-icon-btn"
                    :aria-label="`Xóa hiệp ${i + 1} của ${b.ten_bai_tap}`"
                    title="Xóa hiệp"
                    :disabled="dangLuu"
                    @click="banNhap[viTri].hiep_tap.splice(i, 1)"
                  >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                  </button>
                </div>
                <p v-if="!banNhap[viTri]?.hiep_tap.length" class="nk-muted text-center py-2">
                  <i class="bi bi-dash-circle me-1" aria-hidden="true"></i> Chưa ghi hiệp thực tế.
                  Bấm thêm hiệp để nhập.
                </p>
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm nk-add-set-btn"
                  :disabled="dangLuu || banNhap[viTri]?.hiep_tap.length >= 20"
                  @click="themHiep(viTri)"
                >
                  <i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm hiệp
                </button>
              </template>

              <!-- KHI ĐÃ HOÀN THÀNH HOẶC CHỈ ĐỌC -->
              <template v-else>
                <table v-if="b.hiep_tap.length" class="nk-data-table">
                  <caption>
                    Kết quả thực tế —
                    {{
                      b.ten_bai_tap
                    }}
                  </caption>
                  <thead>
                    <tr>
                      <th>Hiệp</th>
                      <th>Số lần</th>
                      <th>Tạ (kg)</th>
                      <th>Nghỉ (giây)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="h in b.hiep_tap" :key="h.thu_tu">
                      <td>
                        <strong>Hiệp {{ h.thu_tu }}</strong>
                      </td>
                      <td>{{ h.so_lan_lap }} lần</td>
                      <td>
                        <span :class="{ 'text-primary fw-bold': h.khoi_luong_kg != null }">
                          {{ h.khoi_luong_kg != null ? `${h.khoi_luong_kg} kg` : 'Chưa ghi' }}
                        </span>
                      </td>
                      <td>{{ h.nghi_giay }} giây</td>
                    </tr>
                  </tbody>
                </table>
                <p v-else class="nk-muted text-center py-2">Chưa có kết quả thực tế.</p>
              </template>

              <p v-if="b.noi_dung?.du_kien?.ghi_chu" class="nk-footnote mt-3 mb-0">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i> Ghi chú giáo án:
                {{ b.noi_dung.du_kien.ghi_chu }}
              </p>
            </article>

            <!-- GHI CHÚ BUỔI TẬP VÀ THANH LƯU -->
            <section class="nk-panel">
              <h2>Ghi chú buổi tập</h2>
              <label v-if="lich.co_the_ghi">
                Cảm nhận, mức độ khó hoặc điều cần trao đổi
                <textarea
                  v-model="ghiChu"
                  class="form-control"
                  rows="2"
                  maxlength="2000"
                  placeholder="Ghi lại cảm nhận cơ bắp, mức tạ vượt trội hoặc lưu ý cho PT..."
                  :disabled="dangLuu"
                ></textarea>
              </label>
              <p v-else class="nk-note">{{ lich.phien?.ghi_chu || 'Chưa có ghi chú.' }}</p>

              <div v-if="lich.co_the_ghi" class="nk-save-bar">
                <span role="status">
                  <i
                    :class="
                      coThayDoi
                        ? 'bi bi-clock-history text-warning'
                        : 'bi bi-check2-circle text-success'
                    "
                    aria-hidden="true"
                  ></i>
                  {{ coThayDoi ? 'Có thay đổi chưa lưu' : 'Đã đồng bộ kết quả' }}
                </span>
                <button class="btn btn-outline-secondary" :disabled="dangLuu">
                  <i class="bi bi-save me-1" aria-hidden="true"></i>
                  {{ dangLuu ? 'Đang xử lý…' : 'Lưu nháp' }}
                </button>
                <button
                  type="button"
                  class="btn btn-primary"
                  :disabled="dangLuu"
                  @click="deNghiThaoTac('hoan-thanh')"
                >
                  <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i> Hoàn thành buổi
                </button>
              </div>
            </section>
          </form>

          <!-- NHẬN XÉT CỦA PT -->
          <aside class="nk-panel nk-comments">
            <p class="nk-eyebrow">
              <i class="bi bi-chat-heart" aria-hidden="true"></i> ĐỒNG HÀNH CÙNG BẠN
            </p>
            <h2>Nhận xét của PT</h2>

            <p v-if="!lich.nhan_xet.length" class="nk-muted">
              Chưa có nhận xét. PT phụ trách có thể nhận xét sau khi KH hoàn thành buổi tập.
            </p>

            <article v-for="n in lich.nhan_xet" :key="n.id" class="nk-comment">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <strong>
                  <i class="bi bi-person-badge text-primary me-1" aria-hidden="true"></i>
                  {{ n.ten_pt }}
                </strong>
                <small class="text-secondary mb-0">{{ thoiGian(n.created_at) }}</small>
              </div>
              <p>{{ n.noi_dung }}</p>
            </article>

            <form v-if="lich.co_the_nhan_xet" @submit.prevent="guiNhanXet">
              <label>
                Thêm nhận xét
                <textarea
                  v-model="nhanXet"
                  class="form-control"
                  rows="3"
                  maxlength="2000"
                  required
                  placeholder="Ghi nhận nỗ lực hoặc nhắc nhở học viên về kỹ thuật động tác..."
                  :disabled="dangLuu"
                ></textarea>
              </label>
              <button class="btn btn-primary w-100" :disabled="dangLuu || !nhanXet.trim()">
                <i class="bi bi-send-fill me-1" aria-hidden="true"></i>
                {{ dangLuu ? 'Đang gửi…' : 'Gửi nhận xét' }}
              </button>
            </form>

            <p class="nk-footnote">
              <i class="bi bi-shield-check" aria-hidden="true"></i>
              <span>Nhận xét được lưu riêng, giữ nguyên kết quả KH đã ghi.</span>
            </p>
          </aside>
        </div>
      </template>
    </section>
  </CaNhanLayout>
</template>
<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import nhatKyTapService from '../../../services/nhatKyTapService'
import baiTapService from '../../../services/baiTapService'
import { nhanTrangThaiTap, noiDungNhatKy, taoBanNhap } from '../../../utils/nhatKyTap'
import '../../../assets/nhatKyTap.css'
export default {
  name: 'ChiTietNhatKyTap',
  components: { CaNhanLayout },
  data() {
    return {
      lich: null,
      banNhap: [],
      ghiChu: '',
      nhanXet: '',
      maNhanXet: null,
      noiDungNhanXet: null,
      dangTai: false,
      dangLuu: false,
      loi: '',
      loiTruong: {},
      thanhCong: '',
      hanhDongCho: '',
      anhLoi: {},
      banDaLuu: '',
      nhanTrangThai: nhanTrangThaiTap,
      lanTai: 0,
      boHuy: null,
    }
  },
  computed: {
    laPt() {
      return this.$route.meta.vaiTro === 'HUAN_LUYEN_VIEN'
    },
    quayLai() {
      return this.laPt && this.lich
        ? `/pt/hoc-vien/${this.lich.khach_hang_id}/lich-tap`
        : this.laPt
          ? '/pt/hoc-vien'
          : '/khach-hang/lich-tap'
    },
    coThayDoi() {
      return (
        this.lich?.co_the_ghi &&
        JSON.stringify(noiDungNhatKy(this.banNhap, this.ghiChu)) !== this.banDaLuu
      )
    },
  },
  watch: {
    '$route.params.id'() {
      this.lich = null
      this.nhanXet = ''
      this.maNhanXet = null
      this.noiDungNhanXet = null
      this.anhLoi = {}
      this.thanhCong = ''
      this.taiChiTiet()
    },
  },
  mounted() {
    this.taiChiTiet()
    window.addEventListener('beforeunload', this.canhBaoRoiTrang)
  },
  beforeUnmount() {
    this.lanTai++
    this.boHuy?.abort()
    window.removeEventListener('beforeunload', this.canhBaoRoiTrang)
  },
  beforeRouteLeave() {
    return this.choRoiTrang()
  },
  beforeRouteUpdate() {
    return this.choRoiTrang()
  },
  methods: {
    ngay(v) {
      return v.split('-').reverse().join('/')
    },
    thoiGian(v) {
      return v
        ? new Date(v).toLocaleString('vi-VN', {
            timeZone: 'Asia/Ho_Chi_Minh',
            dateStyle: 'short',
            timeStyle: 'short',
          })
        : ''
    },
    media(b) {
      return baiTapService.urlMedia(b.noi_dung?.anh_url || b.noi_dung?.gif_url)
    },
    choRoiTrang() {
      if (this.dangLuu) return false
      return (
        !(this.coThayDoi || this.nhanXet.trim()) ||
        window.confirm('Bạn có nội dung chưa lưu. Rời trang và bỏ những thay đổi này?')
      )
    },
    canhBaoRoiTrang(e) {
      if (this.coThayDoi || this.nhanXet.trim() || this.dangLuu) {
        e.preventDefault()
        e.returnValue = ''
      }
    },
    nhanDuLieu(d) {
      this.lich = d
      this.banNhap = taoBanNhap(d.bai_tap)
      this.ghiChu = d.phien?.ghi_chu || ''
      this.banDaLuu = JSON.stringify(noiDungNhatKy(this.banNhap, this.ghiChu))
    },
    baoLoi(e) {
      this.loi = e.response?.data?.message || 'Chưa xử lý được. Bạn có thể thử lại.'
      this.loiTruong = e.response?.data?.errors || {}
    },
    async taiLai() {
      if (
        this.coThayDoi &&
        !window.confirm(
          'Tải lại sẽ thay thế kết quả chưa lưu bằng dữ liệu trên hệ thống. Tiếp tục?',
        )
      )
        return
      await this.taiChiTiet()
    },
    async taiChiTiet() {
      const lan = ++this.lanTai
      this.boHuy?.abort()
      this.boHuy = new AbortController()
      this.dangTai = true
      this.loi = ''
      this.loiTruong = {}
      try {
        const r = await nhatKyTapService.taiChiTiet(
          this.$route.params.id,
          this.laPt,
          this.boHuy.signal,
        )
        if (lan === this.lanTai) this.nhanDuLieu(r.data)
      } catch (e) {
        if (lan === this.lanTai && e.code !== 'ERR_CANCELED') {
          this.baoLoi(e)
          this.lich = null
        }
      } finally {
        if (lan === this.lanTai) this.dangTai = false
      }
    },
    themHiep(i) {
      if (this.dangLuu || !this.lich.co_the_ghi || this.banNhap[i].hiep_tap.length >= 20) return
      this.banNhap[i].hiep_tap.push({ so_lan_lap: '', khoi_luong_kg: '', nghi_giay: 0 })
    },
    async luuNhap() {
      if (this.dangLuu || !this.lich?.co_the_ghi) return false
      this.dangLuu = true
      this.loi = ''
      this.loiTruong = {}
      this.thanhCong = ''
      try {
        const r = await nhatKyTapService.luu(this.lich.id, {
          updated_at: this.lich.updated_at,
          ...noiDungNhatKy(this.banNhap, this.ghiChu),
        })
        this.nhanDuLieu(r.data)
        this.thanhCong = r.message
        return true
      } catch (e) {
        this.baoLoi(e)
        return false
      } finally {
        this.dangLuu = false
      }
    },
    async hoanThanh() {
      if (this.dangLuu || !this.lich?.co_the_ghi) return
      if (await this.luuNhap()) await this.thaoTac('hoan-thanh')
    },
    async deNghiThaoTac(hanhDong) {
      if (this.dangLuu) return
      this.hanhDongCho = hanhDong
      await this.$nextTick()
      this.$refs.xacNhan?.focus()
    },
    async huyBuoi() {
      if (this.dangLuu || !this.lich?.co_the_huy) return
      await this.thaoTac('huy')
    },
    async thaoTac(hanhDong) {
      if (this.dangLuu || !this.lich) return
      this.dangLuu = true
      this.loi = ''
      this.loiTruong = {}
      this.thanhCong = ''
      try {
        const r = await nhatKyTapService.thaoTac(this.lich.id, this.laPt, hanhDong, {
          updated_at: this.lich.updated_at,
        })
        this.nhanDuLieu(r.data)
        this.thanhCong = r.message
        this.hanhDongCho = ''
      } catch (e) {
        this.baoLoi(e)
      } finally {
        this.dangLuu = false
      }
    },
    async guiNhanXet() {
      if (this.dangLuu || !this.lich?.co_the_nhan_xet || !this.nhanXet.trim()) return
      const noiDung = this.nhanXet.trim()
      if (noiDung !== this.noiDungNhanXet) {
        this.noiDungNhanXet = noiDung
        this.maNhanXet = crypto.randomUUID()
      }
      this.dangLuu = true
      this.loi = ''
      this.loiTruong = {}
      this.thanhCong = ''
      try {
        const r = await nhatKyTapService.thaoTac(this.lich.id, true, 'nhan-xet', {
          noi_dung: noiDung,
          client_request_id: this.maNhanXet,
        })
        this.nhanDuLieu(r.data)
        this.nhanXet = ''
        this.maNhanXet = null
        this.noiDungNhanXet = null
        this.thanhCong = r.message
      } catch (e) {
        this.baoLoi(e)
      } finally {
        this.dangLuu = false
      }
    },
  },
}
</script>
