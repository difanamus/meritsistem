const API_URL = import.meta.env?.VITE_API_URL ?? 'http://localhost:8000/api/v1'
const TOKEN_KEY = 'merit_system_token'

export class ApiError extends Error {
  status: number
  errors: Record<string, string[]>

  constructor(message: string, status: number, errors: Record<string, string[]> = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token: string | null) {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token)
  } else {
    localStorage.removeItem(TOKEN_KEY)
  }
}

export async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = getToken()
  const isFormData = options.body instanceof FormData
  const headers = new Headers(options.headers)

  headers.set('Accept', 'application/json')
  if (!isFormData) headers.set('Content-Type', 'application/json')
  if (token) headers.set('Authorization', `Bearer ${token}`)

  const response = await fetch(`${API_URL}${path}`, { ...options, headers })
  const payload = await response.json().catch(() => {
    throw new ApiError('Respons layanan tidak valid. Periksa konfigurasi backend.', response.status)
  })

  if (!response.ok) {
    throw new ApiError(
      payload.message ?? 'Permintaan tidak dapat diproses.',
      response.status,
      payload.errors ?? {},
    )
  }

  return payload as T
}

export function toQueryString(params: Record<string, string | number | undefined>) {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== '') search.set(key, String(value))
  })
  const query = search.toString()
  return query ? `?${query}` : ''
}

export async function downloadDocument(path: string, filename: string) {
  const headers = new Headers({ Accept: 'application/json' })
  const token = getToken()
  if (token) headers.set('Authorization', `Bearer ${token}`)
  const response = await fetch(`${API_URL}${path}`, { headers })
  if (!response.ok) {
    const payload = await response.json().catch(() => ({}))
    throw new ApiError(payload.message ?? 'Dokumen tidak dapat diunduh.', response.status)
  }
  const blob = await response.blob()
  if (!blob.type.toLowerCase().includes('pdf')) throw new ApiError('Respons dokumen bukan PDF.', 502)
  const objectUrl = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = objectUrl
  link.download = filename.replace(/[\\/]/g, '_')
  document.body.appendChild(link)
  link.click()
  link.remove()
  setTimeout(() => URL.revokeObjectURL(objectUrl), 1000)
}
