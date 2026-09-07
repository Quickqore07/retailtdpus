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
        // Schema::create('upload_workgroups', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->boolean('active')->default(true);
        //     $table->unsignedBigInteger('created_by')->nullable();
        //     $table->unsignedBigInteger('updated_by')->nullable();
        //     $table->timestamps();
        // });

        // Schema::create('upload_companies', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('workgroup_id')->constrained('upload_workgroups')->onDelete('cascade');
        //     $table->string('name');
        //     $table->string('store_number');
        //     $table->unsignedBigInteger('created_by')->nullable();
        //     $table->unsignedBigInteger('updated_by')->nullable();
        //     $table->timestamps();
        // });

      

        // Schema::create('upload_users', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->string('username')->unique();
        //     $table->string('password');
        //     $table->text('tdpus_company_access')->nullable();
        //     $table->text('external_company_access')->nullable();
        //     $table->string('current_password')->nullable();
        //     $table->text('folder_access')->nullable();
        //     $table->unsignedBigInteger('created_by')->nullable();
        //     $table->unsignedBigInteger('updated_by')->nullable();
        //     $table->timestamps();
        // });

        Schema::create('upload_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->json('folder_access')->nullable();
            $table->boolean('only_upload')->default(false)->after('folder_access');
        });
        
        Schema::table('roles', function (Blueprint $table) {
            $table->json('folder_access')->nullable();
        });

        // Add only_upload to company table
        Schema::table('company', function (Blueprint $table) {
            $table->boolean('only_upload')->default(false)->after('active');
        });

        // Add only_upload to workgroup table
        Schema::table('workgroup', function (Blueprint $table) {
            $table->boolean('only_upload')->default(false)->after('active');
        });

        Schema::create('upload_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('upload_folders');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('upload_users');
        // Schema::dropIfExists('upload_folders');
        // Schema::dropIfExists('upload_companies');
        // Schema::dropIfExists('upload_workgroups');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('folder_access');
            $table->dropColumn('only_upload');
        });
        
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('folder_access');
        });
        Schema::table('company', function (Blueprint $table) {
            $table->dropColumn('only_upload');
        });
        Schema::table('workgroup', function (Blueprint $table) {
            $table->dropColumn('only_upload');
        });
        Schema::dropIfExists('upload_documents');
        Schema::dropIfExists('upload_folders');
    }
};
