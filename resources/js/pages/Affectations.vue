<template>
  <div class="layout">
    <SidebarMenu />

    <main class="page">
      <div class="page-content">
        <h1>Affectations</h1>

        <div v-if="msg" class="msg ok">{{ msg }}</div>
        <div v-if="error" class="msg err">{{ error }}</div>

        <form
          v-if="isAdmin"
          ref="editorCard"
          :class="['card', { 'card-editing': editMode, 'card-flash': flashEditor }]"
          @submit.prevent="submitForm"
        >
          <h3 v-if="!editMode">Nouvelle affectation</h3>
          <h3 v-else>Modifier l'affectation</h3>
          <div v-if="editMode" class="edit-banner">
            <strong>Mode modification activé.</strong>
            {{ editAffectationLabel ? ` Les données de ${editAffectationLabel} ont été chargées dans ce formulaire.` : '' }}
          </div>

          <div class="grid">
            <div>
              <label>Véhicule</label>
              <select v-model="form.vehicule_id" required>
                <option value="">-- choisir --</option>
                <option 
                  v-for="v in (editMode ? vehicules : vehiculesDisponibles)" 
                  :key="v.idVehicule" 
                  :value="v.idVehicule"
                >
                  {{ v.immatriculation }} - {{ v.marque }} {{ v.modele }} ({{ v.etat }})
                </option>
              </select>
            </div>

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
              <label>Date début</label>
              <input v-model="form.dateDebut" type="date" required>
            </div>

            <div>
              <label>Date fin</label>
              <input v-model="form.dateFin" type="date">
            </div>

            <div>
              <label for="heure_depart">Heure départ</label>
              <input id="heure_depart" v-model="form.heure_depart" type="time" required>
            </div>

            <div>
              <label for="heure_retour">Heure retour</label>
              <input id="heure_retour" v-model="form.heure_retour" type="time">
            </div>

            <div>
              <label>État départ</label>
              <input v-model="form.etatDepart" type="text" placeholder="Bon" required>
            </div>

            <div>
              <label>État retour <span style="font-size:0.85em;color:#6b7280;">(optionnel)</span></label>
              <input v-model="form.etatRetour" type="text" placeholder="Bon">
            </div>
          </div>

          <div class="grid">
            <div>
              <label>Kilométrage départ</label>
              <input v-model="form.kilometrage_depart" type="number" min="0" step="1" placeholder="120000" required>
            </div>

            <div>
              <label>Kilométrage retour <span style="font-size:0.85em;color:#6b7280;">(optionnel)</span></label>
              <input v-model="form.kilometrage_retour" type="number" min="0" step="1" placeholder="120250">
            </div>
          </div>

          <div>
            <label>Mission</label>
            <input v-model="form.mission" type="text" placeholder="Transport étudiants" required>
          </div>

          <button :disabled="loading">{{ editMode ? 'Mettre à jour' : (loading ? 'Enregistrement...' : 'Enregistrer Affectation') }}</button>
          <button type="button" v-if="editMode" @click="cancelEdit" style="background:#6b7280; margin-left:10px;">Annuler</button>
        </form>

        <section class="card">
          <h3>Calendrier des affectations</h3>
          <div class="calendar-legend">
            <span class="legend-item">
              <span class="legend-pill legend-pill-start">Début</span>
              <span>jour de départ</span>
            </span>
            <span class="legend-item">
              <span class="legend-pill legend-pill-end">Fin</span>
              <span>jour de retour</span>
            </span>
          </div>
          <div style="margin-top:20px;">
            <FullCalendar :options="calendarOptions" />
          </div>
        </section>

        <section class="card">
          <h3>Liste des affectations</h3>
          <div class="searchbar">
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Rechercher (immat, conducteur, mission, état...)"
              @input="applySearch"
            >
            <button class="small" type="button" @click="applySearch">Rechercher</button>
          </div>
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Véhicule</th>
                <th>Conducteur</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Heure départ</th>
                <th>Heure retour</th>
                <th>État départ</th>
                <th>État retour</th>
                <th>Mission</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in filteredAffectations" :key="a.idAffectation">
                <td>{{ a.idAffectation }}</td>
                <td>{{ a.vehicule?.immatriculation || '-' }}</td>
                <td>{{ a.conducteur?.utilisateur?.nom || '-' }} {{ a.conducteur?.utilisateur?.prenom || '' }}</td>
                <td>{{ a.dateDebut }}</td>
                <td>{{ a.dateFin || '-' }}</td>
                <td>{{ a.heure_depart || '-' }}</td>
                <td>{{ a.heure_retour || '-' }}</td>
                <td>{{ a.etatDepart }}</td>
                <td>{{ a.etatRetour || '-' }}</td>
                <td>{{ a.mission }}</td>
                <td>
                  <div v-if="isAdmin" class="table-actions">
                    <IconActionButton
                      icon="edit"
                      variant="warning"
                      label="Modifier l'affectation"
                      @click="editAffectation(a)"
                    />
                    <IconActionButton
                      icon="delete"
                      variant="danger"
                      label="Supprimer l'affectation"
                      @click="askDeleteAffectation(a)"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </section>

        <ConfirmModal
          v-model="confirmDeleteOpen"
          title="Supprimer cette affectation ?"
          :message="pendingDeleteAffectationLabel || 'Cette affectation sera supprimée définitivement.'"
          :loading="deleting"
          @cancel="resetDeleteModal"
          @confirm="confirmDeleteAffectation"
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

import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'

export default {
  name: 'AffectationsPage',
  components: { SidebarMenu, FullCalendar, ConfirmModal, IconActionButton },
  data() {
    return {
      vehicules: [],
      conducteurs: [],
      affectations: [],
      role: localStorage.getItem('role') || '',
      myConducteurId: null,
      loading: false,
      editMode: false,
      currentEditId: null,
      editAffectationLabel: '',
      flashEditor: false,
      flashTimeoutId: null,
      confirmDeleteOpen: false,
      deleting: false,
      pendingDeleteAffectationId: null,
      pendingDeleteAffectationLabel: '',
      msg: '',
      error: '',
      searchTerm: '',
      appliedSearch: '',
      form: {
        vehicule_id: '',
        conducteur_id: '',
        dateDebut: '',
        dateFin: '',
        heure_depart: '',
        heure_retour: '',
        etatDepart: '',
        etatRetour: '',
        kilometrage_depart: '',
        kilometrage_retour: '',
        mission: '',
      },
      calendarOptions: {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        locale: 'fr',
        eventDisplay: 'block',
        dayMaxEventRows: 3,
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,dayGridWeek'
        },
        events: []
      }
    };
  },
  mounted() {
    this.initAuth();
    this.calendarOptions.eventContent = this.renderCalendarEvent;
    this.calendarOptions.eventDidMount = this.attachCalendarTooltip;
    this.loadAll();
  },
  computed: {
    isAdmin() {
      return this.role === 'Admin';
    },
    vehiculesDisponibles() {
      return this.vehicules.filter((v) => v.statut === 'Disponible');
    },
    filteredAffectations() {
      if (!this.appliedSearch) return this.affectations;
      return this.affectations.filter((a) => this.toSearchText(a).includes(this.appliedSearch));
    },
  },
  methods: {
    applySearch() {
      this.appliedSearch = (this.searchTerm || '').trim().toLowerCase();
    },
    toSearchText(a) {
      const conducteurNom = `${a.conducteur?.utilisateur?.nom || ''} ${a.conducteur?.utilisateur?.prenom || ''}`.trim();
      return [
        a.idAffectation,
        a.vehicule?.immatriculation,
        conducteurNom,
        a.dateDebut,
        a.dateFin,
        a.heure_depart,
        a.heure_retour,
        a.etatDepart,
        a.etatRetour,
        a.kilometrage_depart,
        a.kilometrage_retour,
        a.mission,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
    },
    initAuth() {
      const token = localStorage.getItem('token');
      if (!token) {
        this.$router.push('/');
        return;
      }
    },
    submitForm() {
      if (this.editMode) {
        this.updateAffectation();
      } else {
        this.createAffectation();
      }
    },
    editAffectation(affectation) {
      this.editMode = true;
      this.currentEditId = affectation.idAffectation;
      const vehicule = affectation.vehicule?.immatriculation || 'véhicule inconnu';
      const conducteur = `${affectation.conducteur?.utilisateur?.nom || ''} ${affectation.conducteur?.utilisateur?.prenom || ''}`.trim() || 'conducteur inconnu';
      this.editAffectationLabel = `${vehicule} vers ${conducteur}`;
      this.form = {
        vehicule_id: affectation.vehicule_id,
        conducteur_id: affectation.conducteur_id,
        dateDebut: affectation.dateDebut,
        dateFin: affectation.dateFin,
        heure_depart: affectation.heure_depart,
        heure_retour: affectation.heure_retour,
        etatDepart: affectation.etatDepart,
        etatRetour: affectation.etatRetour,
        kilometrage_depart: affectation.kilometrage_depart,
        kilometrage_retour: affectation.kilometrage_retour,
        mission: affectation.mission,
      };
      this.msg = `Modification de l'affectation ${this.editAffectationLabel}.`;
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
      this.editAffectationLabel = '';
      this.flashEditor = false;
      clearTimeout(this.flashTimeoutId);
      this.form = {
        vehicule_id: '',
        conducteur_id: '',
        dateDebut: '',
        dateFin: '',
        heure_depart: '',
        heure_retour: '',
        etatDepart: '',
        etatRetour: '',
        kilometrage_depart: '',
        kilometrage_retour: '',
        mission: '',
      };
      this.error = '';
    },
    async loadAll() {
      await Promise.all([this.loadVehicules(), this.loadConducteurs()]);

      if (this.role === 'Conducteur') {
        const me = await axios.get('/api/me');
        const userId = me.data?.user?.id;
        const found = (this.conducteurs || []).find((c) => c.utilisateur_id === userId);
        this.myConducteurId = found?.idConducteur || null;
      }

      await this.loadAffectations();
    },
    async loadVehicules() {
      const res = await axios.get('/api/vehicules');
      this.vehicules = res.data || [];
    },
    async loadConducteurs() {
      const res = await axios.get('/api/conducteurs');
      this.conducteurs = res.data || [];
    },
    async loadAffectations() {
      const res = await axios.get('/api/affectations');
      const all = res.data || [];
      if (this.role === 'Conducteur' && this.myConducteurId) {
        this.affectations = all.filter((a) => a.conducteur_id === this.myConducteurId);
      } else if (this.role === 'Conducteur') {
        this.affectations = [];
      } else {
        this.affectations = all;
      }
      this.updateCalendarEvents();
    },
    updateCalendarEvents() {
      this.calendarOptions.events = this.affectations.map(a => {
        const v = this.vehicules.find(v => v.idVehicule === a.vehicule_id) || {};
        const c = this.conducteurs.find(c => c.idConducteur === a.conducteur_id) || {};
        const conducteurNom = `${c.utilisateur?.nom || ''} ${c.utilisateur?.prenom || ''}`.trim();
        const dateRetour = a.dateFin || a.dateDebut;
        return {
          title: `${v.immatriculation} - ${conducteurNom} (${a.mission})`,
          start: a.dateDebut,
          end: a.dateFin ? new Date(new Date(a.dateFin).getTime() + 86400000).toISOString().split('T')[0] : a.dateDebut,
          backgroundColor: '#4f46e5',
          borderColor: '#4f46e5',
          classNames: ['affectation-calendar-event'],
          extendedProps: {
            vehiculeLabel: v.immatriculation || 'Véhicule',
            conducteurLabel: conducteurNom || 'Conducteur',
            missionLabel: a.mission || 'Mission',
            startLabel: this.formatCalendarDate(a.dateDebut),
            endLabel: this.formatCalendarDate(dateRetour),
            startTimeLabel: this.formatCalendarTime(a.heure_depart),
            endTimeLabel: this.formatCalendarTime(a.heure_retour || a.heure_depart),
          },
        };
      });
    },
    formatCalendarDate(dateValue) {
      if (!dateValue) return '-';
      return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
      }).format(new Date(`${dateValue}T00:00:00`));
    },
    formatCalendarTime(timeValue) {
      if (!timeValue) return '-';
      return String(timeValue).slice(0, 5);
    },
    escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    },
    renderCalendarEvent(arg) {
      const {
        vehiculeLabel,
        conducteurLabel,
        missionLabel,
        startLabel,
        endLabel,
        startTimeLabel,
        endTimeLabel,
      } = arg.event.extendedProps;
      const sameDay = startLabel === endLabel;
      const startBadge = arg.isStart
        ? `<span class="fc-aff-badge fc-aff-badge-start">${sameDay ? 'Le' : 'Début'} ${this.escapeHtml(startLabel)} • ${this.escapeHtml(startTimeLabel)}</span>`
        : '';
      const endBadge = !sameDay && arg.isEnd
        ? `<span class="fc-aff-badge fc-aff-badge-end">Fin ${this.escapeHtml(endLabel)} • ${this.escapeHtml(endTimeLabel)}</span>`
        : '';
      return {
        html: `
          <div class="fc-aff-card">
            ${startBadge}
            <div class="fc-aff-main">
              <div class="fc-aff-title">${this.escapeHtml(vehiculeLabel)} • ${this.escapeHtml(conducteurLabel)}</div>
              <div class="fc-aff-mission">${this.escapeHtml(missionLabel)}</div>
            </div>
            ${endBadge}
          </div>
        `,
      };
    },
    attachCalendarTooltip(info) {
      const {
        vehiculeLabel,
        conducteurLabel,
        missionLabel,
        startLabel,
        endLabel,
        startTimeLabel,
        endTimeLabel,
      } = info.event.extendedProps;
      info.el.title = `${vehiculeLabel} • ${conducteurLabel}\nMission: ${missionLabel}\nDébut: ${startLabel} à ${startTimeLabel}\nFin: ${endLabel} à ${endTimeLabel}`;
    },
    async createAffectation() {
      this.loading = true;
      this.msg = '';
      this.error = '';
      try {
        await axios.post('/api/affectations', this.form);
        this.msg = 'Affectation ajoutée.';
        this.form = {
          vehicule_id: '',
          conducteur_id: '',
          dateDebut: '',
          dateFin: '',
          heure_depart: '',
          heure_retour: '',
          etatDepart: '',
          etatRetour: '',
          kilometrage_depart: '',
          kilometrage_retour: '',
          mission: '',
        };
        this.loadAffectations();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur pendant l\'ajout.';
      } finally {
        this.loading = false;
      }
    },
    async updateAffectation() {
      this.loading = true;
      this.msg = '';
      this.error = '';
      try {
        await axios.put(`/api/affectations/${this.currentEditId}`, this.form);
        this.msg = 'Affectation modifiée.';
        this.cancelEdit();
        this.loadAffectations();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur pendant la modification.';
      } finally {
        this.loading = false;
      }
    },
    askDeleteAffectation(affectation) {
      this.pendingDeleteAffectationId = affectation.idAffectation;
      const vehicule = affectation.vehicule?.immatriculation || 'véhicule inconnu';
      const conducteur = `${affectation.conducteur?.utilisateur?.nom || ''} ${affectation.conducteur?.utilisateur?.prenom || ''}`.trim() || 'conducteur inconnu';
      this.pendingDeleteAffectationLabel = `Affectation ${vehicule} vers ${conducteur}.`;
      this.confirmDeleteOpen = true;
    },
    resetDeleteModal() {
      if (this.deleting) return;
      this.confirmDeleteOpen = false;
      this.pendingDeleteAffectationId = null;
      this.pendingDeleteAffectationLabel = '';
    },
    async confirmDeleteAffectation() {
      if (!this.pendingDeleteAffectationId) return;
      this.deleting = true;
      try {
        await axios.delete(`/api/affectations/${this.pendingDeleteAffectationId}`);
        this.msg = 'Affectation supprimée.';
        this.loadAffectations();
      } catch (e) {
        this.error = 'Erreur suppression.';
      } finally {
        this.deleting = false;
        this.confirmDeleteOpen = false;
        this.pendingDeleteAffectationId = null;
        this.pendingDeleteAffectationLabel = '';
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
.calendar-legend { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 14px; }
.legend-item { display: inline-flex; align-items: center; gap: 8px; color: #475569; font-size: 13px; }
.legend-pill { display: inline-flex; align-items: center; justify-content: center; min-height: 24px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.legend-pill-start { background: #dcfce7; color: #166534; }
.legend-pill-end { background: #ffedd5; color: #c2410c; }
label { display: block; margin: 8px 0 4px; font-size: 14px; }
input, select { box-sizing: border-box; width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 8px; }
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

:deep(.fc .affectation-calendar-event) {
  border: 0;
  box-shadow: none;
}

:deep(.fc .affectation-calendar-event .fc-event-main) {
  padding: 0;
}

:deep(.fc-aff-card) {
  display: flex;
  align-items: center;
  gap: 8px;
  min-height: 26px;
  padding: 4px 8px;
  border-radius: 10px;
  background: linear-gradient(135deg, #5b21b6, #6d28d9);
  color: #fff;
  box-shadow: 0 8px 16px rgba(91, 33, 182, 0.2);
}

:deep(.fc-aff-main) {
  min-width: 0;
  flex: 1;
}

:deep(.fc-aff-title) {
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

:deep(.fc-aff-mission) {
  font-size: 10px;
  opacity: 0.9;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

:deep(.fc-aff-badge) {
  flex: 0 0 auto;
  padding: 3px 7px;
  border-radius: 999px;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

:deep(.fc-aff-badge-start) {
  background: rgba(220, 252, 231, 0.95);
  color: #166534;
}

:deep(.fc-aff-badge-end) {
  background: rgba(255, 237, 213, 0.95);
  color: #c2410c;
}

@media (max-width: 900px) {
  .page { padding: 16px; }
  .grid { grid-template-columns: 1fr; gap: 16px; }
  .searchbar { flex-direction: column; align-items: stretch; }
  .calendar-legend { flex-direction: column; align-items: flex-start; }
}
</style>
