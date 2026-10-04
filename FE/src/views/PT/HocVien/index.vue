<template>
  <CaNhanLayout>
    <section class="st-students">
      <header class="st-page-heading">
        <div>
          <span class="st-kicker">HUẤN LUYỆN</span>
          <h1>Học viên & giáo án</h1>
          <p>Danh sách học viên đang được phân công cho bạn.</p>
        </div>
        <button
          class="btn btn-outline-secondary"
          :disabled="dangTai"
          aria-label="Cập nhật học viên"
          title="Cập nhật học viên"
          @click="taiDanhSach"
        >
          <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
        </button>
      </header>
      <div class="st-summary-strip">
        <div>
          <span>Học viên đang phụ trách</span><strong>{{ meta.total || 0 }}</strong>
        </div>
        <div>
          <span>Phạm vi hiển thị</span
          ><strong>{{ danhSach.length }} <small>học viên / trang</small></strong>
        </div>
        <RouterLink to="/pt/giao-an-mau"
          ><i class="bi bi-journal-text" aria-hidden="true"></i> Thư viện giáo án mẫu
          <i class="bi bi-arrow-up-right" aria-hidden="true"></i
        ></RouterLink>
      </div>
      <form class="st-toolbar" @submit.prevent="timKiem">
        <div class="st-search">
          <i class="bi bi-search" aria-hidden="true"></i
          ><label class="visually-hidden" for="tim-hoc-vien">Tìm học viên</label
          ><input
            id="tim-hoc-vien"
            v-model="tuKhoa"
            maxlength="100"
            class="form-control"
            placeholder="Tìm theo họ tên học viên…"
          />
        </div>
        <button type="submit" class="btn btn-primary" :disabled="dangTai">
          <i class="bi bi-search" aria-hidden="true"></i> Tìm kiếm
        </button>
        <button v-if="tuKhoa" type="button" class="btn btn-outline-secondary" @click="xoaTimKiem">
          Xóa lọc
        </button>
      </form>
      <p v-if="dangTai" class="st-empty" role="status">Đang tải danh sách học viên…</p>
      <div v-else-if="loi" class="alert alert-danger" role="alert">
        {{ loi }} <button class="btn btn-outline-secondary" @click="taiDanhSach">Thử lại</button>
      </div>
      <div v-else-if="!danhSach.length" class="st-empty">
        <i class="bi bi-people" aria-hidden="true"></i>
        <h2>
          {{ tuKhoa ? 'Không tìm thấy học viên phù hợp' : 'Chưa có học viên được phân công' }}
        </h2>
        <button v-if="tuKhoa" class="btn btn-outline-secondary" @click="xoaTimKiem">
          Xóa bộ lọc
        </button>
      </div>
      <div v-else class="st-table-wrap">
        <table class="table st-student-table align-middle">
          <caption class="visually-hidden">
            Danh sách học viên đang phụ trách
          </caption>
          <thead>
            <tr>
              <th scope="col">Học viên</th>
              <th scope="col">Phân công</th>
              <th scope="col">Theo dõi</th>
              <th scope="col">Giáo án cá nhân</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="kh in danhSach" :key="kh.id">
              <td>
                <div class="st-person">
                  <span class="st-avatar" aria-hidden="true">{{
                    kh.ho_ten?.charAt(0).toUpperCase() || 'H'
                  }}</span>
                  <div>
                    <strong>{{ kh.ho_ten }}</strong
                    ><small>Học viên #{{ kh.id }}</small>
                  </div>
                </div>
              </td>
              <td>
                <span class="st-status"
                  ><i class="bi bi-check-circle" aria-hidden="true"></i> Đang phụ trách</span
                >
              </td>
              <td>
                <div class="st-row-actions">
                  <RouterLink
                    :to="`/pt/hoc-vien/${kh.id}/chi-so-co-the`"
                    class="btn btn-outline-secondary"
                    :aria-label="`Chỉ số cơ thể của ${kh.ho_ten}`"
                    title="Chỉ số cơ thể"
                    ><i class="bi bi-activity" aria-hidden="true"></i></RouterLink
                  ><RouterLink
                    :to="`/pt/hoc-vien/${kh.id}/lich-tap`"
                    class="btn btn-outline-secondary"
                    :aria-label="`Lịch & nhật ký của ${kh.ho_ten}`"
                    title="Lịch & nhật ký"
                    ><i class="bi bi-calendar2-week" aria-hidden="true"></i
                  ></RouterLink>
                </div>
              </td>
              <td>
                <div class="st-row-actions">
                  <RouterLink
                    :to="`/pt/hoc-vien/${kh.id}/ke-hoach`"
                    class="btn btn-outline-secondary"
                    >Xem giáo án</RouterLink
                  ><RouterLink :to="`/pt/hoc-vien/${kh.id}/ke-hoach/them`" class="btn btn-primary"
                    ><i class="bi bi-plus-lg" aria-hidden="true"></i> Soạn mới</RouterLink
                  >
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <nav v-if="meta.last_page > 1" class="st-pagination" aria-label="Phân trang học viên">
        <button
          class="btn btn-outline-secondary"
          :disabled="dangTai || page <= 1"
          @click="chuyenTrang(-1)"
        >
          <i class="bi bi-chevron-left" aria-hidden="true"></i> Trước</button
        ><span>{{ page }} / {{ meta.last_page }}</span
        ><button
          class="btn btn-outline-secondary"
          :disabled="dangTai || page >= meta.last_page"
          @click="chuyenTrang(1)"
        >
          Sau <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </button>
      </nav>
    </section>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import keHoachTapService from '../../../services/keHoachTapService'
import { layLoiApi } from '../../../utils/loiApi'
import '../../../assets/giaoAnMau.css'
import '../../../assets/keHoachTap.css'

export default {
  name: 'HocVienPT',
  components: { CaNhanLayout },
  data() {
    return {
      danhSach: [],
      tuKhoa: '',
      page: 1,
      meta: { last_page: 1, total: 0 },
      dangTai: false,
      loi: '',
      huyTai: null,
    }
  },
  mounted() {
    this.taiDanhSach()
  },
  beforeUnmount() {
    this.huyTai?.abort()
  },
  methods: {
    timKiem() {
      this.page = 1
      this.taiDanhSach()
    },
    xoaTimKiem() {
      this.tuKhoa = ''
      this.timKiem()
    },
    chuyenTrang(huong) {
      this.page += huong
      this.taiDanhSach()
    },
    async taiDanhSach() {
      this.huyTai?.abort()
      const huy = new AbortController()
      this.huyTai = huy
      this.dangTai = true
      this.loi = ''
      try {
        const k = await keHoachTapService.taiHocVien(
          { tu_khoa: this.tuKhoa.trim(), page: this.page },
          huy.signal,
        )
        if (!huy.signal.aborted) {
          this.danhSach = k.data
          this.meta = k.meta
        }
      } catch (e) {
        if (!huy.signal.aborted) this.loi = layLoiApi(e).thongBao
      } finally {
        if (this.huyTai === huy) this.dangTai = false
      }
    },
  },
}
</script>
