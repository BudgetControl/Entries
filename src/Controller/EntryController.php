<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Budgetcontrol\Library\Model\Entry;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Entry\Service\EntryService;
use Budgetcontrol\Entry\Controller\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Budgetcontrol\Library\Service\Wallet\WalletService;
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