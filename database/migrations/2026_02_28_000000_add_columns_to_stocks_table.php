<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('recommended_sell_price', 15, 2)->nullable()->after('current_price');
            $table->decimal('rating_stocks', 5, 2)->nullable()->after('risk_level');
            $table->bigInteger('volume_avg')->nullable()->after('rating_stocks');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['recommended_sell_price', 'rating_stocks', 'volume_avg']);
        });
    }
};
