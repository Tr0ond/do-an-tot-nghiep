<template>
  <section class="ai-plan-preview" aria-label="Giáo án AI đã tạo">
    <span class="ai-eyebrow">GIÁO ÁN CỦA BẠN</span>
    <h3>{{ giaoAn.ten_ke_hoach }}</h3>
    <p>
      {{ giaoAn.so_tuan }} tuần · {{ giaoAn.buoi_moi_tuan }} buổi/tuần ·
      {{ giaoAn.bai_moi_buoi }} bài/buổi
    </p>
    <p class="ai-inline-note">
      Bản dưới là đề xuất lúc tạo. Xem giáo án để kiểm tra bản hiện tại{{
        giaoAn.da_an ? ' (đã ẩn)' : ''
      }}.
    </p>
    <div class="ai-plan-links">
      <RouterLink :to="'/khach-hang/ke-hoach/' + giaoAn.id" class="btn btn-primary"
        >Xem giáo án <i class="bi bi-arrow-up-right" aria-hidden="true"></i
      ></RouterLink>
      <RouterLink
        v-if="giaoAn.trang_thai === 'NHAP'"
        :to="'/khach-hang/ke-hoach/' + giaoAn.id + '/sua'"
        class="btn btn-outline-secondary"
        >Sửa nháp</RouterLink
      >
    </div>
    <details
      v-for="(cacBuoi, tuan) in theoTuan"
      :key="tuan"
      :open="Number(tuan) === 1"
      class="ai-plan-week"
    >
      <summary>
        Tuần {{ tuan }} <span>{{ cacBuoi.length }} buổi</span>
      </summary>
      <section v-for="buoi in cacBuoi" :key="buoi.buoi" class="ai-plan-session">
        <h4>Buổi {{ buoi.buoi }}</h4>
        <ol>
          <li v-for="b in buoi.bai_tap" :key="b.id">
            <AnhBaiTap :src="urlMedia(b.anh_url)" :alt="b.ten" />
            <div>
              <RouterLink :to="'/bai-tap/' + b.id">{{ b.ten }}</RouterLink
              ><small>{{ b.hiep }} hiệp × {{ b.lan }} lần · Nghỉ {{ b.nghi }} giây</small>
            </div>
          </li>
        </ol>
        <p v-if="buoi.bai_tap[0]?.ghi_chu" class="ai-inline-note">{{ buoi.bai_tap[0].ghi_chu }}</p>
      </section>
    </details>
  </section>
</template>
<script>
import AnhBaiTap from './AnhBaiTap.vue'
import baiTapService from '../services/baiTapService'
export default {
  name: 'GiaoAnAi',
  components: { AnhBaiTap },
  props: { giaoAn: { type: Object, required: true } },
  computed: {
    theoTuan() {
      return (this.giaoAn.buoi_tap || []).reduce((cacTuan, b) => {
        ;(cacTuan[b.tuan] ||= []).push(b)
        return cacTuan
      }, {})
    },
  },
  methods: { urlMedia: baiTapService.urlMedia },
}
</script>
