<?php

namespace App\Services\DocumentType;

use App\Repositories\DocumentType\DocumentTypeRepository;
use App\Models\DocumentType;
use Illuminate\Support\Facades\DB;
use Throwable;

class DocumentTypeService
{
    protected DocumentTypeRepository $repo;

    public function __construct(DocumentTypeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }

    public function create(array $data): DocumentType
    {
        DB::beginTransaction();
        try {
            $documentType = $this->repo->create($data);
            DB::commit();
            return $documentType;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(DocumentType $documentType, array $data): DocumentType
    {
        DB::beginTransaction();
        try {
            $updated = $this->repo->update($documentType, $data);
            DB::commit();
            return $updated;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(DocumentType $documenttype): void
    {
        DB::beginTransaction();
        try {
            $this->repo->delete($documentType);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getActiveDocumentTypesForCombo()
    {
        return $this->repo->getActiveForCombo();
    }
}
