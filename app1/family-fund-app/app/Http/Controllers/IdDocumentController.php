<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateIdDocumentRequest;
use App\Http\Requests\UpdateIdDocumentRequest;
use App\Repositories\IdDocumentRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class IdDocumentController extends AppBaseController
{
    /** @var IdDocumentRepository $idDocumentRepository*/
    private $idDocumentRepository;

    public function __construct(IdDocumentRepository $idDocumentRepo)
    {
        $this->idDocumentRepository = $idDocumentRepo;
    }

    /**
     * Display a listing of the IdDocument.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $idDocuments = $this->idDocumentRepository->all();

        return view('id_documents.index')
            ->with('idDocuments', $idDocuments);
    }

    /**
     * Show the form for creating a new IdDocument.
     *
     * @return Response
     */
    public function create()
    {
        return view('id_documents.create');
    }

    /**
     * Store a newly created IdDocument in storage.
     *
     * @param CreateIdDocumentRequest $request
     *
     * @return Response
     */
    public function store(CreateIdDocumentRequest $request)
    {
        $input = $request->all();

        $idDocument = $this->idDocumentRepository->create($input);

        Flash::success('Id Document saved successfully.');

        return redirect(route('idDocuments.index'));
    }

    /**
     * Display the specified IdDocument.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $idDocument = $this->idDocumentRepository->find($id);

        if (empty($idDocument)) {
            Flash::error('Id Document not found');

            return redirect(route('idDocuments.index'));
        }

        return view('id_documents.show')->with('idDocument', $idDocument);
    }

    /**
     * Show the form for editing the specified IdDocument.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $idDocument = $this->idDocumentRepository->find($id);

        if (empty($idDocument)) {
            Flash::error('Id Document not found');

            return redirect(route('idDocuments.index'));
        }

        return view('id_documents.edit')->with('idDocument', $idDocument);
    }

    /**
     * Update the specified IdDocument in storage.
     *
     * @param int $id
     * @param UpdateIdDocumentRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateIdDocumentRequest $request)
    {
        $idDocument = $this->idDocumentRepository->find($id);

        if (empty($idDocument)) {
            Flash::error('Id Document not found');

            return redirect(route('idDocuments.index'));
        }

        $idDocument = $this->idDocumentRepository->update($request->all(), $id);

        Flash::success('Id Document updated successfully.');

        return redirect(route('idDocuments.index'));
    }

    /**
     * Remove the specified IdDocument from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $idDocument = $this->idDocumentRepository->find($id);

        if (empty($idDocument)) {
            Flash::error('Id Document not found');

            return redirect(route('idDocuments.index'));
        }

        $this->idDocumentRepository->delete($id);

        Flash::success('Id Document deleted successfully.');

        return redirect(route('idDocuments.index'));
    }
}
