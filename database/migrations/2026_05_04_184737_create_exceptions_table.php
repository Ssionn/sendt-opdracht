<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('exceptions', function (Blueprint $table): void {
            $table->id();
            $table->string('message');
            $table->text('stack_trace');
            $table->string('file');
            $table->integer('line');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exceptions');
    }
};
