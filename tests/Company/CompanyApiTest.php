<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Tests\Company;

use Dudev\YclientsPhpSdk\Company\CompanyApi;
use Dudev\YclientsPhpSdk\Transport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CompanyApiTest extends TestCase
{
    #[Test]
    public function listParsesEveryCompanyInTheResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'success' => true,
            'data' => [
                ['id' => 1, 'title' => 'Мамина-Сибиряка', 'city' => 'Екатеринбург', 'phones' => ['+79001112233']],
                ['id' => 2, 'title' => 'Весь город', 'city' => 'Екатеринбург', 'phones' => []],
            ],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));
        $transport = new Transport($httpClient, partnerToken: 'partner', throttle: null);

        $companies = (new CompanyApi($transport))->list();

        self::assertCount(2, $companies);
        self::assertSame(1, $companies[0]->id);
        self::assertSame('Мамина-Сибиряка', $companies[0]->title);
        self::assertSame(['+79001112233'], $companies[0]->phones);
        self::assertSame(2, $companies[1]->id);
    }

    #[Test]
    public function getRequiresAUserToken(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'success' => true,
            'data' => ['id' => 1, 'title' => 'Мамина-Сибиряка'],
            'meta' => [],
        ], JSON_THROW_ON_ERROR)));
        $transport = new Transport($httpClient, partnerToken: 'partner', userToken: 'user', throttle: null);

        $company = (new CompanyApi($transport))->get(1);

        self::assertSame('Мамина-Сибиряка', $company->title);
    }
}
