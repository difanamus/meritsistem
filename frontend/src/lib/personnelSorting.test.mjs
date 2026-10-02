import { test } from 'node:test'
import assert from 'node:assert/strict'
import { personnelColumns, personnelSortParams } from './personnelSorting.ts'

test('each header uses its correct server key and sensible initial direction', () => {
  assert.deepEqual(personnelColumns.map((column) => column.key), ['nama', 'jabatan', 'satker', 'jumlah_kualifikasi', 'durasi_pengalaman', 'jumlah_operasi'])
  for (const column of personnelColumns.slice(1)) {
    const params = personnelSortParams(new URLSearchParams('page=4&search=DEMO&arsip=1'), column.key, column.defaultDirection, true)
    assert.equal(params.get('direction'), column.defaultDirection)
    assert.equal(params.get('sort'), column.key)
    assert.equal(params.has('page'), false)
    assert.equal(params.get('search'), 'DEMO')
    assert.equal(params.get('arsip'), '1')
  }
})

test('repeated header clicks reverse direction including implicit default name sorting', () => {
  const original = new URLSearchParams('page=2&operasi_wilayah=Papua')
  const first = personnelSortParams(original, 'nama', 'asc', true)
  assert.equal(first.get('direction'), 'desc')
  const second = personnelSortParams(first, 'nama', 'asc', true)
  assert.equal(second.get('direction'), 'asc')
  assert.equal(second.get('operasi_wilayah'), 'Papua')
  assert.equal(original.get('page'), '2')
})

test('dropdown selection applies default direction without toggling and resets page', () => {
  const params = personnelSortParams(new URLSearchParams('sort=satker&direction=desc&page=3&status=aktif'), 'satker', 'asc')
  assert.equal(params.get('direction'), 'asc')
  assert.equal(params.has('page'), false)
  assert.equal(params.get('status'), 'aktif')
})
