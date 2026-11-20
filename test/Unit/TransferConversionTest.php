<?php

namespace Budgetcontrol\Test\Unit;

use Budgetcontrol\Entry\Controller\TransferController;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class TransferConversionTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['APP_KEY'] = 'base64:' . base64_encode(str_repeat('a', 32));
        $db = new DB();
        $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $db->setAsGlobal();
        $db->bootEloquent();
        DB::statement('PRAGMA foreign_keys = ON');
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication([
            'date' => new Carbon(),
            'validator' => new Factory(new Translator(new ArrayLoader(), 'en')),
        ]);

        $schema = $db->schema();
        $schema->create('wallets', function ($table) {
            $table->increments('id');
        });
        $schema->create('entries', function ($table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('type');
            $table->string('date_time');
            $table->decimal('amount', 12, 2);
            $table->text('note')->nullable();
            $table->boolean('has_keywords')->nullable();
            $table->integer('category_id');
            $table->unsignedInteger('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('wallets');
            $table->integer('currency_id');
            $table->integer('payment_type');
            $table->integer('workspace_id');
            $table->boolean('planned')->default(false);
            $table->boolean('confirmed');
            $table->boolean('transfer')->nullable();
            $table->integer('transfer_id')->nullable();
            $table->string('transfer_relation')->nullable();
            $table->integer('payee_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        $schema->create('labels', function ($table) {
            $table->increments('id');
            $table->string('uuid');
            $table->integer('workspace_id');
            $table->string('name');
            $table->string('color');
            $table->softDeletes();
            $table->timestamps();
        });
        $schema->create('entry_labels', function ($table) {
            $table->integer('entry_id');
            $table->integer('labels_id');
        });
        DB::table('wallets')->insert([['id' => 277], ['id' => 12]]);
        DB::table('entries')->insert([
            'uuid' => 'expense-uuid', 'type' => 'expenses', 'date_time' => '2026-11-19 12:00:00',
            'amount' => -781.88, 'category_id' => 12, 'account_id' => 277, 'currency_id' => 2,
            'payment_type' => 3, 'workspace_id' => 7, 'planned' => false, 'confirmed' => true,
            'transfer' => false,
        ]);
        DB::table('labels')->insert(['id' => 477, 'uuid' => 'label-uuid', 'workspace_id' => 7, 'name' => 'test', 'color' => '#000000']);
    }

    private function update(string $uuid, int $workspace = 7, array $overrides = []): \Psr\Http\Message\ResponseInterface
    {
        $payload = [
            'amount' => 781.88, 'note' => 'Payment', 'labels' => [['name' => 477, 'color' => null]],
            'currency_id' => 2, 'payment_type' => 3, 'transfer_id' => 0,
            'date_time' => '2026-11-19T12:00:00+00:00', 'confirmed' => true,
            'type' => 'transfer', 'account_id' => 277, 'category_id' => 0,
        ];
        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/')->withParsedBody(array_replace($payload, $overrides));
        return (new TransferController())->update($request, new Response(), ['wsid' => $workspace, 'uuid' => $uuid]);
    }

    public function test_converts_expense_to_external_transfer_and_can_update_it_again(): void
    {
        $response = $this->update('expense-uuid');
        self::assertSame(200, $response->getStatusCode());
        $result = json_decode((string) $response->getBody(), true);
        $source = DB::table('entries')->where('uuid', 'expense-uuid')->first();
        $other = DB::table('entries')->where('uuid', $source->transfer_relation)->first();
        self::assertSame('transfer', $source->type);
        self::assertSame(1, $source->transfer);
        self::assertSame(75, $source->category_id);
        self::assertEquals(-781.88, $source->amount);
        self::assertSame(0, $source->transfer_id);
        self::assertNull($other->account_id);
        self::assertSame(277, $other->transfer_id);
        self::assertEquals(781.88, $other->amount);
        self::assertSame($source->uuid, $other->transfer_relation);
        self::assertSame($other->uuid, $result['to_this']['uuid']);
        self::assertSame(2, DB::table('entry_labels')->count());
        self::assertSame(200, $this->update('expense-uuid')->getStatusCode());
        self::assertSame(2, DB::table('entries')->count());
    }

    public function test_missing_or_other_workspace_entry_returns_404(): void
    {
        self::assertSame(404, $this->update('missing')->getStatusCode());
        self::assertSame(404, $this->update('expense-uuid', 8)->getStatusCode());
        self::assertSame(1, DB::table('entries')->count());
    }

    public function test_converts_expense_to_an_internal_wallet(): void
    {
        self::assertSame(200, $this->update('expense-uuid', 7, ['transfer_id' => 12])->getStatusCode());
        $source = DB::table('entries')->where('uuid', 'expense-uuid')->first();
        $other = DB::table('entries')->where('uuid', $source->transfer_relation)->first();
        self::assertSame(12, $other->account_id);
        self::assertSame(277, $other->transfer_id);
    }

    public function test_creates_transfer_to_external_wallet_with_foreign_keys_enabled(): void
    {
        $payload = [
            'amount' => 50, 'currency_id' => 2, 'payment_type' => 3, 'transfer_id' => 0,
            'date_time' => '2026-11-19T12:00:00+00:00', 'confirmed' => true,
            'type' => 'transfer', 'account_id' => 277, 'category_id' => 0,
        ];
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/')->withParsedBody($payload);
        $response = (new TransferController())->create($request, new Response(), ['wsid' => 7]);
        self::assertSame(201, $response->getStatusCode());
        $result = json_decode((string) $response->getBody(), true);
        $other = DB::table('entries')->where('uuid', $result['to_this']['uuid'])->first();
        self::assertNull($other->account_id);
        self::assertEquals(50, $other->amount);
    }

    public function test_conversion_rolls_back_if_the_counterpart_cannot_be_saved(): void
    {
        // Fail the second insert, after the original entry and its labels have been updated.
        DB::statement("CREATE TRIGGER reject_counterpart BEFORE INSERT ON entries BEGIN SELECT RAISE(ABORT, 'counterpart failed'); END");
        try {
            $this->update('expense-uuid');
            self::fail('Expected the counterpart insert to fail');
        } catch (\Illuminate\Database\QueryException $e) {
            self::assertStringContainsString('counterpart failed', $e->getMessage());
        }
        $source = DB::table('entries')->where('uuid', 'expense-uuid')->first();
        self::assertSame('expenses', $source->type);
        self::assertNull($source->transfer_relation);
        self::assertSame(1, DB::table('entries')->count());
        self::assertSame(0, DB::table('entry_labels')->count());
    }

    public function test_existing_transfer_with_missing_counterpart_is_not_converted(): void
    {
        DB::table('entries')->where('uuid', 'expense-uuid')->update(['type' => 'transfer', 'transfer' => true]);
        self::assertSame(404, $this->update('expense-uuid')->getStatusCode());
        self::assertSame(1, DB::table('entries')->count());
    }
}
