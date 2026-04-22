<script setup lang="ts">
import { classCapacitySignals, classRows } from '@/utils/schoolDashboardData'

definePageMeta({
  layout: 'default',
})
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Classes"
      title="Class planning"
      subtitle="Keep sections balanced, staffed, and ready for the next teaching cycle."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-layout-grid">
          Room map
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-plus">
          Add class
        </VBtn>
      </template>
    </SchoolPageHeader>

    <VRow class="mb-2">
      <VCol
        v-for="signal in classCapacitySignals"
        :key="signal.title"
        cols="12"
        md="4"
      >
        <VCard class="school-signal-card h-100">
          <VCardText class="pa-5">
            <div class="school-kicker mb-3">
              Planning signal
            </div>
            <div class="school-metric-card__value text-h6 mb-2">{{ signal.value }}</div>
            <div class="text-h6 font-weight-bold mb-2">{{ signal.title }}</div>
            <div class="text-body-2 text-medium-emphasis">{{ signal.note }}</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VCard class="school-signal-card">
      <VCardText class="pt-2">
        <VTable class="school-data-table">
          <thead>
            <tr>
              <th>Class</th>
              <th>Teacher</th>
              <th>Students</th>
              <th>Room</th>
              <th>Occupancy</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in classRows" :key="row.name">
              <td class="font-weight-medium">{{ row.name }}</td>
              <td>{{ row.teacher }}</td>
              <td>{{ row.students }}</td>
              <td>{{ row.room }}</td>
              <td>{{ row.occupancy }}</td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </div>
</template>
