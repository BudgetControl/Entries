<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Entry\Controller\Controller;
use Budgetcontrol\Library\Entity\Entry as EntryType;
use Budgetcontrol\Library\Model\Debit;
use Budgetcontrol\Library\Model\Payee;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Library\Service\Wallet\WalletService;

class DebitController extends Controller
{
    private int $wsid;

    public function get(Request $request, Response $response, $argv): Response
    {
        $page = $request->getQueryParams()['page'] ?? 1;
        $per_page = $request->getQueryParams()['per_page'] ?? 10;

        $wsId = $argv['wsid'];
        $entries = Debit::WithRelations()->where('workspace_id', $wsId)->where('type', EntryType::debit->value)
                 ->orderBy('date_time', 'desc');

        $entries = $entries->paginate($per_page, ['*'], 'page', $page);

        return response(
            $entries->toArray()
        );
    }

    public function create(Request $request, Response $response, $argv): Response
    {
        $this->validate($request);
        $this->workspaceId = $argv['wsid'];

        $wsId = $argv['wsid'];
        $this->wsid = $wsId;

        $data = $request->getParsedBody();

        try {
            $this->validate($data);
        } catch (\Exception $e) {
            Log::warning($e->getMessage());
            return response(
                ['error' => $e->getMessage()],
                400
            );
        }

        $data['workspace_id'] = $wsId;
        $data['planned'] = $this->isPlanned($data['date_time']);
        $data['category_id'] = 55;
        $data['payee_id'] = $this->createOrExistPayee($data['payee_id']);
        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4();

        $debit = new Debit();
        $debit->fill($data);
        $debit->save();

        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $debit->labels()->attach($label);
            }
        }
        
        $wallet = new WalletService($debit);
        $wallet->sum();

        return response(
            $debit->toArray(),
            201
        );

    }

    public function update(Request $request, Response $response, $argv): Response
    {
        $this->validate($request);
        $this->workspaceId = $argv['wsid'];

        $wsId = $argv['wsid'];
        $this->wsid = $wsId;

        $entryId = $argv['uuid'];
        $entries = Debit::where('workspace_id', $wsId)->where('uuid', $entryId)->get();
        $oldEntry = $entries->first();

        if ($entries->isEmpty()) {
            return response([], 404);
        }

        $entry = $entries->first();
        
        $data = $request->getParsedBody();
        $data['planned'] = $this->isPlanned($data['date_time']);
        $data['category_id'] = 55;
        $data['payee_id'] = $this->createOrExistPayee($data['payee_id']);
        
        $entry->update($data);

        $entry->labels()->detach();
        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $entry->labels()->attach($label);
            }
        }
        
        $wallet = new WalletService($entry, $oldEntry);
        $wallet->sum();

        return response(
            $entry->toArray()
        );
    }


    protected function validate(Request|array $request) 
    {

        if($request instanceof Request) {
            $request = $request->getParsedBody();
        }

        Validator::make($request, [
            'date_time' => 'required|date',
            'amount' => 'required|numeric',
            'note' => 'string',
            'type' => 'string',
            'waranty' => 'boolean',
            'confirmed' => 'boolean',
            'category_id' => 'required|integer',
            'model_id' => 'required|integer',
            'account_id' => 'required|integer',
            'currency_id' => 'required|integer',
            'payment_type' => 'required|integer',
            'payee_id' => 'required|integer',
            'geolocation' => 'array',
            'exclude_from_stats' => 'boolean',
        ]);

    }

    /**
     * Creates or checks if a payee with the given ID exists.
     *
     * @param int|string $id The ID of the payee.
     * @return int The ID of the payee.
     */
    private function createOrExistPayee(string|int $id): int
    {
        $payee = Payee::find($id);
        if(!$payee) {
            $payee = new Payee();
            $payee->fill(['name' => $id, 'workspace_id' => $this->wsid]);
            $payee->save();
        }

        return $payee->id;
    }

}