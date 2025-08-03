<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserFollowsTable extends Migration
{
    public function up()
    {
        Schema::create('user_follows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('stock_id');
            $table->timestamps();

            // Khóa ngoại nếu bạn có bảng users và stocks
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');

            // Đảm bảo một user không follow cùng stock nhiều lần
            $table->unique(['user_id', 'stock_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_follows');
    }
}
