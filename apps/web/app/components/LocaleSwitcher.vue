<script setup lang="ts">
const { locale, setLocale } = useI18n()
const apiLocale = useState<string>('app.locale', () => locale.value)

const localeOptions = [
  { code: 'en', label: 'English' },
  { code: 'bn', label: 'বাংলা' },
]

function chooseLocale(event: Event) {
  const nextLocale = (event.target as HTMLSelectElement).value
  apiLocale.value = nextLocale
  void setLocale(nextLocale)
}
</script>

<template>
  <label class="locale-switcher">
    <span>{{ $t('common.language') }}</span>
    <select :value="locale" @change="chooseLocale">
      <option v-for="option in localeOptions" :key="option.code" :value="option.code">
        {{ option.label }}
      </option>
    </select>
  </label>
</template>

<style scoped>
.locale-switcher {
  display: flex;
  gap: 8px;
  align-items: center;
  color: #26342f;
  font-weight: 800;
}

.locale-switcher select {
  min-height: 42px;
  border: 1px solid #cbd8d2;
  border-radius: 8px;
  padding: 0 10px;
  color: #163f34;
  background: #ffffff;
  font-weight: 800;
}
</style>
