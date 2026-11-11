<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
class AdvancePaymentController extends Controller
{   
    
public function index(): View
{
    // All active tenants, including those without
    // an assigned room, may hold advance credits.
    $tenants = DB::table('tenants')
        ->where('status', 'Active')
        ->select('tenant_id', 'tenant_name')
        ->orderBy('tenant_name')
        ->get();

    // Calculate applied credits per advance record.
    $allocations = DB::table('advance_payment_allocations')
        ->select('advance_payment_id')
        ->selectRaw('SUM(amount) AS applied_amount')
        ->groupBy('advance_payment_id');

    $advances = DB::table('advance_payments as ap')
        ->join(
            'tenants as t',
            't.tenant_id',
            '=',
            'ap.tenant_id'
        )
        ->leftJoinSub($allocations, 'a', function ($join) {
            $join->on(
                'a.advance_payment_id',
                '=',
                'ap.advance_payment_id'
            );
        })
        ->select(
            'ap.advance_payment_id',
            'ap.tenant_id',
            't.tenant_name',
            'ap.amount',
            'ap.payment_date',
            'ap.payment_method',
            'ap.reference_number',
            'ap.external_reference',
            'ap.status'
        )
        ->selectRaw(
            'COALESCE(a.applied_amount, 0) AS applied_amount'
        )
        ->selectRaw(
            "CASE
                WHEN ap.status = 'Paid'
                THEN GREATEST(
                    ap.amount - COALESCE(a.applied_amount, 0),
                    0
                )
                ELSE 0
             END AS available_amount"
        )
        ->orderByDesc('ap.advance_payment_id')
        ->paginate(15);

    return view(
        'admin.advance-payments.index',
        compact('tenants', 'advances')
    );
}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id' => [
                'required',
                'integer',
                Rule::exists('tenants', 'tenant_id'),
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'max:99999999.99',
            ],
            'payment_method' => [
                'required',
                Rule::in([
                    'Cash',
                    'GCash Transfer',
                    'Bank Transfer',
                ]),
            ],
            'payment_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'external_reference' => [
                'nullable',
                'string',
                'max:120',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'submission_key' => [
                'required',
                'uuid',
            ],
            'confirm_received' => [
                'required',
                'accepted',
            ],
        ]);

        if ($data['payment_date'] >
            now('Asia/Manila')->toDateString()) {
            throw ValidationException::withMessages([
                'payment_date' =>
                    'Payment date cannot be in the future.',
            ]);
        }

        if (
            $data['payment_method'] !== 'Cash' &&
            blank($data['external_reference'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'external_reference' =>
                    'Transaction reference is required for transfers.',
            ]);
        }

        DB::transaction(function () use ($data, $request) {

            $tenant = DB::table('tenants')
                ->where('tenant_id', $data['tenant_id'])
                ->lockForUpdate()
                ->first();

            if (!$tenant || $tenant->status !== 'Active') {
                throw ValidationException::withMessages([
                    'tenant_id' =>
                        'Only active tenants can have advance payments.',
                ]);
            }

            // Idempotency protection for form resubmissions.
            $existing = DB::table('advance_payments')
                ->where('submission_key', $data['submission_key'])
                ->first();

            if ($existing) {
                if ((int) $existing->tenant_id !==
                    (int) $tenant->tenant_id) {
                    abort(409, 'Submission key already used.');
                }

                return;
            }

            // Avoid reusing the same transfer reference.
            if ($data['payment_method'] !== 'Cash') {
                $duplicateReference = DB::table('advance_payments')
                    ->where('payment_method', $data['payment_method'])
                    ->where(
                        'external_reference',
                        $data['external_reference']
                    )
                    ->exists();

                if ($duplicateReference) {
                    throw ValidationException::withMessages([
                        'external_reference' =>
                            'This advance payment reference already exists.',
                    ]);
                }
            }

            $advanceId = DB::table('advance_payments')
                ->insertGetId([
                    'tenant_id' => $tenant->tenant_id,
                    'amount' => $data['amount'],
                    'payment_date' => $data['payment_date'],
                    'status' => 'Paid',
                    'payment_method' => $data['payment_method'],
                    'reference_number' => 'ADV-' . Str::ulid(),
                    'external_reference' =>
                        $data['external_reference'] ?? null,
                    'recorded_by_user_id' =>
                        $request->user()->getAuthIdentifier(),
                    'notes' => $data['notes'] ?? null,
                    'submission_key' => $data['submission_key'],
                ]);

            DB::table('transaction_log')->insert([
                'user_id' =>
                    $request->user()->getAuthIdentifier(),
                'transaction_type' =>
                    'ADVANCE_PAYMENT_RECEIVED',
                'amount' => $data['amount'],
                'description' =>
                    "Advance payment #{$advanceId} received "
                    . "for Tenant #{$tenant->tenant_id}.",
                'transaction_date' => now('Asia/Manila'),
            ]);

            DB::table('audit_log')->insert([
                'user_id' =>
                    $request->user()->getAuthIdentifier(),
                'action' => 'ADVANCE_PAYMENT_CONFIRMED',
                'description' =>
                    "Admin confirmed advance payment "
                    . "#{$advanceId} for Tenant "
                    . "#{$tenant->tenant_id}.",
                'created_at' => now('Asia/Manila'),
            ]);

        }, 3);

        return redirect()
            ->route('admin.advance-payments.index')
            ->with(
                'status',
                'Advance payment successfully recorded as tenant credit.'
            );
    }
}
