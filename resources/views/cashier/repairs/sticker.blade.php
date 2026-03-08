<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étiquette #{{ $repair->repair_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @page {
            size: 50mm 30mm;
            margin: 1mm;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 8px;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }
        
        .sticker {
            width: 48mm;
            max-width: 48mm;
            padding: 1mm;
            border: 1px solid #000;
        }
        
        .sticker-header {
            text-align: center;
            font-weight: bold;
            font-size: 9px;
            border-bottom: 1px solid #000;
            padding-bottom: 1mm;
            margin-bottom: 1mm;
        }
        
        .sticker-row {
            display: flex;
            margin-bottom: 0.5mm;
        }
        
        .sticker-label {
            font-weight: bold;
            width: 12mm;
            font-size: 7px;
        }
        
        .sticker-value {
            flex: 1;
            font-size: 8px;
        }
        
        .sticker-issue {
            margin-top: 1mm;
            padding-top: 1mm;
            border-top: 1px dashed #000;
        }
        
        .issue-label {
            font-weight: bold;
            font-size: 7px;
        }
        
        .issue-text {
            font-size: 7px;
            overflow: hidden;
            text-overflow: ellipsis;
            max-height: 10mm;
        }
        
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .sticker { border: 1px solid #000; }
            .no-print { display: none; }
        }
        
        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 5px;
        }
        .print-button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Imprimer</button>
    
    <div class="sticker">
        <div class="sticker-header">
            N° {{ $repair->repair_number }}
        </div>
        
        <div class="sticker-row">
            <span class="sticker-label">Client:</span>
            <span class="sticker-value">{{ $repair->customer->full_name ?? 'N/A' }}</span>
        </div>
        
        <div class="sticker-row">
            <span class="sticker-label">Tél:</span>
            <span class="sticker-value">{{ $repair->customer->phone ?? 'N/A' }}</span>
        </div>
        
        <div class="sticker-issue">
            <div class="issue-label">Panne:</div>
            <div class="issue-text">{{ Str::limit($repair->diagnosis ?? $repair->reported_issue, 80) }}</div>
        </div>
    </div>

    <script>
        // Auto-print si paramètre auto
        @if(request('auto'))
        window.onload = function() {
            window.print();
        };
        @endif
    </script>
</body>
</html>
