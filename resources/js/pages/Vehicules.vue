<template>
  <div class="layout">
    <SidebarMenu />

    <main class="page">
      <div class="page-content">
        <h1>Espace Administrateur</h1>

        <div v-if="msg" class="msg ok">{{ msg }}</div>
        <div v-if="error" class="msg err">{{ error }}</div>

        <form
          ref="editorCard"
          :class="['card', { 'card-editing': editMode, 'card-flash': flashEditor }]"
          @submit.prevent="submitForm"
          style="margin-bottom: 2rem;"
        >
          <h3 v-if="!editMode">Gérer les véhicules (ajout)</h3>
          <h3 v-else>Modifier le véhicule</h3>
          <div v-if="editMode" class="edit-banner">
            <strong>Mode modification activé.</strong>
            {{ editVehicleLabel ? ` Les données du véhicule ${editVehicleLabel} ont été chargées dans ce formulaire.` : '' }}
          </div>

        <div class="grid">
          <div>
            <label>Immatriculation</label>
            <input v-model="form.immatriculation" type="text" required>
          </div>
          <div>
            <label>Marque</label>
            <input v-model="form.marque" type="text" required>
          </div>
          <div>
            <label>Modèle</label>
            <input v-model="form.modele" type="text" required>
          </div>
          <div>
            <label>Année</label>
            <input v-model.number="form.annee" type="number" min="1990" required>
          </div>
          <div>
            <label>Kilométrage</label>
            <input v-model.number="form.kilometrage" type="number" min="0" required>
          </div>
          <div>
            <label>Statut</label>
            <select v-model="form.statut" required>
              <option value="Disponible">Disponible</option>
              <option value="Affecté">Affecté</option>
              <option value="Maintenance">Maintenance</option>
            </select>
          </div>
          <div>
            <label>État</label>
            <select v-model="form.etat" required>
              <option value="Bon">Bon</option>
              <option value="Moyen">Moyen</option>
              <option value="Endommagé">Endommagé</option>
            </select>
          </div>
          <div v-if="showConducteurSelect">
            <label>Conducteur à affecter</label>
            <select v-model="form.conducteur_id" required>
              <option value="">-- choisir un conducteur --</option>
              <option v-for="c in conducteurs" :key="c.idConducteur" :value="c.idConducteur">
                {{ c.utilisateur?.nom }} {{ c.utilisateur?.prenom }}
              </option>
            </select>
            <p class="field-hint">
              Une affectation initiale sera créée automatiquement avec la date et l'heure actuelles.
            </p>
          </div>
        </div>

          <button :disabled="loading">{{ editMode ? 'Mettre à jour' : (loading ? 'Ajout...' : 'Ajouter') }}</button>
          <button type="button" @click="cancelEdit" v-if="editMode" style="background:#6b7280; margin-left: 10px;">Annuler</button>
        </form>

        <section class="card">
          <h3>Liste véhicules</h3>
          <div class="searchbar">
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Rechercher (immatriculation, marque, statut...)"
              @input="applySearch"
            >
            <button class="small" type="button" @click="applySearch">Rechercher</button>
          </div>
          <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Immatriculation</th>
              <th>Marque</th>
              <th>Modèle</th>
              <th>Statut</th>
              <th>État</th>
              <th>Coût total</th>
              <th>Détails</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <template v-for="v in filteredVehicules" :key="v.idVehicule">
              <tr>
                <td>{{ v.idVehicule }}</td>
                <td>{{ v.immatriculation }}</td>
                <td>{{ v.marque }}</td>
                <td>{{ v.modele }}</td>
                <td>{{ v.statut }}</td>
                <td>{{ v.etat }}</td>
                <td>{{ calculerCoutTotal(v.idVehicule) }} DA</td>
                <td>
                  <div class="table-actions">
                    <IconActionButton
                      icon="view"
                      label="Visualiser les détails"
                      @click="openDetails(v.idVehicule)"
                    />
                  </div>
                </td>
                <td>
                  <div class="table-actions">
                    <IconActionButton
                      icon="edit"
                      variant="warning"
                      label="Modifier le véhicule"
                      @click="editVehicule(v)"
                    />
                    <IconActionButton
                      icon="delete"
                      variant="danger"
                      label="Supprimer le véhicule"
                      @click="askDeleteVehicule(v)"
                    />
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
          </table>
        </section>

        <BaseModal
          :model-value="Boolean(selectedVehicule)"
          title="Relations du véhicule"
          :subtitle="selectedVehicule ? `${selectedVehicule.immatriculation} • ${selectedVehicule.marque} ${selectedVehicule.modele}` : ''"
          size="md"
          @update:modelValue="closeDetails"
        >
          <div v-if="selectedVehicule" class="details-modal">
            <div class="details-summary">
              <div class="summary-pill">
                <span class="summary-label">Statut</span>
                <strong>{{ selectedVehicule.statut }}</strong>
              </div>
              <div class="summary-pill">
                <span class="summary-label">État</span>
                <strong>{{ selectedVehicule.etat }}</strong>
              </div>
              <div class="summary-pill">
                <span class="summary-label">Coût total</span>
                <strong>{{ calculerCoutTotal(selectedVehicule.idVehicule) }} DA</strong>
              </div>
            </div>

            <section class="details-block">
              <div class="details-block-header">
                <h4>Documents associés</h4>
                <span class="details-count">{{ selectedVehiculeDocuments.length }}</span>
              </div>
              <ul v-if="selectedVehiculeDocuments.length" class="details-list">
                <li v-for="d in selectedVehiculeDocuments" :key="d.idDocument" class="details-item">
                  <strong>{{ d.type }}</strong>
                  <span>Expire le {{ d.dateExpiration }}</span>
                </li>
              </ul>
              <p v-else class="empty-state">Aucun document associé à ce véhicule.</p>
            </section>

            <section class="details-block">
              <div class="details-block-header">
                <h4>Maintenances associées</h4>
                <span class="details-count">{{ selectedVehiculeMaintenances.length }}</span>
              </div>
              <ul v-if="selectedVehiculeMaintenances.length" class="details-list">
                <li v-for="m in selectedVehiculeMaintenances" :key="m.idMaintenance" class="details-item">
                  <strong>{{ m.type }}</strong>
                  <span>{{ m.statut }}{{ m.dateFin ? ` • fin ${m.dateFin}` : '' }}</span>
                </li>
              </ul>
              <p v-else class="empty-state">Aucune maintenance associée à ce véhicule.</p>
            </section>
          </div>
        </BaseModal>

        <ConfirmModal
          v-model="confirmDeleteOpen"
          title="Supprimer ce véhicule ?"
          :message="pendingDeleteVehiculeLabel ? `Le véhicule ${pendingDeleteVehiculeLabel} sera supprimé définitivement.` : 'Cette action est définitive.'"
          :loading="deleting"
          @cancel="resetDeleteModal"
          @confirm="confirmDeleteVehicule"
        />
      </div>
    </main>
  </div>
</template>

<script>
import axios from '../services/api';
import BaseModal from '../components/BaseModal.vue';
import ConfirmModal from '../components/ConfirmModal.vue';
import IconActionButton from '../components/IconActionButton.vue';
import SidebarMenu from '../components/SidebarMenu.vue';

export default {
  name: 'VehiculesPage',
  components: { SidebarMenu, BaseModal, ConfirmModal, IconActionButton },
  data() {
    return {
      vehicules: [],
      conducteurs: [],
      affectations: [],
      maintenances: [],
      documents: [],
      administrateurId: null,
      detailsVehiculeId: null,
      loading: false,
      editMode: false,
      currentEditId: null,
      editVehicleLabel: '',
      flashEditor: false,
      flashTimeoutId: null,
      confirmDeleteOpen: false,
      deleting: false,
      pendingDeleteVehiculeId: null,
      pendingDeleteVehiculeLabel: '',
      msg: '',
      error: '',
      searchTerm: '',
      appliedSearch: '',
      form: {
        administrateur_id: '',
        immatriculation: '',
        marque: '',
        modele: '',
        annee: new Date().getFullYear(),
        kilometrage: 0,
        statut: 'Disponible',
        etat: 'Bon',
        conducteur_id: '',
      },
    };
  },
  mounted() {
    this.initAuth();
    this.loadAll();
  },
  computed: {
    filteredVehicules() {
      if (!this.appliedSearch) return this.vehicules;
      return this.vehicules.filter((v) => this.toSearchText(v).includes(this.appliedSearch));
    },
    showConducteurSelect() {
      return !this.editMode && this.form.statut === 'Affecté';
    },
    selectedVehicule() {
      return this.vehicules.find((v) => v.idVehicule === this.detailsVehiculeId) || null;
    },
    selectedVehiculeDocuments() {
      return this.detailsVehiculeId ? this.getDocumentsVehicule(this.detailsVehiculeId) : [];
    },
    selectedVehiculeMaintenances() {
      return this.detailsVehiculeId ? this.getMaintenancesVehicule(this.detailsVehiculeId) : [];
    },
  },
  methods: {
    applySearch() {
      this.appliedSearch = (this.searchTerm || '').trim().toLowerCase();
    },
    toSearchText(v) {
      return [
        v.idVehicule,
        v.immatriculation,
        v.marque,
        v.modele,
        v.annee,
        v.kilometrage,
        v.statut,
        v.etat,
      ]
        .filter((value) => value !== null && value !== undefined)
        .join(' ')
        .toLowerCase();
    },
    initAuth() {
      const token = localStorage.getItem('token');
      if (!token) return this.$router.push('/');
    },
    async loadAll() {
      await this.loadAdministrateurId();
      const [v, c, a, m, d] = await Promise.all([
        axios.get('/api/vehicules'),
        axios.get('/api/conducteurs'),
        axios.get('/api/affectations'),
        axios.get('/api/maintenances'),
        axios.get('/api/documents'),
      ]);
      this.vehicules = v.data || [];
      this.conducteurs = c.data || [];
      this.affectations = a.data || [];
      this.maintenances = m.data || [];
      this.documents = d.data || [];
    },
    async loadAdministrateurId() {
      const me = await axios.get('/api/me');
      const userId = me.data?.user?.id;
      const admins = await axios.get('/api/administrateurs');
      const found = (admins.data || []).find((a) => a.utilisateur_id === userId);
      this.administrateurId = found?.idAdministrateur || null;
      this.form.administrateur_id = this.administrateurId;
    },
    calculerCoutTotal(idVehicule) {
      const totalMaint = this.maintenances
        .filter((m) => m.vehicule_id === idVehicule)
        .reduce((s, m) => s + Number(m.cout || 0), 0);
      const totalAffect = this.affectations
        .filter((a) => a.vehicule_id === idVehicule)
        .reduce((s, a) => s + Number(a.coutGenere || 0), 0);
      return totalMaint + totalAffect;
    },
    getDocumentsVehicule(idVehicule) {
      return this.documents.filter((d) => d.vehicule_id === idVehicule);
    },
    getMaintenancesVehicule(idVehicule) {
      return this.maintenances.filter((m) => m.vehicule_id === idVehicule);
    },
    openDetails(idVehicule) {
      this.detailsVehiculeId = idVehicule;
    },
    closeDetails() {
      this.detailsVehiculeId = null;
    },
    submitForm() {
      if (this.editMode) {
        this.updateVehicule();
      } else {
        this.createVehicule();
      }
    },
    editVehicule(vehicule) {
      this.editMode = true;
      this.currentEditId = vehicule.idVehicule;
      this.editVehicleLabel = vehicule.immatriculation || `${vehicule.marque} ${vehicule.modele}`;
      // On pré-remplit le formulaire
      this.form = {
        administrateur_id: vehicule.administrateur_id || this.administrateurId,
        immatriculation: vehicule.immatriculation,
        marque: vehicule.marque,
        modele: vehicule.modele,
        annee: vehicule.annee,
        kilometrage: vehicule.kilometrage,
        statut: vehicule.statut,
        etat: vehicule.etat,
        conducteur_id: '',
      };
      this.msg = `Modification du véhicule ${this.editVehicleLabel}.`;
      this.error = '';
      this.flashEditor = false;
      clearTimeout(this.flashTimeoutId);
      this.$nextTick(() => {
        this.flashEditor = true;
        this.flashTimeoutId = setTimeout(() => {
          this.flashEditor = false;
        }, 1800);
      });
      window.scrollTo({ top: 0, behavior: 'smooth' }); // Remonte en haut de la page
    },
    cancelEdit() {
      this.editMode = false;
      this.currentEditId = null;
      this.editVehicleLabel = '';
      this.flashEditor = false;
      clearTimeout(this.flashTimeoutId);
      this.form = {
        administrateur_id: this.administrateurId,
        immatriculation: '',
        marque: '',
        modele: '',
        annee: new Date().getFullYear(),
        kilometrage: 0,
        statut: 'Disponible',
        etat: 'Bon',
        conducteur_id: '',
      };
      this.error = '';
    },
    async updateVehicule() {
      this.loading = true;
      this.msg = '';
      this.error = '';
      try {
        await axios.put(`/api/vehicules/${this.currentEditId}`, this.form);
        this.msg = 'Véhicule modifié avec succès.';
        this.cancelEdit();
        this.loadAll();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur lors de la modification.';
      } finally {
        this.loading = false;
      }
    },
    async createVehicule() {
      this.loading = true;
      this.msg = '';
      this.error = '';
      try {
        this.form.administrateur_id = this.administrateurId;
        await axios.post('/api/vehicules', this.form);
        this.msg = 'Véhicule ajouté.';
        this.form = {
          administrateur_id: this.administrateurId,
          immatriculation: '',
          marque: '',
          modele: '',
          annee: new Date().getFullYear(),
          kilometrage: 0,
          statut: 'Disponible',
          etat: 'Bon',
          conducteur_id: '',
        };
        this.loadAll();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur ajout véhicule.';
      } finally {
        this.loading = false;
      }
    },
    askDeleteVehicule(vehicule) {
      this.pendingDeleteVehiculeId = vehicule.idVehicule;
      this.pendingDeleteVehiculeLabel = vehicule.immatriculation || `${vehicule.marque} ${vehicule.modele}`;
      this.confirmDeleteOpen = true;
    },
    resetDeleteModal() {
      if (this.deleting) return;
      this.confirmDeleteOpen = false;
      this.pendingDeleteVehiculeId = null;
      this.pendingDeleteVehiculeLabel = '';
    },
    async confirmDeleteVehicule() {
      if (!this.pendingDeleteVehiculeId) return;
      this.deleting = true;
      try {
        await axios.delete(`/api/vehicules/${this.pendingDeleteVehiculeId}`);
        this.msg = 'Véhicule supprimé.';
        if (this.detailsVehiculeId === this.pendingDeleteVehiculeId) {
          this.closeDetails();
        }
        this.resetDeleteModal();
        this.loadAll();
      } catch (e) {
        this.error = 'Erreur suppression (peut-être lié à affectations/maintenances).';
      } finally {
        this.deleting = false;
        this.confirmDeleteOpen = false;
        this.pendingDeleteVehiculeId = null;
        this.pendingDeleteVehiculeLabel = '';
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
input, select { box-sizing: border-box; width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 8px; }
.field-hint { margin: 8px 0 0; color: #64748b; font-size: 12px; line-height: 1.4; }
button { margin-top: 10px; padding: 10px 14px; background: #4f46e5; color: #fff; border: 0; border-radius: 8px; cursor: pointer; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 9px; font-size: 14px; }
.danger { background: #dc2626; margin: 0; }
.small { margin: 0; padding: 6px 10px; font-size: 12px; }
.msg { margin-top: 10px; padding: 10px; border-radius: 8px; }
.ok { background: #dcfce7; color: #166534; }
.err { background: #fee2e2; color: #991b1b; }
.details-modal { display: grid; gap: 18px; }
.details-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.summary-pill { padding: 14px 16px; border-radius: 16px; background: linear-gradient(135deg, #f8fafc, #eef2ff); border: 1px solid #e2e8f0; }
.summary-label { display: block; margin-bottom: 6px; font-size: 12px; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
.details-block { padding: 16px; border-radius: 16px; border: 1px solid #eef2f7; background: #fcfcff; }
.details-block-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
.details-block-header h4 { margin: 0; font-size: 16px; }
.details-count { min-width: 28px; padding: 4px 9px; border-radius: 999px; background: #ede9fe; color: #6d28d9; font-size: 12px; font-weight: 700; text-align: center; }
.details-list { display: grid; gap: 10px; margin: 0; padding: 0; list-style: none; }
.details-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border-radius: 12px; background: #fff; border: 1px solid #eef2f7; }
.details-item strong { color: #0f172a; }
.details-item span { color: #475569; }
.empty-state { margin: 0; color: #64748b; }

@keyframes editorFlash {
  0% { transform: translateY(-2px); box-shadow: 0 0 0 0 rgba(124, 58, 237, 0.28); }
  55% { transform: translateY(0); box-shadow: 0 0 0 10px rgba(124, 58, 237, 0); }
  100% { box-shadow: 0 10px 30px rgba(91, 33, 182, 0.10); }
}

@media (max-width: 900px) {
  .page { padding: 16px; }
  .grid { grid-template-columns: 1fr; gap: 16px; }
  .searchbar { flex-direction: column; align-items: stretch; }
  .details-summary { grid-template-columns: 1fr; }
  .details-item { flex-direction: column; align-items: flex-start; }
}
</style>
