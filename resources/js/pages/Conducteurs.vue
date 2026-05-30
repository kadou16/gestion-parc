<template>
  <div class="layout">
    <SidebarMenu />

    <main class="page">
      <div class="page-content">
        <h1>Conducteurs</h1>

        <div v-if="msg" class="msg ok">{{ msg }}</div>
        <div v-if="error" class="msg err">{{ error }}</div>

        <form
          ref="editorCard"
          :class="['card', { 'card-editing': editMode, 'card-flash': flashEditor }]"
          @submit.prevent="submitForm"
        >
          <h3 v-if="!editMode">Ajouter un conducteur</h3>
          <h3 v-else>Modifier le conducteur</h3>
          <div v-if="editMode" class="edit-banner">
            <strong>Mode modification activé.</strong>
            {{ editConducteurLabel ? ` Les données de ${editConducteurLabel} ont été chargées dans ce formulaire.` : '' }}
          </div>

          <div class="grid">
            <div>
              <label>Nom</label>
              <input v-model="form.nom" type="text" required>
            </div>
            <div>
              <label>Prénom</label>
              <input v-model="form.prenom" type="text" required>
            </div>
            <div>
              <label>Email</label>
              <input v-model="form.email" type="email" required>
            </div>
            <div>
              <label>Mot de passe</label>
              <input v-model="form.motdePasse" type="password" required>
            </div>
            <div>
              <label>N° permis</label>
              <input v-model="form.numPermis" type="text" required>
            </div>
            <div>
              <label>Expiration du permis</label>
              <input v-model="form.DateExpPermis" type="date" required>
            </div>
          </div>

          <button :disabled="loading">{{ editMode ? 'Mettre à jour' : (loading ? 'Ajout...' : 'Ajouter') }}</button>
          <button type="button" @click="cancelEdit" v-if="editMode" style="background:#6b7280; margin-left: 10px;">Annuler</button>
        </form>

        <section class="card">
          <h3>Liste des conducteurs</h3>
          <div class="searchbar">
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Rechercher (nom complet, email, permis...)"
              @input="applySearch"
            >
            <button class="small" type="button" @click="applySearch">Rechercher</button>
          </div>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nom complet</th>
                <th>Email</th>
                <th>Permis</th>
                <th>Expiration du permis</th>
                <th>Score</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in filteredConducteurs" :key="c.idConducteur">
                <td>{{ c.idConducteur }}</td>
                <td>{{ c.utilisateur?.nom }} {{ c.utilisateur?.prenom }}</td>
                <td>{{ c.utilisateur?.email }}</td>
                <td>{{ c.numPermis }}</td>
                <td>{{ formatDisplayDate(c.DateExpPermis) }}</td>
                <td>
                  <div class="score-popover-wrap">
                    <button class="small" @click.stop="consulterScore(c)">Voir le score</button>
                    <div v-if="openedScoreId === c.idConducteur" class="score-popover" @click.stop>
                      <div class="score-popover-label">Score conducteur</div>
                      <div class="score-popover-value">{{ scores[c.idConducteur] ?? 0 }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="table-actions">
                    <IconActionButton
                      icon="edit"
                      variant="warning"
                      label="Modifier le conducteur"
                      @click="editConducteur(c)"
                    />
                    <IconActionButton
                      icon="delete"
                      variant="danger"
                      label="Supprimer le conducteur"
                      @click="askDeleteConducteur(c)"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </section>

        <ConfirmModal
          v-model="confirmDeleteOpen"
          title="Supprimer ce conducteur ?"
          :message="pendingDeleteConducteurLabel ? `Le conducteur ${pendingDeleteConducteurLabel} sera supprimé définitivement.` : 'Cette action est définitive.'"
          :loading="deleting"
          @cancel="resetDeleteModal"
          @confirm="confirmDeleteConducteur"
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
  name: 'ConducteursPage',
  components: { SidebarMenu, ConfirmModal, IconActionButton },
  data() {
    return {
      conducteurs: [],
      scores: {},
      openedScoreId: null,
      confirmDeleteOpen: false,
      deleting: false,
      pendingDeleteConducteurId: null,
      pendingDeleteConducteurLabel: '',
      loading: false,
      editMode: false,
      currentEditId: null,
      editConducteurLabel: '',
      flashEditor: false,
      flashTimeoutId: null,
      msg: '',
      error: '',
      searchTerm: '',
      appliedSearch: '',
      form: {
        nom: '',
        prenom: '',
        email: '',
        motdePasse: '',
        numPermis: '',
        DateExpPermis: '',
      },
    };
  },
  mounted() {
    this.initAuth();
    this.loadConducteurs();
    document.addEventListener('click', this.handleOutsideScoreClick);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleOutsideScoreClick);
  },
  computed: {
    filteredConducteurs() {
      if (!this.appliedSearch) return this.conducteurs;
      return this.conducteurs.filter((c) => this.toSearchText(c).includes(this.appliedSearch));
    },
  },
  methods: {
    applySearch() {
      this.appliedSearch = (this.searchTerm || '').trim().toLowerCase();
    },
    toSearchText(c) {
      const fullName = `${c.utilisateur?.nom || ''} ${c.utilisateur?.prenom || ''}`.trim();
      return [
        c.idConducteur,
        fullName,
        c.utilisateur?.email,
        c.numPermis,
        c.DateExpPermis,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
    },
    normalizeDateInput(dateValue) {
      if (!dateValue) return '';
      return String(dateValue).slice(0, 10);
    },
    formatDisplayDate(dateValue) {
      if (!dateValue) return '-';
      return new Intl.DateTimeFormat('fr-CA').format(new Date(dateValue));
    },
    initAuth() {
      const token = localStorage.getItem('token');
      if (!token) return this.$router.push('/');
    },
    submitForm() {
      if (this.editMode) {
        this.updateConducteur();
      } else {
        this.createConducteur();
      }
    },
    editConducteur(conducteur) {
      this.editMode = true;
      this.currentEditId = conducteur.idConducteur;
      this.editConducteurLabel = `${conducteur.utilisateur?.nom || ''} ${conducteur.utilisateur?.prenom || ''}`.trim();
      this.form = {
        nom: conducteur.utilisateur?.nom || '',
        prenom: conducteur.utilisateur?.prenom || '',
        email: conducteur.utilisateur?.email || '',
        motdePasse: '',
        numPermis: conducteur.numPermis,
        DateExpPermis: this.normalizeDateInput(conducteur.DateExpPermis),
      };
      this.msg = `Modification du conducteur ${this.editConducteurLabel}.`;
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
    cancelEdit() {
      this.editMode = false;
      this.currentEditId = null;
      this.editConducteurLabel = '';
      this.flashEditor = false;
      clearTimeout(this.flashTimeoutId);
      this.form = {
        nom: '',
        prenom: '',
        email: '',
        motdePasse: '',
        numPermis: '',
        DateExpPermis: '',
      };
      this.error = '';
    },
    async updateConducteur() {
      this.loading = true;
      this.msg = '';
      this.error = '';
      try {
        await axios.put(`/api/conducteurs/${this.currentEditId}`, this.form);
        this.msg = 'Conducteur modifié avec succès.';
        this.cancelEdit();
        this.loadConducteurs();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur lors de la modification.';
      } finally {
        this.loading = false;
      }
    },
    async loadConducteurs() {
      const res = await axios.get('/api/conducteurs');
      this.conducteurs = res.data || [];
    },
    async createConducteur() {
      this.loading = true;
      this.msg = '';
      this.error = '';
      try {
        await axios.post('/api/conducteurs', this.form);
        this.msg = 'Conducteur ajouté.';
        this.form = {
          nom: '',
          prenom: '',
          email: '',
          motdePasse: '',
          numPermis: '',
          DateExpPermis: '',
        };
        this.loadConducteurs();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur ajout conducteur.';
      } finally {
        this.loading = false;
      }
    },
    askDeleteConducteur(conducteur) {
      this.pendingDeleteConducteurId = conducteur.idConducteur;
      this.pendingDeleteConducteurLabel = `${conducteur.utilisateur?.nom || ''} ${conducteur.utilisateur?.prenom || ''}`.trim();
      this.confirmDeleteOpen = true;
    },
    resetDeleteModal() {
      if (this.deleting) return;
      this.confirmDeleteOpen = false;
      this.pendingDeleteConducteurId = null;
      this.pendingDeleteConducteurLabel = '';
    },
    async confirmDeleteConducteur() {
      if (!this.pendingDeleteConducteurId) return;
      this.deleting = true;
      try {
        await axios.delete(`/api/conducteurs/${this.pendingDeleteConducteurId}`);
        this.msg = 'Conducteur supprimé.';
        this.loadConducteurs();
      } catch (e) {
        this.error = 'Erreur suppression (peut-être lié à affectations/évaluations).';
      } finally {
        this.deleting = false;
        this.confirmDeleteOpen = false;
        this.pendingDeleteConducteurId = null;
        this.pendingDeleteConducteurLabel = '';
      }
    },
    handleOutsideScoreClick() {
      this.openedScoreId = null;
    },
    async consulterScore(conducteur) {
      if (this.openedScoreId === conducteur.idConducteur) {
        this.openedScoreId = null;
        return;
      }

      try {
        const res = await axios.get('/api/evaluations');
        const evaluations = (res.data || []).filter((e) => e.conducteur_id === conducteur.idConducteur);

        if (!evaluations.length) {
          this.scores[conducteur.idConducteur] = 0;
          this.openedScoreId = conducteur.idConducteur;
          return;
        }

        const tri = evaluations.sort((a, b) => b.idEvaluation - a.idEvaluation);
        this.scores[conducteur.idConducteur] = tri[0].scoreCalcule || 0;
        this.openedScoreId = conducteur.idConducteur;
      } catch (e) {
        this.error = 'Erreur consultation score.';
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
button { margin-top: 10px; padding: 10px 14px; background: #4f46e5; color: #fff; border: 0; border-radius: 8px; cursor: pointer; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 9px; font-size: 14px; }
.danger { background: #dc2626; margin: 0; }
.small { margin: 0; padding: 6px 10px; font-size: 12px; }
.score-popover-wrap { position: relative; display: inline-flex; }
.score-popover { position: absolute; top: calc(100% + 10px); left: 0; min-width: 150px; padding: 12px 14px; border-radius: 12px; background: #111827; color: #fff; box-shadow: 0 18px 36px rgba(15, 23, 42, 0.28); z-index: 20; }
.score-popover::before { content: ''; position: absolute; top: -6px; left: 18px; width: 12px; height: 12px; background: #111827; transform: rotate(45deg); }
.score-popover-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #cbd5e1; }
.score-popover-value { margin-top: 6px; font-size: 24px; font-weight: 700; line-height: 1; }
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
