<template>
  <CaNhanLayout>
    <header class="st-page-heading">
      <div>
        <span class="st-kicker">TÀI KHOẢN CỦA BẠN</span>
        <h1>Hồ sơ của tôi</h1>
        <p>
          {{
            laKhach
              ? 'Thông tin cá nhân và hồ sơ tập luyện.'
              : laPt
                ? 'Thông tin cá nhân và hồ sơ chuyên môn.'
                : 'Thông tin tài khoản quản trị của bạn.'
          }}
        </p>
      </div>
      <RouterLink :to="duongDanHoSo + '/sua'" class="btn btn-primary"
        ><i class="bi bi-pencil-square" aria-hidden="true"></i> Cập nhật hồ sơ</RouterLink
      >
    </header>
    <section class="st-identity-band" aria-label="Thông tin tài khoản">
      <span class="st-avatar st-avatar-large" aria-hidden="true">{{ chuCaiDau }}</span>
      <div>
        <h2>{{ xacThuc.taiKhoan?.ho_ten }}</h2>
        <p>{{ nhanVaiTro }} · #{{ xacThuc.taiKhoan?.id }}</p>
      </div>
      <span class="st-status"><i class="bi bi-check-circle" aria-hidden="true"></i> Hoạt động</span>
    </section>
    <div class="st-profile-grid">
      <section class="st-section" aria-labelledby="thong-tin">
        <h2 id="thong-tin">
          <i class="bi bi-person-vcard" aria-hidden="true"></i> Thông tin liên hệ & cơ bản
        </h2>
        <dl class="st-fact-list">
          <div>
            <dt>Họ và tên</dt>
            <dd>{{ xacThuc.taiKhoan?.ho_ten }}</dd>
          </div>
          <div>
            <dt>Email</dt>
            <dd>{{ xacThuc.taiKhoan?.email }}</dd>
          </div>
          <div>
            <dt>Vai trò</dt>
            <dd>{{ nhanVaiTro }}</dd>
          </div>
          <template v-if="laKhach"
            ><div>
              <dt>Ngày sinh</dt>
              <dd>{{ ngaySinhDinhDang }}</dd>
            </div>
            <div>
              <dt>Giới tính</dt>
              <dd>{{ nhanGioiTinh }}</dd>
            </div></template
          >
        </dl>
      </section>
      <section v-if="laKhach || laPt" class="st-section" aria-labelledby="ho-so">
        <h2 id="ho-so">
          <i class="bi bi-bullseye" aria-hidden="true"></i>
          {{ laPt ? 'Hồ sơ chuyên môn' : 'Thông tin tập luyện & mục tiêu' }}
        </h2>
        <dl class="st-fact-list">
          <template v-if="laKhach"
            ><div>
              <dt>Mục tiêu tập luyện</dt>
              <dd>{{ hoSo?.muc_tieu || 'Chưa cung cấp' }}</dd>
            </div>
            <div>
              <dt>Kinh nghiệm</dt>
              <dd>{{ hoSo?.kinh_nghiem || 'Chưa cung cấp' }}</dd>
            </div>
            <div>
              <dt>Thời gian có thể tập</dt>
              <dd>
                <ul v-if="thoiGianTap.length">
                  <li v-for="(gio, i) in thoiGianTap" :key="i">{{ gio }}</li>
                </ul>
                <span v-else>Chưa cung cấp</span>
              </dd>
            </div></template
          ><template v-else
            ><div>
              <dt>Chuyên môn</dt>
              <dd>{{ hoSo?.chuyen_mon || 'Chưa cung cấp' }}</dd>
            </div>
            <div>
              <dt>Giới thiệu</dt>
              <dd class="gioi-thieu">{{ hoSo?.gioi_thieu || 'Chưa cung cấp' }}</dd>
            </div></template
          >
        </dl>
        <RouterLink :to="duongDanHoSo + '/sua'" class="st-text-link"
          >Bổ sung thông tin hồ sơ <i class="bi bi-arrow-up-right" aria-hidden="true"></i
        ></RouterLink>
      </section>
      <section v-if="laKhach" class="st-section">
        <h2><i class="bi bi-activity" aria-hidden="true"></i> Hành trình tập luyện</h2>
        <RouterLink to="/khach-hang/chi-so-co-the" class="st-link-row"
          ><span>Chỉ số cơ thể</span><i class="bi bi-arrow-right" aria-hidden="true"></i
        ></RouterLink>
        <RouterLink to="/khach-hang/ke-hoach" class="st-link-row"
          ><span>Giáo án của tôi</span><i class="bi bi-arrow-right" aria-hidden="true"></i
        ></RouterLink>
        <RouterLink to="/khach-hang/goi-cua-toi" class="st-link-row"
          ><span>Gói & huấn luyện viên</span><i class="bi bi-arrow-right" aria-hidden="true"></i
        ></RouterLink>
      </section>
      <section class="st-section">
        <h2><i class="bi bi-shield-check" aria-hidden="true"></i> Tài khoản & truy cập</h2>
        <dl class="st-fact-list">
          <div>
            <dt>Trạng thái</dt>
            <dd>Đang hoạt động</dd>
          </div>
          <div>
            <dt>Email đăng nhập</dt>
            <dd>{{ xacThuc.taiKhoan?.email }}</dd>
          </div>
        </dl>
        <RouterLink to="/quen-mat-khau" class="st-text-link"
          >Khôi phục mật khẩu <i class="bi bi-arrow-up-right" aria-hidden="true"></i
        ></RouterLink>
      </section>
    </div>
  </CaNhanLayout>
</template>
<script>
import CaNhanLayout from '../../layouts/CaNhanLayout.vue'
import { useXacThucStore } from '../../stores/xacThuc'
export default {
  name: 'TrangHoSoCaNhan',
  components: { CaNhanLayout },
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
    laPt() {
      return this.xacThuc.taiKhoan?.vai_tro === 'HUAN_LUYEN_VIEN'
    },
    laKhach() {
      return this.xacThuc.taiKhoan?.vai_tro === 'KHACH_HANG'
    },
    duongDanHoSo() {
      return this.$route.path
    },
    nhanVaiTro() {
      return {
        ADMIN: 'Quản trị viên',
        HUAN_LUYEN_VIEN: 'Huấn luyện viên',
        KHACH_HANG: 'Khách hàng',
      }[this.xacThuc.taiKhoan?.vai_tro]
    },
    hoSo() {
      return this.xacThuc.taiKhoan?.[this.laPt ? 'ho_so_huan_luyen_vien' : 'ho_so_khach_hang']
    },
    chuCaiDau() {
      return (this.xacThuc.taiKhoan?.ho_ten || 'U').trim().charAt(0).toUpperCase()
    },
    ngaySinhDinhDang() {
      return this.hoSo?.ngay_sinh
        ? this.hoSo.ngay_sinh.split('T')[0].split('-').reverse().join('/')
        : 'Chưa cung cấp'
    },
    nhanGioiTinh() {
      return { NAM: 'Nam', NU: 'Nữ', KHAC: 'Khác' }[this.hoSo?.gioi_tinh] || 'Chưa cung cấp'
    },
    thoiGianTap() {
      return Array.isArray(this.hoSo?.thoi_gian_co_the_tap) ? this.hoSo.thoi_gian_co_the_tap : []
    },
  },
}
</script>
<style scoped>
.ho-so-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 24px;
  align-items: start;
}
.anh-chu {
  width: 64px;
  height: 64px;
  display: grid;
  place-items: center;
  border-radius: 8px;
  background: var(--mau-chinh);
  color: var(--mau-tren-chinh);
  font-size: 28px;
  font-weight: 700;
  flex-shrink: 0;
}
dt {
  color: var(--mau-phu);
  font-size: 14px;
  margin-bottom: 6px;
  font-weight: 500;
}
dd {
  margin-bottom: 24px;
  overflow-wrap: anywhere;
}
.gioi-thieu {
  white-space: pre-wrap;
}
.btn {
  min-height: 44px;
}
@media (max-width: 800px) {
  .ho-so-grid {
    grid-template-columns: 1fr;
  }
}
</style>
