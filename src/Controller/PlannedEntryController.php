<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Budgetcontrol\Entry\Entity\Order;
use Budgetcontrol\Entry\Entity\Filter;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Entry\Controller\Controller;
use Budgetcontrol\Entry\Entity\Validations\PlannedType;
use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Model\PlannedEntry;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PlannedEntryController extends DebitController
{
    public function list(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsid'];
        $entries = PlannedEntry::WithRelations()->where('workspace_id', $wsId);

        if(!is_null(@$request->getQueryParams()['filters'])) {
            $filters = new Filter($request->getQueryParams()['filters']);
            $this->filters($entries, $filters);
        }

        if(!is_null(@$request->getQueryParams()['order'])) {
            $order = new Order($request->getQueryParams()['order']);
            $this->orderBy($entries, $order);
        }
                
        $entries = $entries->get();

        return response(
            $entries->toArray()
        );
    }

    public function create(Request $request, Response $response, $argv): Response
    {
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
        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4();
        
        $model = new PlannedEntry();
        $model->uuid = \Ramsey\Uuid\Uuid::uuid4();
        $model->date_time = $data['date_time'];
        $model->end_date_time = $data['end_date_time'];
        $model->planning = $data['planning'];
        $model->amount = $data['amount'];
        $model->note = $data['note'];
        $model->type = $data['type'];
        $model->category_id = $this->retriveCategoryIdOfEntryType($data['type'],$data['category_id']);
        $model->account_id = $data['account_id'];
        $model->currency_id = $data['currency_id'];
        $model->payment_type = $data['payment_type'];
        $model->workspace_id = $data['workspace_id'];
        $model->payee_id = !empty($data['payee_id']) ?? $this->createOrExistPayee($data['payee_id']);
        $model->save();

        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $model->labels()->attach($label);
            }
        }

        return response(
            $model->toArray(),
            201
        );

    }

    public function update(Request $request, Response $response, $argv): Response
    {
        $this->workspaceId = $argv['wsid'];

        $wsId = $argv['wsid'];
        $uuid = $argv['uuid'];
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
        $model = PlannedEntry::where('uuid', $uuid)->first();

        if(!$model) {
            return response(
                ['error' => 'Not found'],
                404
            );
        }

        $model->date_time = $data['date_time'];
        $model->end_date_time = $data['end_date_time'];
        $model->planning = $data['planning'];
        $model->amount = $data['amount'];
        $model->note = $data['note'];
        $model->type = $data['type'];
        $model->category_id = $this->retriveCategoryIdOfEntryType($data['type'],$data['category_id']);
        $model->account_id = $data['account_id'];
        $model->currency_id = $data['currency_id'];
        $model->payment_type = $data['payment_type'];
        $model->workspace_id = $data['workspace_id'];
        $model->payee_id = !empty($data['payee_id']) ?? $this->createOrExistPayee($data['payee_id']);
        $model->save();

        $model->labels()->detach();
        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $model->labels()->attach($label);
            }
        }

        return response(
            $model->toArray(),
            200
        );

    }

    public function show(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];
        $entries = PlannedEntry::WithRelations()->where('workspace_id', $wsId)->where('uuid', $entryId)
        ->where('deleted_at', null)
        ->first();

        if (!$entries) {
            return response([], 404);
        }

        return response(
            $entries->toArray()
        );
    }

    public function delete(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsid'];
        $uuid = $argv['uuid'];

        $data['workspace_id'] = $wsId;
        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4();
        
        $model = PlannedEntry::where('uuid', $uuid)->first();

        if(!$model) {
            return response(
                ['error' => 'Not found'],
                404
            );
        }

        $model->delete();

        return response(
            $model->toArray(),
            204
        );

    }

    protected function validate(Request|array $request)
    {

        if($request instanceof Request) {
            $request = $request->getParsedBody();
        }

        if($request['type'] === Entry::transfer) {
            throw new InvalidArgumentException('Transfer is not allowed for planned entries');
        }

        Validator::make($request, [
            'date_time' => 'required|date',
            'amount' => 'required|numeric',
            'note' => 'string',
            'type' => 'string',
            'category_id' => 'required|integer',
            'account_id' => 'required|integer',
            'currency_id' => 'required|integer',
            'payment_type' => 'required|integer',
            'planning' => ['required', new PlannedType],
        ]);

    }
}