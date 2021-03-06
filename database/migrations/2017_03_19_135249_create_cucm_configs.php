<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCucmConfigs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Site Code Table
        Schema::create('cucmsite', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sitecode')->index();                         // Name
            $table->string('trunking')->nullable()->index();           // Comment
            $table->string('e911')->nullable()->index();                  // Comment
            $table->integer('shortextenlength')->nullable();      // Comment
            $table->json('sitesummary')->nullable();           // JSON Details ID of DID Blocks used for the site.
            $table->json('sitedetails')->nullable();           // JSON Details ID of DID Blocks used for the site.
            $table->timestamps();                           // Time Stamps
            $table->softDeletes();                          // Soft Deletes
        });

        // Child Phone Plan - parent site
        Schema::create('cucmphone', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->index();
            $table->string('description')->nullable();
            $table->string('devicepool')->nullable()->index();
            $table->string('model')->nullable()->index();
            $table->string('ownerid')->nullable();
            $table->string('css')->nullable()->index();
            $table->string('erl')->nullable()->index();            // Future E911 Integration
            $table->string('ipv4address')->nullable();        // Future RISDB API

            $table->string('risdb_ipv4address')->nullable();            // RISDB IP Address
            $table->string('risdb_registration_status')->nullable();    // RISDB Status
            $table->json('config')->nullable();                // JSON Details Custom Field Data
            $table->json('lines')->nullable();              // JSON Details Custom Field Data
            $table->timestamps();                            // Time Stamps
            $table->softDeletes();                            // keep deactivated certificates in the table
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop('cucmsite');
        Schema::drop('cucmphone');
    }
}
