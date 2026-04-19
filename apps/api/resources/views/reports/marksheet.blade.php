<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Marksheet</title>
    <style>
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0; text-align: center; }
        h2 { color: #374151; font-size: 15px; margin: 4px 0 18px; text-align: center; }
        h3 { font-size: 13px; margin: 18px 0 8px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #f3f4f6; font-weight: bold; }
        th, td { border: 1px solid #d1d5db; padding: 7px 8px; text-align: left; vertical-align: top; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 16px; padding-bottom: 12px; }
        .muted { color: #6b7280; }
        .meta { margin-bottom: 14px; }
        .meta td { border: 0; padding: 2px 6px 2px 0; }
        .label { color: #374151; font-weight: bold; width: 120px; }
        .pass { color: #047857; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        .summary { margin-top: 18px; }
        .footer { border-top: 1px solid #d1d5db; color: #6b7280; font-size: 10px; margin-top: 28px; padding-top: 8px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $school->name }}</h1>
        <h2>Student Marksheet</h2>
        <div class="muted">Generated {{ $generatedAt }}@if ($requestedBy) by {{ $requestedBy }}@endif / Job {{ $jobId }}</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Student</td>
            <td>{{ $target->student?->full_name ?? 'N/A' }}</td>
            <td class="label">Admission No</td>
            <td>{{ $target->student?->admission_no ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Class</td>
            <td>{{ $target->academicClass?->name ?? 'N/A' }}</td>
            <td class="label">Section</td>
            <td>{{ $target->academicSection?->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Roll No</td>
            <td>{{ $target->roll_no ?? 'N/A' }}</td>
            <td class="label">Academic Year</td>
            <td>{{ $target->academicYear?->name ?? $exam->academicYear?->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Exam</td>
            <td>{{ $exam->name }}</td>
            <td class="label">Exam Type</td>
            <td>{{ $exam->examType?->name ?? 'N/A' }}</td>
        </tr>
    </table>

    <h3>Subject Marks</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Subject</th>
                <th>Full Marks</th>
                <th>Pass Marks</th>
                <th>Obtained</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($marksEntries as $index => $entry)
                @php
                    $passed = ! $entry->is_absent && (float) $entry->marks_obtained >= (float) $entry->pass_marks;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $entry->classSubject?->subject?->name ?? 'N/A' }}</td>
                    <td>{{ number_format((float) $entry->full_marks, 2) }}</td>
                    <td>{{ number_format((float) $entry->pass_marks, 2) }}</td>
                    <td>{{ $entry->is_absent ? 'Absent' : number_format((float) $entry->marks_obtained, 2) }}</td>
                    <td class="{{ $passed ? 'pass' : 'fail' }}">{{ $passed ? 'Pass' : 'Fail' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">No marks entries found for this exam.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($resultSummary)
        <table class="summary">
            <tr>
                <td class="label">Total</td>
                <td>{{ number_format((float) $resultSummary->total_marks_obtained, 2) }} / {{ number_format((float) $resultSummary->total_full_marks, 2) }}</td>
                <td class="label">Percentage</td>
                <td>{{ number_format((float) $resultSummary->percentage, 2) }}%</td>
            </tr>
            <tr>
                <td class="label">GPA</td>
                <td>{{ number_format((float) $resultSummary->gpa, 2) }}</td>
                <td class="label">Grade</td>
                <td>{{ $resultSummary->grade ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Position</td>
                <td>{{ $resultSummary->position_in_class ?? 'N/A' }}</td>
                <td class="label">Result</td>
                <td class="{{ $resultSummary->is_pass ? 'pass' : 'fail' }}">{{ $resultSummary->is_pass ? 'Pass' : 'Fail' }}</td>
            </tr>
        </table>
    @endif

    <div class="footer">Official school copy / {{ $school->name }}</div>
</body>
</html>
