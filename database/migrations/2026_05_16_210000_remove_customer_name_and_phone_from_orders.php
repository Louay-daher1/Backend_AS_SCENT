<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('orders')->whereNotNull('user_id')->get() as $order) {
            $user = User::query()->find($order->user_id);
            if (! $user) {
                continue;
            }

            $updates = [];
            if (! empty($order->customer_name) && empty($user->name)) {
                $updates['name'] = $order->customer_name;
            }
            if (! empty($order->phone) && empty($user->phone)) {
                $updates['phone'] = $order->phone;
            }

            if ($updates !== []) {
                $user->update($updates);
            }
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('discount_code');
            $table->string('phone')->nullable()->after('customer_name');
        });

        foreach (DB::table('orders')->whereNotNull('user_id')->get() as $order) {
            $user = User::query()->find($order->user_id);
            if (! $user) {
                continue;
            }

            DB::table('orders')->where('id', $order->id)->update([
                'customer_name' => $user->name,
                'phone' => $user->phone,
            ]);
        }
    }
};
