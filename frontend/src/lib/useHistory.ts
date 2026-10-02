import { useCallback, useEffect, useState } from 'react'
import { ApiError, apiRequest, peekApiCache } from './api'
import type { PaginationMeta } from '../types'

export function useHistory<T>(personnelId: number, kind: string) {
  const path = useCallback((page: number) => `/personel/${personnelId}/${kind}?page=${page}`, [personnelId, kind])
  const cachedFirstPage = peekApiCache<{ data: T[]; meta: PaginationMeta }>(path(1))
  const [items, setItems] = useState<T[]>(() => cachedFirstPage?.data ?? [])
  const [meta, setMeta] = useState<PaginationMeta | null>(() => cachedFirstPage?.meta ?? null)
  const [page, setPage] = useState(1)
  const [revision, setRevision] = useState(0)
  const [loading, setLoading] = useState(!cachedFirstPage)
  const [error, setError] = useState('')
  useEffect(() => {
    let active = true
    apiRequest<{ data: T[]; meta: PaginationMeta }>(path(page))
      .then((response) => {
        if (!active) return
        if (page > response.meta.last_page) { setPage(response.meta.last_page); return }
        setItems(response.data); setMeta(response.meta); setError('')
      }).catch((exception) => { if (active) { if (exception instanceof ApiError && [401, 403, 404].includes(exception.status)) { setItems([]); setMeta(null) }; setError(exception instanceof ApiError ? exception.message : 'Riwayat tidak dapat dimuat.') } })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [path, page, revision])
  const changePage = (next: number) => {
    const cached = peekApiCache<{ data: T[]; meta: PaginationMeta }>(path(next))
    if (cached) { setItems(cached.data); setMeta(cached.meta) }
    setLoading(!cached)
    setPage(next)
  }
  const reload = () => { setLoading(true); setRevision((value) => value + 1) }
  return { items, meta, page, loading, error, changePage, reload }
}
