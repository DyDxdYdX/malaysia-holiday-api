<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JADUAL HARI KELEPASAN AM PERSEKUTUAN DAN NEGERI {{ $batch->year }}</title>
    <style>
        :root {
            --line: #111;
            --federal: #c1121f;
            --state: #1d4ed8;
            --off: #d1d5db;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #e5e7eb;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
        }

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            background: #fff;
            border-bottom: 1px solid #cbd5e1;
        }

        .toolbar p {
            margin: 0;
            font-size: 13px;
            color: #475569;
        }

        .toolbar button {
            cursor: pointer;
            border: 0;
            border-radius: 8px;
            background: #dc2626;
            color: #fff;
            font-weight: 700;
            padding: 8px 14px;
        }

        .sheet {
            width: 420mm;
            max-width: 100%;
            margin: 16px auto;
            background: #fff;
            padding: 14px 16px 20px;
        }

        h1 {
            margin: 0 0 12px;
            text-align: center;
            font-size: 18px;
            letter-spacing: 0.04em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10px;
        }

        th, td {
            border: 1px solid var(--line);
            vertical-align: middle;
        }

        thead th {
            background: #fff;
            font-size: 8px;
            font-weight: 800;
            text-align: center;
            padding: 4px 3px;
        }

        .col-bil { width: 28px; }
        .col-name { width: 190px; text-align: left; padding: 0 6px; }
        .col-marker { width: 28px; }
        .col-date { width: 78px; }
        .col-day { width: 62px; }
        .col-state { width: 22px; padding: 0; }

        .state-head {
            height: 118px;
            vertical-align: bottom;
        }

        .state-label {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            display: inline-block;
            padding: 6px 0;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        td {
            text-align: center;
            height: 26px;
            padding: 2px 3px;
        }

        td.name {
            text-align: left;
            font-weight: 600;
            padding-left: 6px;
        }

        .marker-p { color: var(--federal); font-weight: 800; }
        .marker-n { color: var(--state); font-weight: 800; }
        .tick-p { color: var(--federal); font-weight: 800; font-size: 12px; }
        .tick-n { color: var(--state); font-weight: 800; font-size: 12px; }
        .on { background: #fff; }
        .off { background: var(--off); }

        @page {
            size: A3 landscape;
            margin: 8mm;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet {
                width: auto;
                margin: 0;
                padding: 0;
            }
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <p>{{ __('Print this page and choose Save as PDF to compare it with the JPM schedule.') }}</p>
        <button type="button" onclick="window.print()">{{ __('Print / Save as PDF') }}</button>
    </div>

    <div class="sheet">
        <h1>JADUAL HARI KELEPASAN AM PERSEKUTUAN DAN NEGERI {{ $batch->year }}</h1>

        <table>
            <thead>
                <tr>
                    <th class="col-bil">BIL</th>
                    <th class="col-name">HARI KELEPASAN AM</th>
                    <th class="col-marker"></th>
                    <th class="col-date">TARIKH</th>
                    <th class="col-day">HARI</th>
                    @foreach ($scheduleHeaders as $code => $label)
                        <th class="col-state state-head">
                            <span class="state-label">{{ $label }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($holidays as $index => $holiday)
                    @php
                        $stateCodes = $holiday->stateCodes();
                        $marker = \App\Support\MalayCalendar::marker($holiday->scope);
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="name">
                            {{ $holiday->name }}@if ($holiday->is_subject_to_change)*@endif
                        </td>
                        <td class="marker-{{ strtolower($marker) }}">({{ $marker }})</td>
                        <td>{{ \App\Support\MalayCalendar::dateLabel($holiday->date) }}</td>
                        <td>{{ \App\Support\MalayCalendar::dayName($holiday->date) }}</td>
                        @foreach ($scheduleHeaders as $code => $label)
                            @php $isOn = in_array($code, $stateCodes, true); @endphp
                            <td class="{{ $isOn ? 'on' : 'off' }}">
                                @if ($isOn)
                                    <span class="tick-{{ strtolower($marker) }}">✓</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
