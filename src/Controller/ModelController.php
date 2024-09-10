<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Budgetcontrol\Entry\Entity\Order;
use Budgetcontrol\Entry\Entity\Filter;
use Budgetcontrol\Library\Model\Model;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Entry\Controller\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModelController extends Controller
{
    public function list(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsid'];
        $entries = Model::WithRelations()->where('workspace_id', $wsId);

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
        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4();
        
        $model = new Model();
        $model->fill($data);
        $model->save();

        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label);
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
        $this->validate($request);
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
        $model = Model::where('uuid', $uuid)->first();

        if(!$model) {
            return response(
                ['error' => 'Not found'],
                404
            );
        }

        $model->update($data);

        $model->labels()->detach();
        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label);
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
        $entries = Model::WithRelations()->where('workspace_id', $wsId)->where('uuid', $entryId)
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
        
        $model = Model::where('uuid', $uuid)->first();

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

        Validator::make($request, [
            'date_time' => 'required|date',
            'amount' => 'required|numeric',
            'note' => 'string',
            'type' => 'string',
            'installment' => 'boolean',
            'category_id' => 'required|integer',
            'model_id' => 'required|integer',
            'account_id' => 'required|integer',
            'currency_id' => 'required|integer',
            'payment_type' => 'required|integer',
            'geolocation' => 'array',
            'exclude_from_stats' => 'boolean',
        ]);

    }
}