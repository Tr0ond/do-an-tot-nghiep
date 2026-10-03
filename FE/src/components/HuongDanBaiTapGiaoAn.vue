<template>
  <dialog
    ref="hopThoai"
    class="kh-guide-dialog"
    aria-labelledby="kh-guide-title"
    @cancel.prevent="$emit('dong')"
    @click.self="$emit('dong')"
  >
    <div class="ga-modal-container">
      <header class="ga-modal-top-bar">
        <button type="button" class="ga-modal-close-btn" autofocus @click="$emit('dong')">
          <i class="bi bi-x-lg" aria-hidden="true"></i> Đóng
        </button>
        <span class="kh-guide-source">Hướng dẫn trong giáo án</span>
      </header>
      <div class="ga-modal-content">
        <div>
          <h2 id="kh-guide-title">{{ baiTap.ten_bai_tap }}</h2>
          <p class="kh-guide-meta">
            {{ baiTap.nhom_co }}<span v-if="baiTap.dung_cu"> · {{ baiTap.dung_cu }}</span>
          </p>
        </div>
        <AnhBaiTap
          class="kh-guide-media"
          :src="urlMedia(xemGif ? baiTap.gif_url : baiTap.anh_url)"
          :alt="`${baiTap.ten_bai_tap} — ${xemGif ? 'minh họa chuyển động' : 'ảnh minh họa'}`"
          loading="eager"
        />
        <button
          v-if="baiTap.gif_url"
          type="button"
          class="ga-btn-guide kh-guide-play"
          :aria-pressed="xemGif"
          @click="xemGif = !xemGif"
        >
          <i :class="xemGif ? 'bi bi-pause-circle' : 'bi bi-play-circle'" aria-hidden="true"></i>
          {{ xemGif ? 'Dừng chuyển động' : 'Xem chuyển động (GIF)' }}
        </button>
        <div class="kh-guide-prescription">
          <span>{{ baiTap.so_hiep }} hiệp × {{ baiTap.so_lan_lap }} lần</span>
          <span>Nghỉ {{ baiTap.nghi_giay }} giây</span>
          <span v-if="baiTap.muc_ta_kg != null">Tạ {{ baiTap.muc_ta_kg }} kg</span>
        </div>
        <section v-if="baiTap.huong_dan || baiTap.cac_buoc?.length" class="kh-guide-instructions">
          <h3>Hướng dẫn thực hiện</h3>
          <p v-if="baiTap.huong_dan" :lang="baiTap.ngon_ngu_huong_dan">{{ baiTap.huong_dan }}</p>
          <ol v-if="baiTap.cac_buoc?.length" :lang="baiTap.ngon_ngu_huong_dan">
            <li v-for="(buoc, i) in baiTap.cac_buoc" :key="i">{{ buoc }}</li>
          </ol>
        </section>
        <p v-else class="kh-guide-meta">Giáo án chưa lưu hướng dẫn cho bài tập này.</p>
        <p v-if="baiTap.ghi_chu" class="kh-note-bubble">
          <strong>Lưu ý:</strong> {{ baiTap.ghi_chu }}
        </p>
      </div>
    </div>
  </dialog>
</template>

<script>
import AnhBaiTap from './AnhBaiTap.vue'
import baiTapService from '../services/baiTapService'

export default {
  name: 'HuongDanBaiTapGiaoAn',
  components: { AnhBaiTap },
  props: { baiTap: { type: Object, required: true } },
  emits: ['dong'],
  data() {
    return { xemGif: false }
  },
  mounted() {
    this.$refs.hopThoai.showModal()
  },
  beforeUnmount() {
    this.$refs.hopThoai.close()
  },
  methods: { urlMedia: baiTapService.urlMedia },
}
</script>
