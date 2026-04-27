<script setup lang="ts">
definePageMeta({
  layout: 'blank',
  public: true,
})

const session = useSession()
const route = useRoute()
const form = ref({
  email: 'test@example.com',
  password: 'password',
  remember: true,
})

const isPasswordVisible = ref(false)
const errorMessage = ref('')

const redirectPath = computed(() => {
  const redirect = Array.isArray(route.query.redirect) ? route.query.redirect[0] : route.query.redirect

  if (!redirect || !redirect.startsWith('/') || redirect.startsWith('//'))
    return '/'

  return redirect
})

async function handleLogin() {
  errorMessage.value = ''

  try {
    await session.login(form.value.email, form.value.password)
    await navigateTo(redirectPath.value)
  }
  catch (error: any) {
    errorMessage.value = error?.data?.message || error?.data?.errors?.email?.[0] || 'We could not sign you in with those credentials.'
  }
}
</script>

<template>
  <div class="school-login">
    <div class="school-login__panel school-login__panel--brand">
      <div class="school-login__brand-wrap">
        <div class="school-login__mark">
          SS
        </div>
        <div>
          <div class="school-kicker mb-2">
            School SaaS Enterprise
          </div>
          <h1 class="school-login__title">
            Operate every campus from one disciplined control surface.
          </h1>
          <p class="school-login__summary">
            Enrollment, attendance, results, fees, and leadership reporting lined up the way an enterprise school group actually works.
          </p>
        </div>
      </div>

      <div class="school-login__signals">
        <div class="school-login__signal">
          <span>Attendance compliance</span>
          <strong>94.6%</strong>
        </div>
        <div class="school-login__signal">
          <span>Collection strength</span>
          <strong>84%</strong>
        </div>
        <div class="school-login__signal">
          <span>Result readiness</span>
          <strong>87%</strong>
        </div>
      </div>
    </div>

    <div class="school-login__panel school-login__panel--form">
      <VCard class="school-signal-card school-login__card" flat>
        <VCardText class="pa-8">
          <div class="school-kicker mb-2">
            Sign in
          </div>
          <h2 class="text-h4 font-weight-bold mb-2">
            Welcome back
          </h2>
          <p class="text-body-1 text-medium-emphasis mb-6">
            Use your workspace credentials to enter the school operations desk.
          </p>

          <VAlert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ errorMessage }}
          </VAlert>

          <VForm @submit.prevent="handleLogin">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="form.email"
                  autofocus
                  label="Work email"
                  type="email"
                  placeholder="principal@northcampus.edu"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="form.password"
                  label="Password"
                  placeholder="••••••••••••"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>

              <VCol cols="12" class="d-flex align-center justify-space-between">
                <VCheckbox v-model="form.remember" label="Keep me signed in" />
                <a class="text-primary" href="javascript:void(0)">
                  Reset access
                </a>
              </VCol>

              <VCol cols="12">
                <VBtn block size="large" type="submit" :loading="session.loading.value">
                  Enter workspace
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </div>
  </div>
</template>
