import { test } from 'node:test';
import { strict as assert } from 'node:assert';
import { containsFiles, toFormData } from '../src/upload/multipart';

const makeFile = (name = 'pic.png'): File =>
    new File([new Uint8Array([1, 2, 3])], name, { type: 'image/png' });

test('containsFiles detects plain File instances', () => {
    assert.equal(containsFiles({ name: 'x', avatar: makeFile() }), true);
});

test('containsFiles detects react-admin {rawFile} shape', () => {
    assert.equal(containsFiles({ avatar: { rawFile: makeFile(), title: 't' } }), true);
});

test('containsFiles returns false for plain data', () => {
    assert.equal(containsFiles({ name: 'x', age: 30, tags: ['a'] }), false);
});

test('toFormData appends scalars, files, and JSON-encoded nested objects', () => {
    const fd = toFormData({
        name: 'avatar',
        avatar: { rawFile: makeFile('a.png'), title: 'Cover' },
        size: 1024,
        meta: { tags: ['x', 'y'] },
        flag: true,
    });
    assert.equal(fd.get('name'), 'avatar');
    assert.ok(fd.get('avatar') instanceof File);
    assert.equal(fd.get('avatar__title'), 'Cover');
    assert.equal(fd.get('size'), '1024');
    assert.equal(fd.get('meta'), JSON.stringify({ tags: ['x', 'y'] }));
    assert.equal(fd.get('flag'), 'true');
});
