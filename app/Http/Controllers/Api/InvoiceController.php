<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Purchase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    private const COMPANY = [
        'name'        => 'PharmaCare',
        'ruc'         => '20512345678',
        'address'     => 'Av. Principal 123, Lima, Perú',
        'phone'       => '+51 1 234 5678',
        'email'       => 'info@pharmacare.pe',
        'logo'        => '', // base64 or path to logo
    ];

    /**
     * Generar factura de venta en PDF
     */
    public function saleInvoice(Sale $sale): Response|JsonResponse
    {
        try {
            $sale->load([
                'client',
                'documentType',
                'user:id,name',
                'saleDetails.product:id,name,code',
            ]);

            $items = $sale->saleDetails->map(fn ($detail) => [
                'code'     => $detail->product?->code ?? 'N/A',
                'product'  => $detail->product?->name ?? 'Producto eliminado',
                'quantity' => (int) $detail->quantity,
                'price'    => (float) $detail->price,
                'subtotal' => (float) $detail->subtotal,
            ])->toArray();

            $data = [
                'company' => self::COMPANY,
                'sale'    => $sale->toArray(),
                'client'  => $sale->client?->toArray() ?? [],
                'items'   => $items,
                'total'   => (float) $sale->total,
            ];

            $pdf = Pdf::loadView('pdf.sale-invoice', $data);
            $pdf->setPaper('a4');

            return $pdf->download("factura-{$sale->id}.pdf");
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al generar factura PDF: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Generar recibo de compra en PDF
     */
    public function purchaseReceipt(Purchase $purchase): Response|JsonResponse
    {
        try {
            $purchase->load([
                'supplier',
                'purchaseDocumentType',
                'user:id,name',
                'purchaseDetails.product:id,name,code',
            ]);

            $items = $purchase->purchaseDetails->map(fn ($detail) => [
                'code'     => $detail->product?->code ?? 'N/A',
                'product'  => $detail->product?->name ?? 'Producto eliminado',
                'quantity' => (int) $detail->quantity,
                'price'    => (float) $detail->price,
                'subtotal' => (float) $detail->subtotal,
            ])->toArray();

            $data = [
                'company'  => self::COMPANY,
                'purchase' => $purchase->toArray(),
                'supplier' => $purchase->supplier?->toArray() ?? [],
                'items'    => $items,
                'total'    => (float) $purchase->total,
            ];

            $pdf = Pdf::loadView('pdf.purchase-receipt', $data);
            $pdf->setPaper('a4');

            return $pdf->download("recibo-compra-{$purchase->id}.pdf");
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al generar recibo PDF: ' . $e->getMessage(),
                code: 500
            );
        }
    }
}
