<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('icon', [
                'Tag',
                'Code',
                'Palette',
                'Briefcase',
                'TrendingUp',
                'Database',
                'BookOpen',
                'Laptop',
                'Lightbulb',
                'Camera'
            ])->default('Tag');
            $table->enum('color', [
                'blue',
                'purple',
                'green',
                'yellow',
                'red',
                'pink',
                'indigo',
                'teal',
                'orange',
                'cyan'
            ])->default('blue');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
