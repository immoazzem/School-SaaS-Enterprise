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
  <label class="flex items-center gap-2 px-3 py-1.5 ring-1 ring-inset ring-slate-200 rounded-full bg-white hover:bg-slate-50 transition-colors shadow-sm cursor-pointer whitespace-nowrap">
    <span class="text-xs font-semibold text-slate-500">{{ $t('common.language') }}:</span>
    <select :value="locale" @change="chooseLocale" class="text-sm font-semibold text-slate-700 bg-transparent border-0 p-0 pr-5 appearance-none focus:ring-0 cursor-pointer outline-none" style="background-image: url('data:image/svg+xml,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3e%3cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27M6 8l4 4 4-4%27/%3e%3c/svg%3e'); background-position: right center; background-repeat: no-repeat; background-size: 1.25em 1.25em;">
      <option v-for="option in localeOptions" :key="option.code" :value="option.code">
        {{ option.label }}
      </option>
    </select>
  </label>
</template>
