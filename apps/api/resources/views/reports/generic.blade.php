<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $type }}</title>
    <style>
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin-top: 24px; }
        .muted { color: #6b7280; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 20px; padding-bottom: 12px; }
        .grid { display: table; width: 100%; }
        .row { display: table-row; }
        .cell { border-bottom: 1px solid #e5e7eb; display: table-cell; padding: 7px 8px; vertical-align: top; }
        .label { color: #374151; font-weight: bold; width: 35%; }
        .stamp { border: 2px solid #111827; display: inline-block; font-weight: bold; margin-top: 24px; padding: 8px 12px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $school->name }}</h1>
        <div class="muted">{{ $type }} / {{ $jobId }}</div>
        <div class="muted">Generated {{ $generatedAt }}@if ($requestedBy) by {{ $requestedBy }}@endif</div>
    </div>

    <h2>Report Details</h2>
    <div class="grid">
        @foreach ($parameters as $key => $value)
            <div class="row">
                <div class="cell label">{{ str($key)->replace('_', ' ')->headline() }}</div>
                <div class="cell">{{ is_scalar($value) ? $value : json_encode($value) }}</div>
            </div>
        @endforeach
    </div>

    @if ($target)
        <h2>Target</h2>
        <div class="grid">
            <div class="row">
                <div class="cell label">Type</div>
                <div class="cell">{{ class_basename($target) }}</div>
            </div>
            <div class="row">
                <div class="cell label">ID</div>
                <div class="cell">{{ $target->getKey() }}</div>
            </div>
        </div>
    @endif

    <div class="stamp">Official school copy</div>
</body>
</html>
