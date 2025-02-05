<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Budgetcontrol\Library\Model\Wallet;
use Budgetcontrol\Library\Model\Transfer;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Entry\Controller\Controller;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Budgetcontrol\Library\Entity\Entry as EntryType;
use Psr\Http\Message\ServerRequestInterface as Request;

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
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $transfer->labels()->attach($label);
            }
        }
        
        // now save new entry transfer with inverted amount
        $data = $request->getParsedBody();
        if(!empty($data['workspace_id'])) {
            $data['workspace_id'] = $this->findWorkspaceId($data['workspace_id']);
        } else {
            $data['workspace_id'] = $wsId;
        }

        $data['amount'] = $data['amount'];
        $data['transfer_id'] = $transfer->account_id;
        $data['account_id'] = $transfer->transfer_id;
        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4();

        $transferTo = new Transfer();
        $transferTo->fill($data);
        $transferTo->save();

        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $transferTo->labels()->attach($label);
            }
        }

        // set the transfer relations
        $transfer->transfer_relation = $transferTo->uuid;
        $transferTo->transfer_relation = $transfer->uuid;

        $transfer->save();
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
        $this->validate($request);
        $this->workspaceId = $argv['wsid'];
        $data = $request->getParsedBody();
        
        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];

        $transfer = Transfer::where('workspace_id', $wsId)->where('uuid', $entryId)->first();
        $transferTo = Transfer::where('workspace_id', $wsId)->where('uuid', $transfer->transfer_relation)->first();

        if (!$transfer || !$transferTo) {
            return response([], 404);
        }


        $data['workspace_id'] = $wsId;
        $data['amount'] = $data['amount'] * -1;
        $data['planned'] = $this->isPlanned($data['date_time']);
        $transfer->update($data);

        $transfer->labels()->detach();
        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $transfer->labels()->attach($label);
            }
        }

        // now save new entry transfer with inverted amount
        $data = $request->getParsedBody();
        if(!empty($data['workspace_id'])) {
            $data['workspace_id'] = $this->findWorkspaceId($data['workspace_id']);
        } else {
            $data['workspace_id'] = $wsId;
        }

        $data['amount'] = $data['amount'];
        $data['transfer_id'] = $transfer->account_id;
        $data['account_id'] = $transfer->transfer_id;
        $data['planned'] = $this->isPlanned($data['date_time']);

        $transferTo->update($data);

        $transferTo->labels()->detach();
        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $transferTo->labels()->attach($label);
            }
        }

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
        $transferTo = Transfer::where('uuid', $transfer->transfer_relation)->first();

        if (empty($transfer) || empty($transferTo)) {
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