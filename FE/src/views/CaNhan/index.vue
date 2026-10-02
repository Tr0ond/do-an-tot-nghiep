<template>
  <CaNhanLayout>
    <div class="page-heading mb-4">
      <div>
        <div class="eyebrow">KHU VỰC CÁ NHÂN</div>
        <h1 class="h2">Hồ sơ của tôi</h1>
        <p class="text-muted mb-0">
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
    </div>
    <div class="ho-so-grid">
      <section class="profile-panel" aria-labelledby="thong-tin">
        <div class="d-flex gap-3 align-items-center mb-4">
          <div class="anh-chu">{{ chuCaiDau }}</div>
          <div>
            <h2 id="thong-tin" class="h4 mb-1">{{ xacThuc.taiKhoan?.ho_ten }}</h2>
            <span class="status-pill">Hoạt động</span>
          </div>
        </div>
        <dl>
          <dt>Email</dt>
          <dd>{{ xacThuc.taiKhoan?.email }}</dd>
          <dt>Vai trò</dt>
          <dd>{{ nhanVaiTro }}</dd>
          <template v-if="laKhach"
            ><dt>Ngày sinh</dt>
            <dd>{{ ngaySinhDinhDang }}</dd>
            <dt>Giới tính</dt>
            <dd>{{ nhanGioiTinh }}</dd></template
          >
        </dl>
      </section>
      <section v-if="laKhach || laPt" class="profile-panel" aria-labelledby="ho-so">
        <h2 id="ho-so" class="h5 mb-4">{{ laPt ? 'Hồ sơ chuyên môn' : 'Hồ sơ tập luyện' }}</h2>
        <dl>
          <template v-if="laKhach"
            ><dt>Mục tiêu tập luyện</dt>
            <dd>{{ hoSo?.muc_tieu || 'Chưa cung cấp' }}</dd>
            <dt>Kinh nghiệm</dt>
            <dd>{{ hoSo?.kinh_nghiem || 'Chưa cung cấp' }}</dd>
            <dt>Thời gian có thể tập</dt>
            <dd>
              <ul v-if="thoiGianTap.length" class="ps-3 mb-0">
                <li v-for="(gio, viTri) in thoiGianTap" :key="viTri">{{ gio }}</li>
              </ul>
              <span v-else>Chưa cung cấp</span>
            </dd></template
          ><template v-else
            ><dt>Chuyên môn</dt>
            <dd>{{ hoSo?.chuyen_mon || 'Chưa cung cấp' }}</dd>
            <dt>Giới thiệu</dt>
            <dd class="gioi-thieu">{{ hoSo?.gioi_thieu || 'Chưa cung cấp' }}</dd></template
          >
        </dl>
        <RouterLink :to="duongDanHoSo + '/sua'"
          >Bổ sung thông tin hồ sơ <i class="bi bi-arrow-right" aria-hidden="true"></i
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
  border-radius: 18px;
  background: var(--mau-chinh);
  color: white;
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
