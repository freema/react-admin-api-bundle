<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Freema\ReactAdminApiBundle\Tests\Functional\App\AccessListener;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Entity\Account;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Entity\Note;
use Freema\ReactAdminApiBundle\Tests\Functional\App\TestKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Attacks through HTTP against a real kernel: the access event must guard
 * every endpoint, and list filters and sorting must not reach columns the
 * resource does not expose.
 */
class ResourceSecurityTest extends WebTestCase
{
    private KernelBrowser $client;
    private int $accountId;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        AccessListener::$deny = [];
        AccessListener::$seen = [];

        $this->client = static::createClient();
        $this->client->disableReboot();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());

        $account = new Account();
        $account->name = 'Alice';
        $account->email = 'alice@example.com';
        $account->password = 's3cret-hash';
        $em->persist($account);

        $note = new Note();
        $note->title = 'First note';
        $note->secret = 'internal';
        $note->account = $account;
        $em->persist($note);
        $em->flush();

        $this->accountId = (int) $account->id;
    }

    /**
     * @return iterable<string, array{string, string, string, ?string}>
     */
    public static function endpoints(): iterable
    {
        yield 'list' => ['GET', '/api/accounts', 'accounts:list', null];
        yield 'get' => ['GET', '/api/accounts/{id}', 'accounts:get', null];
        yield 'create' => ['POST', '/api/accounts', 'accounts:create', '{"name":"Mallory","email":"m@example.com"}'];
        yield 'update' => ['PUT', '/api/accounts/{id}', 'accounts:update', '{"name":"Owned","email":"x@example.com"}'];
        yield 'delete' => ['DELETE', '/api/accounts/{id}', 'accounts:delete', null];
        yield 'deleteMany' => ['DELETE', '/api/accounts', 'accounts:deleteMany', '{"ids":[{id}]}'];
        yield 'related list, parent denied' => ['GET', '/api/accounts/{id}/notes?sort_field=id&sort_order=ASC', 'accounts:get', null];
        yield 'related list, related resource denied' => ['GET', '/api/accounts/{id}/notes?sort_field=id&sort_order=ASC', 'notes:list', null];
    }

    #[DataProvider('endpoints')]
    public function test_a_denying_access_listener_blocks_the_endpoint(string $method, string $path, string $deny, ?string $body): void
    {
        AccessListener::$deny = [$deny];

        $this->request($method, $path, $body);

        $this->assertSame(403, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        $this->assertAccountUnchanged();
        $this->assertSame(1, $this->countAccounts(), 'no account may be created or deleted');
    }

    #[DataProvider('endpoints')]
    public function test_an_allowing_access_listener_lets_the_endpoint_work(string $method, string $path, string $deny, ?string $body): void
    {
        $this->request($method, $path, $body);

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertGreaterThanOrEqual(200, $status, (string) $this->client->getResponse()->getContent());
        $this->assertLessThan(300, $status, (string) $this->client->getResponse()->getContent());
        $this->assertNotEmpty(AccessListener::$seen);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hiddenColumnQueries(): iterable
    {
        yield 'filter on a column the DTO does not expose' => ['/api/accounts?filter='.rawurlencode('{"password":"s3cret"}')];
        yield 'filter with DQL in the key' => ['/api/accounts?filter='.rawurlencode('{"name LIKE :x OR e.password":"s"}')];
        yield 'sort on a column the DTO does not expose' => ['/api/accounts?sort_field=password&sort_order=ASC'];
        yield 'sort with DQL in the field' => ['/api/accounts?sort_field='.rawurlencode('id, e.password').'&sort_order=ASC'];
        yield 'sort order that is not ASC or DESC' => ['/api/accounts?sort_field=name&sort_order='.rawurlencode('ASC, e.password')];
        yield 'related filter on an internal column' => ['/api/accounts/{id}/notes?sort_field=id&sort_order=ASC&filter='.rawurlencode('{"secret":"int"}')];
        yield 'related sort on an internal column' => ['/api/accounts/{id}/notes?sort_field=secret&sort_order=ASC'];
    }

    #[DataProvider('hiddenColumnQueries')]
    public function test_a_list_cannot_filter_or_sort_on_hidden_columns(string $path): void
    {
        $this->request('GET', $path);

        $this->assertSame(400, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('Alice', (string) $this->client->getResponse()->getContent());
    }

    public function test_exposed_fields_still_filter_and_sort(): void
    {
        $this->request('GET', '/api/accounts?sort_field=name&sort_order=desc&filter='.rawurlencode('{"name":"Ali","q":"alice"}'));
        $this->assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Alice', (string) $this->client->getResponse()->getContent());

        $this->request('GET', '/api/accounts?filter='.rawurlencode('{"id":[{id}]}'));
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Alice', (string) $this->client->getResponse()->getContent());

        $this->request('GET', '/api/accounts/{id}/notes?sort_field=title&sort_order=ASC&filter='.rawurlencode('{"title":"First"}'));
        $this->assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        $this->assertStringContainsString('First note', (string) $this->client->getResponse()->getContent());
    }

    public function test_a_page_is_capped(): void
    {
        $this->request('GET', '/api/accounts?page=1&per_page=1000000&sort_field=id&sort_order=ASC');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
    }

    private function request(string $method, string $path, ?string $body = null): void
    {
        $path = str_replace('{id}', (string) $this->accountId, $path);
        $body = $body === null ? null : str_replace('{id}', (string) $this->accountId, $body);
        $this->client->request($method, $path, server: ['CONTENT_TYPE' => 'application/json'], content: $body);
    }

    private function assertAccountUnchanged(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        $em->clear();
        $account = $em->find(Account::class, $this->accountId);
        $this->assertNotNull($account, 'the account must not be deleted');
        $this->assertSame('Alice', $account->name, 'the account must not be updated');
    }

    private function countAccounts(): int
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        return (int) $em->createQuery('SELECT COUNT(a.id) FROM '.Account::class.' a')->getSingleScalarResult();
    }
}
