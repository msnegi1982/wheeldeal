<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_enquiry_notes', function (Blueprint $table) {
            $table->date('follow_up_at')->nullable()->after('note')->index();
        });

        Schema::table('enquiry_notes', function (Blueprint $table) {
            $table->date('follow_up_at')->nullable()->after('note')->index();
        });

        Schema::table('request_notes', function (Blueprint $table) {
            $table->date('follow_up_at')->nullable()->after('note')->index();
        });
    }

    public function down(): void
    {
        Schema::table('contact_enquiry_notes', function (Blueprint $table) {
            $table->dropColumn('follow_up_at');
        });

        Schema::table('enquiry_notes', function (Blueprint $table) {
            $table->dropColumn('follow_up_at');
        });

        Schema::table('request_notes', function (Blueprint $table) {
            $table->dropColumn('follow_up_at');
        });
    }
};
