<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2523;
        }

        h1 {
            font-size: 17px;
            margin: 0 0 4px;
            color: #145c54;
        }

        p {
            margin: 0 0 14px;
            color: #5b6b79;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #145c54;
            color: #fff;
            text-align: left;
        }

        th,
        td {
            border: 1px solid #d8d8d8;
            padding: 6px;
        }

        tr:nth-child(even) {
            background: #f7f6f3;
        }

        .number {
            text-align: right;
        }
    </style>
</head>

<body>
    <h1>{{ $title }}</h1>
    <p>Gudang Store · Dicetak {{ now()->format('d M Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
