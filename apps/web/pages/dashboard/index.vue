<script setup lang="ts">
import { dashboardPathForSchool } from '~/lib/accessControl'

const session = useSession()

await navigateTo(dashboardPathForSchool(session.selectedSchool.value), { replace: true })
</script>
