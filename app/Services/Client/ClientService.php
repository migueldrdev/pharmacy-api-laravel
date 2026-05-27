<?php

namespace App\Services\Client;

use App\Repositories\Client\ClientRepository;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Throwable;

class ClientService
{
    protected ClientRepository $repo;

    public function __construct(ClientRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }

    public function create(array $data): Client
    {
        DB::beginTransaction();
        try {
            $client = $this->repo->create($data);
            DB::commit();
            return $client;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Client $client, array $data): Client
    {
        DB::beginTransaction();
        try {
            $updated = $this->repo->update($client, $data);
            DB::commit();
            return $updated;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(Client $client): void
    {
        DB::beginTransaction();
        try {
            $this->repo->delete($client);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getActiveClientsForCombo()
    {
        return $this->repo->getActiveForCombo();
    }
}
