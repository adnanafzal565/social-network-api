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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->foreign("user_id")->references("id")->on("users")->onUpdate("CASCADE")->onDelete("CASCADE");
            $table->longText("caption")->nullable();
            $table->longText("files")->nullable();
            $table->longText("type")->nullable();
            $table->longText("activity")->nullable();
            $table->longText("activity_value")->nullable();
            $table->unsignedBigInteger("with_user")->default(0);
            $table->unsignedBigInteger("likes")->default(0);
            $table->unsignedBigInteger("comments")->default(0);
            $table->unsignedBigInteger("shares")->default(0);
            $table->unsignedBigInteger("shared_post_id")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
