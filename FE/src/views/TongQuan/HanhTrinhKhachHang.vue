<template>
  <div class="journey">
    <header class="j-heading">
      <div>
        <span class="j-kicker">HÀNH TRÌNH CỦA BẠN</span>
        <h1>Chào {{ tenGoi }}!</h1>
        <p>{{ ngay(hanhTrinh.hom_nay) }} · Tiếp tục hành trình tập luyện theo nhịp của bạn.</p>
      </div>
      <div class="j-actions">
        <button
          class="j-button j-icon"
          :disabled="dangTai"
          aria-label="Cập nhật dashboard"
          @click="$emit('cap-nhat')"
        >
          <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
        </button>
        <RouterLink to="/khach-hang/chatbot" class="j-button"
          ><i class="bi bi-robot" aria-hidden="true"></i> Hỏi Tr0ond AI</RouterLink
        >
        <RouterLink :to="duongBuoi" class="j-button j-primary"
          >{{ buoi ? 'Tiếp tục buổi tập' : 'Lên lịch tập' }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i
        ></RouterLink>
      </div>
    </header>

    <dl class="j-stats" aria-label="Thống kê hành trình tập luyện">
      <div class="j-card" v-for="m in thongKe" :key="m.nhan">
        <dt>{{ m.nhan }} <i :class="m.icon" aria-hidden="true"></i></dt>
        <dd :class="m.mau">{{ m.giaTri }}</dd>
        <p>{{ m.moTa }}</p>
      </div>
    </dl>

    <div class="j-grid">
      <section class="j-card j-workout">
        <div class="j-section-heading">
          <div class="j-heading-inline">
            <span class="j-badge">HÔM NAY</span>
            <h2>Buổi tập hôm nay</h2>
          </div>
          <i class="bi bi-activity j-workout-icon" aria-hidden="true"></i>
        </div>
        <template v-if="buoi">
          <h3 class="j-big-title">{{ buoi.ten }}</h3>
          <p>
            Ngày {{ buoi.ngay_thu }} · {{ so(buoi.so_bai) }} bài tập ·
            {{ buoi.trang_thai === 'DANG_TAP' ? 'Đang tập' : 'Đã lên lịch' }}
          </p>
          <p class="j-workout-copy">
            Ghi lại các hiệp thực tế trong nhật ký. Lịch đã tạo vẫn được giữ khi bạn đổi giáo án.
          </p>
          <div class="j-actions">
            <RouterLink :to="duongBuoi" class="j-button j-primary"
              >{{ buoi.trang_thai === 'DANG_TAP' ? 'Tiếp tục ghi nhật ký' : 'Mở buổi tập' }}
              <i class="bi bi-play-fill" aria-hidden="true"></i></RouterLink
            ><RouterLink :to="`/khach-hang/ke-hoach/${buoi.ke_hoach_id}`" class="j-button"
              >Xem giáo án</RouterLink
            >
          </div>
          <div class="j-progress-copy">
            <span>Bài đã ghi kết quả</span
            ><strong>{{ buoi.so_bai_da_ghi }} / {{ buoi.so_bai }}</strong>
          </div>
          <progress
            :value="buoi.so_bai_da_ghi"
            :max="Math.max(1, buoi.so_bai)"
            aria-label="Bài có hiệp thực tế đã lưu"
          ></progress>
        </template>
        <template v-else>
          <h3 class="j-big-title">Hôm nay bạn chưa có buổi tự tập</h3>
          <p class="j-workout-copy">
            {{
              giaoAn
                ? 'Chọn một ngày trong giáo án đang dùng để lên lịch. Bạn cũng có thể xem lại những buổi đã hoàn thành.'
                : 'Tạo hoặc áp dụng một giáo án để bắt đầu lên lịch. Tự tập không cần mua gói.'
            }}
          </p>
          <div class="j-actions">
            <RouterLink
              :to="giaoAn ? '/khach-hang/lich-tap' : '/khach-hang/ke-hoach'"
              class="j-button j-primary"
              >{{ giaoAn ? 'Lên lịch tự tập' : 'Chọn giáo án' }}
              <i class="bi bi-arrow-right" aria-hidden="true"></i></RouterLink
            ><RouterLink to="/khach-hang/lich-tap" class="j-button">Xem nhật ký</RouterLink>
          </div>
        </template>
      </section>

      <aside class="j-stack" aria-label="Giáo án và lịch sắp tới">
        <section class="j-card">
          <h2>Giáo án đang áp dụng</h2>
          <template v-if="giaoAn">
            <h3>{{ giaoAn.ten }}</h3>
            <p class="j-accent">
              {{ giaoAn.nguon_tao === 'KHACH_HANG' ? 'KH tự tạo' : 'PT giao' }} ·
              {{ giaoAn.so_ngay_tap }} ngày · {{ giaoAn.so_bai }} bài
            </p>
            <div class="j-progress-copy">
              <span>Lịch tự tập tuần này</span
              ><strong>{{ giaoAn.hoan_thanh_tuan }} / {{ giaoAn.lich_tuan }} buổi</strong>
            </div>
            <progress
              :value="giaoAn.hoan_thanh_tuan"
              :max="Math.max(1, giaoAn.lich_tuan)"
              aria-label="Buổi đã hoàn thành trong lịch tuần này"
            ></progress>
            <RouterLink :to="`/khach-hang/ke-hoach/${giaoAn.id}`" class="j-button j-ink j-wide"
              >Xem giáo án <i class="bi bi-arrow-up-right" aria-hidden="true"></i
            ></RouterLink>
          </template>
          <template v-else
            ><p class="j-empty">Chưa có giáo án đang áp dụng.</p>
            <RouterLink to="/khach-hang/ke-hoach/them" class="j-button j-ink j-wide"
              >Tự tạo giáo án</RouterLink
            ></template
          >
        </section>
        <section class="j-card">
          <div class="j-section-heading">
            <h2>Lịch sắp tới</h2>
            <RouterLink to="/khach-hang/lich-tap" class="j-text-link">Xem lịch</RouterLink>
          </div>
          <ul v-if="hanhTrinh.lich_sap_toi.length" class="j-agenda">
            <li v-for="l in hanhTrinh.lich_sap_toi" :key="l.loai + l.id">
              <RouterLink
                :to="
                  l.loai === 'PT' ? `/khach-hang/lich-hen/${l.id}` : `/khach-hang/lich-tap/${l.id}`
                "
                ><div class="j-agenda-date">
                  <strong>{{ ngayNgan(l.ngay) }}</strong
                  ><span>{{ l.bat_dau_luc ? gio(l.bat_dau_luc) : 'Tự tập' }}</span>
                </div>
                <div>
                  <strong>{{ l.ten }}</strong
                  ><span class="j-status" :class="{ 'j-green': l.trang_thai === 'DA_XAC_NHAN' }">{{
                    trangThai(l.trang_thai)
                  }}</span>
                </div>
                <i class="bi bi-chevron-right" aria-hidden="true"></i
              ></RouterLink>
            </li>
          </ul>
          <p v-else class="j-empty">Chưa có lịch tự tập hoặc hẹn PT trong 7 ngày tới.</p>
          <RouterLink to="/khach-hang/lich-hen" class="j-text-link"
            >Xem lịch hẹn PT <i class="bi bi-arrow-up-right" aria-hidden="true"></i
          ></RouterLink>
        </section>
      </aside>

      <section class="j-card">
        <div class="j-section-heading">
          <div>
            <h2>Tiến độ tập luyện</h2>
            <p>Buổi tự tập đã hoàn thành theo ngày lịch.</p>
          </div>
          <div class="j-tabs" aria-label="Khoảng thống kê">
            <button
              v-for="n in [7, 30, 90]"
              :key="n"
              :aria-pressed="hanhTrinh.tien_do.so_ngay === n"
              :disabled="dangTai"
              @click="$emit('doi-khoang', n)"
            >
              {{ n }} ngày
            </button>
          </div>
        </div>
        <div class="j-summary">
          <div>
            <strong>{{ so(hanhTrinh.tien_do.so_buoi) }}</strong
            ><span>buổi trong {{ hanhTrinh.tien_do.so_ngay }} ngày</span>
          </div>
          <div>
            <strong>{{
              hanhTrinh.ti_le_hoan_thanh === null ? '—' : hanhTrinh.ti_le_hoan_thanh + '%'
            }}</strong
            ><span>lịch đã đến tháng này</span>
          </div>
          <RouterLink to="/khach-hang/lich-tap"
            ><i class="bi bi-journal-check" aria-hidden="true"></i
            ><span>Xem nhật ký tập <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span
          ></RouterLink>
        </div>
        <div
          class="j-chart"
          role="img"
          :aria-label="`Biểu đồ ${hanhTrinh.tien_do.so_buoi} buổi tự tập hoàn thành trong ${hanhTrinh.tien_do.so_ngay} ngày; chi tiết trong bảng bên dưới.`"
        >
          <svg viewBox="0 0 660 220" aria-hidden="true">
            <template v-for="n in [0, 1, 2]" :key="n">
              <line x1="42" x2="638" :y1="180 - n * 70" :y2="180 - n * 70" class="j-grid-line" />
              <text x="12" :y="185 - n * 70" class="j-chart-label">
                {{ so((maxBuoi * n) / 2) }}
              </text>
            </template>
            <rect
              v-for="(d, i) in hanhTrinh.tien_do.theo_ngay"
              :key="d.ngay"
              :x="45 + (i * 590) / hanhTrinh.tien_do.so_ngay"
              :y="180 - (d.so_buoi / maxBuoi) * 140"
              :width="Math.max(2, 590 / hanhTrinh.tien_do.so_ngay - 3)"
              :height="(d.so_buoi / maxBuoi) * 140"
              rx="2"
              class="j-chart-bar"
            >
              <title>{{ ngay(d.ngay) }}: {{ d.so_buoi }} buổi</title>
            </rect>
            <text x="42" y="212" class="j-chart-label">
              {{ ngayNgan(hanhTrinh.tien_do.tu_ngay) }}
            </text>
            <text x="638" y="212" text-anchor="end" class="j-chart-label">
              {{ ngayNgan(hanhTrinh.tien_do.den_ngay) }}
            </text>
          </svg>
        </div>
        <p v-if="!hanhTrinh.tien_do.so_buoi" class="j-empty">
          Chưa có buổi tự tập hoàn thành trong khoảng này.
        </p>
        <details class="j-chart-table">
          <summary>Xem số buổi theo ngày</summary>
          <div class="j-table-scroll">
            <table>
              <caption class="visually-hidden">
                Số buổi tự tập hoàn thành theo ngày lịch
              </caption>
              <thead>
                <tr>
                  <th scope="col">Ngày</th>
                  <th scope="col">Buổi hoàn thành</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in hanhTrinh.tien_do.theo_ngay" :key="d.ngay">
                  <td>{{ ngay(d.ngay) }}</td>
                  <td>{{ d.so_buoi }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </details>
      </section>

      <section class="j-card">
        <h2>Gói hiện tại</h2>
        <div v-if="hanhTrinh.goi" class="j-package">
          <i class="bi bi-box-seam" aria-hidden="true"></i>
          <h3>{{ hanhTrinh.goi.ten }}</h3>
          <p>Hết hạn {{ thoiDiem(hanhTrinh.goi.het_han_luc) }}</p>
        </div>
        <p v-else class="j-empty">
          Bạn chưa có gói còn hiệu lực. Giáo án tự tạo, nhật ký và chỉ số cơ thể vẫn dùng miễn phí.
        </p>
        <template v-if="hanhTrinh.goi"
          ><div class="j-progress-copy">
            <span>Buổi PT còn lại</span
            ><strong>{{ hanhTrinh.goi.so_buoi_con_lai }} / {{ hanhTrinh.goi.so_buoi_pt }}</strong>
          </div>
          <progress
            :value="hanhTrinh.goi.so_buoi_con_lai"
            :max="Math.max(1, hanhTrinh.goi.so_buoi_pt)"
            aria-label="Buổi PT còn lại"
          ></progress
        ></template>
        <div class="j-progress-copy">
          <span>Lượt AI hôm nay</span
          ><strong>{{ hanhTrinh.ai.con_lai }} / {{ hanhTrinh.ai.toi_da }}</strong>
        </div>
        <progress
          :value="hanhTrinh.ai.con_lai"
          :max="Math.max(1, hanhTrinh.ai.toi_da)"
          aria-label="Lượt AI còn lại hôm nay"
        ></progress>
        <p v-if="hanhTrinh.ai.dang_giu" class="j-caption">
          {{ hanhTrinh.ai.dang_giu }} lượt đang xử lý.
        </p>
        <RouterLink
          :to="hanhTrinh.goi ? '/khach-hang/goi-cua-toi' : '/goi-tap'"
          class="j-button j-primary j-wide"
          >{{ hanhTrinh.goi ? 'Xem gói của tôi' : 'Khám phá gói tập' }}</RouterLink
        ><RouterLink to="/khach-hang/don-hang" class="j-button j-wide"
          >Lịch sử thanh toán</RouterLink
        >
      </section>
    </div>

    <section class="j-card j-body">
      <div class="j-section-heading">
        <h2>Chỉ số cơ thể</h2>
        <RouterLink to="/khach-hang/chi-so-co-the" class="j-text-link"
          >Cập nhật chỉ số <i class="bi bi-arrow-up-right" aria-hidden="true"></i
        ></RouterLink>
      </div>
      <div v-if="chiSo.moi_nhat" class="j-body-grid">
        <dl class="j-body-stats">
          <div>
            <dt>BMI gần nhất</dt>
            <dd>{{ so(chiSo.moi_nhat.bmi) }}</dd>
            <p>Chỉ số tham khảo</p>
          </div>
          <div>
            <dt>Cân nặng</dt>
            <dd>{{ so(chiSo.moi_nhat.can_nang_kg) }} <small>kg</small></dd>
            <p>Ngày {{ ngay(chiSo.moi_nhat.ngay_ghi) }}</p>
          </div>
          <div>
            <dt>Chiều cao</dt>
            <dd>{{ so(chiSo.moi_nhat.chieu_cao_cm) }} <small>cm</small></dd>
          </div>
          <div>
            <dt>Thay đổi BMI · 30 ngày</dt>
            <dd>{{ chenhLech(chiSo.thay_doi_bmi) }}</dd>
            <p>
              {{
                chiSo.thay_doi_bmi === null
                  ? 'Cần ít nhất 2 lần đo hợp lệ'
                  : 'So với lần đo đầu trong khoảng'
              }}
            </p>
          </div>
        </dl>
        <div>
          <h3 class="j-chart-title">Xu hướng BMI · 30 ngày</h3>
          <div
            v-if="diemBmi.diem.length"
            class="j-chart"
            role="img"
            aria-label="Biểu đồ BMI; số đo chi tiết tại trang Chỉ số cơ thể."
          >
            <svg viewBox="0 0 660 240" aria-hidden="true">
              <template v-for="n in [0, 1, 2]" :key="n">
                <line
                  x1="105"
                  x2="610"
                  :y1="200 - n * 82.5"
                  :y2="200 - n * 82.5"
                  class="j-grid-line"
                />
                <text x="85" :y="205 - n * 82.5" text-anchor="end" class="j-chart-label">
                  {{ so(diemBmi.min + ((diemBmi.max - diemBmi.min) * n) / 2) }}
                </text>
              </template>
              <path :d="diemBmi.duong" class="j-bmi-line" />
              <circle
                v-for="d in diemBmi.diem"
                :key="d.ngay_ghi"
                :cx="d.x"
                :cy="d.y"
                r="4"
                class="j-bmi-dot"
              >
                <title>{{ ngay(d.ngay_ghi) }} · BMI {{ so(d.bmi) }}</title>
              </circle>
              <text x="105" y="232" class="j-chart-label">
                {{ ngayNgan(diemBmi.diem[0].ngay_ghi) }}
              </text>
              <text x="610" y="232" text-anchor="end" class="j-chart-label">
                {{ ngayNgan(diemBmi.diem.at(-1).ngay_ghi) }}
              </text>
            </svg>
          </div>
          <p v-else class="j-empty">Chưa có số đo BMI hợp lệ trong 30 ngày.</p>
          <p class="j-caption">
            BMI không phân biệt cơ và mỡ. Không nội suy số đo những ngày chưa ghi.
          </p>
        </div>
      </div>
      <p v-else class="j-empty">
        Ghi chiều cao và cân nặng lần đầu để theo dõi thay đổi qua các ngày tập luyện.
      </p>
    </section>

    <div class="j-support-grid">
      <section class="j-card">
        <h2>PT của tôi</h2>
        <template v-if="hanhTrinh.pt"
          ><div class="j-trainer">
            <span class="j-avatar" aria-hidden="true">{{
              hanhTrinh.pt.ho_ten.trim().charAt(0).toUpperCase()
            }}</span>
            <div>
              <h3>{{ hanhTrinh.pt.ho_ten }}</h3>
              <p>{{ hanhTrinh.pt.chuyen_mon || 'Huấn luyện viên cá nhân' }}</p>
              <span class="j-status j-green">Đang phụ trách</span>
            </div>
          </div>
          <RouterLink to="/khach-hang/tin-nhan" class="j-button j-ink"
            ><i class="bi bi-chat-left-text" aria-hidden="true"></i> Nhắn tin PT</RouterLink
          ><RouterLink to="/khach-hang/lich-hen" class="j-button"
            >Xem lịch hẹn PT</RouterLink
          ></template
        ><template v-else
          ><p class="j-empty">
            Bạn chưa có PT đang phụ trách. Vẫn có thể tự tạo giáo án và ghi nhật ký.
          </p>
          <RouterLink to="/khach-hang/ke-hoach/them" class="j-button"
            >Tự tạo giáo án</RouterLink
          ></template
        >
      </section>
      <section class="j-card">
        <div class="j-section-heading">
          <h2>Tr0ond AI</h2>
          <span class="j-badge">{{ hanhTrinh.ai.con_lai }} lượt còn lại</span>
        </div>
        <p>Tham khảo bài tập, giáo án và mục tiêu luyện tập cùng Tr0ond AI.</p>
        <p v-if="!hanhTrinh.ai.co_quyen" class="j-caption">
          Cần gói có quyền chatbot còn hiệu lực để hỏi AI.
        </p>
        <p v-else-if="!hanhTrinh.ai.san_sang" class="j-caption">Dịch vụ AI hiện chưa sẵn sàng.</p>
        <p v-else-if="!hanhTrinh.ai.con_lai" class="j-caption">
          Lượt đang được sử dụng hoặc xử lý. Hạn mức được cấp lại lúc 00:00 giờ Việt Nam.
        </p>
        <RouterLink to="/khach-hang/chatbot" class="j-button j-primary"
          ><i class="bi bi-robot" aria-hidden="true"></i> Mở Tr0ond AI</RouterLink
        >
      </section>
    </div>
    <nav class="j-quick" aria-label="Thao tác nhanh của khách hàng">
      <RouterLink to="/khach-hang/lich-tap"
        ><i class="bi bi-calendar2-check" aria-hidden="true"></i> Lịch & nhật ký</RouterLink
      ><RouterLink to="/khach-hang/tin-nhan"
        ><i class="bi bi-chat-left-text" aria-hidden="true"></i> Nhắn PT</RouterLink
      ><RouterLink to="/khach-hang/chatbot"
        ><i class="bi bi-robot" aria-hidden="true"></i> Hỏi AI</RouterLink
      ><RouterLink to="/khach-hang/chi-so-co-the"
        ><i class="bi bi-activity" aria-hidden="true"></i> Cập nhật chỉ số</RouterLink
      >
    </nav>
  </div>
</template>

<script>
import { diemChiSo } from '../../utils/chiSoCoThe'
export default {
  name: 'HanhTrinhKhachHang',
  props: {
    hanhTrinh: { type: Object, required: true },
    tenGoi: { type: String, default: 'bạn' },
    dangTai: Boolean,
  },
  emits: ['cap-nhat', 'doi-khoang'],
  computed: {
    buoi() {
      return this.hanhTrinh.buoi_hom_nay
    },
    giaoAn() {
      return this.hanhTrinh.giao_an
    },
    chiSo() {
      return this.hanhTrinh.chi_so
    },
    duongBuoi() {
      return this.buoi ? `/khach-hang/lich-tap/${this.buoi.id}` : '/khach-hang/lich-tap'
    },
    diemBmi() {
      const goc = diemChiSo(this.chiSo.cac_moc, 'bmi')
      // Chừa chỗ cho nhãn BMI khi biểu đồ thu nhỏ trên điện thoại.
      const diem = goc.diem.map((d) => ({ ...d, x: 105 + ((d.x - 60) * 505) / 550 }))
      return { ...goc, diem, duong: diem.map((d, i) => `${i ? 'L' : 'M'} ${d.x} ${d.y}`).join(' ') }
    },
    maxBuoi() {
      return Math.max(2, ...this.hanhTrinh.tien_do.theo_ngay.map((x) => x.so_buoi))
    },
    thongKe() {
      const h = this.hanhTrinh
      return [
        {
          nhan: 'Buổi tự tập tháng này',
          giaTri: this.so(h.buoi_thang_nay),
          moTa: 'Đã hoàn thành trong nhật ký',
          icon: 'bi bi-calendar2-check',
        },
        {
          nhan: 'Tỷ lệ hoàn thành',
          giaTri: h.ti_le_hoan_thanh === null ? '—' : h.ti_le_hoan_thanh + '%',
          moTa: 'Lịch tự tập đã đến tháng này',
          icon: 'bi bi-check2-circle',
          mau: 'j-green',
        },
        {
          nhan: 'Lịch sắp tới',
          giaTri: this.so(h.so_lich_sap_toi),
          moTa: 'Tự tập và hẹn PT · 7 ngày tới',
          icon: 'bi bi-clock',
        },
        {
          nhan: 'Lượt AI hôm nay',
          giaTri: `${h.ai.con_lai}/${h.ai.toi_da}`,
          moTa: 'Còn lại theo quyền lợi gói',
          icon: 'bi bi-robot',
          mau: 'j-accent',
        },
      ]
    },
  },
  methods: {
    so(n) {
      return n === null || n === undefined
        ? '—'
        : Number(n).toLocaleString('vi-VN', { maximumFractionDigits: 2 })
    },
    chenhLech(n) {
      return n === null ? '—' : (n > 0 ? '+' : '') + this.so(n)
    },
    ngay(n) {
      return n ? n.split('-').reverse().join('/') : '—'
    },
    ngayNgan(n) {
      return n ? this.ngay(n).slice(0, 5) : '—'
    },
    gio(n) {
      return new Date(n).toLocaleTimeString('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        hour: '2-digit',
        minute: '2-digit',
      })
    },
    thoiDiem(n) {
      return new Date(n).toLocaleString('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    },
    trangThai(n) {
      return (
        {
          DA_LEN_LICH: 'Tự tập · Đã lên lịch',
          DANG_TAP: 'Tự tập · Đang tập',
          DA_XAC_NHAN: 'PT · Đã xác nhận',
          CHO_XAC_NHAN: 'PT · Chờ xác nhận',
        }[n] || n
      )
    },
  },
}
</script>

<style scoped>
.journey {
  --j-accent: var(--mau-chinh);
  --j-action: #c2410c;
  --j-success: var(--mau-thanh-cong);
  color: var(--mau-chu);
  display: grid;
  gap: 24px;
}
.j-heading,
.j-section-heading,
.j-actions,
.j-heading-inline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}
.j-heading h1 {
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -1px;
  margin: 6px 0 8px;
}
.journey p {
  color: var(--mau-phu);
  font-size: 14px;
  line-height: 1.7;
  margin: 8px 0 0;
}
.j-kicker {
  font-size: 11px;
  letter-spacing: 1.6px;
  font-weight: 700;
  color: var(--j-accent);
}
.j-actions {
  justify-content: flex-start;
  flex-wrap: wrap;
  gap: 10px;
}
.j-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 44px;
  padding: 11px 18px;
  border: 1px solid var(--mau-vien);
  border-radius: 12px;
  background: var(--mau-the);
  color: var(--mau-chu);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition:
    transform 0.18s,
    border-color 0.18s,
    box-shadow 0.18s;
}
.j-button:hover {
  color: var(--j-accent);
  border-color: var(--j-accent);
}
.j-button:active {
  transform: translateY(1px);
}
.j-button:disabled {
  opacity: 0.5;
  cursor: wait;
}
.j-button:focus-visible,
.j-tabs button:focus-visible,
.j-text-link:focus-visible,
.j-agenda a:focus-visible,
.j-quick a:focus-visible {
  outline: 3px solid var(--j-accent);
  outline-offset: 3px;
}
.j-primary {
  color: #fff;
  background: var(--j-action);
  border-color: var(--j-action);
  box-shadow: 0 5px 14px color-mix(in srgb, var(--j-accent) 15%, transparent);
}
.j-primary:hover {
  color: #fff;
  filter: brightness(0.95);
}
.j-icon {
  padding: 10px;
  width: 44px;
}
.j-ink {
  background: var(--mau-chu);
  color: var(--mau-the);
  border-color: var(--mau-chu);
}
.j-ink:hover {
  color: var(--mau-the);
  filter: brightness(0.9);
}
.j-wide {
  display: flex;
  width: 100%;
  margin-top: 12px;
}
.j-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 20px;
  margin: 0;
}
.j-card {
  min-width: 0;
  padding: 26px;
  background: var(--mau-the);
  border: 1px solid var(--mau-vien);
  border-radius: 22px;
  box-shadow: 0 3px 14px color-mix(in srgb, var(--mau-chu) 3%, transparent);
}
.j-stats dt {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  font-weight: 600;
  color: var(--mau-phu);
  text-transform: uppercase;
  letter-spacing: 0.7px;
}
.j-stats dt i {
  font-size: 19px;
  color: var(--j-accent);
}
.j-stats dd {
  font-size: 32px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  letter-spacing: -1px;
  line-height: 1.2;
  margin: 18px 0 0;
}
.j-stats p {
  font-size: 12px;
}
.j-accent {
  color: var(--j-accent) !important;
}
.j-green {
  color: var(--j-success) !important;
}
.j-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.8fr) minmax(300px, 1fr);
  gap: 24px;
  align-items: start;
}
.j-stack {
  display: grid;
  gap: 24px;
}
.journey h2 {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.35px;
  margin: 0;
  line-height: 1.45;
}
.journey h3 {
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -0.5px;
  margin: 22px 0 4px;
  line-height: 1.4;
  overflow-wrap: anywhere;
}
.j-heading-inline {
  justify-content: flex-start;
  flex-wrap: wrap;
  gap: 12px;
}
.j-badge {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 5px 10px;
  border-radius: 30px;
  background: var(--mau-chinh-nhat);
  color: var(--j-accent);
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}
.j-workout {
  padding: 32px;
  min-height: 380px;
  border-top: 3px solid var(--j-accent);
}
.j-workout-icon {
  color: var(--j-accent);
  font-size: 28px;
}
.journey .j-big-title {
  font-size: clamp(25px, 2.5vw, 38px);
  letter-spacing: -1px;
  margin-top: 38px;
  max-width: 760px;
}
.journey .j-workout-copy {
  margin: 16px 0 26px;
  max-width: 620px;
}
.j-progress-copy {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 12px;
  color: var(--mau-phu);
  margin-top: 24px;
  margin-bottom: 8px;
}
.j-progress-copy strong {
  color: var(--mau-chu);
  font-variant-numeric: tabular-nums;
}
progress {
  width: 100%;
  height: 7px;
  appearance: none;
  border: 0;
  border-radius: 10px;
  display: block;
  overflow: hidden;
  background: var(--mau-nen);
}
progress::-webkit-progress-bar {
  background: var(--mau-nen);
  border-radius: 10px;
}
progress::-webkit-progress-value {
  background: var(--j-accent);
  border-radius: 10px;
}
progress::-moz-progress-bar {
  background: var(--j-accent);
  border-radius: 10px;
}
.j-text-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 44px;
  color: var(--j-accent);
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
}
.j-text-link:hover {
  text-decoration: underline;
}
.j-empty {
  padding: 18px 0;
}
.j-agenda {
  list-style: none;
  padding: 0;
  margin: 8px 0;
}
.j-agenda a {
  display: grid;
  grid-template-columns: 54px minmax(0, 1fr) 12px;
  align-items: center;
  gap: 12px;
  padding: 14px 0;
  color: var(--mau-chu);
  text-decoration: none;
  border-bottom: 1px solid var(--mau-vien);
}
.j-agenda a:hover {
  color: var(--j-accent);
}
.j-agenda strong {
  display: block;
  font-size: 13px;
  overflow-wrap: anywhere;
}
.j-agenda-date {
  display: grid;
  gap: 4px;
  font-variant-numeric: tabular-nums;
}
.j-agenda-date span {
  color: var(--mau-phu);
  font-size: 12px;
}
.j-status {
  display: inline-block;
  font-size: 11px;
  background: var(--mau-nen);
  padding: 3px 7px;
  border-radius: 6px;
  margin-top: 6px;
  color: var(--mau-phu);
}
.j-tabs {
  display: flex;
  gap: 3px;
  padding: 4px;
  background: var(--mau-nen);
  border-radius: 12px;
  flex-shrink: 0;
}
.j-tabs button {
  min-height: 40px;
  border: 0;
  background: transparent;
  padding: 7px 10px;
  border-radius: 9px;
  color: var(--mau-phu);
  font-size: 12px;
  font-weight: 600;
}
.j-tabs button[aria-pressed='true'] {
  background: var(--mau-the);
  color: var(--j-accent);
  box-shadow: 0 1px 4px #0001;
}
.j-tabs button:disabled {
  opacity: 0.6;
}
.j-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin: 24px 0 16px;
}
.j-summary > div,
.j-summary > a {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  min-height: 95px;
  background: var(--mau-nen);
  padding: 14px;
  border-radius: 14px;
  text-decoration: none;
  color: var(--mau-chu);
}
.j-summary > div:first-child {
  background: var(--mau-chinh-nhat);
}
.j-summary strong,
.j-summary > a > i {
  font-size: 24px;
  font-weight: 700;
  color: var(--j-accent);
}
.j-summary span {
  font-size: 12px;
  color: var(--mau-phu);
  line-height: 1.5;
}
.j-chart {
  width: 100%;
}
.j-chart svg {
  display: block;
  width: 100%;
  height: auto;
}
.j-grid-line {
  stroke: var(--mau-vien);
  stroke-dasharray: 3 7;
}
.j-chart-label {
  fill: var(--mau-phu);
  font-size: 13px;
}
.j-chart-bar {
  fill: var(--j-accent);
}
.j-chart-table summary {
  cursor: pointer;
  font-size: 12px;
  min-height: 44px;
  padding: 12px 0;
  color: var(--mau-phu);
}
.j-table-scroll {
  max-height: 240px;
  overflow: auto;
}
.j-chart-table table {
  width: 100%;
  font-size: 13px;
}
.j-chart-table td,
.j-chart-table th {
  padding: 8px;
  border-bottom: 1px solid var(--mau-vien);
}
.j-package {
  background: var(--mau-chu);
  color: var(--mau-the);
  border-radius: 18px;
  padding: 22px;
  margin: 22px 0;
}
.j-package i {
  font-size: 25px;
  color: var(--j-accent);
}
.j-package h3 {
  color: inherit;
  margin-top: 12px;
}
.journey .j-package p {
  color: inherit;
  opacity: 0.8;
}
.journey .j-caption {
  font-size: 12px;
}
.j-body-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr);
  gap: 30px;
  margin-top: 24px;
}
.j-body-stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  margin: 0;
}
.j-body-stats > div {
  background: var(--mau-nen);
  padding: 20px;
  border-radius: 16px;
}
.j-body-stats dt {
  font-size: 11px;
  color: var(--mau-phu);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.j-body-stats dd {
  font-size: 30px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  margin: 12px 0 0;
  line-height: 1.3;
}
.j-body-stats small {
  font-size: 15px;
  font-weight: 400;
}
.j-body-stats p {
  font-size: 12px;
}
.j-bmi-line {
  fill: none;
  stroke: var(--j-accent);
  stroke-width: 3;
}
.j-bmi-dot {
  fill: var(--mau-the);
  stroke: var(--j-accent);
  stroke-width: 2;
}
.journey .j-chart-title {
  font-size: 13px;
  margin: 0 0 14px;
  color: var(--mau-phu);
}
.j-support-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 24px;
}
.j-trainer {
  display: flex;
  align-items: center;
  gap: 18px;
  margin: 24px 0;
}
.j-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 20px;
  background: var(--mau-chinh-nhat);
  color: var(--j-accent);
  font-size: 28px;
  font-weight: 700;
  width: 72px;
  height: 72px;
  flex-shrink: 0;
}
.j-trainer h3 {
  margin: 0;
}
.j-support-grid .j-button {
  margin-top: 18px;
  margin-right: 8px;
}
.j-quick {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
}
.j-quick a {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 105px;
  padding: 20px;
  background: var(--mau-the);
  color: var(--mau-chu);
  border: 1px solid var(--mau-vien);
  border-radius: 16px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition:
    border-color 0.18s,
    transform 0.18s;
}
.j-quick i {
  font-size: 24px;
  color: var(--j-accent);
}
.j-quick a:hover {
  border-color: var(--j-accent);
  transform: translateY(-2px);
}
@media (max-width: 1250px) {
  .j-heading {
    align-items: flex-start;
    flex-direction: column;
  }
  .j-stats {
    gap: 14px;
  }
  .j-card {
    padding: 22px;
  }
  .j-grid {
    grid-template-columns: minmax(0, 1.6fr) minmax(270px, 1fr);
  }
  .j-section-heading {
    flex-wrap: wrap;
  }
  .j-body-grid {
    grid-template-columns: 1fr 1.2fr;
    gap: 20px;
  }
}
@media (max-width: 900px) {
  .j-stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .j-grid {
    grid-template-columns: 1fr;
  }
  .j-stack {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .j-body-grid {
    grid-template-columns: 1fr;
  }
  .j-body-stats {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
  .j-body-stats > div {
    padding: 16px;
  }
  .j-body-stats dd {
    font-size: 25px;
  }
}
@media (max-width: 600px) {
  .journey {
    gap: 18px;
  }
  .j-heading h1 {
    font-size: 28px;
  }
  .j-heading > div,
  .j-actions {
    width: 100%;
  }
  .j-heading .j-primary {
    flex-grow: 1;
  }
  .j-card {
    padding: 20px;
    border-radius: 18px;
  }
  .j-stats {
    gap: 10px;
  }
  .j-stats .j-card {
    padding: 16px;
  }
  .j-stats dt {
    font-size: 12px;
    letter-spacing: 0.25px;
  }
  .j-stats dt i {
    display: none;
  }
  .j-stats dd {
    font-size: 27px;
  }
  .j-stats p {
    font-size: 12px;
  }
  .j-stack,
  .j-support-grid {
    grid-template-columns: 1fr;
    gap: 18px;
  }
  .journey .j-big-title {
    margin-top: 24px;
  }
  .j-workout {
    min-height: 0;
  }
  .j-body-stats,
  .j-quick {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .j-summary {
    gap: 8px;
  }
  .j-summary > div,
  .j-summary > a {
    padding: 10px;
  }
  .j-summary strong {
    font-size: 22px;
  }
  .j-summary span {
    font-size: 12px;
  }
  .j-chart-label {
    font-size: 28px;
  }
  .j-tabs {
    width: 100%;
    justify-content: space-between;
  }
  .j-tabs button {
    flex: 1;
    min-height: 44px;
  }
  .j-progress-copy {
    flex-wrap: wrap;
  }
  .j-quick {
    gap: 12px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .j-button,
  .j-quick a {
    transition: none;
  }
  .j-quick a:hover {
    transform: none;
  }
}
:global(:root[data-theme='light'] .journey) {
  --j-accent: #c2410c;
  --j-success: #047857;
}
</style>
