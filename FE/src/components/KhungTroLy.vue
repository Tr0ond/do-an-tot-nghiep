<template>
  <section class="ai-page" :class="{ 'ai-page--compact': thuGon }">
    <header v-if="!thuGon" class="ai-heading">
      <div>
        <span class="ai-eyebrow">TRỢ LÝ TẬP LUYỆN</span>
        <h1>Tr0ond AI</h1>
        <p>Cùng bạn tìm cách tập phù hợp, từng câu hỏi một.</p>
      </div>
      <button
        type="button"
        class="btn btn-outline-secondary"
        :aria-pressed="chuyenDong"
        @click="chuyenDong = !chuyenDong"
      >
        <i :class="chuyenDong ? 'bi bi-pause-circle' : 'bi bi-play-circle'" aria-hidden="true"></i>
        {{ chuyenDong ? 'Dừng mascot' : 'Bật mascot' }}
      </button>
    </header>
    <p v-if="loi" class="ai-error" role="alert">{{ loi }}</p>
    <div v-if="thuGon" class="ai-compact-tools">
      <span v-if="hanMuc">{{ hanMuc.con_lai }}/{{ hanMuc.toi_da }} lượt hôm nay</span>
      <button type="button" :aria-expanded="lichSuMo" @click="lichSuMo = !lichSuMo">
        <i class="bi bi-clock-history" aria-hidden="true"></i> Lịch sử
      </button>
      <button type="button" :disabled="dangGui || dangTai" @click="cuocMoi">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Mới
      </button>
    </div>
    <div class="ai-workspace">
      <aside class="ai-sidebar" :class="{ 'is-open': lichSuMo }" aria-label="Lịch sử chatbot">
        <button
          type="button"
          class="btn btn-primary w-100"
          :disabled="dangGui || dangTai"
          @click="cuocMoi"
        >
          <i class="bi bi-plus-lg" aria-hidden="true"></i> Cuộc trò chuyện mới
        </button>
        <div v-if="hanMuc" class="ai-quota">
          <span>Lượt hôm nay</span
          ><strong
            >{{ hanMuc.con_lai }}<small> / {{ hanMuc.toi_da }}</small></strong
          >
          <p v-if="hanMuc.co_quyen">{{ hanMuc.ten_goi }} · Cấp lại lúc 00:00</p>
          <p v-else>Bạn cần gói có quyền chatbot để gửi câu hỏi.</p>
          <RouterLink v-if="!hanMuc.co_quyen" to="/goi-tap"
            >Xem gói tập <i class="bi bi-arrow-right" aria-hidden="true"></i
          ></RouterLink>
        </div>
        <button
          type="button"
          class="ai-history-toggle"
          :aria-expanded="lichSuMo"
          @click="lichSuMo = !lichSuMo"
        >
          Lịch sử cuộc trò chuyện
          <i :class="lichSuMo ? 'bi bi-chevron-up' : 'bi bi-chevron-down'" aria-hidden="true"></i>
        </button>
        <div class="ai-recent" :class="{ 'is-open': lichSuMo }">
          <h2>Gần đây</h2>
          <p v-if="!hoiThoai.length" class="text-secondary small">
            Các cuộc trò chuyện của bạn sẽ hiện ở đây.
          </p>
          <button
            v-for="h in hoiThoai"
            :key="h.id"
            type="button"
            class="ai-conversation"
            :class="{ 'is-active': idChon === h.id }"
            :aria-pressed="idChon === h.id"
            :disabled="dangGui || dangTai"
            @click="moHoiThoai(h.id)"
          >
            <i class="bi bi-chat-square-text" aria-hidden="true"></i><span>{{ h.tieu_de }}</span>
          </button>
          <div class="ai-pages" v-if="tongTrangHoi > 1">
            <button
              type="button"
              :disabled="trangHoi === 1 || dangGui"
              @click="taiDanhSach(trangHoi - 1)"
            >
              Trước</button
            ><span>{{ trangHoi }}/{{ tongTrangHoi }}</span
            ><button
              type="button"
              :disabled="trangHoi === tongTrangHoi || dangGui"
              @click="taiDanhSach(trangHoi + 1)"
            >
              Sau
            </button>
          </div>
        </div>
        <div class="ai-help">
          <i class="bi bi-shield-check" aria-hidden="true"></i>
          <p>AI đưa ra gợi ý. Bạn quyết định giáo án và lịch tập của mình.</p>
          <RouterLink to="/bai-tap">Thư viện bài tập</RouterLink
          ><RouterLink to="/khach-hang/tin-nhan">Trao đổi với PT</RouterLink>
          <RouterLink to="/faq">Câu hỏi & tài liệu miễn phí</RouterLink>
        </div>
      </aside>
      <div class="ai-chat">
        <div class="ai-chat-bar">
          <MascotTroLy :chuyen-dong="chuyenDong" />
          <div><strong>Tr0ond AI</strong><small>Tư vấn dựa trên dữ liệu hệ thống</small></div>
          <span v-if="dangGui" role="status">Đang trả lời…</span>
        </div>
        <div ref="vungTin" class="ai-messages" :aria-busy="dangTai">
          <p v-if="dangTai" role="status">Đang tải cuộc trò chuyện…</p>
          <button
            v-if="trangTin < tongTrangTin"
            type="button"
            class="ai-history-more"
            :disabled="dangTai || dangGui"
            @click="taiTinCu"
          >
            Xem tin trước
          </button>
          <div v-if="!tinNhan.length && !dangTai" class="ai-welcome">
            <MascotTroLy :chuyen-dong="chuyenDong" class="ai-mascot-welcome" />
            <span class="ai-eyebrow">CHÀO BẠN, TÔI LÀ TR0OND AI</span>
            <h2>Hôm nay bạn muốn tập thế nào?</h2>
            <p>
              Hỏi về bài tập, giáo án mẫu hoặc quyền lợi gói. Tôi sẽ hỏi thêm khi chưa đủ thông tin.
            </p>
            <div class="ai-prompts">
              <button
                v-for="g in goiY"
                :key="g"
                type="button"
                :disabled="dangGui"
                @click="chonGoiY(g)"
              >
                {{ g }} <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
              </button>
            </div>
          </div>
          <article
            v-for="t in tinNhan"
            :key="t.id"
            class="ai-message"
            :class="{ 'is-user': t.vai_tro === 'USER' }"
          >
            <MascotTroLy v-if="t.vai_tro === 'ASSISTANT'" />
            <div class="ai-message-body">
              <strong>{{ t.vai_tro === 'USER' ? 'Bạn' : 'Tr0ond AI' }}</strong>
              <p>{{ t.noi_dung }}</p>
              <small v-if="t.vai_tro === 'USER' && t.trang_thai === 'DANG_XU_LY'"
                >Đang xử lý. Bấm Cập nhật để kiểm tra kết quả.</small
              >
              <small v-if="t.vai_tro === 'USER' && t.trang_thai === 'LOI'"
                >Chưa có câu trả lời hợp lệ · Không mất lượt</small
              >
              <button
                v-if="t.co_the_thu_lai"
                type="button"
                class="ai-retry"
                :disabled="dangGui"
                @click="thuLaiTin(t)"
              >
                Thử lại câu hỏi này
              </button>
              <div v-if="t.nguon_da_kiem_tra" class="ai-sources">
                <GiaoAnAi
                  v-if="t.nguon_da_kiem_tra.giao_an_da_tao"
                  :giao-an="t.nguon_da_kiem_tra.giao_an_da_tao"
                />
                <RouterLink
                  v-for="g in t.nguon_da_kiem_tra.goi_tap"
                  :key="'g' + g.id"
                  :to="'/goi-tap/' + g.id"
                  class="ai-source-card"
                  ><strong>{{ g.ten_goi }}</strong
                  ><span>{{ tien(g.gia) }} · {{ g.thoi_han_ngay }} ngày</span
                  ><small
                    >{{ g.so_luot_chatbot_moi_ngay }} lượt AI/ngày · {{ g.so_buoi_pt }} buổi
                    PT</small
                  ></RouterLink
                >
                <RouterLink
                  v-for="b in t.nguon_da_kiem_tra.bai_tap"
                  :key="'b' + b.id"
                  :to="'/bai-tap/' + b.id"
                  class="ai-source-card"
                  ><strong>{{ b.ten_bai_tap }}</strong
                  ><small>{{ b.ten_nhom_co }} · {{ b.dung_cu }}</small></RouterLink
                >
                <div
                  v-for="g in t.nguon_da_kiem_tra.giao_an_mau"
                  :key="'m' + g.id"
                  class="ai-source-card"
                >
                  <strong>{{ g.ten_giao_an }}</strong
                  ><small
                    >{{ g.so_ngay_tap }} ngày · Mẫu tham khảo, trao đổi với PT để thiết kế phù
                    hợp</small
                  >
                </div>
                <details v-for="n in t.nguon_da_kiem_tra.tai_lieu" :key="'n' + n.id">
                  <summary>{{ n.tieu_de }} · v{{ n.phien_ban }}</summary>
                  <p>{{ n.noi_dung }}</p>
                </details>
                <small v-if="t.nguon_da_kiem_tra.chinh_sach"
                  >Tham chiếu: {{ t.nguon_da_kiem_tra.chinh_sach.tieu_de }} · v{{
                    t.nguon_da_kiem_tra.chinh_sach.phien_ban
                  }}</small
                >
              </div>
            </div>
          </article>
          <p v-if="dangGui" class="ai-thinking" role="status">
            Tr0ond AI đang tìm câu trả lời phù hợp…
          </p>
        </div>
        <form class="ai-composer" @submit.prevent="gui">
          <div class="ai-consent">
            <label
              ><input v-model="caNhan" type="checkbox" :disabled="dangGui || !!yeuCauCho" /> Dùng dữ
              liệu tập của tôi</label
            >
            <details class="ai-privacy">
              <summary>Dữ liệu gửi Gemini · AI có thể sai</summary>
              <small
                >Câu hỏi và lịch sử chatbot được gửi cho Google Gemini. Khi bật dữ liệu tập, gửi
                thêm mục tiêu, kinh nghiệm, giáo án đang dùng và số buổi hoàn thành. Không dùng chat
                riêng với PT. AI chỉ tư vấn; không dùng để chẩn đoán hoặc điều trị.</small
              >
            </details>
          </div>
          <p v-if="hanMuc && !hanMuc.san_sang" class="ai-inline-note">
            Tr0ond AI đang chờ cấu hình. Lịch sử của bạn vẫn được giữ.
          </p>
          <p v-if="hanMuc && !hanMuc.co_quyen" class="ai-inline-note">
            Cần gói có chatbot để hỏi AI. <RouterLink to="/goi-tap">Xem gói tập</RouterLink>
          </p>
          <details
            class="ai-plan-form"
            :open="formGiaoAnMo"
            @toggle="formGiaoAnMo = $event.target.open"
          >
            <summary>
              <i class="bi bi-journal-plus" aria-hidden="true"></i> Tạo giáo án cùng AI
            </summary>
            <div class="ai-plan-fields">
              <label
                >Buổi/tuần<input
                  v-model.number="buoiTuan"
                  type="number"
                  min="1"
                  max="7"
                  :disabled="dangGui || !!yeuCauCho"
              /></label>
              <label
                >Số tuần<input
                  v-model.number="soTuan"
                  type="number"
                  min="1"
                  max="30"
                  :disabled="dangGui || !!yeuCauCho"
              /></label>
              <label
                >Bài/buổi<input
                  v-model.number="baiBuoi"
                  type="number"
                  min="1"
                  max="8"
                  :disabled="dangGui || !!yeuCauCho"
              /></label>
            </div>
            <p>AI lưu bản nháp, bạn xem và tự áp dụng. Tối đa 30 buổi/120 bài.</p>
            <button
              type="button"
              class="btn btn-outline-primary"
              :disabled="dangGui || !!yeuCauCho"
              @click="soanYeuCauGiaoAn"
            >
              Soạn yêu cầu
            </button>
          </details>
          <label :for="maOHoTro" class="visually-hidden">Câu hỏi cho Tr0ond AI</label>
          <div class="ai-input-row">
            <textarea
              :id="maOHoTro"
              ref="oNhap"
              v-model="noiDung"
              rows="1"
              maxlength="2000"
              placeholder="Hỏi hoặc yêu cầu tạo giáo án…"
              :disabled="dangGui || !!yeuCauCho"
              @keydown.enter.exact.prevent="gui"
            /><button type="submit" class="btn btn-primary" :disabled="!coTheGui">
              <i class="bi bi-send" aria-hidden="true"></i><span>Gửi</span>
            </button>
          </div>
          <div class="ai-composer-foot">
            <small>Enter để gửi · Shift + Enter xuống dòng · {{ noiDung.length }}/2000</small
            ><button type="button" :disabled="dangGui || !idChon" @click="moHoiThoai(idChon)">
              Cập nhật
            </button>
          </div>
          <div v-if="yeuCauCho" class="ai-pending">
            <span>Câu hỏi đang chờ kết quả hoặc gửi lại.</span
            ><button type="button" :disabled="dangGui" @click="guiLai">Kiểm tra / gửi lại</button
            ><button type="button" :disabled="dangGui" @click="boYeuCauCho">
              Soạn câu hỏi mới
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>
<script>
import MascotTroLy from './MascotTroLy.vue'
import GiaoAnAi from './GiaoAnAi.vue'
import service from '../services/chatbotService'
import { layLoiApi } from '../utils/loiApi'
import '../assets/chatbot.css'
export default {
  name: 'KhungTroLy',
  components: { MascotTroLy, GiaoAnAi },
  props: {
    thuGon: { type: Boolean, default: false },
    hoatDong: { type: Boolean, default: true },
    mascotChay: { type: Boolean, default: true },
  },
  data() {
    return {
      hoiThoai: [],
      tinNhan: [],
      hanMuc: null,
      idChon: null,
      noiDung: '',
      caNhan: false,
      chuyenDong: true,
      lichSuMo: false,
      formGiaoAnMo: false,
      buoiTuan: 3,
      soTuan: 4,
      baiBuoi: 4,
      dangGui: false,
      dangTai: false,
      loi: '',
      yeuCauCho: null,
      uuidTao: null,
      trangHoi: 1,
      tongTrangHoi: 1,
      trangTin: 1,
      tongTrangTin: 1,
      huyTai: null,
      daDong: false,
      goiY: [
        'Tôi mới bắt đầu, nên tập thế nào?',
        'Tạo cho tôi giáo án 3 buổi/tuần trong 4 tuần, mỗi buổi 4 bài. Tôi mới bắt đầu, tập tại nhà không có tạ.',
        'Gói chatbot có quyền lợi gì?',
      ],
    }
  },
  computed: {
    maOHoTro() {
      return this.thuGon ? 'ai-window-input' : 'ai-input'
    },
    coTheGui() {
      return (
        !this.dangGui &&
        !this.dangTai &&
        !this.yeuCauCho &&
        !!this.noiDung.trim() &&
        this.hanMuc?.co_quyen &&
        this.hanMuc?.con_lai > 0 &&
        this.hanMuc?.san_sang
      )
    },
  },
  watch: {
    hoatDong(v) {
      if (v) {
        this.taiDanhSach()
        this.$nextTick(() => this.$refs.oNhap?.focus())
      }
    },
    mascotChay: {
      immediate: true,
      handler(v) {
        if (this.thuGon) this.chuyenDong = v
      },
    },
  },
  mounted() {
    this.taiDanhSach()
  },
  beforeUnmount() {
    this.daDong = true
    this.huyTai?.abort()
  },
  methods: {
    soanYeuCauGiaoAn() {
      const d = [this.buoiTuan, this.soTuan, this.baiBuoi]
      if (
        d.some((v) => !Number.isInteger(v) || v < 1) ||
        this.buoiTuan > 7 ||
        this.baiBuoi > 8 ||
        this.buoiTuan * this.soTuan > 30 ||
        d.reduce((a, b) => a * b, 1) > 120
      ) {
        this.loi = 'Tối đa 7 buổi/tuần, 8 bài/buổi, 30 buổi và 120 bài mỗi giáo án.'
        return
      }
      this.loi = ''
      this.noiDung = `Tạo cho tôi giáo án ${this.buoiTuan} buổi/tuần trong ${this.soTuan} tuần, mỗi buổi ${this.baiBuoi} bài. `
      this.formGiaoAnMo = false
      this.$nextTick(() => this.$refs.oNhap?.focus())
    },
    chonGoiY(g) {
      this.noiDung = g
      this.$refs.oNhap.focus()
    },
    tien(gia) {
      return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(gia)
    },
    async taiDanhSach(page = 1) {
      try {
        const r = await service.taiDanhSach(page)
        if (this.daDong) return
        this.hoiThoai = r.data
        this.hanMuc = r.meta.han_muc
        this.trangHoi = page
        this.tongTrangHoi = r.meta.last_page
      } catch (e) {
        if (!this.daDong) this.loi = layLoiApi(e).thongBao
      }
    },
    cuocMoi() {
      this.huyTai?.abort()
      this.idChon = null
      this.tinNhan = []
      this.noiDung = ''
      this.yeuCauCho = null
      this.uuidTao = null
      this.loi = ''
      this.trangTin = this.tongTrangTin = 1
      this.$refs.oNhap.focus()
    },
    async moHoiThoai(id) {
      if (this.dangGui) return
      this.huyTai?.abort()
      const a = new AbortController()
      this.huyTai = a
      this.dangTai = true
      this.loi = ''
      try {
        const r = await service.taiChiTiet(id, 1, a.signal)
        if (a.signal.aborted || this.daDong) return
        if (this.idChon !== id) {
          this.yeuCauCho = null
          this.noiDung = ''
          this.uuidTao = null
        }
        this.idChon = id
        this.lichSuMo = false
        this.tinNhan = r.data
        this.hanMuc = r.meta.han_muc
        this.trangTin = 1
        this.tongTrangTin = r.meta.last_page
        if (
          this.yeuCauCho &&
          r.data.some(
            (t) =>
              t.client_request_id === this.yeuCauCho.client_request_id &&
              t.trang_thai === 'THANH_CONG',
          )
        ) {
          this.yeuCauCho = null
          this.noiDung = ''
        }
        await this.cuonCuoi()
      } catch (e) {
        if (!a.signal.aborted && !this.daDong) this.loi = layLoiApi(e).thongBao
      } finally {
        if (this.huyTai === a) this.dangTai = false
      }
    },
    async taiTinCu() {
      if (this.dangTai || this.dangGui || !this.idChon) return
      this.huyTai?.abort()
      const a = new AbortController()
      this.huyTai = a
      const id = this.idChon
      this.dangTai = true
      try {
        const r = await service.taiChiTiet(id, this.trangTin + 1, a.signal)
        if (a.signal.aborted || this.daDong || this.idChon !== id) return
        this.tinNhan = [...r.data, ...this.tinNhan]
        this.trangTin++
      } catch (e) {
        if (!a.signal.aborted && !this.daDong) this.loi = layLoiApi(e).thongBao
      } finally {
        if (this.huyTai === a) this.dangTai = false
      }
    },
    async gui() {
      if (!this.coTheGui) return
      this.yeuCauCho = {
        client_request_id: crypto.randomUUID(),
        noi_dung: this.noiDung.trim(),
        dung_du_lieu_ca_nhan: this.caNhan,
      }
      await this.guiLai()
    },
    async guiLai() {
      if (this.dangGui || !this.yeuCauCho) return
      this.dangGui = true
      this.loi = ''
      try {
        if (!this.idChon) {
          this.uuidTao ||= crypto.randomUUID()
          const r = await service.tao(this.uuidTao)
          if (this.daDong) return
          this.idChon = r.data.id
        }
        const r = await service.gui(this.idChon, this.yeuCauCho)
        if (this.daDong) return
        this.yeuCauCho = null
        this.noiDung = ''
        this.hanMuc = r.meta.han_muc
      } catch (e) {
        if (!this.daDong) this.loi = layLoiApi(e).thongBao
      } finally {
        this.dangGui = false
        if (!this.daDong) {
          if (this.idChon) {
            const loi = this.loi
            await this.moHoiThoai(this.idChon)
            this.loi = loi
          }
          await this.taiDanhSach()
        }
      }
    },
    thuLaiTin(t) {
      this.yeuCauCho = {
        client_request_id: t.client_request_id,
        noi_dung: t.noi_dung,
        dung_du_lieu_ca_nhan: t.dung_du_lieu_ca_nhan,
      }
      this.guiLai()
    },
    boYeuCauCho() {
      this.yeuCauCho = null
      this.noiDung = ''
    },
    async cuonCuoi() {
      await this.$nextTick()
      const el = this.$refs.vungTin
      if (el) el.scrollTop = el.scrollHeight
    },
  },
}
</script>
