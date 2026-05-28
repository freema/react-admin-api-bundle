# Filter operators

The bundle ships `Freema\ReactAdminApiBundle\Request\FilterOperatorParser`, a tiny helper that interprets react-admin filter keys with operator suffixes (`createdAt_gte`, `email_contains`, `id_in`, …) and returns a `(field, operator)` tuple your `ListTrait` consumer can switch over.

## Why a parser instead of a magic in-built filter

We deliberately kept the operator translation **opt-in**:
- bundle consumers usually want a whitelist of filterable fields (security: no `password_eq` etc.),
- the QueryBuilder dialect varies across repositories (joins, aliases, custom filter callbacks),
- Doctrine query parameter binding has subtle quirks (`IN (:p)` needs an array, `BETWEEN` needs two params, …) that we don't want to monkey-patch into the trait globally.

So the bundle exposes the parser as a service and gives you a recipe for plugging it into your own list method.

## Supported suffixes

| Suffix | Operator key | Typical SQL fragment |
|---|---|---|
| `_gt` | `gt` | `field > :p` |
| `_gte` | `gte` | `field >= :p` |
| `_lt` | `lt` | `field < :p` |
| `_lte` | `lte` | `field <= :p` |
| `_neq` | `neq` | `field != :p` |
| `_in` | `in` | `field IN (:p)` |
| `_nin` | `nin` | `field NOT IN (:p)` |
| `_between` | `between` | `field BETWEEN :pa AND :pb` |
| `_isNull` | `isNull` | `field IS NULL` |
| `_isNotNull` | `isNotNull` | `field IS NOT NULL` |
| `_contains` | `contains` | `field LIKE '%value%'` |
| `_startsWith` | `startsWith` | `field LIKE 'value%'` |
| `_endsWith` | `endsWith` | `field LIKE '%value'` |
| _(none)_ | `eq` | `field = :p` |

The parser scans suffixes longest-first so `createdAt_gte` is never accidentally matched as `_gt`, and `deletedAt_isNotNull` is never matched as `_isNull`.

## Recipe: integrate with your repository

```php
final class UserRepository extends ServiceEntityRepository implements DataRepositoryListInterface
{
    use ListTrait;

    public function __construct(
        ManagerRegistry $registry,
        private readonly FilterOperatorParser $operatorParser,
    ) {
        parent::__construct($registry, User::class);
    }

    public function getFullSearchFields(): array { return ['name', 'email']; }

    /** Whitelist of fields filter operators may target. */
    private const FILTERABLE = ['createdAt', 'email', 'role', 'id', 'age'];

    protected function applyFilters(QueryBuilder $qb, ListDataRequest $req): void
    {
        parent::applyFilters($qb, $req);   // keep bundle defaults (q, IN by id, etc.)

        foreach ($req->getFilterValues() as $key => $value) {
            [$field, $op] = $this->operatorParser->parse($key);
            if (!in_array($field, self::FILTERABLE, true)) {
                continue;  // ignore unsupported targets
            }
            match ($op) {
                'gt'         => $qb->andWhere("e.$field > :p_$key")->setParameter("p_$key", $value),
                'gte'        => $qb->andWhere("e.$field >= :p_$key")->setParameter("p_$key", $value),
                'lt'         => $qb->andWhere("e.$field < :p_$key")->setParameter("p_$key", $value),
                'lte'        => $qb->andWhere("e.$field <= :p_$key")->setParameter("p_$key", $value),
                'neq'        => $qb->andWhere("e.$field != :p_$key")->setParameter("p_$key", $value),
                'in'         => $qb->andWhere("e.$field IN (:p_$key)")->setParameter("p_$key", (array) $value),
                'nin'        => $qb->andWhere("e.$field NOT IN (:p_$key)")->setParameter("p_$key", (array) $value),
                'between'    => $qb
                    ->andWhere("e.$field BETWEEN :p_{$key}_a AND :p_{$key}_b")
                    ->setParameter("p_{$key}_a", $value[0])
                    ->setParameter("p_{$key}_b", $value[1]),
                'isNull'     => $qb->andWhere("e.$field IS NULL"),
                'isNotNull'  => $qb->andWhere("e.$field IS NOT NULL"),
                'contains'   => $qb->andWhere("e.$field LIKE :p_$key")->setParameter("p_$key", '%'.$value.'%'),
                'startsWith' => $qb->andWhere("e.$field LIKE :p_$key")->setParameter("p_$key", $value.'%'),
                'endsWith'   => $qb->andWhere("e.$field LIKE :p_$key")->setParameter("p_$key", '%'.$value),
                default      => $qb->andWhere("e.$field = :p_$key")->setParameter("p_$key", $value),
            };
        }
    }
}
```

## Frontend usage

react-admin's `<DateInput source="createdAt_gte" />`, `<NumberInput source="age_between" />` and friends emit the filter keys exactly as the parser expects. No extra glue required.

```tsx
<List filters={[
    <DateInput source="createdAt_gte" label="Created from" alwaysOn />,
    <NumberInput source="age_between" label="Age range" />,
    <SelectInput source="role_in" choices={ROLES} />,
]}>
    <Datagrid>{/* … */}</Datagrid>
</List>
```

Numeric `_between` expects an array (`[18, 65]`), so wrap the inputs accordingly (RA's `<NumberInput multiple>` or a custom component).

## Security notes

- **Always** maintain a whitelist of filterable fields. The parser will gladly extract `password_eq` from a query string -- it's the repository's job to ignore it.
- For `IN` / `NIN`, cast incoming values to arrays before binding.
- For text operators, do not splice `%` into raw SQL -- always go through parameter binding.
