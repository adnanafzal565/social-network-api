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
        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_1")->nullable();
            $table->foreign("user_1")->references("id")->on("users")->onUpdate("CASCADE")->onDelete("CASCADE");
            $table->unsignedBigInteger("user_2")->nullable();
            $table->foreign("user_2")->references("id")->on("users")->onUpdate("CASCADE")->onDelete("CASCADE");
            $table->enum("status", ["pending", "rejected", "accepted"])->default("pending");
            $table->timestamp("accepted_at")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friends');
    }
};
