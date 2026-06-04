<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index()
    {
        return response()->json(
            Transaction::with([
                'user',
                'details.product'
            ])->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            $transaction = Transaction::create([
                'user_id' => $request->user_id,
                'total_price' => 0,
                'status' => 'completed'
            ]);

            $totalPrice = 0;

            foreach ($request->products as $item) {

                $product = Product::find($item['product_id']);

                if ($product->stock < $item['quantity']) {

                    DB::rollBack();

                    return response()->json([
                        'message' => 'Stok produk tidak mencukupi',
                        'product' => $product->name
                    ], 400);
                }

                $subtotal = $product->price * $item['quantity'];

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal
                ]);

                $product->decrement(
                    'stock',
                    $item['quantity']
                );

                $totalPrice += $subtotal;
            }

            $transaction->update([
                'total_price' => $totalPrice
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transaksi berhasil dibuat',
                'transaction' => $transaction->load([
                    'user',
                    'details.product'
                ])
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        $transaction = Transaction::with([
            'user',
            'details.product'
        ])->find($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }

        return response()->json($transaction);
    }

    public function update(Request $request, string $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }

        $request->validate([
            'status' => 'required|in:pending,completed,cancelled'
        ]);

        $transaction->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Status transaksi berhasil diupdate',
            'data' => $transaction
        ]);
    }

    public function destroy(string $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }

        $transaction->delete();

        return response()->json([
            'message' => 'Transaksi berhasil dihapus'
        ]);
    }
}
