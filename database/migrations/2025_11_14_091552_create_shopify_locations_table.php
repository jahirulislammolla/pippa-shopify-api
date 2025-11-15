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
        Schema::create('shopify_locations', function (Blueprint $table) {
            $table->id();

            // যদি আলাদা shops টেবিল থাকে, তাহলে fk রাখতে পারো
            $table->string('shop_domain'); // যেমন: example.myshopify.com

            // Shopify থেকে আসা location gid
            $table->string('shopify_location_id'); // gid://shopify/Location/...

            $table->string('name');
            $table->string('address1')->nullable();
            $table->string('city')->nullable();
            $table->string('province_code', 10)->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('zip', 20)->nullable();

            $table->timestamps();

            $table->unique(['shop_domain', 'shopify_location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_locations');
    }
};
