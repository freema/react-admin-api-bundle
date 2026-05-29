# Optimistic concurrency control with ETags

Lost updates are an under-reported bug in admin UIs — two users open the same record, both save, the slower save silently wins. The bundle ships an `EtagGenerator` service and a `PreconditionFailedException` that together let resources opt into per-record ETags.

## How it works

1. On every read (`GET /api/{resource}/{id}`) the controller fingerprints the entity (id + Doctrine `#[ORM\Version]` column / `getUpdatedAt()` timestamp) and sets `ETag: W/"<hash>"`.
2. The frontend (`@freema/react-admin-api-bundle` data provider) captures the ETag and replays it as `If-Match` on the next `PUT` / `DELETE` against the same id.
3. The controller compares `If-Match` against the current ETag. Mismatch -> `PreconditionFailedException` (HTTP 412).

The service is **autowired** as `Freema\ReactAdminApiBundle\Concurrency\EtagGenerator`. Integrate from your own controller / event listener if you don't want bundle-wide enforcement:

```php
public function update(string $id, EtagGenerator $etag, EntityManagerInterface $em): JsonResponse
{
    $entity = $em->getRepository(Article::class)->find($id);
    $current = $etag->generate($entity);
    $ifMatch = $request->headers->get('If-Match', '');
    if ($ifMatch === '' || !$etag->matches($ifMatch, $current)) {
        throw new PreconditionFailedException();
    }
    /* … apply update … */
}
```

## Entity contract

`EtagGenerator` reads, in order of preference:

- `getVersion(): int` — recommended (Doctrine `#[ORM\Version]` column).
- `getUpdatedAt(): \DateTimeInterface|null` — fallback when Version is not configured.
- `getId()` — always included so two entities of different ids never collide.

If neither version nor updatedAt is exposed, the ETag is stable per-id but won't change between revisions — pair it with a payload hash via `EtagGenerator::generate($entity, ['payload' => md5(serialize($entity))])` if you really need that.

## Recommended workflow

| Verb | Behaviour |
|---|---|
| `GET /api/{r}/{id}` | Sets `ETag` header. |
| `PUT /api/{r}/{id}` | Requires `If-Match`. On mismatch -> 412. On match -> normal flow. |
| `DELETE /api/{r}/{id}` | Same as `PUT`. |

The frontend data provider stores the last seen ETag per `(resource, id)` and replays it transparently. The user sees the standard 412 react-admin notification ("This record has been modified by another user"); the data provider then refetches to reset the cache.
