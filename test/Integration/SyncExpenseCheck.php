<?php
// Run with: php test/Integration/SyncExpenseCheck.php
require __DIR__ . '/../../vendor/autoload.php';
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Carbon;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Budgetcontrol\Entry\Controller\SyncExpenseController;
$_ENV['APP_KEY'] = 'base64:' . base64_encode(str_repeat('a', 32));
$_ENV['SYNC_API_SECRET'] = 'unit-test-secret';
$db = new DB();
$db->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
$db->setAsGlobal(); $db->bootEloquent();
Facade::setFacadeApplication(['date' => new Carbon()]);
$schema = $db->schema();
$schema->create('wallets', function ($t) { $t->increments('id'); $t->integer('workspace_id'); $t->softDeletes(); });
$schema->create('entries', function ($t) { $t->increments('id'); $t->string('uuid'); $t->string('type'); $t->string('date_time'); $t->decimal('amount', 12, 2); $t->text('note')->nullable(); $t->boolean('has_keywords')->nullable(); $t->integer('category_id'); $t->integer('account_id'); $t->integer('currency_id'); $t->integer('payment_type'); $t->integer('workspace_id'); $t->boolean('planned'); $t->boolean('confirmed'); $t->boolean('transfer')->nullable(); $t->integer('transfer_id')->nullable(); $t->integer('payee_id')->nullable(); $t->timestamps(); });
$schema->create('sync_imported_expenses', function ($t) { $t->integer('workspace_id'); $t->string('source_hash'); $t->string('entry_uuid'); $t->string('created_at'); $t->primary(['workspace_id', 'source_hash']); });
$schema->create('labels', function ($t) { $t->increments('id'); $t->string('uuid'); $t->integer('workspace_id'); $t->string('name'); $t->string('color'); $t->softDeletes(); $t->timestamps(); });
$schema->create('entry_labels', function ($t) { $t->integer('entry_id'); $t->integer('labels_id'); });
DB::table('wallets')->insert(['id'=>1, 'workspace_id'=>7]);
$data=['source_hash'=>str_repeat('a',64),'amount'=>'-12.50','date_time'=>'2026-01-01 12:00:00','note'=>'Transaction','account_id'=>1,'category_id'=>1,'currency_id'=>2,'payment_type'=>1];
$controller = new SyncExpenseController(); $factory = new ServerRequestFactory();
$req = $factory->createServerRequest('POST','/7/sync-expense')->withParsedBody($data)->withHeader('X-Sync-Secret','unit-test-secret');
$first = $controller->create($req, new Response(), ['wsid'=>7]);
$second = $controller->create($req, new Response(), ['wsid'=>7]);
$bad = $controller->create($req->withHeader('X-Sync-Secret','wrong'), new Response(), ['wsid'=>7]);
$other = $controller->create($req, new Response(), ['wsid'=>8]);
if ($first->getStatusCode() !== 201 || $second->getStatusCode() !== 200 || json_decode((string)$second->getBody(),true)['status'] !== 'duplicate' || $bad->getStatusCode() !== 403 || $other->getStatusCode() !== 422 || DB::table('entries')->count() !== 1 || (int) DB::table('entries')->value('planned') !== 0 || !DB::table('entries')->value('confirmed') || DB::table('sync_imported_expenses')->count() !== 1) throw new Exception('Sync expense checks failed');
// A database error while saving the expense must roll back its idempotency marker.
$broken = $data; $broken['source_hash'] = str_repeat('b',64); $broken['note'] = str_repeat('x', 100);
$schema->drop('entries');
try { $controller->create($req->withParsedBody($broken), new Response(), ['wsid'=>7]); throw new Exception('Expected a database error'); } catch (\Illuminate\Database\QueryException $e) {}
if (DB::table('sync_imported_expenses')->count() !== 1) throw new Exception('Failed expense left an idempotency marker');
echo "Expense import, duplicate retry, authorization, workspace and rollback checks passed\n";
