<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'delivery_charge')) {
                $table->decimal('delivery_charge', 15, 2)->default(0)->after('total');
            }

            if (!Schema::hasColumn('invoices', 'previous_due')) {
                $table->decimal('previous_due', 15, 2)->default(0)->after('payable');
            }

            if (!Schema::hasColumn('invoices', 'amount_paid')) {
                $table->decimal('amount_paid', 15, 2)->default(0)->after('previous_due');
            }

            if (!Schema::hasColumn('invoices', 'balance_due')) {
                $table->decimal('balance_due', 15, 2)->default(0)->after('amount_paid');
            }

            if (!Schema::hasColumn('invoices', 'status')) {
                $table->string('status')->default('due')->after('balance_due');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'delivery_charge')) {
                $table->dropColumn('delivery_charge');
            }

            if (Schema::hasColumn('invoices', 'previous_due')) {
                $table->dropColumn('previous_due');
            }

            if (Schema::hasColumn('invoices', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }

            if (Schema::hasColumn('invoices', 'balance_due')) {
                $table->dropColumn('balance_due');
            }

            if (Schema::hasColumn('invoices', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
