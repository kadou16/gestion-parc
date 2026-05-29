<template>
  <div class="layout">
    <SidebarMenu />

    <main class="page">
      <div class="page-content">
        <h1>Maintenances</h1>

        <div v-if="msg" class="msg ok">{{ msg }}</div>
        <div v-if="error" class="msg err">{{ error }}</div>

        <form
          ref="editorCard"
          :class="['card', { 'card-editing': editMode, 'card-flash': flashEditor }]"
          @submit.prevent="submitForm"
        >
          <h3>{{ editMode ? 'Modifier la maintenance' : 'Nouvelle maintenance' }}</h3>
          <div v-if="editMode" class="edit-banner">
            <strong>Mode modification activé.</strong>
            {{ editMaintenanceLabel ? ` Les données de la maintenance ${editMaintenanceLabel} ont été chargées dans ce formulaire.` : '' }}
          </div>

          <div class="grid">
            <div>
              <label>Véhicule</label>
              <select v-model="form.vehicule_id" required>
                <option value="">-- choisir --</option>
                <option v-for="v in vehicules" :key="v.idVehicule" :value="v.idVehicule">{{ v.immatriculation }}</option>
              </select>
            </div>
            <div>
              <label>Type</label>
              <select v-model="form.type" required>
                <option value="Preventive">Préventive</option>
                <option value="Corrective">Corrective</option>
              </select>
            </div>
            <div>
              <label>Date début</label>
              <input v-model="form.dateDebut" type="date" required>
            </div>
            <div>
              <label>Date fin prévue</label>
              <input v-model="form.dateFin" type="date" required>
            </div>
            <div>
              <label>Statut</label>
              <select v-model="form.statut" required>
                <option value="En cours">En cours</option>
                <option value="Terminée">Terminée</option>
              </select>
            </div>
          </div>

          <div class="grid">
            <div>
              <label>Prestataire</label>
              <input v-model="form.prestataire" type="text" required>
            </div>
            <div>
              <label>Coût (optionnel)</label>
              <input v-model.number="form.cout" type="number" min="0">
            </div>
          </div>

          <div>
            <label>Description</label>
            <textarea v-model="form.description" rows="3" required></textarea>
          </div>

          <div style="display:flex; gap:10px;">
            <button :disabled="loading">{{ editMode ? (loading ? 'Mise à jour...' : 'Mettre à jour') : (loading ? 'Ajout...' : 'Ajouter') }}</button>
            <button type="button" class="danger" @click="cancelEdit" v-if="editMode" style="background:#6b7280;">Annuler</button>
          </div>
        </form>

        <section class="card">
          <h3>Liste des maintenances</h3>
          <div class="searchbar">
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Rechercher (statut, immatriculation, type...)"
              @input="applySearch"
            >
            <button class="small" type="button" @click="applySearch">Rechercher</button>
          </div>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Véhicule</th>
                <th>Type</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Statut</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in filteredMaintenances" :key="m.idMaintenance">
                <td>{{ m.idMaintenance }}</td>
                <td>{{ m.vehicule?.immatriculation || '-' }}</td>
                <td>{{ m.type }}</td>
                <td>{{ m.dateDebut }}</td>
                <td>{{ m.dateFin || '-' }}</td>
                <td>{{ m.statut }}</td>
                <td>
                  <div class="table-actions">
                    <IconActionButton
                      icon="edit"
                      variant="warning"
                      label="Modifier la maintenance"
                      @click="editItem(m)"
                    />
                    <IconActionButton
                      icon="delete"
                      variant="danger"
                      label="Supprimer la maintenance"
                      @click="askDeleteMaintenance(m)"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </section>

        <ConfirmModal
          v-model="confirmDeleteOpen"
          title="Supprimer cette maintenance ?"
          :message="pendingDeleteMaintenanceLabel ? `La maintenance ${pendingDeleteMaintenanceLabel} sera supprimée définitivement.` : 'Cette action est définitive.'"
          :loading="deleting"
          @cancel="resetDeleteModal"
          @confirm="confirmDeleteMaintenance"
        />
      </div>
    </main>
  </div>
</template>

<script>
import axios from 'axios';
import ConfirmModal from '../components/ConfirmModal.vue';
import IconActionButton from '../components/IconActionButton.vue';
import SidebarMenu from '../components/SidebarMenu.vue';

export default {
  name: 'MaintenancesPage',
  components: { SidebarMenu, ConfirmModal, IconActionButton },
  data() {
    return {
      vehicules: [],
      maintenances: [],
      loading: false,
      editMode: false,
      editId: null,
      editMaintenanceLabel: '',
      flashEditor: false,
      flashTimeoutId: null,
      confirmDeleteOpen: false,
      deleting: false,
      pendingDeleteMaintenanceId: null,
      pendingDeleteMaintenanceLabel: '',
      msg: '',
      error: '',
      searchTerm: '',
      appliedSearch: '',
      form: {
        vehicule_id: '',
        type: 'Preventive',
        dateDebut: '',
        dateFin: '',
        description: '',
        statut: 'En cours',
        prestataire: '',
        cout: 0,
      },
    };
  },
  mounted() {
    this.initAuth();
    this.loadAll();
  },
  computed: {
    filteredMaintenances() {
      if (!this.appliedSearch) return this.maintenances;
      return this.maintenances.filter((m) => this.toSearchText(m).includes(this.appliedSearch));
    },
  },
  methods: {
    applySearch() {
      this.appliedSearch = (this.searchTerm || '').trim().toLowerCase();
    },
    toSearchText(m) {
      return [
        m.idMaintenance,
        m.vehicule?.immatriculation,
        m.type,
        m.dateDebut,
        m.dateFin,
        m.statut,
        m.prestataire,
        m.description,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
    },
    initAuth() {
      const token = localStorage.getItem('token');
      if (!token) return this.$router.push('/');
      axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    },
    async loadAll() {
      const [v, m] = await Promise.all([axios.get('/api/vehicules'), axios.get('/api/maintenances')]);
      this.vehicules = v.data || [];
      this.maintenances = m.data || [];
    },
    async submitForm() {
      if (this.editMode) this.updateItem();
      else this.createMaintenance();
    },
    editItem(item) {
      this.editMode = true;
      this.editId = item.idVehicule || item.idConducteur || item.idAffectation || item.idMaintenance || item.idDocument || item.idEvaluation;
      this.editMaintenanceLabel = `${item.type}${item.vehicule?.immatriculation ? ` • ${item.vehicule.immatriculation}` : ''}`;
      this.form = {
        vehicule_id: item.vehicule_id || '',
        type: item.type || 'Preventive',
        dateDebut: item.dateDebut || '',
        dateFin: item.dateFin || '',
        description: item.description || '',
        statut: item.statut || 'En cours',
        prestataire: item.prestataire || '',
        cout: item.cout ?? 0,
      };
      this.msg = `Modification de la maintenance ${this.editMaintenanceLabel}.`;
      this.error = '';
      this.flashEditor = false;
      clearTimeout(this.flashTimeoutId);
      this.$nextTick(() => {
        this.flashEditor = true;
        this.flashTimeoutId = setTimeout(() => {
          this.flashEditor = false;
        }, 1800);
      });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    async updateItem() {
      this.loading = true;
      try {
        let endpoint = '';
        if(this.editId && "resources/js/pages/Maintenances.vue".includes("Vehicules")) endpoint = '/api/vehicules/';
        if(this.editId && "resources/js/pages/Maintenances.vue".includes("Conducteurs")) endpoint = '/api/conducteurs/';
        if(this.editId && "resources/js/pages/Maintenances.vue".includes("Affectations")) endpoint = '/api/affectations/';
        if(this.editId && "resources/js/pages/Maintenances.vue".includes("Maintenances")) endpoint = '/api/maintenances/';
        if(this.editId && "resources/js/pages/Maintenances.vue".includes("Documents")) endpoint = '/api/documents/';
        if(this.editId && "resources/js/pages/Maintenances.vue".includes("Evaluations")) endpoint = '/api/evaluations/';
        
        await axios.put(endpoint + this.editId, this.form);
        this.msg = 'Modification réussie.';
        this.error = '';
        this.cancelEdit();
        this.loadAll();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur modification.';
      } finally {
        this.loading = false;
      }
    },
    cancelEdit() {
      this.editMode = false;
      this.editId = null;
      this.editMaintenanceLabel = '';
      this.flashEditor = false;
      clearTimeout(this.flashTimeoutId);
      this.form = { vehicule_id: '',
        type: 'Preventive',
        dateDebut: '',
        dateFin: '',
        description: '',
        statut: 'En cours',
        prestataire: '',
        cout: 0, };
    },
    async createMaintenance() {
      this.loading = true;
      this.msg = '';
      this.error = '';
      try {
        await axios.post('/api/maintenances', this.form);
        this.msg = 'Maintenance ajoutée.';
        this.form = { vehicule_id: '', type: 'Preventive', dateDebut: '', dateFin: '', description: '', statut: 'En cours', prestataire: '', cout: 0 };
        this.loadAll();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur ajout maintenance.';
      } finally {
        this.loading = false;
      }
    },
    askDeleteMaintenance(maintenance) {
      this.pendingDeleteMaintenanceId = maintenance.idMaintenance;
      this.pendingDeleteMaintenanceLabel = `${maintenance.type}${maintenance.vehicule?.immatriculation ? ` • ${maintenance.vehicule.immatriculation}` : ''}`;
      this.confirmDeleteOpen = true;
    },
    resetDeleteModal() {
      if (this.deleting) return;
      this.confirmDeleteOpen = false;
      this.pendingDeleteMaintenanceId = null;
      this.pendingDeleteMaintenanceLabel = '';
    },
    async confirmDeleteMaintenance() {
      if (!this.pendingDeleteMaintenanceId) return;
      this.deleting = true;
      try {
        await axios.delete(`/api/maintenances/${this.pendingDeleteMaintenanceId}`);
        this.msg = 'Maintenance supprimée.';
        this.loadAll();
      } catch (e) {
        this.error = 'Erreur suppression.';
      } finally {
        this.deleting = false;
        this.confirmDeleteOpen = false;
        this.pendingDeleteMaintenanceId = null;
        this.pendingDeleteMaintenanceLabel = '';
      }
    },
  },
};
</script>

<style scoped>
.layout { display: flex; height: 100vh; overflow: hidden; }
.page { flex: 1; min-width: 0; height: 100vh; overflow-y: auto; padding: 24px; background: #f6f7fb; box-sizing: border-box; }
.page-content { width: min(100%, 1120px); margin: 0 auto; }
.card { background: #fff; padding: 16px; border-radius: 10px; margin-top: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.card-editing { border: 1px solid rgba(91, 33, 182, 0.24); box-shadow: 0 10px 30px rgba(91, 33, 182, 0.10); }
.card-flash { animation: editorFlash 1.2s ease; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 16px; }
.searchbar { display: flex; gap: 10px; margin: 10px 0; align-items: center; }
.edit-banner { margin: 12px 0 18px; padding: 12px 14px; border-radius: 10px; background: linear-gradient(135deg, rgba(91, 33, 182, 0.10), rgba(124, 58, 237, 0.16)); color: #4c1d95; border: 1px solid rgba(124, 58, 237, 0.18); }
label { display: block; margin: 8px 0 4px; font-size: 14px; }
input, select, textarea { box-sizing: border-box; width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 8px; }
button { margin-top: 10px; padding: 10px 14px; background: #4f46e5; color: #fff; border: 0; border-radius: 8px; cursor: pointer; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 9px; font-size: 14px; }
.danger { background: #dc2626; margin: 0; }
.small { margin: 0; padding: 8px 10px; font-size: 12px; }
.msg { margin-top: 10px; padding: 10px; border-radius: 8px; }
.ok { background: #dcfce7; color: #166534; }
.err { background: #fee2e2; color: #991b1b; }

@keyframes editorFlash {
  0% { transform: translateY(-2px); box-shadow: 0 0 0 0 rgba(124, 58, 237, 0.28); }
  55% { transform: translateY(0); box-shadow: 0 0 0 10px rgba(124, 58, 237, 0); }
  100% { box-shadow: 0 10px 30px rgba(91, 33, 182, 0.10); }
}

@media (max-width: 900px) {
  .page { padding: 16px; }
  .grid { grid-template-columns: 1fr; gap: 16px; }
  .searchbar { flex-direction: column; align-items: stretch; }
}
</style>
