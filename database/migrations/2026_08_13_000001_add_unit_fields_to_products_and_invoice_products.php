<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('unit_type', 10)->default('pcs')->after('category_id');
            $table->unsignedBigInteger('stock_quantity')->default(0)->after('unit_type');
        });

        Schema::table('invoice_products', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->default(0)->after('product_id');
            $table->string('unit', 10)->default('pcs')->after('quantity');
            $table->unsignedBigInteger('base_quantity')->default(0)->after('unit');
            $table->decimal('subtotal', 15, 2)->default(0)->after('sale_price');
        });

        DB::table('products')->update([
            'unit_type' => 'pcs',
            'stock_quantity' => DB::raw('COALESCE(unit, 0)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'unit', 'base_quantity', 'subtotal']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'stock_quantity']);
        });
    }
};
