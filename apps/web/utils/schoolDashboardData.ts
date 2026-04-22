export const dashboardStats = [
  { title: 'Active students', value: '4,821', delta: '+3.2%', tone: 'primary', icon: 'tabler-users', note: 'Across 3 campuses' },
  { title: 'Attendance today', value: '94.6%', delta: '+1.1%', tone: 'success', icon: 'tabler-user-check', note: '48 homerooms reported' },
  { title: 'Fee collection', value: '৳ 2.4M', delta: '+12.5%', tone: 'warning', icon: 'tabler-receipt-2', note: 'Month to date' },
  { title: 'Result readiness', value: '87%', delta: '+6.4%', tone: 'info', icon: 'tabler-clipboard-check', note: 'Exam cycle completion' },
]

export const dashboardAlerts = [
  { title: 'Mid-term marks review lagging in Grade 10', severity: 'Critical', time: '12 min ago' },
  { title: 'Hostel fee defaulters crossed 40 accounts', severity: 'Warning', time: '35 min ago' },
  { title: 'Two classes pending attendance finalization', severity: 'Info', time: '1 hr ago' },
  { title: 'Transport contract renewal due this week', severity: 'Info', time: '2 hr ago' },
]

export const dashboardCollections = [
  { label: 'Apr 2026', value: 84 },
  { label: 'Mar 2026', value: 79 },
  { label: 'Feb 2026', value: 75 },
  { label: 'Jan 2026', value: 81 },
  { label: 'Dec 2025', value: 73 },
]

export const dashboardQuickActions = [
  { label: 'Mark attendance', to: '/attendance', icon: 'tabler-user-check' },
  { label: 'Publish marks', to: '/marks', icon: 'tabler-clipboard-check' },
  { label: 'Collect fees', to: '/finance/fees', icon: 'tabler-receipt-2' },
  { label: 'Issue notice', to: '/notices', icon: 'tabler-speakerphone' },
]

export const analyticsSignals = [
  { label: 'Attendance compliance', value: '96%', note: 'Homerooms filed by 10:30 AM' },
  { label: 'Academic completion', value: '88%', note: 'Active mark entry coverage' },
  { label: 'Collection conversion', value: '84%', note: 'Billed to collected this month' },
  { label: 'Guardian engagement', value: '71%', note: 'Portal opens in the last 7 days' },
]

export const analyticsCohorts = [
  { title: 'Grade 10 board batch', trend: '+4.8 pts', note: 'Mock-exam average versus last cycle', tone: 'success' },
  { title: 'Middle school retention', trend: '+1.9%', note: 'Students returning into the next academic year', tone: 'primary' },
  { title: 'Scholarship accounts', trend: '6 pending', note: 'Accounts desk still waiting for confirmation', tone: 'warning' },
]

export const studentRoster = [
  { name: 'Aisha Rahman', className: 'Grade 10-A', guardian: 'Nafisa Rahman', risk: 'Low', balance: 'Cleared' },
  { name: 'Rafi Islam', className: 'Grade 9-B', guardian: 'Habib Islam', risk: 'Watch', balance: '৳ 8,400' },
  { name: 'Nadia Hossain', className: 'Grade 10-B', guardian: 'Sadia Hossain', risk: 'Low', balance: 'Cleared' },
  { name: 'Karim Uddin', className: 'Grade 8-A', guardian: 'Morshed Uddin', risk: 'Intervention', balance: '৳ 14,500' },
  { name: 'Mitu Begum', className: 'Grade 7-C', guardian: 'Shamima Begum', risk: 'Low', balance: '৳ 2,200' },
]

export const attendanceRows = [
  { className: 'Grade 10-A', present: 42, absent: 3, late: 2, completedBy: 'J. Kabir' },
  { className: 'Grade 9-B', present: 38, absent: 2, late: 1, completedBy: 'S. Nahar' },
  { className: 'Grade 8-A', present: 35, absent: 6, late: 3, completedBy: 'M. Alam' },
  { className: 'Grade 7-C', present: 40, absent: 0, late: 0, completedBy: 'T. Ahmed' },
]

export const attendanceEscalations = [
  { className: 'Grade 8-A', issue: '6 absences crossed parent follow-up threshold', action: 'Guardian call list ready', tone: 'error' },
  { className: 'Grade 10-A', issue: 'Two transport students marked absent three times this week', action: 'Review route issue', tone: 'warning' },
  { className: 'Grade 9-B', issue: 'Attendance finalized early by the class teacher', action: 'No action needed', tone: 'success' },
]

export const marksRows = [
  { subject: 'Mathematics', progress: '92%', pending: 18, moderated: 'Complete' },
  { subject: 'English', progress: '84%', pending: 32, moderated: 'In review' },
  { subject: 'Physics', progress: '88%', pending: 21, moderated: 'Complete' },
  { subject: 'Chemistry', progress: '79%', pending: 44, moderated: 'Needs review' },
]

export const marksReleaseReadiness = [
  { title: 'Board classes', note: '2 subjects still in moderation review', value: '83%', tone: 'warning' },
  { title: 'Middle school', note: 'Release window opens tomorrow at 09:00', value: '91%', tone: 'success' },
  { title: 'Guardian result slips', note: 'Template refresh pending approval', value: '64%', tone: 'error' },
]

export const financeRows = [
  { stream: 'Tuition', collected: '৳ 1.42M', overdue: '৳ 0.21M', coverage: '87%' },
  { stream: 'Transport', collected: '৳ 0.28M', overdue: '৳ 0.04M', coverage: '82%' },
  { stream: 'Hostel', collected: '৳ 0.36M', overdue: '৳ 0.09M', coverage: '76%' },
  { stream: 'Examination', collected: '৳ 0.34M', overdue: '৳ 0.02M', coverage: '94%' },
]

export const financePressurePoints = [
  { title: 'Defaulters above ৳ 10,000', note: '18 accounts need direct escalation this week', status: 'Escalate', tone: 'error' },
  { title: 'Waiver requests', note: '7 submissions waiting for committee review', status: 'Review', tone: 'warning' },
  { title: 'Bank reconciliation', note: 'Yesterday closed with a clean match', status: 'Clear', tone: 'success' },
]

export const reportQueue = [
  { name: 'Monthly board pack', owner: 'Principal office', status: 'Ready', updated: '8 min ago' },
  { name: 'Fee defaulter follow-up sheet', owner: 'Accounts', status: 'Refreshing', updated: '14 min ago' },
  { name: 'Exam readiness summary', owner: 'Academics', status: 'Needs review', updated: '27 min ago' },
]

export const reportLibrary = [
  { label: 'Board reporting', count: '12 templates', note: 'Trustees, leadership, and audit committee packs' },
  { label: 'Academic exports', count: '8 templates', note: 'Results, readiness, and subject performance snapshots' },
  { label: 'Parent-facing output', count: '5 templates', note: 'Result slips, invoices, and student progress letters' },
]

export const classRows = [
  { name: 'Grade 10-A', teacher: 'J. Kabir', students: 45, room: 'A-301', occupancy: '93%' },
  { name: 'Grade 9-B', teacher: 'S. Nahar', students: 40, room: 'B-204', occupancy: '88%' },
  { name: 'Grade 8-A', teacher: 'M. Alam', students: 41, room: 'C-110', occupancy: '82%' },
  { name: 'Grade 7-C', teacher: 'T. Ahmed', students: 40, room: 'D-009', occupancy: '80%' },
]

export const classCapacitySignals = [
  { title: 'Rooms above 90% occupancy', value: '2', note: 'Review section balancing before admissions reopen' },
  { title: 'Homeroom teacher gaps', value: '0', note: 'All active classes have a named lead teacher' },
  { title: 'Weekly planning conflicts', value: '3', note: 'Timetable desk still needs to resolve overlaps' },
]

export const noticeRows = [
  { title: 'Mid-term exam routine published', audience: 'Students & guardians', channel: 'Portal + SMS', status: 'Live' },
  { title: 'Sports week registration open', audience: 'All campuses', channel: 'Portal', status: 'Scheduled' },
  { title: 'Fee waiver committee review', audience: 'Accounts + leadership', channel: 'Internal', status: 'Draft' },
]

export const noticePerformance = [
  { title: 'Guardian reach', value: '82%', note: 'Delivered or opened within the first 6 hours' },
  { title: 'Staff acknowledgment', value: '91%', note: 'Critical notices acknowledged in policy window' },
  { title: 'Queued sends', value: '6', note: 'Scheduled notices still waiting for their delivery slot' },
]

export const schoolRows = [
  { name: 'North Campus', students: '2,140', staff: '146', attendance: '95.1%', collection: '89%' },
  { name: 'Downtown Campus', students: '1,876', staff: '119', attendance: '93.4%', collection: '84%' },
  { name: 'Girls College Wing', students: '805', staff: '61', attendance: '96.2%', collection: '91%' },
]

export const settingsGroups = [
  { title: 'Academic governance', note: 'Result publication windows, term structure, moderation rules.', owner: 'Academic office' },
  { title: 'Finance controls', note: 'Collection policy, waiver approvals, invoice reminders, reconciliation.', owner: 'Accounts' },
  { title: 'Access and roles', note: 'Campus access, sensitive modules, audit visibility, sign-in policy.', owner: 'IT and compliance' },
  { title: 'Messaging policy', note: 'Notice approvals, SMS escalation, guardian communication defaults.', owner: 'Communications' },
]
