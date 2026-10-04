<template>
  <section class="bao-cao-admin" :aria-busy="dangTai" aria-label="Tổng quan hoạt động">
    <div class="bc-dau">
      <p>
        <i class="bi bi-calendar3" aria-hidden="true"></i> {{ nhanNgay(homNay) }}
        <span>· Giờ Việt Nam</span>
      </p>
      <div class="bc-presets" aria-label="Khoảng thời gian báo cáo">
        <button
          v-for="m in [
            { ma: '7', nhan: '7 ngày' },
            { ma: '30', nhan: '30 ngày' },
            { ma: 'thang', nhan: 'Tháng này' },
          ]"
          :key="m.ma"
          :aria-pressed="khoang === m.ma"
          :disabled="dangTai"
          @click="chonNhanh(m.ma)"
        >
          {{ m.nhan }}
        </button>
      </div>
    </div>
    <details class="bc-bo-loc">
      <summary>
        <i class="bi bi-sliders" aria-hidden="true"></i> Tùy chỉnh kỳ báo cáo
        <span v-if="duLieu"
          >{{ nhanNgay(duLieu.bo_loc.tu_ngay) }} — {{ nhanNgay(duLieu.bo_loc.den_ngay) }}</span
        >
      </summary>
      <form @submit.prevent="taiBaoCao(1)">
        <label
          >Khoảng thời gian<select v-model="khoang" class="form-select" @change="chonKhoang">
            <option value="30">30 ngày gần đây</option>
            <option value="7">7 ngày gần đây</option>
            <option value="thang">Tháng này</option>
            <option value="nam">Năm nay</option>
            <option value="tuy-chon">Tùy chọn</option>
          </select></label
        >
        <label
          >Từ ngày<input
            v-model="boLoc.tu_ngay"
            type="date"
            class="form-control"
            required
            :max="homNay"
            @input="khoang = 'tuy-chon'"
        /></label>
        <label
          >Đến ngày<input
            v-model="boLoc.den_ngay"
            type="date"
            class="form-control"
            required
            :min="boLoc.tu_ngay"
            :max="homNay"
            @input="khoang = 'tuy-chon'"
        /></label>
        <label
          >Nhóm theo<select v-model="boLoc.nhom" class="form-select">
            <option value="ngay">Ngày</option>
            <option value="thang">Tháng</option>
          </select></label
        >
        <button type="submit" class="btn btn-primary" :disabled="dangTai">
          {{ dangTai ? 'Đang tải…' : 'Xem báo cáo' }}
        </button>
      </form>
    </details>
    <div v-if="thongBao" class="alert alert-danger" role="alert">
      {{ thongBao }}
      <button v-if="!hetPhien" class="btn btn-outline-danger btn-sm" @click="taiBaoCao(trangPt)">
        Thử lại</button
      ><RouterLink v-else to="/dang-nhap">Đăng nhập lại</RouterLink>
    </div>
    <p v-if="dangTai && !duLieu" class="bc-trang-thai" role="status">Đang tổng hợp số liệu…</p>
    <template v-if="duLieu">
      <dl class="bc-so-lieu">
        <div
          v-for="muc in cacChiSo"
          :key="muc.ma"
          class="bc-card"
          :class="{ 'bc-card-canh-bao': muc.canhBao }"
        >
          <div class="bc-card-top">
            <span class="bc-icon" :class="'bc-' + muc.mau"
              ><i :class="'bi bi-' + muc.icon" aria-hidden="true"></i></span
            ><span class="bc-pham-vi" :class="{ 'bc-cam': muc.canhBao }">{{ muc.phamVi }}</span>
          </div>
          <dt>{{ muc.nhan }}</dt>
          <dd>{{ muc.so }}</dd>
          <small>{{ muc.moTa }}</small>
        </div>
      </dl>
      <div class="bc-cot-chinh">
        <section class="bc-panel bc-chart-panel">
          <div class="bc-panel-heading">
            <div>
              <h2>Dòng tiền trong kỳ</h2>
              <p>{{ nhanNgay(duLieu.bo_loc.tu_ngay) }} — {{ nhanNgay(duLieu.bo_loc.den_ngay) }}</p>
            </div>
            <span class="bc-chip">VND</span>
          </div>
          <div class="bc-legends">
            <span><i class="bc-dot cam"></i> Tiền đã nhận</span
            ><span><i class="bc-dot xanh"></i> Thực thu sau hoàn</span>
          </div>
          <p v-if="!coDongTien" class="bc-trang-thai">
            Chưa có tiền nhận hoặc tiền hoàn trong khoảng đã chọn.
          </p>
          <template v-else>
            <svg
              class="bc-bieu-do"
              viewBox="0 0 720 220"
              role="img"
              aria-label="Dòng tiền đã nhận và thực thu sau hoàn. Số liệu chi tiết trong bảng bên dưới."
            >
              <line
                v-for="y in [20, 60, 100, 140, 180]"
                :key="y"
                x1="40"
                x2="680"
                :y1="y"
                :y2="y"
                class="bc-grid-line"
              />
              <line x1="40" x2="680" :y1="hinh.zero" :y2="hinh.zero" class="bc-truc-zero" />
              <text x="40" y="14" class="bc-nhan-truc">{{ tien(hinh.tren) }}</text>
              <text x="40" y="210" class="bc-nhan-truc">{{ tien(hinh.duoi) }}</text>
              <polygon
                :points="'40,' + hinh.zero + ' ' + hinhNhan.points + ' 680,' + hinh.zero"
                class="bc-vung-nhan"
              />
              <polyline :points="hinhNhan.points" class="bc-duong bc-duong-nhan" />
              <polyline :points="hinh.points" class="bc-duong" />
              <circle
                v-for="(p, i) in hinh.diem"
                :key="i"
                :cx="p.x"
                :cy="p.y"
                r="10"
                class="bc-diem-chon"
                @mouseenter="mocChon = i"
              />
              <circle
                v-if="hinh.diem[mocChon]"
                :cx="hinh.diem[mocChon].x"
                :cy="hinh.diem[mocChon].y"
                r="5"
                class="bc-diem"
              />
              <circle
                v-if="hinhNhan.diem[mocChon]"
                :cx="hinhNhan.diem[mocChon].x"
                :cy="hinhNhan.diem[mocChon].y"
                r="5"
                class="bc-diem bc-diem-nhan"
              />
            </svg>
            <div class="bc-moc-chon">
              <button :disabled="mocChon <= 0" aria-label="Mốc trước" @click="mocChon--">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
              </button>
              <p aria-live="polite">
                <span>{{ nhanKy(duLieu.bieu_do[mocChon].ky) }}</span
                ><strong>Nhận {{ tien(duLieu.bieu_do[mocChon].tien_da_nhan) }}</strong
                ><span>Sau hoàn {{ tien(duLieu.bieu_do[mocChon].thuc_thu) }}</span>
              </p>
              <button
                :disabled="mocChon >= duLieu.bieu_do.length - 1"
                aria-label="Mốc tiếp theo"
                @click="mocChon++"
              >
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
              </button>
            </div>
          </template>
          <details class="bc-chi-tiet">
            <summary>
              Xem bảng số liệu {{ duLieu.bo_loc.nhom === 'thang' ? 'theo tháng' : 'theo ngày' }}
            </summary>
            <div class="bc-table-scroll">
              <table class="bc-table">
                <caption class="visually-hidden">
                  Dòng tiền theo thời gian
                </caption>
                <thead>
                  <tr>
                    <th scope="col">Thời gian</th>
                    <th scope="col">Tiền nhận</th>
                    <th scope="col">Đã hoàn</th>
                    <th scope="col">Sau hoàn</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="m in duLieu.bieu_do" :key="m.ky">
                    <th scope="row">{{ nhanKy(m.ky) }}</th>
                    <td>{{ tien(m.tien_da_nhan) }}</td>
                    <td>{{ tien(m.tien_da_hoan) }}</td>
                    <td>{{ tien(m.thuc_thu) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </details>
          <p class="bc-chu-thich">
            Tiền nhận được payOS xác minh, gồm khoản chờ đối soát. Hoàn tiền tính theo ngày ghi nhận
            hoàn.
          </p>
          <dl class="bc-tien-phu">
            <div>
              <dt>Tiền nhận</dt>
              <dd>{{ tien(duLieu.trong_ky.tien_da_nhan) }}</dd>
            </div>
            <div>
              <dt>Đã hoàn</dt>
              <dd>{{ tien(duLieu.trong_ky.tien_da_hoan) }}</dd>
            </div>
            <div>
              <dt>Tiền chờ đối soát trong kỳ</dt>
              <dd>{{ tien(duLieu.trong_ky.cho_doi_soat) }}</dd>
            </div>
          </dl>
        </section>
        <section class="bc-panel bc-viec-panel">
          <div class="bc-panel-heading">
            <div>
              <h2>Cần xử lý</h2>
              <p>Tình trạng hiện tại của hệ thống</p>
            </div>
            <span class="bc-icon bc-cam"
              ><i class="bi bi-lightning-charge" aria-hidden="true"></i
            ></span>
          </div>
          <RouterLink v-for="m in cacViec" :key="m.to" :to="m.to" class="bc-viec"
            ><span class="bc-icon" :class="'bc-' + m.mau"
              ><i :class="'bi bi-' + m.icon" aria-hidden="true"></i></span
            ><span class="bc-viec-copy"
              ><strong>{{ so(m.so) }}</strong
              ><span>{{ m.nhan }}</span></span
            ><i class="bi bi-chevron-right" aria-hidden="true"></i
          ></RouterLink>
          <p class="bc-chu-thich">Chọn một mục để mở trang quản lý tương ứng.</p>
        </section>
      </div>
      <section class="bc-panel">
        <div class="bc-panel-heading">
          <div>
            <h2>
              Lịch huấn luyện hôm nay
              <span class="bc-chip">{{ so(lichHomNay.tong) }} lịch hẹn</span>
            </h2>
            <p>{{ nhanNgay(lichHomNay.ngay ?? homNay) }} · Hiển thị tối đa 8 lịch đầu ngày</p>
          </div>
          <RouterLink to="/admin/lich-hen" class="bc-link"
            >Xem tất cả <i class="bi bi-arrow-up-right" aria-hidden="true"></i
          ></RouterLink>
        </div>
        <div class="bc-status-list">
          <span
            v-for="(n, trangThai) in lichHomNay.trang_thai"
            :key="trangThai"
            class="bc-chip"
            :class="'bc-status-' + trangThai"
            >{{ nhanTrangThai(trangThai) }} · {{ so(n) }}</span
          >
        </div>
        <p v-if="!lichHomNay.data.length" class="bc-trang-thai">
          <i class="bi bi-calendar2-check" aria-hidden="true"></i> Hôm nay chưa có lịch hẹn.
        </p>
        <div v-else class="bc-table-scroll">
          <table class="bc-table">
            <caption class="visually-hidden">
              Lịch hẹn hôm nay theo giờ Việt Nam
            </caption>
            <thead>
              <tr>
                <th scope="col">Thời gian</th>
                <th scope="col">Khách hàng</th>
                <th scope="col">Huấn luyện viên</th>
                <th scope="col">Trạng thái</th>
                <th scope="col"><span class="visually-hidden">Chi tiết</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in lichHomNay.data" :key="l.id">
                <td class="bc-number">{{ gio(l.bat_dau_luc) }} — {{ gio(l.ket_thuc_luc) }}</td>
                <th scope="row">{{ l.khach_hang }}</th>
                <td>{{ l.pt }}</td>
                <td>
                  <span class="bc-chip" :class="'bc-status-' + l.trang_thai">{{
                    nhanTrangThai(l.trang_thai)
                  }}</span>
                </td>
                <td>
                  <RouterLink
                    :to="'/admin/lich-hen/' + l.id"
                    :aria-label="'Xem lịch hẹn của ' + l.khach_hang"
                    class="bc-row-link"
                    ><i class="bi bi-arrow-up-right" aria-hidden="true"></i
                  ></RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
      <section class="bc-panel">
        <div class="bc-panel-heading">
          <div>
            <h2>Gói có đơn nhận tiền</h2>
            <p>Theo tên gói tại thời điểm mua · Trong kỳ báo cáo</p>
          </div>
          <RouterLink to="/admin/goi-tap" class="bc-link"
            >Quản lý gói <i class="bi bi-arrow-up-right" aria-hidden="true"></i
          ></RouterLink>
        </div>
        <p v-if="!cacGoi.length" class="bc-trang-thai">Chưa có giao dịch trong kỳ.</p>
        <div v-else class="bc-goi-list">
          <div v-for="(g, i) in cacGoi" :key="g.goi_tap_id + ':' + g.ten_goi" class="bc-goi">
            <div class="bc-goi-ten">
              <span class="bc-rank">{{ i + 1 }}</span
              ><strong>{{ g.ten_goi }}</strong
              ><span>{{ so(g.so_don_nhan_tien) }} đơn nhận tiền</span>
            </div>
            <div class="bc-bar-track">
              <div :style="{ width: (g.so_don_nhan_tien / maxDonGoi) * 100 + '%' }"></div>
            </div>
          </div>
        </div>
        <details v-if="cacGoi.length" class="bc-chi-tiet">
          <summary>Xem chi tiết dòng tiền theo gói</summary>
          <div class="bc-table-scroll">
            <table class="bc-table">
              <caption class="visually-hidden">
                Dòng tiền theo tên gói tại thời điểm mua
              </caption>
              <thead>
                <tr>
                  <th scope="col">Gói tại thời điểm mua</th>
                  <th scope="col">Đơn nhận tiền</th>
                  <th scope="col">Tiền nhận</th>
                  <th scope="col">Đã hoàn</th>
                  <th scope="col">Sau hoàn</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="g in cacGoi" :key="g.goi_tap_id + ':' + g.ten_goi">
                  <th scope="row">{{ g.ten_goi }}</th>
                  <td>{{ so(g.so_don_nhan_tien) }}</td>
                  <td>{{ tien(g.tien_da_nhan) }}</td>
                  <td>{{ tien(g.tien_da_hoan) }}</td>
                  <td>{{ tien(g.thuc_thu) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </details>
      </section>
      <div class="bc-cot-deu">
        <section class="bc-panel">
          <div class="bc-panel-heading">
            <div>
              <h2>Hoạt động huấn luyện viên</h2>
              <p>Học viên hiện tại · Buổi hoàn thành trong kỳ</p>
            </div>
            <RouterLink to="/admin/phan-cong" class="bc-link"
              >Phân công PT <i class="bi bi-arrow-up-right" aria-hidden="true"></i
            ></RouterLink>
          </div>
          <dl class="bc-mini-grid">
            <div>
              <dt>Tổng PT</dt>
              <dd>{{ so(duLieu.pt.meta.total) }}</dd>
            </div>
            <div>
              <dt>PT có học viên</dt>
              <dd>{{ so(vanHanh.pt_co_hoc_vien) }}</dd>
            </div>
            <div>
              <dt>Học viên có PT</dt>
              <dd>{{ so(duLieu.hien_tai.hoc_vien_co_pt) }}</dd>
            </div>
            <div>
              <dt>Buổi hoàn thành</dt>
              <dd>{{ so(duLieu.trong_ky.buoi_pt_hoan_thanh) }}</dd>
            </div>
          </dl>
          <p v-if="!duLieu.pt.meta.total" class="bc-trang-thai">Chưa có huấn luyện viên.</p>
          <div v-else class="bc-table-scroll bc-table-pt">
            <table class="bc-table">
              <caption class="visually-hidden">
                Phân bổ học viên và buổi hoàn thành của PT
              </caption>
              <thead>
                <tr>
                  <th scope="col">Huấn luyện viên</th>
                  <th scope="col">Học viên</th>
                  <th scope="col">Buổi hoàn thành</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in duLieu.pt.data" :key="p.id">
                  <th scope="row">
                    <span class="bc-ten-pt"
                      ><span class="bc-avatar">{{ p.ho_ten.trim().slice(0, 1) }}</span
                      ><span
                        >{{ p.ho_ten
                        }}<small>{{
                          p.trang_thai === 'HOAT_DONG' ? 'Hoạt động' : 'Bị khóa'
                        }}</small></span
                      ></span
                    >
                  </th>
                  <td>{{ so(p.hoc_vien_hien_tai) }}</td>
                  <td>{{ so(p.buoi_hoan_thanh) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <nav
            v-if="duLieu.pt.meta.last_page > 1"
            class="bc-phan-trang"
            aria-label="Phân trang huấn luyện viên"
          >
            <button
              class="btn btn-outline-secondary btn-sm"
              :disabled="dangTai || trangPt <= 1"
              @click="taiBaoCao(trangPt - 1, true)"
            >
              Trước</button
            ><span>Trang {{ trangPt }} / {{ duLieu.pt.meta.last_page }}</span
            ><button
              class="btn btn-outline-secondary btn-sm"
              :disabled="dangTai || trangPt >= duLieu.pt.meta.last_page"
              @click="taiBaoCao(trangPt + 1, true)"
            >
              Sau
            </button>
          </nav>
        </section>
        <section class="bc-panel bc-ai-panel">
          <div class="bc-panel-heading">
            <div>
              <h2><i class="bi bi-stars bc-tim-text" aria-hidden="true"></i> Tr0ond AI</h2>
              <p>Thống kê hôm nay · {{ nhanNgay(ai.ngay ?? homNay) }}</p>
            </div>
            <span class="bc-chip bc-tim">Trợ lý AI</span>
          </div>
          <dl class="bc-mini-grid">
            <div>
              <dt>Yêu cầu</dt>
              <dd>{{ so(ai.yeu_cau) }}</dd>
            </div>
            <div>
              <dt>Thành công</dt>
              <dd class="bc-xanh-text">{{ so(ai.thanh_cong) }}</dd>
            </div>
            <div>
              <dt>Lỗi</dt>
              <dd class="bc-hong-text">{{ so(ai.loi) }}</dd>
            </div>
            <div>
              <dt>Tỷ lệ thành công</dt>
              <dd>{{ tyLeAi }}</dd>
            </div>
          </dl>
          <p class="bc-chu-thich">
            Tỷ lệ tính trên yêu cầu đã kết thúc. {{ so(ai.dang_xu_ly) }} yêu cầu đang xử lý.
          </p>
          <div class="bc-ai-chart">
            <div class="bc-panel-heading">
              <h3>Lượt sử dụng 7 ngày</h3>
              <span class="bc-chip">Yêu cầu</span>
            </div>
            <div
              class="bc-ai-bars"
              role="group"
              aria-label="Số yêu cầu AI theo ngày; số liệu hiển thị trên mỗi cột"
            >
              <div v-for="m in ai.bieu_do" :key="m.ngay" class="bc-ai-bar">
                <strong>{{ so(m.so_luong) }}</strong>
                <div class="bc-ai-bar-track">
                  <div :style="{ height: (m.so_luong / maxAi) * 100 + '%' }"></div>
                </div>
                <span>{{ m.ngay.slice(8) }}/{{ m.ngay.slice(5, 7) }}</span>
              </div>
            </div>
          </div>
          <dl class="bc-token-grid">
            <div>
              <dt>Token đầu vào</dt>
              <dd>{{ so(ai.input_tokens) }}</dd>
            </div>
            <div>
              <dt>Token đầu ra</dt>
              <dd>{{ so(ai.output_tokens) }}</dd>
            </div>
          </dl>
          <RouterLink to="/admin/tai-lieu-tu-van" class="bc-ai-link"
            >Quản lý tài liệu tư vấn <i class="bi bi-arrow-up-right" aria-hidden="true"></i
          ></RouterLink>
        </section>
      </div>
      <section class="bc-panel">
        <div class="bc-panel-heading">
          <div>
            <h2>Thành viên hệ thống</h2>
            <p>Tài khoản và phân quyền hiện tại</p>
          </div>
          <RouterLink to="/admin/tai-khoan" class="bc-link"
            >Quản lý thành viên <i class="bi bi-arrow-up-right" aria-hidden="true"></i
          ></RouterLink>
        </div>
        <dl class="bc-members">
          <div v-for="m in cacThanhVien" :key="m.nhan">
            <dt>{{ m.nhan }}</dt>
            <dd :class="'bc-' + m.mau + '-text'">{{ so(m.so) }}</dd>
          </div>
        </dl>
      </section>
      <div class="bc-notices">
        <div>
          <span class="bc-icon bc-xanh"
            ><i class="bi bi-check2-circle" aria-hidden="true"></i
          ></span>
          <p>
            <strong>{{ so(duLieu.trong_ky.buoi_pt_hoan_thanh) }} buổi PT đã hoàn thành</strong
            ><span>Đã ghi nhận tiêu hao lượt trong kỳ báo cáo.</span>
          </p>
        </div>
        <RouterLink to="/admin/don-hang"
          ><span class="bc-icon bc-cam"
            ><i class="bi bi-hourglass-split" aria-hidden="true"></i
          ></span>
          <p>
            <strong>{{ so(vanHanh.goi_sap_het_han) }} gói sắp hết hạn</strong
            ><span>Các gói đang sử dụng hết hạn trong 7 ngày tới.</span>
          </p></RouterLink
        >
        <RouterLink to="/admin/phan-cong"
          ><span class="bc-icon bc-lam"><i class="bi bi-person-check" aria-hidden="true"></i></span>
          <p>
            <strong>{{ so(vanHanh.can_xu_ly?.khach_cho_pt) }} khách hàng cần PT</strong
            ><span>Kiểm tra và phân công để bắt đầu đồng hành.</span>
          </p></RouterLink
        >
      </div>
    </template>
  </section>
</template>
<script>
import tongQuanService from '../../services/tongQuanService'
import { layLoiApi } from '../../utils/loiApi'
import { ngayVietNam, luiNgay, duongBieuDo } from '../../utils/baoCao'
export default {
  name: 'BaoCaoAdmin',
  props: { tongQuan: { type: Object, default: () => ({}) } },
  data() {
    const homNay = ngayVietNam()
    return {
      homNay,
      khoang: '30',
      boLoc: { tu_ngay: luiNgay(homNay, 29), den_ngay: homNay, nhom: 'ngay' },
      boLocDaTai: null,
      duLieu: null,
      dangTai: false,
      thongBao: '',
      hetPhien: false,
      trangPt: 1,
      mocChon: 0,
      boHuy: null,
      lanTai: 0,
    }
  },
  computed: {
    vanHanh() {
      return this.duLieu?.van_hanh ?? {}
    },
    ai() {
      return this.vanHanh.ai ?? {}
    },
    lichHomNay() {
      return this.vanHanh.lich_hom_nay ?? { tong: 0, data: [], trang_thai: {} }
    },
    quanTri() {
      return this.tongQuan?.quan_tri ?? {}
    },
    cacChiSo() {
      const ky = this.duLieu?.trong_ky ?? {}
      return [
        {
          ma: 'thuc_thu',
          nhan: 'Thực thu sau hoàn',
          so: this.tien(ky.thuc_thu),
          icon: 'wallet2',
          mau: 'cam',
          phamVi: 'Trong kỳ',
          moTa: 'Tiền nhận trừ tiền đã hoàn',
        },
        {
          ma: 'don_kich_hoat',
          nhan: 'Đơn đã kích hoạt',
          so: this.so(ky.don_kich_hoat),
          icon: 'bag-check',
          mau: 'xanh',
          phamVi: 'Trong kỳ',
          moTa: this.so(ky.don_moi) + ' đơn được tạo trong kỳ',
        },
        {
          ma: 'goi',
          nhan: 'Gói đang sử dụng',
          so: this.so(this.duLieu?.hien_tai?.goi_dang_su_dung),
          icon: 'people',
          mau: 'lam',
          phamVi: 'Hiện tại',
          moTa: 'Đã kích hoạt và còn thời hạn',
        },
        {
          ma: 'cho_pt',
          nhan: 'Khách hàng chờ PT',
          so: this.so(this.vanHanh.can_xu_ly?.khach_cho_pt),
          icon: 'person-plus',
          mau: 'cam',
          phamVi: 'Cần phân công',
          canhBao: true,
          moTa: 'Có gói PT còn lượt, chưa có PT',
        },
        {
          ma: 'lich',
          nhan: 'Lịch hẹn hôm nay',
          so: this.so(this.lichHomNay.tong),
          icon: 'calendar2-week',
          mau: 'cham',
          phamVi: 'Hôm nay',
          moTa: this.so(this.lichHomNay.trang_thai.HOAN_THANH) + ' buổi đã hoàn thành',
        },
        {
          ma: 'ai',
          nhan: 'Yêu cầu Tr0ond AI',
          so: this.so(this.ai.yeu_cau),
          icon: 'stars',
          mau: 'tim',
          phamVi: 'Hôm nay',
          moTa: this.so(this.ai.loi) + ' lỗi · ' + this.so(this.ai.dang_xu_ly) + ' đang xử lý',
        },
      ]
    },
    cacViec() {
      const c = this.vanHanh.can_xu_ly ?? {}
      return [
        {
          nhan: 'Giao dịch cần đối soát',
          so: c.giao_dich_doi_soat ?? 0,
          icon: 'credit-card',
          mau: 'lam',
          to: '/admin/don-hang',
        },
        {
          nhan: 'Khách hàng chờ phân công PT',
          so: c.khach_cho_pt ?? 0,
          icon: 'person-plus',
          mau: 'cam',
          to: '/admin/phan-cong',
        },
        {
          nhan: 'Lịch hẹn quá hạn chưa xử lý',
          so: c.lich_qua_han ?? 0,
          icon: 'clock-history',
          mau: 'hong',
          to: '/admin/lich-hen',
        },
        {
          nhan: 'Giáo án mẫu đang ở bản nháp',
          so: c.giao_an_nhap ?? 0,
          icon: 'journal-text',
          mau: 'tim',
          to: '/admin/giao-an-mau',
        },
      ]
    },
    hinh() {
      return duongBieuDo(this.duLieu?.bieu_do ?? [], 'thuc_thu', this.mienBieuDo)
    },
    hinhNhan() {
      return duongBieuDo(this.duLieu?.bieu_do ?? [], 'tien_da_nhan', this.mienBieuDo)
    },
    mienBieuDo() {
      const so = (this.duLieu?.bieu_do ?? []).flatMap((m) => [m.thuc_thu, m.tien_da_nhan])
      return { duoi: Math.min(0, ...so), tren: Math.max(0, ...so) }
    },
    cacGoi() {
      return [...(this.duLieu?.theo_goi ?? [])].sort(
        (a, b) => b.so_don_nhan_tien - a.so_don_nhan_tien,
      )
    },
    maxDonGoi() {
      return Math.max(1, ...this.cacGoi.map((g) => g.so_don_nhan_tien))
    },
    tyLeAi() {
      const ketThuc = (this.ai.thanh_cong ?? 0) + (this.ai.loi ?? 0)
      return ketThuc
        ? ((this.ai.thanh_cong / ketThuc) * 100).toLocaleString('vi-VN', {
            maximumFractionDigits: 1,
          }) + '%'
        : '—'
    },
    maxAi() {
      return Math.max(1, ...(this.ai.bieu_do ?? []).map((m) => m.so_luong))
    },
    cacThanhVien() {
      const tk = this.quanTri.tai_khoan ?? {}
      return [
        { nhan: 'Tổng tài khoản', so: tk.tong, mau: 'cam' },
        { nhan: 'Khách hàng', so: tk.khach_hang, mau: 'lam' },
        { nhan: 'Huấn luyện viên', so: tk.huan_luyen_vien, mau: 'tim' },
        { nhan: 'Quản trị viên', so: tk.admin, mau: 'cham' },
        { nhan: 'Đang hoạt động', so: tk.hoat_dong, mau: 'xanh' },
        { nhan: 'Đã khóa', so: tk.bi_khoa, mau: 'hong' },
      ]
    },
    cacNoiDung() {
      const q = this.quanTri
      return [
        {
          nhan: 'Bài tập hiển thị',
          so: q.bai_tap?.hien_thi,
          icon: 'collection-play',
          mau: 'cam',
          to: '/admin/bai-tap',
        },
        {
          nhan: 'Nhóm cơ hoạt động',
          so: q.nhom_co?.hoat_dong,
          icon: 'diagram-3',
          mau: 'lam',
          to: '/admin/nhom-co',
        },
        {
          nhan: 'Giáo án đã duyệt',
          so: q.giao_an_mau?.da_duyet,
          icon: 'journal-check',
          mau: 'xanh',
          to: '/admin/giao-an-mau',
        },
        {
          nhan: 'Gói đang mở bán',
          so: q.goi_tap?.dang_ban,
          icon: 'box-seam',
          mau: 'tim',
          to: '/admin/goi-tap',
        },
        {
          nhan: 'Tài liệu AI đã xuất bản',
          so: this.vanHanh.tai_lieu_da_xuat_ban,
          icon: 'file-earmark-text',
          mau: 'cham',
          to: '/admin/tai-lieu-tu-van',
        },
      ]
    },
    coDongTien() {
      return this.duLieu.bieu_do.some((m) => m.tien_da_nhan || m.tien_da_hoan)
    },
  },
  mounted() {
    this.taiBaoCao()
  },
  beforeUnmount() {
    this.lanTai++
    this.boHuy?.abort()
  },
  methods: {
    async chonNhanh(khoang) {
      this.khoang = khoang
      this.chonKhoang()
      await this.taiBaoCao()
    },
    gio(luc) {
      return new Intl.DateTimeFormat('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        hour: '2-digit',
        minute: '2-digit',
      }).format(new Date(luc))
    },
    nhanTrangThai(trangThai) {
      return (
        {
          CHO_XAC_NHAN: 'Chờ xác nhận',
          DA_XAC_NHAN: 'Đã xác nhận',
          HOAN_THANH: 'Hoàn thành',
          VANG_MAT: 'Vắng mặt',
          DA_HUY: 'Đã hủy',
          HET_HAN: 'Hết hạn',
          QUA_HAN_XAC_NHAN: 'Quá hạn xác nhận',
        }[trangThai] ?? trangThai
      )
    },
    chonKhoang() {
      this.homNay = ngayVietNam()
      if (this.khoang === 'tuy-chon') return
      this.boLoc.den_ngay = this.homNay
      this.boLoc.tu_ngay =
        this.khoang === 'thang'
          ? this.homNay.slice(0, 7) + '-01'
          : this.khoang === 'nam'
            ? this.homNay.slice(0, 4) + '-01-01'
            : luiNgay(this.homNay, Number(this.khoang) - 1)
      this.boLoc.nhom = this.khoang === 'nam' ? 'thang' : 'ngay'
    },
    async taiBaoCao(trang = 1, giuBoLoc = false) {
      this.homNay = ngayVietNam()
      this.boHuy?.abort()
      this.boHuy = new AbortController()
      const lan = ++this.lanTai
      const params = { ...(giuBoLoc ? this.boLocDaTai : this.boLoc), pt_page: trang }
      this.dangTai = true
      this.thongBao = ''
      this.hetPhien = false
      // Đổi trang PT giữ nguyên kỳ đang xem; tránh co cả dashboard trong lúc tải.
      if (!giuBoLoc) this.duLieu = null
      try {
        const r = await tongQuanService.taiBaoCao(params, this.boHuy.signal)
        if (lan !== this.lanTai) return
        this.duLieu = r.data
        this.boLocDaTai = { ...r.data.bo_loc }
        this.trangPt = r.data.pt.meta.current_page
        this.mocChon = r.data.bieu_do.length - 1
      } catch (e) {
        if (lan !== this.lanTai || e.code === 'ERR_CANCELED') return
        this.duLieu = null
        const loi = layLoiApi(e)
        this.thongBao = Object.values(loi.loiTruong).flat().join(' ') || loi.thongBao
        this.hetPhien = loi.hetPhien
      } finally {
        if (lan === this.lanTai) this.dangTai = false
      }
    },
    so(n) {
      return Number(n ?? 0).toLocaleString('vi-VN')
    },
    tien(n) {
      return this.so(n) + ' ₫'
    },
    nhanNgay(n) {
      return n.split('-').reverse().join('/')
    },
    nhanKy(n) {
      return n.length === 7 ? n.slice(5) + '/' + n.slice(0, 4) : this.nhanNgay(n)
    },
  },
}
</script>
<style scoped>
.bao-cao-admin {
  display: grid;
  gap: 24px;
  color: var(--mau-chu);
  min-width: 0;
}
.bao-cao-admin * {
  box-sizing: border-box;
}
.bao-cao-admin :is(button, a, summary, input, select):focus-visible {
  outline: 3px solid var(--mau-chinh);
  outline-offset: 3px;
}
.bao-cao-admin a {
  text-decoration: none;
}
.bao-cao-admin button {
  cursor: pointer;
}
.bao-cao-admin button:disabled {
  cursor: default;
  opacity: 0.5;
}
.bc-dau {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  background: var(--mau-the);
  padding: 18px 24px;
  border-radius: 8px;
  border: 1px solid var(--mau-vien);
  margin-bottom: 20px;
}
.bc-dau p {
  margin: 0;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--mau-chu);
}
.bc-dau p span {
  color: var(--mau-phu);
  font-weight: 400;
}
.bc-dau p i {
  margin-right: 8px;
  color: var(--mau-chinh);
}
.bc-presets {
  display: flex;
  padding: 3px;
  background: var(--mau-the-sub);
  border: 1px solid var(--mau-vien);
  border-radius: 8px;
  gap: 4px;
}
.bc-presets button {
  padding: 6px 14px;
  min-height: 36px;
  border: 0;
  border-radius: 6px;
  color: var(--mau-phu);
  background: transparent;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  transition: all 0.15s ease-in-out;
}
.bc-presets button:hover {
  color: var(--mau-chu);
}
.bc-presets button[aria-pressed='true'] {
  background: var(--mau-chinh);
  color: var(--mau-tren-chinh);
  box-shadow: none;
}
.bc-bo-loc {
  border-bottom: 1px solid var(--mau-vien);
  padding-bottom: 16px;
  margin-bottom: 24px;
}
.bc-bo-loc summary {
  cursor: pointer;
  font-size: 0.78rem;
  color: var(--mau-phu);
  min-height: 32px;
}
.bc-bo-loc summary span {
  float: right;
  font-variant-numeric: tabular-nums;
}
.bc-bo-loc form {
  display: flex;
  flex-wrap: wrap;
  align-items: end;
  gap: 12px;
  padding-top: 16px;
}
.bc-bo-loc label {
  display: grid;
  gap: 7px;
  font-size: 0.75rem;
  font-weight: 600;
  flex: 1;
  min-width: 140px;
}
.bc-bo-loc :is(input, select, button) {
  min-height: 40px;
  font-size: 0.8rem;
  border-radius: 8px;
}
.bc-so-lieu {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin: 0 0 24px;
}
.bc-card {
  padding: 20px 24px;
  border-radius: 8px;
  border: 1px solid var(--mau-vien);
  background: var(--mau-the);
  box-shadow: none;
  min-width: 0;
}
.bc-card-canh-bao {
  background: color-mix(in srgb, var(--mau-chinh) 6%, var(--mau-the));
  border-color: color-mix(in srgb, var(--mau-chinh) 22%, var(--mau-vien));
}
.bc-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}
.bc-icon {
  width: 42px;
  height: 42px;
  display: inline-grid;
  place-items: center;
  border-radius: 8px;
  flex-shrink: 0;
  font-size: 1.2rem;
}
.bc-cam {
  color: light-dark(#b84308, #ff945f);
  background: rgb(249 115 22 / 0.1);
}
.bc-xanh {
  color: light-dark(#047857, #4cdbb0);
  background: rgb(16 185 129 / 0.1);
}
.bc-lam {
  color: light-dark(#1d4ed8, #79a9ff);
  background: rgb(59 130 246 / 0.1);
}
.bc-tim {
  color: light-dark(var(--mau-thong-tin), var(--mau-thong-tin));
  background: rgb(139 92 246 / 0.1);
}
.bc-cham {
  color: light-dark(#4338ca, #a5a2ff);
  background: rgb(99 102 241 / 0.1);
}
.bc-hong {
  color: light-dark(#be123c, #ff8ca5);
  background: rgb(244 63 94 / 0.1);
}
.bc-cam-text {
  color: var(--mau-chinh);
}
.bc-xanh-text {
  color: light-dark(#047857, #4cdbb0);
}
.bc-hong-text {
  color: light-dark(#be123c, #ff8ca5);
}
.bc-lam-text {
  color: light-dark(#1d4ed8, #79a9ff);
}
.bc-tim-text {
  color: light-dark(var(--mau-thong-tin), var(--mau-thong-tin));
}
.bc-cham-text {
  color: light-dark(#4338ca, #a5a2ff);
}
.bc-pham-vi,
.bc-chip {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  border-radius: 8px;
  padding: 5px 9px;
  color: var(--mau-phu);
  background: var(--mau-the-sub);
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}
.bc-pham-vi.bc-cam {
  color: var(--mau-chinh);
  background: var(--mau-chinh-nhat);
}
.bc-card dt {
  font-size: 0.83rem;
  font-weight: 550;
  color: var(--mau-phu);
}
.bc-card dd {
  margin: 8px 0 8px;
  font-size: 1.75rem;
  line-height: 1.2;
  font-weight: 700;
  letter-spacing: 0;
  font-variant-numeric: tabular-nums;
  overflow-wrap: anywhere;
}
.bc-card small {
  color: var(--mau-phu);
  font-size: 0.75rem;
}
.bc-panel {
  min-width: 0;
  padding: 24px;
  background: var(--mau-the);
  border: 1px solid var(--mau-vien);
  border-radius: 8px;
  box-shadow: var(--bong-nhe);
}
.bc-panel-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}
.bc-panel h2 {
  font-size: 1.03rem;
  font-weight: 700;
  letter-spacing: 0;
  margin: 0;
}
.bc-panel h3 {
  font-size: 0.85rem;
  font-weight: 650;
  margin: 0;
}
.bc-panel-heading p {
  color: var(--mau-phu);
  font-size: 0.75rem;
  margin: 6px 0 0;
}
.bc-link {
  display: inline-flex;
  gap: 7px;
  align-items: center;
  color: var(--mau-chinh);
  font-weight: 600;
  font-size: 0.75rem;
  flex-shrink: 0;
  min-height: 40px;
}
.bc-cot-chinh {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
  align-items: start;
  gap: 24px;
}
.bc-cot-deu {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  align-items: start;
  gap: 24px;
}
.bc-legends {
  display: flex;
  gap: 20px;
  font-size: 0.75rem;
  color: var(--mau-phu);
  align-items: center;
  margin: 4px 0 12px;
  flex-wrap: wrap;
}
.bc-legends span {
  display: inline-flex;
  gap: 6px;
  align-items: center;
}
.bc-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
}
.bc-dot.cam {
  background: #f97316;
}
.bc-bieu-do {
  display: block;
  width: 100%;
  margin-top: 8px;
  overflow: visible;
}
.bc-grid-line {
  stroke: var(--mau-vien);
  stroke-dasharray: 4 6;
}
.bc-truc-zero {
  stroke: var(--mau-phu);
  stroke-width: 1;
  opacity: 0.5;
}
.bc-nhan-truc {
  fill: var(--mau-phu);
  font-size: 11px;
}
.bc-duong {
  fill: none;
  stroke: #10b981;
  stroke-width: 3;
  stroke-linejoin: round;
  stroke-linecap: round;
}
.bc-duong-nhan {
  stroke: #f97316;
}
.bc-vung-nhan {
  fill: rgb(249 115 22 / 0.07);
}
.bc-diem-chon {
  fill: transparent;
  cursor: crosshair;
}
.bc-diem {
  fill: #10b981;
  stroke: var(--mau-the);
  stroke-width: 2;
}
.bc-diem-nhan {
  fill: #f97316;
}
.bc-moc-chon {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 14px;
  padding: 8px;
  background: var(--mau-the-sub);
  border-radius: 8px;
  margin-bottom: 14px;
}
.bc-moc-chon button {
  border: 0;
  border-radius: 8px;
  min-width: 40px;
  min-height: 40px;
  background: var(--mau-the);
  color: var(--mau-chu);
}
.bc-moc-chon p {
  text-align: center;
  display: grid;
  gap: 3px;
  margin: 0;
  font-size: 0.75rem;
  color: var(--mau-phu);
}
.bc-moc-chon strong {
  font-weight: 650;
  color: var(--mau-chu);
}
.bc-chi-tiet {
  margin-top: 14px;
}
.bc-chi-tiet summary {
  color: var(--mau-chinh);
  font-size: 0.75rem;
  cursor: pointer;
  padding: 8px 0;
}
.bc-chu-thich {
  color: var(--mau-phu);
  font-size: 0.75rem;
  line-height: 1.7;
  margin: 12px 0 0;
}
.bc-trang-thai {
  padding: 32px 16px;
  margin: 0;
  text-align: center;
  color: var(--mau-phu);
  font-size: 0.82rem;
  background: var(--mau-the-sub);
  border-radius: 8px;
}
.bc-tien-phu {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  padding-top: 18px;
  border-top: 1px solid var(--mau-vien);
  margin: 18px 0 0;
}
.bc-tien-phu dt {
  color: var(--mau-phu);
  font-size: 0.75rem;
  font-weight: 500;
}
.bc-tien-phu dd {
  margin: 6px 0 0;
  font-size: 0.82rem;
  font-weight: 650;
  font-variant-numeric: tabular-nums;
  overflow-wrap: anywhere;
}
.bc-viec {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 17px 0;
  border-bottom: 1px solid var(--mau-vien);
  color: var(--mau-phu);
  transition: transform 0.2s;
}
.bc-viec:hover {
  transform: translateX(3px);
  color: var(--mau-chinh);
}
.bc-viec-copy {
  flex: 1;
  display: grid;
  gap: 4px;
  min-width: 0;
  font-size: 0.75rem;
}
.bc-viec-copy strong {
  color: var(--mau-chu);
  font-size: 1.3rem;
  line-height: 1;
  font-variant-numeric: tabular-nums;
}
.bc-viec > .bi {
  font-size: 0.75rem;
}
.bc-status-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: -4px 0 16px;
}
.bc-status-HOAN_THANH {
  background: rgb(16 185 129 / 0.1);
  color: light-dark(#047857, #4cdbb0);
}
.bc-status-DA_XAC_NHAN {
  background: rgb(59 130 246 / 0.1);
  color: light-dark(#1d4ed8, #79a9ff);
}
.bc-status-CHO_XAC_NHAN {
  background: rgb(249 115 22 / 0.1);
  color: light-dark(#b84308, #ff945f);
}
.bc-status-QUA_HAN_XAC_NHAN,
.bc-status-VANG_MAT {
  background: rgb(244 63 94 / 0.1);
  color: light-dark(#be123c, #ff8ca5);
}
.bc-table-scroll {
  position: relative;
  overflow-x: auto;
  max-width: 100%;
  scrollbar-width: thin;
}
.bc-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.75rem;
}
.bc-table th {
  font-weight: 600;
}
.bc-table th,
.bc-table td {
  padding: 15px 12px;
  border-bottom: 1px solid var(--mau-vien);
  text-align: left;
  white-space: nowrap;
}
.bc-table th:first-child,
.bc-table td:first-child {
  padding-left: 0;
}
.bc-table th:last-child,
.bc-table td:last-child {
  padding-right: 0;
}
.bc-table thead th {
  color: var(--mau-phu);
  font-size: 0.75rem;
  font-weight: 500;
  padding-top: 10px;
}
.bc-table tbody tr:last-child :is(th, td) {
  border-bottom: 0;
}
.bc-table td {
  font-variant-numeric: tabular-nums;
}
.bc-table tbody tr:hover {
  background: var(--mau-the-sub);
}
.bc-row-link {
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  color: var(--mau-phu);
  border-radius: 8px;
}
.bc-row-link:hover {
  color: var(--mau-chinh);
  background: var(--mau-chinh-nhat);
}
.bc-goi-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 22px 36px;
}
.bc-goi-ten {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 9px;
  margin-bottom: 10px;
  font-size: 0.76rem;
}
.bc-goi-ten strong {
  flex: 1;
}
.bc-goi-ten > span:last-child {
  color: var(--mau-phu);
  font-size: 0.75rem;
}
.bc-rank {
  width: 24px;
  height: 24px;
  display: grid;
  place-items: center;
  border-radius: 7px;
  color: var(--mau-chinh);
  background: var(--mau-chinh-nhat);
  font-size: 0.75rem;
  font-weight: 600;
}
.bc-bar-track {
  height: 7px;
  border-radius: 99px;
  background: var(--mau-the-sub);
  overflow: hidden;
}
.bc-bar-track > div {
  height: 100%;
  border-radius: 99px;
  background: var(--mau-chinh);
}
.bc-goi:nth-child(2n) .bc-bar-track > div {
  background: var(--mau-chinh);
}
.bc-mini-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin: 0 0 18px;
}
.bc-mini-grid > div {
  background: var(--mau-the-sub);
  border-radius: 8px;
  padding: 14px 12px;
}
.bc-mini-grid dt {
  color: var(--mau-phu);
  font-size: 0.75rem;
  font-weight: 500;
}
.bc-mini-grid dd {
  font-size: 1.4rem;
  font-weight: 700;
  margin: 8px 0 0;
  letter-spacing: 0;
  font-variant-numeric: tabular-nums;
}
.bc-ten-pt {
  display: flex;
  align-items: center;
  gap: 9px;
}
.bc-ten-pt small {
  display: block;
  font-size: 0.75rem;
  font-weight: 400;
  color: var(--mau-phu);
  margin-top: 4px;
}
.bc-avatar {
  background: var(--mau-chinh-nhat);
  color: var(--mau-chinh);
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  flex-shrink: 0;
}
.bc-table-pt {
  max-height: 330px;
}
.bc-table-pt thead {
  position: sticky;
  top: 0;
  background: var(--mau-the);
}
.bc-phan-trang {
  display: flex;
  align-items: center;
  justify-content: end;
  gap: 14px;
  font-size: 0.75rem;
  margin-top: 16px;
}
.bc-ai-panel {
  background: var(--mau-the-sub);
}
.bc-ai-chart {
  margin: 24px 0;
  border-top: 1px solid var(--mau-vien);
  padding-top: 20px;
}
.bc-ai-bars {
  display: flex;
  gap: 12px;
}
.bc-ai-bar {
  flex: 1;
  min-width: 0;
  text-align: center;
  color: var(--mau-phu);
  font-size: 0.75rem;
}
.bc-ai-bar strong {
  display: block;
  font-weight: 550;
  margin-bottom: 8px;
  color: var(--mau-chu);
  font-variant-numeric: tabular-nums;
}
.bc-ai-bar-track {
  height: 100px;
  display: flex;
  align-items: end;
  justify-content: center;
  background: var(--mau-the-sub);
  border-radius: 8px;
  margin-bottom: 10px;
}
.bc-ai-bar-track > div {
  width: 100%;
  max-width: 32px;
  border-radius: 7px 7px 0 0;
  background: var(--mau-chinh);
}
.bc-token-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin: 0;
}
.bc-token-grid dt {
  color: var(--mau-phu);
  font-size: 0.75rem;
  font-weight: 500;
}
.bc-token-grid dd {
  font-size: 1rem;
  font-weight: 650;
  margin: 8px 0 0;
  font-variant-numeric: tabular-nums;
}
.bc-ai-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  background: rgb(139 92 246 / 0.07);
  color: light-dark(var(--mau-thong-tin), var(--mau-thong-tin));
  border-radius: 8px;
  min-height: 44px;
  padding: 12px 16px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-top: 24px;
}
.bc-members {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 16px;
  margin: 0;
}
.bc-members > div {
  text-align: center;
  border-right: 1px solid var(--mau-vien);
}
.bc-members > div:last-child {
  border-right: 0;
}
.bc-members dt {
  color: var(--mau-phu);
  font-size: 0.75rem;
  font-weight: 500;
}
.bc-members dd {
  margin: 10px 0 0;
  font-size: 1.8rem;
  font-weight: 700;
  letter-spacing: 0;
  font-variant-numeric: tabular-nums;
}
.bc-content-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 16px;
}
.bc-content {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: start;
  gap: 10px;
  padding: 16px;
  border-radius: 8px;
  background: var(--mau-the-sub);
  border: 1px solid transparent;
  color: var(--mau-phu);
  font-size: 0.75rem;
  transition: border-color 0.2s;
}
.bc-content:hover {
  border-color: var(--mau-chinh);
}
.bc-content strong {
  font-size: 1.6rem;
  color: var(--mau-chu);
  letter-spacing: 0;
  font-variant-numeric: tabular-nums;
}
.bc-content > .bi {
  position: absolute;
  top: 16px;
  right: 16px;
}
.bc-notices {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}
.bc-notices > :is(div, a) {
  display: flex;
  align-items: start;
  gap: 12px;
  background: var(--mau-the);
  border-radius: 8px;
  border: 1px solid var(--mau-vien);
  padding: 18px;
  color: var(--mau-chu);
}
.bc-notices p {
  display: grid;
  gap: 7px;
  margin: 0;
}
.bc-notices strong {
  font-size: 0.76rem;
  font-weight: 600;
}
.bc-notices p span {
  font-size: 0.75rem;
  line-height: 1.7;
  color: var(--mau-phu);
}
@media (max-width: 1199px) {
  .bc-cot-chinh {
    grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
  }
  .bc-cot-deu {
    grid-template-columns: 1fr;
  }
  .bc-content-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .bc-members {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
  }
  .bc-members > div:nth-child(3n) {
    border-right: 0;
  }
  .bc-notices {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 900px) {
  .bc-so-lieu {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .bc-cot-chinh {
    grid-template-columns: 1fr;
  }
  .bc-goi-list {
    grid-template-columns: 1fr;
  }
  .bc-viec-panel {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 22px;
  }
  .bc-viec-panel > :is(.bc-panel-heading, .bc-chu-thich) {
    grid-column: 1 / -1;
  }
}
@media (max-width: 575px) {
  .bao-cao-admin {
    gap: 18px;
  }
  .bc-dau {
    align-items: start;
    flex-direction: column;
    gap: 12px;
  }
  .bc-presets {
    width: 100%;
  }
  .bc-presets button {
    flex: 1;
    padding: 9px 10px;
  }
  .bc-bo-loc summary span {
    display: block;
    float: none;
    margin-top: 8px;
  }
  .bc-bo-loc label,
  .bc-bo-loc form > button {
    min-width: 100%;
  }
  .bc-so-lieu {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .bc-card {
    padding: 20px;
    border-radius: 8px;
  }
  .bc-card-top {
    margin-bottom: 12px;
  }
  .bc-card dd {
    font-size: 1.9rem;
  }
  .bc-panel {
    padding: 20px 16px;
    border-radius: 8px;
  }
  .bc-panel-heading {
    flex-wrap: wrap;
    gap: 8px;
  }
  .bc-panel h2 {
    line-height: 1.6;
  }
  .bc-viec-panel {
    grid-template-columns: 1fr;
  }
  .bc-mini-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .bc-members {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .bc-members > div:nth-child(3n) {
    border-right: 1px solid var(--mau-vien);
  }
  .bc-members > div:nth-child(2n) {
    border-right: 0;
  }
  .bc-content-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }
  .bc-content {
    padding: 12px;
  }
  .bc-tien-phu {
    grid-template-columns: 1fr;
  }
  .bc-tien-phu > div {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
  }
  .bc-tien-phu dd {
    margin: 0;
  }
  .bc-ai-bars {
    gap: 7px;
  }
  .bc-moc-chon {
    gap: 8px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .bc-card,
  .bc-viec,
  .bc-content {
    transition: none;
  }
  .bc-card:hover,
  .bc-viec:hover {
    transform: none;
  }
}
</style>
