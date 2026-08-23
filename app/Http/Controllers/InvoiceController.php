<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Services\UnitConversionService;

class InvoiceController extends Controller
{
    public function createInvoice(Request $request)
    {
        $converter = app(UnitConversionService::class);

        try {
            $userId = $request->header('id');
            $products = $request->input('products', []);

            $request->validate([
                'cus_id' => 'required|exists:customers,id',
                'total' => 'required|numeric|min:0',
                'delivery_charge' => 'nullable|numeric|min:0',
                'discount' => 'nullable|numeric|min:0',
                'vat' => 'nullable|numeric|min:0',
                'payable' => 'required|numeric|min:0',
                'amount_paid' => 'nullable|numeric|min:0',
                'payment_type' => 'nullable|in:paid,due,cod',
                'delivery_charge_paid' => 'nullable|boolean',
            ]);

            if (!is_array($products) || count($products) === 0) {
                throw ValidationException::withMessages([
                    'products' => 'Add at least one product to create an invoice.',
                ]);
            }

            $customerId = $request->input('cus_id');
            $deliveryCharge = (float) ($request->input('delivery_charge') ?? 0);
            $payable = (float) $request->input('payable');
            $amountPaid = min($payable, (float) ($request->input('amount_paid') ?? 0));
            $paymentType = strtolower((string) $request->input('payment_type', 'paid'));
            $deliveryChargePaid = $request->boolean('delivery_charge_paid');

            if ($paymentType === 'paid') {
                $amountPaid = $payable;
            } elseif ($paymentType === 'cod' && $deliveryChargePaid) {
                $amountPaid = max($amountPaid, min($deliveryCharge, $payable));
            }
            $status = 'due';

            return DB::transaction(function () use ($request, $userId, $products, $converter, $customerId, $deliveryCharge, $payable, $amountPaid, $paymentType, $deliveryChargePaid, $status) {
                $customer = Customer::where('user_id', $userId)
                    ->where('id', $customerId)
                    ->lockForUpdate()
                    ->first();

                if (!$customer) {
                    throw ValidationException::withMessages([
                        'cus_id' => 'Selected customer could not be found.',
                    ]);
                }

                $invoiceBalanceDue = max(0, round($payable - $amountPaid, 2));
                $status = $invoiceBalanceDue > 0 ? 'due' : 'paid';

                $invoice = Invoice::create([
                    'user_id' => $userId,
                    'customer_id' => $customer->id,
                    'total' => $request->input('total'),
                    'delivery_charge' => $deliveryCharge,
                    'vat' => $request->input('vat') ?? 0,
                    'payable' => $payable,
                    'discount' => $request->input('discount') ?? 0,
                    'previous_due' => 0,
                    'amount_paid' => $amountPaid,
                    'balance_due' => $invoiceBalanceDue,
                    'status' => $status,
                    'payment_type' => $paymentType,
                    'delivery_charge_paid' => $deliveryChargePaid,
                ]);

                foreach ($products as $productRow) {
                    $isCustomItem = filter_var($productRow['is_custom_item'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $unitType = $converter->normalizeUnitType($productRow['unit'] ?? 'pcs');
                    $enteredQuantity = $productRow['quantity'] ?? $productRow['unit'] ?? 0;

                    if ($isCustomItem) {
                        $itemName = trim((string) ($productRow['name'] ?? ''));
                        $unitPrice = (float) ($productRow['unit_price'] ?? $productRow['price'] ?? 0);
                        $costPrice = array_key_exists('cost_price', $productRow) && $productRow['cost_price'] !== null
                            ? (float) $productRow['cost_price']
                            : null;

                        if ($itemName === '' || !$converter->isValidQuantity($enteredQuantity, $unitType) || $unitPrice < 0 || ($costPrice !== null && $costPrice < 0)) {
                            throw ValidationException::withMessages([
                                'products' => 'Invalid custom item details detected.',
                            ]);
                        }

                        $subtotal = round($unitPrice * (float) $enteredQuantity, 2);

                        InvoiceProduct::create([
                            'invoice_id' => $invoice->id,
                            'product_id' => null,
                            'user_id' => $userId,
                            'qty' => $enteredQuantity,
                            'sale_price' => $unitPrice,
                            'quantity' => $enteredQuantity,
                            'unit' => $unitType,
                            'base_quantity' => 0,
                            'subtotal' => $subtotal,
                            'is_custom_item' => true,
                            'item_name' => $itemName,
                            'cost_price' => $costPrice,
                            'note' => trim((string) ($productRow['note'] ?? '')) ?: null,
                        ]);

                        continue;
                    }

                    $productId = $productRow['id'] ?? null;
                    if (!$productId) {
                        throw ValidationException::withMessages([
                            'products' => 'One of the selected products is missing an id.',
                        ]);
                    }

                    $product = Product::where('user_id', '=', $userId)
                        ->where('id', '=', $productId)
                        ->lockForUpdate()
                        ->first();

                    if (!$product) {
                        throw ValidationException::withMessages([
                            'products' => 'Selected product could not be found.',
                        ]);
                    }

                    $unitType = $converter->normalizeUnitType($productRow['unit'] ?? $product->unit_type);

                    if (!$converter->isValidQuantity($enteredQuantity, $unitType)) {
                        throw ValidationException::withMessages([
                            'products' => 'Invalid quantity detected in the invoice.',
                        ]);
                    }

                    $baseQuantity = $converter->convertToBaseQuantity($enteredQuantity, $unitType);
                    $currentStock = (int) ($product->stock_quantity ?? $product->unit ?? 0);

                    if ($baseQuantity > $currentStock) {
                        throw ValidationException::withMessages([
                            'products' => 'Not enough stock available for ' . $product->name . '.',
                        ]);
                    }

                    $unitPrice = (float) ($productRow['unit_price'] ?? $productRow['price'] ?? $product->price);
                    $subtotal = $converter->calculateSubtotal($unitPrice, $enteredQuantity, $unitType);
                    $remainingStock = $currentStock - $baseQuantity;

                    InvoiceProduct::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                        'user_id' => $userId,
                        'qty' => $enteredQuantity,
                        'sale_price' => $unitPrice,
                        'quantity' => $enteredQuantity,
                        'unit' => $unitType,
                        'base_quantity' => $baseQuantity,
                        'subtotal' => $subtotal,
                    ]);

                    $product->update([
                        'stock_quantity' => $remainingStock,
                        'unit' => $remainingStock,
                    ]);
                }

                $customer->update([
                    'balance_due' => max(0, round((float) ($customer->balance_due ?? 0) + $invoiceBalanceDue, 2)),
                ]);

                $invoice->load('customer', 'invoiceProducts.product');

                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Invoice created successfully',
                        'invoice' => $invoice,
                    ]);
                }

                return redirect()->route('salePage')->with([
                    'status' => true,
                    'message' => 'Invoice created successfully',
                    'invoice_id' => $invoice->id,
                ]);
            });
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()->route('salePage')->with(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function listInvoice(Request $request)
    {
        $userId = $request->header('id');
        $list = Invoice::where('user_id', '=', $userId)
            ->with('customer', 'invoiceProducts.product')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Invoice/InvoiceListPage', [
            'list' => $list,
            'business' => config('pos.business'),
        ]);
    }

    public function updateInvoiceStatus(Request $request)
    {
        try {
            $userId = $request->header('id');
            $invoiceId = $request->input('id');
            $status = strtolower((string) $request->input('status', 'paid'));

            if ($status !== 'paid') {
                return redirect()->route('listInvoice')->with(['status' => false, 'message' => 'Invalid invoice status.']);
            }

            $invoice = Invoice::where('user_id', '=', $userId)
                ->where('id', '=', $invoiceId)
                ->first();

            if (!$invoice) {
                return redirect()->route('listInvoice')->with(['status' => false, 'message' => 'Invoice not found.']);
            }

            $invoice->amount_paid = $invoice->payable;
            $invoice->balance_due = 0;
            $invoice->payment_type = 'paid';

            $invoice->status = $status;
            $invoice->save();

            $customer = Customer::where('user_id', '=', $userId)
                ->where('id', '=', $invoice->customer_id)
                ->first();

            if ($customer) {
                $customer->balance_due = Invoice::where('user_id', '=', $userId)
                    ->where('customer_id', '=', $customer->id)
                    ->sum('balance_due');
                $customer->save();
            }

            return redirect()->route('listInvoice')->with(['status' => true, 'message' => 'Invoice status updated successfully.']);
        } catch (Exception $e) {
            return redirect()->route('listInvoice')->with(['status' => false, 'message' => 'Something went wrong']);
        }
    }

    public function deleteInvoice(Request $request)
    {
        DB::beginTransaction();
        try {
            $userId = $request->header('id');
            $invoiceId = $request->input('id');
            InvoiceProduct::where('invoice_id', '=', $invoiceId)->where('user_id', '=', $userId)->delete();
            Invoice::where('user_id', '=', $userId)->where('id', '=', $invoiceId)->delete();
            DB::commit();

            return redirect()->route('listInvoice')->with(['status' => true, 'message' => 'Invoice deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->route('listInvoice')->with(['status' => false, 'message' => 'Something went wrong']);
        }
    }
}
