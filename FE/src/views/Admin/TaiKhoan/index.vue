<template>
  <CaNhanLayout>
    <!-- Tiêu đề trang quản trị -->
    <div class="page-heading mb-4">
      <div>
        <div class="eyebrow">
          <i class="bi bi-shield-lock-fill"></i>
          <span>TRUNG TÂM QUẢN TRỊ HỆ THỐNG</span>
        </div>
        <h1 class="h2 fw-bold mb-1">Quản lý tài khoản</h1>
        <p class="text-muted small mb-0">
          Tra cứu, quản lý phân quyền và tạo mới tài khoản Huấn luyện viên hoặc Quản trị viên.
        </p>
      </div>

      <div class="d-flex gap-2">
        <button
          class="btn btn-outline-secondary btn-sm"
          :disabled="dangTai"
          @click="taiDanhSach(phanTrang.current_page)"
          title="Làm mới dữ liệu"
        >
          <i class="bi bi-arrow-clockwise" :class="{ 'spin-anim': dangTai }"></i>
          <span>Làm mới</span>
        </button>
      </div>
    </div>

    <!-- Hàng thống kê nhanh Admin -->
    <div class="stats-grid mb-4">
      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-emerald">
          <i class="bi bi-people-fill"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ phanTrang.total || 0 }}</h3>
          <p>Tổng tài khoản hệ thống</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-amber">
          <i class="bi bi-award-fill"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ soLuongPt }}</h3>
          <p>Huấn luyện viên</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-blue">
          <i class="bi bi-person-fill"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ soLuongKhachHang }}</h3>
          <p>Khách hàng thành viên</p>
        </div>
      </div>

      <div class="stat-item-card">
        <div class="stat-icon-wrapper stat-icon-purple">
          <i class="bi bi-shield-check"></i>
        </div>
        <div class="stat-info-content">
          <h3>{{ soLuongAdmin }}</h3>
          <p>Quản trị viên</p>
        </div>
      </div>
    </div>

    <!-- Thông báo kết quả tác vụ -->
    <div
      v-if="thongBao"
      class="alert d-flex align-items-center gap-2 p-3 mb-4 rounded-3 animate__animated animate__fadeIn"
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
      ></i>
      <div class="small fw-semibold flex-grow-1">{{ thongBao }}</div>
      <button type="button" class="btn-close" @click="thongBao = ''" aria-label="Đóng"></button>
    </div>

    <!-- Khung chính: Bảng danh sách + Form tạo tài khoản -->
    <div class="admin-grid">
      <!-- Cột trái: Danh sách tài khoản có tìm kiếm & lọc -->
      <section class="profile-panel p-0 overflow-hidden" aria-labelledby="danh-sach">
        <div
          class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between flex-wrap gap-2"
        >
          <h2 id="danh-sach" class="h5 mb-0 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-table text-primary"></i>
            <span>Danh sách tài khoản</span>
          </h2>
          <span class="badge bg-secondary-subtle text-dark border">
            Trang {{ phanTrang.current_page }} / {{ phanTrang.last_page }} ({{ phanTrang.total }}
            mục)
          </span>
        </div>

        <!-- Thanh tìm kiếm nhanh tại chỗ -->
        <div class="p-3 border-bottom bg-white d-flex gap-2">
          <div class="input-group-modern flex-grow-1">
            <span class="input-icon-prefix"><i class="bi bi-search"></i></span>
            <input
              v-model="tuKhoaTimKiem"
              type="search"
              class="form-control form-control-sm has-prefix"
              placeholder="Tìm theo tên hoặc email trên trang này…"
            />
          </div>
        </div>

        <!-- Trạng thái đang tải -->
        <div v-if="dangTai" class="p-5 text-center" role="status">
          <div class="spinner-border text-primary mb-3" role="status"></div>
          <p class="text-muted mb-0 small">Đang tải danh sách tài khoản từ máy chủ…</p>
        </div>

        <!-- Trạng thái danh sách rỗng -->
        <div v-else-if="!danhSachHienThi.length" class="p-5 text-center text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
          <p class="mb-2 fw-semibold">Không tìm thấy tài khoản nào phù hợp.</p>
          <button
            v-if="tuKhoaTimKiem"
            class="btn btn-outline-secondary btn-sm"
            @click="tuKhoaTimKiem = ''"
          >
            Xóa bộ lọc tìm kiếm
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
              </tr>
            </thead>
            <tbody>
              <tr v-for="taiKhoan in danhSachHienThi" :key="taiKhoan.id">
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="table-user-avatar" :class="layClassAvatar(taiKhoan.vai_tro)">
                      {{ taiKhoan.ho_ten ? taiKhoan.ho_ten.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div>
                      <strong class="d-block text-dark">{{ taiKhoan.ho_ten }}</strong>
                      <span class="text-muted small d-flex align-items-center gap-1">
                        <i class="bi bi-envelope"></i> {{ taiKhoan.email }}
                      </span>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge-role" :class="layClassHuyHieu(taiKhoan.vai_tro)">
                    <i :class="layBieuTuongVaiTro(taiKhoan.vai_tro)"></i>
                    <span>{{ nhanVaiTro(taiKhoan.vai_tro) }}</span>
                  </span>
                </td>
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
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Điều hướng phân trang -->
        <nav
          v-if="phanTrang.last_page > 1"
          class="pagination-controls"
          aria-label="Phân trang danh sách tài khoản"
        >
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="dangTai || phanTrang.current_page <= 1"
            @click="taiDanhSach(phanTrang.current_page - 1)"
          >
            <i class="bi bi-chevron-left me-1"></i>Trang trước
          </button>
          <span class="small fw-semibold text-muted">
            Trang {{ phanTrang.current_page }} / {{ phanTrang.last_page }}
          </span>
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="dangTai || phanTrang.current_page >= phanTrang.last_page"
            @click="taiDanhSach(phanTrang.current_page + 1)"
          >
            Trang sau<i class="bi bi-chevron-right ms-1"></i>
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
      <section class="profile-panel" aria-labelledby="tao-tai-khoan">
        <div class="panel-heading">
          <h2 id="tao-tai-khoan" class="h5 fw-bold mb-0">
            <i class="bi bi-person-plus-fill text-success"></i>
            <span>Tạo tài khoản phân quyền</span>
          </h2>
        </div>

        <form @submit.prevent="taoTaiKhoan" :aria-busy="dangLuu" novalidate>
          <TruongNhap
            v-model="duLieu.ho_ten"
            id="ho_ten_moi"
            nhan="Họ và tên"
            goi-y-nhap="Ví dụ: Huấn Luyện Viên Trần Nam"
            tu-dong-dien="off"
            bieu-tuong="bi bi-person"
            :loi="loiTruong.ho_ten"
            :vo-hieu="dangLuu"
          />

          <TruongNhap
            v-model="duLieu.email"
            id="email_moi"
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
            <label class="form-label"
              >Chọn vai trò cấp quyền <span class="text-danger">*</span></label
            >
            <div class="role-picker-grid">
              <label
                class="role-card-option"
                :class="{ selected: duLieu.vai_tro === 'HUAN_LUYEN_VIEN' }"
              >
                <input
                  type="radio"
                  v-model="duLieu.vai_tro"
                  value="HUAN_LUYEN_VIEN"
                  name="vai_tro_select"
                  class="d-none"
                  :disabled="dangLuu"
                />
                <div class="role-card-icon text-success">
                  <i class="bi bi-award-fill"></i>
                </div>
                <div>
                  <strong class="d-block small">Huấn luyện viên</strong>
                  <small class="text-muted">Kèm cặp học viên & giáo án</small>
                </div>
              </label>

              <label class="role-card-option" :class="{ selected: duLieu.vai_tro === 'ADMIN' }">
                <input
                  type="radio"
                  v-model="duLieu.vai_tro"
                  value="ADMIN"
                  name="vai_tro_select"
                  class="d-none"
                  :disabled="dangLuu"
                />
                <div class="role-card-icon text-purple">
                  <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                  <strong class="d-block small">Quản trị viên</strong>
                  <small class="text-muted">Toàn quyền hệ thống</small>
                </div>
              </label>
            </div>
          </div>

          <TruongNhap
            v-model="duLieu.password"
            id="mat_khau_moi"
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
            v-model="duLieu.password_confirmation"
            id="mat_khau_xac_nhan"
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

          <button class="btn btn-primary w-100 shadow-sm" :disabled="dangLuu" type="submit">
            <span v-if="dangLuu" class="spinner-border spinner-border-sm me-1" role="status"></span>
            <span>{{ dangLuu ? 'Đang tạo tài khoản…' : 'Xác nhận tạo tài khoản' }}</span>
            <i v-if="!dangLuu" class="bi bi-plus-circle ms-1"></i>
          </button>
        </form>
      </section>
    </div>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import TruongNhap from '../../../components/TruongNhap.vue'
import taiKhoanService from '../../../services/taiKhoanService'
import { layLoiApi } from '../../../utils/loiApi'

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
    }
  },
  computed: {
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
  methods: {
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
      if (this.dangTai) return
      this.dangTai = true
      this.loiTai = false
      try {
        const phanHoi = await taiKhoanService.taiDanhSach(page)
        this.danhSach = phanHoi.data
        this.phanTrang = phanHoi.meta
      } catch (loi) {
        this.thongBao = layLoiApi(loi).thongBao
        this.coLoi = true
        this.loiTai = true
      } finally {
        this.dangTai = false
      }
    },
    async taoTaiKhoan() {
      if (this.dangLuu) return
      this.dangLuu = true
      this.loiTruong = {}
      this.thongBao = ''
      try {
        const phanHoi = await taiKhoanService.taoTaiKhoan(this.duLieu)
        this.thongBao = phanHoi.message || 'Tạo tài khoản thành công.'
        this.coLoi = false
        this.duLieu = bieuMauMoi()
        await this.taiDanhSach()
      } catch (loi) {
        const phanHoi = layLoiApi(loi)
        this.thongBao = phanHoi.thongBao
        this.loiTruong = phanHoi.loiTruong
        this.coLoi = true
      } finally {
        this.dangLuu = false
      }
    },
  },
}
</script>

<style scoped>
.table-user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 0.95rem;
  color: white;
  flex-shrink: 0;
}

.avatar-emerald {
  background: linear-gradient(135deg, #059669, #10b981);
}
.avatar-blue {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
}
.avatar-purple {
  background: linear-gradient(135deg, #7c3aed, #8b5cf6);
}
.avatar-gray {
  background: #64748b;
}

.role-picker-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.role-card-option {
  border: 1.5px solid var(--mau-vien);
  border-radius: 12px;
  padding: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.2s ease;
  user-select: none;
}

.role-card-option:hover {
  border-color: #94a3b8;
  background: #f8fafc;
}

.role-card-option.selected {
  border-color: var(--mau-chinh);
  background: #ecfdf5;
  box-shadow: 0 0 0 1px var(--mau-chinh);
}

.role-card-icon {
  font-size: 1.4rem;
}

.text-purple {
  color: #7c3aed;
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
</style>
