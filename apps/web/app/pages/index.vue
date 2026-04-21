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
  <main class="min-h-screen flex flex-col lg:flex-row bg-slate-50 relative overflow-hidden">
    <!-- Left Column: Branding (Dark/Glassy) -->
    <section class="lg:w-1/2 bg-slate-900 text-white flex flex-col justify-center p-8 lg:p-16 relative z-10">
      <!-- Subtle Background Pattern -->
      <div class="absolute inset-0 z-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 32px 32px;"></div>
      
      <div class="relative z-10 max-w-lg fade-in">
        <p class="text-brand-400 font-bold tracking-widest uppercase text-sm mb-4">Enterprise Operations</p>
        <h1 class="text-4xl lg:text-6xl font-display font-extrabold tracking-tight leading-tight mb-6 text-white mt-0">
          Run every school from one calm workspace.
        </h1>
        <p class="text-slate-300 text-lg md:text-xl leading-relaxed mb-10">
          Sign in, choose a school scope, and manage academics, people, and operations with absolute precision.
        </p>
        
        <div class="inline-flex items-center gap-3 px-4 py-2.5 rounded-full bg-white/10 border border-white/20 backdrop-blur-md">
          <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
          <span class="text-sm font-semibold text-slate-200">API: <strong class="text-white">{{ apiEndpoint }}</strong></span>
        </div>
      </div>
    </section>

    <!-- Right Column: Authentication -->
    <section class="lg:w-1/2 flex items-center justify-center p-8 lg:p-16 bg-white relative z-20 shadow-2xl">
      <div class="w-full max-w-md fade-in" style="animation-delay: 150ms;">
        <div class="mb-8 text-center lg:text-left">
          <div class="w-12 h-12 bg-brand-600 rounded-xl flex items-center justify-center shadow-lg shadow-brand-600/20 mb-6 mx-auto lg:mx-0">
            <span class="text-white font-bold text-xl">S</span>
          </div>
          <p class="text-sm font-bold text-brand-600 uppercase tracking-widest mb-2 m-0">Workspace Access</p>
          <h2 class="text-3xl font-display font-bold text-slate-900 tracking-tight m-0">Sign in to continue</h2>
        </div>

        <form @submit.prevent="submitLogin" class="flex flex-col gap-5">
          <div class="field">
            <label for="email">Email address</label>
            <input id="email" v-model="email" autocomplete="email" type="email" required />
          </div>
          <div class="field">
            <label for="password">Password</label>
            <input id="password" v-model="password" autocomplete="current-password" type="password" required />
          </div>

          <p v-if="error" class="error mt-2">{{ error }}</p>

          <button class="button mt-4 w-full" type="submit" :disabled="loading">
            <span v-if="loading" class="flex items-center gap-2">
              <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Authenticating...
            </span>
            <span v-else>Continue to Workspace</span>
          </button>
        </form>
      </div>
    </section>
  </main>
</template>
