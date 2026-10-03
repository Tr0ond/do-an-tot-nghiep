<template>
  <CaNhanLayout>
    <section class="dashboard" :aria-busy="dangTai">
      <!-- Header Dashboard -->
      <header class="dashboard-heading">
        <div>
          <div class="eyebrow mb-1">
            <i
              :class="laAdmin ? 'bi bi-shield-lock-fill' : 'bi bi-lightning-charge-fill'"
              class="text-emerald me-1"
              aria-hidden="true"
            ></i>
            <span>{{ laAdmin ? 'TRUNG TÂM ĐIỀU HÀNH HỆ THỐNG' : 'KHÔNG GIAN CỦA BẠN' }}</span>
          </div>
          <h1 class="h2 fw-bold mb-1">
            {{ laAdmin ? 'Tổng quan hệ thống' : 'Chào ' + tenGoi + '!' }}
          </h1>
          <p class="text-muted small mb-0">{{ moTa }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button
            class="btn btn-outline-secondary btn-sm shadow-sm"
            :disabled="dangTai"
            title="Làm mới số liệu"
            @click="taiTongQuan"
          >
            <i
              class="bi bi-arrow-clockwise me-1"
              :class="{ 'spin-anim': dangTai }"
              aria-hidden="true"
            ></i>
            <span>{{ dangTai ? 'Đang tải…' : 'Cập nhật' }}</span>
          </button>
        </div>
      </header>

      <!-- Thông báo lỗi nếu có -->
      <div
        v-if="thongBao"
        class="alert alert-danger d-flex align-items-center gap-2 p-3 mb-4 rounded-3"
        role="alert"
      >
        <i
          class="bi bi-exclamation-triangle-fill fs-5 text-danger flex-shrink-0"
          aria-hidden="true"
        ></i>
        <div class="small fw-semibold flex-grow-1">{{ thongBao }}</div>
        <RouterLink v-if="hetPhien" to="/dang-nhap" class="btn btn-primary btn-sm">
          Đăng nhập lại
        </RouterLink>
        <button v-else class="btn btn-outline-danger btn-sm" @click="taiTongQuan">Thử lại</button>
      </div>

      <!-- Trạng thái đang tải toàn trang -->
      <div v-else-if="dangTai && !duLieu" class="dashboard-loading p-5 text-center" role="status">
        <span class="spinner-border text-success mb-3" aria-hidden="true"></span>
        <p class="text-muted mb-0 small">Đang tải thống kê của bạn…</p>
      </div>

      <!-- Nội dung Dashboard khi có dữ liệu -->
      <template v-if="duLieu && vaiTro">
        <!-- Hàng thẻ chỉ số tổng quan (Metric Cards) -->
        <dl class="metric-grid mb-4" aria-label="Thống kê tổng quan">
          <div v-for="muc in cacThongKe" :key="muc.nhan" class="metric-item">
            <div class="metric-top">
              <dt>{{ muc.nhan }}</dt>
              <div class="metric-icon-box">
                <i :class="muc.bieuTuong" aria-hidden="true"></i>
              </div>
            </div>
            <dd>
              {{ dinhDangSo(muc.so)
              }}<span v-if="muc.donVi" class="metric-unit">{{ muc.donVi }}</span>
            </dd>
            <p>{{ muc.moTa }}</p>
          </div>
        </dl>

        <!-- DASHBOARD DÀNH CHO ADMIN -->
        <div v-if="laAdmin" class="dashboard-columns">
          <!-- Cột 1: Biểu đồ phân bố tài khoản (Charts.css Column Chart) -->
          <section class="dashboard-panel shadow-sm">
            <div class="panel-heading">
              <div>
                <span class="section-kicker">THÀNH VIÊN & PHÂN QUYỀN</span>
                <h2>Phân bố tài khoản</h2>
              </div>
              <RouterLink to="/admin/tai-khoan" class="btn-link-action">
                <span>Quản lý</span>
                <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i>
              </RouterLink>
            </div>

            <!-- Biểu đồ cột thuần CSS phân bố vai trò -->
            <div class="chart-container-box my-3">
              <table
                class="charts-css column show-labels show-data-axes data-spacing-15"
                style="height: 180px; width: 100%"
              >
                <caption>
                  Phân bố vai trò tài khoản trong hệ thống
                </caption>
                <thead>
                  <tr>
                    <th scope="col">Vai trò</th>
                    <th scope="col">Số lượng</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="muc in cacVaiTro" :key="muc.nhan">
                    <th scope="row" class="chart-label">{{ muc.nhan }}</th>
                    <td
                      class="chart-bar"
                      :class="layClassCotVaiTro(muc.nhan)"
                      :style="{ '--size': tiLeThapPhan(muc.so, maxVaiTro) }"
                    >
                      <span class="data font-monospace">{{ dinhDangSo(muc.so) }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Thanh tỷ lệ phân bổ chi tiết -->
            <div class="role-bars mt-4">
              <div v-for="muc in cacVaiTro" :key="muc.nhan" class="role-row">
                <div class="role-row-header">
                  <span class="d-flex align-items-center gap-2">
                    <span class="role-legend-dot" :class="layClassChamVaiTro(muc.nhan)"></span>
                    <span>{{ muc.nhan }}</span>
                  </span>
                  <div>
                    <strong>{{ dinhDangSo(muc.so) }}</strong>
                    <span class="text-muted small ms-1">
                      ({{ tiLe(muc.so, duLieu.quan_tri.tai_khoan.tong) }}%)
                    </span>
                  </div>
                </div>
                <div class="bar-track" aria-hidden="true">
                  <span
                    :class="layClassTienTrinhVaiTro(muc.nhan)"
                    :style="{ width: tiLe(muc.so, duLieu.quan_tri.tai_khoan.tong) + '%' }"
                  ></span>
                </div>
              </div>
            </div>

            <!-- Tình trạng hoạt động tài khoản -->
            <div class="account-status">
              <span class="status-badge-item text-success">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <strong>{{ duLieu.quan_tri.tai_khoan.hoat_dong }}</strong> đang hoạt động
              </span>
              <span class="status-badge-item text-danger">
                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                <strong>{{ duLieu.quan_tri.tai_khoan.bi_khoa }}</strong> bị khóa
              </span>
            </div>
          </section>

          <!-- Cột 2: Danh mục nội dung hệ thống -->
          <section class="dashboard-panel shadow-sm">
            <div class="panel-heading">
              <div>
                <span class="section-kicker">NỘI DUNG HUẤN LUYỆN</span>
                <h2>Danh mục đang phục vụ</h2>
              </div>
            </div>

            <ul class="catalog-summary">
              <li v-for="muc in cacDanhMuc" :key="muc.nhan">
                <RouterLink :to="muc.to" class="catalog-link-item">
                  <div class="summary-icon">
                    <i :class="muc.bieuTuong" aria-hidden="true"></i>
                  </div>
                  <div class="catalog-link-copy">
                    <strong>{{ muc.nhan }}</strong>
                    <small>{{ muc.moTa }}</small>
                  </div>
                  <div class="catalog-metric-pill">
                    <b>{{ dinhDangSo(muc.so) }}</b>
                    <i class="bi bi-chevron-right ms-1 text-muted" aria-hidden="true"></i>
                  </div>
                </RouterLink>
              </li>
            </ul>

            <!-- Thống kê trạng thái giáo án chi tiết -->
            <div class="plan-status-breakdown mt-4 pt-3 border-top">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-semibold text-body">
                  <i class="bi bi-journal-text text-emerald me-1" aria-hidden="true"></i>Tình trạng
                  duyệt giáo án mẫu
                </span>
                <span class="small text-muted font-monospace">
                  {{ duLieu.quan_tri.giao_an_mau.da_duyet }} /
                  {{ duLieu.quan_tri.giao_an_mau.tong }} đã duyệt
                </span>
              </div>
              <div class="progress" style="height: 8px">
                <div
                  class="progress-bar bg-success"
                  role="progressbar"
                  :style="{
                    width:
                      tiLe(duLieu.quan_tri.giao_an_mau.da_duyet, duLieu.quan_tri.giao_an_mau.tong) +
                      '%',
                  }"
                  title="Đã duyệt"
                ></div>
                <div
                  class="progress-bar bg-warning"
                  role="progressbar"
                  :style="{
                    width:
                      tiLe(duLieu.quan_tri.giao_an_mau.ban_nhap, duLieu.quan_tri.giao_an_mau.tong) +
                      '%',
                  }"
                  title="Bản nháp"
                ></div>
                <div
                  class="progress-bar bg-secondary"
                  role="progressbar"
                  :style="{
                    width:
                      tiLe(
                        duLieu.quan_tri.giao_an_mau.ngung_su_dung,
                        duLieu.quan_tri.giao_an_mau.tong,
                      ) + '%',
                  }"
                  title="Ngừng dùng"
                ></div>
              </div>
              <div class="d-flex justify-content-between text-muted small mt-2">
                <span
                  ><i class="bi bi-dot text-success"></i>Đã duyệt:
                  {{ duLieu.quan_tri.giao_an_mau.da_duyet }}</span
                >
                <span
                  ><i class="bi bi-dot text-warning"></i>Bản nháp:
                  {{ duLieu.quan_tri.giao_an_mau.ban_nhap }}</span
                >
                <span
                  ><i class="bi bi-dot text-secondary"></i>Ngừng dùng:
                  {{ duLieu.quan_tri.giao_an_mau.ngung_su_dung }}</span
                >
              </div>
            </div>
          </section>
        </div>

        <!-- DASHBOARD DÀNH CHO KHÁCH HÀNG & PT -->
        <div v-else class="dashboard-columns">
          <!-- Cột 1: Thẻ tiêu điểm cá nhân / Mục tiêu thể hình -->
          <section class="personal-focus shadow-sm">
            <div class="focus-badge mb-2">
              <i class="bi bi-compass-fill me-1" aria-hidden="true"></i>
              <span>{{ laPt ? 'CHUYÊN MÔN & GIÁO ÁN' : 'MỤC TIÊU CỦA BẠN' }}</span>
            </div>
            <h2>{{ tieuDeMucTieu }}</h2>
            <p>
              {{
                laPt
                  ? 'Đọc giáo án đã duyệt và tham khảo hướng dẫn bài tập cho công việc huấn luyện.'
                  : 'Hồ sơ giúp huấn luyện viên hiểu kinh nghiệm và thời gian tập phù hợp với bạn.'
              }}
            </p>
            <RouterLink
              class="btn btn-primary shadow-sm"
              :to="laPt ? '/pt/giao-an-mau' : duongDanHoSo + '/sua'"
            >
              <i
                :class="laPt ? 'bi bi-journal-bookmark me-1' : 'bi bi-pencil-square me-1'"
                aria-hidden="true"
              ></i>
              <span>{{ laPt ? 'Mở thư viện giáo án' : 'Cập nhật mục tiêu' }}</span>
              <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
            </RouterLink>

            <div class="focus-footnote">
              <i class="bi bi-shield-check text-emerald" aria-hidden="true"></i>
              <span>
                {{
                  laPt
                    ? 'Chỉ giáo án đã được duyệt xuất hiện trong thư viện công khai.'
                    : 'Bạn có thể bổ sung hoặc thay đổi hồ sơ bất cứ lúc nào để nhận giáo án tối ưu.'
                }}
              </span>
            </div>
          </section>

          <!-- Cột 2: Thẻ hoàn thiện hồ sơ với Vòng tiến độ SVG -->
          <section class="dashboard-panel profile-panel shadow-sm">
            <div class="panel-heading">
              <div>
                <span class="section-kicker">TIẾN ĐỘ THỂ CHẤT</span>
                <h2>Hoàn thiện hồ sơ</h2>
              </div>
              <strong class="profile-percent">{{ phanTramHoSo }}%</strong>
            </div>

            <!-- Vòng tròn tiến độ SVG trực quan -->
            <div class="profile-ring-container my-3">
              <div class="ring-wrapper">
                <svg viewBox="0 0 100 100" class="progress-ring-svg" aria-hidden="true">
                  <circle class="ring-bg" cx="50" cy="50" r="42" />
                  <circle
                    class="ring-indicator"
                    cx="50"
                    cy="50"
                    r="42"
                    :style="{
                      strokeDasharray: '264',
                      strokeDashoffset: (264 - (264 * phanTramHoSo) / 100).toFixed(1),
                    }"
                  />
                </svg>
                <div class="ring-center-content">
                  <span class="ring-number">{{ phanTramHoSo }}%</span>
                  <span class="ring-label">đầy đủ</span>
                </div>
              </div>

              <div class="ring-summary-copy">
                <p class="profile-caption mb-1">
                  <strong>{{ duLieu.ho_so.hoan_thanh }}</strong> trên tổng số
                  <strong>{{ duLieu.ho_so.tong_muc }}</strong> mục đã có dữ liệu.
                </p>
                <span class="text-muted small">
                  {{
                    phanTramHoSo === 100
                      ? 'Hồ sơ đã đạt mức tối đa! Huấn luyện viên đã có đủ thông tin.'
                      : 'Bổ sung thêm để huấn luyện viên hiểu rõ bạn hơn.'
                  }}
                </span>
              </div>
            </div>

            <!-- Thanh tiến trình ẩn phục vụ accessibility -->
            <progress
              :value="duLieu.ho_so.hoan_thanh"
              :max="duLieu.ho_so.tong_muc"
              aria-label="Mức độ đầy đủ hồ sơ"
              class="d-none"
            ></progress>

            <!-- Danh sách các mục trong hồ sơ -->
            <ul class="profile-checklist">
              <li v-for="muc in duLieu.ho_so.cac_muc" :key="muc.ma">
                <i
                  :class="muc.da_co ? 'bi bi-check-circle-fill da-co' : 'bi bi-circle'"
                  aria-hidden="true"
                ></i>
                <span class="checklist-name">{{ muc.nhan }}</span>
                <span
                  class="badge small"
                  :class="
                    muc.da_co ? 'bg-success-subtle text-success' : 'bg-light text-muted border'
                  "
                >
                  {{ muc.da_co ? 'Đã có' : 'Cần bổ sung' }}
                </span>
              </li>
            </ul>

            <RouterLink :to="duongDanHoSo" class="profile-link">
              <span>Xem chi tiết hồ sơ</span>
              <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
            </RouterLink>
          </section>
        </div>

        <!-- Thao tác nhanh (Lối tắt) -->
        <section class="quick-section">
          <div class="panel-heading mb-3">
            <div>
              <span class="section-kicker">LỐI TẮT HỆ THỐNG</span>
              <h2 class="h5 fw-bold mb-0">
                {{ laAdmin ? 'Điều hành & quản lý' : 'Tiếp tục khám phá' }}
              </h2>
            </div>
          </div>
          <div class="quick-grid">
            <RouterLink
              v-for="muc in cacLoiTat"
              :key="muc.to"
              :to="muc.to"
              class="quick-link shadow-sm"
            >
              <div class="quick-icon-wrapper">
                <i :class="muc.bieuTuong" aria-hidden="true"></i>
              </div>
              <div class="quick-link-copy">
                <strong>{{ muc.nhan }}</strong>
                <small>{{ muc.moTa }}</small>
              </div>
              <div class="quick-arrow-box">
                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
              </div>
            </RouterLink>
          </div>
        </section>

        <!-- Thời gian cập nhật -->
        <p class="updated-label">
          <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
          Cập nhật lúc {{ thoiGianCapNhat }}
        </p>
      </template>
    </section>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../layouts/CaNhanLayout.vue'
import { useXacThucStore } from '../../stores/xacThuc'
import tongQuanService from '../../services/tongQuanService'
import { layLoiApi } from '../../utils/loiApi'

export default {
  name: 'TrangTongQuan',
  components: { CaNhanLayout },
  data() {
    return { duLieu: null, dangTai: false, thongBao: '', hetPhien: false, boHuy: null, lanTai: 0 }
  },
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
    vaiTro() {
      return this.xacThuc.taiKhoan?.vai_tro
    },
    laAdmin() {
      return this.vaiTro === 'ADMIN'
    },
    laPt() {
      return this.vaiTro === 'HUAN_LUYEN_VIEN'
    },
    tenGoi() {
      return this.xacThuc.taiKhoan?.ho_ten?.trim().split(/\s+/).at(-1) || 'bạn'
    },
    moTa() {
      return this.laAdmin
        ? 'Theo dõi thành viên và nội dung huấn luyện trong một nơi.'
        : this.laPt
          ? 'Tổng quan hồ sơ chuyên môn và thư viện dành cho huấn luyện viên.'
          : 'Một nơi để theo dõi hồ sơ và bắt đầu hành trình tập luyện của bạn.'
    },
    duongDanHoSo() {
      return this.laPt ? '/pt/ho-so' : '/khach-hang/ho-so'
    },
    phanTramHoSo() {
      return this.tiLe(this.duLieu?.ho_so?.hoan_thanh, this.duLieu?.ho_so?.tong_muc)
    },
    tieuDeMucTieu() {
      return this.laPt
        ? 'Chuẩn bị cho từng buổi tập'
        : this.xacThuc.taiKhoan?.ho_so_khach_hang?.muc_tieu || 'Bắt đầu từ mục tiêu của bạn'
    },
    thoiGianCapNhat() {
      return new Date(this.duLieu.cap_nhat_luc).toLocaleString('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: '2-digit',
      })
    },
    cacThongKe() {
      if (!this.duLieu) return []
      const thuVien = this.duLieu.thu_vien
      if (this.laAdmin) {
        const q = this.duLieu.quan_tri
        return [
          {
            nhan: 'Tổng tài khoản',
            so: q.tai_khoan.tong,
            moTa: q.tai_khoan.hoat_dong + ' tài khoản hoạt động',
            bieuTuong: 'bi bi-people-fill',
          },
          {
            nhan: 'Thư viện bài tập',
            so: q.bai_tap.tong,
            moTa: q.bai_tap.hien_thi + ' bài đang hiển thị',
            bieuTuong: 'bi bi-collection-play-fill',
          },
          {
            nhan: 'Gói đang mở bán',
            so: q.goi_tap.dang_ban,
            moTa: q.goi_tap.tong + ' gói trong danh mục',
            bieuTuong: 'bi bi-box-seam-fill',
          },
          {
            nhan: 'Giáo án đã duyệt',
            so: q.giao_an_mau.da_duyet,
            moTa: q.giao_an_mau.ban_nhap + ' bản nháp cần xem xét',
            bieuTuong: 'bi bi-journal-check',
          },
        ]
      }
      return [
        {
          nhan: 'Hồ sơ hoàn thiện',
          so: this.phanTramHoSo,
          donVi: '%',
          moTa: 'Thông tin cá nhân đã cung cấp',
          bieuTuong: 'bi bi-person-check-fill',
        },
        {
          nhan: 'Bài tập có thể xem',
          so: thuVien.bai_tap,
          moTa: 'Hướng dẫn trong thư viện',
          bieuTuong: 'bi bi-collection-play-fill',
        },
        {
          nhan: 'Nhóm cơ trong thư viện',
          so: thuVien.nhom_co,
          moTa: 'Có bài tập đang hiển thị',
          bieuTuong: 'bi bi-diagram-3-fill',
        },
        this.laPt
          ? {
              nhan: 'Giáo án có thể đọc',
              so: this.duLieu.giao_an_da_duyet,
              moTa: 'Đã được Admin duyệt',
              bieuTuong: 'bi bi-journal-check',
            }
          : {
              nhan: 'Gói đang mở bán',
              so: thuVien.goi_tap,
              moTa: 'Xem giá và quyền lợi',
              bieuTuong: 'bi bi-box-seam-fill',
            },
      ]
    },
    cacVaiTro() {
      const q = this.duLieu?.quan_tri?.tai_khoan
      return q
        ? [
            { nhan: 'Khách hàng', so: q.khach_hang },
            { nhan: 'Huấn luyện viên', so: q.huan_luyen_vien },
            { nhan: 'Quản trị viên', so: q.admin },
          ]
        : []
    },
    maxVaiTro() {
      if (!this.cacVaiTro.length) return 1
      return Math.max(1, ...this.cacVaiTro.map((v) => v.so))
    },
    cacDanhMuc() {
      const q = this.duLieu?.quan_tri
      return q
        ? [
            {
              nhan: 'Bài tập',
              so: q.bai_tap.hien_thi,
              moTa: 'Đang hiển thị cho thành viên',
              to: '/admin/bai-tap',
              bieuTuong: 'bi bi-collection-play',
            },
            {
              nhan: 'Nhóm cơ',
              so: q.nhom_co.hoat_dong,
              moTa: q.nhom_co.tong + ' nhóm trong hệ thống',
              to: '/admin/nhom-co',
              bieuTuong: 'bi bi-diagram-3',
            },
            {
              nhan: 'Gói tập',
              so: q.goi_tap.dang_ban,
              moTa: 'Hợp lệ và đang mở bán',
              to: '/admin/goi-tap',
              bieuTuong: 'bi bi-box-seam',
            },
            {
              nhan: 'Giáo án mẫu',
              so: q.giao_an_mau.da_duyet,
              moTa: q.giao_an_mau.tong + ' giáo án trong hệ thống',
              to: '/admin/giao-an-mau',
              bieuTuong: 'bi bi-journal-check',
            },
          ]
        : []
    },
    cacLoiTat() {
      return this.laAdmin
        ? [
            {
              nhan: 'Quản lý tài khoản',
              moTa: 'Cấp quyền, khóa và mở khóa',
              to: '/admin/tai-khoan',
              bieuTuong: 'bi bi-people-fill',
            },
            {
              nhan: 'Thêm gói tập',
              moTa: 'Thiết lập giá và quyền lợi',
              to: '/admin/goi-tap/them',
              bieuTuong: 'bi bi-box-seam-fill',
            },
            {
              nhan: 'Soạn giáo án',
              moTa: 'Chuẩn bị nội dung huấn luyện',
              to: '/admin/giao-an-mau/them',
              bieuTuong: 'bi bi-journal-plus',
            },
          ]
        : [
            {
              nhan: 'Thư viện bài tập',
              moTa: 'Tìm hướng dẫn theo nhóm cơ',
              to: '/bai-tap',
              bieuTuong: 'bi bi-collection-play-fill',
            },
            this.laPt
              ? {
                  nhan: 'Giáo án mẫu',
                  moTa: 'Tham khảo nội dung đã duyệt',
                  to: '/pt/giao-an-mau',
                  bieuTuong: 'bi bi-journal-check',
                }
              : {
                  nhan: 'Khám phá gói tập',
                  moTa: 'Xem giá và quyền lợi phù hợp',
                  to: '/goi-tap',
                  bieuTuong: 'bi bi-box-seam-fill',
                },
            {
              nhan: 'Hồ sơ của tôi',
              moTa: 'Xem và bổ sung thông tin',
              to: this.duongDanHoSo,
              bieuTuong: 'bi bi-person-fill',
            },
          ]
    },
  },
  watch: {
    '$route.path'() {
      this.duLieu = null
      this.taiTongQuan()
    },
  },
  mounted() {
    this.taiTongQuan()
  },
  beforeUnmount() {
    this.lanTai++
    this.boHuy?.abort()
  },
  methods: {
    dinhDangSo(so) {
      return Number(so).toLocaleString('vi-VN')
    },
    tiLe(so, tong) {
      return tong > 0 ? Math.min(100, Math.round((so / tong) * 100)) : 0
    },
    tiLeThapPhan(so, tong) {
      if (!tong || tong <= 0) return 0
      return Math.min(1, Math.max(0, Number((so / tong).toFixed(3))))
    },
    layClassCotVaiTro(nhan) {
      return (
        {
          'Khách hàng': 'chart-bar-blue',
          'Huấn luyện viên': 'chart-bar-amber',
          'Quản trị viên': 'chart-bar-purple',
        }[nhan] || ''
      )
    },
    layClassChamVaiTro(nhan) {
      return (
        {
          'Khách hàng': 'bg-primary',
          'Huấn luyện viên': 'bg-warning',
          'Quản trị viên': 'bg-purple',
        }[nhan] || 'bg-secondary'
      )
    },
    layClassTienTrinhVaiTro(nhan) {
      return (
        {
          'Khách hàng': 'bg-primary',
          'Huấn luyện viên': 'bg-warning',
          'Quản trị viên': 'bg-purple',
        }[nhan] || 'bg-secondary'
      )
    },
    async taiTongQuan() {
      this.boHuy?.abort()
      const lanTai = ++this.lanTai
      this.boHuy = new AbortController()
      this.dangTai = true
      this.thongBao = ''
      this.hetPhien = false
      try {
        const ketQua = await tongQuanService.taiTongQuan(this.vaiTro, this.boHuy.signal)
        if (lanTai !== this.lanTai) return
        this.duLieu = ketQua.data
      } catch (loi) {
        if (lanTai !== this.lanTai || loi.code === 'ERR_CANCELED') return
        const ketQua = layLoiApi(loi)
        this.thongBao = ketQua.thongBao
        this.hetPhien = ketQua.hetPhien
        this.duLieu = null
        if (this.hetPhien) this.xacThuc.taiKhoan = null
      } finally {
        if (lanTai === this.lanTai) this.dangTai = false
      }
    },
  },
}
</script>

<style scoped>
.text-emerald {
  color: var(--mau-chinh);
}

.bg-purple {
  background-color: #7c3aed !important;
}

.dashboard {
  --dashboard-ink: var(--mau-chu, #ffffff);
  --dashboard-green: var(--mau-chinh, #f45b20);
  --dashboard-muted: var(--mau-phu, #94a3b8);
}

.dashboard-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 28px;
}

.dashboard-heading h1 {
  font-family: 'Outfit', 'Plus Jakarta Sans', system-ui, sans-serif;
  font-size: clamp(1.65rem, 3vw, 2.15rem);
  letter-spacing: -0.04em;
  margin: 4px 0 6px;
  color: var(--dashboard-ink, #ffffff);
  overflow-wrap: anywhere;
}

.dashboard-heading p {
  margin: 0;
  color: var(--dashboard-muted);
}

/* Metric Cards */
.metric-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin: 0 0 28px;
}

.metric-item {
  background: var(--mau-the, #141414);
  border: 1px solid var(--mau-vien, rgba(255, 255, 255, 0.08));
  border-radius: 16px;
  padding: 20px;
  transition:
    transform 0.25s cubic-bezier(0.23, 1, 0.32, 1),
    box-shadow 0.25s ease,
    border-color 0.25s ease;
}

.metric-item:hover {
  transform: translateY(-2px);
  border-color: rgba(244, 91, 32, 0.4);
  box-shadow:
    0 8px 24px rgba(0, 0, 0, 0.6),
    0 0 20px rgba(244, 91, 32, 0.12);
}

.metric-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.metric-top dt {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--dashboard-muted);
  margin: 0;
}

.metric-icon-box {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: rgba(244, 91, 32, 0.12);
  color: var(--mau-chinh);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  border: 1px solid rgba(244, 91, 32, 0.25);
}

.metric-item dd {
  font-family: 'Outfit', 'Plus Jakarta Sans', system-ui, sans-serif;
  font-size: 2.2rem;
  letter-spacing: -0.05em;
  font-weight: 800;
  color: var(--dashboard-ink, var(--mau-chu));
  margin: 12px 0 4px;
  line-height: 1.1;
  font-variant-numeric: tabular-nums;
}

.metric-unit {
  font-size: 1.25rem;
  margin-left: 2px;
  color: var(--mau-chinh);
}

.metric-item p {
  margin: 0;
  color: var(--dashboard-muted);
  font-size: 0.8rem;
}

/* 2 Columns Layout */
.dashboard-columns {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
  gap: 24px;
}

.dashboard-panel {
  background: var(--mau-the, #141414);
  border: 1px solid var(--mau-vien, rgba(255, 255, 255, 0.08));
  border-radius: 16px;
  padding: 26px;
  min-width: 0;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
}

.panel-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.section-kicker {
  color: var(--mau-chinh, #f45b20);
  font-size: 0.72rem;
  letter-spacing: 0.1em;
  font-weight: 750;
  display: block;
  text-transform: uppercase;
}

.panel-heading h2 {
  font-family: 'Outfit', 'Plus Jakarta Sans', system-ui, sans-serif;
  font-size: 1.22rem;
  font-weight: 700;
  margin: 4px 0 0;
  letter-spacing: -0.025em;
  color: var(--dashboard-ink, var(--mau-chu));
}

.btn-link-action {
  font-size: 0.85rem;
  font-weight: 650;
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  color: var(--mau-chinh, #f45b20);
  text-decoration: none;
  transition: var(--chuyen-canh-nhanh);
}

.btn-link-action:hover {
  color: var(--mau-chinh-hover, #ff6f38);
  text-decoration: underline;
}

/* Biểu đồ thuần CSS tương thích chuẩn charts.css trong Dark Mode */
.chart-container-box {
  background: var(--mau-the-sub, #0d0d0d);
  border: 1px solid var(--mau-vien, rgba(255, 255, 255, 0.08));
  border-radius: 12px;
  padding: 20px 20px 12px;
}

.charts-css {
  border-collapse: collapse;
  position: relative;
  display: block;
  margin: 0 auto;
}

.charts-css caption {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

.charts-css thead {
  display: none;
}

.charts-css.column tbody {
  display: flex;
  justify-content: space-around;
  align-items: flex-end;
  height: 135px;
  width: 100%;
  border-bottom: 2px solid rgba(255, 255, 255, 0.12);
  padding-bottom: 0;
}

.charts-css.column tr {
  display: flex;
  flex-direction: column-reverse;
  align-items: center;
  flex: 1;
  height: 100%;
  max-width: 90px;
  position: relative;
}

.charts-css.column th.chart-label {
  margin-top: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--mau-chu);
  text-align: center;
  white-space: nowrap;
}

.charts-css.column td.chart-bar {
  width: 44px;
  height: calc(100% * var(--size, 0));
  min-height: 6px;
  border-radius: 8px 8px 0 0;
  position: relative;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  transition: height 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
}

.charts-css.column td.chart-bar .data {
  position: absolute;
  top: -24px;
  font-size: 0.78rem;
  font-weight: 750;
  color: #ffffff;
  background: #1e1e1e;
  padding: 1px 6px;
  border-radius: 4px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
  white-space: nowrap;
}

.chart-bar-blue {
  background: linear-gradient(180deg, #3b82f6, #2563eb);
}

.chart-bar-amber {
  background: linear-gradient(180deg, #f59e0b, #d97706);
}

.chart-bar-purple {
  background: linear-gradient(180deg, #8b5cf6, #7c3aed);
}

/* Bars Breakdown */
.role-bars {
  display: grid;
  gap: 16px;
}

.role-row-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 6px;
  font-size: 0.88rem;
  color: var(--dashboard-ink, var(--mau-chu));
}

.role-legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.bar-track {
  height: 8px;
  background: var(--mau-vien, #202020);
  border-radius: 6px;
  overflow: hidden;
}

.bar-track span {
  height: 100%;
  display: block;
  border-radius: 6px;
}

.account-status {
  border-top: 1px solid var(--mau-vien);
  padding-top: 18px;
  margin-top: 24px;
  display: flex;
  flex-wrap: wrap;
  gap: 16px 24px;
  font-size: 0.85rem;
}

.status-badge-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Catalog Summary */
.catalog-summary {
  margin: 0;
  padding: 0;
  list-style: none;
}

.catalog-summary li + li {
  border-top: 1px solid var(--mau-vien);
}

.catalog-link-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 0;
  color: var(--dashboard-ink);
  text-decoration: none;
  transition: all 0.15s ease;
}

.summary-icon {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  background: rgba(244, 91, 32, 0.12);
  color: var(--mau-chinh);
  border-radius: 12px;
  font-size: 1.15rem;
  flex-shrink: 0;
  border: 1px solid rgba(244, 91, 32, 0.2);
}

.catalog-link-copy {
  flex-grow: 1;
  min-width: 0;
}

.catalog-link-copy strong {
  display: block;
  font-size: 0.95rem;
  color: var(--mau-chu);
}

.catalog-link-copy small {
  display: block;
  font-size: 0.78rem;
  color: var(--dashboard-muted);
  margin-top: 2px;
}

.catalog-metric-pill {
  display: flex;
  align-items: center;
  gap: 4px;
}

.catalog-metric-pill b {
  font-size: 1.1rem;
  font-weight: 750;
  color: var(--dashboard-ink, var(--mau-chu));
  font-variant-numeric: tabular-nums;
}

.catalog-link-item:hover {
  transform: translateX(4px);
}

.catalog-link-item:hover strong {
  color: var(--mau-chinh);
}

/* Personal Focus (Khách hàng & PT) - Cinematic Dark Card */
.personal-focus {
  background: linear-gradient(145deg, #1c1008 0%, #141414 70%);
  border: 1px solid rgba(244, 91, 32, 0.35);
  border-radius: 16px;
  padding: 32px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-width: 0;
  box-shadow:
    0 12px 36px rgba(0, 0, 0, 0.6),
    0 0 25px rgba(244, 91, 32, 0.1);
}

:root[data-theme='light'] .personal-focus,
.light-theme .personal-focus {
  background: linear-gradient(145deg, #fff7ed 0%, #ffffff 70%);
  border-color: rgba(244, 91, 32, 0.25);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
}

.focus-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.72rem;
  font-weight: 750;
  letter-spacing: 0.1em;
  color: var(--mau-chinh);
  background: rgba(244, 91, 32, 0.15);
  border: 1px solid rgba(244, 91, 32, 0.3);
  padding: 4px 10px;
  border-radius: 20px;
}

.personal-focus h2 {
  font-family: 'Outfit', 'Plus Jakarta Sans', system-ui, sans-serif;
  font-size: 1.85rem;
  letter-spacing: -0.04em;
  line-height: 1.25;
  margin: 18px 0 12px;
  max-width: 440px;
  color: var(--dashboard-ink, var(--mau-chu));
  font-weight: 800;
  overflow-wrap: anywhere;
}

.personal-focus p {
  color: var(--dashboard-muted, var(--mau-phu));
  line-height: 1.6;
  margin-bottom: 24px;
  max-width: 420px;
  font-size: 0.92rem;
}

.focus-footnote {
  border-top: 1px solid rgba(244, 91, 32, 0.2);
  margin-top: auto;
  padding-top: 20px;
  font-size: 0.82rem;
  color: var(--dashboard-muted, var(--mau-phu));
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
}

.personal-focus .btn + .focus-footnote {
  margin-top: 28px;
}

/* Profile Completion Ring */
.profile-ring-container {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 16px;
  background: var(--mau-the-sub, #0d0d0d);
  border: 1px solid var(--mau-vien, rgba(255, 255, 255, 0.08));
  border-radius: 14px;
}

.ring-wrapper {
  position: relative;
  width: 90px;
  height: 90px;
  flex-shrink: 0;
}

.progress-ring-svg {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.ring-bg {
  fill: none;
  stroke: var(--mau-the-hover, #222222);
  stroke-width: 8;
}

.ring-indicator {
  fill: none;
  stroke: var(--mau-chinh);
  stroke-width: 8;
  stroke-linecap: round;
  transition: stroke-dashoffset 0.8s ease;
}

.ring-center-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.ring-number {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--dashboard-ink, var(--mau-chu));
  line-height: 1;
}

.ring-label {
  font-size: 0.65rem;
  color: var(--dashboard-muted);
  text-transform: uppercase;
  margin-top: 2px;
}

.profile-percent {
  font-size: 1.8rem;
  color: var(--mau-chinh);
  letter-spacing: -0.04em;
  font-weight: 800;
}

.profile-caption {
  font-size: 0.88rem;
  color: var(--dashboard-muted, var(--mau-phu));
}

.profile-checklist {
  list-style: none;
  padding: 0;
  margin: 18px 0 0;
  display: grid;
  gap: 12px;
}

.profile-checklist li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.86rem;
  padding-bottom: 8px;
  border-bottom: 1px dashed var(--mau-vien, rgba(255, 255, 255, 0.08));
}

.profile-checklist li:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.profile-checklist i {
  color: #64748b;
  font-size: 1rem;
}

.profile-checklist .da-co {
  color: var(--mau-chinh);
}

.checklist-name {
  color: var(--mau-chu);
  font-weight: 550;
}

.profile-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--mau-chinh);
  font-size: 0.85rem;
  font-weight: 650;
  min-height: 40px;
  margin-top: 14px;
  text-decoration: none;
}

.profile-link:hover {
  color: var(--mau-chinh-hover);
  text-decoration: underline;
}

/* Quick Actions */
.quick-section {
  margin-top: 32px;
}

.quick-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.quick-link {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  border: 1px solid var(--mau-vien);
  border-radius: 14px;
  background: var(--mau-the, #141414);
  color: var(--dashboard-ink);
  text-decoration: none;
  min-height: 82px;
  transition: all 0.2s ease;
}

.quick-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(244, 91, 32, 0.12);
  color: var(--mau-chinh);
  display: grid;
  place-items: center;
  font-size: 1.25rem;
  flex-shrink: 0;
  border: 1px solid rgba(244, 91, 32, 0.2);
}

.quick-link-copy {
  flex-grow: 1;
  min-width: 0;
}

.quick-link-copy strong {
  display: block;
  font-size: 0.95rem;
  color: var(--mau-chu);
}

.quick-link-copy small {
  display: block;
  font-size: 0.78rem;
  margin-top: 3px;
  color: var(--dashboard-muted);
}

.quick-arrow-box {
  color: #64748b;
  font-size: 0.9rem;
  transition:
    transform 0.2s ease,
    color 0.2s ease;
}

.quick-link:hover {
  transform: translateY(-2px);
  border-color: var(--mau-chinh);
  background: var(--mau-the-hover, #1a1a1a);
  box-shadow: var(--bong-trung);
}

.quick-link:hover .quick-arrow-box {
  color: var(--mau-chinh);
  transform: translate(2px, -2px);
}

.quick-link:hover strong {
  color: var(--mau-chinh);
}

.updated-label {
  margin: 24px 0 0;
  font-size: 0.78rem;
  color: var(--dashboard-muted);
  text-align: right;
}

.spin-anim {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.btn-primary {
  background: var(--mau-gradient-chinh, linear-gradient(135deg, #f45b20 0%, #d94a15 100%));
  border-color: var(--mau-chinh);
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #ff6f38 0%, #f45b20 100%);
  border-color: #ff6f38;
}

/* Responsive */
@media (max-width: 1050px) {
  .metric-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 860px) {
  .dashboard-columns {
    grid-template-columns: minmax(0, 1fr);
  }
  .quick-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (max-width: 520px) {
  .metric-grid {
    grid-template-columns: 1fr;
  }
  .profile-ring-container {
    flex-direction: column;
    text-align: center;
  }
}
</style>
