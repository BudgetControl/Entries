<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Entry\Domain\Model\Entries;
use Budgetcontrol\Entry\Controller\Controller;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

class EntryController extends Controller
{
    public function get(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsis'];
        $entries = Entries::where('workspace_id', $wsId)->get()->paginate(10);

        return response(
            $entries->toArray()
        );
    }

    public function show(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsis'];
        $entryId = $argv['uuid'];
        $entries = Entries::where('workspace_id', $wsId)->where('id', $entryId)->get();

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

        $entry = new Entries();
        $entry->workspace_id = $wsId;
        $data['uuid'] = Uuid::uuid4()->toString();
        $data['planned'] = $this->planned($data['date_time']);
        $entry->fill($data);
        $entry->save();

        return response(
            $entry->toArray(),
            201
        );

    }

    public function update(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsis'];
        $entryId = $argv['uuid'];
        $entries = Entries::where('workspace_id', $wsId)->where('uuid', $entryId)->get();

        if ($entries->isEmpty()) {
            return response([], 404);
        }

        $entry = $entries->first();
        $data = $request->getParsedBody();
        $data['planned'] = $this->planned($data['date_time']);
        $entry->fill($data);
        $entry->save();

        return response(
            $entry->toArray()
        );
    }

    public function delete(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsis'];
        $entryId = $argv['uuid'];
        $entries = Entries::where('workspace_id', $wsId)->where('id', $entryId)->get();

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

    protected function planned(string $date_time): bool
    {
        $value = false;
        $date_time = Carbon::createFromFormat('Y-d-m H:i:s', $date_time)->toAtomString();
        if($date_time > Carbon::now()->toAtomString()) {
            $value = true;
        }

        return $value;
    }
}