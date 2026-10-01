import { test } from 'node:test'
import assert from 'node:assert/strict'
import { historyBody, isActivePrimary, pdfError } from './historyForm.ts'

test('creation sends explicit empty optional fields and no method override', () => {
  const body = historyBody({ nama_kualifikasi: 'Pelatihan', bidang_fungsi_id: '' }, null, 'dokumen_pendukung', null, false)
  assert.equal(body.get('nama_kualifikasi'), 'Pelatihan')
  assert.equal(body.get('bidang_fungsi_id'), '')
  assert.equal(body.has('_method'), false)
})

test('editing sends changed fields including cleared values and preserves unchanged references', () => {
  const original = { nama_kualifikasi: 'A', bidang_fungsi_id: '7', jenjang: 'S2' }
  const body = historyBody({ ...original, jenjang: '' }, original, 'dokumen_pendukung', null, false)
  assert.deepEqual([...body.entries()], [['_method', 'PUT'], ['jenjang', '']])
})

test('changing one date sends both dates for cross-field validation', () => {
  const original = { tanggal_mulai: '2024-01-01', tanggal_selesai: '2024-02-01' }
  const body = historyBody({ ...original, tanggal_selesai: '' }, original, 'dokumen_sk', null, false)
  assert.equal(body.get('tanggal_mulai'), '2024-01-01')
  assert.equal(body.get('tanggal_selesai'), '')
})

test('replacement file takes precedence over document removal', () => {
  const file = new File(['%PDF-1.4'], 'sk.pdf', { type: 'application/pdf' })
  const body = historyBody({}, {}, 'dokumen_sk', file, true)
  assert.equal(body.get('dokumen_sk').name, 'sk.pdf')
  assert.equal(body.has('hapus_dokumen'), false)
  assert.equal(historyBody({}, {}, 'dokumen_sk', null, true).get('hapus_dokumen'), '1')
})

test('PDF validation allows optional documents and 5 MB but rejects wrong type or oversized files', () => {
  assert.equal(pdfError(null), '')
  assert.equal(pdfError({ name: 'SK.PDF', size: 5 * 1024 * 1024, type: 'application/pdf' }), '')
  assert.equal(pdfError({ name: 'sk.exe', size: 10, type: 'application/pdf' }), 'Dokumen harus berupa PDF.')
  assert.equal(pdfError({ name: 'sk.pdf', size: 10, type: 'text/plain' }), 'Dokumen harus berupa PDF.')
  assert.equal(pdfError({ name: 'sk.pdf', size: 5 * 1024 * 1024 + 1, type: 'application/pdf' }), 'Ukuran PDF maksimal 5 MB.')
})

test('primary position without an end date is locked while additional or completed positions are not', () => {
  assert.equal(isActivePrimary({ is_jabatan_utama: true, tanggal_selesai: null }), true)
  assert.equal(isActivePrimary({ is_jabatan_utama: false, tanggal_selesai: null }), false)
  assert.equal(isActivePrimary({ is_jabatan_utama: true, tanggal_selesai: '2024-12-31' }), false)
})
