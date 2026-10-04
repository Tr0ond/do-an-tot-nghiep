<template>
  <CaNhanLayout class="chat-layout">
    <div class="chat-page-heading">
      <div>
        <p class="eyebrow mb-1">
          <i class="bi bi-chat-dots-fill me-1 text-emerald" aria-hidden="true"></i>ĐỒNG HÀNH TẬP
          LUYỆN
        </p>
        <h1 class="h3 mb-0 fw-bold">Tin nhắn</h1>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span
          class="chat-connection"
          :class="{ 'da-ket-noi': chat.trangThai === 'connected' }"
          role="status"
        >
          <span class="chat-status-dot" aria-hidden="true"></span>
          <span>{{
            chat.trangThai === 'connected' ? 'Đã kết nối trực tiếp' : 'Đang kết nối lại'
          }}</span>
        </span>
      </div>
    </div>

    <div v-if="thongBao" class="alert alert-warning chat-global-alert" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
      <span>{{ thongBao }}</span>
    </div>

    <div
      class="chat-workspace"
      :class="{ 'co-hoi-thoai': idHoiThoai, 'st-chat-context-open': hoiThoai }"
    >
      <!-- Cột trái: Danh sách hội thoại -->
      <aside class="chat-sidebar" aria-label="Danh sách hội thoại">
        <div class="chat-sidebar-heading">
          <div class="d-flex align-items-center gap-2">
            <h2 class="h6 mb-0 fw-bold">Hội thoại</h2>
            <span class="badge bg-secondary-subtle text-body border small">{{ meta.total }}</span>
          </div>
          <button
            v-if="danhSach.length"
            type="button"
            class="chat-icon-button small-btn"
            title="Tải lại danh sách"
            :disabled="dangTaiDanhSach"
            aria-label="Tải lại danh sách hội thoại"
            @click="taiDanhSach"
          >
            <i
              class="bi bi-arrow-clockwise"
              :class="{ 'spin-anim': dangTaiDanhSach }"
              aria-hidden="true"
            ></i>
          </button>
        </div>

        <form class="chat-search" @submit.prevent="timKiem">
          <label class="visually-hidden" for="tim-hoi-thoai">Tìm người trò chuyện</label>
          <i class="bi bi-search chat-search-icon" aria-hidden="true"></i>
          <input
            id="tim-hoi-thoai"
            v-model="tuKhoa"
            type="search"
            maxlength="100"
            placeholder="Tìm theo tên…"
          />
          <button
            v-if="tuKhoa"
            type="button"
            class="chat-icon-button small-btn chat-search-clear"
            aria-label="Xóa tìm kiếm"
            @click="xoaTimKiem"
          >
            <i class="bi bi-x-lg" aria-hidden="true"></i>
          </button>
          <button v-else class="chat-icon-button small-btn" aria-label="Tìm hội thoại">
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </button>
        </form>

        <div v-if="dangTaiDanhSach" class="chat-state" role="status">
          <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
          <p class="small text-muted mb-0">Đang tải hội thoại…</p>
        </div>

        <div v-else-if="loiDanhSach" class="chat-state" role="alert">
          <i
            class="bi bi-exclamation-triangle text-danger fs-4 mb-2 d-block"
            aria-hidden="true"
          ></i>
          <p class="small mb-2">{{ loiDanhSach }}</p>
          <button class="btn btn-outline-secondary btn-sm" @click="taiDanhSach">Thử lại</button>
        </div>

        <div v-else-if="!danhSach.length" class="chat-state">
          <div class="chat-empty-sidebar-icon mb-2">
            <i class="bi bi-chat-square-text text-muted fs-3" aria-hidden="true"></i>
          </div>
          <p class="small text-muted mb-0">
            {{
              tuKhoa
                ? 'Không tìm thấy hội thoại phù hợp.'
                : laKhach
                  ? 'Bạn sẽ có hội thoại khi được phân công PT.'
                  : 'Chưa có khách hàng được phân công.'
            }}
          </p>
        </div>

        <div v-else class="chat-conversation-list">
          <RouterLink
            v-for="hoi in danhSach"
            :key="hoi.id"
            :to="`${duongDan}/${hoi.id}`"
            class="chat-conversation"
            :class="{ selected: hoi.id === idHoiThoai }"
            :aria-current="hoi.id === idHoiThoai ? 'page' : undefined"
          >
            <div class="chat-avatar-wrapper">
              <span class="chat-avatar" aria-hidden="true">{{
                hoi.doi_phuong.ho_ten.charAt(0).toUpperCase()
              }}</span>
              <span
                class="chat-avatar-status"
                :class="hoi.da_ket_thuc ? 'status-ended' : 'status-active'"
                :title="hoi.da_ket_thuc ? 'Phân công đã kết thúc' : 'Đang hoạt động'"
              ></span>
            </div>
            <span class="chat-conversation-copy">
              <div class="chat-conv-row">
                <strong class="chat-conv-name">{{ hoi.doi_phuong.ho_ten }}</strong>
                <time v-if="hoi.tin_cuoi_luc" class="chat-conv-time">{{
                  dinhDangGio(hoi.tin_cuoi_luc)
                }}</time>
              </div>
              <div class="chat-conv-subrow">
                <small class="chat-conv-snippet text-truncate">
                  <i v-if="hoi.tin_cuoi?.includes('ảnh')" class="bi bi-image me-1"></i>
                  {{ hoi.tin_cuoi || 'Bắt đầu trao đổi về việc tập luyện' }}
                </small>
                <span
                  v-if="hoi.so_chua_doc"
                  class="chat-unread"
                  :aria-label="`${hoi.so_chua_doc} tin chưa đọc`"
                >
                  {{ hoi.so_chua_doc > 99 ? '99+' : hoi.so_chua_doc }}
                </span>
              </div>
              <span v-if="hoi.da_ket_thuc" class="chat-archive-label">
                <i class="bi bi-archive me-1"></i>Phân công đã kết thúc
              </span>
            </span>
          </RouterLink>
        </div>

        <nav
          v-if="meta.last_page > 1"
          class="chat-list-pagination"
          aria-label="Phân trang hội thoại"
        >
          <button
            class="chat-icon-button small-btn"
            :disabled="page <= 1"
            aria-label="Trang trước"
            @click="doiTrang(-1)"
          >
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
          </button>
          <span class="chat-page-number">{{ page }} / {{ meta.last_page }}</span>
          <button
            class="chat-icon-button small-btn"
            :disabled="page >= meta.last_page"
            aria-label="Trang sau"
            @click="doiTrang(1)"
          >
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </button>
        </nav>
      </aside>

      <!-- Cột phải: Nội dung hội thoại -->
      <section class="chat-panel" aria-label="Nội dung hội thoại">
        <!-- Khi chưa chọn hội thoại -->
        <div v-if="!idHoiThoai" class="chat-empty">
          <div class="chat-empty-halo">
            <span class="chat-empty-symbol">
              <i class="bi bi-chat-heart-fill" aria-hidden="true"></i>
            </span>
          </div>
          <h2 class="h5 fw-bold mt-3 mb-2">
            Trao đổi trực tiếp cùng {{ laKhach ? 'Huấn Luyện Viên' : 'Học Viên' }}
          </h2>
          <p class="text-muted small mb-4 chat-empty-desc">
            {{
              laKhach
                ? 'Hỏi đáp kỹ thuật động tác, trao đổi form bài tập, chế độ dinh dưỡng và nhận hướng dẫn cá nhân hóa từ huấn luyện viên.'
                : 'Theo dõi tình hình tập luyện của học viên, giải đáp thắc mắc chuyên môn và hỗ trợ kỹ thuật kịp thời.'
            }}
          </p>
          <div class="chat-empty-features">
            <span class="badge bg-secondary-subtle text-body border py-2 px-3">
              <i class="bi bi-shield-check text-emerald me-1"></i>Kênh riêng tư 1-1
            </span>
            <span class="badge bg-secondary-subtle text-body border py-2 px-3">
              <i class="bi bi-lightning-charge text-warning me-1"></i>Đồng bộ tức thì
            </span>
            <span class="badge bg-secondary-subtle text-body border py-2 px-3">
              <i class="bi bi-images text-primary me-1"></i>Đính kèm tối đa 4 ảnh
            </span>
          </div>
        </div>

        <!-- Khi đã chọn hội thoại -->
        <template v-else>
          <header class="chat-panel-header">
            <RouterLink
              :to="duongDan"
              class="chat-icon-button chat-back"
              aria-label="Quay lại danh sách hội thoại"
            >
              <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </RouterLink>

            <div class="chat-avatar-wrapper">
              <span class="chat-avatar" aria-hidden="true">{{
                hoiThoai?.doi_phuong.ho_ten.charAt(0).toUpperCase() || '?'
              }}</span>
              <span
                v-if="hoiThoai"
                class="chat-avatar-status"
                :class="hoiThoai.da_ket_thuc ? 'status-ended' : 'status-active'"
              ></span>
            </div>

            <div class="chat-panel-header-info flex-grow-1 min-width-0">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <h2 class="h6 mb-0 fw-bold">
                  {{ hoiThoai?.doi_phuong.ho_ten || 'Đang tải hội thoại…' }}
                </h2>
                <span
                  v-if="hoiThoai?.da_ket_thuc"
                  class="badge bg-secondary-subtle text-body border small"
                >
                  <i class="bi bi-archive me-1"></i>Lịch sử trước đây
                </span>
                <span
                  v-else-if="hoiThoai"
                  class="badge bg-success-subtle text-success border border-success-subtle small"
                >
                  <i class="bi bi-shield-check me-1"></i>Đang phụ trách
                </span>
              </div>
              <small class="text-muted d-block">
                {{
                  hoiThoai?.da_ket_thuc
                    ? 'Phân công đã kết thúc'
                    : laKhach
                      ? 'Huấn luyện viên cá nhân'
                      : 'Học viên đang phụ trách'
                }}
              </small>
            </div>

            <div class="chat-panel-header-actions">
              <button
                type="button"
                class="chat-icon-button small-btn"
                title="Đồng bộ tin nhắn mới"
                aria-label="Đồng bộ tin nhắn mới"
                :disabled="dangDongBo || dangTaiTin"
                @click="taiTinMoi"
              >
                <i
                  class="bi bi-arrow-repeat"
                  :class="{ 'spin-anim': dangDongBo }"
                  aria-hidden="true"
                ></i>
              </button>
            </div>
          </header>

          <div
            ref="khungTin"
            class="chat-messages"
            role="log"
            aria-label="Tin nhắn"
            aria-live="polite"
            :aria-busy="dangTaiTin"
            @scroll.passive="cuonTin"
          >
            <div v-if="dangTaiTin" class="chat-state" role="status">
              <div class="spinner-border text-primary mb-2" role="status"></div>
              <p class="small text-muted mb-0">Đang tải tin nhắn…</p>
            </div>

            <div v-else-if="loiTin" class="chat-state" role="alert">
              <i
                class="bi bi-exclamation-triangle text-danger fs-3 mb-2 d-block"
                aria-hidden="true"
              ></i>
              <p class="small mb-2">{{ loiTin }}</p>
              <button class="btn btn-outline-secondary btn-sm" @click="taiTinDau">Thử lại</button>
            </div>

            <template v-else>
              <button
                v-if="conTinCu"
                class="chat-load-older"
                :disabled="dangTaiCu"
                @click="taiTinCu"
              >
                <span
                  v-if="dangTaiCu"
                  class="spinner-border spinner-border-sm me-1"
                  role="status"
                ></span>
                <i v-else class="bi bi-clock-history me-1" aria-hidden="true"></i>
                {{ dangTaiCu ? 'Đang tải…' : 'Xem tin nhắn trước' }}
              </button>

              <div v-if="!cacTin.length" class="chat-state">
                <div class="chat-empty-bubble-icon mb-2">
                  <i class="bi bi-chat-square-dots text-muted fs-3" aria-hidden="true"></i>
                </div>
                <p class="small text-muted mb-0">Chưa có tin nhắn. Hãy bắt đầu cuộc trò chuyện.</p>
              </div>

              <article
                v-for="tin in cacTin"
                :key="`${tin.nguoi_gui_id}:${tin.client_message_id}`"
                class="chat-message"
                :class="{ 'tin-cua-toi': tin.nguoi_gui_id === xacThuc.taiKhoan?.id }"
              >
                <span
                  v-if="tin.nguoi_gui_id !== xacThuc.taiKhoan?.id"
                  class="chat-avatar small"
                  aria-hidden="true"
                >
                  {{ hoiThoai?.doi_phuong.ho_ten.charAt(0).toUpperCase() }}
                </span>

                <div class="chat-message-body">
                  <div v-if="tin.anh?.length || tin.tep_anh?.length" class="chat-image-grid">
                    <AnhTinNhan
                      v-for="(anh, viTri) in tin.anh?.length ? tin.anh : tin.tep_anh"
                      :key="`${tin.id || tin.client_message_id}:${viTri}`"
                      :anh="anh"
                      :hoi-thoai-id="tin.hoi_thoai_id"
                      :tin-id="tin.id || null"
                      :src-tam="tin.id ? '' : anh.url"
                      @xem-anh="moAnh"
                      @da-tai="anhDaTai"
                      @mat-quyen="kiemTraQuyenAnh"
                    />
                  </div>

                  <p v-if="tin.noi_dung" class="chat-bubble">{{ tin.noi_dung }}</p>

                  <div class="chat-message-info">
                    <time :datetime="tin.created_at">{{ dinhDangGio(tin.created_at) }}</time>
                    <span
                      v-if="tin.nguoi_gui_id === xacThuc.taiKhoan?.id"
                      class="chat-delivery-status"
                    >
                      <template v-if="!tin.id">
                        <span v-if="tin.trang_thai === 'loi'" class="text-danger fw-semibold">
                          <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>Gửi chưa
                          thành công
                        </span>
                        <span v-else class="text-muted">
                          <span class="spinner-border spinner-border-sm me-1" role="status"></span
                          >Đang gửi…
                        </span>
                      </template>
                      <template v-else>
                        <span
                          v-if="tin.id <= (hoiThoai?.cursor_doi_phuong || 0)"
                          class="text-emerald fw-semibold"
                        >
                          <i class="bi bi-check2-all me-1" aria-hidden="true"></i>Đã đọc
                        </span>
                        <span v-else class="text-muted">
                          <i class="bi bi-check2 me-1" aria-hidden="true"></i>Đã lưu
                        </span>
                      </template>
                    </span>
                    <button
                      v-if="!tin.id && tin.trang_thai === 'loi' && hoiThoai?.co_the_gui"
                      class="chat-retry"
                      @click="guiTin(tin)"
                    >
                      <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Gửi lại
                    </button>
                  </div>
                  <p v-if="tin.loi" class="chat-message-error" role="alert">{{ tin.loi }}</p>
                </div>
              </article>
            </template>
          </div>

          <button v-if="!ganCuoi && coTinMoi" class="chat-new-message" @click="xuongCuoi">
            <span>Có tin nhắn mới</span>
            <i class="bi bi-arrow-down-circle-fill ms-1" aria-hidden="true"></i>
          </button>

          <!-- Khi hội thoại bị khóa gửi -->
          <div v-if="hoiThoai && !hoiThoai.co_the_gui" class="chat-readonly" role="status">
            <i class="bi bi-info-circle-fill me-2 fs-5" aria-hidden="true"></i>
            <div>
              <strong class="d-block mb-1">Chế độ xem lại lịch sử</strong>
              <p class="mb-0 small">
                {{
                  hoiThoai.da_ket_thuc
                    ? 'Phân công đã kết thúc. Bạn vẫn có thể xem lại lịch sử.'
                    : 'Đối phương đang tạm ngừng hoạt động. Chưa thể gửi tin nhắn.'
                }}
              </p>
            </div>
          </div>

          <!-- Vùng soạn tin nhắn -->
          <form
            v-else
            class="chat-composer"
            :class="{ 'dang-keo-anh': dangKeoAnh }"
            @submit.prevent="guiTin()"
            @paste="danAnh"
            @dragover.prevent="dangKeoAnh = true"
            @dragleave.self="dangKeoAnh = false"
            @drop.prevent="thaAnh"
          >
            <!-- Dropzone overlay khi đang kéo tệp ảnh -->
            <div v-if="dangKeoAnh" class="chat-dropzone-overlay">
              <i class="bi bi-cloud-arrow-up-fill fs-1 text-primary mb-2" aria-hidden="true"></i>
              <strong class="fs-6">Thả ảnh vào đây để đính kèm</strong>
              <small class="text-muted">Tối đa 4 ảnh JPG, PNG, WebP (≤ 5MB)</small>
            </div>

            <input
              ref="chonAnh"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              multiple
              class="visually-hidden"
              tabindex="-1"
              aria-label="Tệp ảnh đính kèm"
              @change="chonTepAnh"
            />

            <!-- Danh sách ảnh xem trước chuẩn bị gửi -->
            <div v-if="anhCho.length" class="chat-attachment-preview" aria-label="Ảnh chuẩn bị gửi">
              <div v-for="(anh, viTri) in anhCho" :key="anh.url" class="chat-attachment">
                <img :src="anh.url" :alt="anh.ten" />
                <button
                  type="button"
                  class="chat-icon-button chat-attachment-remove"
                  :aria-label="`Bỏ ảnh ${anh.ten}`"
                  @click="boAnh(viTri)"
                >
                  <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
              </div>
            </div>

            <p v-if="loiAnh" class="chat-attachment-error" role="alert">
              <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>{{ loiAnh }}
            </p>

            <div class="chat-composer-actions">
              <button
                type="button"
                class="btn-composer-tool"
                aria-label="Chọn ảnh gửi trong chat"
                :title="
                  anhCho.length >= 4
                    ? 'Đã đạt tối đa 4 ảnh'
                    : 'Đính kèm ảnh — tối đa 4 JPG, PNG, WebP, 5 MB/ảnh; có thể kéo thả hoặc dán'
                "
                :disabled="!hoiThoai?.co_the_gui || dangGui || dangTaiTin || anhCho.length >= 4"
                @click="$refs.chonAnh.click()"
              >
                <i class="bi bi-image" aria-hidden="true"></i>
              </button>
              <span class="chat-composer-help">Nội dung tin nhắn</span>
              <small class="chat-char-counter">
                <span v-if="anhCho.length" class="chat-attachment-count">
                  {{ anhCho.length }}/4 ảnh ·
                </span>
                {{ noiDung.length }}/4000
              </small>
            </div>

            <div class="chat-composer-box">
              <label class="visually-hidden" for="noi-dung-tin-nhan">Nội dung tin nhắn</label>
              <textarea
                id="noi-dung-tin-nhan"
                ref="noiDungTin"
                v-model="noiDung"
                rows="1"
                maxlength="4000"
                placeholder="Nhập tin nhắn…"
                :disabled="!hoiThoai || dangTaiTin"
                @keydown.enter.exact="guiBangEnter"
              ></textarea>

              <button
                type="submit"
                class="btn btn-primary btn-chat-send"
                :disabled="
                  !hoiThoai?.co_the_gui ||
                  (!noiDung.trim() && !anhCho.length) ||
                  dangGui ||
                  dangTaiTin
                "
                aria-label="Gửi tin nhắn"
              >
                <span
                  v-if="dangGui"
                  class="spinner-border spinner-border-sm me-1"
                  role="status"
                ></span>
                <i v-else class="bi bi-send-fill" aria-hidden="true"></i>
                <span>Gửi</span>
              </button>
            </div>
          </form>
        </template>
      </section>
      <aside v-if="hoiThoai" class="st-chat-context" aria-label="Thông tin hội thoại">
        <span class="st-profile-avatar" aria-hidden="true">{{
          hoiThoai.doi_phuong.ho_ten.charAt(0).toUpperCase()
        }}</span>
        <h2>{{ hoiThoai.doi_phuong.ho_ten }}</h2>
        <p>{{ laKhach ? 'Huấn luyện viên' : 'Học viên' }}</p>
        <dl class="st-rail-facts">
          <div>
            <dt>Phân công</dt>
            <dd>{{ hoiThoai.da_ket_thuc ? 'Đã kết thúc' : 'Đang hoạt động' }}</dd>
          </div>
          <div>
            <dt>Trao đổi</dt>
            <dd>{{ hoiThoai.co_the_gui ? 'Có thể gửi tin' : 'Chỉ xem lịch sử' }}</dd>
          </div>
        </dl>
        <nav v-if="!hoiThoai.da_ket_thuc" class="st-quick-links" aria-label="Theo dõi tập luyện">
          <RouterLink :to="laKhach ? '/khach-hang/lich-hen' : '/pt/lich-hen'"
            ><i class="bi bi-calendar3" aria-hidden="true"></i>Lịch hẹn</RouterLink
          >
          <RouterLink v-if="laKhach" to="/khach-hang/goi-cua-toi"
            ><i class="bi bi-box-seam" aria-hidden="true"></i>Gói đang dùng</RouterLink
          >
          <RouterLink v-else to="/pt/hoc-vien"
            ><i class="bi bi-people" aria-hidden="true"></i>Danh sách học viên</RouterLink
          >
        </nav>
      </aside>
    </div>

    <!-- Hộp thoại phóng to ảnh -->
    <dialog
      ref="anhLon"
      class="chat-image-dialog"
      aria-label="Xem ảnh trong hội thoại"
      @close="anhDangXem = null"
      @click="bamNenAnh"
    >
      <template v-if="anhDangXem">
        <div class="chat-image-dialog-header">
          <div class="d-flex align-items-center gap-2 text-truncate">
            <i class="bi bi-image text-emerald" aria-hidden="true"></i>
            <span class="text-truncate fw-semibold">{{ anhDangXem.ten }}</span>
          </div>
          <button
            type="button"
            class="chat-icon-button chat-dialog-close"
            aria-label="Đóng ảnh"
            autofocus
            @click="dongAnh"
          >
            <i class="bi bi-x-lg" aria-hidden="true"></i>
          </button>
        </div>
        <div class="chat-image-dialog-content">
          <img :src="anhDangXem.src" :alt="anhDangXem.ten" />
        </div>
      </template>
    </dialog>
  </CaNhanLayout>
</template>

<script>
import CaNhanLayout from '../../layouts/CaNhanLayout.vue'
import { useXacThucStore } from '../../stores/xacThuc'
import { useChatStore } from '../../stores/chat'
import chatService from '../../services/chatService'
import { hopNhatTin, dinhDangGioChat } from '../../utils/chat'
import { kiemTraAnhChat } from '../../utils/chat'
import AnhTinNhan from '../../components/AnhTinNhan.vue'
import { layLoiApi } from '../../utils/loiApi'
import '../../assets/chat.css'

export default {
  name: 'TinNhan',
  components: { CaNhanLayout, AnhTinNhan },
  data() {
    return {
      danhSach: [],
      meta: { total: 0, last_page: 1 },
      page: 1,
      tuKhoa: '',
      hoiThoai: null,
      tinNhan: [],
      tinCho: [],
      noiDung: '',
      anhCho: [],
      loiAnh: '',
      dangKeoAnh: false,
      anhDangXem: null,
      boHuyGui: [],
      dangTaiDanhSach: false,
      dangTaiTin: false,
      dangTaiCu: false,
      dangDongBo: false,
      canDongBoLai: false,
      conTinCu: false,
      ganCuoi: true,
      coTinMoi: false,
      thongBao: '',
      loiDanhSach: '',
      loiTin: '',
      cursorDongBo: 0,
      cursorDaGui: 0,
      luotTin: 0,
      luotDanhSach: 0,
      boHuyTin: null,
      boHuyDanhSach: null,
      daRoiTrang: false,
    }
  },
  computed: {
    xacThuc() {
      return useXacThucStore()
    },
    chat() {
      return useChatStore()
    },
    laKhach() {
      return this.xacThuc.taiKhoan?.vai_tro === 'KHACH_HANG'
    },
    duongDan() {
      return `/${this.laKhach ? 'khach-hang' : 'pt'}/tin-nhan`
    },
    idHoiThoai() {
      return Number(this.$route.params.id) || null
    },
    cacTin() {
      return hopNhatTin(
        this.tinNhan,
        this.tinCho.filter((tin) => tin.hoi_thoai_id === this.idHoiThoai),
      )
    },
    dangGui() {
      return this.tinCho.some(
        (tin) => tin.hoi_thoai_id === this.idHoiThoai && tin.trang_thai === 'dang-gui',
      )
    },
  },
  watch: {
    noiDung() {
      void this.coGianOChat()
    },
    '$route.params.id'() {
      void this.taiTinDau()
    },
    'chat.phienDongBo'() {
      void this.taiDanhSach()
      void this.taiTinMoi()
    },
    'xacThuc.taiKhoan'(nguoi) {
      if (!nguoi) {
        this.luotTin++
        this.luotDanhSach++
        this.boHuyTin?.abort()
        this.boHuyDanhSach?.abort()
        this.hoiThoai = null
        this.donAnhCho()
        this.donTinCho()
        this.dongAnh()
        this.boHuyGui.forEach((bo) => bo.abort())
        this.tinNhan = this.tinCho = this.danhSach = []
        this.noiDung = ''
        this.thongBao = 'Phiên đăng nhập đã hết hiệu lực. Vui lòng đăng nhập lại.'
      }
    },
  },
  mounted() {
    void this.taiDanhSach()
    void this.taiTinDau()
  },
  beforeUnmount() {
    this.daRoiTrang = true
    this.luotTin++
    this.luotDanhSach++
    this.boHuyTin?.abort()
    this.boHuyDanhSach?.abort()
    this.boHuyGui.forEach((bo) => bo.abort())
    this.dongAnh()
    this.donAnhCho()
    this.donTinCho()
  },
  methods: {
    dinhDangGio: dinhDangGioChat,
    async coGianOChat() {
      await this.$nextTick()
      const oNhap = this.$refs.noiDungTin
      if (!oNhap) return
      // Đo lại từ một dòng để ô nhập thu về khi xóa hoặc gửi tin.
      oNhap.style.height = '0px'
      oNhap.style.height = `${Math.min(oNhap.scrollHeight, 116)}px`
    },
    themAnh(cacTep) {
      if (!this.hoiThoai?.co_the_gui || this.dangGui || this.dangTaiTin) return
      const tep = Array.from(cacTep)
      if (!tep.length) return
      this.loiAnh = kiemTraAnhChat(tep, this.anhCho.length)
      if (this.loiAnh) return
      this.anhCho.push(
        ...tep.map((file) => ({ file, ten: file.name, url: URL.createObjectURL(file) })),
      )
    },
    chonTepAnh(suKien) {
      this.themAnh(suKien.target.files)
      suKien.target.value = ''
    },
    danAnh(suKien) {
      if (suKien.clipboardData?.files.length) {
        suKien.preventDefault()
        this.themAnh(suKien.clipboardData.files)
      }
    },
    thaAnh(suKien) {
      this.dangKeoAnh = false
      this.themAnh(suKien.dataTransfer.files)
    },
    boAnh(viTri) {
      const [anh] = this.anhCho.splice(viTri, 1)
      if (anh) URL.revokeObjectURL(anh.url)
      this.loiAnh = ''
    },
    donAnhCho() {
      this.anhCho.forEach((anh) => URL.revokeObjectURL(anh.url))
      this.anhCho = []
      this.loiAnh = ''
      this.dangKeoAnh = false
    },
    donTinCho() {
      this.tinCho.forEach((tin) => tin.tep_anh?.forEach((anh) => URL.revokeObjectURL(anh.url)))
      this.tinCho = []
    },
    donTinDaLuu() {
      this.tinCho = this.tinCho.filter((tin) => {
        const daLuu = this.tinNhan.some(
          (luu) =>
            luu.id &&
            luu.hoi_thoai_id === tin.hoi_thoai_id &&
            luu.nguoi_gui_id === tin.nguoi_gui_id &&
            luu.client_message_id === tin.client_message_id,
        )
        if (daLuu) {
          tin.tep_anh?.forEach((anh) => URL.revokeObjectURL(anh.url))
          this.dongAnh()
        }
        return !daLuu
      })
    },
    async moAnh(anh) {
      this.anhDangXem = anh
      await this.$nextTick()
      this.$refs.anhLon?.showModal()
    },
    dongAnh() {
      if (this.$refs.anhLon?.open) this.$refs.anhLon.close()
      this.anhDangXem = null
    },
    bamNenAnh(suKien) {
      if (suKien.target !== this.$refs.anhLon) return
      const khung = this.$refs.anhLon.getBoundingClientRect()
      if (
        suKien.clientX < khung.left ||
        suKien.clientX > khung.right ||
        suKien.clientY < khung.top ||
        suKien.clientY > khung.bottom
      )
        this.dongAnh()
    },
    anhDaTai() {
      if (this.ganCuoi) this.xuongCuoi()
    },
    kiemTraQuyenAnh(loi) {
      if (loi.response?.status === 404) void this.taiTinMoi()
      else this.xuLyQuyen(loi)
    },
    guiBangEnter(suKien) {
      if (suKien.isComposing) return
      suKien.preventDefault()
      void this.guiTin()
    },
    async taiDanhSach() {
      if (!this.xacThuc.taiKhoan || this.daRoiTrang) return
      const luot = ++this.luotDanhSach
      this.boHuyDanhSach?.abort()
      this.boHuyDanhSach = new AbortController()
      this.dangTaiDanhSach = !this.danhSach.length
      try {
        const r = await chatService.taiHoiThoai(
          { page: this.page, tu_khoa: this.tuKhoa },
          this.boHuyDanhSach.signal,
        )
        if (luot !== this.luotDanhSach || this.daRoiTrang) return
        this.danhSach = r.data
        this.meta = r.meta
        this.loiDanhSach = ''
      } catch (loi) {
        if (luot !== this.luotDanhSach || this.daRoiTrang) return
        this.loiDanhSach = layLoiApi(loi).thongBao
        this.xuLyQuyen(loi)
      } finally {
        if (luot === this.luotDanhSach) this.dangTaiDanhSach = false
      }
    },
    timKiem() {
      this.page = 1
      void this.taiDanhSach()
    },
    xoaTimKiem() {
      this.tuKhoa = ''
      this.timKiem()
    },
    doiTrang(buoc) {
      this.page += buoc
      void this.taiDanhSach()
    },
    async taiTinDau() {
      const luot = ++this.luotTin
      this.boHuyTin?.abort()
      this.boHuyTin = new AbortController()
      this.hoiThoai = null
      this.tinNhan = []
      this.noiDung = ''
      this.donAnhCho()
      this.dongAnh()
      this.loiTin = ''
      this.cursorDongBo = this.cursorDaGui = 0
      this.ganCuoi = true
      this.coTinMoi = false
      this.conTinCu = false
      this.dangTaiTin = false
      if (!this.idHoiThoai || !this.xacThuc.taiKhoan) return
      const id = this.idHoiThoai
      this.dangTaiTin = true
      try {
        const r = await chatService.taiTin(id, {}, this.boHuyTin.signal)
        if (luot !== this.luotTin || this.daRoiTrang) return
        this.hoiThoai = r.data.hoi_thoai
        this.tinNhan = r.data.tin_nhan
        this.donTinDaLuu()
        this.cursorDongBo = this.tinNhan.at(-1)?.id || 0
        this.cursorDaGui = this.hoiThoai.cursor_da_doc
        this.conTinCu = r.data.con_tin
      } catch (loi) {
        if (luot !== this.luotTin || this.daRoiTrang) return
        this.loiTin = layLoiApi(loi).thongBao
        this.xuLyQuyen(loi)
      } finally {
        if (luot === this.luotTin) {
          this.dangTaiTin = false
          await this.$nextTick()
          this.xuongCuoi()
          // Bao phủ event tới trong lúc tải trang đầu và HTTP response tới chậm.
          void this.taiTinMoi()
        }
      }
    },
    async taiTinMoi() {
      if (!this.hoiThoai || this.daRoiTrang || this.dangTaiTin) return
      if (this.dangDongBo) {
        this.canDongBoLai = true
        return
      }
      this.dangDongBo = true
      const luot = this.luotTin
      const id = this.idHoiThoai
      try {
        let conTin
        do {
          this.canDongBoLai = false
          const r = await chatService.taiTin(
            id,
            { after_id: this.cursorDongBo },
            this.boHuyTin.signal,
          )
          if (luot !== this.luotTin || this.daRoiTrang) return
          this.hoiThoai = r.data.hoi_thoai
          const tinMoi = r.data.tin_nhan
          this.tinNhan = hopNhatTin(this.tinNhan, tinMoi)
          this.donTinDaLuu()
          if (tinMoi.length) {
            this.cursorDongBo = tinMoi.at(-1).id
            this.coTinMoi = true
            await this.$nextTick()
            if (this.ganCuoi) this.xuongCuoi()
          }
          conTin = r.data.con_tin
        } while (conTin || this.canDongBoLai)
        void this.ghiDaDoc()
      } catch (loi) {
        if (luot === this.luotTin && !this.daRoiTrang) {
          this.xuLyQuyen(loi)
          if (![401, 403, 404, 419].includes(loi.response?.status))
            this.thongBao = layLoiApi(loi).thongBao
        }
      } finally {
        this.dangDongBo = false
        if (luot !== this.luotTin && !this.daRoiTrang) void this.taiTinMoi()
      }
    },
    async taiTinCu() {
      if (this.dangTaiCu || !this.tinNhan.length) return
      const luot = this.luotTin
      const id = this.idHoiThoai
      const khung = this.$refs.khungTin
      const viTri = khung.scrollTop
      const cao = khung.scrollHeight
      this.dangTaiCu = true
      try {
        const r = await chatService.taiTin(
          id,
          { before_id: this.tinNhan[0].id },
          this.boHuyTin.signal,
        )
        if (luot !== this.luotTin || this.daRoiTrang) return
        this.tinNhan = hopNhatTin(r.data.tin_nhan, this.tinNhan)
        this.conTinCu = r.data.con_tin
        await this.$nextTick()
        khung.scrollTop = viTri + khung.scrollHeight - cao
      } catch (loi) {
        if (luot === this.luotTin && !this.daRoiTrang) {
          this.thongBao = layLoiApi(loi).thongBao
          this.xuLyQuyen(loi)
        }
      } finally {
        this.dangTaiCu = false
      }
    },
    async guiTin(tinLoi) {
      if (!this.hoiThoai?.co_the_gui || this.dangGui || this.dangTaiTin) return
      const retry = tinLoi?.client_message_id ? tinLoi : null
      if (!retry && !this.noiDung.trim() && !this.anhCho.length) return
      const tin = retry || {
        hoi_thoai_id: this.idHoiThoai,
        nguoi_gui_id: this.xacThuc.taiKhoan.id,
        client_message_id: crypto.randomUUID(),
        noi_dung: this.noiDung,
        tep_anh: this.anhCho,
        created_at: new Date().toISOString(),
      }
      const phien = this.chat.maPhien
      this.tinCho = hopNhatTin(this.tinCho, [{ ...tin, trang_thai: 'dang-gui', loi: '' }])
      if (!retry) {
        this.noiDung = ''
        this.anhCho = []
        this.loiAnh = ''
      }
      const boHuy = new AbortController()
      this.boHuyGui.push(boHuy)
      this.thongBao = ''
      await this.$nextTick()
      this.xuongCuoi()
      try {
        const r = await chatService.gui(
          tin.hoi_thoai_id,
          {
            noi_dung: tin.noi_dung,
            client_message_id: tin.client_message_id,
            anh: (tin.tep_anh || []).map((anh) => anh.file),
          },
          boHuy.signal,
        )
        if (this.daRoiTrang || this.chat.maPhien !== phien) return
        this.tinCho = this.tinCho.filter((t) => t.client_message_id !== tin.client_message_id)
        tin.tep_anh?.forEach((anh) => URL.revokeObjectURL(anh.url))
        this.dongAnh()
        if (this.idHoiThoai === tin.hoi_thoai_id) {
          this.tinNhan = hopNhatTin(this.tinNhan, [r.data])
          await this.$nextTick()
          if (this.ganCuoi) this.xuongCuoi()
          void this.taiTinMoi()
        }
        void this.taiDanhSach()
      } catch (loi) {
        if (this.daRoiTrang || this.chat.maPhien !== phien) return
        const loiApi = layLoiApi(loi)
        this.tinCho = this.tinCho.map((t) =>
          t.client_message_id === tin.client_message_id
            ? {
                ...t,
                trang_thai: 'loi',
                loi: Object.values(loiApi.loiTruong).flat().join(' ') || loiApi.thongBao,
              }
            : t,
        )
        if (this.idHoiThoai === tin.hoi_thoai_id) {
          this.xuLyQuyen(loi)
          if (loi.response?.status === 409) void this.taiTinMoi()
        }
      } finally {
        this.boHuyGui = this.boHuyGui.filter((bo) => bo !== boHuy)
      }
    },
    xuLyQuyen(loi) {
      if ([401, 403, 419].includes(loi.response?.status)) {
        this.chat.dongKetNoi()
        this.xacThuc.taiKhoan = null
      }
      if (loi.response?.status === 404) {
        this.luotTin++
        this.boHuyTin?.abort()
        this.hoiThoai = null
        this.donAnhCho()
        this.donTinCho()
        this.dongAnh()
        this.tinNhan = this.tinCho = []
        this.noiDung = ''
        this.thongBao = 'Hội thoại không còn khả dụng. Phân công có thể đã thay đổi.'
        void this.$router.replace(this.duongDan)
      }
    },
    cuonTin() {
      const khung = this.$refs.khungTin
      this.ganCuoi = khung.scrollHeight - khung.scrollTop - khung.clientHeight < 80
      if (this.ganCuoi) {
        this.coTinMoi = false
        void this.ghiDaDoc()
      }
    },
    xuongCuoi() {
      const khung = this.$refs.khungTin
      if (!khung || this.daRoiTrang) return
      khung.scrollTop = khung.scrollHeight
      this.ganCuoi = true
      this.coTinMoi = false
      void this.ghiDaDoc()
    },
    async ghiDaDoc() {
      if (!this.hoiThoai || !this.ganCuoi || document.hidden || this.daRoiTrang) return
      const tin = this.tinNhan.at(-1)
      if (!tin || tin.id <= this.cursorDaGui) return
      const id = this.idHoiThoai
      const luot = this.luotTin
      const cu = this.cursorDaGui
      this.cursorDaGui = tin.id
      try {
        await chatService.daDoc(id, tin.id)
        if (luot === this.luotTin && !this.daRoiTrang) void this.chat.taiSoChuaDoc()
      } catch (loi) {
        if (luot === this.luotTin && !this.daRoiTrang) {
          this.cursorDaGui = cu
          this.xuLyQuyen(loi)
        }
      }
    },
  },
}
</script>
