export class ApiError extends Error {
  status: number
  errors: Record<string, string[]>

  constructor(status: number, message: string, errors: Record<string, string[]>) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

function xsrfToken(): string | null {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)
  const value = match?.[1]
  return value ? decodeURIComponent(value) : null
}

let csrf: Promise<void> | null = null

export function ensureCsrf(): Promise<void> {
  csrf ??= api('/sanctum/csrf-cookie').then((response) => {
    if (!response.ok) {
      csrf = null
      throw new ApiError(response.status, 'Cererea de securitate a eșuat.', {})
    }
  })
  return csrf
}

export async function api(path: string, init: RequestInit = {}): Promise<Response> {
  const headers = new Headers(init.headers)

  if (!headers.has('Accept')) {
    headers.set('Accept', 'application/json')
  }

  const token = xsrfToken()
  if (token) {
    headers.set('X-XSRF-TOKEN', token)
  }

  return fetch(path, {
    ...init,
    headers,
    credentials: 'include',
  })
}

export async function apiJson<T>(path: string, init: RequestInit = {}, retry = true): Promise<T> {
  const headers = new Headers(init.headers)
  if (init.body && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }

  const response = await api(path, { ...init, headers })
  if (response.status === 419 && retry) {
    csrf = null
    await ensureCsrf()
    return apiJson<T>(path, init, false)
  }
  if (response.status === 204) {
    return undefined as T
  }

  const data = await response.json().catch(() => ({}))
  if (!response.ok) {
    const errors = data && typeof data === 'object' && data.errors && typeof data.errors === 'object'
      ? data.errors as Record<string, string[]>
      : {}
    const message = data && typeof data === 'object' && typeof data.message === 'string'
      ? data.message
      : 'Cererea a eșuat.'
    throw new ApiError(response.status, message, errors)
  }

  return data as T
}
