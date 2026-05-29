<template>
  <div class="dashboard-layout">
    <SidebarMenu />

    <!-- Main Content -->
    <main class="main-content">
      <div class="page-content">
        <header class="top-bar">
          <div>
            <h1>Tableau de bord</h1>
            <small class="updated-at">Mise à jour: {{ lastUpdatedAt }}</small>
          </div>
          <span class="role-badge">{{ role }}</span>
        </header>

        <div v-if="loading" class="info-banner">Chargement des statistiques...</div>
        <div v-if="error" class="error-banner">{{ error }}</div>

        <!-- Stats Cards -->
        <section class="stats-grid" aria-label="Statistiques du parc">
          <div class="stat-card blue">
            <div class="stat-icon">🚙</div>
            <div class="stat-info">
              <h3>{{ stats.vehicules }}</h3>
              <p>Véhicules</p>
            </div>
          </div>
          <div class="stat-card green">
            <div class="stat-icon">👤</div>
            <div class="stat-info">
              <h3>{{ stats.conducteurs }}</h3>
              <p>Conducteurs</p>
            </div>
          </div>
          <div class="stat-card orange">
            <div class="stat-icon">📋</div>
            <div class="stat-info">
              <h3>{{ stats.affectations }}</h3>
              <p>Affectations</p>
            </div>
          </div>
          <div class="stat-card red">
            <div class="stat-icon">🔧</div>
            <div class="stat-info">
              <h3>{{ stats.maintenances }}</h3>
              <p>Maintenances</p>
            </div>
          </div>
        </section>

        <!-- Welcome Message -->
        <div class="welcome-card">
          <h2>Bienvenue 👋</h2>
          <p>Système de gestion et d'analyse du parc automobile — SAPS</p>

          <div class="actions">
            <router-link to="/vehicules" class="action-btn">Voir les véhicules</router-link>
            <router-link to="/conducteurs" class="action-btn">Voir les conducteurs</router-link>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>


<script>
import SidebarMenu from '../components/SidebarMenu.vue';

export default {
  name: 'Dashboard',
  components: { SidebarMenu },
  data() {
    return {
      role: localStorage.getItem('role') || 'ADMIN',
      stats: {
        vehicules: 0,
        conducteurs: 0,
        affectations: 0,
        maintenances: 0,
      },
      loading: false,
      error: null,
      lastUpdatedAt: '—',
    };
  },
  mounted() {
    this.setAxiosToken();
    this.loadStats();
  },
  methods: {
    setAxiosToken() {
      const token = localStorage.getItem('token');
      if (token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      }
    },
    async loadStats() {
      this.loading = true;
      this.error = null;
      try {
        const [v, c, a, m] = await Promise.all([
          axios.get('/api/vehicules'),
          axios.get('/api/conducteurs'),
          axios.get('/api/affectations'),
          axios.get('/api/maintenances'),
        ]);
        this.stats.vehicules    = v.data.length;
        this.stats.conducteurs  = c.data.length;
        this.stats.affectations = a.data.length;
        this.stats.maintenances = m.data.length;
        this.lastUpdatedAt = new Date().toLocaleTimeString('fr-FR', {
          hour: '2-digit',
          minute: '2-digit',
        });
      } catch (err) {
        this.error = 'Impossible de charger les statistiques pour le moment.';
        console.error('Erreur chargement stats:', err);
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  height: 100vh;
  min-height: 0;
  overflow: hidden;
  background: #f6f7fb;
}

.main-content {
  flex: 1;
  min-width: 0;
  height: 100vh;
  overflow-y: auto;
  padding: 24px;
}

.page-content {
  width: min(100%, 1180px);
  margin: 0 auto;
}

.top-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 22px;
}

.top-bar h1 {
  margin: 0;
  color: #111827;
  font-size: 30px;
  line-height: 1.15;
}

.updated-at {
  display: inline-block;
  margin-top: 6px;
  color: #6b7280;
  font-size: 14px;
}

.role-badge {
  flex: 0 0 auto;
  max-width: 100%;
  padding: 8px 12px;
  border-radius: 999px;
  background: #eef2ff;
  color: #3730a3;
  font-weight: 700;
  font-size: 13px;
  line-height: 1;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 18px;
  margin-bottom: 24px;
}

.info-banner,
.error-banner {
  margin-bottom: 16px;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 14px;
}

.info-banner {
  background: #e0e7ff;
  color: #3730a3;
}

.error-banner {
  background: #fee2e2;
  color: #b91c1c;
}

.stat-card {
  background: white;
  border-radius: 12px;
  min-width: 0;
  min-height: 128px;
  padding: 22px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.stat-icon {
  font-size: 36px;
}

.stat-info h3 {
  font-size: 28px;
  line-height: 1;
  font-weight: bold;
  margin: 0;
}

.stat-info p {
  color: #666;
  margin: 4px 0 0;
  overflow-wrap: anywhere;
}

.stat-card.blue  { border-left: 4px solid #3b82f6; }
.stat-card.green { border-left: 4px solid #22c55e; }
.stat-card.orange{ border-left: 4px solid #f97316; }
.stat-card.red   { border-left: 4px solid #ef4444; }

.welcome-card {
  background: white;
  border-radius: 12px;
  padding: 28px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.welcome-card h2 {
  color: #1a1a2e;
  margin: 0 0 8px;
  font-size: 22px;
  line-height: 1.25;
}

.welcome-card p {
  color: #666;
  margin: 0;
}

.actions {
  margin-top: 20px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 40px;
  text-decoration: none;
  color: white;
  background: #4f46e5;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  line-height: 1.2;
  text-align: center;
  overflow-wrap: anywhere;
}

@media (max-width: 1100px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .dashboard-layout {
    flex-direction: column;
    height: 100dvh;
  }

  .dashboard-layout :deep(.sidebar) {
    width: 100%;
    height: auto;
    max-height: 42dvh;
    padding: 10px 12px;
  }

  .dashboard-layout :deep(.sidebar.collapsed) {
    width: 100%;
  }

  .dashboard-layout :deep(.sidebar-top) {
    padding-bottom: 10px;
  }

  .dashboard-layout :deep(.sidebar-nav) {
    display: flex;
    gap: 8px;
    padding: 10px 0 0;
    overflow-x: auto;
    overflow-y: hidden;
  }

  .dashboard-layout :deep(.nav-item) {
    flex: 0 0 auto;
    margin-bottom: 0;
  }

  .dashboard-layout :deep(.sidebar-footer) {
    display: none;
  }

  .main-content {
    height: auto;
    flex: 1;
    padding: 16px;
  }

  .top-bar {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .stat-card {
    min-height: 112px;
    padding: 18px;
  }

  .actions {
    flex-direction: column;
  }

  .action-btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .main-content {
    padding: 12px;
  }

  .top-bar h1 {
    font-size: 24px;
  }

  .welcome-card {
    padding: 20px;
  }

  .stat-card {
    align-items: flex-start;
  }

  .stat-icon {
    font-size: 30px;
  }
}
</style>
