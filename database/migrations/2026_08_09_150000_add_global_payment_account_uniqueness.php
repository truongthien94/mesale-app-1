<?php

use App\Models\UserPaymentAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('user_payment_accounts', 'destination_hash')) {
            Schema::table('user_payment_accounts', function (Blueprint $table): void {
                // A fixed-size digest keeps the global unique index small on MariaDB.
                $table->char('destination_hash', 64)->nullable()->after('account_number');
            });
        }

        foreach (DB::table('user_payment_accounts')->select([
            'id',
            'payment_method',
            'bank_name',
            'account_number',
        ])->orderBy('id')->get() as $account) {
            DB::table('user_payment_accounts')
                ->where('id', $account->id)
                ->update([
                    'destination_hash' => UserPaymentAccount::destinationHash(
                        $account->payment_method,
                        $account->bank_name,
                        $account->account_number,
                    ),
                ]);
        }

        $duplicates = DB::table('user_payment_accounts')
            ->select('destination_hash')
            ->whereNotNull('destination_hash')
            ->groupBy('destination_hash')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('destination_hash');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot enforce global payment-account uniqueness: duplicate payout destinations exist. '
                .'Resolve the duplicate user_payment_accounts rows before retrying this migration.'
            );
        }

        $hasUniqueIndex = collect(Schema::getIndexes('user_payment_accounts'))
            ->contains(fn (array $index): bool => $index['name'] === 'user_payment_accounts_destination_hash_unique');

        if (! $hasUniqueIndex) {
            Schema::table('user_payment_accounts', function (Blueprint $table): void {
                $table->unique('destination_hash', 'user_payment_accounts_destination_hash_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('user_payment_accounts', 'user_payment_accounts_destination_hash_unique')) {
            Schema::table('user_payment_accounts', function (Blueprint $table): void {
                $table->dropUnique('user_payment_accounts_destination_hash_unique');
            });
        }

        if (Schema::hasColumn('user_payment_accounts', 'destination_hash')) {
            Schema::table('user_payment_accounts', function (Blueprint $table): void {
                $table->dropColumn('destination_hash');
            });
        }
    }
};
