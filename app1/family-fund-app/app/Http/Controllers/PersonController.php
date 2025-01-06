<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Repositories\PersonRepository;
use App\Repositories\AddressRepository;
use App\Repositories\PhoneRepository;
use App\Repositories\IdDocumentRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;
use Illuminate\Support\Facades\Log;
class PersonController extends AppBaseController
{
    /** @var PersonRepository $personRepository*/
    private $personRepository;
    private $addressRepository;
    private $phoneRepository;
    private $idDocumentRepository;

    public function __construct(PersonRepository $personRepo, AddressRepository $addressRepo, PhoneRepository $phoneRepo, IdDocumentRepository $idDocumentRepo)
    {
        $this->personRepository = $personRepo;
        $this->addressRepository = $addressRepo;
        $this->phoneRepository = $phoneRepo;
        $this->idDocumentRepository = $idDocumentRepo;
    }

    /**
     * Display a listing of the Person.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $people = $this->personRepository->all();

        return view('people.index')
            ->with('people', $people);
    }

    /**
     * Show the form for creating a new Person.
     *
     * @return Response
     */
    public function create()
    {
        return view('people.create');
    }

    /**
     * Store a newly created Person in storage.
     *
     * @param CreatePersonRequest $request
     *
     * @return Response
     */
    public function store(CreatePersonRequest $request)
    {
        $input = $request->all();

        $person = $this->personRepository->create($input);

        // process addresses

        $this->updateSubEntities($person, $request, 'addresses', $this->addressRepository);
        $this->updateSubEntities($person, $request, 'phones', $this->phoneRepository);
        $this->updateSubEntities($person, $request, 'id_documents', $this->idDocumentRepository);

        Flash::success('Person saved successfully.');

        return redirect(route('people.index'));
    }

    /**
     * Display the specified Person.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $person = $this->personRepository->find($id);

        if (empty($person)) {
            Flash::error('Person not found');

            return redirect(route('people.index'));
        }

        return view('people.show')->with('person', $person);
    }

    /**
     * Show the form for editing the specified Person.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $person = $this->personRepository->find($id);

        if (empty($person)) {
            Flash::error('Person not found');

            return redirect(route('people.index'));
        }

        return view('people.edit')->with('person', $person);
    }

    /**
     * Update the specified Person in storage.
     *
     * @param int $id
     * @param UpdatePersonRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatePersonRequest $request)
    {
        Log::info("update person " . $id . " " . json_encode($request->all()));
        $person = $this->personRepository->find($id);

        if (empty($person)) {
            Flash::error('Person not found');

            return redirect(route('people.index'));
        }

        $person = $this->personRepository->update($request->all(), $id);

        $this->updateSubEntities($person, $request, 'addresses', $this->addressRepository);
        $this->updateSubEntities($person, $request, 'phones', $this->phoneRepository);
        $this->updateSubEntities($person, $request, 'id_documents', $this->idDocumentRepository);

        Flash::success('Person updated successfully.');

        return redirect(route('people.index'));
    }

    private function updateSubEntities($person, $request, $what, $repository) {
        // process addresses
        $subEntities = $request->input($what);
        Log::info("processing " . $what);
        if (isset($subEntities)) {
            foreach ($subEntities as $subEntity) {
                // set is_primary to false if not set, or if its null
                $subEntity['is_primary'] = $subEntity['is_primary'] ?? false;
            }
        }
        Log::info("subEntities: " . json_encode($subEntities));

        // delete all subentities that are not in the request
        $existingSubEntities = $person->$what;
        if (isset($existingSubEntities)) {
            foreach ($existingSubEntities as $existingSubEntity) {
                $found = false;
                foreach ($subEntities as $subEntity) {
                Log::info("existingSubEntity: " . $existingSubEntity->id . " subEntity: " . json_encode($subEntity));
                if ($existingSubEntity->id == $subEntity['id']) {
                    $found = true;
                    break;
                    }   
                }
                if (!$found) {
                    $existingSubEntity->delete();
                }
            }
        }
        if (isset($subEntities)) {
            foreach ($subEntities as $subEntity) {
                if (isset($subEntity['id'])) {
                    Log::info("processing " . $what . " " . $subEntity['id']);
                    $subEntity['person_id'] = $person->id;
                    $repository->update($subEntity, $subEntity['id']);
                } else {
                    Log::info("processing " . $what . " " . json_encode($subEntity));
                    $subEntity['person_id'] = $person->id;
                    $repository->create($subEntity);
                }
            }
        }
    }

    /**
     * Remove the specified Person from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $person = $this->personRepository->find($id);

        if (empty($person)) {
            Flash::error('Person not found');

            return redirect(route('people.index'));
        }

        $this->personRepository->delete($id);

        Flash::success('Person deleted successfully.');

        return redirect(route('people.index'));
    }
}
