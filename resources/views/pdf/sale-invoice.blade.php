<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura #{{ $sale['id'] }}</title>
    <style>
        @page { margin: 120px 40px 80px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { position: fixed; top: -100px; left: 0; right: 0; height: 80px; }
        .footer { position: fixed; bottom: -60px; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 8px; }
        .company-name { font-size: 22px; font-weight: bold; color: #1976d2; }
        .company-info { font-size: 10px; color: #666; margin-top: 2px; }
        .invoice-title { font-size: 18px; font-weight: bold; color: #1976d2; text-align: right; }
        .invoice-number { font-size: 14px; color: #555; text-align: right; }
        .info-section { margin-top: 20px; }
        .info-section table { width: 100%; font-size: 11px; }
        .info-section td { vertical-align: top; padding: 4px 8px; }
        .info-label { color: #666; font-weight: bold; width: 30%; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 25px; }
        .items-table thead { background: #1976d2; color: white; }
        .items-table th { padding: 10px 8px; font-size: 11px; text-transform: uppercase; }
        .items-table td { padding: 8px; border-bottom: 1px solid #e0e0e0; }
        .items-table tr:nth-child(even) td { background: #fafafa; }
        .items-table .text-right { text-align: right; }
        .items-table .text-center { text-align: center; }
        .totals { margin-top: 20px; float: right; width: 300px; }
        .totals table { width: 100%; }
        .totals td { padding: 6px 10px; font-size: 12px; }
        .totals .total-row { font-size: 16px; font-weight: bold; color: #1976d2; border-top: 2px solid #1976d2; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 60%;">
                    <div class="company-name">{{ $company['name'] }}</div>
                    <div class="company-info">
                        RUC: {{ $company['ruc'] }}<br>
                        {{ $company['address'] }}<br>
                        Tel: {{ $company['phone'] }} | {{ $company['email'] }}
                    </div>
                </td>
                <td style="width: 40%; text-align: right;">
                    <div class="invoice-title">FACTURA DE VENTA</div>
                    <div class="invoice-number">N° {{ str_pad($sale['id'], 6, '0', STR_PAD_LEFT) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td style="width: 50%;">
                    <table>
                        <tr>
                            <td class="info-label">Cliente:</td>
                            <td>{{ $client['name'] ?? $sale['customer_name'] ?? 'Consumidor Final' }}</td>
                        </tr>
                        @if(!empty($client['document_number']))
                        <tr>
                            <td class="info-label">{{ $sale['document_type']['name'] ?? 'DNI' }}:</td>
                            <td>{{ $client['document_number'] }}</td>
                        </tr>
                        @endif
                        @if(!empty($client['email']))
                        <tr>
                            <td class="info-label">Email:</td>
                            <td>{{ $client['email'] }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
                <td style="width: 50%;">
                    <table>
                        <tr>
                            <td class="info-label">Fecha:</td>
                            <td>{{ \Carbon\Carbon::parse($sale['sale_date'])->format('d/m/Y') }}</td>
                        </tr>
                        @if(isset($sale['document_type']))
                        <tr>
                            <td class="info-label">Comprobante:</td>
                            <td>{{ $sale['document_type']['name'] ?? 'Boleta' }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="info-label">Vendedor:</td>
                            <td>{{ $sale['user']['name'] ?? 'Sistema' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%;">Código</th>
                <th style="width: 44%;">Producto</th>
                <th class="text-center" style="width: 12%;">Cantidad</th>
                <th class="text-right" style="width: 16%;">P. Unitario</th>
                <th class="text-right" style="width: 20%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td class="text-center">{{ $item['code'] }}</td>
                <td>{{ $item['product'] }}</td>
                <td class="text-center">{{ $item['quantity'] }}</td>
                <td class="text-right">S/ {{ number_format($item['price'], 2) }}</td>
                <td class="text-right">S/ {{ number_format($item['subtotal'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="text-right">S/ {{ number_format($total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        PharmaCare &copy; {{ date('Y') }} | Factura generada electrónicamente | Página {PAGE_NUM} de {PAGE_COUNT}
    </div>

</body>
</html>
