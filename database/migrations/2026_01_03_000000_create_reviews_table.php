<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->index();
            $table->string('author_name')->nullable();
            $table->text('text')->nullable();
            $table->tinyInteger('rating')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('reviews'); }
};
