<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Library\Model\Income;
use Budgetcontrol\Entry\Controller\Controller;
use Budgetcontrol\Library\Entity\Entry as EntryType;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Library\Service\Wallet\WalletService;

class IncomingController extends Controller
{
    public function get(Request $request, Response $response, $argv): Response
    {
        $page = $request->getQueryParams()['page'] ?? 1;
        $perPage = $request->getQueryParams()['perPage'] ?? 10;
        $planned = (bool) @$request->getQueryParams()['planned'] ?? null;

        $wsId = $argv['wsid'];
        $entries = Income::WithRelations()->where('workspace_id', $wsId)->where('type', EntryType::incoming->value)
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
        $this->validate($request);

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
        $incoming = new Income();
        $incoming->fill($data);
        
        $wallet = new WalletService($incoming);
        $wallet->sum();

        return response(
            $incoming->toArray(),
            201
        );

    }

    public function update(Request $request, Response $response, $argv): Response
    {
        $this->validate($request);

        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];
        $entries = Income::where('workspace_id', $wsId)->where('uuid', $entryId)->get();

        if ($entries->isEmpty()) {
            return response([], 404);
        }

        $entry = $entries->first();

        $data = $request->getParsedBody();
        $data['planned'] = $this->isPlanned($data['date_time']);
        
        $entry->update($data);
        
        $wallet = new WalletService($entry);
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

        if($request['amount'] < 0) {
            throw new ValidationException('Amount must be greater than or equal to 0');
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