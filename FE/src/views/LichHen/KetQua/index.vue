<template>
  <CaNhanLayout>
    <section class="nk kqpt">
      <header class="nk-heading">
        <div>
          <p class="nk-eyebrow">TẬP CÙNG HUẤN LUYỆN VIÊN</p>
          <h1>Kết quả buổi tập với PT</h1>
          <p>Bài tập và số liệu thực tế của buổi hẹn, tách biệt nhật ký tự tập.</p>
        </div>
        <RouterLink
          :to="`/${khuVuc}/lich-hen/${$route.params.id}`"
          class="btn btn-outline-secondary"
        >
          <i class="bi bi-arrow-left" aria-hidden="true"></i> Về lịch hẹn
        </RouterLink>
      </header>
      <p v-if="dangTai" class="nk-panel" role="status">Đang tải kết quả buổi tập…</p>
      <div v-if="loi" class="alert alert-warning" role="alert">
        <p class="mb-2">{{ loi }}</p>
        <ul v-if="Object.keys(loiTruong).length" class="mb-2">
          <li v-for="(cacLoi, truong) in loiTruong" :key="truong">{{ cacLoi.join(' ') }}</li>
        </ul>
        <button class="btn btn-outline-secondary btn-sm" :disabled="dangLuu" @click="taiLai">
          Tải lại kết quả
        </button>
      </div>
      <p v-if="thanhCong" class="alert alert-success" role="status">{{ thanhCong }}</p>
      <template v-if="duLieu && !dangTai">
        <section class="nk-panel">
          <div class="d-flex justify-content-between flex-wrap gap-2">
            <h2>Buổi #{{ duLieu.lich.id }} · {{ duLieu.lich.khach_hang }}</h2>
            <span
              class="badge align-self-start"
              :class="duLieu.ket_qua?.chot_luc ? 'bg-success' : 'bg-secondary'"
            >
              {{
                duLieu.ket_qua?.chot_luc
                  ? 'Đã chốt kết quả'
                  : duLieu.ket_qua
                    ? 'Bản nháp'
                    : 'Chưa ghi kết quả'
              }}
            </span>
          </div>
          <p>
            PT: <strong>{{ duLieu.lich.pt }}</strong> · {{ thoiGian(duLieu.lich.bat_dau_luc) }} –
            {{ thoiGian(duLieu.lich.ket_thuc_luc) }}
          </p>
          <p class="nk-footnote mb-0">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            Lưu hoặc chốt kết quả không trừ lượt PT. Xác nhận hoàn thành lịch hẹn là thao tác riêng.
          </p>
          <p v-if="duLieu.ly_do_khoa" class="nk-muted mt-3 mb-0">{{ duLieu.ly_do_khoa }}</p>
          <p v-if="noiDungCho || chotCho" class="alert alert-warning mt-3 mb-0" role="status">
            Chưa rõ kết quả lần gửi trước. Thử lại đúng yêu cầu hoặc tải lại để kiểm tra; nội dung
            đang được giữ nguyên.
          </p>
        </section>

        <section v-if="coTheGhi" class="nk-panel">
          <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center">
            <h2 class="mb-0">Bài tập thực tế · {{ baiTap.length }}/30</h2>
            <button
              class="btn btn-outline-secondary"
              :disabled="khoaNhap || baiTap.length >= 30"
              @click="moChonBai"
            >
              <i class="bi bi-plus-lg" aria-hidden="true"></i> Chọn bài tập
            </button>
          </div>
          <p class="nk-muted mt-2 mb-0">
            Chọn bài đã tập, rồi nhập từng hiệp. Không lấy chỉ tiêu giáo án làm kết quả.
          </p>
        </section>
        <section v-if="moThuVien && coTheGhi" class="nk-panel" aria-label="Thư viện chọn bài tập">
          <div class="d-flex justify-content-between gap-2">
            <h2>Chọn từ thư viện bài tập</h2>
            <button class="btn btn-outline-secondary btn-sm" @click="dongThuVien">
              Đóng thư viện
            </button>
          </div>
          <form class="d-flex gap-2 mb-3" @submit.prevent="timBai">
            <input
              v-model="tuKhoa"
              class="form-control"
              aria-label="Tìm bài tập"
              placeholder="Tên bài tập…"
              maxlength="100"
            />
            <button class="btn btn-primary" :disabled="dangTim">Tìm</button>
          </form>
          <p v-if="dangTim" role="status">Đang tải bài tập…</p>
          <p v-if="loiCatalog" class="text-danger" role="alert">{{ loiCatalog }}</p>
          <div class="kqpt-catalog">
            <button
              v-for="b in catalog"
              :key="b.id"
              class="btn btn-outline-secondary text-start"
              :disabled="khoaNhap || baiTap.length >= 30 || daChon(b.id)"
              @click="themBai(b)"
            >
              {{ b.ten_tieng_viet || b.ten_bai_tap }}
              <small>{{ daChon(b.id) ? 'Đã chọn' : 'Thêm vào buổi tập' }}</small>
            </button>
          </div>
          <p v-if="!dangTim && !catalog.length && !loiCatalog" class="nk-muted">
            Không có bài phù hợp.
          </p>
          <nav
            v-if="metaCatalog.last_page > 1"
            class="d-flex gap-3 mt-3"
            aria-label="Trang bài tập"
          >
            <button
              class="btn btn-outline-secondary btn-sm"
              :disabled="dangTim || trangCatalog <= 1"
              @click="doiTrangCatalog(-1)"
            >
              Trước
            </button>
            <span>{{ trangCatalog }}/{{ metaCatalog.last_page }}</span>
            <button
              class="btn btn-outline-secondary btn-sm"
              :disabled="dangTim || trangCatalog >= metaCatalog.last_page"
              @click="doiTrangCatalog(1)"
            >
              Sau
            </button>
          </nav>
        </section>

        <div class="nk-grid">
          <div>
            <p v-if="!baiTap.length" class="nk-panel nk-muted">
              {{
                coTheGhi
                  ? 'Chưa chọn bài tập. Mở thư viện để thêm bài đã tập.'
                  : 'PT chưa lưu bài tập thực tế của buổi này.'
              }}
            </p>
            <article v-for="(b, i) in baiTap" :key="b.bai_tap_id" class="nk-panel mb-4">
              <header class="d-flex align-items-center gap-3 mb-3">
                <img
                  v-if="media(b) && !anhLoi[b.bai_tap_id]"
                  :src="media(b)"
                  alt=""
                  class="kqpt-image"
                  @error="anhLoi[b.bai_tap_id] = true"
                />
                <div class="flex-grow-1">
                  <h2 class="mb-1">{{ i + 1 }}. {{ b.ten_bai_tap }}</h2>
                  <small v-if="b.ghi_cong_media" class="nk-muted">{{ b.ghi_cong_media }}</small>
                </div>
                <button
                  v-if="coTheGhi"
                  class="btn btn-outline-danger btn-sm"
                  :disabled="khoaNhap"
                  :aria-label="`Bỏ bài ${i + 1}`"
                  @click="boBai(i)"
                >
                  Bỏ bài
                </button>
              </header>
              <div v-if="b.hiep_tap.length" class="table-responsive">
                <table class="table kqpt-table">
                  <thead>
                    <tr>
                      <th>Hiệp</th>
                      <th>Số lần</th>
                      <th>Tạ (kg)</th>
                      <th>Nghỉ (giây)</th>
                      <th v-if="coTheGhi"><span class="visually-hidden">Thao tác</span></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(h, j) in b.hiep_tap" :key="j">
                      <td>{{ j + 1 }}</td>
                      <template v-if="coTheGhi">
                        <td>
                          <input
                            v-model="h.so_lan_lap"
                            class="form-control"
                            type="number"
                            min="1"
                            max="1000"
                            step="1"
                            :disabled="khoaNhap"
                            :aria-label="`Bài ${i + 1}, hiệp ${j + 1}: số lần`"
                          />
                        </td>
                        <td>
                          <input
                            v-model="h.khoi_luong_kg"
                            class="form-control"
                            type="number"
                            min="0"
                            max="1000"
                            step="0.01"
                            placeholder="Chưa ghi"
                            :disabled="khoaNhap"
                            :aria-label="`Bài ${i + 1}, hiệp ${j + 1}: tạ kg`"
                          />
                        </td>
                        <td>
                          <input
                            v-model="h.nghi_giay"
                            class="form-control"
                            type="number"
                            min="0"
                            max="3600"
                            step="1"
                            :disabled="khoaNhap"
                            :aria-label="`Bài ${i + 1}, hiệp ${j + 1}: nghỉ giây`"
                          />
                        </td>
                        <td>
                          <button
                            class="btn btn-outline-danger btn-sm"
                            :disabled="khoaNhap"
                            :aria-label="`Bỏ hiệp ${j + 1} của bài ${i + 1}`"
                            @click="boHiep(i, j)"
                          >
                            <i class="bi bi-trash" aria-hidden="true"></i>
                          </button>
                        </td>
                      </template>
                      <template v-else
                        ><td>{{ h.so_lan_lap }}</td>
                        <td>
                          {{
                            h.khoi_luong_kg === '' || h.khoi_luong_kg == null
                              ? 'Chưa ghi'
                              : h.khoi_luong_kg
                          }}
                        </td>
                        <td>{{ h.nghi_giay }}</td></template
                      >
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else class="nk-muted">Chưa ghi hiệp thực tế.</p>
              <button
                v-if="coTheGhi"
                class="btn btn-outline-secondary w-100"
                :disabled="khoaNhap || b.hiep_tap.length >= 20"
                @click="themHiep(i)"
              >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm hiệp
              </button>
            </article>
          </div>
          <aside class="nk-panel nk-comments">
            <h2>Ghi chú và nhận xét của PT</h2>
            <template v-if="coTheGhi">
              <label class="d-block mb-3"
                >Ghi chú buổi tập<textarea
                  v-model="ghiChu"
                  class="form-control mt-2"
                  rows="3"
                  maxlength="2000"
                  :disabled="khoaNhap"
                ></textarea>
              </label>
              <label class="d-block"
                >Nhận xét kỹ thuật, mức tạ, lưu ý cho lần sau<textarea
                  v-model="nhanXet"
                  class="form-control mt-2"
                  rows="4"
                  maxlength="2000"
                  :disabled="khoaNhap"
                ></textarea>
              </label>
            </template>
            <template v-else>
              <h3 class="h6">Ghi chú buổi tập</h3>
              <p class="nk-note">{{ ghiChu || 'Chưa có ghi chú.' }}</p>
              <h3 class="h6">Nhận xét của PT</h3>
              <p class="nk-note">{{ nhanXet || 'Chưa có nhận xét.' }}</p>
            </template>
            <p v-if="duLieu.ket_qua?.chot_luc" class="nk-footnote">
              Đã chốt lúc {{ thoiGian(duLieu.ket_qua.chot_luc) }}. Kết quả được giữ nguyên.
            </p>
          </aside>
        </div>

        <section v-if="coTheGhi" class="nk-panel">
          <div class="nk-save-bar">
            <span role="status">{{
              coThayDoi ? 'Có thay đổi chưa lưu' : 'Dữ liệu đã lưu không có thay đổi'
            }}</span>
            <button
              class="btn btn-outline-secondary"
              :disabled="dangLuu || !!chotCho || (!coThayDoi && !noiDungCho)"
              @click="luuNhap"
            >
              {{ dangLuu ? 'Đang xử lý…' : noiDungCho ? 'Thử lại lưu nháp' : 'Lưu nháp' }}
            </button>
            <button
              class="btn btn-primary"
              :disabled="khoaNhap || coThayDoi || !duLieu.ket_qua || !duLieu.co_the_chot"
              @click="deNghiChot"
            >
              Chốt kết quả
            </button>
          </div>
          <p class="nk-footnote mb-0">
            Lưu nháp trước khi chốt. Chỉ chốt sau giờ kết thúc, trong hạn 24 giờ; mỗi bài cần ít
            nhất một hiệp thực tế.
          </p>
        </section>
        <section
          v-if="choChot"
          class="nk-panel border-warning"
          role="alertdialog"
          aria-label="Xác nhận chốt kết quả"
        >
          <h2>Chốt kết quả buổi tập?</h2>
          <p>
            Sau khi chốt không thể sửa bài tập, hiệp hoặc nhận xét. Lịch hẹn chưa được xác nhận hoàn
            thành bởi thao tác này.
          </p>
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary" :disabled="dangLuu" @click="chotKetQua">
              {{ chotCho ? 'Thử lại chốt kết quả' : 'Xác nhận chốt' }}
            </button>
            <button
              class="btn btn-outline-secondary"
              :disabled="dangLuu || !!chotCho"
              @click="choChot = false"
            >
              Tiếp tục xem
            </button>
          </div>
        </section>
      </template>
    </section>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../../layouts/CaNhanLayout.vue'
import ketQuaBuoiPtService from '../../../services/ketQuaBuoiPtService'
import baiTapService from '../../../services/baiTapService'
import { useXacThucStore } from '../../../stores/xacThuc'
import { chuKyNhapPt, noiDungKetQuaPt } from '../../../utils/ketQuaBuoiPt'
import { layLoiApi } from '../../../utils/loiApi'
import { dinhDangLuc } from '../../../utils/donHang'
import '../../../assets/nhatKyTap.css'

export default {
  name: 'KetQuaBuoiPtPage',
  components: { CaNhanLayout },
  data() {
    return {
      duLieu: null,
      baiTap: [],
      ghiChu: '',
      nhanXet: '',
      banDaLuu: '',
      dangTai: false,
      dangLuu: false,
      loi: '',
      loiTruong: {},
      thanhCong: '',
      xungDot: false,
      noiDungCho: null,
      chotCho: null,
      choChot: false,
      moThuVien: false,
      tuKhoa: '',
      tuKhoaLoc: '',
      catalog: [],
      metaCatalog: {},
      trangCatalog: 1,
      dangTim: false,
      loiCatalog: '',
      anhLoi: {},
      boHuy: null,
      boHuyGhi: null,
      boHuyCatalog: null,
      lanTai: 0,
      lanCatalog: 0,
      daDong: false,
    }
  },
  computed: {
    taiKhoan() {
      return useXacThucStore().taiKhoan
    },
    khuVuc() {
      return this.taiKhoan?.vai_tro === 'HUAN_LUYEN_VIEN' ? 'pt' : 'khach-hang'
    },
    coTheGhi() {
      return !!this.duLieu?.co_the_ghi && !this.xungDot
    },
    khoaNhap() {
      return this.dangLuu || !!this.noiDungCho || !!this.chotCho || this.choChot
    },
    coThayDoi() {
      return !!this.duLieu && chuKyNhapPt(this.baiTap, this.ghiChu, this.nhanXet) !== this.banDaLuu
    },
  },
  watch: {
    '$route.fullPath': 'doiNguCanh',
    'taiKhoan.id': 'doiNguCanh',
  },
  mounted() {
    this.taiDuLieu()
    window.addEventListener('beforeunload', this.canhBaoRoiTrang)
    window.addEventListener('focus', this.kiemTraLai)
  },
  beforeUnmount() {
    this.daDong = true
    this.huyYeuCau()
    window.removeEventListener('beforeunload', this.canhBaoRoiTrang)
    window.removeEventListener('focus', this.kiemTraLai)
  },
  beforeRouteLeave() {
    return this.choRoiTrang()
  },
  beforeRouteUpdate() {
    return this.choRoiTrang()
  },
  methods: {
    thoiGian: dinhDangLuc,
    media(b) {
      return baiTapService.urlMedia(b.anh_url || b.gif_url)
    },
    choRoiTrang() {
      return (
        !this.dangLuu &&
        (!(this.coThayDoi || this.noiDungCho || this.chotCho) ||
          window.confirm('Bạn có dữ liệu chưa lưu hoặc yêu cầu chưa rõ kết quả. Rời trang?'))
      )
    },
    canhBaoRoiTrang(e) {
      if (this.coThayDoi || this.dangLuu || this.noiDungCho || this.chotCho) {
        e.preventDefault()
        e.returnValue = ''
      }
    },
    kiemTraLai() {
      if (!this.coThayDoi && !this.khoaNhap && !this.dangTai) this.taiDuLieu()
    },
    huyYeuCau() {
      this.lanTai++
      this.lanCatalog++
      this.boHuy?.abort()
      this.boHuyGhi?.abort()
      this.boHuyCatalog?.abort()
    },
    doiNguCanh() {
      this.huyYeuCau()
      this.duLieu = null
      this.baiTap = []
      this.ghiChu = ''
      this.nhanXet = ''
      this.banDaLuu = ''
      this.noiDungCho = null
      this.chotCho = null
      this.choChot = false
      this.moThuVien = false
      this.catalog = []
      this.anhLoi = {}
      this.thanhCong = ''
      this.dangLuu = false
      this.taiDuLieu()
    },
    nhanDuLieu(d) {
      this.duLieu = d
      this.baiTap = (d.ket_qua?.bai_tap || []).map((b) => ({
        ...b,
        hiep_tap: b.hiep_tap.map((h) => ({
          so_lan_lap: String(h.so_lan_lap),
          khoi_luong_kg: h.khoi_luong_kg == null ? '' : String(h.khoi_luong_kg),
          nghi_giay: String(h.nghi_giay),
        })),
      }))
      this.ghiChu = d.ket_qua?.ghi_chu || ''
      this.nhanXet = d.ket_qua?.nhan_xet || ''
      this.banDaLuu = chuKyNhapPt(this.baiTap, this.ghiChu, this.nhanXet)
      this.noiDungCho = null
      this.chotCho = null
      this.choChot = false
      this.xungDot = false
    },
    async taiDuLieu() {
      this.boHuy?.abort()
      const boHuy = new AbortController()
      this.boHuy = boHuy
      const lan = ++this.lanTai
      const nguoi = this.taiKhoan?.id
      this.duLieu = null
      this.loi = ''
      this.loiTruong = {}
      this.dangTai = false
      if (!nguoi || !['KHACH_HANG', 'HUAN_LUYEN_VIEN'].includes(this.taiKhoan.vai_tro)) return
      this.dangTai = true
      try {
        const r = await ketQuaBuoiPtService.tai(this.khuVuc, this.$route.params.id, boHuy.signal)
        if (!this.daDong && lan === this.lanTai && nguoi === this.taiKhoan?.id)
          this.nhanDuLieu(r.data)
      } catch (e) {
        if (
          !boHuy.signal.aborted &&
          !this.daDong &&
          lan === this.lanTai &&
          nguoi === this.taiKhoan?.id
        )
          this.loi = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong && lan === this.lanTai) this.dangTai = false
      }
    },
    taiLai() {
      if (this.dangLuu) return
      if (
        (this.coThayDoi || this.noiDungCho || this.chotCho) &&
        !window.confirm('Tải lại dữ liệu từ máy chủ và bỏ nội dung chưa lưu?')
      )
        return
      this.thanhCong = ''
      this.taiDuLieu()
    },
    daChon(id) {
      return this.baiTap.some((b) => Number(b.bai_tap_id) === Number(id))
    },
    moChonBai() {
      if (!this.coTheGhi || this.khoaNhap) return
      this.moThuVien = true
      this.trangCatalog = 1
      this.taiCatalog()
    },
    dongThuVien() {
      this.moThuVien = false
      this.lanCatalog++
      this.boHuyCatalog?.abort()
      this.dangTim = false
    },
    timBai() {
      this.tuKhoaLoc = this.tuKhoa.trim()
      this.trangCatalog = 1
      this.taiCatalog()
    },
    doiTrangCatalog(buoc) {
      this.trangCatalog += buoc
      this.taiCatalog()
    },
    async taiCatalog() {
      this.boHuyCatalog?.abort()
      const boHuy = new AbortController()
      this.boHuyCatalog = boHuy
      const lan = ++this.lanCatalog
      const nguoi = this.taiKhoan?.id
      this.catalog = []
      this.loiCatalog = ''
      this.dangTim = true
      try {
        const r = await baiTapService.taiDanhSach(
          { tu_khoa: this.tuKhoaLoc, page: this.trangCatalog, per_page: 12 },
          boHuy.signal,
        )
        if (!this.daDong && lan === this.lanCatalog && nguoi === this.taiKhoan?.id) {
          this.catalog = r.data
          this.metaCatalog = r.meta
        }
      } catch (e) {
        if (!boHuy.signal.aborted && !this.daDong && lan === this.lanCatalog)
          this.loiCatalog = layLoiApi(e).thongBao
      } finally {
        if (!this.daDong && lan === this.lanCatalog) this.dangTim = false
      }
    },
    themBai(b) {
      if (!this.coTheGhi || this.khoaNhap || this.daChon(b.id) || this.baiTap.length >= 30) return
      this.baiTap.push({
        bai_tap_id: b.id,
        ten_bai_tap: b.ten_tieng_viet || b.ten_bai_tap,
        anh_url: b.anh_url,
        gif_url: b.gif_url,
        ghi_cong_media: b.ghi_cong_media,
        hiep_tap: [],
      })
    },
    boBai(i) {
      if (this.coTheGhi && !this.khoaNhap) this.baiTap.splice(i, 1)
    },
    themHiep(i) {
      if (this.coTheGhi && !this.khoaNhap && this.baiTap[i]?.hiep_tap.length < 20)
        this.baiTap[i].hiep_tap.push({ so_lan_lap: '', khoi_luong_kg: '', nghi_giay: '' })
    },
    boHiep(i, j) {
      if (this.coTheGhi && !this.khoaNhap) this.baiTap[i].hiep_tap.splice(j, 1)
    },
    async luuNhap() {
      if (!this.coTheGhi || this.dangLuu || this.chotCho || this.choChot) return
      try {
        this.noiDungCho ??= noiDungKetQuaPt(
          this.baiTap,
          this.ghiChu,
          this.nhanXet,
          this.duLieu.ket_qua?.updated_at ?? null,
        )
      } catch (e) {
        this.loi = e.message
        return
      }
      await this.gui(false)
    },
    deNghiChot() {
      if (
        this.coTheGhi &&
        this.duLieu.co_the_chot &&
        this.duLieu.ket_qua &&
        !this.khoaNhap &&
        !this.coThayDoi
      )
        this.choChot = true
    },
    async chotKetQua() {
      if (!this.choChot || this.dangLuu || !this.coTheGhi || this.coThayDoi || this.noiDungCho)
        return
      this.chotCho ??= this.duLieu.ket_qua.updated_at
      await this.gui(true)
    },
    async gui(chot) {
      const nguoi = this.taiKhoan?.id
      const lan = this.lanTai
      const path = this.$route.fullPath
      const boHuy = new AbortController()
      this.boHuyGhi = boHuy
      this.dangLuu = true
      this.loi = ''
      this.loiTruong = {}
      this.thanhCong = ''
      try {
        const r = chot
          ? await ketQuaBuoiPtService.chot(this.$route.params.id, this.chotCho, boHuy.signal)
          : await ketQuaBuoiPtService.luu(this.$route.params.id, this.noiDungCho, boHuy.signal)
        if (
          !this.daDong &&
          lan === this.lanTai &&
          nguoi === this.taiKhoan?.id &&
          path === this.$route.fullPath
        ) {
          this.nhanDuLieu(r.data)
          this.thanhCong = r.message
        }
      } catch (e) {
        if (
          this.daDong ||
          lan !== this.lanTai ||
          nguoi !== this.taiKhoan?.id ||
          path !== this.$route.fullPath ||
          boHuy.signal.aborted
        )
          return
        const loi = layLoiApi(e)
        this.loi = loi.thongBao
        this.loiTruong = loi.loiTruong
        const status = e.response?.status
        if (status >= 400 && status < 500) {
          this.noiDungCho = null
          this.chotCho = null
          this.choChot = false
          if ([401, 403, 404, 409].includes(status)) {
            this.xungDot = true
            this.dongThuVien()
          }
        }
      } finally {
        if (!this.daDong && lan === this.lanTai && nguoi === this.taiKhoan?.id) this.dangLuu = false
      }
    },
  },
}
</script>

<style scoped>
.kqpt-image {
  width: 72px;
  height: 72px;
  object-fit: contain;
  border-radius: 8px;
  background: white;
}
.kqpt .nk-grid > div {
  min-width: 0;
}
.kqpt .nk-comments {
  align-self: start;
}
.kqpt .table-responsive {
  position: relative;
}
.kqpt-catalog {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 12px;
}
.kqpt-catalog button {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.kqpt-catalog small {
  color: var(--mau-chu-phu);
}
.kqpt-table {
  color: inherit;
  --bs-table-bg: transparent;
  --bs-table-color: var(--mau-chu);
  --bs-table-border-color: var(--mau-vien);
}
.kqpt-table input {
  min-width: 90px;
}
.nk-note {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}
</style>
