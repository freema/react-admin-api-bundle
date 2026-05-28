# E2E — Users CRUD round-trip

Validates that the bundle's standard `users` resource (or whichever resource you wire up in your demo app) behaves correctly end-to-end: list, create, edit, delete, plus pagination headers, AbortSignal forwarding, and console hygiene.

Assumes the consumer's admin app is reachable at `http://localhost:5173` and uses the bundle's data provider against `http://localhost:8080/api`.

## Scenario

```
# 1. Land on the list
1. navigate_page  url=http://localhost:5173/#/users
2. wait_for       text="Users"
3. take_snapshot
4. list_network_requests filter="/api/users"
   → first request includes ?page=1&per_page=10&sort_field=id&sort_order=ASC
   → response has Content-Range and X-Content-Range headers

# 2. Create
5. click          selector='a[href="#/users/create"]'
6. wait_for       text="Create"
7. fill_form      fields=[{name:'name',value:'e2e-user'},{name:'email',value:'e2e-user@example.com'}]
8. click          selector='button[type="submit"]'
9. wait_for       text="e2e-user"

# 3. Show detail (records the ETag for later)
10. evaluate_script function=async () => {
      const r = await fetch('/api/users?filter=' + encodeURIComponent(JSON.stringify({name:'e2e-user'})), { credentials: 'include' });
      const json = await r.json();
      const id = json.data[0]?.id;
      const detail = await fetch('/api/users/' + id, { credentials: 'include' });
      return { id, etag: detail.headers.get('ETag') };
    }
    → { id: <number>, etag: matches /^W\/".+"$/ if ETag opt-in is enabled }

# 4. Edit (replay ETag as If-Match if enabled)
11. click         selector='tr:has-text("e2e-user") a[aria-label="Edit"]'
12. fill          selector='input[name="email"]' value='e2e-user+v2@example.com'
13. click         selector='button[type="submit"]'
14. wait_for      text="e2e-user+v2@example.com"

# 5. Delete + verify deletion
15. click         selector='tr:has-text("e2e-user") button[aria-label="Delete"]'
16. click         selector='button:has-text("Confirm")'
17. wait_for      text="Element deleted"
18. evaluate_script function=() => document.body.innerText.includes('e2e-user@example.com')
    → false

# 6. Console hygiene
19. list_console_messages → 0 errors
```

## Notes

- If the consumer hasn't enabled the ETag flow (see `doc/concurrency.md`), step 10's `etag` value will be `null` — the assertion remains useful for catching regressions when the feature is turned on later.
- If the data provider was created with `timeoutMs`, you can simulate a slow endpoint by mocking it in DevTools and verifying the request is aborted within the configured budget.
- Re-run the scenario with `<Suspense fallback={<Loading />}>` wrapped around the `<Admin>` to confirm the fallback shows during the slow fetch (no UI flicker between renders).
