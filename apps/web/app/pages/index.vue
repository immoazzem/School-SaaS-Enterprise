<script setup lang="ts">
const auth = useAuth()
const config = useRuntimeConfig()
const router = useRouter()

const email = ref('test@example.com')
const password = ref('password')
const loading = ref(false)
const error = ref('')

async function submitLogin() {
  loading.value = true
  error.value = ''

  try {
    await auth.login(email.value, password.value)
    await router.push('/dashboard')
  } catch (loginError) {
    error.value = loginError instanceof Error ? loginError.message : 'Login failed.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="login-page">
    <section class="login-intro fade-in">
      <p class="eyebrow">Enterprise school operations</p>
      <h1>Run every school from one calm workspace.</h1>
      <p class="intro-copy">
        Sign in, choose a school, and manage the first academic setup module.
      </p>
      <div class="status-line">
        <span>API</span>
        <strong>{{ config.public.apiBase }}</strong>
      </div>
    </section>

    <section class="login-panel surface fade-in" aria-label="Sign in">
      <div>
        <p class="panel-label">Workspace access</p>
        <h2>Sign in</h2>
      </div>

      <form class="login-form" @submit.prevent="submitLogin">
        <div class="field">
          <label for="email">Email</label>
          <input id="email" v-model="email" autocomplete="email" type="email" />
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input id="password" v-model="password" autocomplete="current-password" type="password" />
        </div>

        <p v-if="error" class="error">{{ error }}</p>

        <button class="button" type="submit" :disabled="loading">
          {{ loading ? 'Signing in' : 'Continue' }}
        </button>
      </form>
    </section>
  </main>
</template>

<style scoped>
.login-page {
  display: grid;
  min-height: 100vh;
  grid-template-columns: minmax(0, 1.1fr) minmax(320px, 440px);
  gap: 48px;
  align-items: center;
  padding: 56px;
  background:
    linear-gradient(135deg, rgba(15, 95, 74, 0.12), transparent 34%),
    #f6f8f7;
}

.login-intro {
  max-width: 760px;
}

.eyebrow,
.panel-label {
  margin: 0 0 18px;
  color: #0f5f4a;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0;
  text-transform: uppercase;
}

h1 {
  max-width: 780px;
  margin: 0;
  color: #15221d;
  font-size: clamp(3rem, 8vw, 7.4rem);
  font-weight: 800;
  line-height: 0.92;
}

.intro-copy {
  max-width: 520px;
  margin: 28px 0 0;
  color: #4d6158;
  font-size: 1.2rem;
  line-height: 1.6;
}

.status-line {
  display: inline-flex;
  max-width: 100%;
  gap: 12px;
  align-items: center;
  margin-top: 38px;
  border: 1px solid #d4e0dc;
  border-radius: 8px;
  padding: 10px 12px;
  background: rgba(255, 255, 255, 0.74);
  color: #607169;
}

.status-line strong {
  overflow-wrap: anywhere;
  color: #17231e;
}

.login-panel {
  display: grid;
  gap: 28px;
  padding: 30px;
}

.login-panel h2 {
  margin: 0;
  color: #16201c;
  font-size: 2rem;
}

.login-form {
  display: grid;
  gap: 18px;
}

button:disabled {
  cursor: progress;
  opacity: 0.7;
}

@media (max-width: 860px) {
  .login-page {
    grid-template-columns: 1fr;
    gap: 30px;
    padding: 28px;
  }

  h1 {
    font-size: clamp(2.6rem, 16vw, 4.4rem);
  }
}
</style>
