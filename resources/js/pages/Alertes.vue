<template>
  <div class="layout">
    <SidebarMenu />

    <main class="page">
      <div class="page-content">
        <header class="header">
          <h1>Notifications & Alertes</h1>
        </header>

        <div v-if="msg" class="msg ok">{{ msg }}</div>
        <div v-if="error" class="msg err">{{ error }}</div>

        <section class="card">
          <h3>Toutes les alertes</h3>
          <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Véhicule</th>
              <th>Statut</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in alertes" :key="a.idAlerte" :class="{ 'unread': a.statut === 'Non lue' }">
              <td>{{ a.dateAlerte }}</td>
              <td>
                <span class="badge" :class="a.typeAlerte === 'Fin de maintenance' ? 'badge-warning' : 'badge-danger'">
                  {{ a.typeAlerte }}
                </span>
              </td>
              <td>
                <span v-if="a.maintenance?.vehicule?.immatriculation">{{ a.maintenance.vehicule.immatriculation }}</span>
                <span v-else-if="a.document?.vehicule?.immatriculation">{{ a.document.vehicule.immatriculation }}</span>
                <span v-else>-</span>
              </td>
              <td>
                <span class="status" :class="{'status-unread': a.statut === 'Non lue', 'status-read': a.statut === 'Lue'}">
                  {{ a.statut }}
                </span>
              </td>
              <td>
                <div class="table-actions">
                  <button v-if="a.statut === 'Non lue'" class="small" @click="marquerCommeLue(a.idAlerte)">
                    ✔ Marquer lue
                  </button>
                  <IconActionButton
                    icon="delete"
                    variant="danger"
                    label="Supprimer l'alerte"
                    @click="askDeleteAlerte(a.idAlerte)"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="alertes.length === 0">
              <td colspan="5" style="text-align: center; color: gray;">Aucune alerte pour le moment.</td>
            </tr>
          </tbody>
          </table>
        </section>

        <ConfirmModal
          v-model="confirmDeleteOpen"
          title="Supprimer cette alerte ?"
          message="Cette alerte sera retirée définitivement de la liste."
          :loading="deleting"
          @cancel="resetDeleteModal"
          @confirm="confirmDeleteAlerte"
        />
      </div>
    </main>
  </div>
</template>

<script>
import axios from '../services/api';
import ConfirmModal from '../components/ConfirmModal.vue';
import IconActionButton from '../components/IconActionButton.vue';
import SidebarMenu from '../components/SidebarMenu.vue';

export default {
  name: 'AlertesPage',
  components: { SidebarMenu, ConfirmModal, IconActionButton },
  data() {
    return {
      alertes: [],
      confirmDeleteOpen: false,
      deleting: false,
      pendingDeleteAlerteId: null,
      msg: '',
      error: '',
    };
  },
  mounted() {
    this.initAuth();
    this.loadAlertes();
  },
  methods: {
    initAuth() {
      const token = localStorage.getItem('token');
      if (!token) return this.$router.push('/');
    },
    async loadAlertes() {
      try {
        const response = await axios.get('/api/alertes');
        this.alertes = response.data.sort((a, b) => b.idAlerte - a.idAlerte);
      } catch (err) {
        console.error("Erreur de chargement des alertes", err);
      }
    },
    async marquerCommeLue(id) {
      try {
        await axios.put(`/api/alertes/${id}`, { statut: 'Lue' });
        this.loadAlertes();
      } catch (err) {
        this.error = 'Erreur lors de la mise à jour.';
      }
    },
    askDeleteAlerte(id) {
      this.pendingDeleteAlerteId = id;
      this.confirmDeleteOpen = true;
    },
    resetDeleteModal() {
      if (this.deleting) return;
      this.confirmDeleteOpen = false;
      this.pendingDeleteAlerteId = null;
    },
    async confirmDeleteAlerte() {
      if (!this.pendingDeleteAlerteId) return;
      this.deleting = true;
      try {
        await axios.delete(`/api/alertes/${this.pendingDeleteAlerteId}`);
        this.loadAlertes();
      } catch (err) {
        this.error = 'Erreur lors de la suppression.';
      } finally {
        this.deleting = false;
        this.confirmDeleteOpen = false;
        this.pendingDeleteAlerteId = null;
      }
    }
  }
};
</script>

<style scoped>
.layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: #f6f7fb;
}

.page {
  flex: 1;
  min-width: 0;
  height: 100vh;
  overflow-y: auto;
  padding: 24px;
  box-sizing: border-box;
}

.page-content {
  width: min(100%, 1120px);
  margin: 0 auto;
}

.header {
  margin-bottom: 24px;
}

.subtitle {
  color: #6b7280;
  margin-top: 4px;
}

.card {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

th, td {
  text-align: left;
  padding: 12px;
  border-bottom: 1px solid #f3f4f6;
}

th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
}

tr.unread {
  background-color: #fffbeb; 
}

.badge {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 0.85em;
  font-weight: 600;
}

.badge-warning { background: #fef3c7; color: #d97706; }
.badge-danger { background: #fee2e2; color: #dc2626; }

.status-unread {
  color: #dc2626;
  font-weight: bold;
}

.status-read {
  color: #10b981;
}

button {
  cursor: pointer;
  padding: 6px 12px;
  border: none;
  border-radius: 6px;
  background: #4f46e5;
  color: white;
  margin-right: 5px;
}

button.danger {
  background: #ef4444;
}

button:hover { opacity: 0.9; }

@media (max-width: 900px) {
  .page {
    padding: 16px;
  }
}
</style>
