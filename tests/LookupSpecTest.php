<?php

declare(strict_types=1);

namespace PhpSoftBox\DatabaseLookup\Tests;

use InvalidArgumentException;
use PhpSoftBox\DatabaseLookup\LookupSpec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LookupSpec::class)]
final class LookupSpecTest extends TestCase
{
    #[Test]
    public function buildsDefaultWarmupKeyFromCriteriaAndLookupColumn(): void
    {
        $lookup = LookupSpec::forTable('shipment_products')
            ->lookupColumn('product_id')
            ->values([10, 20])
            ->where('shipment_id', 123);

        self::assertSame('shipment_products', $lookup->tableName());
        self::assertSame('product_id', $lookup->lookupColumnName());
        self::assertSame([10, 20], $lookup->lookupValues());
        self::assertSame(['shipment_id' => 123], $lookup->whereCriteria());
        self::assertSame(['shipment_id', 'product_id'], $lookup->warmupKeyColumns());
        self::assertSame(
            ['shipment_id' => 123, 'product_id' => 10],
            $lookup->keyValuesFor(10),
        );
    }

    #[Test]
    public function explicitWarmupKeyColumnsAreNormalizedAndValidated(): void
    {
        $lookup = LookupSpec::forTable('shipment_products')
            ->lookupColumn('product_id')
            ->where('shipment_id', 123)
            ->keyColumns('shipment_id', 'product_id', 'product_id');

        self::assertSame(['shipment_id', 'product_id'], $lookup->warmupKeyColumns());
    }

    #[Test]
    public function explicitWarmupKeyColumnsMustIncludeCriteriaColumns(): void
    {
        $lookup = LookupSpec::forTable('shipment_products')
            ->lookupColumn('product_id')
            ->where('shipment_id', 123)
            ->keyColumns('product_id');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Lookup key columns must include criteria column "shipment_id".');

        $lookup->warmupKeyColumns();
    }

    #[Test]
    public function lookupColumnIsRequiredForKeyOperations(): void
    {
        $lookup = LookupSpec::forTable('products');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Lookup column is not configured.');

        $lookup->warmupKeyColumns();
    }
}
