<template>
  <CaNhanLayout>
    <!-- Tiêu đề trang quản trị -->
    <div class="page-heading mb-4">
      <div>
        <div class="eyebrow">
          <i class="bi bi-shield-lock-fill me-1" aria-hidden="true"></i>
          <span>TRUNG TÂM QUẢN TRỊ HỆ THỐNG</span>
        </div>
        <h1 class="h2 fw-bold mb-1">Quản lý tài khoản & Phân quyền</h1>
        <p class="text-muted small mb-0">
          Tra cứu, quản lý phân quyền và khởi tạo tài khoản Huấn luyện viên hoặc Quản trị viên mới.
        </p>
      </div>

      <div class="d-flex gap-2">
        <button
          ref="nutTao"
          type="button"
          class="btn btn-primary"
          @click="$refs.formTao.showModal()"
        >
          <i class="bi bi-person-plus" aria-hidden="true"></i> Tạo tài khoản
        </button>
        <button
          class="btn btn-outline-secondary btn-sm"
          :disabled="dangTai"
          title="Làm mới dữ liệu từ máy chủ"
          @click="taiDanhSach(phanTrang.current_page)"
        >
          <i
            class="bi bi-arrow-clockwise me-1"
            :class="{ 'spin-anim': dangTai }"
            aria-hidden="true"
          ></i>
          <span>Làm mới</span>
        </button>
      </div>
    </div>

    <!-- Hàng thống kê tổng quan -->
    <div class="stats-grid mb-4">
      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-emerald">
          <i class="bi bi-people-fill" aria-hidden="true"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ phanTrang.total || 0 }}</h3>
          <p>Tổng tài khoản hệ thống</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-amber">
          <i class="bi bi-award-fill" aria-hidden="true"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ soLuongPt }}</h3>
          <p>Huấn luyện viên (trang này)</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-blue">
          <i class="bi bi-person-fill" aria-hidden="true"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ soLuongKhachHang }}</h3>
          <p>Khách hàng (trang này)</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-purple">
          <i class="bi bi-shield-check" aria-hidden="true"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ soLuongAdmin }}</h3>
          <p>Quản trị viên (trang này)</p>
        </div>
      </div>
    </div>

    <!-- Thông báo kết quả tác vụ -->
    <div
      v-if="thongBao"
      class="alert d-flex align-items-center gap-2 p-3 mb-4 rounded-3"
      :class="coLoi ? 'alert-danger border-danger-subtle' : 'alert-success border-success-subtle'"
      role="status"
      aria-live="polite"
    >
      <i
        :class="
          coLoi
            ? 'bi bi-exclamation-triangle-fill text-danger'
            : 'bi bi-check-circle-fill text-success'
        "
        class="fs-5 flex-shrink-0"
        aria-hidden="true"
      ></i>
      <div class="small fw-semibold flex-grow-1">{{ thongBao }}</div>
      <button
        type="button"
        class="btn-close"
        aria-label="Đóng thông báo"
        @click="thongBao = ''"
      ></button>
    </div>

    <!-- Khung chính: Bảng danh sách + Form tạo tài khoản -->
    <div class="admin-grid">
      <!-- Cột trái: Danh sách tài khoản có tìm kiếm & phân trang -->
      <section class="profile-panel p-0 overflow-hidden shadow-sm" aria-labelledby="danh-sach">
        <div
          class="panel-table-header p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2"
        >
          <h2 id="danh-sach" class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-table text-emerald" aria-hidden="true"></i>
            <span>Danh sách tài khoản</span>
          </h2>
          <span class="badge bg-secondary-subtle text-body border small">
            Trang {{ phanTrang.current_page }} / {{ phanTrang.last_page }} ({{ phanTrang.total }}
            mục)
          </span>
        </div>

        <!-- Thanh tìm kiếm nhanh tại chỗ -->
        <div class="p-3 border-bottom bg-light-subtle d-flex gap-2">
          <div class="input-group-modern flex-grow-1">
            <span class="input-icon-prefix" aria-hidden="true">
              <i class="bi bi-search"></i>
            </span>
            <input
              v-model="tuKhoaTimKiem"
              type="search"
              class="form-control form-control-sm has-prefix"
              placeholder="Tìm theo tên hoặc email trên trang này…"
              aria-label="Tìm tài khoản trên trang"
            />
          </div>
          <button
            v-if="tuKhoaTimKiem"
            class="btn btn-outline-secondary btn-sm"
            type="button"
            title="Xóa tìm kiếm"
            @click="tuKhoaTimKiem = ''"
          >
            <i class="bi bi-x-lg" aria-hidden="true"></i>
          </button>
        </div>

        <!-- Trạng thái đang tải -->
        <div v-if="dangTai" class="p-5 text-center" role="status">
          <div class="spinner-border text-success mb-3" role="status"></div>
          <p class="text-muted mb-0 small">Đang tải danh sách tài khoản từ máy chủ…</p>
        </div>

        <!-- Trạng thái danh sách rỗng -->
        <div v-else-if="!danhSachHienThi.length" class="p-5 text-center text-muted">
          <div class="empty-icon-ring mx-auto mb-3">
            <i class="bi bi-inbox fs-2 text-muted" aria-hidden="true"></i>
          </div>
          <p class="mb-2 fw-semibold text-body">Không tìm thấy tài khoản nào phù hợp.</p>
          <button
            v-if="tuKhoaTimKiem"
            class="btn btn-outline-secondary btn-sm mt-1"
            @click="tuKhoaTimKiem = ''"
          >
            <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Xóa bộ lọc tìm kiếm
          </button>
        </div>

        <!-- Bảng hiển thị dữ liệu -->
        <div v-else class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">Tài khoản</th>
                <th scope="col">Vai trò</th>
                <th scope="col">Trạng thái</th>
                <th scope="col" class="text-end">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="taiKhoan in danhSachHienThi" :key="taiKhoan.id">
                <!-- Cột 1: Thông tin tài khoản -->
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="table-user-avatar" :class="layClassAvatar(taiKhoan.vai_tro)">
                      {{ taiKhoan.ho_ten ? taiKhoan.ho_ten.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div>
                      <strong class="d-block text-body">{{ taiKhoan.ho_ten }}</strong>
                      <span class="text-muted small d-flex align-items-center gap-1 font-monospace">
                        <i class="bi bi-envelope" aria-hidden="true"></i> {{ taiKhoan.email }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Cột 2: Vai trò -->
                <td>
                  <span class="badge-role" :class="layClassHuyHieu(taiKhoan.vai_tro)">
                    <i :class="layBieuTuongVaiTro(taiKhoan.vai_tro)" aria-hidden="true"></i>
                    <span>{{ nhanVaiTro(taiKhoan.vai_tro) }}</span>
                  </span>
                </td>

                <!-- Cột 3: Trạng thái -->
                <td>
                  <span
                    class="status-pill"
                    :class="{ 'status-locked': taiKhoan.trang_thai !== 'HOAT_DONG' }"
                  >
                    <span
                      class="status-dot"
                      :class="{ 'dot-locked': taiKhoan.trang_thai !== 'HOAT_DONG' }"
                    ></span>
                    <span>{{ taiKhoan.trang_thai === 'HOAT_DONG' ? 'Hoạt động' : 'Đã khóa' }}</span>
                  </span>
                </td>

                <!-- Cột 4: Thao tác khóa / mở khóa -->
                <td class="text-end">
                  <span
                    v-if="taiKhoan.id === xacThuc.taiKhoan?.id"
                    class="badge bg-light text-muted border small"
                  >
                    <i class="bi bi-person-check me-1" aria-hidden="true"></i>Tài khoản của bạn
                  </span>
                  <button
                    v-else
                    type="button"
                    class="btn btn-sm"
                    :class="
                      taiKhoan.trang_thai === 'HOAT_DONG'
                        ? 'btn-outline-danger'
                        : 'btn-outline-success'
                    "
                    :disabled="dangDoi || dangLuu"
                    :aria-label="
                      (taiKhoan.trang_thai === 'HOAT_DONG' ? 'Khóa ' : 'Mở khóa ') + taiKhoan.ho_ten
                    "
                    @click="moXacNhan(taiKhoan)"
                  >
                    <i
                      :class="
                        taiKhoan.trang_thai === 'HOAT_DONG'
                          ? 'bi bi-lock-fill me-1'
                          : 'bi bi-unlock-fill me-1'
                      "
                      aria-hidden="true"
                    ></i>
                    <span>{{ taiKhoan.trang_thai === 'HOAT_DONG' ? 'Khóa' : 'Mở khóa' }}</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Điều hướng phân trang -->
        <nav
          v-if="phanTrang.last_page > 1"
          class="pagination-controls p-3 border-top d-flex align-items-center justify-content-between"
          aria-label="Phân trang danh sách tài khoản"
        >
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="dangTai || phanTrang.current_page <= 1"
            @click="taiDanhSach(phanTrang.current_page - 1)"
          >
            <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>Trang trước
          </button>
          <span class="small fw-semibold text-muted">
            Trang {{ phanTrang.current_page }} / {{ phanTrang.last_page }}
          </span>
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="dangTai || phanTrang.current_page >= phanTrang.last_page"
            @click="taiDanhSach(phanTrang.current_page + 1)"
          >
            Trang sau<i class="bi bi-chevron-right ms-1" aria-hidden="true"></i>
          </button>
        </nav>

        <div v-if="loiTai" class="p-3 bg-danger-subtle text-danger text-center">
          <p class="small mb-2">Đã xảy ra lỗi khi tải dữ liệu từ máy chủ.</p>
          <button class="btn btn-danger btn-sm" @click="taiDanhSach(phanTrang.current_page)">
            Thử tải lại
          </button>
        </div>
      </section>

      <!-- Cột phải: Form tạo tài khoản HLV hoặc Admin mới -->
      <dialog
        ref="formTao"
        class="st-drawer"
        aria-labelledby="tao-tai-khoan"
        @cancel="huyTao"
        @close="$refs.nutTao.focus()"
      >
        <button
          type="button"
          class="st-drawer-close btn btn-outline-secondary"
          aria-label="Đóng form tạo tài khoản"
          :disabled="dangLuu"
          @click="$refs.formTao.close()"
        >
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
        <p v-if="thongBao && coLoi" class="alert alert-danger" role="alert">{{ thongBao }}</p>
        <section class="profile-panel shadow-sm" aria-labelledby="tao-tai-khoan">
          <div class="panel-heading mb-3">
            <div class="eyebrow mb-1">
              <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
              <span>CẤP TÀI KHOẢN MỚI</span>
            </div>
            <h2 id="tao-tai-khoan" class="h6 fw-bold mb-0 text-body">Tạo tài khoản phân quyền</h2>
          </div>

          <form @submit.prevent="taoTaiKhoan" :aria-busy="dangLuu" novalidate>
            <TruongNhap
              id="ho_ten_moi"
              v-model="duLieu.ho_ten"
              nhan="Họ và tên"
              goi-y-nhap="Ví dụ: Huấn Luyện Viên Trần Nam"
              tu-dong-dien="off"
              bieu-tuong="bi bi-person"
              :loi="loiTruong.ho_ten"
              :vo-hieu="dangLuu"
            />

            <TruongNhap
              id="email_moi"
              v-model="duLieu.email"
              nhan="Địa chỉ Email"
              loai="email"
              goi-y-nhap="pt.nam@gymfit.vn"
              bieu-tuong="bi bi-envelope"
              :toi-da="191"
              :loi="loiTruong.email"
              :vo-hieu="dangLuu"
            />

            <!-- Chọn vai trò dạng Thẻ trực quan -->
            <div class="mb-3">
              <label class="form-label fw-bold">
                Chọn vai trò cấp quyền <span class="text-danger">*</span>
              </label>
              <div class="role-picker-grid">
                <label
                  class="role-card-option"
                  :class="{ selected: duLieu.vai_tro === 'HUAN_LUYEN_VIEN' }"
                >
                  <input
                    v-model="duLieu.vai_tro"
                    type="radio"
                    value="HUAN_LUYEN_VIEN"
                    name="vai_tro_select"
                    class="d-none"
                    :disabled="dangLuu"
                  />
                  <div class="role-card-icon text-success">
                    <i class="bi bi-award-fill" aria-hidden="true"></i>
                  </div>
                  <div>
                    <strong class="d-block small">Huấn luyện viên</strong>
                    <small class="text-muted">Kèm cặp học viên & giáo án</small>
                  </div>
                </label>

                <label class="role-card-option" :class="{ selected: duLieu.vai_tro === 'ADMIN' }">
                  <input
                    v-model="duLieu.vai_tro"
                    type="radio"
                    value="ADMIN"
                    name="vai_tro_select"
                    class="d-none"
                    :disabled="dangLuu"
                  />
                  <div class="role-card-icon text-purple">
                    <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                  </div>
                  <div>
                    <strong class="d-block small">Quản trị viên</strong>
                    <small class="text-muted">Toàn quyền hệ thống</small>
                  </div>
                </label>
              </div>
            </div>

            <TruongNhap
              id="mat_khau_moi"
              v-model="duLieu.password"
              nhan="Mật khẩu khởi tạo"
              loai="password"
              goi-y-nhap="Ít nhất 8 ký tự"
              tu-dong-dien="new-password"
              bieu-tuong="bi bi-lock"
              :toi-thieu="8"
              :toi-da="72"
              :loi="loiTruong.password"
              :vo-hieu="dangLuu"
            />

            <TruongNhap
              id="mat_khau_xac_nhan"
              v-model="duLieu.password_confirmation"
              nhan="Nhập lại mật khẩu"
              loai="password"
              goi-y-nhap="Xác nhận lại mật khẩu"
              tu-dong-dien="new-password"
              bieu-tuong="bi bi-shield-check"
              :toi-thieu="8"
              :toi-da="72"
              :loi="loiTruong.password_confirmation"
              :vo-hieu="dangLuu"
            />

            <button class="btn btn-primary w-100 shadow-sm mt-2" :disabled="dangLuu" type="submit">
              <span
                v-if="dangLuu"
                class="spinner-border spinner-border-sm me-1"
                role="status"
              ></span>
              <i v-else class="bi bi-plus-circle me-1" aria-hidden="true"></i>
              <span>{{ dangLuu ? 'Đang tạo tài khoản…' : 'Xác nhận tạo tài khoản' }}</span>
            </button>
          </form>
        </section>
      </dialog>
    </div>

    <!-- Hộp thoại xác nhận Khóa / Mở khóa tài khoản & Thu hồi phiên -->
    <dialog
      ref="xacNhan"
      class="xac-nhan-tai-khoan shadow-lg"
      aria-labelledby="tieu-de-khoa"
      @cancel="huyXacNhan"
    >
      <template v-if="taiKhoanDoi">
        <div class="dialog-header d-flex align-items-center gap-3 mb-3">
          <div
            class="dialog-icon-wrapper"
            :class="
              taiKhoanDoi.trang_thai === 'HOAT_DONG'
                ? 'bg-danger-subtle text-danger'
                : 'bg-success-subtle text-success'
            "
          >
            <i
              :class="
                taiKhoanDoi.trang_thai === 'HOAT_DONG' ? 'bi bi-lock-fill' : 'bi bi-unlock-fill'
              "
              aria-hidden="true"
            ></i>
          </div>
          <div>
            <div class="eyebrow mb-1">QUẢN TRỊ TRUY CẬP & PHIÊN ĐĂNG NHẬP</div>
            <h2 id="tieu-de-khoa" class="h5 fw-bold mb-0">
              {{
                taiKhoanDoi.trang_thai === 'HOAT_DONG'
                  ? 'Khóa tài khoản và thu hồi phiên?'
                  : 'Mở khóa tài khoản?'
              }}
            </h2>
          </div>
        </div>

        <!-- Thông tin người dùng được chọn -->
        <div class="target-user-card p-3 rounded-3 mb-3 bg-body-secondary border">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <strong class="d-block text-body">{{ taiKhoanDoi.ho_ten }}</strong>
              <span class="text-muted small email-xac-nhan font-monospace">{{
                taiKhoanDoi.email
              }}</span>
            </div>
            <span class="badge-role" :class="layClassHuyHieu(taiKhoanDoi.vai_tro)">
              <i :class="layBieuTuongVaiTro(taiKhoanDoi.vai_tro)" aria-hidden="true"></i>
              <span>{{ nhanVaiTro(taiKhoanDoi.vai_tro) }}</span>
            </span>
          </div>
        </div>

        <!-- Hộp cảnh báo nghiệp vụ và thu hồi phiên -->
        <div
          class="alert p-3 mb-4 rounded-3 small"
          :class="
            taiKhoanDoi.trang_thai === 'HOAT_DONG'
              ? 'alert-warning border-warning-subtle'
              : 'alert-info border-info-subtle'
          "
        >
          <template v-if="taiKhoanDoi.trang_thai === 'HOAT_DONG'">
            <div class="fw-semibold mb-1">
              <i class="bi bi-exclamation-triangle-fill me-1 text-warning" aria-hidden="true"></i>
              Thu hồi phiên đăng nhập:
            </div>
            <p class="mb-2">
              Người dùng sẽ bị thu hồi phiên đăng nhập và không thể truy cập tài khoản. Hồ sơ và
              lịch sử vẫn được giữ nguyên.
            </p>
            <div class="text-muted small">
              <i class="bi bi-shield-check text-success me-1" aria-hidden="true"></i>
              Mọi gói tập, lịch sử tập và giáo án của người dùng vẫn được bảo toàn nguyên vẹn.
            </div>
          </template>
          <template v-else>
            <div class="fw-semibold mb-1">
              <i class="bi bi-info-circle-fill me-1 text-info" aria-hidden="true"></i>
              Khôi phục quyền truy cập:
            </div>
            <p class="mb-0">Người dùng có thể đăng nhập lại. Phiên cũ không được khôi phục.</p>
          </template>
        </div>

        <!-- Cụm nút xác nhận -->
        <div class="d-flex justify-content-end gap-2 flex-wrap">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            :disabled="dangDoi"
            @click="dongXacNhan"
          >
            Hủy
          </button>
          <button
            type="button"
            class="btn btn-sm"
            :class="taiKhoanDoi.trang_thai === 'HOAT_DONG' ? 'btn-danger' : 'btn-primary'"
            :disabled="dangDoi"
            @click="xacNhanTrangThai"
          >
            <span v-if="dangDoi" class="spinner-border spinner-border-sm me-1" role="status"></span>
            <i
              v-else
              :class="
                taiKhoanDoi.trang_thai === 'HOAT_DONG'
                  ? 'bi bi-lock-fill me-1'
                  : 'bi bi-unlock-fill me-1'
              "
              aria-hidden="true"
            ></i>
            <span>
              {{
                dangDoi
                  ? 'Đang xử lý…'
                  : taiKhoanDoi.trang_thai === 'HOAT_DONG'
                    ? 'Xác nhận khóa'
                    : 'Xác nhận mở khóa'
              }}
            </span>
          </button>
        </div>
      </template>
    </dialog>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import TruongNhap from '../../../components/TruongNhap.vue'
import taiKhoanService from '../../../services/taiKhoanService'
import { layLoiApi } from '../../../utils/loiApi'
import { useXacThucStore } from '../../../stores/xacThuc'

const bieuMauMoi = () => ({
  ho_ten: '',
  email: '',
  password: '',
  password_confirmation: '',
  vai_tro: 'HUAN_LUYEN_VIEN',
})

export default {
  name: 'TrangTaiKhoanAdmin',
  components: { CaNhanLayout, TruongNhap },
  data() {
    return {
      danhSach: [],
      phanTrang: { total: 0, current_page: 1, last_page: 1 },
      duLieu: bieuMauMoi(),
      tuKhoaTimKiem: '',
      dangTai: false,
      dangLuu: false,
      thongBao: '',
      coLoi: false,
      loiTruong: {},
      loiTai: false,
      dangDoi: false,
      taiKhoanDoi: null,
      daHuy: false,
      lanTai: 0,
      boHuy: null,
    }
  },
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
    danhSachHienThi() {
      if (!this.tuKhoaTimKiem.trim()) return this.danhSach
      const tuKhoa = this.tuKhoaTimKiem.toLowerCase().trim()
      return this.danhSach.filter(
        (tk) =>
          tk.ho_ten?.toLowerCase().includes(tuKhoa) || tk.email?.toLowerCase().includes(tuKhoa),
      )
    },
    soLuongPt() {
      return this.danhSach.filter((tk) => tk.vai_tro === 'HUAN_LUYEN_VIEN').length
    },
    soLuongKhachHang() {
      return this.danhSach.filter((tk) => tk.vai_tro === 'KHACH_HANG').length
    },
    soLuongAdmin() {
      return this.danhSach.filter((tk) => tk.vai_tro === 'ADMIN').length
    },
  },
  mounted() {
    this.taiDanhSach()
  },
  beforeUnmount() {
    this.daHuy = true
    this.lanTai++
    this.boHuy?.abort()
    this.$refs.xacNhan?.close()
  },
  methods: {
    huyTao(e) {
      if (this.dangLuu) e.preventDefault()
    },
    async moXacNhan(taiKhoan) {
      if (this.dangDoi || taiKhoan.id === this.xacThuc.taiKhoan?.id) return
      this.taiKhoanDoi = { ...taiKhoan }
      await this.$nextTick()
      if (this.daHuy || !this.taiKhoanDoi) return
      this.$refs.xacNhan.showModal()
    },
    dongXacNhan() {
      if (this.dangDoi) return
      this.$refs.xacNhan.close()
      this.taiKhoanDoi = null
    },
    huyXacNhan(suKien) {
      suKien.preventDefault()
      this.dongXacNhan()
    },
    async xacNhanTrangThai() {
      if (this.dangDoi || !this.taiKhoanDoi) return
      const taiKhoan = this.taiKhoanDoi
      this.dangDoi = true
      try {
        const phanHoi = await taiKhoanService.datTrangThai(taiKhoan.id, {
          trang_thai: taiKhoan.trang_thai === 'HOAT_DONG' ? 'BI_KHOA' : 'HOAT_DONG',
          updated_at: taiKhoan.updated_at ?? null,
        })
        if (this.daHuy) return
        this.thongBao = phanHoi.message
        this.coLoi = false
        this.danhSach = this.danhSach.map((hang) => (hang.id === taiKhoan.id ? phanHoi.data : hang))
      } catch (loi) {
        if (this.daHuy) return
        this.thongBao = layLoiApi(loi).thongBao
        this.coLoi = true
        if (loi.response?.status === 409) await this.taiDanhSach(this.phanTrang.current_page)
      } finally {
        if (!this.daHuy) {
          this.dangDoi = false
          this.dongXacNhan()
        }
      }
    },
    nhanVaiTro(vaiTro) {
      return (
        {
          KHACH_HANG: 'Khách hàng',
          HUAN_LUYEN_VIEN: 'Huấn luyện viên',
          ADMIN: 'Quản trị viên',
        }[vaiTro] || vaiTro
      )
    },
    layBieuTuongVaiTro(vaiTro) {
      return (
        {
          KHACH_HANG: 'bi bi-person-fill',
          HUAN_LUYEN_VIEN: 'bi bi-award-fill',
          ADMIN: 'bi bi-shield-check',
        }[vaiTro] || 'bi bi-person'
      )
    },
    layClassHuyHieu(vaiTro) {
      return (
        {
          ADMIN: 'badge-role-admin',
          HUAN_LUYEN_VIEN: 'badge-role-pt',
          KHACH_HANG: 'badge-role-khach',
        }[vaiTro] || ''
      )
    },
    layClassAvatar(vaiTro) {
      return (
        {
          ADMIN: 'avatar-purple',
          HUAN_LUYEN_VIEN: 'avatar-emerald',
          KHACH_HANG: 'avatar-blue',
        }[vaiTro] || 'avatar-gray'
      )
    },
    async taiDanhSach(page = 1) {
      const lan = ++this.lanTai
      this.boHuy?.abort()
      this.boHuy = new AbortController()
      this.dangTai = true
      this.loiTai = false
      try {
        const phanHoi = await taiKhoanService.taiDanhSach(page, this.boHuy.signal)
        if (this.daHuy || lan !== this.lanTai) return
        this.danhSach = phanHoi.data
        this.phanTrang = phanHoi.meta
      } catch (loi) {
        if (this.daHuy || lan !== this.lanTai || loi.code === 'ERR_CANCELED') return
        this.thongBao = layLoiApi(loi).thongBao
        this.coLoi = true
        this.loiTai = true
      } finally {
        if (!this.daHuy && lan === this.lanTai) this.dangTai = false
      }
    },
    async taoTaiKhoan() {
      if (this.dangLuu) return
      this.dangLuu = true
      this.loiTruong = {}
      this.thongBao = ''
      try {
        const phanHoi = await taiKhoanService.taoTaiKhoan(this.duLieu)
        if (this.daHuy) return
        this.thongBao = phanHoi.message || 'Tạo tài khoản thành công.'
        this.coLoi = false
        this.duLieu = bieuMauMoi()
        this.$refs.formTao?.close()
        await this.taiDanhSach()
      } catch (loi) {
        if (this.daHuy) return
        const phanHoi = layLoiApi(loi)
        this.thongBao = phanHoi.thongBao
        this.loiTruong = phanHoi.loiTruong
        this.coLoi = true
      } finally {
        if (!this.daHuy) this.dangLuu = false
      }
    },
  },
}
</script>

<style scoped>
.text-emerald {
  color: var(--mau-chinh);
}

.text-purple {
  color: var(--mau-thong-tin);
}

.admin-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(320px, 1fr);
  gap: 24px;
  align-items: start;
}

.panel-table-header {
  background: var(--mau-table-header-bg, var(--mau-the-sub));
}

.empty-icon-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--mau-the-hover, var(--mau-the-hover));
  display: flex;
  align-items: center;
  justify-content: center;
}

.table-user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 0.95rem;
  color: white;
  flex-shrink: 0;
}

.avatar-emerald {
  background: var(--mau-the-sub);
}
.avatar-blue {
  background: var(--mau-the-sub);
}
.avatar-purple {
  background: var(--mau-the-sub);
}
.avatar-gray {
  background: var(--mau-phu);
}

.role-picker-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.role-card-option {
  border: 1.5px solid var(--mau-vien);
  border-radius: 8px;
  padding: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.2s ease;
  user-select: none;
  background: var(--mau-the-sub, var(--mau-the-sub));
}

.role-card-option:hover {
  border-color: color-mix(in srgb, var(--mau-chinh) 40%, transparent);
  background: var(--mau-the-hover, var(--mau-the-hover));
}

.role-card-option.selected {
  border-color: var(--mau-chinh);
  background: color-mix(in srgb, var(--mau-chinh) 15%, transparent);
  box-shadow: var(--bong-nhe);
}

.role-card-icon {
  font-size: 1.4rem;
}

.btn-primary {
  background: var(--mau-chinh);
  border-color: var(--mau-chinh);
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: var(--mau-chinh-dam);
  border-color: var(--mau-chinh-dam);
}

.btn {
  min-height: 40px;
  border-radius: 8px;
  font-weight: 600;
}

/* Modal xác nhận */
.xac-nhan-tai-khoan {
  width: min(520px, calc(100% - 32px));
  max-height: calc(100dvh - 32px);
  overflow-y: auto;
  border: 1px solid var(--mau-vien);
  border-radius: 8px;
  padding: 28px;
  color: var(--mau-chu);
  background: var(--mau-the);
}

.xac-nhan-tai-khoan::backdrop {
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
}

.dialog-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  flex-shrink: 0;
}

.email-xac-nhan {
  overflow-wrap: anywhere;
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

@media (max-width: 992px) {
  .admin-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .role-picker-grid {
    grid-template-columns: 1fr;
  }
}
</style>
