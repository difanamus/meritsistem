export const personnelColumns = [
  { key: 'nama', label: 'Personel', defaultDirection: 'asc' },
  { key: 'jabatan', label: 'Jabatan saat ini', defaultDirection: 'asc' },
  { key: 'satker', label: 'Satker', defaultDirection: 'asc' },
  { key: 'jumlah_kualifikasi', label: 'Kualifikasi relevan', defaultDirection: 'desc' },
  { key: 'durasi_pengalaman', label: 'Pengalaman', defaultDirection: 'desc' },
  { key: 'jumlah_operasi', label: 'Operasi', defaultDirection: 'desc' },
] as const

export function personnelSortParams(current: URLSearchParams, key: string, defaultDirection = 'asc', toggle = false) {
  const next = new URLSearchParams(current)
  const sameColumn = (current.get('sort') ?? 'nama') === key
  const direction = toggle && sameColumn ? ((current.get('direction') ?? 'asc') === 'asc' ? 'desc' : 'asc') : defaultDirection
  next.set('sort', key)
  next.set('direction', direction)
  next.delete('page')
  return next
}
