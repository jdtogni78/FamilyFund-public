<?php

namespace Tests\Unit\Repositories;

use App\Repositories\BaseRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Discovery-style smoke test that walks every concrete repository in
 * app/Repositories/, instantiates it, and verifies it can answer the
 * baseline contract (model class + searchable fields). This is what keeps
 * each "1-line" boilerplate repo from rotting silently and is the cheapest
 * way to keep this directory's line coverage from collapsing as new
 * repositories are added.
 */
class RepositorySmokeTest extends TestCase
{
    /** @return iterable<string,array{class-string<BaseRepository>}> */
    public static function repositoryClasses(): iterable
    {
        $dir = dirname(__DIR__, 3) . '/app/Repositories';
        foreach (glob($dir . '/*Repository.php') as $file) {
            $name = basename($file, '.php');
            if ($name === 'BaseRepository') {
                continue;
            }
            $fqcn = 'App\\Repositories\\' . $name;
            // Some repositories (e.g. ExchangeHolidayRepository) implement
            // their own interface rather than extending BaseRepository.
            // Skip those — this smoke test covers the BaseRepository contract.
            if (!is_subclass_of($fqcn, BaseRepository::class)) {
                continue;
            }
            yield $name => [$fqcn];
        }
    }

    #[DataProvider('repositoryClasses')]
    public function test_repository_constructs_and_exposes_contract(string $fqcn): void
    {
        $repo = app($fqcn);

        $this->assertInstanceOf(BaseRepository::class, $repo);
        $this->assertNotEmpty($repo->model(), "$fqcn::model() must return a class FQCN");
        $this->assertTrue(
            class_exists($repo->model()),
            "$fqcn::model() returned non-existent class: {$repo->model()}"
        );

        $fields = $repo->getFieldsSearchable();
        $this->assertIsArray($fields, "$fqcn::getFieldsSearchable() must return an array");
    }
}
