<?php namespace Tests\Repositories;

use App\Models\AccountReport;
use App\Repositories\AccountReportRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class AccountReportRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var AccountReportRepository
     */
    protected $accountReportRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->accountReportRepo = \App::make(AccountReportRepository::class);
    }
    public function test_create_account_report()
    {
        $accountReport = AccountReport::factory()->make()->toArray();

        $createdAccountReport = $this->accountReportRepo->create($accountReport);

        $createdAccountReport = $createdAccountReport->toArray();
        $this->assertArrayHasKey('id', $createdAccountReport);
        $this->assertNotNull($createdAccountReport['id'], 'Created AccountReport must have id specified');
        $this->assertNotNull(AccountReport::find($createdAccountReport['id']), 'AccountReport with given id must be in DB');
        $this->assertModelData($accountReport, $createdAccountReport);
    }
    public function test_read_account_report()
    {
        $accountReport = AccountReport::factory()->create();

        $dbAccountReport = $this->accountReportRepo->find($accountReport->id);

        $dbAccountReport = $dbAccountReport->toArray();
        $this->assertModelData($accountReport->toArray(), $dbAccountReport);
    }
    public function test_update_account_report()
    {
        $accountReport = AccountReport::factory()->create();
        $fakeAccountReport = AccountReport::factory()->make()->toArray();

        $updatedAccountReport = $this->accountReportRepo->update($fakeAccountReport, $accountReport->id);

        $this->assertModelData($fakeAccountReport, $updatedAccountReport->toArray());
        $dbAccountReport = $this->accountReportRepo->find($accountReport->id);
        $this->assertModelData($fakeAccountReport, $dbAccountReport->toArray());
    }
    public function test_delete_account_report()
    {
        $accountReport = AccountReport::factory()->create();

        $resp = $this->accountReportRepo->delete($accountReport->id);

        $this->assertTrue($resp);
        $this->assertNull(AccountReport::find($accountReport->id), 'AccountReport should not exist in DB');
    }
}
