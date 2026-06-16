<?php

declare(strict_types=1);

namespace PhpSoftBox\DatabaseLookup;

use InvalidArgumentException;

use function array_key_exists;
use function array_keys;
use function array_values;
use function implode;
use function in_array;
use function trim;

final readonly class LookupSpec
{
    /**
     * @param list<mixed> $values
     * @param array<string, mixed> $criteria
     * @param list<string> $keyColumns
     */
    private function __construct(
        private string $table,
        private ?string $lookupColumn = null,
        private array $values = [],
        private array $criteria = [],
        private array $keyColumns = [],
    ) {
        $this->assertName($this->table, 'table');
    }

    public static function forTable(string $table): self
    {
        return new self(self::normalizeName($table, 'table'));
    }

    public function lookupColumn(string $column): self
    {
        return new self(
            $this->table,
            self::normalizeName($column, 'lookup column'),
            $this->values,
            $this->criteria,
            $this->keyColumns,
        );
    }

    /**
     * @param array<mixed> $values
     */
    public function values(array $values): self
    {
        return new self(
            $this->table,
            $this->lookupColumn,
            array_values($values),
            $this->criteria,
            $this->keyColumns,
        );
    }

    public function value(mixed $value): self
    {
        return $this->values([$value]);
    }

    public function where(string $column, mixed $value): self
    {
        $criteria                                               = $this->criteria;
        $criteria[self::normalizeName($column, 'where column')] = $value;

        return new self(
            $this->table,
            $this->lookupColumn,
            $this->values,
            $criteria,
            $this->keyColumns,
        );
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function whereAll(array $criteria): self
    {
        $normalized = $this->criteria;
        foreach ($criteria as $column => $value) {
            $normalized[self::normalizeName((string) $column, 'where column')] = $value;
        }

        return new self(
            $this->table,
            $this->lookupColumn,
            $this->values,
            $normalized,
            $this->keyColumns,
        );
    }

    public function keyColumns(string ...$columns): self
    {
        return new self(
            $this->table,
            $this->lookupColumn,
            $this->values,
            $this->criteria,
            self::uniqueColumns($columns),
        );
    }

    public function tableName(): string
    {
        return $this->table;
    }

    public function lookupColumnName(): string
    {
        if ($this->lookupColumn === null) {
            throw new InvalidArgumentException('Lookup column is not configured.');
        }

        return $this->lookupColumn;
    }

    /**
     * @return list<mixed>
     */
    public function lookupValues(): array
    {
        return $this->values;
    }

    /**
     * @return array<string, mixed>
     */
    public function whereCriteria(): array
    {
        return $this->criteria;
    }

    /**
     * @return list<string>
     */
    public function warmupKeyColumns(): array
    {
        $lookupColumn = $this->lookupColumnName();

        if ($this->keyColumns === []) {
            return self::uniqueColumns([...array_keys($this->criteria), $lookupColumn]);
        }

        if (!in_array($lookupColumn, $this->keyColumns, true)) {
            throw new InvalidArgumentException(
                'Lookup key columns must include lookup column "' . $lookupColumn . '".',
            );
        }

        foreach (array_keys($this->criteria) as $criteriaColumn) {
            if (!in_array((string) $criteriaColumn, $this->keyColumns, true)) {
                throw new InvalidArgumentException(
                    'Lookup key columns must include criteria column "' . $criteriaColumn . '".',
                );
            }
        }

        return $this->keyColumns;
    }

    /**
     * @return array<string, mixed>
     */
    public function keyValuesFor(mixed $lookupValue): array
    {
        $lookupColumn = $this->lookupColumnName();
        $key          = [];

        foreach ($this->warmupKeyColumns() as $keyColumn) {
            if ($keyColumn === $lookupColumn) {
                $key[$keyColumn] = $lookupValue;
                continue;
            }

            if (array_key_exists($keyColumn, $this->criteria)) {
                $key[$keyColumn] = $this->criteria[$keyColumn];
                continue;
            }

            throw new InvalidArgumentException(
                'Lookup key column "' . $keyColumn . '" is not available. Use one of: '
                . implode(', ', array_values([$lookupColumn, ...array_keys($this->criteria)])),
            );
        }

        return $key;
    }

    private function assertName(string $name, string $kind): void
    {
        self::normalizeName($name, $kind);
    }

    private static function normalizeName(string $name, string $kind): string
    {
        $normalized = trim($name);
        if ($normalized === '') {
            throw new InvalidArgumentException('Lookup ' . $kind . ' must not be empty.');
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $columns
     * @return list<string>
     */
    private static function uniqueColumns(array $columns): array
    {
        $unique = [];
        foreach ($columns as $column) {
            $column = self::normalizeName($column, 'key column');
            if (!in_array($column, $unique, true)) {
                $unique[] = $column;
            }
        }

        return $unique;
    }
}
