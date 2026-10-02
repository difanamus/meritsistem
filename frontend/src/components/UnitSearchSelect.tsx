import { useEffect, useId, useRef, useState } from 'react'
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
  source?: 'unit' | 'personel'
  onChange: (value: string, unit?: ReferenceItem) => void
}

export function UnitSearchSelect({ label, value, current, excludeId, disabled = false, required = true, error, source = 'unit', onChange }: Props) {
  const [search, setSearch] = useState('')
  const [options, setOptions] = useState<ReferenceItem[]>([])
  const [selected, setSelected] = useState<ReferenceItem | null>(null)
  const [loading, setLoading] = useState(false)
  const [failed, setFailed] = useState(false)
  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState(false)
  const [highlight, setHighlight] = useState(-1)
  const input = useRef<HTMLInputElement>(null)
  const listId = useId()
  const chosen = selected?.id === Number(value) ? selected : current?.id === Number(value) ? current : null
  const displayed = editing ? search : chosen?.nama ?? search

  useEffect(() => { input.current?.setCustomValidity(required && !value ? 'Pilih salah satu hasil pencarian.' : '') }, [value, required])

  useEffect(() => {
    const term = search.trim()
    if (disabled || term.length < 3) return
    let active = true
    const timer = window.setTimeout(() => {
      setLoading(true)
      setFailed(false)
      const request = source === 'unit'
        ? apiRequest<{ data: { unit_organisasi: ReferenceItem[] } }>(`/reference-options?only=unit_organisasi&search=${encodeURIComponent(term)}`).then((response) => response.data.unit_organisasi)
        : apiRequest<{ data: { id: number; nama_lengkap: string; nomor_identitas: string }[] }>(`/personel-options?search=${encodeURIComponent(term)}`).then((response) => response.data.map((person) => ({ id: person.id, nama: person.nama_lengkap, kode: person.nomor_identitas })))
      void request.then((items) => { if (active) setOptions(items.filter((item) => item.id !== excludeId)) })
        .catch(() => { if (active) { setOptions([]); setFailed(true) } })
        .finally(() => { if (active) setLoading(false) })
    }, 250)
    return () => { active = false; window.clearTimeout(timer) }
  }, [search, excludeId, disabled, source])

  const select = (item: ReferenceItem) => {
    setSelected(item); setSearch(''); setEditing(false); setOpen(false); setOptions([]); setHighlight(-1)
    onChange(String(item.id), item)
  }

  return <label className="wide unit-search-field"><span>{label}</span>
    <div className="search-combobox">
      <input ref={input} value={displayed} disabled={disabled} required={required} role="combobox" aria-autocomplete="list" aria-expanded={open && editing} aria-controls={open && editing ? listId : undefined} aria-activedescendant={open && highlight >= 0 ? `${listId}-${highlight}` : undefined} autoComplete="off"
        placeholder={source === 'unit' ? 'Cari nama atau kode Satker (minimal 3 karakter)' : 'Cari nama atau NRP/NIP (minimal 3 karakter)'}
        onFocus={() => setOpen(true)} onBlur={() => setOpen(false)}
        onChange={(event) => { setEditing(true); setSearch(event.target.value); setSelected(null); setOptions([]); setHighlight(-1); setOpen(true); setLoading(event.target.value.trim().length >= 3); setFailed(false); onChange('') }}
        onKeyDown={(event) => {
          if (event.key === 'Escape') { setOpen(false); return }
          if (event.key === 'ArrowDown' || event.key === 'ArrowUp') { event.preventDefault(); setOpen(true); setHighlight((index) => options.length ? index < 0 ? event.key === 'ArrowDown' ? 0 : options.length - 1 : (index + (event.key === 'ArrowDown' ? 1 : -1) + options.length) % options.length : -1) }
          if (event.key === 'Enter' && open && highlight >= 0 && options[highlight]) { event.preventDefault(); select(options[highlight]) }
        }} />
      {open && editing && <div className="combobox-options" id={listId} role="listbox" aria-label={`Hasil ${label}`}>
        {options.map((item, index) => <div key={item.id} id={`${listId}-${index}`} role="option" aria-selected={highlight === index} className={highlight === index ? 'highlighted' : ''} onMouseDown={(event) => { event.preventDefault(); select(item) }}><strong>{item.nama}</strong>{item.kode && <small>{item.kode}</small>}</div>)}
        {options.length === 0 && <div className="combobox-message">{failed ? 'Pencarian gagal. Ubah kata kunci untuk mencoba lagi.' : loading ? 'Mencari…' : search.trim().length < 3 ? 'Ketik minimal 3 karakter.' : 'Tidak ada hasil yang cocok.'}</div>}
        {options.length === 25 && <div className="combobox-message">25 hasil pertama. Perjelas kata kunci.</div>}
      </div>}
    </div>
    {!disabled && chosen && !editing && <small className="unit-search-hint">Terpilih: {chosen.nama}{chosen.kode ? ` (${chosen.kode})` : ''}</small>}
    {error && <small role="alert">{error}</small>}
  </label>
}
