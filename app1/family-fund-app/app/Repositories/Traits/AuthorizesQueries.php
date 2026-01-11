<?php

namespace App\Repositories\Traits;

use App\Services\AuthorizationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait AuthorizesQueries
{
    protected bool $useAuthorization = false;
    protected ?AuthorizationService $authorizationService = null;

    /**
     * Enable authorization filtering for this repository instance.
     *
     * @return static
     */
    public function withAuthorization(): static
    {
        $clone = clone $this;
        $clone->useAuthorization = true;
        $clone->authorizationService = new AuthorizationService(Auth::user());
        return $clone;
    }

    /**
     * Disable authorization filtering for this repository instance.
     *
     * @return static
     */
    public function withoutAuthorization(): static
    {
        $clone = clone $this;
        $clone->useAuthorization = false;
        $clone->authorizationService = null;
        return $clone;
    }

    /**
     * Apply authorization scope to the query.
     * Override this method in child repositories to implement specific scoping logic.
     */
    protected function applyAuthorizationScope(Builder $query): Builder
    {
        // Default implementation - no filtering
        // Child repositories should override this method
        return $query;
    }

    /**
     * Override allQuery to apply authorization scope.
     */
    public function allQuery($search = [], $skip = null, $limit = null)
    {
        $query = parent::allQuery($search, $skip, $limit);

        if ($this->useAuthorization && $this->authorizationService) {
            $query = $this->applyAuthorizationScope($query);
        }

        return $query;
    }

    /**
     * Override find to apply authorization scope.
     */
    public function find($id, $columns = ['*'])
    {
        $query = $this->model->newQuery();

        if ($this->useAuthorization && $this->authorizationService) {
            $query = $this->applyAuthorizationScope($query);
        }

        return $query->find($id, $columns);
    }

    /**
     * Get the authorization service.
     */
    protected function getAuthorizationService(): ?AuthorizationService
    {
        return $this->authorizationService;
    }

    /**
     * Check if authorization is enabled.
     */
    public function isAuthorizationEnabled(): bool
    {
        return $this->useAuthorization;
    }
}
