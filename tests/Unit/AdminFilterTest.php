<?php

namespace Tests\Unit;

use App\Support\AdminFilter;
use PHPUnit\Framework\TestCase;

class AdminFilterTest extends TestCase
{
    /**
     * @dataProvider likeAliasProvider
     */
    public function testItNormalizesSupportedLikeAliases(string $condition): void
    {
        $this->assertSame('like', AdminFilter::normalizeCondition($condition));
    }

    public function likeAliasProvider(): array
    {
        return [
            'canonical API value' => ['like'],
            'legacy Vietnamese UI value' => ['Tương đối'],
            'legacy Chinese UI value' => ['模糊'],
        ];
    }

    public function testItEscapesSqlLikeWildcardsAndEscapeCharacters(): void
    {
        $this->assertSame(
            '%50\\%\\_off\\\\test%',
            AdminFilter::prepareValue('like', '50%_off\\test')
        );
    }

    public function testItDoesNotChangeValuesForOtherOperators(): void
    {
        $this->assertSame(25, AdminFilter::prepareValue('>=', 25));
    }
}
