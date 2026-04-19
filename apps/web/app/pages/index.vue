<script setup lang="ts">
const auth = useAuth()
const config = useRuntimeConfig()
const router = useRouter()
const apiEndpoint = computed(() => `${config.public.apiBase}/v1`)

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
        <strong>{{ apiEndpoint }}</strong>
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
  position: relative;
  isolation: isolate;
  display: grid;
  min-height: 100vh;
  grid-template-columns: minmax(0, 1.1fr) minmax(320px, 440px);
  gap: 48px;
  align-items: center;
  padding: 56px;
  background:
    radial-gradient(circle at 82% 12%, rgba(238, 135, 203, 0.32), transparent 24rem),
    radial-gradient(circle at 16% 20%, rgba(255, 241, 190, 0.78), transparent 28rem),
    linear-gradient(180deg, #fffaf4 0%, #f5f0ed 54%, #eceff3 100%);
  overflow: hidden;
}

.login-page::before {
  position: absolute;
  inset: 18px;
  z-index: -1;
  border-radius: 8px;
  background: linear-gradient(115deg, #fff1be 28%, #ee87cb 70%, #b060ff);
  box-shadow: inset 0 0 0 1px rgba(17, 24, 39, 0.06);
  content: "";
  opacity: 0.86;
}

.login-page::after {
  position: absolute;
  inset: 26px;
  z-index: -1;
  border-radius: 8px;
  background:
    linear-gradient(90deg, rgba(255, 255, 255, 0.72), rgba(255, 255, 255, 0.2)),
    linear-gradient(rgba(17, 24, 39, 0.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(17, 24, 39, 0.05) 1px, transparent 1px);
  background-size: auto, 64px 64px, 64px 64px;
  content: "";
}

.login-intro {
  max-width: 760px;
  padding: 26px;
}

.eyebrow,
.panel-label {
  margin: 0 0 18px;
  color: #7c1938;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0;
  text-transform: uppercase;
}

h1 {
  max-width: 780px;
  margin: 0;
  color: #111827;
  font-size: clamp(3rem, 8vw, 7.4rem);
  font-weight: 760;
  letter-spacing: -0.065em;
  line-height: 0.84;
}

.intro-copy {
  max-width: 520px;
  margin: 28px 0 0;
  color: rgba(17, 24, 39, 0.72);
  font-size: 1.2rem;
  line-height: 1.6;
}

.status-line {
  display: inline-flex;
  max-width: 100%;
  gap: 12px;
  align-items: center;
  margin-top: 38px;
  border: 1px solid rgba(17, 24, 39, 0.07);
  border-radius: 999px;
  padding: 10px 12px;
  background: rgba(255, 255, 255, 0.42);
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.7),
    0 14px 30px rgba(17, 24, 39, 0.08);
  color: rgba(17, 24, 39, 0.64);
  backdrop-filter: blur(18px);
}

.status-line strong {
  overflow-wrap: anywhere;
  color: #111827;
}

.login-panel {
  display: grid;
  gap: 28px;
  border-color: rgba(255, 255, 255, 0.42);
  padding: 34px;
  background: rgba(255, 255, 255, 0.52);
}

.login-panel h2 {
  margin: 0;
  color: #111827;
  font-size: 2rem;
  letter-spacing: -0.035em;
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
