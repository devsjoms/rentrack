<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table): void {
            $table->integer('admin_id', true);
            $table->string('admin_name', 100);
            $table->string('phone_number', 50)->nullable();
            $table->dateTime('last_login')->nullable();
            $table->string('status', 20)->default('Active');
            $table->text('image')->nullable();
        });

        Schema::create('rooms', function (Blueprint $table): void {
            $table->integer('room_id', true);
            $table->string('room_number', 20);
            $table->string('building_number', 20);
            $table->string('building_address', 150);
            $table->string('gender_allowed', 10);
            $table->integer('tenants_count')->default(0);
            $table->integer('maximum_tenants')->default(1);
            $table->decimal('rent_price', 10, 2)->default(0);
            $table->string('availability_status', 20)->default('Available');
        });

        Schema::create('tenants', function (Blueprint $table): void {
            $table->integer('tenant_id', true);
            $table->string('tenant_name', 50)->nullable();
            $table->integer('tenant_age')->nullable();
            $table->string('tenant_address', 70)->nullable();
            $table->string('tenant_number', 50)->nullable();
            $table->integer('room_id')->nullable();
            $table->string('tenant_gender', 10)->nullable();
            $table->date('move_in_date')->nullable();
            $table->string('status', 10)->default('Active');
            $table->text('image')->nullable();
            $table->foreign('room_id', 'fk_tenants_room')->references('room_id')->on('rooms')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('accounts', function (Blueprint $table): void {
            $table->integer('user_id', true);
            $table->integer('admin_id')->nullable();
            $table->integer('tenant_id')->nullable();
            $table->string('username', 30);
            $table->string('email');
            $table->string('password_hash', 200);
            $table->enum('role', ['tenant', 'admin']);
            $table->boolean('profile_completed')->default(false);
            $table->unique('username', 'uq_accounts_username');
            $table->unique('email', 'email');
            $table->unique('admin_id', 'uq_accounts_admin');
            $table->unique('tenant_id', 'uq_accounts_tenant');
            $table->foreign('admin_id', 'fk_accounts_admin')->references('admin_id')->on('admins')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('tenant_id', 'fk_accounts_tenant')->references('tenant_id')->on('tenants')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('registration_requests', function (Blueprint $table): void {
            $table->integer('registration_id', true);
            $table->string('email');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Used', 'Expired'])->default('Pending');
            $table->string('token')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unique('email', 'uq_registration_email');
            $table->unique('token', 'uq_registration_token');
        });

        Schema::create('otp_verifications', function (Blueprint $table): void {
            $table->integer('otp_id', true);
            $table->integer('user_id');
            $table->string('otp_hash');
            $table->dateTime('expires_at');
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->index('user_id', 'idx_otp_user_id');
            $table->foreign('user_id', 'fk_otp_user')->references('user_id')->on('accounts')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('room_requests', function (Blueprint $table): void {
            $table->integer('request_id', true);
            $table->integer('tenant_id');
            $table->integer('room_id');
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->dateTime('request_date')->useCurrent();
            $table->index('tenant_id', 'idx_room_requests_tenant');
            $table->index('room_id', 'idx_room_requests_room');
            $table->foreign('tenant_id', 'fk_room_requests_tenant')->references('tenant_id')->on('tenants')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('room_id', 'fk_room_requests_room')->references('room_id')->on('rooms')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('billings', function (Blueprint $table): void {
            $table->integer('billing_id', true);
            $table->integer('tenant_id');
            $table->integer('room_id')->nullable();
            $table->date('billing_period')->nullable();
            $table->date('billing_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status', 20)->default('Unpaid');
            $table->date('payment_date')->nullable();
            $table->unique(['tenant_id', 'billing_period'], 'uq_billings_tenant_period');
            $table->index('tenant_id', 'idx_billings_tenant');
            $table->index('room_id', 'idx_billings_room');
            $table->foreign('tenant_id', 'fk_billings_tenant')->references('tenant_id')->on('tenants')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('room_id', 'fk_billings_room')->references('room_id')->on('rooms')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('billing_items', function (Blueprint $table): void {
            $table->bigIncrements('billing_item_id');
            $table->integer('billing_id');
            $table->string('item_type', 20);
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['billing_id', 'item_type'], 'uq_bill_item_type');
            $table->foreign('billing_id', 'fk_billing_item_bill')->references('billing_id')->on('billings')->cascadeOnUpdate();
        });

        Schema::create('advance_payments', function (Blueprint $table): void {
            $table->integer('advance_payment_id', true);
            $table->integer('tenant_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('payment_method', 30)->nullable();
            $table->string('reference_number', 64)->nullable();
            $table->string('external_reference', 120)->nullable();
            $table->integer('recorded_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->char('submission_key', 36)->nullable();
            $table->date('payment_date');
            $table->string('status', 20)->default('Paid');
            $table->unique('reference_number', 'uq_advance_reference');
            $table->unique('submission_key', 'uq_advance_submission');
            $table->index('tenant_id', 'idx_advance_payments_tenant');
            $table->index('recorded_by_user_id', 'fk_advance_recorded_by');
            $table->foreign('tenant_id', 'fk_advance_payments_tenant')->references('tenant_id')->on('tenants')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('recorded_by_user_id', 'fk_advance_recorded_by')->references('user_id')->on('accounts')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('advance_payment_allocations', function (Blueprint $table): void {
            $table->bigIncrements('allocation_id');
            $table->integer('advance_payment_id');
            $table->integer('billing_id');
            $table->decimal('amount', 10, 2);
            $table->dateTime('applied_at')->useCurrent();
            $table->unique(['advance_payment_id', 'billing_id'], 'uq_advance_bill');
            $table->index('billing_id', 'idx_allocation_bill');
            $table->foreign('advance_payment_id', 'fk_allocation_advance')->references('advance_payment_id')->on('advance_payments')->cascadeOnUpdate();
            $table->foreign('billing_id', 'fk_allocation_bill')->references('billing_id')->on('billings')->cascadeOnUpdate();
        });

        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->bigIncrements('payment_id');
            $table->integer('billing_id');
            $table->string('provider', 30)->default('paymongo');
            $table->string('payment_method', 30)->nullable();
            $table->integer('recorded_by_user_id')->nullable();
            $table->string('external_reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_number', 64);
            $table->string('checkout_session_id', 128)->nullable();
            $table->string('provider_payment_id', 128)->nullable();
            $table->text('checkout_url')->nullable();
            $table->unsignedBigInteger('amount_centavos');
            $table->char('currency', 3)->default('PHP');
            $table->string('status', 20)->default('Pending');
            $table->boolean('is_live')->default(false);
            $table->dateTime('paid_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique('reference_number', 'uq_payment_reference');
            $table->unique('checkout_session_id', 'uq_checkout_session');
            $table->unique('provider_payment_id', 'uq_provider_payment');
            $table->index(['billing_id', 'status'], 'idx_payment_billing_status');
            $table->index('recorded_by_user_id', 'idx_payment_recorded_by');
            $table->foreign('billing_id', 'fk_payment_billing')->references('billing_id')->on('billings')->cascadeOnUpdate();
            $table->foreign('recorded_by_user_id', 'fk_payment_recorded_by')->references('user_id')->on('accounts')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->bigIncrements('webhook_event_id');
            $table->string('provider_event_id', 128);
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('event_type', 120);
            $table->string('checkout_session_id', 128)->nullable();
            $table->char('payload_sha256', 64);
            $table->boolean('is_live')->default(false);
            $table->string('processing_status', 20)->default('Received');
            $table->dateTime('received_at')->useCurrent();
            $table->dateTime('processed_at')->nullable();
            $table->unique('provider_event_id', 'uq_provider_event');
            $table->index('checkout_session_id', 'idx_webhook_session');
            $table->index('payment_id', 'idx_webhook_payment');
            $table->foreign('payment_id', 'fk_webhook_payment')->references('payment_id')->on('payment_transactions')->cascadeOnUpdate();
        });

        Schema::create('audit_log', function (Blueprint $table): void {
            $table->integer('audit_id', true);
            $table->integer('user_id')->nullable();
            $table->string('action', 100);
            $table->text('description')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->index('user_id', 'idx_audit_user');
            $table->foreign('user_id', 'fk_audit_user')->references('user_id')->on('accounts')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('transaction_log', function (Blueprint $table): void {
            $table->integer('transaction_id', true);
            $table->integer('user_id')->nullable();
            $table->string('transaction_type', 100);
            $table->decimal('amount', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->dateTime('transaction_date')->useCurrent();
            $table->index('user_id', 'idx_transaction_user');
            $table->foreign('user_id', 'fk_transaction_user')->references('user_id')->on('accounts')->nullOnDelete()->cascadeOnUpdate();
        });

        DB::statement("ALTER TABLE billing_items ADD CONSTRAINT chk_billing_item_amount CHECK (amount > 0)");
        DB::statement("ALTER TABLE billing_items ADD CONSTRAINT chk_billing_item_type CHECK (item_type IN ('Rent', 'Electricity', 'Water'))");
        DB::statement("ALTER TABLE advance_payment_allocations ADD CONSTRAINT chk_allocation_amount CHECK (amount > 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('advance_payment_allocations');
        Schema::dropIfExists('advance_payments');
        Schema::dropIfExists('billing_items');
        Schema::dropIfExists('billings');
        Schema::dropIfExists('room_requests');
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('registration_requests');
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('transaction_log');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('admins');
    }
};
