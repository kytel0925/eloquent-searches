<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name');
            $table->dateTime('birth_date');
            $table->enum('sex', ['M', 'F']);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tag', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('active');
            $table->timestamps();
        });

        Schema::create('taggable', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('tag');
            $table->morphs('taggable', 'taggable_class');
            $table->unsignedInteger('order')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('country', function (Blueprint $table) {
            $table->id();
            $table->char('iso_code', 5)->unique();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('administrative_division', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrative_division_id')->nullable()->constrained('administrative_division');
            $table->foreignId('country_id')->constrained('country');
            $table->foreignId('tag_id')->constrained('tag');
            $table->string('abbr', 10)->unique();
            $table->string('name');
            $table->json('options')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('country');
            $table->foreignId('administrative_division_id')->constrained('administrative_division');
            $table->string('abbr', 10)->unique()->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained();
            $table->foreignId('country_id')->constrained('country');
            $table->foreignId('location_id')->constrained('location');
            $table->text('description');
            $table->json('options')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('persons');
            $table->string('title');
            $table->text('description');
            $table->boolean('complete')->default(true);
            $table->json('options')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('task_follower', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained();
            $table->foreignId('person_id')->constrained();

            $table->unique(['person_id', 'task_id'], 'task_follower_unique');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('persons');
            $table->foreignId('task_id')->constrained();
            $table->longText('body');
            $table->unsignedInteger('likes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('path');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('secret')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('task_follower');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('location');
        Schema::dropIfExists('administrative_division');
        Schema::dropIfExists('country');
        Schema::dropIfExists('taggable');
        Schema::dropIfExists('tag');
        Schema::dropIfExists('persons');
    }
};
