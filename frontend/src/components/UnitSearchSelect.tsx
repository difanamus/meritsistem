import { useEffect, useState } from 'react'
import { apiRequest } from '../lib/api'
import type { ReferenceItem } from '../types'

interface Props {
  label: string
  value: string
  current?: ReferenceItem | null
  excludeId?: number
  disabled?: boolean
  required?: boolean
  error?: string
  onChange: (value: string, unit?: ReferenceItem) => void
}

export function UnitSearchSelect({ label, value, current, excludeId, disabled = false, required = true, error, onChange }: Props) {
  const [search, setSearch] = useState('')
  const [options, setOptions] = useState<ReferenceItem[]>([])
  const [selected, setSelected] = useState<ReferenceItem | null>(null)
  const [loading, setLoading] = useState(false)
  const [failed, setFailed] = useState(false)

  useEffect(() => {
    const term = search.trim()
    if (disabled || term.length < 3) return
    let active = true
    const timer = window.setTimeout(() => {
      setLoading(true)
      setFailed(false)
      void apiRequest<{ data: { unit_organisasi: ReferenceItem[] } }>(`/reference-options?only=unit_organisasi&search=${encodeURIComponent(term)}`)
        .then((response) => { if (active) setOptions(response.data.unit_organisasi.filter((unit) => unit.id !== excludeId)) })
        .catch(() => { if (active) { setOptions([]); setFailed(true) } })
        .finally(() => { if (active) setLoading(false) })
    }, 250)
    return () => { active = false; window.clearTimeout(timer) }
  }, [search, excludeId, disabled])

  const chosen = selected?.id === Number(value) ? selected : current?.id === Number(value) ? current : null
  const visible = chosen && !options.some((item) => item.id === chosen.id) ? [chosen, ...options] : options

  return <label className="wide unit-search-field"><span>{label}</span>
    {!disabled && <input type="search" value={search} onChange={(event) => { setSearch(event.target.value); setOptions([]); setLoading(false); setFailed(false) }} placeholder="Ketik minimal 3 huruf nama atau kode Satker" aria-label={`Cari ${label.toLowerCase()}`} />}
    <select value={value} disabled={disabled} required={required} onChange={(event) => {
      const unit = visible.find((item) => item.id === Number(event.target.value))
      setSelected(unit ?? null)
      onChange(event.target.value, unit)
    }}>
      <option value="">{disabled ? 'Unit tidak tersedia' : 'Pilih unit dari hasil pencarian'}</option>
      {visible.map((unit) => <option key={unit.id} value={unit.id}>{unit.nama}{unit.kode ? ` (${unit.kode})` : ''}</option>)}
    </select>
    {!disabled && <small className="unit-search-hint">{failed ? 'Pencarian gagal. Coba lagi.' : loading ? 'Mencari Satker…' : search.trim().length < 3 ? 'Cari berdasarkan nama atau kode Satker, minimal 3 karakter.' : options.length === 25 ? 'Menampilkan 25 hasil pertama; perjelas kata kunci.' : options.length === 0 ? 'Tidak ada unit yang cocok.' : `${options.length} unit ditemukan.`}</small>}
    {error && <small role="alert">{error}</small>}
  </label>
}
