<script setup lang="ts">
import type { AcademicClass, AcademicSection, StudentEnrollment } from '~/composables/useApi'

definePageMeta({
  layout: 'default',
})

interface ListResponse<T> {
  data: T[]
}

type ClassTableRow = {
  id: number
  name: string
  teacher: string
  students: number
  room: string
  occupancy: string
}

type PlanningSignal = {
  title: string
  value: string
  note: string
}

const session = useSession()
const loading = ref(false)
const errorMessage = ref('')
const rows = ref<ClassTableRow[]>([])
const signals = ref<PlanningSignal[]>([])

async function loadClasses() {
  if (!session.selectedSchool.value) {
    rows.value = []
    signals.value = []
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const schoolId = session.selectedSchool.value.id
    const [classResponse, sectionResponse, enrollmentResponse] = await Promise.all([
      useApiFetch<ListResponse<AcademicClass>>(`/schools/${schoolId}/academic-classes?per_page=100`),
      useApiFetch<ListResponse<AcademicSection>>(`/schools/${schoolId}/academic-sections?per_page=100`),
      useApiFetch<ListResponse<StudentEnrollment>>(`/schools/${schoolId}/student-enrollments?status=active&per_page=300`),
    ])

    const sectionsByClass = new Map<number, AcademicSection[]>()
    sectionResponse.data.forEach((section) => {
      const bucket = sectionsByClass.get(section.academic_class_id) ?? []
      bucket.push(section)
      sectionsByClass.set(section.academic_class_id, bucket)
    })

    const studentsByClass = new Map<number, number>()
    enrollmentResponse.data.forEach((enrollment) => {
      studentsByClass.set(enrollment.academic_class_id, (studentsByClass.get(enrollment.academic_class_id) ?? 0) + 1)
    })

    rows.value = classResponse.data.map((academicClass) => {
      const classSections = sectionsByClass.get(academicClass.id) ?? []
      const firstSection = classSections[0]
      const studentCount = studentsByClass.get(academicClass.id) ?? 0
      const totalCapacity = classSections.reduce((sum, section) => sum + (section.capacity ?? 0), 0)
      const occupancy = totalCapacity > 0 ? `${Math.round((studentCount / totalCapacity) * 100)}%` : 'N/A'

      return {
        id: academicClass.id,
        name: academicClass.name,
        teacher: firstSection ? `${classSections.length} active section${classSections.length > 1 ? 's' : ''}` : 'Needs section setup',
        students: studentCount,
        room: firstSection?.room ?? 'Not assigned',
        occupancy,
      }
    })

    const overloadedRooms = rows.value.filter(row => Number.parseInt(row.occupancy, 10) >= 90).length
    const classesWithoutSections = rows.value.filter(row => row.teacher === 'Needs section setup').length
    const averageStudents = rows.value.length
      ? Math.round(rows.value.reduce((sum, row) => sum + row.students, 0) / rows.value.length)
      : 0

    signals.value = [
      {
        title: 'Rooms above 90% occupancy',
        value: overloadedRooms.toString(),
        note: 'Review balancing before the next admissions cycle.',
      },
      {
        title: 'Classes missing sections',
        value: classesWithoutSections.toString(),
        note: 'These classes still need active section planning.',
      },
      {
        title: 'Average class load',
        value: averageStudents.toString(),
        note: 'Students per academic class in the active school.',
      },
    ]
  }
  catch {
    errorMessage.value = 'Class planning data could not be loaded from the API right now.'
    rows.value = []
    signals.value = []
  }
  finally {
    loading.value = false
  }
}

watch(() => session.selectedSchool.value?.id, loadClasses, { immediate: true })
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Classes"
      title="Class planning"
      subtitle="Keep sections balanced, staffed, and ready for the next teaching cycle."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-layout-grid" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/academic-sections` : '/schools'">
          Sections
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-plus" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/academic-classes` : '/schools'">
          Manage classes
        </VBtn>
      </template>
    </SchoolPageHeader>

    <VAlert
      v-if="errorMessage"
      type="warning"
      variant="tonal"
      class="mb-4"
    >
      {{ errorMessage }}
    </VAlert>

    <VRow class="mb-2">
      <VCol
        v-for="signal in signals"
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
        <div
          v-if="loading"
          class="text-body-2 text-medium-emphasis"
        >
          Loading class planning data...
        </div>
        <VTable v-else class="school-data-table">
          <thead>
            <tr>
              <th>Class</th>
              <th>Section plan</th>
              <th>Students</th>
              <th>Room</th>
              <th>Occupancy</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
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
