<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->boolean('is_custom_item')->default(false)->after('product_id');
            $table->string('item_name')->nullable()->after('is_custom_item');
            $table->decimal('cost_price', 15, 2)->nullable()->after('sale_price');
            $table->text('note')->nullable()->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->dropColumn(['is_custom_item', 'item_name', 'cost_price', 'note']);
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
