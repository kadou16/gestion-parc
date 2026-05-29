<template>
  <div class="layout">
    <SidebarMenu />

    <main class="page">
      <div class="page-content">
        <h1>Mon profil</h1>

        <div v-if="msg" class="msg ok">{{ msg }}</div>
        <div v-if="error" class="msg err">{{ error }}</div>

        <section class="card">
          <h3>Mes informations</h3>

        <div class="grid">
          <div>
            <label>Nom</label>
            <input v-model="profil.nom" type="text" />
          </div>
          <div>
            <label>Prénom</label>
            <input v-model="profil.prenom" type="text" />
          </div>
          <div>
            <label>Email</label>
            <input v-model="profil.email" type="email" />
          </div>
          <div>
            <label>Rôle</label>
            <input v-model="profil.role" type="text" readonly />
          </div>
          <div>
            <label>Nouveau mot de passe</label>
            <input v-model="profil.motdePasse" type="password" placeholder="Laisser vide pour ne pas changer" />
          </div>
          <div>
            <label>Confirmation mot de passe</label>
            <input v-model="profil.motdePasseConfirm" type="password" placeholder="Confirmer le mot de passe" />
          </div>
        </div>

          <button @click="saveProfil">Mettre à jour profil</button>
        </section>

        <section v-if="profil.role === 'Conducteur'" class="card">
          <h3>Espace Conducteur</h3>

        <div class="grid">
          <div>
            <label>N° permis</label>
            <input :value="conducteur.numPermis || ''" type="text" readonly />
          </div>
          <div>
            <label>Expiration du permis</label>
            <input :value="formatDisplayDate(conducteur.DateExpPermis)" type="text" readonly />
          </div>
        </div>

          <button @click="consulterScore">Voir mon score</button>
          <p class="score" v-if="score !== null">Score actuel: {{ score }}</p>
          <div v-if="notification" :class="['msg', notificationType]">{{ notification }}</div>
        </section>
      </div>
    </main>
  </div>
</template>

<script>
import axios from 'axios';
import SidebarMenu from '../components/SidebarMenu.vue';

export default {
  name: 'ProfilPage',
  components: { SidebarMenu },
  data() {
    return {
      profil: {
        id: null,
        nom: '',
        prenom: '',
        email: '',
        role: '',
        motdePasse: '',
        motdePasseConfirm: '',
      },
      conducteur: {
        idConducteur: null,
        numPermis: '',
        DateExpPermis: '',
      },
      score: null,
      notification: '',
      notificationType: '',
      msg: '',
      error: '',
    };
  },
  mounted() {
    this.initAuth();
    this.loadProfil();
  },
  methods: {
    formatDisplayDate(dateValue) {
      if (!dateValue) return '';
      return new Intl.DateTimeFormat('fr-CA').format(new Date(dateValue));
    },
    initAuth() {
      const token = localStorage.getItem('token');
      if (!token) {
        this.$router.push('/');
        return;
      }
      axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    },
    async loadProfil() {
      this.msg = '';
      this.error = '';
      try {
        const res = await axios.get('/api/me');
        const user = res.data?.user || {};

        this.profil.id = user.id;
        this.profil.nom = user.nom || '';
        this.profil.prenom = user.prenom || '';
        this.profil.email = user.email || '';
        this.profil.role = user.role || '';

        if (this.profil.role === 'Conducteur') {
          await this.loadConducteurData();
        }
      } catch (e) {
        this.error = 'Erreur chargement profil.';
      }
    },
    async loadConducteurData() {
      const res = await axios.get('/api/conducteurs');
      const list = res.data || [];
      const found = list.find((c) => c.utilisateur_id === this.profil.id);
      if (found) {
        this.conducteur = found;
      }
    },
    async saveProfil() {
      this.msg = '';
      this.error = '';

      if (this.profil.motdePasse && this.profil.motdePasse !== this.profil.motdePasseConfirm) {
        this.error = 'La confirmation du mot de passe ne correspond pas.';
        return;
      }

      try {
        const payload = {
          nom: this.profil.nom,
          prenom: this.profil.prenom,
          email: this.profil.email,
        };

        if (this.profil.motdePasse) {
          payload.motdePasse = this.profil.motdePasse;
        }

        await axios.put('/api/me', payload);
        this.msg = 'Profil mis à jour avec succès.';
        this.profil.motdePasse = '';
        this.profil.motdePasseConfirm = '';
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur mise à jour profil.';
      }
    },
    async consulterScore() {
      this.error = '';
      this.notification = '';
      if (!this.conducteur.idConducteur) {
        this.error = 'Conducteur non trouvé.';
        return;
      }

      try {
        const res = await axios.get('/api/evaluations');
        const evaluations = (res.data || []).filter(
          (e) => e.conducteur_id === this.conducteur.idConducteur
        );

        if (!evaluations.length) {
          this.score = 0;
          this.setNotification(0);
          return;
        }

        const tri = evaluations.sort((a, b) => b.idEvaluation - a.idEvaluation);
        this.score = tri[0].scoreCalcule || 0;
        this.setNotification(this.score);
      } catch (e) {
        this.error = 'Erreur calcul score.';
      }
    },
    setNotification(score) {
      const numericScore = Number(score);
      if (numericScore >= 75) {
        this.notification = "Bravo pour votre conduite responsable !";
        this.notificationType = "ok";
      } else if (numericScore >= 50) {
        this.notification = "Nous vous proposons une formation ou un suivi pour améliorer votre conduite.";
        this.notificationType = "warn";
      } else {
        this.notification = "Alerte : Votre score est insuffisant. Une décision managériale (restriction d'accès ou suspension) est en cours d'évaluation.";
        this.notificationType = "err";
      }
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
label { display: block; margin: 8px 0 4px; font-size: 14px; }
input { width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 8px; }
button { margin-top: 10px; padding: 10px 14px; background: #4f46e5; color: #fff; border: 0; border-radius: 8px; cursor: pointer; }
.score { margin-top: 10px; font-weight: bold; }
.msg { margin-top: 10px; padding: 10px; border-radius: 8px; }
.ok { background: #dcfce7; color: #166534; }
.warn { background: #fef08a; color: #854d0e; }
.err { background: #fee2e2; color: #991b1b; }

@media (max-width: 900px) {
  .page { padding: 16px; }
  .grid { grid-template-columns: 1fr; gap: 16px; }
}
</style>
