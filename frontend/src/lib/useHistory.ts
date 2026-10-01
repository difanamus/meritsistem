import { useEffect, useState } from 'react'
import { ApiError, apiRequest } from './api'
import type { PaginationMeta } from '../types'

export function useHistory<T>(personnelId: number, kind: string) {
  const [items, setItems] = useState<T[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [page, setPage] = useState(1)
  const [revision, setRevision] = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  useEffect(() => {
    let active = true
    apiRequest<{ data: T[]; meta: PaginationMeta }>(`/personel/${personnelId}/${kind}?page=${page}`)
      .then((response) => {
        if (!active) return
        if (page > response.meta.last_page) { setPage(response.meta.last_page); return }
        setItems(response.data); setMeta(response.meta); setError('')
      }).catch((exception) => { if (active) setError(exception instanceof ApiError ? exception.message : 'Riwayat tidak dapat dimuat.') })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [personnelId, kind, page, revision])
  const changePage = (next: number) => { setLoading(true); setPage(next) }
  const reload = () => { setLoading(true); setRevision((value) => value + 1) }
  return { items, meta, page, loading, error, changePage, reload }
}
