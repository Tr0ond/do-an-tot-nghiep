<template>
  <CaNhanLayout>
    <!-- Banner hồ sơ cá nhân hiện đại với hiệu ứng gradient -->
    <div class="profile-banner-card mb-4" :class="laPt ? 'banner-pt' : 'banner-khach'">
      <div class="banner-content">
        <div class="banner-avatar-wrap">
          <div class="banner-avatar" :class="laPt ? 'avatar-pt' : 'avatar-khach'">
            {{ chuCaiDau }}
          </div>
          <div
            class="avatar-badge-sub"
            :title="laPt ? 'Huấn luyện viên chuẩn' : 'Hội viên chính thức'"
          >
            <i
              :class="
                laPt
                  ? 'bi bi-patch-check-fill text-warning'
                  : 'bi bi-check-circle-fill text-success'
              "
            ></i>
          </div>
        </div>

        <div class="banner-info flex-grow-1">
          <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h1 class="h2 mb-0 fw-bold text-white">{{ xacThuc.taiKhoan?.ho_ten }}</h1>
            <span class="status-pill status-pill-light">
              <span class="status-dot"></span>
              Đang hoạt động
            </span>
          </div>

          <p class="text-white-50 mb-2 small d-flex align-items-center gap-2 flex-wrap">
            <span><i class="bi bi-envelope me-1"></i>{{ xacThuc.taiKhoan?.email }}</span>
            <span>•</span>
            <span class="badge" :class="laPt ? 'bg-warning text-dark' : 'bg-info text-dark'">
              <i :class="laPt ? 'bi bi-award-fill' : 'bi bi-person-heart'"></i>
              {{ laPt ? 'HUẤN LUYỆN VIÊN CÁ NHÂN' : 'HỌC VIÊN CÁ NHÂN' }}
            </span>
          </p>
        </div>

        <div class="banner-quick-actions">
          <button class="btn btn-light btn-sm shadow-sm" @click="thongBaoDangPhatTrien">
            <i class="bi bi-pencil-square"></i>
            <span>Cập nhật hồ sơ</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Thông báo tác vụ nếu có -->
    <div
      v-if="thongBao"
      class="alert alert-info alert-dismissible fade show rounded-3"
      role="alert"
    >
      <i class="bi bi-info-circle-fill me-2"></i>{{ thongBao }}
      <button type="button" class="btn-close" @click="thongBao = ''" aria-label="Đóng"></button>
    </div>

    <!-- Hàng thống kê tổng quan (Metrics Cards) -->
    <div class="stats-grid mb-4">
      <template v-if="!laPt">
        <!-- Dành cho Khách Hàng -->
        <div class="stat-item-card">
          <div class="stat-icon-wrapper stat-icon-emerald">
            <i class="bi bi-bullseye"></i>
          </div>
          <div class="stat-info-content">
            <h3>{{ hoSo?.muc_tieu || 'Tăng cơ & Giảm mỡ' }}</h3>
            <p>Mục tiêu tập luyện</p>
          </div>
        </div>

        <div class="stat-item-card">
          <div class="stat-icon-wrapper stat-icon-amber">
            <i class="bi bi-speedometer2"></i>
          </div>
          <div class="stat-info-content">
            <h3>{{ hoSo?.kinh_nghiem || 'Mới bắt đầu' }}</h3>
            <p>Kinh nghiệm thể hình</p>
          </div>
        </div>

        <div class="stat-item-card">
          <div class="stat-icon-wrapper stat-icon-blue">
            <i class="bi bi-calendar3"></i>
          </div>
          <div class="stat-info-content">
            <h3>{{ ngaySinhDinhDang }}</h3>
            <p>Ngày sinh của bạn</p>
          </div>
        </div>

        <div class="stat-item-card">
          <div class="stat-icon-wrapper stat-icon-purple">
            <i class="bi bi-trophy-fill"></i>
          </div>
          <div class="stat-info-content">
            <h3>Gói 1-kèm-1</h3>
            <p>Chương trình đang tham gia</p>
          </div>
        </div>
      </template>

      <template v-else>
        <!-- Dành cho Huấn Luyện Viên -->
        <div class="stat-item-card">
          <div class="stat-icon-wrapper stat-icon-amber">
            <i class="bi bi-award-fill"></i>
          </div>
          <div class="stat-info-content">
            <h3>{{ hoSo?.chuyen_mon || 'Thể hình chuyên sâu' }}</h3>
            <p>Lĩnh vực chuyên môn</p>
          </div>
        </div>

        <div class="stat-item-card">
          <div class="stat-icon-wrapper stat-icon-emerald">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="stat-info-content">
            <h3>Sẵn sàng</h3>
            <p>Tiếp nhận học viên mới</p>
          </div>
        </div>

        <div class="stat-item-card">
          <div class="stat-icon-wrapper stat-icon-blue">
            <i class="bi bi-star-fill text-warning"></i>
          </div>
          <div class="stat-info-content">
            <h3>5.0 / 5.0</h3>
            <p>Đánh giá chất lượng PT</p>
          </div>
        </div>

        <div class="stat-item-card">
          <div class="stat-icon-wrapper stat-icon-purple">
            <i class="bi bi-shield-check"></i>
          </div>
          <div class="stat-info-content">
            <h3>Đã xác thực</h3>
            <p>Chứng chỉ huấn luyện</p>
          </div>
        </div>
      </template>
    </div>

    <!-- Chi tiết thông tin được phân nhóm rõ ràng -->
    <div class="row g-4">
      <!-- Cột 1: Thông tin cơ bản & Định danh -->
      <div class="col-lg-6">
        <section class="profile-panel h-100" aria-labelledby="tieu-de-thong-tin">
          <div class="panel-heading">
            <h2 id="tieu-de-thong-tin">
              <i class="bi bi-person-vcard text-success"></i>
              <span>Thông tin định danh</span>
            </h2>
            <span class="badge bg-light text-muted border">ID: #{{ xacThuc.taiKhoan?.id }}</span>
          </div>

          <dl class="profile-grid">
            <div class="profile-field-box">
              <dt><i class="bi bi-person"></i> Họ và tên</dt>
              <dd>{{ xacThuc.taiKhoan?.ho_ten }}</dd>
            </div>

            <div class="profile-field-box">
              <dt><i class="bi bi-envelope"></i> Email liên hệ</dt>
              <dd>{{ xacThuc.taiKhoan?.email }}</dd>
            </div>

            <div class="profile-field-box">
              <dt><i class="bi bi-shield-lock"></i> Vai trò tài khoản</dt>
              <dd>
                <span class="badge-role" :class="laPt ? 'badge-role-pt' : 'badge-role-khach'">
                  {{ laPt ? 'Huấn luyện viên' : 'Khách hàng' }}
                </span>
              </dd>
            </div>

            <div class="profile-field-box">
              <dt><i class="bi bi-clock-history"></i> Trạng thái</dt>
              <dd class="text-success fw-bold">
                <i class="bi bi-check-circle me-1"></i>Hoạt động bình thường
              </dd>
            </div>
          </dl>
        </section>
      </div>

      <!-- Cột 2: Hồ sơ chi tiết (Mục tiêu học viên hoặc Chuyên môn HLV) -->
      <div class="col-lg-6">
        <section class="profile-panel h-100" aria-labelledby="tieu-de-chuyen-sau">
          <div class="panel-heading">
            <h2 id="tieu-de-chuyen-sau">
              <i
                :class="
                  laPt
                    ? 'bi bi-mortarboard-fill text-warning'
                    : 'bi bi-heart-pulse-fill text-danger'
                "
              ></i>
              <span>{{ laPt ? 'Hồ sơ chuyên môn HLV' : 'Hồ sơ tập luyện học viên' }}</span>
            </h2>
            <span class="badge bg-success-subtle text-success">Chi tiết</span>
          </div>

          <!-- Nội dung cho Khách Hàng -->
          <div v-if="!laPt">
            <div class="profile-field-box mb-3">
              <dt><i class="bi bi-flag-fill"></i> Mục tiêu tập luyện cốt lõi</dt>
              <dd class="text-success">{{ hoSo?.muc_tieu || 'Chưa cung cấp mục tiêu' }}</dd>
            </div>

            <div class="row g-3">
              <div class="col-sm-6">
                <div class="profile-field-box">
                  <dt><i class="bi bi-bar-chart-steps"></i> Mức độ kinh nghiệm</dt>
                  <dd>{{ hoSo?.kinh_nghiem || 'Chưa cung cấp' }}</dd>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="profile-field-box">
                  <dt><i class="bi bi-calendar-event"></i> Ngày sinh</dt>
                  <dd>{{ ngaySinhDinhDang }}</dd>
                </div>
              </div>
            </div>

            <div class="mt-3 p-3 bg-light rounded-3 border">
              <div class="small fw-semibold text-muted mb-1">
                <i class="bi bi-lightbulb-fill text-warning me-1"></i>Lời khuyên huấn luyện:
              </div>
              <p class="small mb-0 text-muted">
                Hãy duy trì lịch tập đều đặn và thường xuyên ghi nhận nhật ký dinh dưỡng để đạt hiệu
                quả tối ưu.
              </p>
            </div>
          </div>

          <!-- Nội dung cho Huấn Luyện Viên -->
          <div v-else>
            <div class="profile-field-box mb-3">
              <dt><i class="bi bi-star-fill text-warning"></i> Chuyên môn thế mạnh</dt>
              <dd class="text-primary">{{ hoSo?.chuyen_mon || 'Chưa cung cấp chuyên môn' }}</dd>
            </div>

            <div class="p-3 bg-light rounded-3 border mb-3">
              <div class="small fw-semibold text-muted mb-1">
                <i class="bi bi-check2-all text-success me-1"></i>Tiêu chuẩn huấn luyện viên:
              </div>
              <p class="small mb-0 text-muted">
                Huấn luyện viên cam kết đảm bảo an toàn động tác, lên giáo án chuẩn xác và theo dõi
                sát sao chỉ số thể chất của từng học viên.
              </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
              <span class="badge bg-secondary-subtle text-dark border">
                <i class="bi bi-shield-check text-success me-1"></i>Chứng chỉ NASM Certified
              </span>
              <span class="badge bg-secondary-subtle text-dark border">
                <i class="bi bi-heart-pulse text-danger me-1"></i>Sơ cấp cứu CPR/AED
              </span>
            </div>
          </div>
        </section>
      </div>
    </div>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../layouts/CaNhanLayout.vue'
import { useXacThucStore } from '../../stores/xacThuc'

export default {
  name: 'TrangHoSoCaNhan',
  components: { CaNhanLayout },
  data() {
    return {
      thongBao: '',
    }
  },
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
    laPt() {
      return this.xacThuc.taiKhoan?.vai_tro === 'HUAN_LUYEN_VIEN'
    },
    hoSo() {
      return this.xacThuc.taiKhoan?.[this.laPt ? 'ho_so_huan_luyen_vien' : 'ho_so_khach_hang']
    },
    chuCaiDau() {
      const ten = this.xacThuc.taiKhoan?.ho_ten || 'U'
      return ten.trim().charAt(0).toUpperCase()
    },
    ngaySinhDinhDang() {
      const ngay = this.hoSo?.ngay_sinh
      if (!ngay) return 'Chưa cung cấp'
      try {
        return ngay.split('T')[0].split('-').reverse().join('/')
      } catch {
        return ngay
      }
    },
  },
  methods: {
    thongBaoDangPhatTrien() {
      this.thongBao = 'Tính năng chỉnh sửa hồ sơ trực tuyến đang được nâng cấp và sẽ sớm ra mắt.'
    },
  },
}
</script>

<style scoped>
.profile-banner-card {
  border-radius: 20px;
  padding: 32px 36px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
}

.banner-khach {
  background: linear-gradient(135deg, #065f46 0%, #047857 50%, #0d9488 100%);
}

.banner-pt {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 50%, #064e3b 100%);
}

.banner-content {
  display: flex;
  align-items: center;
  gap: 24px;
  flex-wrap: wrap;
  position: relative;
  z-index: 2;
}

.banner-avatar-wrap {
  position: relative;
}

.banner-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 2.2rem;
  font-weight: 800;
  color: white;
  border: 4px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.avatar-khach {
  background: linear-gradient(135deg, #10b981, #06b6d4);
}

.avatar-pt {
  background: linear-gradient(135deg, #f59e0b, #ef4444);
}

.avatar-badge-sub {
  position: absolute;
  bottom: 0;
  right: 0;
  background: white;
  border-radius: 50%;
  width: 26px;
  height: 26px;
  display: grid;
  place-items: center;
  font-size: 1rem;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}

.status-pill-light {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border-color: rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(8px);
}

@media (max-width: 640px) {
  .profile-banner-card {
    padding: 24px 20px;
  }
  .banner-content {
    flex-direction: column;
    text-align: center;
  }
  .banner-info {
    text-align: center;
  }
  .banner-info .d-flex {
    justify-content: center;
  }
}
</style>
