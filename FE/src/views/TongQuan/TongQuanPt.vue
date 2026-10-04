<template>
  <div class="pt-journey">
    <header class="pt-welcome">
      <div>
        <span class="pt-kicker">KHÔNG GIAN HUẤN LUYỆN VIÊN</span>
        <h1>Chào {{ tenGoi }}!</h1>
        <p>
          Bạn có <strong>{{ huanLuyen.so_buoi_hom_nay }}</strong> buổi huấn luyện hôm nay.
        </p>
      </div>
      <div class="pt-actions">
        <button
          class="pt-button pt-icon"
          :disabled="dangTai"
          aria-label="Cập nhật tổng quan PT"
          @click="$emit('cap-nhat')"
        >
          <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
        </button>
        <RouterLink :to="lichHomNay" class="pt-button pt-primary"
          ><i class="bi bi-calendar2-week" aria-hidden="true"></i>Xem lịch hôm nay</RouterLink
        >
        <RouterLink to="/pt/khung-gio" class="pt-button">Quản lý khung giờ</RouterLink>
      </div>
    </header>

    <dl class="pt-metrics">
      <div v-for="muc in cacThongKe" :key="muc.nhan" class="pt-card pt-metric">
        <dt>{{ muc.nhan }}<i :class="muc.icon" aria-hidden="true"></i></dt>
        <dd>{{ muc.so }}</dd>
        <p>{{ muc.moTa }}</p>
      </div>
    </dl>

    <div class="pt-grid">
      <section class="pt-card pt-wide">
        <div class="pt-section-heading">
          <h2>Lịch huấn luyện hôm nay</h2>
          <RouterLink :to="lichHomNay" class="pt-link"
            >Xem lịch đầy đủ <i class="bi bi-arrow-up-right" aria-hidden="true"></i
          ></RouterLink>
        </div>
        <div v-if="!huanLuyen.lich_hom_nay.length" class="pt-empty">
          <i class="bi bi-calendar2-check" aria-hidden="true"></i
          ><strong>Hôm nay chưa có lịch hẹn</strong>
          <p>Bạn có thể mở thêm giờ rảnh để học viên đặt lịch.</p>
          <RouterLink to="/pt/khung-gio" class="pt-link">Mở khung giờ</RouterLink>
        </div>
        <div v-else class="pt-agenda">
          <RouterLink
            v-for="lich in huanLuyen.lich_hom_nay"
            :key="lich.id"
            :to="`/pt/lich-hen/${lich.id}`"
            class="pt-agenda-row"
          >
            <div class="pt-time">
              <b>{{ gio(lich.bat_dau_luc) }}</b
              ><small>{{ gio(lich.ket_thuc_luc) }}</small>
            </div>
            <div class="pt-copy">
              <strong>{{ lich.ho_ten }}</strong
              ><small>Buổi huấn luyện cá nhân · 60 phút</small>
            </div>
            <span class="pt-state" :class="mauTrangThai(lich.trang_thai)">{{
              tenTrangThai(lich.trang_thai)
            }}</span>
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </RouterLink>
          <p v-if="huanLuyen.tong_lich_hom_nay > 6" class="pt-footnote">
            Đang hiển thị 6/{{ huanLuyen.tong_lich_hom_nay }} lịch hẹn.
          </p>
        </div>
      </section>

      <section class="pt-card pt-tasks">
        <div class="pt-section-heading">
          <h2>Cần bạn xử lý</h2>
          <span class="pt-state pt-orange">{{ soViec }} việc</span>
        </div>
        <RouterLink v-for="viec in cacViec" :key="viec.nhan" :to="viec.to" class="pt-task-row"
          ><span class="pt-small-icon"><i :class="viec.icon" aria-hidden="true"></i></span
          ><span
            ><b>{{ viec.so }}</b> {{ viec.nhan }}</span
          ><i class="bi bi-chevron-right" aria-hidden="true"></i
        ></RouterLink>
        <p class="pt-footnote">Mở từng mục để xem chi tiết và xử lý.</p>
      </section>

      <section class="pt-card pt-wide">
        <div class="pt-section-heading">
          <h2>Học viên của tôi</h2>
          <span class="pt-state">{{ huanLuyen.so_hoc_vien }} học viên</span>
        </div>
        <p v-if="!huanLuyen.hoc_vien.length" class="pt-empty-copy">
          Chưa có học viên đang được phân công cho bạn.
        </p>
        <div v-for="khach in huanLuyen.hoc_vien" :key="khach.id" class="pt-person-row">
          <span class="pt-avatar" aria-hidden="true">{{ chuCai(khach.ho_ten) }}</span>
          <div class="pt-copy">
            <strong>{{ khach.ho_ten }}</strong
            ><small>{{ khach.muc_tieu || 'Chưa bổ sung mục tiêu' }}</small
            ><small class="pt-plan-name">{{ khach.giao_an || 'Chưa áp dụng giáo án' }}</small>
          </div>
          <div class="pt-student-progress">
            <span>{{ khach.so_buoi_tu_tap }}/{{ khach.so_lich_tuan }} buổi tự tập</span>
            <div class="pt-track">
              <span :style="{ width: tiLe(khach.so_buoi_tu_tap, khach.so_lich_tuan) + '%' }"></span>
            </div>
            <small>Tuần này</small>
          </div>
          <RouterLink :to="`/pt/hoc-vien/${khach.id}/ke-hoach`" class="pt-button"
            >Xem<span class="visually-hidden"> giáo án của {{ khach.ho_ten }}</span></RouterLink
          >
        </div>
        <RouterLink to="/pt/hoc-vien" class="pt-bottom-link"
          >Xem tất cả học viên <i class="bi bi-arrow-right" aria-hidden="true"></i
        ></RouterLink>
      </section>

      <section class="pt-card">
        <div class="pt-section-heading">
          <h2>Học viên cần chú ý</h2>
          <span class="pt-state">{{ huanLuyen.tong_can_chu_y }}</span>
        </div>
        <p v-if="!huanLuyen.can_chu_y.length" class="pt-empty-copy">
          Chưa có học viên cần chú ý theo lịch và giáo án hiện tại.
        </p>
        <RouterLink
          v-for="khach in huanLuyen.can_chu_y"
          :key="khach.id"
          :to="`/pt/hoc-vien/${khach.id}/${khach.loai === 'GIAO_AN' ? 'ke-hoach' : 'lich-tap'}`"
          class="pt-attention-row"
          ><i class="bi bi-exclamation-circle" aria-hidden="true"></i>
          <div class="pt-copy">
            <strong>{{ khach.ho_ten }}</strong
            ><small>{{ khach.ly_do }}</small>
          </div>
          <i class="bi bi-chevron-right" aria-hidden="true"></i
        ></RouterLink>
        <RouterLink v-if="huanLuyen.tong_can_chu_y > 4" to="/pt/hoc-vien" class="pt-bottom-link"
          >Xem thêm học viên</RouterLink
        >
      </section>

      <section class="pt-card pt-wide">
        <div class="pt-section-heading">
          <h2>Giáo án & kế hoạch</h2>
          <RouterLink to="/pt/hoc-vien" class="pt-link"
            >Quản lý giáo án <i class="bi bi-arrow-up-right" aria-hidden="true"></i
          ></RouterLink>
        </div>
        <dl class="pt-plan-stats">
          <div>
            <dd>{{ huanLuyen.giao_an.nhap }}</dd>
            <dt>Bản nháp</dt>
          </div>
          <div>
            <dd>{{ huanLuyen.giao_an.cho_xac_nhan }}</dd>
            <dt>Chờ KH xác nhận</dt>
          </div>
          <div>
            <dd>{{ huanLuyen.giao_an.dang_ap_dung }}</dd>
            <dt>Đang áp dụng</dt>
          </div>
        </dl>
        <p v-if="!huanLuyen.giao_an.gan_day.length" class="pt-empty-copy">
          Chưa có giáo án. Chọn học viên để bắt đầu soạn giáo án cá nhân.
        </p>
        <div v-for="ban in huanLuyen.giao_an.gan_day" :key="ban.id" class="pt-plan-row">
          <div class="pt-copy">
            <strong>{{ ban.ho_ten }}</strong
            ><small>{{ ban.ten }}</small
            ><span class="pt-source">{{
              ban.nguon_tao === 'KHACH_HANG' ? 'KH tự tạo' : 'PT giao'
            }}</span>
          </div>
          <span class="pt-state" :class="mauTrangThai(ban.trang_thai)">{{
            tenTrangThai(ban.trang_thai)
          }}</span
          ><RouterLink
            :to="`/pt/ke-hoach/${ban.id}${ban.co_the_sua ? '/sua' : ''}`"
            class="pt-button"
            >{{ ban.co_the_sua ? 'Tiếp tục' : 'Xem'
            }}<span class="visually-hidden"> {{ ban.ten }}</span></RouterLink
          >
        </div>
      </section>

      <section class="pt-card">
        <div class="pt-section-heading">
          <h2>Tin nhắn gần đây</h2>
          <i class="bi bi-chat-left-text" aria-hidden="true"></i>
        </div>
        <p v-if="!huanLuyen.tin_nhan.gan_day.length" class="pt-empty-copy">
          Chưa có hội thoại với học viên.
        </p>
        <RouterLink
          v-for="hoi in huanLuyen.tin_nhan.gan_day"
          :key="hoi.id"
          :to="`/pt/tin-nhan/${hoi.id}`"
          class="pt-chat-row"
          ><span class="pt-avatar" aria-hidden="true">{{ chuCai(hoi.ho_ten) }}</span>
          <div class="pt-copy">
            <strong>{{ hoi.ho_ten }}</strong
            ><small class="pt-preview">{{ hoi.noi_dung || 'Bắt đầu trò chuyện' }}</small
            ><small>{{ thoiGian(hoi.luc) }}</small>
          </div>
          <span v-if="hoi.so_chua_doc" class="pt-unread"
            >{{ hoi.so_chua_doc }}<span class="visually-hidden"> tin chưa đọc</span></span
          ></RouterLink
        >
        <RouterLink to="/pt/tin-nhan" class="pt-bottom-link"
          >Xem tất cả tin nhắn <i class="bi bi-arrow-right" aria-hidden="true"></i
        ></RouterLink>
      </section>

      <section class="pt-card pt-wide">
        <div class="pt-section-heading">
          <div>
            <h2>Hoạt động tuần này</h2>
            <p>{{ ngay(huanLuyen.tu_ngay) }} – {{ ngay(huanLuyen.den_ngay) }}</p>
          </div>
          <span class="pt-state">Buổi PT</span>
        </div>
        <dl class="pt-week-stats">
          <div v-for="muc in thongKeTuan" :key="muc.nhan">
            <dd>{{ muc.so }}</dd>
            <dt>{{ muc.nhan }}</dt>
          </div>
        </dl>
        <div class="pt-chart" aria-label="Buổi PT hoàn thành theo ngày">
          <div
            v-for="(diem, i) in huanLuyen.tuan.theo_ngay"
            :key="diem.ngay"
            class="pt-chart-column"
          >
            <span class="pt-chart-number">{{ diem.so_buoi }}</span>
            <div class="pt-chart-track">
              <div
                class="pt-chart-bar"
                :style="{ height: (diem.so_buoi / maxBuoi) * 100 + '%' }"
              ></div>
            </div>
            <span>{{ i === 6 ? 'CN' : 'T' + (i + 2) }}</span
            ><span class="visually-hidden"
              >{{ ngay(diem.ngay) }}, {{ diem.so_buoi }} buổi PT hoàn thành</span
            >
          </div>
        </div>
        <p class="pt-footnote">
          Tỷ lệ trên {{ huanLuyen.tuan.da_den }} buổi đã kết thúc, loại yêu cầu hết hạn, bị từ chối
          hoặc hủy. Buổi hoàn thành đã ghi nhận tiêu hao lượt PT.
        </p>
      </section>

      <section class="pt-card pt-slots">
        <div class="pt-section-heading">
          <h2>Khung giờ của bạn</h2>
          <i class="bi bi-clock" aria-hidden="true"></i>
        </div>
        <p class="pt-footnote">Trong 7 ngày tới · bắt đầu sau ít nhất 4 giờ</p>
        <dl class="pt-slot-stats">
          <div>
            <dt>Khung giờ mở</dt>
            <dd>{{ huanLuyen.khung_gio.tong }}</dd>
          </div>
          <div>
            <dt>Đã được đặt</dt>
            <dd>{{ huanLuyen.khung_gio.da_dat }} / {{ huanLuyen.khung_gio.tong }}</dd>
          </div>
        </dl>
        <div
          class="pt-track"
          role="progressbar"
          :aria-valuenow="tiLe(huanLuyen.khung_gio.da_dat, huanLuyen.khung_gio.tong)"
          aria-valuemin="0"
          aria-valuemax="100"
          aria-label="Tỷ lệ khung giờ đã được đặt"
        >
          <span
            :style="{ width: tiLe(huanLuyen.khung_gio.da_dat, huanLuyen.khung_gio.tong) + '%' }"
          ></span>
        </div>
        <div class="pt-slot-empty">
          <i class="bi bi-calendar2-plus" aria-hidden="true"></i
          ><strong>{{ huanLuyen.khung_gio.con_trong }}</strong
          ><span>khung giờ còn trống</span>
        </div>
        <RouterLink to="/pt/khung-gio" class="pt-button pt-primary"
          >Quản lý khung giờ <i class="bi bi-arrow-right" aria-hidden="true"></i
        ></RouterLink>
      </section>
    </div>

    <section class="pt-quick-section">
      <h2>Thao tác nhanh</h2>
      <div class="pt-quick-grid">
        <RouterLink
          v-for="muc in thaoTacNhanh"
          :key="muc.nhan"
          :to="muc.to"
          class="pt-card pt-quick"
          ><i :class="muc.icon" aria-hidden="true"></i><strong>{{ muc.nhan }}</strong
          ><i class="bi bi-arrow-up-right" aria-hidden="true"></i
        ></RouterLink>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: 'TongQuanPt',
  props: {
    huanLuyen: { type: Object, required: true },
    tenGoi: { type: String, default: 'bạn' },
    dangTai: Boolean,
  },
  emits: ['cap-nhat'],
  computed: {
    lichHomNay() {
      return { path: '/pt/lich-hen', query: { ngay: this.huanLuyen.hom_nay } }
    },
    soViec() {
      const v = this.huanLuyen.can_xu_ly
      return v.cho_dat_lich + v.cho_ket_qua + v.nhap_pt + v.hoi_thoai_chua_doc
    },
    cacThongKe() {
      return [
        {
          nhan: 'Buổi hôm nay',
          so: this.huanLuyen.so_buoi_hom_nay,
          moTa: 'Buổi huấn luyện',
          icon: 'bi bi-calendar2-week',
        },
        {
          nhan: 'Chờ xác nhận',
          so: this.huanLuyen.can_xu_ly.cho_dat_lich,
          moTa: 'Yêu cầu đặt lịch còn hạn',
          icon: 'bi bi-clock-history',
        },
        {
          nhan: 'Học viên',
          so: this.huanLuyen.so_hoc_vien,
          moTa: 'Đang phụ trách',
          icon: 'bi bi-people',
        },
        { nhan: 'Cần xử lý', so: this.soViec, moTa: 'Việc đang chờ', icon: 'bi bi-list-check' },
      ]
    },
    cacViec() {
      const v = this.huanLuyen.can_xu_ly
      return [
        {
          so: v.cho_dat_lich,
          nhan: 'yêu cầu đặt lịch chờ xác nhận',
          icon: 'bi bi-calendar2-check',
          to: v.lich_dat ? `/pt/lich-hen/${v.lich_dat.id}` : '/pt/lich-hen',
        },
        {
          so: v.cho_ket_qua,
          nhan: 'buổi đã kết thúc chờ ghi kết quả',
          icon: 'bi bi-check2-circle',
          to: v.lich_ket_qua ? `/pt/lich-hen/${v.lich_ket_qua.id}` : '/pt/lich-hen',
        },
        {
          so: v.nhap_pt,
          nhan: 'giáo án PT đang ở bản nháp',
          icon: 'bi bi-journal-text',
          to: v.giao_an_nhap_id ? `/pt/ke-hoach/${v.giao_an_nhap_id}/sua` : '/pt/hoc-vien',
        },
        {
          so: v.hoi_thoai_chua_doc,
          nhan: 'học viên có tin nhắn chưa đọc',
          icon: 'bi bi-chat-left-text',
          to: '/pt/tin-nhan',
        },
      ]
    },
    thongKeTuan() {
      const t = this.huanLuyen.tuan
      return [
        { nhan: 'Lịch đã tạo', so: t.da_len_lich },
        { nhan: 'Hoàn thành', so: t.hoan_thanh },
        { nhan: 'Hủy / từ chối', so: t.da_huy },
        { nhan: 'Vắng mặt', so: t.vang_mat },
        {
          nhan: 'Tỷ lệ hoàn thành',
          so: t.ti_le_hoan_thanh == null ? '—' : t.ti_le_hoan_thanh + '%',
        },
      ]
    },
    maxBuoi() {
      return Math.max(1, ...this.huanLuyen.tuan.theo_ngay.map((d) => d.so_buoi))
    },
    thaoTacNhanh() {
      return [
        { nhan: 'Học viên', icon: 'bi bi-people', to: '/pt/hoc-vien' },
        { nhan: 'Lịch hôm nay', icon: 'bi bi-calendar2-week', to: this.lichHomNay },
        { nhan: 'Khung giờ', icon: 'bi bi-clock', to: '/pt/khung-gio' },
        { nhan: 'Tạo giáo án', icon: 'bi bi-journal-plus', to: '/pt/hoc-vien' },
        { nhan: 'Tin nhắn', icon: 'bi bi-chat-left-text', to: '/pt/tin-nhan' },
        { nhan: 'Thư viện giáo án', icon: 'bi bi-journal-bookmark', to: '/pt/giao-an-mau' },
      ]
    },
  },
  methods: {
    chuCai(ten) {
      return ten?.trim().split(/\s+/).at(-1)?.slice(0, 1).toUpperCase() || '?'
    },
    tiLe(so, tong) {
      return tong > 0 ? Math.min(100, Math.max(0, Math.round((so * 100) / tong))) : 0
    },
    gio(luc) {
      return new Date(luc).toLocaleTimeString('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        hour: '2-digit',
        minute: '2-digit',
      })
    },
    ngay(ngay) {
      return ngay ? ngay.slice(8, 10) + '/' + ngay.slice(5, 7) : '—'
    },
    thoiGian(luc) {
      return luc
        ? new Date(luc).toLocaleString('vi-VN', {
            timeZone: 'Asia/Ho_Chi_Minh',
            hour: '2-digit',
            minute: '2-digit',
            day: '2-digit',
            month: '2-digit',
          })
        : 'Chưa có tin nhắn'
    },
    tenTrangThai(ma) {
      return (
        {
          NHAP: 'Bản nháp',
          CHO_DUYET: 'Chờ KH xác nhận',
          DANG_AP_DUNG: 'Đang áp dụng',
          LUU_TRU: 'Đã lưu trữ',
          DA_HUY: 'Đã hủy',
          QUA_HAN: 'Đề xuất hết hạn',
          CHO_XAC_NHAN: 'Chờ xác nhận',
          DA_XAC_NHAN: 'Đã xác nhận',
          HOAN_THANH: 'Hoàn thành',
          VANG_MAT: 'Vắng mặt',
          QUA_HAN_XAC_NHAN: 'Quá hạn ghi kết quả',
          HET_HAN: 'Yêu cầu hết hạn',
          TU_CHOI: 'Đã từ chối',
        }[ma] || ma
      )
    },
    mauTrangThai(ma) {
      return ['HOAN_THANH', 'DA_XAC_NHAN', 'DANG_AP_DUNG'].includes(ma)
        ? 'pt-green'
        : ['CHO_XAC_NHAN', 'CHO_DUYET', 'NHAP'].includes(ma)
          ? 'pt-orange'
          : ''
    },
  },
}
</script>

<style scoped>
.pt-journey {
  --pt-accent: var(--mau-chinh);
  --pt-green: #4ade80;
  color: var(--mau-chu);
}
:global(:root[data-theme='light'] .pt-journey) {
  --pt-accent: var(--mau-chinh);
  --pt-green: #047857;
}
.pt-welcome {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  background: var(--mau-the);
  padding: 24px;
  border-radius: 8px;
  border: 1px solid var(--mau-vien);
  margin-bottom: 24px;
}
.pt-kicker {
  font-size: 11px;
  letter-spacing: 0.04em;
  font-weight: 700;
  color: var(--mau-chinh);
  text-transform: uppercase;
}
.pt-welcome h1 {
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.01em;
  margin: 0;
  color: var(--mau-chu);
}
.pt-welcome p {
  color: var(--mau-phu);
  font-size: 13.5px;
  line-height: 1.5;
  margin: 6px 0 0;
}
.pt-actions {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}
.pt-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 40px;
  padding: 8px 16px;
  border: 1px solid var(--mau-vien);
  background: var(--mau-the);
  color: var(--mau-chu);
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.15s ease-in-out;
  white-space: nowrap;
}
.pt-button:hover {
  border-color: var(--mau-vien);
  background: var(--mau-the-hover);
  color: var(--mau-chu);
}
.pt-button:disabled {
  opacity: 0.6;
  cursor: wait;
}
.pt-icon {
  width: 40px;
  height: 40px;
  padding: 0;
}
.pt-primary {
  background: var(--mau-chinh);
  border-color: var(--mau-chinh);
  color: var(--mau-tren-chinh);
}
.pt-primary:hover {
  background: var(--mau-chinh-dam);
  border-color: var(--mau-chinh-dam);
  color: var(--mau-tren-chinh);
}
.pt-card {
  background: var(--mau-the);
  border: 1px solid var(--mau-vien);
  border-radius: 8px;
  padding: 24px;
  min-width: 0;
  box-shadow: none;
}
.pt-metrics {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin: 0 0 24px;
}
.pt-metric {
  padding: 16px 20px;
  background: var(--mau-the);
  border: 1px solid var(--mau-vien);
  border-radius: 8px;
  box-shadow: none;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.pt-metric dt {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  font-size: 11.5px;
  color: var(--mau-phu);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.pt-metric dt i {
  color: var(--mau-chinh);
  font-size: 1.15rem;
}
.pt-metric dd {
  margin: 8px 0 0;
  font-size: 26px;
  font-family: var(--font-chinh);
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  color: var(--mau-chu);
}
.pt-metric p {
  font-size: 11.5px;
  color: var(--mau-phu);
  margin: 4px 0 0;
}
.pt-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr);
  gap: 24px;
  align-items: start;
}
.pt-section-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 19px;
}
.pt-section-heading h2,
.pt-quick-section h2 {
  font-size: 1.05rem;
  font-weight: 750;
  letter-spacing: 0;
  margin: 0;
}
.pt-section-heading > i {
  color: var(--pt-accent);
}
.pt-section-heading p {
  color: var(--mau-phu);
  font-size: 0.72rem;
  margin: 7px 0 0;
}
.pt-link {
  font-size: 0.73rem;
  font-weight: 650;
  color: var(--pt-accent);
  text-decoration: none;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}
.pt-link:hover,
.pt-bottom-link:hover {
  text-decoration: underline;
}
.pt-agenda-row {
  display: flex;
  align-items: center;
  gap: 16px;
  text-decoration: none;
  color: var(--mau-chu);
  padding: 17px 0;
  border-top: 1px solid var(--mau-vien);
}
.pt-agenda-row:hover strong {
  color: var(--pt-accent);
}
.pt-time {
  padding: 0 16px 0 12px;
  border-left: 3px solid var(--pt-accent);
  flex-shrink: 0;
}
.pt-time b {
  display: block;
  font-size: 0.88rem;
  font-variant-numeric: tabular-nums;
}
.pt-time small {
  font-size: 0.7rem;
  color: var(--mau-phu);
}
.pt-copy {
  flex: 1;
  min-width: 0;
  overflow-wrap: anywhere;
}
.pt-copy strong {
  display: block;
  font-size: 0.83rem;
  font-weight: 650;
  line-height: 1.5;
}
.pt-copy small {
  display: block;
  color: var(--mau-phu);
  font-size: 0.73rem;
  line-height: 1.5;
  margin-top: 3px;
}
.pt-state {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.66rem;
  font-weight: 650;
  border-radius: 8px;
  padding: 6px 9px;
  background: var(--mau-nen);
  color: var(--mau-phu);
  text-align: center;
  max-width: 140px;
}
.pt-green {
  color: var(--pt-green);
  background: color-mix(in srgb, var(--pt-green) 10%, transparent);
}
.pt-orange {
  color: var(--pt-accent);
  background: var(--mau-chinh-nhat);
}
.pt-task-row {
  display: flex;
  gap: 12px;
  align-items: center;
  color: var(--mau-chu);
  text-decoration: none;
  padding: 14px 0;
  border-top: 1px solid var(--mau-vien);
  font-size: 0.75rem;
}
.pt-task-row > span:nth-child(2) {
  flex: 1;
}
.pt-task-row:hover {
  color: var(--pt-accent);
}
.pt-task-row b {
  font-variant-numeric: tabular-nums;
}
.pt-small-icon {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: var(--mau-chinh-nhat);
  color: var(--pt-accent);
  flex-shrink: 0;
}
.pt-footnote {
  font-size: 0.69rem;
  line-height: 1.65;
  color: var(--mau-phu);
  margin: 14px 0 0;
}
.pt-person-row,
.pt-chat-row {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 17px 0;
  border-top: 1px solid var(--mau-vien);
}
.pt-avatar {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 42px;
  height: 42px;
  border-radius: 50%;
  color: var(--pt-accent);
  background: var(--mau-chinh-nhat);
  font-weight: 750;
}
.pt-plan-name {
  font-style: italic;
}
.pt-student-progress {
  width: 112px;
  flex-shrink: 0;
  font-size: 0.68rem;
}
.pt-student-progress small {
  font-size: 0.65rem;
  color: var(--mau-phu);
}
.pt-track {
  height: 6px;
  border-radius: 8px;
  background: var(--mau-vien);
  overflow: hidden;
  margin: 7px 0;
}
.pt-track > span {
  display: block;
  background: var(--pt-accent);
  height: 100%;
  border-radius: inherit;
}
.pt-bottom-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 44px;
  border-top: 1px solid var(--mau-vien);
  margin-top: 12px;
  padding-top: 14px;
  color: var(--pt-accent);
  font-size: 0.76rem;
  font-weight: 650;
  text-decoration: none;
}
.pt-attention-row {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 13px 0;
  color: var(--mau-chu);
  text-decoration: none;
}
.pt-attention-row > i {
  color: var(--pt-accent);
}
.pt-attention-row:hover strong {
  color: var(--pt-accent);
}
.pt-plan-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin: 0 0 18px;
  text-align: center;
}
.pt-plan-stats > div {
  background: var(--mau-nen);
  border: 1px solid var(--mau-vien);
  padding: 14px 8px;
  border-radius: 8px;
}
.pt-plan-stats dd {
  font-size: 1.3rem;
  font-weight: 750;
  margin: 0 0 3px;
}
.pt-plan-stats dt {
  font-size: 0.69rem;
  font-weight: 500;
  color: var(--mau-phu);
}
.pt-plan-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 15px 0;
  border-top: 1px solid var(--mau-vien);
}
.pt-source {
  display: block;
  color: var(--pt-accent);
  font-size: 0.64rem;
  margin-top: 4px;
}
.pt-chat-row {
  text-decoration: none;
  color: var(--mau-chu);
}
.pt-chat-row:hover strong {
  color: var(--pt-accent);
}
.pt-preview {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.pt-unread {
  display: grid;
  place-items: center;
  min-width: 23px;
  height: 23px;
  border-radius: 50%;
  padding: 2px;
  background: var(--mau-chinh);
  color: #fff;
  font-size: 0.65rem;
}
.pt-week-stats {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 12px;
  margin: 24px 0 30px;
}
.pt-week-stats dd {
  font-size: 1.55rem;
  font-weight: 750;
  margin: 0 0 5px;
  font-family: var(--font-chinh);
  font-variant-numeric: tabular-nums;
}
.pt-week-stats dt {
  color: var(--mau-phu);
  font-size: 0.65rem;
  font-weight: 500;
}
.pt-chart {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 14px;
}
.pt-chart-column {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  font-size: 0.69rem;
  color: var(--mau-phu);
}
.pt-chart-number {
  font-weight: 650;
  color: var(--mau-chu);
}
.pt-chart-track {
  display: flex;
  align-items: end;
  height: 110px;
  width: 100%;
  background: var(--mau-nen);
  border-radius: 7px;
  overflow: hidden;
}
.pt-chart-bar {
  width: 100%;
  background: var(--pt-accent);
  border-radius: 7px 7px 0 0;
}
.pt-slots > .pt-footnote {
  margin: -8px 0 20px;
}
.pt-slot-stats {
  margin-bottom: 12px;
}
.pt-slot-stats > div {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 0;
}
.pt-slot-stats dt {
  font-size: 0.76rem;
  font-weight: 500;
  color: var(--mau-phu);
}
.pt-slot-stats dd {
  font-size: 0.85rem;
  font-weight: 700;
  margin: 0;
}
.pt-slot-empty {
  display: flex;
  align-items: center;
  gap: 11px;
  border: 1px dashed var(--mau-vien);
  padding: 20px 14px;
  border-radius: 8px;
  margin: 22px 0;
  color: var(--mau-phu);
  font-size: 0.73rem;
}
.pt-slot-empty strong {
  font-size: 1.6rem;
  color: var(--pt-accent);
}
.pt-slots > .pt-button {
  width: 100%;
}
.pt-empty {
  display: grid;
  justify-items: center;
  padding: 26px 10px;
  text-align: center;
  gap: 10px;
}
.pt-empty > i {
  font-size: 1.8rem;
  color: var(--pt-accent);
}
.pt-empty strong {
  font-size: 0.88rem;
}
.pt-empty p,
.pt-empty-copy {
  color: var(--mau-phu);
  font-size: 0.8rem;
  line-height: 1.7;
}
.pt-empty p {
  margin: 0;
}
.pt-empty-copy {
  padding: 16px 0;
  margin: 0;
}
.pt-quick-section {
  margin-top: 30px;
}
.pt-quick-section h2 {
  margin-bottom: 18px;
}
.pt-quick-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 14px;
}
.pt-quick {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 19px 14px;
  text-decoration: none;
  color: var(--mau-chu);
  border-radius: 8px;
  transition: border-color 0.18s;
}
.pt-quick > i:first-child {
  color: var(--pt-accent);
  font-size: 1.15rem;
}
.pt-quick > i:last-child {
  margin-left: auto;
  font-size: 0.65rem;
  color: var(--mau-phu);
}
.pt-quick strong {
  font-size: 0.73rem;
  font-weight: 650;
}
.pt-quick:hover {
  border-color: var(--pt-accent);
  color: var(--pt-accent);
}
a:focus-visible,
button:focus-visible {
  outline: 3px solid var(--pt-accent);
  outline-offset: 3px;
}
@media (max-width: 1200px) {
  .pt-welcome {
    align-items: start;
  }
  .pt-actions {
    justify-content: end;
  }
  .pt-quick-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .pt-person-row {
    flex-wrap: wrap;
  }
  .pt-student-progress {
    margin-left: 55px;
  }
  .pt-person-row > .pt-button {
    margin-left: auto;
  }
}
@media (max-width: 1000px) {
  .pt-grid {
    grid-template-columns: minmax(0, 1fr);
  }
  .pt-welcome {
    flex-wrap: wrap;
  }
  .pt-actions {
    justify-content: start;
  }
}
@media (max-width: 700px) {
  .pt-person-row {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) auto;
    gap: 12px;
  }
  .pt-person-row > .pt-avatar {
    grid-column: 1;
    grid-row: 1;
  }
  .pt-person-row > .pt-copy {
    grid-column: 2 / 4;
    grid-row: 1;
  }
  .pt-person-row > .pt-student-progress {
    grid-column: 2;
    grid-row: 2;
    width: auto;
    margin-left: 0;
  }
  .pt-person-row > .pt-button {
    grid-column: 3;
    grid-row: 2;
  }
  .pt-metrics {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }
  .pt-card {
    padding: 19px;
    border-radius: 8px;
  }
  .pt-welcome h1 {
    font-size: 1.65rem;
  }
  .pt-section-heading {
    flex-wrap: wrap;
  }
  .pt-quick-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }
  .pt-week-stats {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    row-gap: 18px;
  }
  .pt-agenda-row {
    flex-wrap: wrap;
    gap: 10px;
  }
  .pt-agenda-row .pt-copy {
    min-width: 130px;
  }
  .pt-agenda-row .pt-state {
    margin-left: 86px;
  }
  .pt-agenda-row > i {
    margin-left: auto;
  }
  .pt-plan-row {
    flex-wrap: wrap;
  }
  .pt-plan-row .pt-copy {
    flex-basis: calc(100% - 120px);
  }
  .pt-plan-row .pt-state {
    max-width: 105px;
  }
  .pt-plan-row > .pt-button {
    margin-left: auto;
  }
  .pt-plan-stats dt {
    font-size: 0.64rem;
  }
  .pt-chart {
    gap: 8px;
  }
  .pt-quick {
    padding: 16px 12px;
    gap: 8px;
  }
}
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
  }
}
</style>
