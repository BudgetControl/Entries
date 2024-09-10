<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Entry\Controller\Controller;
use Budgetcontrol\Library\Entity\Entry as EntryType;
use Budgetcontrol\Library\Model\Transfer;
use Budgetcontrol\Library\Model\Wallet;
use Dotenv\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Library\Service\Wallet\WalletService;

class TransferController extends Controller
{
    public function get(Request $request, Response $response, $argv): Response
    {
        $page = $request->getQueryParams()['page'] ?? 1;
        $per_page = $request->getQueryParams()['per_page'] ?? 10;
        $planned = (bool) @$request->getQueryParams()['planned'] ?? null;

        $wsId = $argv['wsid'];
        $entries = Transfer::WithRelations()->where('workspace_id', $wsId)->where('type', EntryType::transfer->value)
                 ->orderBy('date_time', 'desc');

        if($planned === false) {
            $entries = $entries->where('planned', 0);
        } elseif($planned === true) {
            $entries = $entries->where('planned', 1);
        }

        $entries = $entries->paginate($per_page, ['*'], 'page', $page);

        return response(
            $entries->toArray()
        );
    }

    public function show(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];
        $entries = Transfer::WithRelations()->where('workspace_id', $wsId)->where('uuid', $entryId)->first();

        if (empty($entries)) {
            return response([], 404);
        }

        return response(
            $entries->toArray()
        );
    }

    public function create(Request $request, Response $response, $argv): Response
    {
        $this->validate($request);
        $this->workspaceId = $argv['wsid'];
        
        $wsId = $argv['wsid'];
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
        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4();

        $transfer = new Transfer();
        $transfer->fill($data);
        $transfer->save();

        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label);
                $transfer->labels()->attach($label);
            }
        }
        
        // now save new entry transfer with inverted amount
        $data['amount'] = $data['amount'] * -1;
        $data['transfer_id'] = $transfer->account_id;
        $data['account_id'] = $transfer->transfer_id;
        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4();

        $transferTo = new Transfer();
        $transferTo->fill($data);
        $transferTo->save();

        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label);
                $transferTo->labels()->attach($label);
            }
        }

        // set the transfer relations
        $transfer->transfer_relation = $transferTo->uuid;
        $transferTo->transfer_relation = $transfer->uuid;

        $transfer->save();
        $transferTo->save();

        $wallet = new WalletService($transfer);
        $wallet->sum();

        $walletTransferTo = new WalletService($transferTo);
        $walletTransferTo->sum();
        
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
        $this->validate($request);
        $this->workspaceId = $argv['wsid'];
        
        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];

        $transfer = Transfer::where('workspace_id', $wsId)->where('uuid', $entryId)->first();
        $transferTo = Transfer::where('workspace_id', $wsId)->where('uuid', $transfer->transfer_relation)->first();

        $olderTransfer = clone $transfer;
        $olderTransferTo = clone $transferTo;

        if (!$transfer || !$transferTo) {
            return response([], 404);
        }

        $data = $request->getParsedBody();

        $data['amount'] = $data['amount'] * -1;
        $data['planned'] = $this->isPlanned($data['date_time']);
        $transfer->update($data);

        $transfer->labels()->detach();
        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label);
                $transfer->labels()->attach($label);
            }
        }

        // now save new entry transfer with inverted amount
        $data['amount'] = $data['amount'] * -1;
        $data['transfer_id'] = $transfer->account_id;
        $data['account_id'] = $transfer->transfer_id;
        $data['planned'] = $this->isPlanned($data['date_time']);
        $data['workspace_id'] = $wsId;

        $transferTo->update($data);

        $transferTo->labels()->detach();
        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label);
                $transferTo->labels()->attach($label);
            }
        }

        $wallet = new WalletService($transfer, $olderTransfer);
        $wallet->sum();

        $walletTransferTo = new WalletService($transferTo, $olderTransferTo);
        $walletTransferTo->sum();

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
        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];

        $transfer = Transfer::where('workspace_id', $wsId)->where('uuid', $entryId)->first();
        $transferTo = Transfer::where('workspace_id', $wsId)->where('uuid', $transfer->transfer_relation)->first();

        if (empty($transfer) || empty($transferTo)) {
            return response([], 404);
        }

        $transfer->delete();
        $transferTo->delete();

        $wallet = new WalletService($transfer);
        $wallet->subtract();

        $walletTransferTo = new WalletService($transferTo);
        $walletTransferTo->subtract();

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