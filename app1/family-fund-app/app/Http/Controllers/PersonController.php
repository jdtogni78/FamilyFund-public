<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Repositories\PersonRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class PersonController extends AppBaseController
{
    /** @var PersonRepository $personRepository */
    private $personRepository;

    public function __construct(PersonRepository $personRepo)
    {
        $this->personRepository = $personRepo;
    }

    /**
     * Display a listing of the Person.
     */
    public function index(Request $request)
    {
        $persons = $this->personRepository->all();

        return view('persons.index')
            ->with('persons', $persons);
    }

    /**
     * Show the form for creating a new Person.
     */
    public function create()
    {
        return view('persons.create');
    }

    /**
     * Store a newly created Person in storage.
     */
    public function store(CreatePersonRequest $request)
    {
        $input = $request->all();

        $person = $this->personRepository->create($input);

        // Handle phones
        if (isset($input['phones'])) {
            foreach ($input['phones'] as $phoneData) {
                $person->phones()->create($phoneData);
            }
        }

        // Handle addresses
        if (isset($input['addresses'])) {
            foreach ($input['addresses'] as $addressData) {
                $person->addresses()->create($addressData);
            }
        }

        // Handle ID documents
        if (isset($input['documents'])) {
            foreach ($input['documents'] as $documentData) {
                $person->idDocuments()->create($documentData);
            }
        }

        Flash::success('Person saved successfully.');

        return redirect(route('persons.index'));
    }

    /**
     * Display the specified Person.
     */
    public function show($id)
    {
        $person = $this->personRepository->find($id);

        if (empty($person)) {
            Flash::error('Person not found');
            return redirect(route('persons.index'));
        }

        return view('persons.show')->with('person', $person);
    }

    /**
     * Show the form for editing the specified Person.
     */
    public function edit($id)
    {
        $person = $this->personRepository->find($id);

        if (empty($person)) {
            Flash::error('Person not found');
            return redirect(route('persons.index'));
        }

        return view('persons.edit')->with('person', $person);
    }

    /**
     * Update the specified Person in storage.
     */
    public function update($id, UpdatePersonRequest $request)
    {
        $person = $this->personRepository->find($id);

        if (empty($person)) {
            Flash::error('Person not found');
            return redirect(route('persons.index'));
        }

        $input = $request->all();

        $person = $this->personRepository->update($input, $id);

        // Update phones
        if (isset($input['phones'])) {
            $person->phones()->delete(); // Remove existing phones
            foreach ($input['phones'] as $phoneData) {
                $person->phones()->create($phoneData);
            }
        }

        // Update addresses
        if (isset($input['addresses'])) {
            $person->addresses()->delete(); // Remove existing addresses
            foreach ($input['addresses'] as $addressData) {
                $person->addresses()->create($addressData);
            }
        }

        // Update ID documents
        if (isset($input['documents'])) {
            $person->idDocuments()->delete(); // Remove existing documents
            foreach ($input['documents'] as $documentData) {
                $person->idDocuments()->create($documentData);
            }
        }

        Flash::success('Person updated successfully.');

        return redirect(route('persons.index'));
    }

    /**
     * Remove the specified Person from storage.
     */
    public function destroy($id)
    {
        $person = $this->personRepository->find($id);

        if (empty($person)) {
            Flash::error('Person not found');
            return redirect(route('persons.index'));
        }

        $this->personRepository->delete($id);

        Flash::success('Person deleted successfully.');

        return redirect(route('persons.index'));
    }
} 