<template>
  <div class="layout">
    <SidebarMenu />

    <main class="page">
      <div class="page-content">
        <h1>Documents</h1>

        <div v-if="msg" class="msg ok">{{ msg }}</div>
        <div v-if="error" class="msg err">{{ error }}</div>

        <form
          ref="editorCard"
          :class="['card', { 'card-editing': editMode, 'card-flash': flashEditor }]"
          @submit.prevent="submitForm"
        >
          <h3>{{ editMode ? 'Modifier le document' : 'Nouveau document' }}</h3>
          <div v-if="editMode" class="edit-banner">
            <strong>Mode modification activé.</strong>
            {{ editDocumentLabel ? ` Les données du document ${editDocumentLabel} ont été chargées dans ce formulaire.` : '' }}
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
                <option value="Assurance">Assurance</option>
                <option value="Visite technique">Visite technique</option>
              </select>
            </div>
          </div>

          <div class="grid">
            <div>
              <label>Date début</label>
              <input v-model="form.dateDebut" type="date" required>
            </div>
            <div>
              <label>Date expiration</label>
              <input v-model="form.dateExpiration" type="date" required>
            </div>
          </div>

          <div class="grid">
            <div>
              <label>Statut</label>
              <input v-model="form.statut" type="text" placeholder="Valide" required>
            </div>
          </div>

          <input
            ref="fileInput"
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            style="display:none"
            @change="onFileChange"
          >
          <input
            ref="rowFileInput"
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            style="display:none"
            @change="onRowFileChange"
          >

          <div class="upload-panel">
            <div class="upload-panel-copy">
              <div class="upload-title">Fichier du document</div>
              <div class="upload-subtitle">
                PDF, JPG ou PNG jusqu'à 10 Mo.
              </div>
            </div>

            <button class="small upload-btn" type="button" @click="openFilePicker">
              Téléverser un fichier
            </button>
          </div>

          <div :class="['upload-status', { ready: hasSelectedFile }]">
            <div class="upload-status-icon" aria-hidden="true">
              {{ hasSelectedFile ? '✓' : '↑' }}
            </div>
            <div class="upload-status-copy">
              <div class="upload-status-title">
                {{ hasSelectedFile ? 'Fichier prêt à être enregistré' : 'Aucun fichier sélectionné' }}
              </div>
              <div class="upload-status-text">
                {{ hasSelectedFile ? selectedFileName : 'Utilisez le bouton pour ajouter le document à ce dossier.' }}
              </div>
            </div>
          </div>

          <div class="actions-row">
            <div class="actions-main">
              <button :disabled="loading">{{ editMode ? (loading ? 'Mise à jour...' : 'Mettre à jour') : (loading ? 'Ajout...' : 'Ajouter') }}</button>
              <button type="button" class="btn-secondary" @click="cancelEdit" v-if="editMode">Annuler</button>
            </div>
          </div>
        </form>

        <section class="card">
          <h3>Liste des documents</h3>
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
                <th>Début</th>
                <th>Expiration</th>
                <th>Statut</th>
                <th>Fichier</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in filteredDocuments" :key="d.idDocument">
                <td>{{ d.idDocument }}</td>
                <td>{{ d.vehicule?.immatriculation || '-' }}</td>
                <td>{{ d.type }}</td>
                <td>{{ d.dateDebut }}</td>
                <td>{{ d.dateExpiration }}</td>
                <td>{{ d.statut }}</td>
                <td>
                  <span :class="['file-chip', { missing: !d.fichier_path }]">
                    {{ d.fichier_path ? 'Fichier lié' : 'Aucun fichier' }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <IconActionButton
                      v-if="d.fichier_path"
                      icon="view"
                      label="Visualiser le fichier"
                      @click="visualiserDocument(d)"
                    />
                    <IconActionButton
                      v-if="d.fichier_path"
                      icon="download"
                      variant="success"
                      label="Télécharger le fichier"
                      @click="telechargerDocument(d)"
                    />
                    <IconActionButton
                      v-else
                      icon="upload"
                      :label="rowUploadingId === d.idDocument ? 'Téléversement en cours' : 'Téléverser un fichier'"
                      :disabled="rowUploadingId === d.idDocument"
                      @click="openRowFilePicker(d)"
                    />
                    <IconActionButton
                      icon="edit"
                      variant="warning"
                      label="Modifier le document"
                      @click="editItem(d)"
                    />
                    <IconActionButton
                      icon="delete"
                      variant="danger"
                      label="Supprimer le document"
                      @click="askDeleteDocument(d)"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </section>

        <ConfirmModal
          v-model="confirmDeleteOpen"
          title="Supprimer ce document ?"
          :message="pendingDeleteDocumentLabel ? `Le document ${pendingDeleteDocumentLabel} sera supprimé définitivement.` : 'Cette action est définitive.'"
          :loading="deleting"
          @cancel="resetDeleteModal"
          @confirm="confirmDeleteDocument"
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
  name: 'DocumentsPage',
  components: { SidebarMenu, ConfirmModal, IconActionButton },
  data() {
    return {
      vehicules: [],
      documents: [],
      loading: false,
      editMode: false,
      editId: null,
      editDocumentLabel: '',
      flashEditor: false,
      flashTimeoutId: null,
      confirmDeleteOpen: false,
      deleting: false,
      pendingDeleteDocumentId: null,
      pendingDeleteDocumentLabel: '',
      rowUploadingId: null,
      rowUploadTargetId: null,
      rowUploadTargetLabel: '',
      msg: '',
      error: '',
      searchTerm: '',
      appliedSearch: '',
      form: {
        vehicule_id: '',
        type: 'Assurance',
        dateDebut: '',
        dateExpiration: '',
        statut: '',
      },
      selectedFile: null,
      selectedFileName: '',
    };
  },
  mounted() {
    this.initAuth();
    this.loadAll();
  },
  computed: {
    filteredDocuments() {
      if (!this.appliedSearch) return this.documents;
      return this.documents.filter((d) => this.toSearchText(d).includes(this.appliedSearch));
    },
    hasSelectedFile() {
      return Boolean(this.selectedFileName);
    },
  },
  methods: {
    applySearch() {
      this.appliedSearch = (this.searchTerm || '').trim().toLowerCase();
    },
    toSearchText(d) {
      return [
        d.idDocument,
        d.vehicule?.immatriculation,
        d.type,
        d.dateDebut,
        d.dateExpiration,
        d.statut,
        d.fichier_path,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
    },
    openFilePicker() {
      this.$refs.fileInput?.click();
    },
    onFileChange(event) {
      const file = event.target.files?.[0] || null;
      this.selectedFile = file;
      this.selectedFileName = file?.name || '';
    },
    openRowFilePicker(document) {
      this.rowUploadTargetId = document.idDocument;
      this.rowUploadTargetLabel = `${document.type}${document.vehicule?.immatriculation ? ` • ${document.vehicule.immatriculation}` : ''}`;
      this.$refs.rowFileInput?.click();
    },
    async onRowFileChange(event) {
      const file = event.target.files?.[0] || null;
      if (!file || !this.rowUploadTargetId) {
        return;
      }

      this.rowUploadingId = this.rowUploadTargetId;
      this.msg = '';
      this.error = '';

      try {
        const payload = new FormData();
        payload.append('_method', 'PUT');
        payload.append('fichier', file);

        await axios.post(`/api/documents/${this.rowUploadTargetId}`, payload, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });

        this.msg = `Fichier téléversé pour ${this.rowUploadTargetLabel || 'le document'}.`;
        await this.loadAll();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur pendant le téléversement du fichier.';
      } finally {
        this.rowUploadingId = null;
        this.rowUploadTargetId = null;
        this.rowUploadTargetLabel = '';
        if (this.$refs.rowFileInput) this.$refs.rowFileInput.value = '';
      }
    },
    initAuth() {
      const token = localStorage.getItem('token');
      if (!token) return this.$router.push('/');
      axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    },
    async loadAll() {
      const [v, d] = await Promise.all([axios.get('/api/vehicules'), axios.get('/api/documents')]);
      this.vehicules = v.data || [];
      this.documents = d.data || [];
    },
    async submitForm() {
      if (this.editMode) this.updateItem();
      else this.createDocument();
    },
    editItem(item) {
      this.editMode = true;
      this.editId = item.idVehicule || item.idConducteur || item.idAffectation || item.idMaintenance || item.idDocument || item.idEvaluation;
      this.editDocumentLabel = `${item.type}${item.vehicule?.immatriculation ? ` • ${item.vehicule.immatriculation}` : ''}`;
      this.form = {
        vehicule_id: item.vehicule_id || '',
        type: item.type || 'Assurance',
        dateDebut: item.dateDebut || '',
        dateExpiration: item.dateExpiration || '',
        statut: item.statut || '',
      };
      this.selectedFile = null;
      this.selectedFileName = item.fichier_path ? 'Fichier existant conservé' : '';
      if (this.$refs.fileInput) this.$refs.fileInput.value = '';
      this.msg = `Modification du document ${this.editDocumentLabel}.`;
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
      this.msg = '';
      this.error = '';
      try {
        if (this.selectedFile) {
          const payload = new FormData();
          payload.append('_method', 'PUT');
          payload.append('type', this.form.type);
          payload.append('dateDebut', this.form.dateDebut);
          payload.append('dateExpiration', this.form.dateExpiration);
          payload.append('statut', this.form.statut);
          payload.append('fichier', this.selectedFile);

          await axios.post(`/api/documents/${this.editId}`, payload, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          });
        } else {
          await axios.put(`/api/documents/${this.editId}`, this.form);
        }
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
      this.editDocumentLabel = '';
      this.flashEditor = false;
      clearTimeout(this.flashTimeoutId);
      this.form = { vehicule_id: '',
        type: 'Assurance',
        dateDebut: '',
        dateExpiration: '',
        statut: '', };
      this.selectedFile = null;
      this.selectedFileName = '';
      if (this.$refs.fileInput) this.$refs.fileInput.value = '';
    },
    async createDocument() {
      this.loading = true;
      this.msg = '';
      this.error = '';
      try {
        const payload = new FormData();
        payload.append('vehicule_id', this.form.vehicule_id);
        payload.append('type', this.form.type);
        payload.append('dateDebut', this.form.dateDebut);
        payload.append('dateExpiration', this.form.dateExpiration);
        payload.append('statut', this.form.statut);
        if (this.selectedFile) {
          payload.append('fichier', this.selectedFile);
        }

        await axios.post('/api/documents', payload, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });
        this.msg = 'Document ajouté.';
        this.form = { vehicule_id: '', type: 'Assurance', dateDebut: '', dateExpiration: '', statut: '' };
        this.selectedFile = null;
        this.selectedFileName = '';
        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        this.loadAll();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur ajout document.';
      } finally {
        this.loading = false;
      }
    },
    askDeleteDocument(document) {
      this.pendingDeleteDocumentId = document.idDocument;
      this.pendingDeleteDocumentLabel = `${document.type}${document.vehicule?.immatriculation ? ` • ${document.vehicule.immatriculation}` : ''}`;
      this.confirmDeleteOpen = true;
    },
    resetDeleteModal() {
      if (this.deleting) return;
      this.confirmDeleteOpen = false;
      this.pendingDeleteDocumentId = null;
      this.pendingDeleteDocumentLabel = '';
    },
    async confirmDeleteDocument() {
      if (!this.pendingDeleteDocumentId) return;
      this.deleting = true;
      try {
        await axios.delete(`/api/documents/${this.pendingDeleteDocumentId}`);
        this.msg = 'Document supprimé.';
        this.loadAll();
      } catch (e) {
        this.error = 'Erreur suppression.';
      } finally {
        this.deleting = false;
        this.confirmDeleteOpen = false;
        this.pendingDeleteDocumentId = null;
        this.pendingDeleteDocumentLabel = '';
      }
    },
    async renouvelerDocument(document) {
      const debut = new Date().toISOString().slice(0, 10);
      const fin = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().slice(0, 10);

      try {
        await axios.put(`/api/documents/${document.idDocument}`, {
          dateDebut: debut,
          dateExpiration: fin,
          statut: 'Valide',
        });
        this.msg = 'Document renouvelé.';
        this.loadAll();
      } catch (e) {
        this.error = 'Erreur renouvellement document.';
      }
    },
    async visualiserDocument(document) {
      if (!document?.fichier_path) {
        this.error = 'Aucun fichier associé à ce document.';
        return;
      }

      this.error = '';
      try {
        const response = await axios.get(`/api/documents/${document.idDocument}/visualiser`, {
          responseType: 'blob',
        });

        const fileURL = window.URL.createObjectURL(response.data);
        window.open(fileURL, '_blank');
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur visualisation fichier.';
      }
    },
    async telechargerDocument(document) {
      if (!document?.fichier_path) {
        this.error = 'Aucun fichier associé à ce document.';
        return;
      }

      this.error = '';
      try {
        const response = await axios.get(`/api/documents/${document.idDocument}/telecharger`, {
          responseType: 'blob',
        });

        const blobUrl = window.URL.createObjectURL(response.data);
        const link = window.document.createElement('a');
        link.href = blobUrl;
        link.download = `document_${document.idDocument}`;
        link.click();
        window.URL.revokeObjectURL(blobUrl);
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur téléchargement fichier.';
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
.upload-panel { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 18px; border: 1px dashed #c4b5fd; border-radius: 14px; background: linear-gradient(135deg, #faf5ff, #f5f3ff); margin-bottom: 14px; }
.upload-title { font-size: 15px; font-weight: 700; color: #4c1d95; }
.upload-subtitle { margin-top: 4px; font-size: 13px; color: #6d28d9; }
.upload-status { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border-radius: 14px; border: 1px solid #e5e7eb; background: #f8fafc; margin-bottom: 8px; }
.upload-status.ready { border-color: #86efac; background: #f0fdf4; }
.upload-status-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: #ede9fe; color: #6d28d9; font-size: 18px; font-weight: 800; }
.upload-status.ready .upload-status-icon { background: #dcfce7; color: #166534; }
.upload-status-title { font-size: 14px; font-weight: 700; color: #111827; }
.upload-status-text { margin-top: 2px; font-size: 13px; color: #64748b; }
.actions-row { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
.actions-main { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.btn-secondary { margin-top: 10px; padding: 10px 14px; background: #6b7280; color: #fff; border: 0; border-radius: 8px; cursor: pointer; }
.upload-btn { margin-left: auto; }
.file-chip { display: inline-flex; align-items: center; justify-content: center; min-height: 28px; padding: 5px 10px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; }
.file-chip.missing { background: #fef3c7; color: #92400e; }
label { display: block; margin: 8px 0 4px; font-size: 14px; }
input, select { box-sizing: border-box; width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 8px; }
button { margin-top: 10px; padding: 10px 14px; background: #4f46e5; color: #fff; border: 0; border-radius: 8px; cursor: pointer; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 9px; font-size: 14px; }
.danger { background: #dc2626; margin: 0; }
.small { margin: 0 8px 0 0; padding: 6px 10px; font-size: 12px; }
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
  .searchbar,
  .actions-row { flex-direction: column; align-items: stretch; }
  .upload-panel,
  .upload-status { flex-direction: column; align-items: flex-start; }
  .actions-main { width: 100%; }
  .upload-btn { margin-left: 0; }
  .small { margin-right: 0; }
}
</style>
