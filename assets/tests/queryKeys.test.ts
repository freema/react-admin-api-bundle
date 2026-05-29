import { test } from 'node:test';
import { strict as assert } from 'node:assert';
import { getResourceQueryKeys } from '../src/queryKeys';

test('getResourceQueryKeys produces the expected hierarchy', () => {
    const keys = getResourceQueryKeys('users');
    assert.deepEqual(keys.all, ['users']);
    assert.deepEqual(keys.lists, ['users', 'getList']);
    assert.deepEqual(keys.list({ filter: { active: true } }), [
        'users',
        'getList',
        { filter: { active: true } },
    ]);
    assert.deepEqual(keys.details, ['users', 'getOne']);
    assert.deepEqual(keys.detail(42), ['users', 'getOne', { id: 42 }]);
    assert.deepEqual(keys.detail('abc'), ['users', 'getOne', { id: 'abc' }]);
    assert.deepEqual(keys.many, ['users', 'getMany']);
    assert.deepEqual(keys.manyReferences, ['users', 'getManyReference']);
    assert.deepEqual(keys.manyReference('postId', 7), [
        'users',
        'getManyReference',
        { target: 'postId', id: 7 },
    ]);
});

test('getResourceQueryKeys keys for different resources do not collide', () => {
    const users = getResourceQueryKeys('users');
    const posts = getResourceQueryKeys('posts');
    assert.notDeepEqual(users.lists, posts.lists);
    assert.notDeepEqual(users.detail(1), posts.detail(1));
});
