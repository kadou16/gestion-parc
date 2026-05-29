<template>
  <div class="layout">
    <SidebarMenu />

    <main class="page">
      <div class="page-content">
        <h1>Évaluations des conducteurs</h1>

        <div v-if="msg" class="msg ok">{{ msg }}</div>
        <div v-if="error" class="msg err">{{ error }}</div>
        <div v-if="feedbackMsg" :class="['msg', feedbackMsg.type]">{{ feedbackMsg.text }}</div>

        <form class="card" @submit.prevent="createEvaluation">
          <h3>Nouvelle évaluation</h3>

          <div class="grid">
            <div>
              <label>Conducteur</label>
              <select v-model="form.conducteur_id" required>
                <option value="">-- choisir --</option>
                <option v-for="c in conducteurs" :key="c.idConducteur" :value="c.idConducteur">
                  {{ c.utilisateur?.nom }} {{ c.utilisateur?.prenom }}
                </option>
              </select>
            </div>
            <div>
              <label>Nombre sinistres</label>
              <input v-model.number="form.nombreSinistres" type="number" min="0">
            </div>
            <div>
              <label>Retards</label>
              <input v-model.number="form.retards" type="number" min="0">
            </div>
            <div>
              <label>Date retour</label>
              <input v-model="form.date_retour" type="date">
            </div>
          </div>

          <button :disabled="loading">{{ loading ? 'Ajout...' : 'Ajouter' }}</button>
        </form>

        <section class="card">
          <div class="report-header">
            <h3>Rapport Évaluations</h3>
            <button class="small" @click="exportPDF" style="background:#16a34a; margin-top:0;">Exporter en PDF</button>
          </div>
          <div class="searchbar">
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Rechercher (nom, classification, score...)"
              @input="applySearch"
            >
            <button class="small" type="button" @click="applySearch">Rechercher</button>
          </div>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Conducteur</th>
                <th>Score calculé</th>
                <th>Classification</th>
                <th>Sinistres</th>
                <th>Retards</th>
                <th>Date retour</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="e in filteredEvaluations" :key="e.idEvaluation">
                <td>{{ e.idEvaluation }}</td>
                <td>{{ e.conducteur?.utilisateur?.nom || '-' }} {{ e.conducteur?.utilisateur?.prenom || '' }}</td>
                <td>{{ e.scoreCalcule }}</td>
                <td>{{ classifierConducteur(e.scoreCalcule) }}</td>
                <td>{{ e.nombreSinistres }}</td>
                <td>{{ e.retards }}</td>
                <td>{{ e.date_retour || '-' }}</td>
                <td>
                  <div class="table-actions">
                    <IconActionButton
                      icon="send"
                      variant="primary"
                      label="Envoyer un message de motivation/alerte"
                      @click="envoyerMessage(e)"
                    />
                    <IconActionButton
                      icon="delete"
                      variant="danger"
                      label="Supprimer l'évaluation"
                      @click="askDeleteEvaluation(e)"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </section>

        <ConfirmModal
          v-model="confirmDeleteOpen"
          title="Supprimer cette évaluation ?"
          :message="pendingDeleteEvaluationLabel ? `L'évaluation de ${pendingDeleteEvaluationLabel} sera supprimée définitivement.` : 'Cette action est définitive.'"
          :loading="deleting"
          @cancel="resetDeleteModal"
          @confirm="confirmDeleteEvaluation"
        />

        <!-- Send Message Modal -->
        <div v-if="sendMessageOpen" class="overlay" @click.self="sendMessageOpen = false">
          <div class="modal">
            <h3>Envoyer un message au conducteur</h3>
            <p>{{ pendingMessageText }}</p>
            <div class="modal-actions">
              <button class="btn-cancel" @click="sendMessageOpen = false" :disabled="sending">Annuler</button>
              <button class="btn-confirm" @click="confirmSendMessage" :disabled="sending">
                {{ sending ? 'Envoi...' : 'Envoyer' }}
              </button>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>

<script>
import axios from 'axios';
import ConfirmModal from '../components/ConfirmModal.vue';
import IconActionButton from '../components/IconActionButton.vue';
import SidebarMenu from '../components/SidebarMenu.vue';

import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

export default {
  name: 'EvaluationsPage',
  components: { SidebarMenu, ConfirmModal, IconActionButton },
  data() {
    return {
      conducteurs: [],
      evaluations: [],
      loading: false,
      confirmDeleteOpen: false,
      deleting: false,
      pendingDeleteEvaluationId: null,
      pendingDeleteEvaluationLabel: '',
      sendMessageOpen: false,
      sending: false,
      pendingMessageId: null,
      pendingMessageText: '',
      pendingMessageType: '',
      msg: '',
      error: '',
      feedbackMsg: null,
      searchTerm: '',
      appliedSearch: '',
      form: {
        conducteur_id: '',
        nombreSinistres: 0,
        retards: 0,
        date_retour: '',
      },
    };
  },
  mounted() {
    this.initAuth();
    this.loadAll();
  },
  computed: {
    filteredEvaluations() {
      if (!this.appliedSearch) return this.evaluations;
      return this.evaluations.filter((e) => this.toSearchText(e).includes(this.appliedSearch));
    },
  },
  methods: {
    applySearch() {
      this.appliedSearch = (this.searchTerm || '').trim().toLowerCase();
    },
    toSearchText(e) {
      const conducteurNom = `${e.conducteur?.utilisateur?.nom || ''} ${e.conducteur?.utilisateur?.prenom || ''}`.trim();
      return [
        e.idEvaluation,
        conducteurNom,
        e.scoreCalcule,
        this.classifierConducteur(e.scoreCalcule),
        e.nombreSinistres,
        e.retards,
        e.date_retour,
      ]
        .filter((value) => value !== null && value !== undefined)
        .join(' ')
        .toLowerCase();
    },
    initAuth() {
      const token = localStorage.getItem('token');
      if (!token) return this.$router.push('/');
      axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    },
    async loadAll() {
      const [c, e] = await Promise.all([axios.get('/api/conducteurs'), axios.get('/api/evaluations')]);
      this.conducteurs = c.data || [];
      this.evaluations = e.data || [];
    },
    async createEvaluation() {
      this.loading = true;
      this.msg = '';
      this.error = '';
      try {
        await axios.post('/api/evaluations', this.form);
        this.msg = 'Évaluation ajoutée.';
        this.form = { conducteur_id: '', nombreSinistres: 0, retards: 0, date_retour: '' };
        this.loadAll();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur ajout évaluation.';
      } finally {
        this.loading = false;
      }
    },
    askDeleteEvaluation(evaluation) {
      this.pendingDeleteEvaluationId = evaluation.idEvaluation;
      this.pendingDeleteEvaluationLabel = `${evaluation.conducteur?.utilisateur?.nom || ''} ${evaluation.conducteur?.utilisateur?.prenom || ''}`.trim();
      this.confirmDeleteOpen = true;
    },
    resetDeleteModal() {
      if (this.deleting) return;
      this.confirmDeleteOpen = false;
      this.pendingDeleteEvaluationId = null;
      this.pendingDeleteEvaluationLabel = '';
    },
    async confirmDeleteEvaluation() {
      if (!this.pendingDeleteEvaluationId) return;
      this.deleting = true;
      try {
        await axios.delete(`/api/evaluations/${this.pendingDeleteEvaluationId}`);
        this.msg = 'Évaluation supprimée.';
        this.loadAll();
      } catch (e) {
        this.error = 'Erreur suppression.';
      } finally {
        this.deleting = false;
        this.confirmDeleteOpen = false;
        this.pendingDeleteEvaluationId = null;
        this.pendingDeleteEvaluationLabel = '';
      }
    },
    classifierConducteur(score) {
      const value = Number(score || 0);
      if (value >= 75) return 'Excellent';
      if (value >= 50) return 'Moyen';
      return 'À risque';
    },
    envoyerMessage(e) {
      const score = e.scoreCalcule;
      const classification = this.classifierConducteur(score);

      this.pendingMessageId = e.idEvaluation;
      
      if (classification === 'Excellent') {
        this.pendingMessageText = `Félicitations pour votre excellente conduite ! Maintenez ce standard.`;
        this.pendingMessageType = 'ok';
      } else if (classification === 'Moyen') {
        this.pendingMessageText = `Attention, essayez d'améliorer votre conduite. Il est recommandé d'être plus prudent.`;
        this.pendingMessageType = 'warn';
      } else {
        this.pendingMessageText = `Alerte : Comportement à risque détecté ! Veuillez faire attention car des sanctions pourraient suivre.`;
        this.pendingMessageType = 'err';
      }

      this.sendMessageOpen = true;
    },
    async confirmSendMessage() {
      this.sending = true;
      try {
        await axios.put(`/api/evaluations/${this.pendingMessageId}`, {
          message: this.pendingMessageText
        });
        
        this.feedbackMsg = { type: this.pendingMessageType, text: 'Message envoyé au conducteur avec succès.' };
        setTimeout(() => { this.feedbackMsg = null; }, 5000);
        this.sendMessageOpen = false;
        this.loadAll();
      } catch (err) {
        this.feedbackMsg = { type: 'err', text: "Erreur lors de l'envoi du message." };
      } finally {
        this.sending = false;
      }
    },
    exportPDF() {
      const doc = new jsPDF();
      doc.text('Rapport des Évaluations', 14, 15);

      const tableData = this.evaluations.map(e => [
        `${e.conducteur?.utilisateur?.nom || ''} ${e.conducteur?.utilisateur?.prenom || ''}`,
        e.scoreCalcule,
        this.classifierConducteur(e.scoreCalcule),
        e.nombreSinistres,
        e.retards,
        e.date_retour || '-',
      ]);

      autoTable(doc, {
        head: [['Conducteur', 'Score', 'Classification', 'Sinistres', 'Retards', 'Date retour']],
        body: tableData,
        startY: 20,
      });

      doc.save('rapport_evaluations.pdf');
    }
  },
};
</script>

<style scoped>
.layout { display: flex; height: 100vh; overflow: hidden; }
.page { flex: 1; min-width: 0; height: 100vh; overflow-y: auto; padding: 24px; background: #f6f7fb; box-sizing: border-box; }
.page-content { width: min(100%, 1120px); margin: 0 auto; }
.card { background: #fff; padding: 16px; border-radius: 10px; margin-top: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 16px; }
.searchbar { display: flex; gap: 10px; margin: 10px 0; align-items: center; }
.report-header { display:flex; justify-content:space-between; align-items:center; gap:16px; }
label { display: block; margin: 8px 0 4px; font-size: 14px; }
input, select { box-sizing: border-box; width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 8px; }
button { margin-top: 10px; padding: 10px 14px; background: #4f46e5; color: #fff; border: 0; border-radius: 8px; cursor: pointer; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 9px; font-size: 14px; }
.danger { background: #dc2626; margin: 0; }
.msg { margin-top: 10px; padding: 10px; border-radius: 8px; font-weight: 500; }
.ok { background: #dcfce7; color: #166534; }
.warn { background: #fef08a; color: #854d0e; }
.err { background: #fee2e2; color: #991b1b; }

.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal { background: #fff; padding: 24px; border-radius: 12px; width: 400px; max-width: 90%; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.modal h3 { margin-top: 0; }
.modal p { color: #555; line-height: 1.5; }
.modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
.btn-cancel { background: #e5e7eb; color: #374151; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; }
.btn-confirm { background: #4f46e5; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; margin: 0; }

@media (max-width: 900px) {
  .page { padding: 16px; }
  .grid { grid-template-columns: 1fr; gap: 16px; }
  .searchbar,
  .report-header { flex-direction: column; align-items: stretch; }
}
</style>
