<template>
  <button
    :type="type"
    :class="['icon-action-btn', `icon-action-btn--${variant}`]"
    :title="label"
    :aria-label="label"
    :disabled="disabled || loading"
    @click="$emit('click', $event)"
  >
    <svg
      v-if="icon === 'view'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.9"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
      <circle cx="12" cy="12" r="3" />
    </svg>

    <svg
      v-else-if="icon === 'edit'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.9"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <path d="M12 20h9" />
      <path d="m16.5 3.5 4 4L8 20l-5 1 1-5 12.5-12.5Z" />
    </svg>

    <svg
      v-else-if="icon === 'delete'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.9"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <path d="M3 6h18" />
      <path d="M8 6V4h8v2" />
      <path d="M19 6l-1 14H6L5 6" />
      <path d="M10 11v6" />
      <path d="M14 11v6" />
    </svg>

    <svg
      v-else-if="icon === 'upload'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.9"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <path d="M12 16V4" />
      <path d="m7 9 5-5 5 5" />
      <path d="M4 20h16" />
    </svg>

    <svg
      v-else-if="icon === 'download'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.9"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <path d="M12 4v12" />
      <path d="m7 11 5 5 5-5" />
      <path d="M4 20h16" />
    </svg>

    <svg
      v-else-if="icon === 'send'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.9"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <path d="M21 3 10 14" />
      <path d="m21 3-7 18-4-7-7-4 18-7Z" />
    </svg>
  </button>
</template>

<script>
export default {
  name: 'IconActionButton',
  emits: ['click'],
  props: {
    icon: {
      type: String,
      required: true,
    },
    label: {
      type: String,
      required: true,
    },
    variant: {
      type: String,
      default: 'primary',
    },
    type: {
      type: String,
      default: 'button',
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
};
</script>

<style scoped>
.icon-action-btn {
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  margin: 0;
  border: 0;
  border-radius: 10px;
  color: #fff;
  cursor: pointer;
  box-shadow: 0 10px 18px rgba(15, 23, 42, 0.14);
  transition: transform 0.18s ease, opacity 0.18s ease, box-shadow 0.18s ease;
}

.icon-action-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 14px 24px rgba(15, 23, 42, 0.18);
}

.icon-action-btn:focus-visible {
  outline: 3px solid rgba(99, 102, 241, 0.22);
  outline-offset: 2px;
}

.icon-action-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  box-shadow: none;
}

.icon-action-btn svg {
  width: 18px;
  height: 18px;
}

.icon-action-btn--primary {
  background: linear-gradient(135deg, #5b21b6, #7c3aed);
}

.icon-action-btn--warning {
  background: linear-gradient(135deg, #f59e0b, #f97316);
}

.icon-action-btn--danger {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.icon-action-btn--success {
  background: linear-gradient(135deg, #16a34a, #15803d);
}
</style>
