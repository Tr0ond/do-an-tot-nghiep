<template>
  <div class="mb-3">
    <label :for="id" class="form-label">
      {{ nhan }}
      <span v-if="batBuoc" class="text-danger" aria-hidden="true">*</span>
    </label>
    <div class="input-group-modern" :class="{ 'co-bieu-tuong': bieuTuong }">
      <span v-if="bieuTuong" class="input-icon-prefix" aria-hidden="true">
        <i :class="bieuTuong"></i>
      </span>
      <input
        :id="id"
        :name="id"
        :value="modelValue"
        :type="loai"
        :placeholder="goiYNhap"
        :autocomplete="tuDongDien"
        :required="batBuoc"
        :minlength="toiThieu"
        :maxlength="toiDa"
        :min="loai === 'number' ? toiThieu : undefined"
        :max="loai === 'number' ? toiDa : undefined"
        :step="loai === 'number' ? 1 : undefined"
        :disabled="voHieu"
        class="form-control"
        :class="{ 'is-invalid': loi.length, 'has-prefix': bieuTuong }"
        :aria-invalid="loi.length ? 'true' : undefined"
        :aria-describedby="loi.length ? id + '-loi' : goiY ? id + '-goi-y' : undefined"
        @input="$emit('update:modelValue', $event.target.value)"
      />
    </div>
    <p
      v-if="loi.length"
      :id="id + '-loi'"
      class="invalid-feedback d-flex align-items-center gap-1 mt-1"
    >
      <i class="bi bi-exclamation-circle-fill"></i>
      <span>{{ loi[0] }}</span>
    </p>
    <p v-else-if="goiY" :id="id + '-goi-y'" class="form-text mb-0 mt-1">
      <i class="bi bi-info-circle me-1"></i>{{ goiY }}
    </p>
  </div>
</template>

<script>
export default {
  name: 'TruongNhap',
  props: {
    modelValue: { type: String, default: '' },
    id: { type: String, required: true },
    nhan: { type: String, required: true },
    loai: { type: String, default: 'text' },
    tuDongDien: { type: String, default: 'off' },
    loi: { type: Array, default: () => [] },
    goiY: { type: String, default: '' },
    goiYNhap: { type: String, default: '' },
    batBuoc: { type: Boolean, default: true },
    voHieu: { type: Boolean, default: false },
    toiThieu: { type: Number, default: undefined },
    toiDa: { type: Number, default: 255 },
    bieuTuong: { type: String, default: '' },
  },
  emits: ['update:modelValue'],
}
</script>

<style scoped>
.input-group-modern {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.input-icon-prefix {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--mau-phu);
  font-size: 1.1rem;
  z-index: 4;
  pointer-events: none;
  transition: color 0.2s ease;
}

.form-control.has-prefix {
  padding-left: 42px;
}

.input-group-modern:focus-within .input-icon-prefix {
  color: var(--mau-chinh);
}
</style>
