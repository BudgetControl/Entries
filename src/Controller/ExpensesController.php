<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Entry\Controller\Controller;
use Budgetcontrol\Library\Entity\Entry as EntryType;
use Budgetcontrol\Library\Model\Expense;
use Dotenv\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ExpensesController extends Controller
{
    public function get(Request $request, Response $response, $argv): Response
    {
        $page = $request->getQueryParams()['page'] ?? 1;
        $per_page = $request->getQueryParams()['per_page'] ?? 10;
        $planned = (bool) @$request->getQueryParams()['planned'] ?? null;

        $wsId = $argv['wsid'];
        $entries = Expense::WithRelations()->where('workspace_id', $wsId)->where('type', EntryType::expenses->value)
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
        $entries = Expense::WithRelations()->where('workspace_id', $wsId)->where('uuid', $entryId)->get();

        if ($entries->isEmpty()) {
            return response([], 404);
        }

        return response(
            $entries->first()->toArray()
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
        $data['planned'] = $this->isPlanned($data['date_time']);
        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4();
        $data['type'] = EntryType::expenses->value;
        
        $expenses = new Expense();
        $expenses->fill($data);
        $expenses->save();

        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $expenses->labels()->attach($label);
            }
        }

        return response(
            $expenses->toArray(),
            201
        );

    }

    public function update(Request $request, Response $response, $argv): Response
    {
        $this->validate($request);
        $this->workspaceId = $argv['wsid'];

        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];
        $entries = Expense::where('workspace_id', $wsId)->where('uuid', $entryId)->get();
        $oldEntry = clone $entries->first();

        if ($entries->isEmpty()) {
            return response([], 404);
        }

        $entry = $entries->first();

        $data = $request->getParsedBody();
        $data['planned'] = $this->isPlanned($data['date_time']);
        
        $entry->update($data);

        $entry->labels()->detach();
        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $entry->labels()->attach($label);
            }
        }

        return response(
            $entry->toArray()
        );
    }

    protected function validate(Request|array $request) 
    {

        if($request instanceof Request) {
            $request = $request->getParsedBody();
        }

        if($request['amount'] > 0) {
            throw new ValidationException('Amount must be less than 0');
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
            'geolocation' => 'array',
            'exclude_from_stats' => 'boolean',
        ]);

    }

}