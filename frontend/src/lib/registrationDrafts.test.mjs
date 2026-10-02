import { test } from 'node:test'
import assert from 'node:assert/strict'
import { appendRegistrationData, emptyRegistrationRecord, initialRegistrationHistories } from './registrationDrafts.ts'

test('initial registration has separate education and no optional history rows', () => {
  const draft = initialRegistrationHistories()
  assert.deepEqual(draft.pendidikan_umum.values, {})
  assert.deepEqual(draft.pendidikan_polri.values, {})
  for (const key of ['kualifikasi', 'riwayat_jabatan', 'penugasan_operasi', 'prestasi', 'penghargaan']) assert.deepEqual(draft[key], [])
  assert.notEqual(draft.pendidikan_umum.id, draft.pendidikan_polri.id)
})

test('registration uses bracket notation and excludes internal UI fields and blank optional values', () => {
  const body = new FormData()
  appendRegistrationData(body, 'kualifikasi[0]', { ...emptyRegistrationRecord(), values: { nama_kualifikasi: 'Kejuruan Intelijen', tahun: '2024', bidang_fungsi_id: '' }, unit: { id: 1, nama: 'Tidak dikirim' } }, 'dokumen_pendukung')
  assert.deepEqual([...body.entries()], [['kualifikasi[0][nama_kualifikasi]', 'Kejuruan Intelijen'], ['kualifikasi[0][tahun]', '2024']])
})

test('initial PDF uses the correct nested document key without changing its filename', () => {
  const body = new FormData()
  const file = new File(['%PDF-1.4'], 'ijazah.pdf', { type: 'application/pdf' })
  appendRegistrationData(body, 'pendidikan_umum', { values: { nama_kualifikasi: 'SMA' }, file }, 'dokumen_pendukung')
  assert.equal(body.get('pendidikan_umum[dokumen_pendukung]').name, 'ijazah.pdf')
  assert.equal(body.get('pendidikan_umum[nama_kualifikasi]'), 'SMA')
})

test('initial main position sends nivelering and notes with the existing API keys', () => {
  const body = new FormData()
  appendRegistrationData(body, 'jabatan_utama', { values: { nama_jabatan: 'Banit Sat Intelkam', jenis_penugasan_id: '1', nivelering: 'IV', keterangan: 'Sesuai SK pengangkatan.' }, file: null }, 'dokumen_sk')
  assert.equal(body.get('jabatan_utama[nivelering]'), 'IV')
  assert.equal(body.get('jabatan_utama[keterangan]'), 'Sesuai SK pengangkatan.')
  assert.equal(body.get('jabatan_utama[jenis_penugasan_id]'), '1')

  const optionalBody = new FormData()
  appendRegistrationData(optionalBody, 'jabatan_utama', { values: { nama_jabatan: 'Banit Sat Intelkam', nivelering: '', keterangan: '' }, file: null }, 'dokumen_sk')
  assert.deepEqual([...optionalBody.entries()], [['jabatan_utama[nama_jabatan]', 'Banit Sat Intelkam']])
})
