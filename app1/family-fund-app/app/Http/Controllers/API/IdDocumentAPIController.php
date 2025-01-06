<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateIdDocumentAPIRequest;
use App\Http\Requests\API\UpdateIdDocumentAPIRequest;
use App\Models\IdDocument;
use App\Repositories\IdDocumentRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\IdDocumentResource;
use Response;

/**
 * Class IdDocumentController
 * @package App\Http\Controllers\API
 */

class IdDocumentAPIController extends AppBaseController
{
    /** @var  IdDocumentRepository */
    private $idDocumentRepository;

    public function __construct(IdDocumentRepository $idDocumentRepo)
    {
        $this->idDocumentRepository = $idDocumentRepo;
    }

    /**
     * Display a listing of the IdDocument.
     * GET|HEAD /idDocuments
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $idDocuments = $this->idDocumentRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(IdDocumentResource::collection($idDocuments), 'Id Documents retrieved successfully');
    }

    /**
     * Store a newly created IdDocument in storage.
     * POST /idDocuments
     *
     * @param CreateIdDocumentAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateIdDocumentAPIRequest $request)
    {
        $input = $request->all();

        $idDocument = $this->idDocumentRepository->create($input);

        return $this->sendResponse(new IdDocumentResource($idDocument), 'Id Document saved successfully');
    }

    /**
     * Display the specified IdDocument.
     * GET|HEAD /idDocuments/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var IdDocument $idDocument */
        $idDocument = $this->idDocumentRepository->find($id);

        if (empty($idDocument)) {
            return $this->sendError('Id Document not found');
        }

        return $this->sendResponse(new IdDocumentResource($idDocument), 'Id Document retrieved successfully');
    }

    /**
     * Update the specified IdDocument in storage.
     * PUT/PATCH /idDocuments/{id}
     *
     * @param int $id
     * @param UpdateIdDocumentAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateIdDocumentAPIRequest $request)
    {
        $input = $request->all();

        /** @var IdDocument $idDocument */
        $idDocument = $this->idDocumentRepository->find($id);

        if (empty($idDocument)) {
            return $this->sendError('Id Document not found');
        }

        $idDocument = $this->idDocumentRepository->update($input, $id);

        return $this->sendResponse(new IdDocumentResource($idDocument), 'IdDocument updated successfully');
    }

    /**
     * Remove the specified IdDocument from storage.
     * DELETE /idDocuments/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var IdDocument $idDocument */
        $idDocument = $this->idDocumentRepository->find($id);

        if (empty($idDocument)) {
            return $this->sendError('Id Document not found');
        }

        $idDocument->delete();

        return $this->sendSuccess('Id Document deleted successfully');
    }
}
