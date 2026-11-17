<?php
namespace Budgetcontrol\Entry\Controller;

use Budgetcontrol\Library\Entity\Entry as EntryType;
use Budgetcontrol\Library\Model\Expense;
use Budgetcontrol\Library\Model\Income;
use Budgetcontrol\Library\Model\Wallet;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Ramsey\Uuid\Uuid;

final class SyncExpenseController extends Controller
{
    public function create(Request $request, Response $response, array $args): Response
    {
        $secret = (string) env('SYNC_API_SECRET', '');
        if ($secret === '' || !hash_equals($secret, $request->getHeaderLine('X-Sync-Secret'))) return response(['error' => 'Forbidden'], 403);
        $data = $request->getParsedBody() ?: [];
        $wsId = (int) $args['wsid'];
        if ($wsId < 1 || !preg_match('/^[a-f0-9]{64}$/', $data['source_hash'] ?? '') || !is_numeric($data['amount'] ?? null) || (float)$data['amount'] == 0 || empty($data['date_time'])) return response(['error' => 'Invalid transaction'], 422);
        foreach (['account_id', 'category_id', 'currency_id', 'payment_type'] as $field) {
            if (filter_var($data[$field] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) return response(['error' => 'Invalid expense settings'], 422);
        }
        if (!Wallet::where('id', $data['account_id'])->where('workspace_id', $wsId)->exists()) return response(['error' => 'Wallet not found'], 422);
        $result = DB::connection()->transaction(function () use ($data, $wsId) {
            $uuid = Uuid::uuid4()->toString();
            $inserted = DB::table('sync_imported_expenses')->insertOrIgnore(['workspace_id' => $wsId, 'source_hash' => $data['source_hash'], 'entry_uuid' => $uuid, 'created_at' => gmdate('Y-m-d H:i:s')]);
            if (!$inserted) return 'duplicate';
            unset($data['source_hash']);
            $data['workspace_id'] = $wsId;
            $data['uuid'] = $uuid;
            $incoming = (float) $data['amount'] > 0;
            $data['type'] = $incoming ? EntryType::incoming->value : EntryType::expenses->value;
            // Only booked bank transactions reach this endpoint.
            $data['planned'] = false;
            $data['confirmed'] = true;
            $expense = $incoming ? new Income() : new Expense();
            $expense->fill($data);
            $expense->save();
            $this->workspaceId = $wsId;
            $label = $this->createOrGetLabel('sync', '#059669');
            $expense->labels()->attach($label);
            return 'created';
        });
        return response(['status' => $result], $result === 'created' ? 201 : 200);
    }
}
