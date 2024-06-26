<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Entry\Controller\Controller;
use Budgetcontrol\Entry\Domain\Enum\EntryType;
use Budgetcontrol\Entry\Domain\Model\Debit;
use Budgetcontrol\Entry\Domain\Model\Payee;
use Dotenv\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DebitController extends Controller
{
    public function get(Request $request, Response $response, $argv): Response
    {
        $page = $request->getQueryParams()['page'] ?? 1;
        $perPage = $request->getQueryParams()['perPage'] ?? 10;

        $wsId = $argv['wsis'];
        $entries = Debit::WithRelations()->where('workspace_id', $wsId)->where('type', EntryType::debit->value)
                 ->orderBy('date_time', 'desc');

        $entries = $entries->paginate($perPage, ['*'], 'page', $page);

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
        $data['planned'] = $this->isPlanned($data['date_time']);
        $data['category_id'] = 55;
        $data['payee_id'] = $this->createOrExistPayee($data['payee_id']);

        $debit = new Debit();
        $debit->fill($data);
        $debit->save();

        return response(
            $debit->toArray(),
            201
        );

    }

    public function update(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsis'];
        $entryId = $argv['uuid'];
        $entries = Debit::where('workspace_id', $wsId)->where('uuid', $entryId)->get();

        if ($entries->isEmpty()) {
            return response([], 404);
        }

        $entry = $entries->first();
        $data = $request->getParsedBody();
        $data['planned'] = $this->isPlanned($data['date_time']);
        $data['category_id'] = 55;
        $data['payee_id'] = $this->createOrExistPayee($data['payee_id']);
        
        $entry->update($data);

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
            'payee_id' => 'required|integer',
            'geolocation' => 'array',
            'exclude_from_stats' => 'boolean',
        ]);

    }

    /**
     * Creates or checks if a payee with the given ID exists.
     *
     * @param int|string $id The ID of the payee.
     * @return int The ID of the payee.
     */
    private function createOrExistPayee(string|int $id): int
    {
        $payee = Payee::find($id);
        if(!$payee) {
            $payee = new Payee();
            $payee->fill(['name' => $id]);
            $payee->save();
        }

        return $payee->id;
    }

}