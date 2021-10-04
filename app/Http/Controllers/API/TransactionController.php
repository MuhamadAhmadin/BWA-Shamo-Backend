<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit');
        $status = $request->input('status');

        if ($id) {
            $transaction = Transaction::with(['items.product'])->find($id);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Data transaksi berhasil diambil'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data transaksi tidak ditemukan',
                    404
                );
            }
        }

        $transaction = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);

        if ($status) {
            $transaction->where('status', $status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data list transaksi berhasil diambil'
        );
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'address' => ['required'],
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:products,id'],
            'status' => ['required', 'in:PENDING,SUCCESS,FAILED,CANCELLED,SHIPPING,SHIPPED'],
            'total_price' => ['required'],
            'shipping_price' => ['required'],
        ]);

        $user_id = Auth::user()->id;
        $transaction = Transaction::create([
            'address' => $request->address,
            'status' => $request->status,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'users_id' => $user_id,
        ]);

        foreach($request->items as $item) {
            TransactionItem::create([
                'users_id' => $user_id,
                'transactions_id' => $transaction->id,
                'products_id' => $item['id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return ResponseFormatter::success(
            $transaction->load('items.product'),
            'Transaksi berhasil'
        );
    }
}
