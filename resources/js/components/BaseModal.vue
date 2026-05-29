<template>
  <teleport to="body">
    <div v-if="modelValue" class="modal-overlay" @click.self="close">
      <div class="modal-shell" :class="sizeClass" role="dialog" aria-modal="true" :aria-labelledby="titleId">
        <div class="modal-header">
          <div>
            <h3 :id="titleId" class="modal-title">{{ title }}</h3>
            <p v-if="subtitle" class="modal-subtitle">{{ subtitle }}</p>
          </div>
          <button type="button" class="modal-close" aria-label="Fermer" @click="close">✕</button>
        </div>

        <div class="modal-body">
          <slot />
        </div>
      </div>
    </div>
  </teleport>
</template>

<script>
export default {
  name: 'BaseModal',
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    title: {
      type: String,
      default: '',
    },
    subtitle: {
      type: String,
      default: '',
    },
    size: {
      type: String,
      default: 'md',
    },
  },
  emits: ['update:modelValue'],
  computed: {
    titleId() {
      return `base-modal-title-${this._.uid}`;
    },
    sizeClass() {
      return `size-${this.size}`;
    },
  },
  mounted() {
    window.addEventListener('keydown', this.onKeydown);
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.onKeydown);
  },
  methods: {
    onKeydown(event) {
      if (event.key === 'Escape' && this.modelValue) {
        this.close();
      }
    },
    close() {
      this.$emit('update:modelValue', false);
    },
  },
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(5px);
}

.modal-shell {
  width: min(100%, 680px);
  max-height: min(86vh, 920px);
  display: flex;
  flex-direction: column;
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
  overflow: hidden;
}

.modal-shell.size-sm {
  width: min(100%, 520px);
}

.modal-shell.size-lg {
  width: min(100%, 860px);
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 22px 22px 18px;
  border-bottom: 1px solid #eef2f7;
}

.modal-title {
  margin: 0;
  font-size: 22px;
}

.modal-subtitle {
  margin: 6px 0 0;
  color: #64748b;
  line-height: 1.5;
}

.modal-close {
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
  border: 0;
  border-radius: 12px;
  background: #f3f4f6;
  color: #0f172a;
  cursor: pointer;
  font-size: 16px;
}

.modal-body {
  overflow: auto;
  padding: 22px;
}

@media (max-width: 640px) {
  .modal-overlay {
    padding: 14px;
  }

  .modal-header,
  .modal-body {
    padding: 18px;
  }
}
</style>
