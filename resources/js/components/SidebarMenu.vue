<template>
  <aside :class="['sidebar', { collapsed }]">
    <div class="sidebar-top">
      <div class="brand">
        <span class="brand-mark">🚗</span>
        <span v-if="!collapsed" class="brand-text">Parc Auto</span>
      </div>

      <button
        class="toggle-btn"
        type="button"
        :aria-label="collapsed ? 'Ouvrir le menu' : 'Réduire le menu'"
        @click="toggleSidebar"
      >
        <span aria-hidden="true">{{ collapsed ? '☰' : '✕' }}</span>
      </button>
    </div>

    <nav class="sidebar-nav">
      <router-link
        v-for="item in visibleItems"
        :key="item.to"
        :to="item.to"
        class="nav-item"
        active-class="active"
        :title="collapsed ? item.label : ''"
      >
        <span class="nav-icon" aria-hidden="true">{{ item.icon }}</span>
        <span v-if="!collapsed" class="nav-label">{{ item.label }}</span>
        <span
          v-if="item.to === '/alertes' && unreadCount > 0"
          :class="['badge-alert', { compact: collapsed }]"
        >
          {{ collapsed ? '' : unreadCount }}
        </span>
      </router-link>
    </nav>

    <div class="sidebar-footer">
      <button class="logout-btn" type="button" :title="collapsed ? 'Déconnexion' : ''" @click="logout">
        <span class="nav-icon" aria-hidden="true">🚪</span>
        <span v-if="!collapsed" class="nav-label">Déconnexion</span>
      </button>
    </div>
  </aside>
</template>

<script>
import axios, { clearAuthSession } from '../services/api';

export default {
  name: 'SidebarMenu',
  data() {
    return {
      role: localStorage.getItem('role') || '',
      unreadCount: 0,
      collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    };
  },
  computed: {
    visibleItems() {
      return this.navItems.filter((item) => item.roles.includes(this.role));
    },
    navItems() {
      return [
        { to: '/dashboard', label: 'Dashboard', icon: '📊', roles: ['Admin'] },
        { to: '/vehicules', label: 'Véhicules', icon: '🚙', roles: ['Admin'] },
        { to: '/conducteurs', label: 'Conducteurs', icon: '👤', roles: ['Admin'] },
        { to: '/affectations', label: 'Affectations', icon: '📋', roles: ['Admin', 'Conducteur'] },
        { to: '/maintenances', label: 'Maintenances', icon: '🔧', roles: ['Admin'] },
        { to: '/documents', label: 'Documents', icon: '📄', roles: ['Admin'] },
        { to: '/evaluations', label: 'Évaluations', icon: '⭐', roles: ['Admin'] },
        { to: '/alertes', label: 'Alertes', icon: '🔔', roles: ['Admin'] },
        { to: '/powerbi', label: 'Power BI', icon: '📈', roles: ['Admin'] },
        { to: '/profil', label: 'Profil', icon: '🧾', roles: ['Admin', 'Conducteur'] },
      ];
    },
  },
  mounted() {
    if (this.role === 'Admin') {
      this.loadUnreadAlerts();
    }
  },
  methods: {
    async loadUnreadAlerts() {
      try {
        const response = await axios.get('/api/alertes');
        const alertes = response.data;
        this.unreadCount = alertes.filter(a => a.statut === 'Non lue').length;
      } catch (err) {
        console.error('Erreur chargement alertes:', err);
      }
    },
    toggleSidebar() {
      this.collapsed = !this.collapsed;
      localStorage.setItem('sidebarCollapsed', String(this.collapsed));
    },
    logout() {
      axios.post('/api/logout').finally(() => {
        clearAuthSession();
        this.$router.push('/');
      });
    },
  },
};
</script>

<style scoped>
.sidebar {
  width: 264px;
  flex-shrink: 0;
  height: 100%;
  background: #1a1a2e;
  color: white;
  display: flex;
  flex-direction: column;
  padding: 14px 12px 18px;
  overflow-x: hidden;
  overflow-y: hidden;
  scrollbar-width: none;
  -ms-overflow-style: none;
  transition: width 0.25s ease, padding 0.25s ease;
}

.sidebar.collapsed {
  width: 84px;
  padding-inline: 10px;
}

.sidebar-top {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 6px 4px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.brand {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: opacity 0.2s ease;
}

.brand-mark {
  width: 40px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: linear-gradient(135deg, #5b21b6, #7c3aed);
  font-size: 20px;
  box-shadow: 0 10px 24px rgba(91, 33, 182, 0.28);
}

.brand-text {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 0.01em;
  white-space: nowrap;
}

.toggle-btn {
  width: 38px;
  height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.06);
  color: #fff;
  cursor: pointer;
  transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease, opacity 0.2s ease;
}

.toggle-btn:hover {
  background: rgba(124, 58, 237, 0.18);
  border-color: rgba(167, 139, 250, 0.45);
  transform: translateY(-1px);
}

.sidebar-nav {
  flex: 1;
  min-height: 0;
  padding: 16px 0;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.sidebar::-webkit-scrollbar,
.sidebar-nav::-webkit-scrollbar {
  width: 0;
  height: 0;
  display: none;
}

.nav-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 50px;
  margin-bottom: 6px;
  padding: 12px 14px;
  border-radius: 14px;
  color: #b7b8c9;
  text-decoration: none;
  transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
}

.nav-item:hover,
.nav-item.active {
  background: linear-gradient(135deg, #5b21b6, #7c3aed);
  color: white;
  transform: translateX(2px);
}

.sidebar.collapsed .nav-item {
  justify-content: center;
  padding-inline: 0;
}

.sidebar.collapsed .sidebar-top {
  justify-content: center;
  padding-inline: 0;
}

.sidebar.collapsed .brand {
  justify-content: center;
}

.sidebar.collapsed .toggle-btn {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  opacity: 0;
  pointer-events: none;
}

.sidebar.collapsed .sidebar-top:hover .toggle-btn,
.sidebar.collapsed .sidebar-top:focus-within .toggle-btn {
  opacity: 1;
  pointer-events: auto;
}

.sidebar.collapsed .sidebar-top:hover .brand,
.sidebar.collapsed .sidebar-top:focus-within .brand {
  opacity: 0.18;
}

.sidebar.collapsed .sidebar-top:hover .toggle-btn {
  transform: translate(-50%, -50%);
}

.nav-icon {
  flex: 0 0 24px;
  width: 24px;
  text-align: center;
  font-size: 19px;
}

.nav-label {
  min-width: 0;
  white-space: nowrap;
}

.sidebar-footer {
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.logout-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  min-height: 52px;
  padding: 12px 14px;
  border: none;
  border-radius: 14px;
  background: rgba(220, 38, 38, 0.14);
  color: #fff;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease;
}

.logout-btn:hover {
  background: rgba(220, 38, 38, 0.24);
  transform: translateY(-1px);
}

.sidebar.collapsed .logout-btn {
  padding-inline: 0;
}

.badge-alert {
  margin-left: auto;
  min-width: 22px;
  padding: 2px 7px;
  background-color: #ef4444;
  color: white;
  border-radius: 999px;
  font-size: 11px;
  line-height: 18px;
  text-align: center;
  font-weight: bold;
}

.badge-alert.compact {
  position: absolute;
  top: 9px;
  right: 9px;
  min-width: 10px;
  width: 10px;
  height: 10px;
  padding: 0;
  font-size: 0;
  line-height: 10px;
}

@media (max-width: 900px) {
  .sidebar {
    width: 228px;
  }
}
</style>
