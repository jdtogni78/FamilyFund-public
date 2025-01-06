<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreatePersonAPIRequest;
use App\Http\Requests\API\UpdatePersonAPIRequest;
use App\Models\Person;
use App\Repositories\PersonRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\PersonResource;
use Response;

/**
 * Class PersonController
 * @package App\Http\Controllers\API
 */

class PersonAPIController extends AppBaseController
{
    /** @var  PersonRepository */
    private $personRepository;

    public function __construct(PersonRepository $personRepo)
    {
        $this->personRepository = $personRepo;
    }

    /**
     * Display a listing of the Person.
     * GET|HEAD /people
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $people = $this->personRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(PersonResource::collection($people), 'People retrieved successfully');
    }

    /**
     * Store a newly created Person in storage.
     * POST /people
     *
     * @param CreatePersonAPIRequest $request
     *
     * @return Response
     */
    public function store(CreatePersonAPIRequest $request)
    {
        $input = $request->all();

        $person = $this->personRepository->create($input);

        return $this->sendResponse(new PersonResource($person), 'Person saved successfully');
    }

    /**
     * Display the specified Person.
     * GET|HEAD /people/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var Person $person */
        $person = $this->personRepository->find($id);

        if (empty($person)) {
            return $this->sendError('Person not found');
        }

        return $this->sendResponse(new PersonResource($person), 'Person retrieved successfully');
    }

    /**
     * Update the specified Person in storage.
     * PUT/PATCH /people/{id}
     *
     * @param int $id
     * @param UpdatePersonAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatePersonAPIRequest $request)
    {
        $input = $request->all();

        /** @var Person $person */
        $person = $this->personRepository->find($id);

        if (empty($person)) {
            return $this->sendError('Person not found');
        }

        $person = $this->personRepository->update($input, $id);

        return $this->sendResponse(new PersonResource($person), 'Person updated successfully');
    }

    /**
     * Remove the specified Person from storage.
     * DELETE /people/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var Person $person */
        $person = $this->personRepository->find($id);

        if (empty($person)) {
            return $this->sendError('Person not found');
        }

        $person->delete();

        return $this->sendSuccess('Person deleted successfully');
    }
}
