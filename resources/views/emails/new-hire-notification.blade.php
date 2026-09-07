@php
    $employee = $props['employee'] ?? [];
    $rates = is_array($props['rates'] ?? null) ? $props['rates'] : [];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>New hire notification</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; padding: 20px; background: #f1f1f1; color: #333; }
        .container { background: #fff; max-width: 640px; margin: 0 auto; padding: 24px; border-radius: 8px; }
        h1 { font-size: 20px; margin: 0 0 16px; color: #b1151d; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.meta th { text-align: left; width: 32%; padding: 8px 8px 8px 0; color: #b1151d; vertical-align: top; }
        table.meta td { padding: 8px 0; }
        table.rates { width: 100%; border-collapse: collapse; font-size: 14px; }
        table.rates th, table.rates td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.rates th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="container">
        <h1>New hire — pending rate requests</h1>
        <p>A new employee was added. Summary:</p>
        <table class="meta">
            <tr><th>Name</th><td>{{ $employee['name'] ?? '—' }}</td></tr>
            <tr><th>Email</th><td>{{ $employee['email'] ?? '—' }}</td></tr>
            <tr><th>Employee ID</th><td>{{ $employee['employee_id'] ?? '—' }}</td></tr>
            <tr><th>SSN</th><td>{{ $employee['ssn'] ?? '—' }}</td></tr>
            <tr><th>Position</th><td>{{ $employee['position'] ?? '—' }}</td></tr>
        </table>
        <p><strong>Rate requests</strong></p>
        <table class="rates">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Rate</th>
                    <th>Effective date</th>
                    <th>Rate type</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rates as $row)
                    <tr>
                        <td>{{ $row['company'] ?? '—' }}</td>
                        <td>{{ isset($row['rate']) ? number_format((float) $row['rate'], 2) : '—' }}</td>
                        <td>{{ $row['effective_date'] ?? '—' }}</td>
                        <td>{{ $row['rate_type'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No rate lines.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
