import { test } from 'node:test'
import assert from 'node:assert/strict'
import { ApiError, apiRequest, downloadDocument } from './api.ts'

function session(t) {
  t.mock.method(globalThis, 'fetch')
  const previous = globalThis.localStorage
  globalThis.localStorage = { getItem: () => 'demo-token' }
  t.after(() => { globalThis.localStorage = previous })
}

test('API client refuses malformed success responses rather than reporting saved data', async (t) => {
  session(t)
  fetch.mock.mockImplementation(async () => new Response('<html>PHP warning</html>', { status: 200 }))
  await assert.rejects(apiRequest('/kualifikasi/1'), (error) => error instanceof ApiError && error.message === 'Respons layanan tidak valid. Periksa konfigurasi backend.')
})

test('API client preserves structured validation errors', async (t) => {
  session(t)
  fetch.mock.mockImplementation(async () => Response.json({ message: 'Tanggal tidak valid.', errors: { tanggal_selesai: ['Periode terbalik.'] } }, { status: 422 }))
  await assert.rejects(apiRequest('/kualifikasi/1'), (error) => error instanceof ApiError && error.status === 422 && error.errors.tanggal_selesai[0] === 'Periode terbalik.')
})

test('document request sends bearer token in headers and surfaces forbidden errors', async (t) => {
  session(t)
  fetch.mock.mockImplementation(async (url, options) => {
    assert.equal(url.endsWith('/kualifikasi/1/dokumen'), true)
    assert.equal(url.includes('demo-token'), false)
    assert.equal(options.headers.get('Authorization'), 'Bearer demo-token')
    assert.equal(options.headers.get('Accept'), 'application/json')
    return Response.json({ message: 'Akses tidak diberikan.' }, { status: 403 })
  })
  await assert.rejects(downloadDocument('/kualifikasi/1/dokumen', 'test.pdf'), (error) => error instanceof ApiError && error.status === 403)
})

test('HTML response is never downloaded as a PDF', async (t) => {
  session(t)
  fetch.mock.mockImplementation(async () => new Response('<html>Login</html>', { headers: { 'Content-Type': 'text/html' } }))
  await assert.rejects(downloadDocument('/kualifikasi/1/dokumen', 'test.pdf'), (error) => error instanceof ApiError && error.status === 502)
})

test('successful PDF download creates and cleans a blob URL without exposing the token', async (t) => {
  session(t)
  fetch.mock.mockImplementation(async () => new Response('%PDF-1.4', { headers: { 'Content-Type': 'application/pdf' } }))
  t.mock.method(URL, 'createObjectURL', () => 'blob:demo-document')
  t.mock.method(URL, 'revokeObjectURL')
  t.mock.method(globalThis, 'setTimeout', (callback) => { callback(); return 1 })
  const previous = globalThis.document
  let clicked = false
  let removed = false
  const link = { click: () => { clicked = true }, remove: () => { removed = true } }
  globalThis.document = { createElement: () => link, body: { appendChild: () => {} } }
  t.after(() => { globalThis.document = previous })
  await downloadDocument('/riwayat-jabatan/1/dokumen', '../sk.pdf')
  assert.equal(clicked, true)
  assert.equal(removed, true)
  assert.equal(link.href, 'blob:demo-document')
  assert.equal(link.download, '.._sk.pdf')
  assert.equal(URL.revokeObjectURL.mock.calls[0].arguments[0], 'blob:demo-document')
})
