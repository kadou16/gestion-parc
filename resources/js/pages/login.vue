<template>
  <div class="login-container">
    <div class="login-box">
      <h2>🚗 Gestion Parc Auto</h2>
      <h3>Connexion</h3>

      <div v-if="error" class="error-msg">{{ error }}</div>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label>Email</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="email@saps.dz"
            required
          />
        </div>

        <div class="form-group">
          <label>Mot de passe</label>
          <input
            v-model="form.motdePasse"
            type="password"
            placeholder="••••••"
            required
          />
        </div>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Connexion...' : 'Se connecter' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import axios, { setAuthSession } from '../services/api';

export default {
  name: 'Login',
  data() {
    return {
      form: {
        email: '',
        motdePasse: '',
      },
      error: null,
      loading: false,
    };
  },
  methods: {
    async handleLogin() {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.post('/api/login', this.form);
        const { token, role } = response.data;

        setAuthSession(token, role);

        if (role === 'Admin') {
          this.$router.push('/dashboard');
        } else if (role === 'Gestionnaire') {
          this.$router.push('/vehicules');
        } else {
          this.$router.push('/profil');
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Erreur de connexion';
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f2f5;
}

.login-box {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  width: 100%;
  max-width: 400px;
}

h2 {
  text-align: center;
  color: #1a1a2e;
  margin-bottom: 8px;
}

h3 {
  text-align: center;
  color: #666;
  font-weight: normal;
  margin-bottom: 30px;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 6px;
  color: #333;
  font-weight: 500;
}

input {
  width: 100%;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  box-sizing: border-box;
  transition: border 0.2s;
}

input:focus {
  outline: none;
  border-color: #4f46e5;
}

button {
  width: 100%;
  padding: 12px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.2s;
}

button:hover:not(:disabled) {
  background: #4338ca;
}

button:disabled {
  background: #a5b4fc;
  cursor: not-allowed;
}

.error-msg {
  background: #fee2e2;
  color: #dc2626;
  padding: 10px;
  border-radius: 8px;
  margin-bottom: 20px;
  text-align: center;
}
</style>
