<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étiquette #{{ $repair->repair_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: 59mm 38mm;
            margin: 1.5mm;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 8px;
            line-height: 1.3;
            color: #000;
            background: #fff;
            width: 56mm;
        }

        .sticker {
            width: 56mm;
            max-width: 56mm;
            height: 34mm;
            max-height: 34mm;
            overflow: hidden;
            padding: 1mm;
            display: flex;
            flex-direction: row;
            gap: 1.5mm;
        }

        .sticker-left {
            flex: 1;
            overflow: hidden;
        }

        .sticker-right {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sticker-right img {
            width: 22mm;
            height: 22mm;
        }

        .sticker-header {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 1mm;
            margin-bottom: 1mm;
            letter-spacing: 0.3px;
        }

        .sticker-row {
            display: flex;
            margin-bottom: 0.8mm;
        }

        .sticker-label {
            font-weight: bold;
            width: 11mm;
            font-size: 7px;
            flex-shrink: 0;
        }

        .sticker-value {
            flex: 1;
            font-size: 8px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
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
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        @media print {
            @page { size: 59mm 38mm; margin: 1.5mm; }
            body { width: 56mm; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
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
        <div class="sticker-left">
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

        @if($qrCode)
        <div class="sticker-right">
            <img src="{{ $qrCode }}" alt="QR">
        </div>
        @endif
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
