# Soft delete

The bundle ships `Freema\ReactAdminApiBundle\Interface\SoftDeletableEntityInterface` and `Freema\ReactAdminApiBundle\SoftDeleteTrait` so repositories can convert physical `DELETE` into a `deletedAt` flush without changing the controller path.

## Entity contract

```php
use Doctrine\ORM\Mapping as ORM;
use Freema\ReactAdminApiBundle\Interface\SoftDeletableEntityInterface;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification implements SoftDeletableEntityInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /* … */

    public function getDeletedAt(): ?\DateTimeImmutable { return $this->deletedAt; }
    public function setDeletedAt(?\DateTimeImmutable $deletedAt): void { $this->deletedAt = $deletedAt; }
}
```

## Repository

Swap `DeleteTrait` for `SoftDeleteTrait`:

```php
final class NotificationRepository extends ServiceEntityRepository implements
    DataRepositoryListInterface,
    DataRepositoryDeleteInterface,
    DataRepositoryFindInterface
{
    use ListTrait;
    use FindTrait;
    use SoftDeleteTrait;  // <- delete() now sets deletedAt instead of removing

    public function getFullSearchFields(): array { return ['title']; }

    protected function applyFilters(QueryBuilder $qb, ListDataRequest $req): void
    {
        $this->applySoftDeleteScope($qb, 'e', $req->getFilterValues()['include_deleted'] ?? false);
        parent::applyFilters($qb, $req);
    }
}
```

`applySoftDeleteScope($qb, alias, $includeDeleted)` adds `WHERE alias.deletedAt IS NULL` unless the caller passes `?filter={"include_deleted":1}`. Skip the call entirely if you prefer Doctrine's global filter API.

## Frontend opt-in

The bundle data provider already passes arbitrary filter keys through, so consumers can simply append `include_deleted` to the filter object:

```tsx
<List filter={{ include_deleted: 1 }} actions={<IncludeDeletedToggle />}>
    <Datagrid>{/* … */}</Datagrid>
</List>
```

## Mixed repositories

If a single repository handles entities of which only some are soft-deletable, `SoftDeleteTrait::delete()` falls back to a physical `remove()` for the non-soft-deletable ones. No second trait or branch needed.

## What about restoring?

Restoration is intentionally not part of the trait -- restoring is a domain decision (some apps want auto-purge after N days, others want admin-only restore). Implement a custom controller / repository method when you need it:

```php
public function restore(int $id): void
{
    $entity = $this->find($id);
    if ($entity instanceof SoftDeletableEntityInterface) {
        $entity->setDeletedAt(null);
        $this->getEntityManager()->flush();
    }
}
```
