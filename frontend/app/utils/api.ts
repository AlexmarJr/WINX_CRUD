export function apiBaseUrl(): string {
  const configured = useRuntimeConfig().public.apiBaseUrl

  if (configured) return configured.replace(/\/$/, '')

  return `http://${window.location.hostname}:8000`
}

function xsrfToken(): string {
  const cookie = document.cookie.split('; ').find(value => value.startsWith('XSRF-TOKEN='))
  return cookie ? decodeURIComponent(cookie.substring('XSRF-TOKEN='.length)) : ''
}

export async function apiGet<T>(path: string, query?: Record<string, string | number>, signal?: AbortSignal): Promise<T> {
  return await $fetch<T>(`${apiBaseUrl()}${path}`, {
    credentials: 'include',
    headers: { Accept: 'application/json' },
    query,
    signal
  })
}

export async function apiWrite<T>(path: string, method: 'POST' | 'PATCH' | 'DELETE', body?: object): Promise<T> {
  await $fetch(`${apiBaseUrl()}/sanctum/csrf-cookie`, {
    credentials: 'include',
    headers: { Accept: 'application/json' }
  })

  return await $fetch<T>(`${apiBaseUrl()}${path}`, {
    method,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'X-XSRF-TOKEN': xsrfToken()
    },
    body
  })
}

export function apiErrorMessage(error: unknown, fallback: string): string {
  const response = error as { data?: { message?: string, errors?: Record<string, string[]> } }
  const firstError = Object.values(response.data?.errors ?? {})[0]?.[0]

  return firstError || response.data?.message || fallback
}
