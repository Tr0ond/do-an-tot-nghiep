<template>
  <dl class="package-facts-list">
    <div class="package-fact-item">
      <dt class="d-flex align-items-center gap-2">
        <span class="fact-icon-circle fact-icon-blue">
          <i class="bi bi-calendar3" aria-hidden="true"></i>
        </span>
        <span>Thời hạn gói</span>
      </dt>
      <dd>{{ dinhDangSo(goi.thoi_han_ngay) }} ngày</dd>
    </div>

    <div class="package-fact-item">
      <dt class="d-flex align-items-center gap-2">
        <span class="fact-icon-circle fact-icon-emerald">
          <i class="bi bi-person-check-fill" aria-hidden="true"></i>
        </span>
        <span>Huấn luyện viên (PT)</span>
      </dt>
      <dd :class="Number(goi.so_buoi_pt) > 0 ? 'text-success' : 'text-muted'">
        {{
          Number(goi.so_buoi_pt) > 0 ? dinhDangSo(goi.so_buoi_pt) + ' buổi 1-kèm-1' : 'Không kèm PT'
        }}
      </dd>
    </div>

    <div class="package-fact-item">
      <dt class="d-flex align-items-center gap-2">
        <span class="fact-icon-circle fact-icon-purple">
          <i class="bi bi-chat-square-text-fill" aria-hidden="true"></i>
        </span>
        <span>Trợ lý AI Chatbot</span>
      </dt>
      <dd>{{ dinhDangSo(goi.so_luot_chatbot_moi_ngay) }} lượt / ngày</dd>
    </div>
  </dl>
</template>

<script>
import { dinhDangSo } from '../utils/goiTap'
export default {
  name: 'QuyenLoiGoiTap',
  props: { goi: { type: Object, required: true } },
  methods: { dinhDangSo },
}
</script>

<style scoped>
.package-facts-list {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.package-fact-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 10px 0;
  border-bottom: 1px solid var(--mau-vien);
}

.package-fact-item:last-child {
  border-bottom: none;
}

dt {
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
  margin: 0;
}

.fact-icon-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 0.95rem;
  flex-shrink: 0;
}

.fact-icon-blue {
  background: #eff6ff;
  color: #2563eb;
}

.fact-icon-emerald {
  background: #ecfdf5;
  color: #059669;
}

.fact-icon-purple {
  background: #f5f3ff;
  color: #7c3aed;
}

dd {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--mau-chu);
  text-align: right;
  overflow-wrap: anywhere;
}

@media (max-width: 480px) {
  .package-fact-item {
    flex-wrap: wrap;
    gap: 6px;
  }
}
</style>
