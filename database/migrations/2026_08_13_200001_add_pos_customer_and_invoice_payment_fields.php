<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('address')->nullable()->after('mobile');
            $table->text('notes')->nullable()->after('address');
            $table->decimal('balance_due', 15, 2)->default(0)->after('notes');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('previous_due', 15, 2)->default(0)->after('payable');
            $table->decimal('amount_paid', 15, 2)->default(0)->after('previous_due');
            $table->decimal('balance_due', 15, 2)->default(0)->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['previous_due', 'amount_paid', 'balance_due']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['address', 'notes', 'balance_due']);
        });
    }
};
