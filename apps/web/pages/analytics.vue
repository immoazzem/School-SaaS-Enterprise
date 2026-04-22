<script setup lang="ts">
import { analyticsCohorts, analyticsSignals, dashboardCollections, dashboardStats } from '@/utils/schoolDashboardData'

definePageMeta({
  layout: 'default',
})
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Analytics"
      title="Performance analytics"
      subtitle="Institutional health and delivery metrics for academic and operational leadership."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-chart-histogram">
          Compare periods
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-presentation-analytics">
          Leadership view
        </VBtn>
      </template>
    </SchoolPageHeader>

    <VRow class="mb-2">
      <VCol
        v-for="stat in dashboardStats"
        :key="stat.title"
        cols="12"
        md="6"
        xl="3"
      >
        <SchoolMetricCard v-bind="stat" />
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" xl="8">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Trend monitor
            </VCardTitle>
            <VCardSubtitle>Collection and compliance movement over recent periods.</VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <div class="school-bar-stack">
              <div
                v-for="row in dashboardCollections"
                :key="row.label"
                class="school-bar-stack__row"
              >
                <div class="school-bar-stack__label">{{ row.label }}</div>
                <div class="school-bar-stack__track">
                  <div class="school-bar-stack__fill" :style="{ width: `${row.value}%` }" />
                </div>
                <div class="school-bar-stack__value">{{ row.value }}%</div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" xl="4">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Signal pack
            </VCardTitle>
            <VCardSubtitle>The most useful ratios for a daily executive scan.</VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <div class="school-alert-list">
              <div
                v-for="channel in analyticsSignals"
                :key="channel.label"
                class="school-alert-list__item"
              >
                <div>
                  <div class="font-weight-medium">{{ channel.label }}</div>
                  <div class="text-body-2 text-medium-emphasis">{{ channel.note }}</div>
                </div>
                <div class="school-metric-card__value text-h6">{{ channel.value }}</div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol
        v-for="cohort in analyticsCohorts"
        :key="cohort.title"
        cols="12"
        md="4"
      >
        <VCard class="school-signal-card h-100">
          <VCardText class="pa-5">
            <div class="school-kicker mb-3">
              Cohort signal
            </div>
            <div class="text-h6 font-weight-bold mb-2">
              {{ cohort.title }}
            </div>
            <div class="text-body-1 text-medium-emphasis mb-4">
              {{ cohort.note }}
            </div>
            <VChip :color="cohort.tone" variant="tonal" size="small">
              {{ cohort.trend }}
            </VChip>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
