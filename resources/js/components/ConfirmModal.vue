<template>
  <teleport to="body">
    <div v-if="modelValue" class="confirm-overlay" @click.self="close">
      <div
        class="confirm-dialog"
        :class="toneClass"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
      >
        <div class="confirm-header">
          <div class="confirm-icon" aria-hidden="true">{{ toneIcon }}</div>
          <div>
            <h3 :id="titleId" class="confirm-title">{{ title }}</h3>
            <p v-if="message" class="confirm-message">{{ message }}</p>
          </div>
        </div>

        <div class="confirm-actions">
          <button type="button" class="btn btn-secondary" :disabled="loading" @click="cancel">
            {{ cancelText }}
          </button>
          <button type="button" class="btn btn-danger" :disabled="loading" @click="$emit('confirm')">
            {{ loading ? 'Suppression...' : confirmText }}
          </button>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script>
export default {
  name: 'ConfirmModal',
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    title: {
      type: String,
      default: 'Confirmer la suppression',
    },
    message: {
      type: String,
      default: '',
    },
    confirmText: {
      type: String,
      default: 'Supprimer',
    },
    cancelText: {
      type: String,
      default: 'Annuler',
    },
    loading: {
      type: Boolean,
      default: false,
    },
    tone: {
      type: String,
      default: 'danger',
    },
  },
  emits: ['update:modelValue', 'confirm', 'cancel'],
  computed: {
    titleId() {
      return `confirm-modal-title-${this._.uid}`;
    },
    toneClass() {
      return `tone-${this.tone}`;
    },
    toneIcon() {
      if (this.tone === 'warning') return '!';
      return '!';
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
      if (event.key === 'Escape' && this.modelValue && !this.loading) {
        this.cancel();
      }
    },
    close() {
      this.$emit('update:modelValue', false);
    },
    cancel() {
      this.$emit('cancel');
      this.close();
    },
  },
};
</script>

<style scoped>
.confirm-overlay {
  position: fixed;
  inset: 0;
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(3px);
}

.confirm-dialog {
  width: min(100%, 460px);
  background: #fff;
  border-radius: 18px;
  padding: 22px;
  box-shadow: 0 22px 60px rgba(15, 23, 42, 0.28);
}

.confirm-header {
  display: grid;
  grid-template-columns: 52px 1fr;
  gap: 14px;
  align-items: start;
}

.confirm-icon {
  width: 52px;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  font-size: 24px;
  font-weight: 800;
}

.confirm-title {
  margin: 0;
  font-size: 20px;
}

.confirm-message {
  margin: 8px 0 0;
  color: #475569;
  line-height: 1.5;
}

.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 22px;
}

.btn {
  min-width: 112px;
  padding: 11px 16px;
  border: 0;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  background: #e5e7eb;
  color: #111827;
}

.btn-danger {
  background: linear-gradient(135deg, #dc2626, #ef4444);
  color: #fff;
}

.tone-danger .confirm-icon {
  background: rgba(239, 68, 68, 0.14);
  color: #dc2626;
}

@media (max-width: 640px) {
  .confirm-dialog {
    padding: 18px;
    border-radius: 16px;
  }

  .confirm-actions {
    flex-direction: column-reverse;
  }

  .btn {
    width: 100%;
  }
}
</style>
