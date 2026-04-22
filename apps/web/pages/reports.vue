<script setup lang="ts">
import { reportLibrary, reportQueue } from '@/utils/schoolDashboardData'

definePageMeta({
  layout: 'default',
})
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Reports"
      title="Reporting pipeline"
      subtitle="Generate operational, academic, and board-ready output from one managed queue."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-clock">
          Schedule
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-file-export">
          New export
        </VBtn>
      </template>
    </SchoolPageHeader>

    <VRow class="mb-2">
      <VCol
        v-for="library in reportLibrary"
        :key="library.label"
        cols="12"
        md="4"
      >
        <VCard class="school-signal-card h-100">
          <VCardText class="pa-5">
            <div class="school-kicker mb-3">
              Report library
            </div>
            <div class="text-h6 font-weight-bold mb-2">{{ library.label }}</div>
            <div class="text-body-2 text-medium-emphasis mb-4">{{ library.note }}</div>
            <div class="school-metric-card__value text-h6">{{ library.count }}</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VCard class="school-signal-card">
      <VCardText class="pt-2">
        <div class="school-alert-list">
          <div
            v-for="report in reportQueue"
            :key="report.name"
            class="school-alert-list__item"
          >
            <div>
              <div class="font-weight-medium">{{ report.name }}</div>
              <div class="text-body-2 text-medium-emphasis">{{ report.owner }} · {{ report.updated }}</div>
            </div>
            <div class="d-flex align-center gap-3">
              <VChip :color="report.status === 'Ready' ? 'success' : report.status === 'Refreshing' ? 'warning' : 'default'" variant="tonal" size="small">
                {{ report.status }}
              </VChip>
              <VBtn variant="text" color="primary" append-icon="tabler-arrow-right">
                Open
              </VBtn>
            </div>
          </div>
        </div>
      </VCardText>
    </VCard>
  </div>
</template>
