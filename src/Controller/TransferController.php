<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Entry\Controller\Controller;
use Budgetcontrol\Entry\Domain\Enum\EntryType;
use Budgetcontrol\Entry\Domain\Model\Transfer;
use Budgetcontrol\Entry\Domain\Model\Wallet;
use Dotenv\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransferController extends Controller
{
    public function get(Request $request, Response $response, $argv): Response
    {
        $page = $request->getQueryParams()['page'] ?? 1;
        $perPage = $request->getQueryParams()['perPage'] ?? 10;
        $planned = (bool) $request->getQueryParams()['planned'] ?? null;

        $wsId = $argv['wsis'];
        $entries = Transfer::WithRelations()->where('workspace_id', $wsId)->where('type', EntryType::transfer->value)
                 ->orderBy('date_time', 'desc');

        if($planned === false) {
            $entries = $entries->where('planned', 0);
        } elseif($planned === true) {
            $entries = $entries->where('planned', 1);
        }

        $entries = $entries->paginate($perPage, ['*'], 'page', $page);

        return response(
            $entries->toArray()
        );
    }

    public function show(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsis'];
        $entryId = $argv['uuid'];
        $entries = Transfer::WithRelations()->where('workspace_id', $wsId)->where('id', $entryId)->get();

        if ($entries->isEmpty()) {
            return response([], 404);
        }

        return response(
            $entries->toArray()
        );
    }

    public function create(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsis'];
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
        // transfer relation transfer_id
        $data['amount'] = $data['amount'] * -1;
        $data['planned'] = $this->isPlanned($data['date_time']);
        $data['category_id'] = 75;

        $transfer = new Transfer();
        $transfer->fill($data);
        $transfer->save();
        
        // now save new entry transfer with inverted amount
        $data['amount'] = $data['amount'] * -1;
        $data['transfer_id'] = $transfer->account_id;
        $data['account_id'] = $transfer->transfer_id;
        $transferTo = new Transfer();
        $transferTo->fill($data);
        $transferTo->save();

        return response(
            [
                'transfer_this' => $transfer->toArray(),
                'to_this' => $transferTo->toArray()
            ],
            201
        );

    }

    public function update(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsis'];
        $entryId = $argv['uuid'];

        $transfer = Transfer::where('workspace_id', $wsId)->where('uuid', $entryId)->first();
        $transferTo = Transfer::where('workspace_id', $wsId)->where('uuid', $transfer->transfer_relation)->first();

        if (!$transfer || !$transferTo) {
            return response([], 404);
        }

        $data = $request->getParsedBody();
        
        $data['workspace_id'] = $wsId;
        // transfer relation transfer_id
        $data['amount'] = $data['amount'] * -1;
        $data['category_id'] = 75;
        $transfer->fill($data);
        $transfer->update();
        
        // now save new entry transfer with inverted amount
        $data['amount'] = $data['amount'] * -1;
        $data['transfer_id'] = $transfer->account_id;
        $data['account_id'] = $transfer->transfer_id;
        $data['planned'] = $this->isPlanned($data['date_time']);

        $transferTo->fill($data);
        $transferTo->update();

        return response(
            [
                'transfer_this' => $transfer->toArray(),
                'to_this' => $transferTo->toArray()
            ],
            200
        );
    }

    public function delete(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsis'];
        $entryId = $argv['uuid'];

        $transfer = Transfer::where('workspace_id', $wsId)->where('uuid', $entryId)->first();
        $transferTo = Transfer::where('workspace_id', $wsId)->where('uuid', $transfer->transfer_relation)->first();

        if (!$transfer) {
            return response([], 404);
        }

        $transfer->delete();
        $transferTo->delete();

        return response([], 204);
    }

    protected function validate(Request|array $request) 
    {

        if($request instanceof Request) {
            $request = $request->getParsedBody();
        }

        // check if transfer_id is valid
        if(Wallet::find($request['transfer_id']) === null) {
            throw new ValidationException('Invalid wallet ID');
        }

        Validator::make($request, [
            'date_time' => 'required|date',
            'amount' => 'required|numeric',
            'note' => 'string',
            'type' => 'string',
            'waranty' => 'boolean',
            'transfer' => 'boolean',
            'confirmed' => 'boolean',
            'installment' => 'boolean',
            'category_id' => 'required|integer',
            'model_id' => 'required|integer',
            'account_id' => 'required|integer',
            'transfer_id' => 'integer',
            'currency_id' => 'required|integer',
            'payment_type' => 'required|integer',
            'geolocation' => 'array',
            'exclude_from_stats' => 'boolean',
        ]);

    }

}