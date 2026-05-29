<template>
  <div class="layout">
    <SidebarMenu />

    <main class="page">
      <div class="page-content">
        <header class="header">
          <div>
            <h1>Dashboard Power BI</h1>
          </div>
        </header>

        <div class="card">
          <iframe
            class="powerbi-frame"
            src="https://app.powerbi.com/reportEmbed?reportId=74fbdbaa-ca8d-4685-9cbe-7333e076b7aa&autoAuth=true&ctid=190ce420-b157-44ae-bc2f-69563baa5a3b"
            frameborder="0"
            allowfullscreen
            title="Power BI Dashboard"
          ></iframe>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
import SidebarMenu from '../components/SidebarMenu.vue';

export default {
  name: 'PowerBIPage',
  components: { SidebarMenu },
  data() {
    return {
      embedUrl: import.meta.env.VITE_POWERBI_EMBED_URL || '',
    };
  },
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
  width: min(100%, 1280px);
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.card {
  background: #fff;
  border-radius: 12px;
  padding: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.powerbi-frame {
  width: 100%;
  height: 80vh;
  border: none;
  border-radius: 8px;
}

@media (max-width: 900px) {
  .page {
    padding: 16px;
  }
}
</style>
