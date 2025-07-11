<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Facades\Log;
use Budgetcontrol\Library\Model\Saving;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Entry\Controller\Controller;
use Budgetcontrol\Library\Entity\Entry as EntryType;
use Dotenv\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SavingController extends Controller {

    public function get(Request $request, Response $response, $argv): Response
    {
        $page = $request->getQueryParams()['page'] ?? 1;
        $per_page = $request->getQueryParams()['per_page'] ?? 10;
        $planned = (bool) @$request->getQueryParams()['planned'] ?? null;

        $wsId = $argv['wsid'];

        try {
            $goalId = $this->getIdOfGoal($argv['goalUuid']);
        } catch (ValidationException $e) {
            Log::warning($e->getMessage());
            return response(
                ['error' => $e->getMessage()],
                404
            );
        }

        $entries = Saving::WithRelations()->where('workspace_id', $wsId)
        ->where('goal_id', $goalId)
        ->where('type', EntryType::saving->value)
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
        $entries = Saving::WithRelations()->where('workspace_id', $wsId)->where('uuid', $entryId)->get();

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

        try {
            $data['goal_id'] = $this->getIdOfGoal($data['goal_id']);
        } catch (ValidationException $e) {
            Log::warning($e->getMessage());
            return response(
                ['error' => $e->getMessage()],
                404
            );
        }

        $data['workspace_id'] = $wsId;
        $data['planned'] = $this->isPlanned($data['date_time']);
        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4();
        $data['type'] = EntryType::saving->value;
        
        $saving = new Saving();
        $saving->fill($data);
        $saving->save();

        if(!empty($data['labels'])) {
            foreach($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $saving->labels()->attach($label);
            }
        }

        return response(
            $saving->toArray(),
            201
        );

    }

    public function update(Request $request, Response $response, $argv): Response
    {
        $this->validate($request);
        $this->workspaceId = $argv['wsid'];

        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];
        $entries = Saving::where('workspace_id', $wsId)->where('uuid', $entryId)->get();

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

        Validator::make($request, [
            'date_time' => 'required|date',
            'goal_id' => 'required|integer',
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

    /**
     * Retrieves the database ID of a goal based on its UUID.
     *
     * @param string $goalUuid The UUID of the goal to look up
     * @return int The database ID of the goal
     * @throws \Exception If the goal is not found
     */
    private function getIdOfGoal(string $goalUuid): int
    {
        $goal = \Budgetcontrol\Library\Model\Goal::where('uuid', $goalUuid)->first();
        if (!$goal) {
            throw new ValidationException('Goal not found');
        }
        return $goal->id;
    }
}