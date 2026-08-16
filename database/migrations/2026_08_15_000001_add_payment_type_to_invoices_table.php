<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'payment_type')) {
                $table->string('payment_type')->default('paid')->after('status');
            }

            if (!Schema::hasColumn('invoices', 'delivery_charge_paid')) {
                $table->boolean('delivery_charge_paid')->default(false)->after('payment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'delivery_charge_paid')) {
                $table->dropColumn('delivery_charge_paid');
            }

            if (Schema::hasColumn('invoices', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
        });
    }
};
