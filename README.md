# PhpSoftBox Database Lookup

Общий объект описания keyed lookup для компонентов, которым нужно согласованно
читать строки по явным идентификаторам.

```php
use PhpSoftBox\DatabaseLookup\LookupSpec;

$lookup = LookupSpec::forTable('shipment_products')
    ->lookupColumn('product_id')
    ->values([10, 20, 30])
    ->where('shipment_id', 123);
```

Если warmup key не задан явно, он строится из fixed `where`-колонок и lookup
колонки: `shipment_id + product_id`. Явный ключ можно задать через
`keyColumns('shipment_id', 'product_id')`.

## Переиспользуемые lookup-спецификации

`LookupSpec` — immutable value object. Он не рассчитан на наследование: fluent-методы
возвращают новый объект спецификации, а сам объект должен оставаться простым переносимым
контрактом между Database, Validator, ORM и другими слоями.

Если спецификацию нужно описать заранее и переиспользовать в нескольких местах, лучше
оформить это отдельной фабрикой или именованными методами в доменном коде:

```php
use PhpSoftBox\DatabaseLookup\LookupSpec;

final class ShipmentProductLookups
{
    public static function productsForShipment(int $shipmentId): LookupSpec
    {
        return LookupSpec::forTable('shipment_products')
            ->lookupColumn('product_id')
            ->where('shipment_id', $shipmentId);
    }
}
```

Такой подход оставляет `LookupSpec` закрытым и предсказуемым value object, но позволяет
централизовать таблицы, колонки и scoped-условия.

## Лицензия
MIT
