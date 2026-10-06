<template>
  <main class="auth-shell">
    <header class="st-auth-header">
      <RouterLink to="/" class="brand" aria-label="FitForge, trang chủ"
        ><LogoThuongHieu /><span>FitForge</span></RouterLink
      >
      <div><RouterLink to="/faq">Trợ giúp</RouterLink><NutChuyenChuDe /></div>
    </header>
    <div class="st-auth-content">
      <aside class="auth-intro">
        <div class="auth-story">
          <span class="auth-eyebrow">HUẤN LUYỆN CÁ NHÂN</span>
          <h2>FitForge</h2>
          <p>Mỗi ngày. Một bước tiến.</p>
        </div>
        <img
          :src="'/images/exercise-anatomy.png'"
          alt="Minh họa các động tác tập luyện và nhóm cơ"
          class="auth-training-image"
        />
        <RouterLink to="/" class="auth-home-link"
          ><i class="bi bi-arrow-left" aria-hidden="true"></i> Về trang chủ</RouterLink
        >
      </aside>
      <section class="auth-form-area">
        <div class="auth-form-card"><slot /></div>
      </section>
    </div>
    <footer class="st-auth-footer">
      <span>FitForge</span><RouterLink to="/">Trang chủ</RouterLink>
    </footer>
  </main>
</template>
<script>
import NutChuyenChuDe from '../components/NutChuyenChuDe.vue'
import LogoThuongHieu from '../components/LogoThuongHieu.vue'
export default {
  name: 'XacThucLayout',
  components: { NutChuyenChuDe, LogoThuongHieu },
}
</script>
<style scoped>
.auth-top-action {
  position: absolute;
  top: 20px;
  right: 24px;
}
</style>
