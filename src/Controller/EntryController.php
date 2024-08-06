<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Entry\Service\EntryService;
use Budgetcontrol\Entry\Controller\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EntryController extends Controller
{
    public function get(Request $request, Response $response, $argv): Response
    {
        $page = $request->getQueryParams()['page'] ?? 1;
        $perPage = $request->getQueryParams()['perPage'] ?? 10;
        $planned = (bool) @$request->getQueryParams()['planned'] ?? null;

        $wsId = $argv['wsid'];
        $entries = Entry::WithRelations()->where('workspace_id', $wsId)
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

    public function create(Request $request, Response $response, $argv): Response
    {
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

        $entry = new Entry();
        $entry->fill($data);
        $this->saveBalance($entry);

        return response(
            $entry->toArray(),
            201
        );

    }

    public function update(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];
        $entries = Entry::where('workspace_id', $wsId)->where('uuid', $entryId)->get();

        if ($entries->isEmpty()) {
            return response([], 404);
        }

        $entry = $entries->first();
        $this->setOldEntry($entry);

        $data = $request->getParsedBody();
        $data['planned'] = $this->isPlanned($data['date_time']);
        
        $entry->update($data);
        $this->updateBalance($entry);

        return response(
            $entry->toArray()
        );
    }

    public function delete(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];
        $entries = Entry::where('workspace_id', $wsId)->where('uuid', $entryId)->get();

        if ($entries->isEmpty()) {
            return response([], 404);
        }

        $entries->first()->delete();

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
            'transfer_relation' => 'integer',
            'currency_id' => 'required|integer',
            'payment_type' => 'required|integer',
            'payee_id' => 'required|integer',
            'geolocation' => 'array',
            'exclude_from_stats' => 'boolean',
        ]);

    }
}